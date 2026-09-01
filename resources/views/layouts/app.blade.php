@php
    use App\Services\MonitoringAuth;
    $isAdmin = MonitoringAuth::isAdmin();
    $currentFaeId = MonitoringAuth::faeId();
    $currentFaeName = MonitoringAuth::faeName();
    $currentFaeCode = MonitoringAuth::faeCode();
    $notificationItems = MonitoringAuth::notifications();
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

    <link rel="stylesheet" href="{{ asset('style.css') }}?v={{ time() }}">
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

            <a href="{{ env('GOOGLE_DRIVE_FOLDER_URL', 'https://drive.google.com/drive/my-drive') }}" target="_blank" rel="noopener" class="nav-item" title="Open Google Drive Workspace">
                <span class="nav-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"></path>
                    </svg>
                </span>
                <span>Google Drive</span>
            </a>
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
                                <div class="notification-item">
                                    <span style="color:var(--primary); font-size:9px; font-weight:700; text-transform:uppercase;">{{ $item['type'] }}</span>
                                    <strong style="font-size:11px; line-height:1.35; color:var(--text);">{{ $item['title'] }}</strong>
                                    @if(!empty($item['date']))
                                        <small style="color:var(--text-light); font-size:9px;">{{ date('M d, Y', strtotime($item['date'])) }}</small>
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
                                <img class="profile-image" src="{{ asset($profileImage) }}" alt="{{ $currentFaeName }} profile">
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
            <div class="alert-banner success" style="margin: 20px 30px 0 30px;">
                {{ session('success') ?: session('msg') }}
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

<!-- PROFILE PHOTO UPDATE MODAL -->
<div class="custom-modal-overlay" id="updateProfilePhotoModal">
    <div class="custom-modal" style="max-width:400px;">
        <div class="custom-modal-header">
            <h3>Update Profile Picture</h3>
            <button type="button" class="custom-modal-close" onclick="closeProfileModal()">&times;</button>
        </div>

        <form method="POST" action="{{ route('profile.updatePhoto') }}" enctype="multipart/form-data">
            @csrf
            <div class="custom-modal-body" style="text-align:center;">
                <div style="margin-bottom:16px;">
                    <div class="avatar" style="width:70px; height:70px; font-size:24px; margin:0 auto 10px auto; border-radius:50%; overflow:hidden; background:var(--primary); color:white; display:flex; align-items:center; justify-content:center;">
                        @if(!empty($profileImage))
                            <img src="{{ asset($profileImage) }}" style="width:100%; height:100%; object-fit:cover;" alt="Current profile picture">
                        @else
                            {{ $isAdmin ? 'AD' : strtoupper(substr($currentFaeName, 0, 2)) }}
                        @endif
                    </div>
                    <strong style="display:block; font-size:13px; color:var(--text);">{{ $isAdmin ? 'Administrator' : $currentFaeName }}</strong>
                    <span style="font-size:11px; color:var(--text-light);">{{ $isAdmin ? 'Admin Profile' : ($currentFaeCode ?: 'FAE Profile') }}</span>
                </div>

                <div class="form-group" style="text-align:left;">
                    <label class="form-label">Select New Image File <span class="req">*</span></label>
                    <input type="file" name="profile_image" class="form-control" accept="image/png,image/jpeg,image/jpg,image/webp,image/gif" required>
                    <small style="font-size:10px; color:var(--text-light); margin-top:4px; display:block;">Supported formats: JPG, PNG, WEBP, GIF (Max 2MB)</small>
                </div>
            </div>

            <div class="custom-modal-footer">
                <button type="button" class="secondary-button btn-sm" onclick="closeProfileModal()">Cancel</button>
                <button type="submit" class="primary-button btn-sm">Upload &amp; Save</button>
            </div>
        </form>
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
<script src="{{ asset('script.js') }}"></script>
@stack('scripts')
</body>
</html>
