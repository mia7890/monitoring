<?php

namespace App\Http\Controllers;

use App\Mail\AdminKeyMail;
use App\Mail\ContactApprovedMail;
use App\Models\Department;
use App\Models\FaeUser;
use App\Models\Setting;
use App\Services\MonitoringAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showAccess(Request $request)
    {
        $error = $request->query('err', '');
        return view('access', compact('error'));
    }

    public function showRegister()
    {
        $departments = Department::orderBy('department_name')->get();
        return view('auth.register', compact('departments'));
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'department_id' => 'required|exists:departments,id',
        ], [
            'name.required' => 'Please enter your full name.',
            'email.required' => 'Please enter your email address.',
            'phone.required' => 'Please enter your phone number.',
            'department_id.required' => 'Please select your department.',
        ]);

        $name = trim((string)$request->input('name'));
        $email = strtolower(trim((string)$request->input('email')));
        
        // Check if email already registered
        $existing = FaeUser::where('email', $email)->first();
        if ($existing) {
            if ($existing->isApproved()) {
                return back()->withInput()->with('error', 'This email address is already registered. Your Access Code is: ' . $existing->fae_code . '. Please log in with this code.');
            }
            if ($existing->isPending()) {
                $accessCode = $existing->fae_code ?: $this->generateUniqueCode();
                $existing->update([
                    'status' => 'approved',
                    'fae_code' => $accessCode,
                ]);
                try {
                    Mail::to($email)->send(new ContactApprovedMail($existing->name, $accessCode));
                } catch (\Throwable $e) {
                    Log::error('Registration email delivery error: ' . $e->getMessage());
                }
                return redirect()->route('access')->with('success', "Registration approved! Your Access Code is: {$accessCode}. A copy has been sent to {$email}.");
            }
        }

        $accessCode = $this->generateUniqueCode();

        FaeUser::create([
            'name' => $name,
            'email' => $email,
            'phone' => trim((string)$request->input('phone', '')),
            'department_id' => $request->input('department_id') ? (int)$request->input('department_id') : null,
            'status' => 'approved',
            'fae_code' => $accessCode,
        ]);

        $emailSent = false;
        try {
            Mail::to($email)->send(new ContactApprovedMail($name, $accessCode));
            $emailSent = true;
        } catch (\Throwable $e) {
            Log::error('Registration instant email error: ' . $e->getMessage());
        }

        $msg = "Registration successful! Your Access Code is: {$accessCode}. " . ($emailSent ? "An email has also been sent to {$email}." : "Please save this Access Code to log in.");

        return redirect()->route('access')->with('success', $msg);
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
                if ($fae->isPending()) {
                    return back()->with('error', 'Your registration is currently pending administrator approval.');
                }
                if ($fae->status === 'rejected') {
                    return back()->with('error', 'Your registration request was not approved. Please contact the administrator.');
                }

                $request->session()->regenerate();
                Session::put('monitoring_role', 'fae');
                Session::put('monitoring_fae_id', (int)$fae->id);
                Session::put('monitoring_fae_name', $fae->name);
                Session::put('monitoring_fae_code', $fae->fae_code);
                return redirect()->route('dashboard');
            }
            return back()->with('error', 'That Access Code was not found.');
        }

        return back()->with('error', 'Invalid workspace selection.');
    }

    public function directFaeLink(Request $request, string $code)
    {
        $faeCode = strtoupper(trim($code));
        $fae = FaeUser::where('fae_code', $faeCode)->first();
        if (!$fae) {
            return redirect()->route('access')->with('error', 'This Access link is invalid or no longer active.');
        }

        if ($fae->isPending()) {
            return redirect()->route('access')->with('error', 'Your registration is currently pending administrator approval.');
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
        $adminEmails = MonitoringAuth::adminEmails();

        if (empty($adminEmails)) {
            return back()->with('error', 'Admin email recovery is not configured. Contact your system administrator.');
        }

        if (!MonitoringAuth::adminKeyConfigured()) {
            return back()->with('error', 'No admin key is configured yet.');
        }

        $adminKey = MonitoringAuth::adminKey();

        try {
            Mail::purge();

            Mail::to($adminEmails)->send(new AdminKeyMail($adminKey));
            $count = count($adminEmails);
            $msg = $count > 1 
                ? 'The admin key has been sent to all registered administrator email addresses.' 
                : 'The admin key has been sent to the registered admin email address.';
            return back()->with('success', $msg);
        } catch (\Throwable $e) {
            Log::error('Failed to send admin key recovery email: ' . $e->getMessage());
            return back()->with('success', "Notice: Could not deliver email ({$e->getMessage()}). Your Admin Access Key is: {$adminKey}");
        }
    }

    private function generateUniqueCode(): string
    {
        do {
            $code = 'CTC-' . strtoupper(Str::random(5));
        } while (FaeUser::where('fae_code', $code)->exists());

        return $code;
    }
}
