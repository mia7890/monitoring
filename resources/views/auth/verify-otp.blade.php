<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Two-Factor Verification | Monitoring System</title>
    <link rel="stylesheet" href="{{ asset_versioned('style.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        .otp-shell {
            max-width: 440px;
            margin: 60px auto;
            padding: 36px 32px;
            background: #ffffff;
            border: 1px solid var(--border, #e2e8f0);
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .otp-badge {
            width: 52px;
            height: 52px;
            margin: 0 auto 16px auto;
            border-radius: 50%;
            background: #fee2e2;
            color: #dc2626;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .otp-input {
            width: 100%;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 12px;
            text-align: center;
            padding: 12px 16px;
            border-radius: 8px;
            border: 2px solid #cbd5e1;
            outline: none;
            transition: all 0.2s ease;
            font-family: 'Courier New', Courier, monospace;
            box-sizing: border-box;
            color: #0f172a;
            background: #f8fafc;
        }

        .otp-input:focus {
            border-color: #b52f32;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(181, 47, 50, 0.15);
        }

        .otp-actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
        }

        .otp-secondary-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid #f1f5f9;
            font-size: 12px;
        }

        .btn-link {
            background: none;
            border: none;
            color: #b52f32;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: underline;
            padding: 0;
        }

        .btn-link:hover {
            color: #881e21;
        }
    </style>
</head>
<body class="access-page">
    <main class="otp-shell">
        <div class="otp-badge">
            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                <polyline points="9 11 12 14 22 4"/>
            </svg>
        </div>

        <p class="welcome-label" style="font-size: 10.5px; margin-bottom: 4px;">ADMIN SECURITY VERIFICATION</p>
        <h1 style="font-size: 22px; font-weight: 800; margin: 0 0 8px 0; color: #0f172a;">Two-Factor Authentication</h1>
        <p style="font-size: 13px; color: #64748b; line-height: 1.5; margin: 0 0 20px 0;">
            A 6-digit security code has been sent to:<br>
            <strong style="color: #0f172a;">{{ $maskedEmail }}</strong>
        </p>

        @if(session('error'))
            <div class="alert-banner error" style="margin-bottom: 16px;">{{ session('error') }}</div>
        @endif
        @if(session('success'))
            <div class="alert-banner success" style="margin-bottom: 16px;">{{ session('success') }}</div>
        @endif

        <form method="POST" action="{{ route('auth.otp.verify') }}" class="otp-actions">
            @csrf
            <div>
                <input type="text"
                       id="otp"
                       name="otp"
                       class="otp-input"
                       maxlength="6"
                       pattern="[0-9]{6}"
                       inputmode="numeric"
                       autocomplete="one-time-code"
                       placeholder="••••••"
                       required
                       autofocus>
            </div>

            <button type="submit" class="primary-button access-button" style="width: 100%; margin-top: 6px;">
                Verify &amp; Open Workspace
            </button>
        </form>

        <div class="otp-secondary-row">
            <form method="POST" action="{{ route('auth.otp.resend') }}" style="margin: 0;">
                @csrf
                <button type="submit" class="btn-link">Resend Code</button>
            </form>

            <form method="POST" action="{{ route('auth.otp.cancel') }}" style="margin: 0;">
                @csrf
                <button type="submit" class="btn-link" style="color: #64748b; text-decoration: none;">&larr; Cancel</button>
            </form>
        </div>
    </main>

    <script>
        // Auto-submit when 6 digits are typed
        const otpInput = document.getElementById('otp');
        if (otpInput) {
            otpInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length === 6) {
                    this.form.submit();
                }
            });
        }
    </script>
</body>
</html>
