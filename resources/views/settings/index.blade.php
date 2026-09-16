@extends('layouts.app')

@section('title', 'System Settings')
@section('breadcrumb', 'System Settings')

@section('content')
<section class="content">
    <div class="page-intro">
        <div>
            <span class="welcome-label">ADMIN CONFIGURATION</span>
            <h1>System Settings</h1>
            <p>Manage administrator security access keys and SMTP mail configuration.</p>
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

        <!-- Panel 2: SMTP Mail Delivery & Test -->
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2>SMTP Mail Server Configuration</h2>
                    <p>View active SMTP mail settings and test email delivery.</p>
                </div>
            </div>

            <div style="display:flex; flex-direction:column; gap:16px;">
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:16px; font-size:13px; color:#334155;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; border-bottom:1px solid #e2e8f0; padding-bottom:8px;">
                        <strong>SMTP Status</strong>
                        <span style="font-size:11px; font-weight:700; background:#10b981; color:#fff; padding:2px 8px; border-radius:12px;">ACTIVE (SMTP ONLY)</span>
                    </div>
                    <div style="display:grid; grid-template-columns: 120px 1fr; gap:6px 12px; font-size:12px;">
                        <span style="color:#64748b; font-weight:600;">Host:</span>
                        <span><code>{{ $smtpHost }}</code></span>
                        <span style="color:#64748b; font-weight:600;">Port:</span>
                        <span><code>{{ $smtpPort }}</code></span>
                        <span style="color:#64748b; font-weight:600;">Username / From:</span>
                        <span><code>{{ $smtpUsername }}</code></span>
                        <span style="color:#64748b; font-weight:600;">Sender Address:</span>
                        <span><code>{{ $smtpFromAddress }}</code></span>
                        <span style="color:#64748b; font-weight:600;">Admin Recovery:</span>
                        <span><code>{{ $adminEmail ?: 'Not Configured' }}</code></span>
                    </div>
                </div>

                <!-- Test SMTP Email Form -->
                <div style="padding-top:14px; border-top:1px solid #e2e8f0;">
                    <h4 style="margin:0 0 8px 0; font-size:13px; color:#334155;">Test Live SMTP Delivery</h4>
                    <form method="POST" action="{{ route('settings.testSmtp') }}" style="display:flex; gap:8px;">
                        @csrf
                        <input class="form-control" name="recipient" type="email" placeholder="Recipient email" value="{{ $adminEmail }}" required style="font-size:12px;">
                        <button class="primary-button btn-sm" type="submit" style="white-space:nowrap;">Send Test Email</button>
                    </form>
                    <small style="color:#64748b; font-size:11px; margin-top:6px; display:block;">
                        Dispatches a verification email via standard SMTP to verify server connectivity and recipient delivery.
                    </small>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
