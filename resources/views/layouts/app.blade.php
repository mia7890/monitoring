@php
    use App\Services\MonitoringAuth;
    use App\Services\NotificationService;
    $isAdmin = MonitoringAuth::isAdmin();
    $currentFaeId = MonitoringAuth::faeId();
    $currentFaeName = MonitoringAuth::faeName();
    $currentFaeCode = MonitoringAuth::faeCode();
    $notificationItems = NotificationService::notifications();
    $notificationCount = count($notificationItems);
    $notificationKey = hash('sha256', json_encode($notificationItems));
    $profileImage = MonitoringAuth::currentProfileImage();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Monitoring System') | Hytec Power Inc.</title>

    <link rel="stylesheet" href="{{ asset_versioned('style.css') }}">
    @stack('styles')

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="@yield('body_class')">

<div class="app">

    <!-- ================= SIDEBAR ================= -->
    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <div class="logo-icon">
                <img src="{{ asset('logo.png') }}" alt="Monitoring System logo">
            </div>
            <div class="logo-text">
                <h2>Hytec Power inc.</h2>
                <span>Monitoring</span>
            </div>
        </div>

        <nav class="navigation">
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
                        <polyline points="9 22 9 12 15 12 15 22"/>
                    </svg>
                </span>
                <span>Dashboard</span>
            </a>

            @if($isAdmin)
                <a href="{{ route('fae.index') }}" class="nav-item {{ request()->routeIs('fae.*') ? 'active' : '' }}" id="navFaeLink">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                            <path d="M16 3.13a4 4 0 010 7.75"/>
                        </svg>
                    </span>
                    <span>FAE</span>
                </a>
            @endif

            <a href="{{ route('tasks.index') }}" class="nav-item {{ request()->routeIs('tasks.*') ? 'active' : '' }}">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 11l3 3L22 4"/>
                        <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                    </svg>
                </span>
                <span>Tasks</span>
            </a>

            @if($isAdmin)
                <a href="{{ route('departments.index') }}" class="nav-item {{ request()->routeIs('departments.*') ? 'active' : '' }}">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 7h16M4 12h16M4 17h10"/>
                            <circle cx="17" cy="17" r="3"/>
                        </svg>
                    </span>
                    <span>Departments</span>
                </a>
            @endif

            <a href="{{ route('calendar.index') }}" class="nav-item {{ request()->routeIs('calendar.*') ? 'active' : '' }}">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </span>
                <span>Calendar</span>
            </a>

            @if($isAdmin)
                <a href="{{ route('settings.index') }}" class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <span class="nav-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="3"/>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                        </svg>
                    </span>
                    <span>Settings</span>
                </a>
            @endif
        </nav>

        <div class="sidebar-bottom">
            <div class="sidebar-footer">
                <span>© 2026 Hytec Power Inc.</span>
            </div>
        </div>
    </aside>

    <!-- ================= MAIN CONTENT ================= -->
    <main class="main">

        <!-- TOPBAR -->
        <header class="topbar">
            <button class="menu-toggle" id="menuToggle" aria-label="Toggle menu">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
            </button>

            <div class="breadcrumb">
                <a href="{{ route('dashboard') }}" style="color:inherit; text-decoration:none;">Home</a>
                <b>/</b>
                <strong>@yield('breadcrumb', 'Dashboard')</strong>
            </div>

            <div class="topbar-right">
                <!-- Notifications dropdown -->
                <div class="notification-wrap" id="notificationWrap" data-notification-key="{{ $notificationKey }}">
                    <button type="button" class="icon-button notification-button" id="notificationBtn" title="Notifications" aria-label="Notifications" onclick="event.stopPropagation(); document.getElementById('notificationWrap').classList.toggle('notification-open');">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="display:block; color:var(--text-light);">
                            <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                            <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                        </svg>
                        @if($notificationCount > 0)
                            <span class="notification-count">{{ $notificationCount > 99 ? '99+' : $notificationCount }}</span>
                        @endif
                    </button>
                    <div class="notification-menu" onclick="event.stopPropagation();">
                        <div class="notification-header" style="display:flex; align-items:center; justify-content:space-between; padding-bottom:8px; border-bottom:1px solid var(--border); margin-bottom:4px;">
                            <strong style="font-size:12px; font-weight:700;">Notifications</strong>
                            @if($notificationCount > 0)
                                <span style="font-size:10px; font-weight:700; color:var(--primary); background:rgba(181,47,50,0.1); padding:2px 6px; border-radius:10px;">{{ $notificationCount }} active</span>
                            @endif
                        </div>
                        @if($notificationCount === 0)
                            <span class="notification-empty">No new notifications.</span>
                        @else
                            @foreach(array_slice($notificationItems, 0, 8) as $item)
                                @php
                                    $type = $item['type'];
                                    $title = $item['title'];
                                    $date = $item['date'];
                                    
                                    // Extract FAE name and description from title
                                    $faeName = '';
                                    $description = '';
                                    
                                    if ($type === 'FAE Progress Report') {
                                        // Format: "FAE Name on \"Task Name\": message"
                                        if (preg_match('/^([^"]+)\s+on\s+"([^"]+)"\:\s*(.*)$/', $title, $matches)) {
                                            $faeName = trim($matches[1]);
                                            $description = $matches[3];
                                        }
                                    } elseif ($type === 'Admin Remark') {
                                        // Format: "Admin on \"Task Name\": message"
                                        if (preg_match('/^Admin\s+on\s+"([^"]+)"\:\s*(.*)$/', $title, $matches)) {
                                            $faeName = 'Administrator';
                                            $description = $matches[2];
                                        }
                                    } elseif ($type === 'Assigned task' || $type === 'Overdue task') {
                                        $description = $title;
                                        $faeName = '';
                                    } elseif ($type === 'Appointment request') {
                                        $description = $title;
                                        $faeName = '';
                                    }
                                @endphp
                                <div style="background: rgba(59,130,246,0.05); border: 1px solid var(--border); border-radius: 8px; padding: 12px; margin-bottom: 8px; transition: all 0.2s ease;">
                                    <div style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 8px;">
                                        <span style="color: var(--primary); font-size: 8px; font-weight: 700; text-transform: uppercase; background: rgba(59,130,246,0.15); padding: 3px 6px; border-radius: 4px; flex-shrink: 0;">{{ $type }}</span>
                                        @if(!empty($faeName))
                                            <span style="color: white; font-size: 10px; font-weight: 700; background: var(--primary); padding: 3px 8px; border-radius: 4px; flex-shrink: 0;">{{ $faeName }}</span>
                                        @endif
                                    </div>
                                    
                                    @if(!empty($description))
                                        <p style="margin: 0 0 8px 0; font-size: 11px; color: var(--text); line-height: 1.4;">{{ $description }}</p>
                                    @endif
                                    
                                    @if(!empty($date))
                                        <div style="display: flex; align-items: center; gap: 4px; font-size: 9px; color: var(--text-light);">
                                            <span style="font-weight: 600;">Date:</span>
                                            <span>{{ date('M d, Y • H:i', strtotime($date)) }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                <a href="{{ route('logout') }}" class="role-tag {{ $isAdmin ? 'admin' : 'fae' }}" style="text-decoration:none;">
                    Sign out
                </a>

                <div class="profile" onclick="openProfileModal()" style="cursor:pointer;" title="Click to update profile picture">
                    <div style="position:relative; flex-shrink:0;">
                        <div class="avatar">
                            @if(!empty($profileImage))
                                <img class="profile-image" src="{{ \App\Services\UploadService::url($profileImage) }}" alt="{{ $currentFaeName }} profile">
                            @else
                                {{ $isAdmin ? 'AD' : strtoupper(substr($currentFaeName, 0, 2)) }}
                            @endif
                        </div>
                        <span style="position:absolute; bottom:-2px; right:-2px; background:#fff; color:#555; border-radius:50%; width:14px; height:14px; display:flex; align-items:center; justify-content:center; box-shadow:0 1px 3px rgba(0,0,0,0.25); border:1px solid #ddd;">
                            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                        </span>
                    </div>
                    <div class="profile-info">
                        <strong>{{ $isAdmin ? 'Administrator' : $currentFaeName }}</strong>
                        <span>{{ $isAdmin ? 'Full Management' : ($currentFaeCode ?: 'Field Engineer') }}</span>
                    </div>
                </div>
            </div>
        </header>

        <!-- FLASH MESSAGES -->
        @if(session('success') || session('msg'))
            <div class="alert-banner success" style="margin: 20px 30px 0 30px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
                <span>{{ session('success') ?: session('msg') }}</span>
                @if(session('success_link'))
                    <a href="{{ session('success_link') }}" target="_blank" rel="noopener noreferrer" style="display: inline-flex; align-items: center; gap: 6px; background: #059669; color: #ffffff; padding: 5px 12px; border-radius: 6px; font-weight: 600; font-size: 12px; text-decoration: none; box-shadow: 0 1px 2px rgba(0,0,0,0.08);">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                        View in Google Drive &rarr;
                    </a>
                @endif
            </div>
        @endif

        @if(session('error') || session('err'))
            <div class="alert-banner error" style="margin: 20px 30px 0 30px;">
                {{ session('error') ?: session('err') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert-banner error" style="margin: 20px 30px 0 30px;">
                <ul style="margin:0; padding-left: 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- PAGE CONTENT -->
        @yield('content')

    </main>
</div>

<!-- PROFILE PHOTO & GOOGLE ACCOUNT MODAL -->
<div class="custom-modal-overlay" id="updateProfilePhotoModal">
    <div class="custom-modal" style="max-width:440px;">
        <div class="custom-modal-header">
            <h3>Profile &amp; Google Account</h3>
            <button type="button" class="custom-modal-close" onclick="closeProfileModal()">&times;</button>
        </div>

        <div class="custom-modal-body" style="text-align:left;">
            <!-- Current User Info -->
            <div style="text-align:center; margin-bottom:16px;">
                <div class="avatar" style="width:70px; height:70px; font-size:24px; margin:0 auto 10px auto; border-radius:50%; overflow:hidden; background:var(--primary); color:white; display:flex; align-items:center; justify-content:center;">
                    @if(!empty($profileImage))
                        <img src="{{ \App\Services\UploadService::url($profileImage) }}" style="width:100%; height:100%; object-fit:cover;" alt="Current profile picture">
                    @else
                        {{ $isAdmin ? 'AD' : strtoupper(substr($currentFaeName, 0, 2)) }}
                    @endif
                </div>
                <strong style="display:block; font-size:14px; color:var(--text);">{{ $isAdmin ? 'Administrator' : $currentFaeName }}</strong>
                <span style="font-size:11px; color:var(--text-light);">{{ $isAdmin ? 'Admin Profile' : ($currentFaeCode ?: 'FAE Profile') }}</span>
            </div>

            <!-- Connected Google Account Section -->
            @php
                $isGoogleConn = MonitoringAuth::currentGoogleConnected();
                $googleEmail = MonitoringAuth::currentGoogleEmail();
                $googleAvatar = MonitoringAuth::currentGoogleAvatar();
            @endphp
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:14px; margin-bottom:16px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <strong style="font-size:12px; color:var(--text); display:flex; align-items:center; gap:6px;">
                        <svg width="14" height="14" viewBox="0 0 48 48">
                            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                        </svg>
                        Google / Gmail Account
                    </strong>
                    @if($isGoogleConn)
                        <span style="background:#22c55e; color:#fff; font-size:9px; font-weight:700; padding:2px 6px; border-radius:4px;">CONNECTED</span>
                    @else
                        <span style="background:#94a3b8; color:#fff; font-size:9px; font-weight:700; padding:2px 6px; border-radius:4px;">NOT CONNECTED</span>
                    @endif
                </div>

                @if($isGoogleConn)
                    <div style="font-size:12px; color:#166534; word-break:break-all; margin-bottom:10px;">
                        ✓ Linked to: <strong>{{ $googleEmail }}</strong>
                    </div>
                    <form method="POST" action="{{ route('google.disconnect') }}" onsubmit="return confirm('Disconnect this Google account?');">
                        @csrf
                        <button type="submit" class="secondary-button btn-sm" style="color:#ef4444; border-color:#fca5a5; width:100%; font-size:11px;">
                            Disconnect Google Account
                        </button>
                    </form>
                @else
                    <p style="font-size:11px; color:var(--text-light); margin:0 0 10px 0;">
                        Connect your Google account to sync appointments with Google Calendar and save reports directly to Google Drive.
                    </p>
                    <a href="{{ route('google.redirect', ['mode' => $isAdmin ? 'connect_admin' : 'connect_fae']) }}" class="primary-button btn-sm" style="display:flex; align-items:center; justify-content:center; gap:6px; text-decoration:none; font-size:12px; width:100%;">
                        Connect Google Account
                    </a>
                @endif
            </div>

            <!-- Profile Photo Upload -->
            <form method="POST" action="{{ route('profile.updatePhoto') }}" enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                    <label class="form-label">Upload Custom Profile Picture</label>
                    <input type="file" name="profile_image" class="form-control" accept="image/png,image/jpeg,image/jpg,image/webp,image/gif" required>
                    <small style="font-size:10px; color:var(--text-light); margin-top:4px; display:block;">Supported formats: JPG, PNG, WEBP, GIF (Max 2MB)</small>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:14px;">
                    <button type="button" class="secondary-button btn-sm" onclick="closeProfileModal()">Cancel</button>
                    <button type="submit" class="primary-button btn-sm">Save Photo</button>
                </div>
            </form>
        </div>
    </div>
</div>


<!-- Core JavaScript -->
<script>
    function openProfileModal() {
        const modal = document.getElementById("updateProfilePhotoModal");
        if (modal) modal.classList.add("active");
    }

    function closeProfileModal() {
        const modal = document.getElementById("updateProfilePhotoModal");
        if (modal) modal.classList.remove("active");
    }

    // Responsive Mobile Menu Toggle
    const menuToggle = document.getElementById('menuToggle');
    const sidebar = document.getElementById('sidebar');
    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
    }

    // Notification dropdown dismiss & view tracking
    const notificationWrap = document.getElementById("notificationWrap");
    const notificationKey = notificationWrap ? notificationWrap.getAttribute("data-notification-key") : "";
    const viewedKey = localStorage.getItem("monitoring_notifications_viewed_key");
    const countBadge = document.querySelector(".notification-count");
    if (countBadge && notificationKey && viewedKey === notificationKey) {
        countBadge.style.display = "none";
    }

    document.addEventListener("click", function(e) {
        if (notificationWrap && !notificationWrap.contains(e.target)) {
            notificationWrap.classList.remove("notification-open");
        }
    });

    const notificationBtn = document.getElementById("notificationBtn");
    if (notificationBtn) {
        notificationBtn.addEventListener("click", function() {
            if (countBadge) {
                countBadge.style.display = "none";
                if (notificationKey) {
                    localStorage.setItem("monitoring_notifications_viewed_key", notificationKey);
                }
            }
        });
    }
</script>
<script src="{{ asset_versioned('script.js') }}"></script>
@stack('scripts')
</body>
</html>
