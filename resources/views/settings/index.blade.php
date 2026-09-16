@extends('layouts.app')

@section('title', 'System Settings')
@section('breadcrumb', 'System Settings')

@section('content')
<section class="content">
    <div class="page-intro">
        <div>
            <span class="welcome-label">ADMIN CONFIGURATION</span>
            <h1>System Settings</h1>
            <p>Manage administrator security and system access keys.</p>
        </div>
    </div>

    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap:20px;">
        <!-- Panel 1: Change Admin Key -->
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2>Change Admin Access Key</h2>
                    <p>Update the key used to log in to the administrator workspace.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('settings.updateKey') }}" style="display:flex; flex-direction:column; gap:16px;">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="current_key">Current Admin Key <span class="req">*</span></label>
                    <input class="form-control" id="current_key" name="current_key" type="password" required placeholder="Enter current admin key">
                </div>

                <div class="form-group">
                    <label class="form-label" for="new_key">New Admin Key <span class="req">*</span></label>
                    <input class="form-control" id="new_key" name="new_key" type="password" required minlength="6" placeholder="Enter new admin key (min. 6 characters)">
                </div>

                <div class="form-group">
                    <label class="form-label" for="new_key_confirmation">Confirm New Key <span class="req">*</span></label>
                    <input class="form-control" id="new_key_confirmation" name="new_key_confirmation" type="password" required minlength="6" placeholder="Re-enter new admin key">
                </div>

                <div style="margin-top:8px;">
                    <button class="primary-button btn-sm" type="submit">Update Admin Key</button>
                </div>
            </form>
        </div>

        <!-- Panel 2: Connected Google Account for Admin -->
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2>Admin Google Account</h2>
                    <p>Connect your personal/work Gmail account to sign in and send notifications.</p>
                </div>
            </div>

            <div style="display:flex; flex-direction:column; gap:16px;">
                @if($adminGoogleConnected)
                    <div style="display:flex; align-items:center; gap:14px; padding:16px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px;">
                        @if($adminGoogleAvatar)
                            <img src="{{ $adminGoogleAvatar }}" alt="{{ $adminGoogleName }}" style="width:48px; height:48px; border-radius:50%; border:2px solid #22c55e;">
                        @else
                            <div style="width:48px; height:48px; border-radius:50%; background:#22c55e; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:18px;">
                                {{ strtoupper(substr($adminGoogleEmail ?? 'A', 0, 1)) }}
                            </div>
                        @endif
                        <div style="flex:1;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <strong style="font-size:14px; color:#14532d;">{{ $adminGoogleName ?: 'Connected Google Account' }}</strong>
                                <span style="display:inline-block; font-size:10px; font-weight:700; background:#22c55e; color:#ffffff; padding:2px 6px; border-radius:4px;">CONNECTED</span>
                            </div>
                            <span style="font-size:12px; color:#166534;">{{ $adminGoogleEmail }}</span>
                        </div>
                    </div>

                    <!-- Disconnect Button -->
                    <form method="POST" action="{{ route('google.disconnect') }}" onsubmit="return confirm('Are you sure you want to disconnect this Google account?');">
                        @csrf
                        <button type="submit" class="secondary-button btn-sm" style="color:#ef4444; border-color:#fca5a5;">
                            Disconnect Google Account
                        </button>
                    </form>

                    <!-- Send Test Email via Gmail API -->
                    <div style="padding-top:14px; border-top:1px solid #e2e8f0;">
                        <h4 style="margin:0 0 8px 0; font-size:13px; color:#334155;">Test Gmail API Integration</h4>
                        <form method="POST" action="{{ route('google.testGmail') }}" style="display:flex; gap:8px;">
                            @csrf
                            <input class="form-control" name="recipient" type="email" placeholder="Recipient email" value="{{ $adminGoogleEmail }}" required style="font-size:12px;">
                            <button class="primary-button btn-sm" type="submit" style="white-space:nowrap;">Send Test Email</button>
                        </form>
                    </div>
                @else
                    <div style="padding:16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;">
                        <p style="margin:0 0 12px 0; font-size:13px; color:#475569;">
                            Connecting your Google account allows sending emails directly via your Gmail account and syncing calendar schedules.
                        </p>
                        @if($isGoogleConfigured)
                            <a href="{{ route('google.redirect', ['mode' => 'connect_admin']) }}" class="primary-button btn-sm" style="display:inline-flex; align-items:center; gap:8px; text-decoration:none;">
                                <svg width="16" height="16" viewBox="0 0 48 48">
                                    <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                                </svg>
                                <span>Connect Admin Google Account</span>
                            </a>
                        @else
                            <div style="color:#e11d48; font-size:12px; font-weight:600;">
                                ⚠️ Configure Google OAuth Credentials to enable connecting Google accounts.
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        </div>
    </div>
</section>
@endsection


