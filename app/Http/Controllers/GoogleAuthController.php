<?php

namespace App\Http\Controllers;

use App\Models\AdminEvent;
use App\Models\Appointment;
use App\Models\FaeUser;
use App\Models\Setting;
use App\Models\Task;
use App\Services\GoogleService;
use App\Services\MonitoringAuth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class GoogleAuthController extends Controller
{
    /**
     * Redirect user to Google OAuth consent screen.
     */
    public function redirect(Request $request)
    {
        if (!GoogleService::isConfigured()) {
            $msg = 'Google OAuth is not configured yet. Please configure the Google Client ID and Secret in System Settings or .env file.';
            if (MonitoringAuth::isAdmin()) {
                return redirect()->route('settings.index')->with('error', $msg);
            }
            if (MonitoringAuth::isFae()) {
                return redirect()->route('dashboard')->with('error', $msg);
            }
            return redirect()->route('access')->with('error', $msg);
        }


        if (!MonitoringAuth::isAdmin() && !MonitoringAuth::isFae()) {
            return redirect()->route('access')->with('error', 'Please log in with your Admin Key or FAE Code first to connect your Google account.');
        }

        $mode = MonitoringAuth::isAdmin() ? 'connect_admin' : 'connect_fae';
        $authUrl = GoogleService::getAuthUrl($mode);
        return redirect()->away($authUrl);
    }

    /**
     * Handle Google OAuth callback.
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            $errorMsg = $request->query('error_description', $request->query('error'));
            $returnRoute = MonitoringAuth::isAdmin() ? 'settings.index' : (MonitoringAuth::isFae() ? 'dashboard' : 'access');
            return redirect()->route($returnRoute)->with('error', 'Google authorization was cancelled or failed: ' . $errorMsg);
        }

        $code = (string) $request->query('code', '');
        $stateRaw = (string) $request->query('state', '');

        if (!$code) {
            $returnRoute = MonitoringAuth::isAdmin() ? 'settings.index' : (MonitoringAuth::isFae() ? 'dashboard' : 'access');
            return redirect()->route($returnRoute)->with('error', 'Missing authorization code from Google.');
        }

        $stateData = json_decode(base64_decode($stateRaw), true) ?: [];
        $mode = $stateData['mode'] ?? (MonitoringAuth::isAdmin() ? 'connect_admin' : 'connect_fae');

        $tokens = GoogleService::exchangeCode($code);
        if (!$tokens || empty($tokens['access_token'])) {
            $returnRoute = MonitoringAuth::isAdmin() ? 'settings.index' : (MonitoringAuth::isFae() ? 'dashboard' : 'access');
            return redirect()->route($returnRoute)->with('error', 'Failed to retrieve access token from Google. Please verify your Client ID & Secret.');
        }

        $userInfo = GoogleService::getUserInfo($tokens['access_token']);
        if (!$userInfo || empty($userInfo['email'])) {
            $returnRoute = MonitoringAuth::isAdmin() ? 'settings.index' : (MonitoringAuth::isFae() ? 'dashboard' : 'access');
            return redirect()->route($returnRoute)->with('error', 'Failed to retrieve user profile from Google.');
        }

        $googleEmail = strtolower(trim($userInfo['email']));

        // --- MODE: Connect Admin Account ---
        if ($mode === 'connect_admin' || MonitoringAuth::isAdmin()) {
            if (!MonitoringAuth::isAdmin()) {
                return redirect()->route('access')->with('error', 'Admin session expired. Please log in with your Admin Key first.');
            }

            GoogleService::saveAdminGoogleConnection($userInfo, $tokens);
            return redirect()->route('settings.index')->with('success', "Admin Google account ({$googleEmail}) connected successfully!");
        }

        // --- MODE: Connect FAE User Account ---
        if ($mode === 'connect_fae' || MonitoringAuth::isFae()) {
            $faeId = MonitoringAuth::faeId();
            if (!$faeId) {
                return redirect()->route('access')->with('error', 'User session expired. Please log in with your FAE Code first.');
            }

            GoogleService::saveFaeGoogleConnection($faeId, $userInfo, $tokens);
            return redirect()->route('dashboard')->with('success', "Your Google account ({$googleEmail}) has been connected successfully!");
        }

        return redirect()->route('access')->with('error', 'Please log in with your Admin Key or FAE Code first to connect your Google account.');
    }


    /**
     * Disconnect connected Google Account.
     */
    public function disconnect(Request $request)
    {
        if (MonitoringAuth::isAdmin()) {
            GoogleService::disconnectAdmin();
            return redirect()->route('settings.index')->with('success', 'Admin Google account has been disconnected.');
        }

        $faeId = MonitoringAuth::faeId();
        if ($faeId) {
            GoogleService::disconnectFae($faeId);
            return back()->with('success', 'Your Google account has been disconnected.');
        }

        return redirect()->route('access');
    }

    /**
     * Direct 1-Click Sync of Task or Appointment or Event to Google Calendar.
     */
    public function syncCalendar(Request $request)
    {
        $type = $request->input('type');
        $id = (int) $request->input('id');

        $accessToken = null;
        $refreshToken = null;
        $expiresAt = null;
        $onRefresh = null;

        if (MonitoringAuth::isAdmin()) {
            $accessToken = Setting::get('admin_google_access_token');
            $refreshToken = Setting::get('admin_google_refresh_token');
            $expiresAt = Setting::get('admin_google_token_expires_at');
            $onRefresh = function ($newToken, $newExpiresAt) {
                Setting::set('admin_google_access_token', $newToken);
                Setting::set('admin_google_token_expires_at', $newExpiresAt);
            };
        } else {
            $fae = MonitoringAuth::currentFae();
            if ($fae) {
                $accessToken = $fae->google_access_token;
                $refreshToken = $fae->google_refresh_token;
                $expiresAt = $fae->google_token_expires_at;
                $onRefresh = function ($newToken, $newExpiresAt) use ($fae) {
                    $fae->update([
                        'google_access_token' => $newToken,
                        'google_token_expires_at' => $newExpiresAt,
                    ]);
                };
            }
        }

        if (!$accessToken && !$refreshToken) {
            return back()->with('error', 'You must connect your Google account first before syncing to Google Calendar.');
        }

        $eventData = [];

        if ($type === 'task') {
            $task = Task::with('fae')->findOrFail($id);
            $eventData = [
                'title' => 'Deadline: ' . $task->task_name . ($task->course ? " ({$task->course})" : ''),
                'description' => "Monitoring System Task\nAssigned to: " . ($task->fae?->name ?? 'Unassigned') . "\nStatus: " . ucfirst($task->status) . "\nProgress: {$task->progress}%\nRegion: {$task->region}",
                'date' => $task->deadline,
            ];
        } elseif ($type === 'appointment') {

            $appointment = Appointment::findOrFail($id);
            $dateStr = Carbon::parse($appointment->appointment_date)->format('Y-m-d');
            $eventData = [
                'title' => 'Appointment: ' . $appointment->user_name,
                'description' => "Monitoring System Appointment\nRequested by: {$appointment->user_name}\nReason: {$appointment->reason}\nStatus: {$appointment->status}",
            ];

            if ($appointment->start_time && $appointment->end_time) {
                $eventData['start_datetime'] = $dateStr . ' ' . $appointment->start_time;
                $eventData['end_datetime'] = $dateStr . ' ' . $appointment->end_time;
            } else {
                $eventData['date'] = $dateStr;
            }
        } elseif ($type === 'event') {
            $event = AdminEvent::findOrFail($id);
            $eventData = [
                'title' => $event->title . ' [' . ucfirst($event->category) . ']',
                'description' => "Admin Calendar Event\nCategory: {$event->category}\nDescription: " . ($event->description ?? 'N/A'),
                'date' => $event->event_date,
                'end_date' => $event->end_date,
            ];
        } else {
            return back()->with('error', 'Invalid calendar sync item.');
        }

        $result = GoogleService::syncCalendarEvent(
            (string) $accessToken,
            $refreshToken ? (string) $refreshToken : null,
            $expiresAt ? (string) $expiresAt : null,
            $eventData,
            $onRefresh
        );

        if ($result['success']) {
            $msg = 'Successfully synced to your Google Calendar!';
            if (!empty($result['html_link'])) {
                $msg .= ' <a href="' . e($result['html_link']) . '" target="_blank" style="color:inherit; text-decoration:underline; font-weight:bold;">View in Google Calendar &rarr;</a>';
            }
            return back()->with('success', $msg);
        }

        return back()->with('error', 'Failed to sync to Google Calendar: ' . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * Send test email using Gmail API from connected Google account.
     */
    public function testGmail(Request $request)
    {
        $accessToken = null;
        $refreshToken = null;
        $expiresAt = null;
        $fromEmail = null;
        $onRefresh = null;

        if (MonitoringAuth::isAdmin()) {
            $accessToken = Setting::get('admin_google_access_token');
            $refreshToken = Setting::get('admin_google_refresh_token');
            $expiresAt = Setting::get('admin_google_token_expires_at');
            $fromEmail = Setting::get('admin_google_email');
            $onRefresh = function ($newToken, $newExpiresAt) {
                Setting::set('admin_google_access_token', $newToken);
                Setting::set('admin_google_token_expires_at', $newExpiresAt);
            };
        } else {
            $fae = MonitoringAuth::currentFae();
            if ($fae) {
                $accessToken = $fae->google_access_token;
                $refreshToken = $fae->google_refresh_token;
                $expiresAt = $fae->google_token_expires_at;
                $fromEmail = $fae->google_email ?: $fae->email;
                $onRefresh = function ($newToken, $newExpiresAt) use ($fae) {
                    $fae->update([
                        'google_access_token' => $newToken,
                        'google_token_expires_at' => $newExpiresAt,
                    ]);
                };
            }
        }

        if (!$accessToken && !$refreshToken) {
            return back()->with('error', 'You must connect your Google account before sending emails via Gmail API.');
        }

        $recipient = $request->input('recipient', $fromEmail);
        if (!$recipient) {
            return back()->with('error', 'Recipient email address is required.');
        }

        $subject = 'Test Message from Monitoring System (via Gmail API)';
        $bodyHtml = '
            <div style="font-family: Arial, sans-serif; max-width:600px; margin:0 auto; padding:20px; border:1px solid #e2e8f0; border-radius:8px;">
                <h2 style="color:#b52f32; margin-top:0;">Monitoring System Google Integration</h2>
                <p>Hello,</p>
                <p>This is a verification email sent directly from your connected Gmail account (<strong>' . e($fromEmail) . '</strong>) using the Google Gmail API.</p>
                <div style="background:#f8fafc; padding:12px; border-radius:6px; border-left:4px solid #10b981; font-size:13px; margin:16px 0;">
                    <strong>Integration Status:</strong> Active &amp; Verified<br>
                    <strong>Sent Time:</strong> ' . now()->toRfc850String() . '
                </div>
                <p style="font-size:12px; color:#64748b; margin-top:20px;">Monitoring System &copy; ' . date('Y') . '</p>
            </div>
        ';

        $result = GoogleService::sendGmailMessage(
            (string) $accessToken,
            $refreshToken ? (string) $refreshToken : null,
            $expiresAt ? (string) $expiresAt : null,
            $recipient,
            $subject,
            $bodyHtml,
            $onRefresh
        );

        if ($result['success']) {
            return back()->with('success', "Test email sent successfully to {$recipient} via your connected Gmail account!");
        }

        return back()->with('error', 'Failed to send email via Gmail API: ' . ($result['error'] ?? 'Unknown error'));
    }

    /**
     * Upload task report file/attachment to Google Drive.
     */
    public function uploadDrive(Request $request)
    {
        $accessToken = null;
        $refreshToken = null;
        $expiresAt = null;
        $onRefresh = null;

        if (MonitoringAuth::isAdmin()) {
            $accessToken = Setting::get('admin_google_access_token');
            $refreshToken = Setting::get('admin_google_refresh_token');
            $expiresAt = Setting::get('admin_google_token_expires_at');
            $onRefresh = function ($newToken, $newExpiresAt) {
                Setting::set('admin_google_access_token', $newToken);
                Setting::set('admin_google_token_expires_at', $newExpiresAt);
            };
        } else {
            $fae = MonitoringAuth::currentFae();
            if ($fae) {
                $accessToken = $fae->google_access_token;
                $refreshToken = $fae->google_refresh_token;
                $expiresAt = $fae->google_token_expires_at;
                $onRefresh = function ($newToken, $newExpiresAt) use ($fae) {
                    $fae->update([
                        'google_access_token' => $newToken,
                        'google_token_expires_at' => $newExpiresAt,
                    ]);
                };
            }
        }

        if (!$accessToken && !$refreshToken) {
            return redirect()->route('google.redirect')->with('error', 'Please connect your Google account first to access Google Drive.');
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = $file->getClientOriginalName();
            $mimeType = $file->getClientMimeType() ?: 'application/octet-stream';
            $contents = file_get_contents($file->getRealPath());
        } elseif ($request->has('filepath')) {
            $relPath = $request->input('filepath');
            $fullPath = storage_path('app/' . $relPath);
            if (!file_exists($fullPath)) {
                $fullPath = storage_path('app/public/' . $relPath);
            }
            if (!file_exists($fullPath)) {
                return back()->with('error', 'File not found on server.');
            }
            $filename = basename($fullPath);
            $mimeType = mime_content_type($fullPath) ?: 'application/octet-stream';
            $contents = file_get_contents($fullPath);
        } else {
            return back()->with('error', 'No file provided for Google Drive upload.');
        }

        // If message context or report details are provided, create 1 single Google Doc containing the message & embedded photo
        if ($request->filled('message') || $request->filled('author_name') || $request->filled('task_name')) {
            $authorName = $request->input('author_name') ?: 'User';
            $authorRole = $request->input('author_role') ?: 'FAE';
            $messageText = $request->input('message') ?: '';
            $dateText = $request->input('created_at') ?: now()->toDayDateTimeString();
            $progressText = $request->input('progress') ?: '';
            $statusText = $request->input('status') ?: '';
            $taskTitle = $request->input('task_name') ?: '';

            $isImage = str_starts_with($mimeType, 'image/');
            $base64Data = base64_encode($contents);

            $htmlDoc = '<!DOCTYPE html><html><head><meta charset="utf-8">';
            $htmlDoc .= '</head><body style="font-family: Arial, sans-serif; font-size: 11pt; line-height: 1.5; color: #1e293b; padding: 10pt;">';
            $htmlDoc .= '<h1 style="color: #0f172a; font-size: 18pt; margin-bottom: 4pt; border-bottom: 2pt solid #2563eb; padding-bottom: 4pt;">HYTEC POWER INC. &mdash; FAE PROGRESS REPORT</h1>';
            $htmlDoc .= '<p style="color: #64748b; font-size: 9pt; margin-top: 0; margin-bottom: 12pt;">Monitoring System &bull; Activity Log &bull; ' . htmlspecialchars($dateText) . '</p>';

            $htmlDoc .= '<table style="width: 100%; border-collapse: collapse; margin-bottom: 14pt; font-size: 10pt;" border="1" cellpadding="6" cellspacing="0">';
            $htmlDoc .= '<tr style="background-color: #f8fafc;"><td style="font-weight: bold; width: 130pt;">Submitted By:</td><td><strong>' . htmlspecialchars($authorName) . '</strong> [' . htmlspecialchars($authorRole) . ']</td></tr>';
            if ($taskTitle) {
                $htmlDoc .= '<tr><td style="font-weight: bold; background-color: #f8fafc;">Task Name:</td><td>' . htmlspecialchars($taskTitle) . '</td></tr>';
            }
            $htmlDoc .= '<tr><td style="font-weight: bold; background-color: #f8fafc;">Date &amp; Time:</td><td>' . htmlspecialchars($dateText) . '</td></tr>';
            if ($statusText) {
                $htmlDoc .= '<tr><td style="font-weight: bold; background-color: #f8fafc;">Status:</td><td><strong>' . htmlspecialchars($statusText) . '</strong></td></tr>';
            }
            if ($progressText) {
                $htmlDoc .= '<tr><td style="font-weight: bold; background-color: #f8fafc;">Progress:</td><td><strong>' . htmlspecialchars($progressText) . '</strong></td></tr>';
            }
            $htmlDoc .= '</table>';

            $htmlDoc .= '<h3 style="color: #1e293b; font-size: 12pt; margin-top: 14pt; margin-bottom: 4pt;">Message / Notes</h3>';
            $htmlDoc .= '<div style="background-color: #f8fafc; border-left: 4pt solid #2563eb; padding: 10pt 14pt; margin-bottom: 16pt; font-size: 11pt;">';
            $htmlDoc .= '<p style="margin: 0; white-space: pre-wrap;">' . nl2br(htmlspecialchars($messageText ?: '(No message text provided)')) . '</p>';
            $htmlDoc .= '</div>';

            if ($isImage && !empty($base64Data)) {
                $htmlDoc .= '<h3 style="color: #1e293b; font-size: 12pt; margin-top: 14pt; margin-bottom: 6pt;">Attached Photo / Evidence</h3>';
                $htmlDoc .= '<p style="text-align: center; margin-top: 8pt; margin-bottom: 6pt;">';
                $htmlDoc .= '<img src="data:' . $mimeType . ';base64,' . $base64Data . '" style="max-width: 520pt; max-height: 420pt; border: 1pt solid #cbd5e1;" alt="Report Photo Attachment">';
                $htmlDoc .= '</p>';
                $htmlDoc .= '<p style="text-align: center; font-size: 8.5pt; color: #64748b; margin-top: 2pt;">Attachment File: ' . htmlspecialchars($filename) . '</p>';
            }

            $htmlDoc .= '<hr style="border: none; border-top: 1pt solid #e2e8f0; margin-top: 24pt; margin-bottom: 8pt;">';
            $htmlDoc .= '<p style="font-size: 8pt; color: #94a3b8; text-align: center; margin: 0;">Hytec Power Inc. &copy; ' . date('Y') . ' &bull; Monitoring System</p>';
            $htmlDoc .= '</body></html>';

            $safeAuthor = preg_replace('/[^A-Za-z0-9_-]/', '_', $authorName);
            $docName = "Report_{$safeAuthor}_" . date('Y-m-d_His');

            // Upload directly as a single native Google Doc with embedded photo
            $result = GoogleService::uploadFileToDrive(
                (string) $accessToken,
                $refreshToken ? (string) $refreshToken : null,
                $expiresAt ? (string) $expiresAt : null,
                $htmlDoc,
                $docName,
                'text/html; charset=UTF-8',
                $onRefresh,
                "FAE Progress Report for {$authorName} - " . ($messageText ?: 'Photo & Update'),
                'application/vnd.google-apps.document'
            );

            if ($result['success']) {
                $msg = "Google Doc report ('{$docName}') with message and photo saved to Google Drive successfully!";
                $redirect = back()->with('success', $msg);
                if (!empty($result['web_view_link'])) {
                    $redirect = $redirect->with('success_link', $result['web_view_link']);
                }
                return $redirect;
            }

            return back()->with('error', 'Failed to save Google Doc to Google Drive: ' . ($result['error'] ?? 'Unknown error'));
        }

        // Standard raw file upload (e.g. from standalone Drive upload modal)
        $result = GoogleService::uploadFileToDrive(
            (string) $accessToken,
            $refreshToken ? (string) $refreshToken : null,
            $expiresAt ? (string) $expiresAt : null,
            $contents,
            $filename,
            $mimeType,
            $onRefresh
        );

        if ($result['success']) {
            $msg = "File '{$filename}' uploaded to Google Drive successfully!";
            $redirect = back()->with('success', $msg);
            if (!empty($result['web_view_link'])) {
                $redirect = $redirect->with('success_link', $result['web_view_link']);
            }
            return $redirect;
        }

        return back()->with('error', 'Failed to upload to Google Drive: ' . ($result['error'] ?? 'Unknown error'));
    }
}

