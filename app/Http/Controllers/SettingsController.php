<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\GoogleService;
use App\Services\MonitoringAuth;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $currentKey = MonitoringAuth::adminKey();
        $adminEmail = MonitoringAuth::adminEmail();

        $googleClientId = GoogleService::getClientId();
        $googleClientSecret = GoogleService::getClientSecret();
        $googleRedirectUri = GoogleService::getRedirectUri();
        $isGoogleConfigured = GoogleService::isConfigured();

        $adminGoogleConnected = MonitoringAuth::adminGoogleConnected();
        $adminGoogleEmail = MonitoringAuth::adminGoogleEmail();
        $adminGoogleName = MonitoringAuth::adminGoogleName();
        $adminGoogleAvatar = MonitoringAuth::adminGoogleAvatar();

        return view('settings.index', compact(
            'currentKey',
            'adminEmail',
            'googleClientId',
            'googleClientSecret',
            'googleRedirectUri',
            'isGoogleConfigured',
            'adminGoogleConnected',
            'adminGoogleEmail',
            'adminGoogleName',
            'adminGoogleAvatar'
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

    public function updateGoogleCredentials(Request $request)
    {
        $request->validate([
            'google_client_id' => 'nullable|string|max:500',
            'google_client_secret' => 'nullable|string|max:500',
            'google_redirect_uri' => 'nullable|url|max:500',
        ]);

        Setting::set('google_client_id', trim((string)$request->input('google_client_id', '')));
        Setting::set('google_client_secret', trim((string)$request->input('google_client_secret', '')));

        $redirectUri = trim((string)$request->input('google_redirect_uri', ''));
        if ($redirectUri !== '') {
            Setting::set('google_redirect_uri', $redirectUri);
        } else {
            Setting::set('google_redirect_uri', null);
        }

        return back()->with('success', 'Google OAuth credentials updated successfully!');
    }
}

