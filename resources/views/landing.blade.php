@php
    use App\Services\MonitoringAuth;
    $today = date('Y-m-d');
    $firstDayRaw = (int)date('w', mktime(0, 0, 0, $month, 1, $year));
    $daysInMonth = (int)date('t', mktime(0, 0, 0, $month, 1, $year));

    // Build JSON-safe arrays for client-side modal click handler
    $tasksByDayJson = [];
    foreach ($tasksByDay as $day => $tasks) {
        foreach ($tasks as $t) {
            $tasksByDayJson[$day][] = [
                'task_name' => $t->task_name,
                'status'    => $t->status ?? 'Pending',
                'progress'  => (int)$t->progress,
                'fae_name'  => $t->fae->name ?? 'Unassigned',
                'region'    => $t->region ?? '',
            ];
        }
    }

    $apptsByDayJson = [];
    foreach ($apptsByDay as $day => $appts) {
        foreach ($appts as $a) {
            $apptsByDayJson[$day][] = [
                'user_name' => $a->user_name ?? ($a->fae->name ?? 'User'),
                'reason'    => $a->reason,
                'status'    => $a->status,
            ];
        }
    }

    $adminEventsByDayJson = [];
    foreach ($adminEventsByDay as $day => $events) {
        foreach ($events as $e) {
            $adminEventsByDayJson[$day][] = [
                'title'       => $e->title,
                'category'    => $e->category,
                'description' => $e->description,
            ];
        }
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule &amp; Monitoring Portal | Hytec Power Inc.</title>

    <!-- Stylesheets -->
    <link rel="stylesheet" href="{{ asset('style.css') }}">
    <link rel="stylesheet" href="{{ asset('calendar.css') }}">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
            width: 100%;
            overflow: hidden;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            color: var(--text, #1e293b);
            -webkit-font-smoothing: antialiased;
        }

        .landing-shell {
            height: 100vh;
            width: 100vw;
            max-height: 100vh;
            max-width: 100vw;
            display: flex;
            flex-direction: column;
            box-sizing: border-box;
            overflow: hidden;
            background: #f8fafc;
        }

        /* ── Topbar ─────────────────────────────────────────── */
        .landing-topbar {
            flex: 0 0 54px;
            height: 54px;
            background: #ffffff;
            border-bottom: 1px solid var(--border, #e2e8f0);
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            z-index: 50;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
            box-sizing: border-box;
        }

        .landing-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: inherit;
            flex-shrink: 0;
        }

        .landing-logo img {
            width: 34px;
            height: 34px;
            object-fit: contain;
        }

        .landing-logo-text h1 {
            font-size: 14px;
            font-weight: 800;
            margin: 0;
            line-height: 1.2;
            color: var(--text, #0f172a);
            letter-spacing: -0.2px;
        }

        .landing-logo-text span {
            font-size: 10px;
            color: var(--primary, #b52f32);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .landing-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        /* ── Main View Area (100vh / 100vw 16:9 Responsive) ──── */
        .landing-main {
            flex: 1 1 auto;
            min-height: 0;
            height: calc(100vh - 54px - 32px);
            width: 100%;
            max-width: 1920px;
            margin: 0 auto;
            padding: 10px 20px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .landing-shell .cal-layout {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 330px;
            gap: 14px;
            flex: 1 1 auto;
            min-height: 0;
            height: 100%;
            max-height: 100%;
            margin-bottom: 0;
        }

        /* ── Left Calendar Panel ────────────────────────────── */
        .landing-shell .cal-main {
            display: flex;
            flex-direction: column;
            min-height: 0;
            height: 100%;
        }

        .landing-shell .cal-panel {
            display: flex;
            flex-direction: column;
            height: 100%;
            min-height: 0;
            margin-bottom: 0;
            background: #ffffff;
            border: 1px solid var(--border, #e2e8f0);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .landing-shell .cal-header {
            padding: 8px 16px;
            border-bottom: 1px solid #f1f5f9;
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .landing-shell .cal-title h2 {
            font-size: 16px;
            font-weight: 800;
            margin: 0;
            color: var(--text, #0f172a);
        }

        .landing-shell .cal-title span {
            font-size: 11px;
            color: var(--text-light, #64748b);
        }

        .landing-shell .cal-nav-btn {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            font-size: 18px;
        }

        .landing-shell .cal-day-labels {
            flex: 0 0 auto;
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .landing-shell .cal-day-label {
            padding: 5px 0;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            text-align: center;
        }

        .landing-shell .cal-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            grid-auto-rows: 1fr;
            flex: 1 1 auto;
            min-height: 0;
            height: 100%;
            border-left: 1px solid #f1f5f9;
        }

        .landing-shell .cal-cell {
            min-height: 0;
            height: 100%;
            box-sizing: border-box;
            padding: 4px 6px;
            border-right: 1px solid #f1f5f9;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            flex-direction: column;
            gap: 2px;
            overflow: hidden;
            cursor: pointer;
            position: relative;
            background: #ffffff;
            transition: background 0.15s ease;
        }

        .landing-shell .cal-cell:hover:not(.cal-cell--empty) {
            background: #f8faff;
        }

        .landing-shell .cal-day-num {
            font-size: 11px;
            font-weight: 700;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 4px;
            color: #334155;
            flex-shrink: 0;
        }

        .landing-shell .cal-cell--today .cal-day-num {
            background: var(--primary, #b52f32);
            color: #ffffff;
        }

        .landing-shell .cal-events {
            flex: 1 1 auto;
            min-height: 0;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 2px;
            scrollbar-width: none;
        }

        .landing-shell .cal-events::-webkit-scrollbar {
            display: none;
        }

        .landing-shell .cal-event {
            font-size: 9.5px;
            line-height: 1.25;
            padding: 2px 4px;
            border-radius: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            font-weight: 500;
        }

        /* ── Right Sidebar (Wireframe Matched) ───────────────── */
        .landing-shell .cal-side {
            display: flex;
            flex-direction: column;
            gap: 8px;
            height: 100%;
            min-height: 0;
            overflow-y: auto;
            scrollbar-width: thin;
            padding-right: 2px;
        }

        /* Wireframe Top Row: 3 KPI Cards */
        .wireframe-kpi-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            flex-shrink: 0;
        }

        .wireframe-kpi-card {
            background: #ffffff;
            border: 1px solid var(--border, #e2e8f0);
            border-radius: 8px;
            padding: 8px 4px;
            text-align: center;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .wireframe-kpi-card strong {
            font-size: 17px;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.1;
            margin-bottom: 2px;
        }

        .wireframe-kpi-card span {
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #64748b;
            line-height: 1.2;
        }

        .wireframe-kpi-card.kpi-total strong { color: #2563eb; }
        .wireframe-kpi-card.kpi-progress strong { color: #f59e0b; }
        .wireframe-kpi-card.kpi-completed strong { color: #10b981; }

        /* Wireframe Stacked Panels */
        .wireframe-panel {
            background: #ffffff;
            border: 1px solid var(--border, #e2e8f0);
            border-radius: 8px;
            padding: 10px 12px;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.02);
            flex-shrink: 0;
        }

        .wireframe-panel h3 {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text, #0f172a);
            margin: 0 0 6px 0;
            line-height: 1.2;
        }

        /* ── Footer ─────────────────────────────────────────── */
        .landing-footer {
            flex: 0 0 32px;
            height: 32px;
            background: #ffffff;
            border-top: 1px solid var(--border, #e2e8f0);
            padding: 0 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            color: #64748b;
            box-sizing: border-box;
        }

        /* ── Responsive rules ───────────────────────────────── */
        @media (max-width: 1024px), (max-height: 640px) {
            html, body {
                overflow-y: auto;
                height: auto;
            }
            .landing-shell {
                height: auto;
                min-height: 100vh;
                overflow: visible;
            }
            .landing-main {
                height: auto;
                flex: none;
                padding: 10px 14px;
            }
            .landing-shell .cal-layout {
                grid-template-columns: 1fr;
                height: auto;
            }
            .landing-shell .cal-grid {
                height: auto;
            }
            .landing-shell .cal-cell {
                min-height: 68px;
            }
        }

        @media (max-width: 768px) {
            .landing-topbar, .landing-footer {
                padding-left: 14px;
                padding-right: 14px;
            }
        }
    </style>
</head>
<body class="landing-shell">

    <!-- ================= TOPBAR ================= -->
    <header class="landing-topbar">
        <a href="{{ route('landing') }}" class="landing-logo">
            <img src="{{ asset('logo.png') }}" alt="Hytec Power Logo">
            <div class="landing-logo-text">
                <h1>Hytec Power Inc.</h1>
                <span>Schedule &amp; Monitoring Portal</span>
            </div>
        </a>

        <div class="landing-actions">
            <div class="date-box" style="display:none; @media(min-width:640px){display:flex;}">
                <span>Today</span>
                <strong>{{ date('F d, Y') }}</strong>
            </div>

            @if($role)
                <a href="{{ route('dashboard') }}" class="primary-button btn-sm" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px;">
                    <span>Go to Dashboard</span>
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
                <a href="{{ route('logout') }}" class="role-tag {{ $isAdmin ? 'admin' : 'fae' }}" style="text-decoration:none;">
                    Sign out
                </a>
            @else
                <a href="{{ route('access') }}" class="outline-button btn-sm" style="text-decoration:none;">
                    FAE Access
                </a>
                <a href="{{ route('access') }}" class="primary-button btn-sm" style="text-decoration:none;">
                    Supervisor Sign In
                </a>
            @endif
        </div>
    </header>

    <!-- ================= MAIN SECTION (100vh / 100vw Responsive) ================= -->
    <main class="landing-main">

        <div class="cal-layout">

            <!-- LEFT: CALENDAR PANEL -->
            <div class="cal-main">
                <div class="panel cal-panel">

                    <!-- Month Navigation Header -->
                    <div class="cal-header">
                        <a href="{{ route('landing', ['month' => $prevMonth, 'year' => $prevYear]) }}"
                           class="cal-nav-btn" title="Previous month">&#8249;</a>

                        <div class="cal-title">
                            <h2>{{ date('F', mktime(0,0,0,$month,1,$year)) }}</h2>
                            <span>{{ $year }} &bull; Operational Schedule</span>
                        </div>

                        <div style="display: flex; align-items: center; gap: 8px;">
                            <a href="{{ route('landing', ['month' => $nextMonth, 'year' => $nextYear]) }}"
                               class="cal-nav-btn" title="Next month">&#8250;</a>
                        </div>
                    </div>

                    <!-- Day Labels -->
                    <div class="cal-day-labels">
                        @foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dl)
                            <div class="cal-day-label">{{ $dl }}</div>
                        @endforeach
                    </div>

                    <!-- Calendar Day Grid -->
                    <div class="cal-grid">
                        @for ($i = 0; $i < $firstDayRaw; $i++)
                            <div class="cal-cell cal-cell--empty"></div>
                        @endfor

                        @for ($day = 1; $day <= $daysInMonth; $day++)
                            @php
                                $dateStr        = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                $isToday        = ($dateStr === $today);
                                $isPast         = ($dateStr < $today);
                                $hasTasks       = !empty($tasksByDay[$day]);
                                $hasAppts       = !empty($apptsByDay[$day]);
                                $hasAdminEvents = !empty($adminEventsByDay[$day]);
                                $isBooked       = isset($bookedDays[$day]);
                                $isAccepted     = $isBooked && (($bookedDays[$day]->status ?? '') === 'accepted');

                                $cls = 'cal-cell';
                                if ($isToday)               $cls .= ' cal-cell--today';
                                if ($isPast)                $cls .= ' cal-cell--past';
                                if ($hasTasks || $hasAppts || $hasAdminEvents) $cls .= ' cal-cell--has-events';
                                if ($isBooked)              $cls .= $isAccepted ? ' cal-cell--accepted' : ' cal-cell--booked';
                            @endphp

                            <div class="{{ $cls }}"
                                 data-date="{{ $dateStr }}"
                                 data-day="{{ $day }}"
                                 data-booked="{{ $isBooked ? '1' : '0' }}"
                                 data-busy="{{ isset($busyDays[$day]) ? '1' : '0' }}">

                                <span class="cal-day-num">{{ $day }}</span>

                                <!-- Admin Events Badges -->
                                @if($hasAdminEvents)
                                    <div class="cal-events">
                                        @foreach (array_slice($adminEventsByDay[$day] instanceof \Illuminate\Support\Collection ? $adminEventsByDay[$day]->toArray() : (is_array($adminEventsByDay[$day]) ? $adminEventsByDay[$day] : iterator_to_array($adminEventsByDay[$day])), 0, 2) as $ae)
                                            @php
                                                $aeData = is_object($ae) ? $ae : (object)$ae;
                                                $catClass = 'ev-admin-' . ($aeData->category ?? 'other');
                                            @endphp
                                            <div class="cal-event {{ $catClass }} ev-admin"
                                                 title="Event: {{ $aeData->title }} ({{ ucfirst($aeData->category ?? 'Event') }})">
                                                 {{ mb_strimwidth($aeData->title, 0, 14, '...') }}
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <!-- Task Deadline Badges -->
                                @if($hasTasks)
                                    <div class="cal-events">
                                        @foreach (array_slice(is_array($tasksByDay[$day]) ? $tasksByDay[$day] : $tasksByDay[$day]->toArray(), 0, 2) as $t)
                                            @php
                                                $tData = is_object($t) ? $t : (object)$t;
                                                $sc = strtolower(str_replace(' ', '', $tData->status ?? 'pending'));
                                                $ec = ($sc === 'inprogress') ? 'ev-progress' : 'ev-' . $sc;
                                            @endphp
                                            <div class="cal-event {{ $ec }}"
                                                 title="Task: {{ $tData->task_name }} ({{ $tData->status ?? 'Pending' }} - {{ (int)($tData->progress ?? 0) }}%)">
                                                ✓ {{ mb_strimwidth($tData->task_name, 0, 14, '...') }}
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <!-- Appointment Badges -->
                                @if($hasAppts)
                                    <div class="cal-events">
                                        @foreach (array_slice(is_array($apptsByDay[$day]) ? $apptsByDay[$day] : $apptsByDay[$day]->toArray(), 0, 2) as $a)
                                            @php
                                                $aData = is_object($a) ? $a : (object)$a;
                                                $apptClass = ($aData->status === 'rejected') ? 'ev-overdue' : (($aData->status === 'accepted') ? 'ev-completed' : 'ev-appt');
                                            @endphp
                                            <div class="cal-event {{ $apptClass }}"
                                                 title="Appointment: {{ $aData->reason }} (Status: {{ ucfirst($aData->status) }})">
                                                Appt: {{ mb_strimwidth($aData->reason, 0, 12, '...') }}
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endfor
                    </div>

                </div>
            </div>

            <!-- RIGHT: WIREFRAME-ALIGNED SIDEBAR -->
            <div class="cal-side">

                <!-- 1. TOP ROW: TOTAL TASK | IN PROGRES | COMPLETED -->
                <div class="wireframe-kpi-row">
                    <div class="wireframe-kpi-card kpi-total">
                        <strong>{{ $totalTasks }}</strong>
                        <span>TOTAL TASK</span>
                    </div>
                    <div class="wireframe-kpi-card kpi-progress">
                        <strong>{{ $totalInProgressTasks }}</strong>
                        <span>IN PROGRES</span>
                    </div>
                    <div class="wireframe-kpi-card kpi-completed">
                        <strong>{{ $totalCompletedTasks }}</strong>
                        <span>COMPLETED</span>
                    </div>
                </div>

                <!-- 2. SCHEDULE GUIDE AND STATUS -->
                <div class="wireframe-panel">
                    <h3>SCHEDULE GUIDE AND STATUS</h3>
                    <p style="font-size:10.5px; color:var(--text-light); line-height:1.35; margin:0 0 8px 0;">
                        Click any date to inspect scheduled events, field tasks, or appointments.
                    </p>
                    <div style="display:flex; flex-direction:column; gap:4px; font-size:10.5px;">
                        <div style="display:flex; align-items:center; gap:6px;">
                            <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:#2563eb;"></span>
                            <span>Task Deadlines</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:6px;">
                            <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:#8b5cf6;"></span>
                            <span>Appointments</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:6px;">
                            <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:#10b981;"></span>
                            <span>Completed / Accepted</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:6px;">
                            <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:#ef4444;"></span>
                            <span>Unavailable / Booked</span>
                        </div>
                    </div>
                </div>

                <!-- 3. GOOGLE CALENDAR -->
                <div class="wireframe-panel">
                    <h3>GOOGLE CALENDAR</h3>
                    <p style="font-size:10.5px; color:var(--text-light); line-height:1.35; margin:0 0 8px 0;">
                        Sync monitoring deadlines directly with Google Workspace.
                    </p>
                    <a href="https://calendar.google.com" target="_blank" rel="noopener" class="google-calendar-link" style="font-size:10.5px; padding:4px 10px; display:inline-block; text-decoration:none; font-weight:600;">
                        Open Google Calendar →
                    </a>
                </div>

                <!-- 4. UPCOMING EVENT -->
                <div class="wireframe-panel">
                    <h3>UPCOMING EVENT</h3>
                    @if($upcomingEvents->count() > 0)
                        <div style="display:flex; flex-direction:column; gap:5px;">
                            @foreach ($upcomingEvents->take(4) as $ev)
                                <div style="display:flex; justify-content:space-between; align-items:center; background:#f8fafc; padding:5px 8px; border-radius:6px; border:1px solid var(--border);">
                                    <div>
                                        <strong style="font-size:10.5px; display:block; color:var(--text);">{{ $ev->title }}</strong>
                                        <small style="color:var(--text-light); font-size:9.5px;">{{ \Carbon\Carbon::parse($ev->event_date)->format('M d, Y') }}</small>
                                    </div>
                                    <span class="status-badge muted" style="font-size:8.5px; text-transform:uppercase; padding:1px 4px;">{{ $ev->category }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p style="font-size:10.5px; color:var(--text-light); margin:0;">No upcoming events scheduled.</p>
                    @endif
                </div>

                <!-- 5. WORKSPACE ACCESS -->
                <div class="wireframe-panel" style="background:linear-gradient(135deg, rgba(181,47,50,0.02) 0%, rgba(37,99,235,0.02) 100%);">
                    <h3>WORKSPACE ACCESS</h3>
                    <p style="font-size:10.5px; color:var(--text-light); line-height:1.35; margin:0 0 8px 0;">
                        Authorized engineers and supervisors can access dedicated tools.
                    </p>
                    <div style="display:flex; flex-direction:column; gap:6px;">
                        @if($role)
                            <a href="{{ route('dashboard') }}" class="primary-button btn-sm" style="text-decoration:none; text-align:center; font-size:11px; padding:5px 10px;">Go to Dashboard</a>
                        @else
                            <a href="{{ route('access') }}" class="primary-button btn-sm" style="text-decoration:none; text-align:center; font-size:11px; padding:5px 10px;">Open Workspace Portal</a>
                        @endif
                    </div>
                </div>

            </div>

        </div>

    </main>

    <!-- ================= DATE DETAILS MODAL ================= -->
    <div class="cal-modal-overlay" id="calModalOverlay">
        <div class="cal-modal">
            <div class="cal-modal-header">
                <h3 id="calModalTitle">Date Details</h3>
                <button type="button" class="cal-modal-close" id="calModalClose">&times;</button>
            </div>
            <div class="cal-modal-body" id="calModalBody"></div>
        </div>
    </div>

    <!-- ================= FOOTER ================= -->
    <footer class="landing-footer">
        <span>© 2026 Hytec Power Inc. Monitoring System</span>
        <span>Operational &amp; Live</span>
    </footer>

    <!-- ================= SCRIPTS ================= -->
    <script>
        var tasksData = @json($tasksByDayJson);
        var apptsData = @json($apptsByDayJson);
        var adminEventsData = @json($adminEventsByDayJson);
        var isLoggedIn = @json((bool)$role);
        var accessUrl = "{{ route('access') }}";
        var calendarUrl = "{{ route('calendar.index') }}";

        var calOverlay = document.getElementById("calModalOverlay");
        var calModalTitle = document.getElementById("calModalTitle");
        var calModalBody = document.getElementById("calModalBody");
        var calModalClose = document.getElementById("calModalClose");

        function escHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function closeCalModal() {
            if (calOverlay) {
                calOverlay.classList.remove("active");
                calOverlay.classList.remove("open");
            }
        }

        if (calModalClose) {
            calModalClose.addEventListener("click", closeCalModal);
        }

        if (calOverlay) {
            calOverlay.addEventListener("click", function(e) {
                if (e.target === calOverlay) closeCalModal();
            });
        }

        document.addEventListener("keydown", function(e) {
            if (e.key === "Escape") closeCalModal();
        });

        // Click cell to inspect date details
        document.querySelectorAll(".cal-cell:not(.cal-cell--empty)").forEach(function(cell) {
            cell.addEventListener("click", function() {
                var dateStr = this.getAttribute("data-date");
                var dayNum = parseInt(this.getAttribute("data-day"), 10);

                var dayTasks = tasksData[dayNum] || [];
                var dayAppts = apptsData[dayNum] || [];
                var dayAdminEvents = adminEventsData[dayNum] || [];

                if (calOverlay && calModalBody && calModalTitle) {
                    calModalTitle.textContent = "Schedule & Status for " + dateStr;
                    var html = "";

                    if (dayAdminEvents.length > 0) {
                        html += '<div style="margin-bottom:14px;"><strong>Events &amp; Schedules:</strong><ul style="margin:6px 0 0 18px; padding:0; font-size:13px; color:var(--text-main);">';
                        dayAdminEvents.forEach(function(ev) {
                            html += '<li style="margin-bottom:4px;"><strong>' + escHtml(ev.title) + '</strong> (' + escHtml(ev.category) + ')' + (ev.description ? ' - ' + escHtml(ev.description) : '') + '</li>';
                        });
                        html += '</ul></div>';
                    }

                    if (dayTasks.length > 0) {
                        html += '<div style="margin-bottom:14px;"><strong>Tasks Due:</strong><ul style="margin:6px 0 0 18px; padding:0; font-size:13px; color:var(--text-main);">';
                        dayTasks.forEach(function(t) {
                            html += '<li style="margin-bottom:4px;"><strong>' + escHtml(t.task_name) + '</strong> - Assigned: ' + escHtml(t.fae_name) + ' [' + escHtml(t.status) + ', ' + t.progress + '%]</li>';
                        });
                        html += '</ul></div>';
                    }

                    if (dayAppts.length > 0) {
                        html += '<div style="margin-bottom:14px;"><strong>Appointments:</strong><ul style="margin:6px 0 0 18px; padding:0; font-size:13px; color:var(--text-main);">';
                        dayAppts.forEach(function(a) {
                            html += '<li style="margin-bottom:4px;"><strong>' + escHtml(a.user_name || 'User') + '</strong>: ' + escHtml(a.reason) + ' [<span style="text-transform:capitalize;">' + escHtml(a.status) + '</span>]</li>';
                        });
                        html += '</ul></div>';
                    }

                    if (dayAdminEvents.length === 0 && dayTasks.length === 0 && dayAppts.length === 0) {
                        html = '<p style="color:var(--text-light); margin:0 0 12px; font-size:13px;">No tasks, events, or appointments scheduled for this date.</p>';
                    }

                    // Call to Action
                    html += '<div style="margin-top:16px; padding-top:12px; border-top:1px solid var(--border); display:flex; justify-content:flex-end; gap:8px;">';
                    if (isLoggedIn) {
                        html += '<a href="' + calendarUrl + '" class="primary-button btn-sm" style="text-decoration:none;">Open in Monitoring Calendar →</a>';
                    } else {
                        html += '<a href="' + accessUrl + '" class="primary-button btn-sm" style="text-decoration:none;">Sign in to Book or Manage</a>';
                    }
                    html += '</div>';

                    calModalBody.innerHTML = html;
                    calOverlay.classList.add("active");
                    calOverlay.classList.add("open");
                }
            });
        });
    </script>
</body>
</html>
