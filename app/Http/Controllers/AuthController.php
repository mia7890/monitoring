<?php

namespace App\Http\Controllers;

use App\Mail\AdminKeyMail;
use App\Models\FaeUser;
use App\Models\Setting;
use App\Services\MonitoringAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    public function showAccess(Request $request)
    {
        $error = $request->query('err', '');
        return view('access', compact('error'));
    }

    public function login(Request $request)
    {
        $accessType = $request->input('access_type');

        if ($accessType === 'admin') {
            if (!MonitoringAuth::adminKeyConfigured()) {
                return back()->with('error', 'The administrator workspace is not configured. Contact your administrator.');
            }

            $adminKey = (string)$request->input('admin_key', '');
            if (hash_equals(MonitoringAuth::adminKey(), $adminKey)) {
                // Direct login — no OTP verification
                $request->session()->regenerate();
                Session::put('monitoring_role', 'admin');
                Session::forget(['monitoring_fae_id', 'monitoring_fae_name', 'monitoring_fae_code']);
                return redirect()->route('dashboard');
            }
            return back()->with('error', 'The admin access key is not valid.');
        }

        if ($accessType === 'fae') {
            $faeCode = strtoupper(trim((string)$request->input('fae_code', '')));
            $fae = FaeUser::where('fae_code', $faeCode)->first();
            if ($fae) {
                $request->session()->regenerate();
                Session::put('monitoring_role', 'fae');
                Session::put('monitoring_fae_id', (int)$fae->id);
                Session::put('monitoring_fae_name', $fae->name);
                Session::put('monitoring_fae_code', $fae->fae_code);
                return redirect()->route('dashboard');
            }
            return back()->with('error', 'That FAE code was not found.');
        }

        return back()->with('error', 'Invalid workspace selection.');
    }

    public function directFaeLink(Request $request, string $code)
    {
        $faeCode = strtoupper(trim($code));
        $fae = FaeUser::where('fae_code', $faeCode)->first();
        if (!$fae) {
            return redirect()->route('access')->with('error', 'This FAE link is invalid or no longer active.');
        }

        $request->session()->regenerate();
        Session::put('monitoring_role', 'fae');
        Session::put('monitoring_fae_id', (int)$fae->id);
        Session::put('monitoring_fae_name', $fae->name);
        Session::put('monitoring_fae_code', $fae->fae_code);

        return redirect()->route('dashboard');
    }

    public function logout()
    {
        Session::flush();
        return redirect()->route('access');
    }

    public function forgotKey()
    {
        $adminEmail = MonitoringAuth::adminEmail();

        if (!$adminEmail) {
            return back()->with('error', 'Admin email recovery is not configured. Contact your system administrator.');
        }

        if (!MonitoringAuth::adminKeyConfigured()) {
            return back()->with('error', 'No admin key is configured yet.');
        }

        try {
            Mail::to($adminEmail)->send(new AdminKeyMail(MonitoringAuth::adminKey()));
            return back()->with('success', 'The admin key has been sent to the registered admin email address.');
        } catch (\Throwable $e) {
            Log::error('Failed to send admin key recovery email via SMTP: ' . $e->getMessage());
            return back()->with('error', 'Failed to send recovery email via SMTP: ' . $e->getMessage());
        }
    }
}
