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

    <div style="max-width:540px;">
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
    </div>
</section>
@endsection
