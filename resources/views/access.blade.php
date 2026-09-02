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
        .back-home-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 6px;
            background: #ffffff;
            border: 1px solid var(--border, #e2e8f0);
            color: var(--text, #1e293b);
            font-size: 12px;
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
    </style>
</head>
<body class="access-page">
    <main class="access-shell">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 16px;">
            <a href="{{ route('landing') }}" class="back-home-btn" id="btnBackToHome">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                <span>Back to Home</span>
            </a>
            <span style="font-size:11px; color:var(--text-secondary); font-weight:700; letter-spacing:0.5px;">HYTEC POWER INC.</span>
        </div>

        <div class="access-mark">
            <img src="{{ asset('logo.png') }}" alt="Monitoring System logo">
        </div>
        <p class="welcome-label">MONITORING SYSTEM</p>
        <h1>Choose your workspace</h1>
        <p class="access-intro">Use the access details provided by your supervisor.</p>

        @if(session('error') || !empty($error))
            <div class="alert-banner error">{{ session('error') ?: $error }}</div>
        @endif
        @if(session('success'))
            <div class="alert-banner success">{{ session('success') }}</div>
        @endif

        <div class="access-grid">
            <!-- FAE Workspace Form -->
            <form method="POST" action="{{ route('login') }}" class="access-card">
                @csrf
                <input type="hidden" name="access_type" value="fae">
                <span class="access-kicker">FIELD ENGINEER</span>
                <h2>FAE workspace</h2>
                <p>See only your assigned work and request time with the admin.</p>
                <label class="form-label" for="fae_code">FAE code</label>
                <input class="form-control" id="fae_code" name="fae_code" required autocomplete="off" placeholder="e.g. FAE-001" autofocus>
                <button class="primary-button access-button" type="submit">Open my workspace</button>
            </form>

            <!-- Admin Workspace Form -->
            <form method="POST" action="{{ route('login') }}" class="access-card access-card--admin">
                @csrf
                <input type="hidden" name="access_type" value="admin">
                <span class="access-kicker">SUPERVISOR</span>
                <h2>Admin workspace</h2>
                <p>Manage FAEs, assign tasks, review progress, and handle appointments.</p>
                <label class="form-label" for="admin_key">Admin access key</label>
                <input class="form-control" id="admin_key" name="admin_key" type="password" required autocomplete="current-password" placeholder="Enter admin key">
                <button class="primary-button access-button" type="submit">Open admin workspace</button>
            </form>
        </div>

        <div style="margin-top: 20px; text-align: center;">
            <a href="{{ route('landing') }}" style="color:var(--text-secondary); font-size:12px; text-decoration:none; font-weight:500;">
                ← Back to Home
            </a>
        </div>
    </main>
</body>
</html>
