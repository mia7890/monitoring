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

        return view('settings.index', compact(
            'currentKey',
            'adminEmail'
        ));
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

    public function testSmtp(Request $request)
    {
        $request->validate([
            'recipient' => 'required|email|max:255',
        ]);

        $recipient = trim((string)$request->input('recipient'));

        try {
            Mail::to($recipient)->send(new TestSmtpMail($recipient));
            return back()->with('success', "Test email sent successfully to {$recipient} via SMTP!");
        } catch (\Throwable $e) {
            Log::error('SMTP test email failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to send test email via SMTP: ' . $e->getMessage());
        }
    }
}

