@extends('layouts.app')

@section('title', 'System Settings')
@section('breadcrumb', 'System Settings')

@section('content')
<section class="content">
    <div class="page-intro">
        <div>
            <span class="welcome-label">ADMIN CONFIGURATION</span>
            <h1>System Settings</h1>
            <p>Manage administrator security, system access keys, and mobile access links.</p>
        </div>
    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(340px, 1fr)); gap:20px; align-items:start;">
        <!-- Panel 0: Administrator Profile & Display Name -->
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2>Administrator Display Name</h2>
                    <p>Update the display name shown across the system and notifications.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('settings.updateName') }}" style="display:flex; flex-direction:column; gap:16px;">
                @csrf

                <div class="form-group">
                    <label class="form-label" for="admin_name">Admin Full Name / Display Name <span class="req">*</span></label>
                    <input class="form-control" id="admin_name" name="admin_name" type="text" required value="{{ old('admin_name', $adminName ?? 'Administrator') }}" placeholder="e.g. System Administrator">
                    <small style="font-size:10.5px; color:#64748b; margin-top:3px; display:block;">This name will be displayed in the top header and activity logs.</small>
                </div>

                <div style="margin-top:8px;">
                    <button class="primary-button btn-sm" type="submit">Save Administrator Name</button>
                </div>
            </form>
        </div>

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
                    <div style="position:relative; display:flex; align-items:center;">
                        <input class="form-control" id="current_key" name="current_key" type="password" required placeholder="Enter current admin key" style="padding-right:40px;">
                        <button type="button" class="btn-toggle-pw" data-target="current_key" title="Toggle visibility" style="position:absolute; right:10px; background:none; border:none; color:#64748b; cursor:pointer; padding:4px;">
                            <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="new_key">New Admin Key <span class="req">*</span></label>
                    <div style="position:relative; display:flex; align-items:center;">
                        <input class="form-control" id="new_key" name="new_key" type="password" required minlength="6" placeholder="Enter new admin key (min. 6 characters)" style="padding-right:40px;">
                        <button type="button" class="btn-toggle-pw" data-target="new_key" title="Toggle visibility" style="position:absolute; right:10px; background:none; border:none; color:#64748b; cursor:pointer; padding:4px;">
                            <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="new_key_confirmation">Confirm New Key <span class="req">*</span></label>
                    <div style="position:relative; display:flex; align-items:center;">
                        <input class="form-control" id="new_key_confirmation" name="new_key_confirmation" type="password" required minlength="6" placeholder="Re-enter new admin key" style="padding-right:40px;">
                        <button type="button" class="btn-toggle-pw" data-target="new_key_confirmation" title="Toggle visibility" style="position:absolute; right:10px; background:none; border:none; color:#64748b; cursor:pointer; padding:4px;">
                            <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                        </button>
                    </div>
                </div>

                <div style="margin-top:8px;">
                    <button class="primary-button btn-sm" type="submit">Update Admin Key</button>
                </div>
            </form>
        </div>

        <!-- Panel 2: Contact Registration QR Code & Link Generator -->
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2>Contact Registration Link &amp; QR Code</h2>
                    <p>Shareable registration link &amp; QR code for mobile onboarding.</p>
                </div>
            </div>

            <div style="display:flex; flex-direction:column; align-items:center; text-align:center; padding:12px 0;">
                <div id="qrCodeContainer" style="background:white; padding:12px; border-radius:12px; border:1px solid #e2e8f0; box-shadow:0 4px 12px rgba(0,0,0,0.05); margin-bottom:14px; display:inline-block;">
                    <img id="qrCodeImg" src="https://api.qrserver.com/v1/create-qr-code/?size=160x160&data={{ urlencode(route('register')) }}" alt="Contact Registration QR Code" style="width:160px; height:160px; display:block;">
                </div>

                <div style="width:100%; margin-bottom:12px;">
                    <label class="form-label" style="text-align:left; display:block; font-size:12px; margin-bottom:4px;">Contact Registration Link</label>
                    <div style="display:flex; gap:6px;">
                        <input type="text" id="hostUrlInput" class="form-control" value="{{ route('register') }}" readonly style="font-size:12px; background:#f8fafc;">
                        <button type="button" onclick="copyHostUrl()" class="outline-button btn-sm" style="flex-shrink:0;">Copy</button>
                    </div>
                </div>
                <p style="font-size:11.5px; color:#64748b; margin:0; line-height:1.4;">
                    Scan with any smartphone camera or tablet to open the Contact Registration Form directly.
                </p>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.btn-toggle-pw').forEach(button => {
            button.addEventListener('click', function () {
                const targetId = this.dataset.target;
                const input = document.getElementById(targetId);
                const eyeOpen = this.querySelector('.eye-open');
                const eyeClosed = this.querySelector('.eye-closed');

                if (input.type === 'password') {
                    input.type = 'text';
                    eyeOpen.style.display = 'none';
                    eyeClosed.style.display = 'block';
                } else {
                    input.type = 'password';
                    eyeOpen.style.display = 'block';
                    eyeClosed.style.display = 'none';
                }
            });
        });
    });

    function copyHostUrl() {
        const input = document.getElementById('hostUrlInput');
        input.select();
        input.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(input.value).then(() => {
            alert('Host URL copied to clipboard: ' + input.value);
        });
    }
</script>
@endsection
