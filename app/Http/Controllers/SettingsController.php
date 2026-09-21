<?php

namespace App\Http\Controllers;

use App\Mail\TestSmtpMail;
use App\Models\Setting;
use App\Services\MonitoringAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SettingsController extends Controller
{
    public function index()
    {
        $currentKey = MonitoringAuth::adminKey();
        $adminEmail = MonitoringAuth::adminEmail();
        $adminName = MonitoringAuth::adminName();

        return view('settings.index', compact(
            'currentKey',
            'adminEmail',
            'adminName'
        ));
    }

    public function updateName(Request $request)
    {
        $request->validate([
            'admin_name' => 'required|string|max:255',
        ]);

        $name = trim((string)$request->input('admin_name'));
        Setting::set('admin_name', $name);

        return back()->with('success', 'Administrator display name updated successfully!');
    }

    public function updateKey(Request $request)
    {
        $request->validate([
            'current_key' => 'required|string',
            'new_key' => 'required|string|min:6|confirmed',
        ]);

        $inputCurrentKey = (string)$request->input('current_key');
        $activeKey = MonitoringAuth::adminKey();

        if (!hash_equals($activeKey, $inputCurrentKey)) {
            return back()->with('error', 'The current admin key you entered is incorrect.');
        }

        $newKey = (string)$request->input('new_key');
        Setting::set('admin_key', $newKey);

        return back()->with('success', 'Admin access key updated successfully!');
    }

    public function updateEmail(Request $request)
    {
        $request->validate([
            'admin_email' => 'required|string|max:500',
        ]);

        $raw = trim((string)$request->input('admin_email'));

        // Validate each email in the comma-separated list
        $parts = preg_split('/[\s,;]+/', $raw);
        $validEmails = [];
        foreach ($parts as $part) {
            $cleaned = trim($part);
            if ($cleaned === '') continue;
            if (!filter_var($cleaned, FILTER_VALIDATE_EMAIL)) {
                return back()->with('error', "Invalid email address: {$cleaned}");
            }
            $validEmails[] = $cleaned;
        }

        if (empty($validEmails)) {
            return back()->with('error', 'Please enter at least one valid email address.');
        }

        Setting::set('admin_email', implode(', ', $validEmails));

        $count = count($validEmails);
        $msg = $count === 1
            ? "Admin notification email updated successfully!"
            : "Admin notification emails updated successfully! ({$count} emails)";

        return back()->with('success', $msg);
    }

    public function testSmtp(Request $request)
    {
        $request->validate([
            'recipient' => 'required|email|max:255',
        ]);

        $recipient = trim((string)$request->input('recipient'));
        $mailerDriver = config('mail.default');

        if ($mailerDriver === 'smtp' && !app()->runningUnitTests()) {
            $smtpHost = config('mail.mailers.smtp.host', 'smtp.gmail.com');
            $smtpPort = (int) config('mail.mailers.smtp.port', 465);

            $connection = @fsockopen($smtpHost, $smtpPort, $errno, $errstr, 2);
            if (!$connection) {
                Log::warning("SMTP server {$smtpHost}:{$smtpPort} unreachable from server: {$errstr} ({$errno})");
                return back()->with('error', "Outbound SMTP port {$smtpPort} to {$smtpHost} is blocked or unreachable by hosting network: {$errstr}");
            }
            fclose($connection);
        }

        try {
            @ini_set('default_socket_timeout', '5');
            config(['mail.mailers.smtp.timeout' => 5]);
            Mail::purge('smtp');

            Mail::to($recipient)->send(new TestSmtpMail($recipient));
            return back()->with('success', "Test email sent successfully to {$recipient} via SMTP!");
        } catch (\Throwable $e) {
            Log::error('SMTP test email failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to send test email via SMTP: ' . $e->getMessage());
        }
    }
}

