<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Access | Monitoring System</title>
    <link rel="stylesheet" href="{{ asset_versioned('style.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }
        .access-page {
            height: 100vh;
            height: 100dvh;
            max-height: 100vh;
            box-sizing: border-box;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 12px 20px;
            background: #fafafa;
            overflow: hidden;
        }
        .access-shell {
            width: min(720px, 100%);
            text-align: center;
            box-sizing: border-box;
        }
        .back-home-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 6px;
            background: #b52f32;
            border: 1px solid var(--border, #e2e8f0);
            color: #ffffff;
            font-size: 11.5px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.18s ease;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }
        .back-home-btn:hover {
            background: #f8fafc;
            border-color: var(--primary, #b52f32);
            color: var(--primary, #b52f32);
            transform: translateX(-2px);
        }
        .access-mark {
            width: 64px;
            height: 64px;
            margin: 0 auto 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background: #ffffff;
            padding: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.07), 0 0 0 1px var(--border, #e2e8f0);
        }
        .access-mark img {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        .access-shell .welcome-label {
            display: block;
            font-size: 10px;
            font-weight: 800;
            letter-spacing: 1.2px;
            color: var(--primary, #b52f32);
            text-transform: uppercase;
            margin: 0 0 2px 0;
        }
        .access-shell h1 {
            margin: 0 0 3px 0;
            font-size: 21px;
            font-weight: 800;
            letter-spacing: -0.4px;
            color: #0f172a;
        }
        .access-intro {
            color: var(--text-secondary, #64748b);
            font-size: 12px;
            margin: 0 0 14px 0;
        }
        .access-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 12px;
            text-align: left;
        }
        .access-card {
            background: white;
            border: 1px solid var(--border, #e2e8f0);
            border-radius: 8px;
            padding: 14px 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .access-kicker {
            color: var(--primary, #b52f32);
            font-size: 9px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            display: block;
            margin-bottom: 2px;
        }
        .access-card h2 {
            font-size: 15px;
            font-weight: 700;
            margin: 0 0 3px 0;
            letter-spacing: -0.3px;
            color: #0f172a;
        }
        .access-card p {
            min-height: unset;
            color: var(--text-secondary, #64748b);
            font-size: 11.5px;
            line-height: 1.35;
            margin: 0 0 10px 0;
        }
        .access-card .form-label {
            display: block;
            margin-bottom: 3px;
            font-size: 11px;
            font-weight: 600;
            color: #334155;
        }
        .access-card .form-control {
            width: 100%;
            padding: 7px 10px;
            font-size: 12.5px;
            box-sizing: border-box;
        }
        .access-button {
            width: 100%;
            margin-top: 10px;
            padding: 8px 14px;
            font-size: 12.5px;
        }
        .reg-cta-banner {
            text-align: left;
            margin-top: 12px;
            padding: 10px 16px;
            background: #f1f5f9;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        @media (max-height: 560px), (max-width: 620px) {
            html, body, .access-page {
                height: auto;
                min-height: 100vh;
                overflow-y: auto;
            }
            .access-page {
                padding: 16px;
            }
            .access-grid {
                grid-template-columns: 1fr;
            }
            .reg-cta-banner {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</head>
<body class="access-page">
    <main class="access-shell">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 8px;">
            <a href="{{ route('landing') }}" class="back-home-btn" id="btnBackToHome">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                <span>Back to Home</span>
            </a>
            <span style="font-size:10.5px; color:var(--text-secondary); font-weight:700; letter-spacing:0.5px;">HYTEC POWER INC.</span>
        </div>

        <div class="access-mark">
            <img src="{{ asset_versioned('logo.jpg') }}" alt="Monitoring System logo">
        </div>
        <p class="welcome-label">MONITORING SYSTEM</p>
        <h1>Choose your workspace</h1>
        <p class="access-intro">Use the access details provided by your supervisor.</p>

        @if(session('error') || !empty($error))
            <div class="alert-banner error" style="margin-bottom: 10px; padding: 8px 12px; font-size: 12px;">{{ session('error') ?: $error }}</div>
        @endif
        @if(session('success'))
            <div class="alert-banner success" style="margin-bottom: 10px; padding: 8px 12px; font-size: 12px;">{{ session('success') }}</div>
        @endif

        <div class="access-grid">
            <!-- Contact Access Code Form -->
            <form method="POST" action="{{ route('login') }}" class="access-card">
                @csrf
                <input type="hidden" name="access_type" value="fae">
                <span class="access-kicker">CONTACT</span>
                <h2>Contacts Workpace</h2>
                <p>See only your assigned work and request time with the admin.</p>
                <label class="form-label" for="fae_code">Access Code</label>
                <input type="password" class="form-control" id="fae_code" name="fae_code" required autocomplete="off" placeholder="e.g. CTC-A8B2X" autofocus>
                <button class="primary-button access-button" type="submit">Open my workspace</button>
            </form>

            <!-- Admin Workspace Form -->
            <form method="POST" action="{{ route('login') }}" class="access-card access-card--admin">
                @csrf
                <input type="hidden" name="access_type" value="admin">
                <span class="access-kicker">SUPERVISOR</span>
                <h2>Admin workspace</h2>
                <p>Manage contacts, assign tasks, review progress, and handle appointments.</p>
                <label class="form-label" for="admin_key">Admin access key</label>
                <input class="form-control" id="admin_key" name="admin_key" type="password" required autocomplete="current-password" placeholder="Enter admin key">
                <button class="primary-button access-button" type="submit">Open admin workspace</button>

                <div style="margin-top: 8px; text-align: right;">
                    <button type="button" onclick="if(confirm('Send admin access key to registered admin email?')) { document.getElementById('forgotKeyForm').submit(); }" style="background:none; border:none; color:var(--primary, #b52f32); font-size:10.5px; font-weight:600; cursor:pointer; text-decoration:underline; padding:0;">
                        Forgot Admin Key?
                    </button>
                </div>
            </form>

            <form id="forgotKeyForm" method="POST" action="{{ route('forgot.admin.key') }}" style="display:none;">
                @csrf
            </form>
        </div>

        {{-- Registration CTA --}}
        <div class="reg-cta-banner">
            <div>
                <div style="margin:0 0 2px 0; font-size:12px; color:#1e293b; font-weight:700;">New here? Request an Access Code</div>
                <div style="font-size:11px; color:#64748b;">Get credentials to start booking appointments.</div>
            </div>
            <a href="{{ route('register') }}" style="flex-shrink:0; display:inline-flex; align-items:center; gap:5px; padding:7px 14px; background:linear-gradient(135deg, #b52f32, #8b1a1d); color:#ffffff; font-size:11.5px; font-weight:700; border-radius:6px; text-decoration:none; box-shadow:0 2px 6px rgba(181,47,50,0.25); transition:all 0.2s ease;" id="btnRegisterLink">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="8.5" cy="7" r="4"/><line x1="20" y1="8" x2="20" y2="14"/><line x1="23" y1="11" x2="17" y2="11"/></svg>
                Register Now
            </a>
        </div>

    </main>
</body>
</html>
