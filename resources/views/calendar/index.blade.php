@php
    use App\Services\MonitoringAuth;
@endphp
@extends('layouts.app')

@section('title', 'Calendar & Schedule')
@section('breadcrumb', 'Calendar')


@push('styles')
<link rel="stylesheet" href="{{ asset_versioned('calendar.css') }}">
@endpush

@section('content')
<section class="content">

    <!-- PAGE INTRO -->
    <div class="page-intro">
        <div>
            <span class="welcome-label">SCHEDULE</span>
            <h1>Calendar &amp; Events</h1>
            <p>View task deadlines, appointments and manage upcoming activities.</p>
        </div>
        <div class="date-box">
            <span>Today</span>
            <strong>{{ date('F d, Y') }}</strong>
        </div>
    </div>

    @php
        $today       = date('Y-m-d');
        $firstDayRaw = (int)date('w', mktime(0, 0, 0, $month, 1, $year));
        $daysInMonth = (int)date('t', mktime(0, 0, 0, $month, 1, $year));

        // Build JSON-safe arrays for the JS grid click handler
        $tasksByDayJson = [];
        foreach ($tasksByDay as $day => $tasks) {
            foreach ($tasks as $t) {
                $tasksByDayJson[$day][] = [
                    'task_name' => $t->task_name,
                    'status'    => $t->status ?? 'Pending',
                    'progress'  => (int)$t->progress,
                ];
            }
        }

        $apptsByDayJson = [];
        foreach ($apptsByDay as $day => $appts) {
            foreach ($appts as $a) {
                $isOwnAppt = !$isAdmin && isset($currentFaeId) && $a->fae_id == $currentFaeId;
                if ($isAdmin || $isOwnAppt) {
                    // Full details for admin or own appointment
                    $apptsByDayJson[$day][] = [
                        'fae_id'     => $a->fae_id,
                        'user_name'  => $a->user_name ?? ($a->fae->name ?? 'User'),
                        'start_time' => $a->start_time,
                        'end_time'   => $a->end_time,
                        'time_slot'  => $a->time_slot,
                        'reason'     => $a->reason,
                        'status'     => $a->status,
                        'is_own'     => true,
                    ];
                } else {
                    // Masked: only show time slot and status so FAE knows it's taken
                    $apptsByDayJson[$day][] = [
                        'fae_id'     => null,
                        'user_name'  => null,
                        'start_time' => $a->start_time,
                        'end_time'   => $a->end_time,
                        'time_slot'  => $a->time_slot,
                        'reason'     => null,
                        'status'     => $a->status,
                        'is_own'     => false,
                    ];
                }
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

        // Flatten month appointments for the table
        // Non-admin FAEs only see their own appointments in the table
        $monthAppts = collect();
        foreach ($apptsByDay as $appts) {
            foreach ($appts as $a) {
                if ($isAdmin || (isset($currentFaeId) && $a->fae_id == $currentFaeId)) {
                    $monthAppts->push($a);
                }
            }
        }

        // Flatten month admin events for the sidebar
        $monthAdminEvents = collect();
        foreach ($adminEventsByDay as $events) {
            foreach ($events as $e) {
                $monthAdminEvents->push($e);
            }
        }
        $monthAdminEvents = $monthAdminEvents->unique('id');
    @endphp

    <!-- CALENDAR LAYOUT -->
    <div class="cal-layout">

        <!-- LEFT: Main Calendar -->
        <div class="cal-main">
            <div class="panel cal-panel">

                <!-- Month header -->
                <div class="cal-header">
                    <a href="{{ route('calendar.index', ['month' => $prevMonth, 'year' => $prevYear]) }}"
                       class="cal-nav-btn" title="Previous month">&#8249;</a>

                    <div class="cal-title">
                        <h2>{{ date('F', mktime(0,0,0,$month,1,$year)) }}</h2>
                        <span>{{ $year }}</span>
                    </div>

                    <div style="display: flex; align-items: center; gap: 8px;">
                        @if($isAdmin)
                            <button type="button" class="btn-add-event" id="openAddEventModalBtn" title="Add upcoming busy schedule or event">
                                + Add Event
                            </button>
                        @endif
                        <a href="{{ route('calendar.index', ['month' => $nextMonth, 'year' => $nextYear]) }}"
                           class="cal-nav-btn" title="Next month">&#8250;</a>
                    </div>
                </div>

                <!-- Day labels -->
                <div class="cal-day-labels">
                    @foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dl)
                        <div class="cal-day-label">{{ $dl }}</div>
                    @endforeach
                </div>

                <!-- Grid -->
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

                            @if($hasAdminEvents)
                                <div class="cal-events">
                                    @foreach (array_slice($adminEventsByDay[$day] instanceof \Illuminate\Support\Collection ? $adminEventsByDay[$day]->toArray() : (is_array($adminEventsByDay[$day]) ? $adminEventsByDay[$day] : iterator_to_array($adminEventsByDay[$day])), 0, 2) as $ae)
                                        @php
                                            $aeData = is_object($ae) ? $ae : (object)$ae;
                                            $catClass = 'ev-admin-' . ($aeData->category ?? 'other');
                                            
                                            $contClass = '';
                                            if (!empty($aeData->event_date)) {
                                                $evtStartStr = \Carbon\Carbon::parse($aeData->event_date)->format('Y-m-d');
                                                $evtEndStr = !empty($aeData->end_date) ? \Carbon\Carbon::parse($aeData->end_date)->format('Y-m-d') : $evtStartStr;
                                                if ($evtStartStr !== $evtEndStr) {
                                                    $curDateStr = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                                    $dayOfWeek = \Carbon\Carbon::createFromDate($year, $month, $day)->dayOfWeek;
                                                    
                                                    $isFirstDayOfEvt = ($curDateStr === $evtStartStr);
                                                    $isLastDayOfEvt = ($curDateStr === $evtEndStr);
                                                    $isFirstDayOfRow = ($dayOfWeek === 0);
                                                    $isLastDayOfRow = ($dayOfWeek === 6);

                                                    if ($isFirstDayOfEvt || $isFirstDayOfRow) {
                                                        if (!($isLastDayOfEvt || $isLastDayOfRow)) {
                                                            $contClass = 'ev-cont-start';
                                                        }
                                                    } else if ($isLastDayOfEvt || $isLastDayOfRow) {
                                                        $contClass = 'ev-cont-end';
                                                    } else {
                                                        $contClass = 'ev-cont-middle';
                                                    }
                                                }
                                            }
                                        @endphp
                                        <div class="cal-event {{ $catClass }} ev-admin {{ $contClass }}"
                                             title="Event: {{ $aeData->title }}">
                                            • {{ mb_strimwidth($aeData->title, 0, 14, '...') }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if($hasTasks)
                                <div class="cal-events">
                                    @foreach (array_slice(is_array($tasksByDay[$day]) ? $tasksByDay[$day] : $tasksByDay[$day]->toArray(), 0, 2) as $t)
                                        @php
                                            $tData = is_object($t) ? $t : (object)$t;
                                            $sc = strtolower(str_replace(' ', '', $tData->status ?? 'pending'));
                                            $ec = ($sc === 'inprogress') ? 'ev-progress' : 'ev-' . $sc;
                                        @endphp
                                        <div class="cal-event {{ $ec }}"
                                             title="Task: {{ $tData->task_name }} ({{ (int)($tData->progress ?? 0) }}%)">
                                            ✓ {{ mb_strimwidth($tData->task_name, 0, 14, '...') }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if($hasAppts)
                                <div class="cal-events">
                                    @foreach (array_slice(is_array($apptsByDay[$day]) ? $apptsByDay[$day] : $apptsByDay[$day]->toArray(), 0, 2) as $a)
                                        @php
                                            $aData = is_object($a) ? $a : (object)$a;
                                            $isPastDate = !empty($aData->appointment_date) ? (\Carbon\Carbon::parse($aData->appointment_date)->format('Y-m-d') < date('Y-m-d')) : false;
                                            $effSt = $isPastDate ? 'expired' : ($aData->status ?? 'pending');
                                            $apptClass = ($effSt === 'expired') ? 'ev-expired' : (($effSt === 'rejected') ? 'ev-overdue' : (($effSt === 'accepted') ? 'ev-completed' : 'ev-appt'));
                                            $isOwn = !$isAdmin && isset($currentFaeId) && ($aData->fae_id == $currentFaeId);
                                            $statusTxt = ucfirst($effSt);
                                        @endphp
                                        @if($isAdmin || $isOwn)
                                            {{-- Full details for admin or own appointment --}}
                                            @php $ownerName = $isOwn ? 'You' : ($aData->user_name ?? 'FAE'); @endphp
                                            <div class="cal-event {{ $apptClass }}"
                                                 style="{{ $effSt === 'expired' ? 'opacity:0.5;' : '' }}"
                                                 title="Appointment ({{ $ownerName }}): {{ $aData->time_slot ? '[' . $aData->time_slot . '] ' : '' }}{{ $aData->reason }} (Status: {{ $statusTxt }})">
                                                Appt: {{ $aData->time_slot ? $aData->time_slot : mb_strimwidth($aData->reason, 0, 10, '...') }} ({{ $ownerName }})
                                            </div>
                                        @else
                                            {{-- Masked: only show that time is unavailable --}}
                                            <div class="cal-event ev-appt" style="opacity:0.65; font-style:italic;"
                                                 title="This time slot is unavailable ({{ $aData->time_slot ?: 'Booked' }})">
                                                🔒 {{ $aData->time_slot ?: 'Slot Taken' }}
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endfor
                </div>

            </div>
        </div>

        <!-- RIGHT: Sidebar -->
        <div class="cal-side">
            <!-- Calendar Guide / Legend (FAE only) -->
            @if(!$isAdmin)
                <div class="wireframe-panel" style="background:white; padding:20px 20px; border-radius:8px;">
                    <h3>SCHEDULE GUIDE AND STATUS</h3>
                    <p style="font-size:10.5px; color:var(--text-light); line-height:1.35; margin:0 0 14px 0;">
                        Click any date to inspect scheduled events, field tasks, or appointments.
                    </p>
                    <div style="display:flex; flex-direction:column; gap:8px; font-size:10.5px;">
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
            @endif

            <div class="wireframe-panel" style="background:white; padding:20px 20px; border-radius:8px;">
                @php
                    $isGoogleConnected = \App\Services\MonitoringAuth::currentGoogleConnected();
                    $googleEmail = \App\Services\MonitoringAuth::currentGoogleEmail();
                @endphp
                <div class="google-tools" style="margin-top:0; padding-top:0; border-top:none;">
                    <div class="google-tools-header">
                        <strong>
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/>
                            </svg>
                            Google Workspace
                        </strong>
                        @if($isGoogleConnected)
                            <span class="google-status-badge connected" title="Connected to Google Account">
                                <span style="width:6px; height:6px; border-radius:50%; background:#10b981; display:inline-block;"></span>
                                Connected
                            </span>
                        @else
                            <span class="google-status-badge disconnected" title="Account not linked">
                                <span style="width:6px; height:6px; border-radius:50%; background:#f59e0b; display:inline-block;"></span>
                                Sign-in Required
                            </span>
                        @endif
                    </div>

                    @if($isGoogleConnected)
                        <p class="google-tools-desc">
                            Linked: <strong style="color:var(--text);">{{ $googleEmail }}</strong>
                        </p>

                        <div class="google-cards-grid">
                            <!-- GOOGLE CALENDAR -->
                            <a href="https://calendar.google.com" target="_blank" rel="noopener" class="google-app-card" title="Open Google Calendar">
                                <div class="google-app-card-top">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    <strong>Calendar</strong>
                                </div>
                                <span>View synced appointments &amp; deadlines</span>
                                <span class="btn-card-action">Open Calendar &rarr;</span>
                            </a>

                            <!-- GOOGLE DRIVE -->
                            <a href="https://drive.google.com" target="_blank" rel="noopener" class="google-app-card" title="Open Google Drive">
                                <div class="google-app-card-top">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                                    <strong>Drive</strong>
                                </div>
                                <span>Cloud reports &amp; file storage</span>
                                <span class="btn-card-action">Open Drive &rarr;</span>
                            </a>
                        </div>

                        <div style="display:flex; gap:6px; margin-top:6px;">
                            <button type="button" class="outline-button btn-sm" id="openDriveUploadModalBtn" style="flex:1; justify-content:center; display:inline-flex; align-items:center; gap:5px; font-size:11px;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                Upload to Google Drive
                            </button>
                            <form method="POST" action="{{ route('google.disconnect') }}" onsubmit="return confirm('Disconnect and log out this Google account?');" style="margin:0; display:inline;">
                                @csrf
                                <button type="submit" class="outline-button btn-sm" style="color:#ef4444; border-color:#fca5a5; font-size:11px; padding:6px 10px; display:inline-flex; align-items:center; gap:4px;" title="Log out / Disconnect Google Account">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                                    Log out
                                </button>
                            </form>
                        </div>
                    @else
                        <p class="google-tools-desc">
                            Connect your Google account to sync appointments with <strong>Google Calendar</strong> and store reports &amp; files in <strong>Google Drive</strong>.
                        </p>

                        <div class="google-cards-grid">
                            <!-- GOOGLE CALENDAR (NOT CONNECTED) -->
                            <a href="{{ route('google.redirect') }}" class="google-app-card" title="Sign in to link Google Calendar">
                                <div class="google-app-card-top">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    <strong>Calendar</strong>
                                </div>
                                <span>Sign in to enable appointment sync</span>
                                <span class="btn-card-action">Connect &rarr;</span>
                            </a>

                            <!-- GOOGLE DRIVE (NOT CONNECTED) -->
                            <a href="{{ route('google.redirect') }}" class="google-app-card" title="Sign in to link Google Drive">
                                <div class="google-app-card-top">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                                    <strong>Drive</strong>
                                </div>
                                <span>Sign in to store report files</span>
                                <span class="btn-card-action">Connect &rarr;</span>
                            </a>
                        </div>

                        <a href="{{ route('google.redirect') }}" class="google-connect-cta" style="margin-top:6px;">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z" fill="#FBBC05"/>
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z" fill="#EA4335"/>
                            </svg>
                            Sign In to Google Workspace
                        </a>
                    @endif
                </div>
            </div>

            <!-- Admin Events List -->
            @if($monthAdminEvents->count() > 0)
                <div class="panel" style="padding:16px 20px;">
                    <h3 style="font-size:13px; font-weight:700; margin-bottom:10px;">Events This Month</h3>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        @foreach ($monthAdminEvents as $ev)
                            @php 
                                $evData = is_object($ev) ? $ev : (object)$ev;
                                $catColor = match($evData->category ?? 'other') {
                                    'busy' => '#ef4444',
                                    'meeting' => '#8b5cf6',
                                    'reminder' => '#2563eb',
                                    default => '#ef4444'
                                };
                            @endphp
                            <div style="display:flex; justify-content:space-between; align-items:center; background:#f8fafc; padding:8px 10px; border-radius:7px; border:1px solid var(--border);">
                                <div>
                                    <strong style="font-size:12px; display:flex; align-items:center; gap:6px;">
                                        <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:{{ $catColor }};"></span>
                                        {{ $evData->title }}
                                    </strong>
                                    <small style="color:var(--text-light); font-size:10px;">
                                        {{ \Carbon\Carbon::parse($evData->event_date)->format('M d, Y') }}
                                        @if(!empty($evData->end_date) && \Carbon\Carbon::parse($evData->event_date)->format('Y-m-d') !== \Carbon\Carbon::parse($evData->end_date)->format('Y-m-d'))
                                            - {{ \Carbon\Carbon::parse($evData->end_date)->format('M d, Y') }}
                                        @endif
                                        &bull; {{ ucfirst($evData->category) }}
                                    </small>
                                </div>
                                @if($isAdmin)
                                    <form method="POST" action="{{ route('calendar.events.destroy') }}" onsubmit="return confirm('Delete this event?');">
                                        @csrf
                                        <input type="hidden" name="event_id" value="{{ $evData->id }}">
                                        <button type="submit" style="background:none; border:none; color:var(--red); cursor:pointer; font-size:11px; font-weight:600;">Delete</button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

    </div>

    <!-- APPOINTMENTS TABLE -->
    @if($monthAppts->count() > 0)
        <div class="panel" style="margin-top:20px;">
            <div class="panel-header">
                <div>
                    <h2>Month Appointments</h2>
                    <p>All appointment requests for {{ date('F Y', mktime(0,0,0,$month,1,$year)) }}</p>
                </div>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Date</th>
                            <th>Time Slot</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Admin Notes</th>
                            <th>Calendar</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($monthAppts as $ap)
                            @php
                                $isPast = $ap->is_past ?? (!empty($ap->appointment_date) && \Carbon\Carbon::parse($ap->appointment_date)->format('Y-m-d') < date('Y-m-d'));
                                $st = $ap->status;
                                $effectiveSt = $isPast ? 'expired' : $st;
                                $bCls = $effectiveSt === 'accepted' ? 'complete-badge' : ($effectiveSt === 'rejected' ? 'overdue-badge' : ($effectiveSt === 'expired' ? 'expired-badge' : ($effectiveSt === 'cancelled' ? 'cancelled-badge' : 'pending-badge')));
                                
                                if ($ap->start_time && $ap->end_time) {
                                    $dateStr = \Carbon\Carbon::parse($ap->appointment_date)->format('Y-m-d');
                                    $googleDate = \Carbon\Carbon::parse($dateStr . ' ' . $ap->start_time)->format('Ymd\THis');
                                    $googleEnd  = \Carbon\Carbon::parse($dateStr . ' ' . $ap->end_time)->format('Ymd\THis');
                                } else {
                                    $googleDate = \Carbon\Carbon::parse($ap->appointment_date)->format('Ymd');
                                    $googleEnd  = \Carbon\Carbon::parse($ap->appointment_date)->addDay()->format('Ymd');
                                }
                                $googleUrl  = 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' . urlencode('Meeting with Admin') . '&dates=' . $googleDate . '/' . $googleEnd . '&details=' . urlencode($ap->reason);
                            @endphp
                            <tr class="{{ $effectiveSt === 'expired' ? 'row-expired' : '' }}" style="{{ $effectiveSt === 'expired' ? 'opacity:0.55; background:#f8fafc;' : '' }}">
                                <td>
                                    <strong style="{{ $effectiveSt === 'expired' ? 'color:#94a3b8; text-decoration:line-through;' : '' }}">{{ $ap->user_name }}</strong>
                                    @if(!$isAdmin && isset($currentFaeId) && $ap->fae_id == $currentFaeId)
                                        <span class="badge" style="background:#e0f2fe; color:#0369a1; font-size:10px; margin-left:4px;">You</span>
                                    @endif
                                </td>
                                <td style="{{ $effectiveSt === 'expired' ? 'color:#94a3b8;' : '' }}">{{ \Carbon\Carbon::parse($ap->appointment_date)->format('M d, Y') }}</td>
                                <td>
                                    @if($ap->time_slot)
                                        <span class="badge" style="{{ $effectiveSt === 'expired' ? 'background:#f1f5f9; color:#94a3b8; border:1px solid #e2e8f0;' : 'background:#eef2ff; color:#4338ca; border:1px solid #c7d2fe;' }} font-weight:600;">{{ $ap->time_slot }}</span>
                                    @else
                                        <span style="color:var(--text-light); font-size:12px;">All Day</span>
                                    @endif
                                </td>
                                <td style="{{ $effectiveSt === 'expired' ? 'color:#94a3b8; text-decoration:line-through;' : '' }}">{{ $ap->reason }}</td>
                                <td><span class="badge {{ $bCls }}">{{ ucfirst($effectiveSt) }}{{ $isPast && $effectiveSt !== 'expired' ? ' (Past)' : '' }}</span></td>
                                <td><small style="color:{{ $effectiveSt === 'expired' ? '#cbd5e1' : 'var(--text-light)' }};">{{ $ap->admin_comment ?? '—' }}</small></td>
                                <td>
                                    @if($effectiveSt !== 'expired')
                                        <div style="display:flex; align-items:center; gap:6px;">
                                            @if(MonitoringAuth::currentGoogleConnected())
                                                <form method="POST" action="{{ route('google.syncCalendar') }}" style="display:inline;" title="Sync directly to Google Calendar">
                                                    @csrf
                                                    <input type="hidden" name="type" value="appointment">
                                                    <input type="hidden" name="id" value="{{ $ap->id }}">
                                                    <button type="submit" class="google-calendar-link" style="background:none; border:none; padding:0; cursor:pointer; font-size:11px; font-weight:600; text-decoration:none; white-space:nowrap;">
                                                         Sync
                                                    </button>
                                                </form>
                                                <span style="color:#cbd5e1;">|</span>
                                            @endif
                                            <a class="google-calendar-link" href="{{ $googleUrl }}" target="_blank" rel="noopener" style="font-size:11px;">Link</a>
                                        </div>
                                    @else
                                        <small style="color:#cbd5e1;">—</small>
                                    @endif
                                </td>

                                <td style="text-align:right;">
                                    @if($isAdmin)
                                        @if($effectiveSt === 'expired' || $st === 'cancelled')
                                            <form method="POST" action="{{ route('calendar.appointment.destroy') }}" onsubmit="return confirm('Permanently delete this appointment?');" style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="appt_id" value="{{ $ap->id }}">
                                                <button type="submit" class="tbl-action-btn" style="background:#fee2e2; color:#dc2626; border:1px solid #fca5a5; font-size:11px; display:inline-flex; align-items:center; gap:4px;" title="Delete appointment">
                                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                                    Delete
                                                </button>
                                            </form>
                                        @elseif($st === 'pending' && !$isPast)
                                            <form method="POST" action="{{ route('calendar.updateAppointmentStatus') }}" style="display:inline;">
                                                @csrf
                                                <input type="hidden" name="appt_id" value="{{ $ap->id }}">
                                                <input type="hidden" name="status" value="accepted">
                                                <button type="submit" class="tbl-action-btn tbl-btn-accept">Accept</button>
                                            </form>

                                            <button type="button" class="tbl-action-btn tbl-btn-reject btn-trigger-reject" data-id="{{ $ap->id }}">
                                                Reject
                                            </button>
                                        @else
                                            <small style="color:#9ca3af;">Handled</small>
                                        @endif
                                    @else
                                        @if(isset($currentFaeId) && $ap->fae_id == $currentFaeId)
                                            @if(in_array($st, ['pending', 'accepted', 'rejected']) && !$isPast)
                                                <form method="POST" action="{{ route('calendar.cancelAppointment') }}" onsubmit="return confirm('Are you sure you want to cancel your appointment booking?');" style="display:inline;">
                                                    @csrf
                                                    <input type="hidden" name="appt_id" value="{{ $ap->id }}">
                                                    <button type="submit" class="tbl-action-btn" style="background:#fff1f2; color:#e11d48; border:1px solid #fecdd3; font-size:11px; display:inline-flex; align-items:center; gap:4px;" title="Cancel appointment booking">
                                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                        Cancel Booking
                                                    </button>
                                                </form>
                                            @elseif($st === 'cancelled')
                                                <form method="POST" action="{{ route('calendar.appointment.destroy') }}" onsubmit="return confirm('Permanently delete this cancelled appointment?');" style="display:inline;">
                                                    @csrf
                                                    <input type="hidden" name="appt_id" value="{{ $ap->id }}">
                                                    <button type="submit" class="tbl-action-btn" style="background:#fee2e2; color:#dc2626; border:1px solid #fca5a5; font-size:11px; display:inline-flex; align-items:center; gap:4px;" title="Delete cancelled appointment">
                                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                                        Delete
                                                    </button>
                                                </form>
                                            @else
                                                <small style="color:#9ca3af;">—</small>
                                            @endif
                                        @else
                                            <small style="color:#9ca3af;">—</small>
                                        @endif
                                    @endif
                                </td>

                            </tr>

                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- FOOTER -->
    <footer>
        <span>Monitoring System © 2026</span>
        <span>Interactive Calendar Live</span>
    </footer>

</section>

<!-- =========================================================
     MODALS
     ========================================================= -->

<!-- Day Detail Modal -->
<div class="cal-modal-overlay" id="calModalOverlay">
    <div class="cal-modal">
        <div class="cal-modal-header">
            <h3 id="calModalTitle">Date Details</h3>
            <button type="button" class="cal-modal-close" id="calModalClose">&times;</button>
        </div>
        <div class="cal-modal-body" id="calModalBody"></div>
    </div>
</div>

<!-- Add Admin Event Modal -->
<div class="custom-modal-overlay" id="addEventModalOverlay">
    <div class="custom-modal">
        <div class="custom-modal-header">
            <h3>Add Event / Busy Schedule</h3>
            <button type="button" class="custom-modal-close" id="addEventModalClose">&times;</button>
        </div>
        <form method="POST" action="{{ route('calendar.events.store') }}">
            @csrf
            <div class="custom-modal-body">
                <div class="form-group">
                    <label class="form-label">Event Title <span class="req">*</span></label>
                    <input type="text" name="event_title" class="form-control" placeholder="e.g. System Deployment / Executive Meeting" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Start Date <span class="req">*</span></label>
                        <input type="date" name="event_date" id="addEventModalDate" class="form-control" required value="{{ $today }}" min="{{ $today }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" id="addEventModalEndDate" class="form-control" value="{{ $today }}" min="{{ $today }}">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="event_category" class="form-select">
                            <option value="meeting">Meeting</option>
                            <option value="busy">Busy / Unavailable</option>
                            <option value="reminder">Reminder</option>
                            <option value="other" selected>Event</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description / Notes</label>
                    <textarea name="event_description" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                </div>
            </div>
            <div class="custom-modal-footer">
                <button type="button" class="secondary-button btn-sm" id="cancelEventBtn">Cancel</button>
                <button type="submit" class="primary-button btn-sm">Save Event</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div class="custom-modal-overlay" id="rejectModalOverlay">
    <div class="custom-modal" style="max-width:420px;">
        <div class="custom-modal-header">
            <h3 style="color:var(--red);">Reject Appointment</h3>
            <button type="button" class="custom-modal-close" id="rejectModalClose">&times;</button>
        </div>
        <form method="POST" action="{{ route('calendar.updateAppointmentStatus') }}">
            @csrf
            <input type="hidden" name="status" value="rejected">
            <input type="hidden" name="appt_id" id="rejectApptId" value="">
            <div class="custom-modal-body">
                <div class="form-group">
                    <label class="form-label">Reason / Comment for Rejection</label>
                    <textarea name="admin_comment" class="form-control" rows="3" placeholder="Explain why the request was declined..."></textarea>
                </div>
            </div>
            <div class="custom-modal-footer">
                <button type="button" class="secondary-button btn-sm" id="cancelRejectBtn">Cancel</button>
                <button type="submit" class="btn-danger-outline btn-sm">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

<!-- Google Drive Upload Modal -->
<div class="custom-modal-overlay" id="driveUploadModalOverlay">
    <div class="custom-modal" style="max-width:440px;">
        <div class="custom-modal-header">
            <div style="display:flex; align-items:center; gap:8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
                <h3>Upload to Google Drive</h3>
            </div>
            <button type="button" class="custom-modal-close" id="driveUploadModalClose">&times;</button>
        </div>
        <form method="POST" action="{{ route('google.uploadDrive') }}" enctype="multipart/form-data">
            @csrf
            <div class="custom-modal-body">
                <p style="font-size:12px; color:var(--text-light); margin-bottom:14px;">
                    Upload inspection reports, photos, or documents directly to your connected Google Drive (<strong>{{ \App\Services\MonitoringAuth::currentGoogleEmail() }}</strong>).
                </p>

                <div class="form-group">
                    <label class="form-label">Select Report or Document <span class="req">*</span></label>
                    <input type="file" name="file" class="form-control" required accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip">
                    <small style="font-size:10px; color:var(--text-light); margin-top:4px; display:block;">Supported: PDF, Word, Excel, Images, ZIP (Max 25MB).</small>
                </div>
            </div>
            <div class="custom-modal-footer">
                <button type="button" class="secondary-button btn-sm" id="cancelDriveUploadBtn">Cancel</button>
                <button type="submit" class="primary-button btn-sm" style="background:#059669; border-color:#059669;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    Upload Now
                </button>
            </div>
        </form>
    </div>
</div>

@if(!$isAdmin)
<!-- Booking Modal (Triggered by clicking open date) -->
<div class="custom-modal-overlay" id="bookModalOverlay">
    <div class="custom-modal">
        <div class="custom-modal-header">
            <h3 id="bookModalTitle">Request Appointment</h3>
            <button type="button" class="custom-modal-close" id="bookModalClose">&times;</button>
        </div>
        <form method="POST" action="{{ route('calendar.book') }}">
            @csrf
            <div class="custom-modal-body">
                <p class="booking-identity">Requesting as <strong>{{ session('monitoring_fae_name', 'your FAE profile') }}</strong></p>
                
                <div class="form-group">
                    <label class="form-label">Selected Date</label>
                    <input type="date" name="appointment_date" id="bookModalDate" class="form-control" required readonly min="{{ $today }}" style="background:#f8fafc;">
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Start Time <span class="req">*</span></label>
                        <input type="time" name="start_time" id="bookModalStartTime" class="form-control" value="09:00" required step="1800">
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Time <span class="req">*</span></label>
                        <input type="time" name="end_time" id="bookModalEndTime" class="form-control" value="12:00" required step="1800">
                    </div>
                </div>

                <div id="alreadyBookedWarning" style="display:none; margin-bottom:14px; padding:12px; background:#fef2f2; border:1px solid #fecaca; border-radius:6px; font-size:12px; color:#991b1b;">
                    <div style="display:flex; align-items:flex-start; gap:8px;">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2" style="flex-shrink:0; margin-top:1px;"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <div>
                            <strong style="display:block; margin-bottom:2px; font-size:13px;">Double Booking Restricted</strong>
                            <span id="alreadyBookedWarningMsg">You already have an appointment booked on this date. You cannot request multiple appointments on the same day.</span>
                        </div>
                    </div>
                </div>


                <div id="bookedSlotsNotice" style="display:none; margin-bottom:14px; padding:10px 12px; background:#eff6ff; border:1px solid #bfdbfe; border-radius:6px; font-size:12px;">
                    <strong style="color:#1e40af; display:block; margin-bottom:4px;">Existing Bookings on this Date:</strong>
                    <div id="bookedSlotsList" style="color:#1e3a8a; line-height:1.4;"></div>
                    <small style="display:block; margin-top:4px; color:#64748b;">Please choose a time slot that does not overlap with any existing requests.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Discussion Topic <span class="req">*</span></label>
                    <textarea name="reason" id="bookModalReason" class="form-control" rows="3" required placeholder="Describe what you want to consult..."></textarea>
                </div>
            </div>
            <div class="custom-modal-footer">
                <button type="button" class="secondary-button btn-sm" id="cancelBookBtn">Cancel</button>
                <button type="submit" class="primary-button btn-sm" id="submitBookBtn">Submit Request</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
var tasksData = @json($tasksByDayJson);
var apptsData = @json($apptsByDayJson);
var adminEventsData = @json($adminEventsByDayJson);
var isAdmin = @json($isAdmin);
var currentFaeId = @json($currentFaeId);
var todayStr = "{{ $today }}";

var calOverlay = document.getElementById("calModalOverlay");
var calModalTitle = document.getElementById("calModalTitle");
var calModalBody = document.getElementById("calModalBody");
var calModalClose = document.getElementById("calModalClose");

var addEventOverlay = document.getElementById("addEventModalOverlay");
var openAddEventBtn = document.getElementById("openAddEventModalBtn");
var addEventClose = document.getElementById("addEventModalClose");
var cancelEventBtn = document.getElementById("cancelEventBtn");
var addEventModalDate = document.getElementById("addEventModalDate");

var bookOverlay = document.getElementById("bookModalOverlay");
var bookModalClose = document.getElementById("bookModalClose");
var bookModalDate = document.getElementById("bookModalDate");
var cancelBookBtn = document.getElementById("cancelBookBtn");

var rejectOverlay = document.getElementById("rejectModalOverlay");
var rejectModalClose = document.getElementById("rejectModalClose");
var cancelRejectBtn = document.getElementById("cancelRejectBtn");
var rejectApptId = document.getElementById("rejectApptId");

var driveUploadOverlay = document.getElementById("driveUploadModalOverlay");
var openDriveUploadBtn = document.getElementById("openDriveUploadModalBtn");
var driveUploadClose = document.getElementById("driveUploadModalClose");
var cancelDriveUploadBtn = document.getElementById("cancelDriveUploadBtn");

function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function openCalModal(overlay) {
    if (overlay) {
        overlay.classList.add("active");
        overlay.classList.add("open");
    }
}

function closeAllCalModals() {
    [calOverlay, addEventOverlay, bookOverlay, rejectOverlay, driveUploadOverlay].forEach(function(overlay) {
        if (overlay) {
            overlay.classList.remove("active");
            overlay.classList.remove("open");
        }
    });
}

if (calModalClose) calModalClose.addEventListener("click", closeAllCalModals);
if (addEventClose) addEventClose.addEventListener("click", closeAllCalModals);
if (cancelEventBtn) cancelEventBtn.addEventListener("click", closeAllCalModals);
if (bookModalClose) bookModalClose.addEventListener("click", closeAllCalModals);
if (cancelBookBtn) cancelBookBtn.addEventListener("click", closeAllCalModals);
if (rejectModalClose) rejectModalClose.addEventListener("click", closeAllCalModals);
if (cancelRejectBtn) cancelRejectBtn.addEventListener("click", closeAllCalModals);
if (driveUploadClose) driveUploadClose.addEventListener("click", closeAllCalModals);
if (cancelDriveUploadBtn) cancelDriveUploadBtn.addEventListener("click", closeAllCalModals);

if (openDriveUploadBtn) {
    openDriveUploadBtn.addEventListener("click", function() {
        openCalModal(driveUploadOverlay);
    });
}

if (openAddEventBtn) {
    openAddEventBtn.addEventListener("click", function() {
        if (addEventModalDate) {
            addEventModalDate.min = todayStr;
            if (!addEventModalDate.value || addEventModalDate.value < todayStr) {
                addEventModalDate.value = todayStr;
            }
        }
        if (addEventModalEndDate) {
            var minEnd = (addEventModalDate && addEventModalDate.value >= todayStr) ? addEventModalDate.value : todayStr;
            addEventModalEndDate.min = minEnd;
            if (!addEventModalEndDate.value || addEventModalEndDate.value < minEnd) {
                addEventModalEndDate.value = minEnd;
            }
        }
        openCalModal(addEventOverlay);
    });
}

if (addEventModalDate) {
    addEventModalDate.addEventListener("change", function() {
        if (this.value < todayStr) {
            this.value = todayStr;
        }
        if (addEventModalEndDate) {
            addEventModalEndDate.min = this.value;
            if (addEventModalEndDate.value && addEventModalEndDate.value < this.value) {
                addEventModalEndDate.value = this.value;
            }
        }
    });
}

document.querySelectorAll(".btn-trigger-reject").forEach(function(btn) {
    btn.addEventListener("click", function() {
        var apptId = this.getAttribute("data-id");
        if (rejectApptId) rejectApptId.value = apptId;
        openCalModal(rejectOverlay);
    });
});

[calOverlay, addEventOverlay, bookOverlay, rejectOverlay].forEach(function(overlay) {
    if (overlay) {
        overlay.addEventListener("click", function(e) {
            if (e.target === overlay) closeAllCalModals();
        });
    }
});

// Click cell to open appointment modal or event details
document.querySelectorAll(".cal-cell:not(.cal-cell--empty)").forEach(function(cell) {
    cell.addEventListener("click", function(e) {
        // Prevent clicking on previous/past dates
        if (this.classList.contains('cal-cell--past')) {
            return;
        }

        var dateStr = this.getAttribute("data-date");
        var dayNum = parseInt(this.getAttribute("data-day"), 10);

        var dayTasks = tasksData[dayNum] || [];
        var dayAppts = apptsData[dayNum] || [];
        var dayAdminEvents = adminEventsData[dayNum] || [];

        // For non-admin users, clicking a date cell opens the Appointment Booking modal
        if (!isAdmin && bookOverlay && bookModalDate) {
            bookModalDate.value = dateStr;

            var warningEl = document.getElementById("alreadyBookedWarning");
            var warningMsgEl = document.getElementById("alreadyBookedWarningMsg");
            var noticeEl = document.getElementById("bookedSlotsNotice");
            var listEl = document.getElementById("bookedSlotsList");
            var submitBtn = document.getElementById("submitBookBtn");
            var startTimeInput = document.getElementById("bookModalStartTime");
            var endTimeInput = document.getElementById("bookModalEndTime");
            var reasonInput = document.getElementById("bookModalReason");

            // Check if THIS FAE already has any appointment on this date (pending, accepted, or rejected)
            var myExistingBooking = dayAppts.find(function(a) {
                return (a.status === 'pending' || a.status === 'accepted' || a.status === 'rejected') && currentFaeId && a.fae_id == currentFaeId;
            });

            if (myExistingBooking) {
                var mySlot = myExistingBooking.time_slot || (myExistingBooking.start_time && myExistingBooking.end_time ? myExistingBooking.start_time + ' - ' + myExistingBooking.end_time : "Active Booking");
                var mySt = myExistingBooking.status ? myExistingBooking.status.charAt(0).toUpperCase() + myExistingBooking.status.slice(1) : "Pending";
                if (warningEl && warningMsgEl) {
                    warningMsgEl.textContent = "You already have a " + mySt + " appointment booked on this date (" + mySlot + "). Double booking on the same account for the same day is not permitted.";
                    warningEl.style.display = "block";
                }
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = "0.5";
                    submitBtn.style.cursor = "not-allowed";
                    submitBtn.title = "You cannot double book on this date";
                }
                if (startTimeInput) startTimeInput.disabled = true;
                if (endTimeInput) endTimeInput.disabled = true;
                if (reasonInput) reasonInput.disabled = true;
            } else {
                if (warningEl) warningEl.style.display = "none";
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = "1";
                    submitBtn.style.cursor = "pointer";
                    submitBtn.title = "";
                }
                if (startTimeInput) startTimeInput.disabled = false;
                if (endTimeInput) endTimeInput.disabled = false;
                if (reasonInput) reasonInput.disabled = false;
            }


            if (noticeEl && listEl) {
                var activeAppts = dayAppts.filter(function(a) {
                    return a.status === 'pending' || a.status === 'accepted';
                });

                if (activeAppts.length > 0) {
                    var bookedHtml = "";
                    activeAppts.forEach(function(a) {
                        var slot = a.time_slot || (a.start_time && a.end_time ? a.start_time + ' - ' + a.end_time : "All Day");
                        var st = a.status ? a.status.charAt(0).toUpperCase() + a.status.slice(1) : "Pending";
                        if (isAdmin || a.is_own) {
                            var applicant = a.is_own ? "You (" + (a.user_name || "FAE") + ")" : (a.user_name || "FAE");
                            bookedHtml += "• <strong>" + escHtml(slot) + "</strong> (" + escHtml(st) + ") — <em>" + escHtml(applicant) + "</em>" + (a.reason ? ": " + escHtml(a.reason) : "") + "<br>";
                        } else {
                            bookedHtml += "• <strong>" + escHtml(slot) + "</strong> (" + escHtml(st) + ") — <em style=\"color:#b91c1c;\">🔒 Slot Taken / Unavailable</em><br>";
                        }
                    });
                    listEl.innerHTML = bookedHtml;
                    noticeEl.style.display = "block";
                } else {
                    noticeEl.style.display = "none";
                }
            }

            openCalModal(bookOverlay);
            return;
        }

        // For admin users, clicking a date cell sets the date in Add Event modal and shows details
        if (isAdmin && addEventModalDate) {
            addEventModalDate.value = dateStr;
        }

        // Open details modal
        if (calOverlay && calModalBody && calModalTitle) {
            calModalTitle.textContent = "Details for " + dateStr;
            var html = "";

            if (dayAdminEvents.length > 0) {
                html += '<div style="margin-bottom:14px;"><strong>Events & Busy Schedules:</strong><ul style="margin:6px 0 0 18px; padding:0; font-size:13px; color:var(--text-main);">';
                dayAdminEvents.forEach(function(ev) {
                    html += '<li style="margin-bottom:4px;"><strong>' + escHtml(ev.title) + '</strong> (' + escHtml(ev.category) + ')' + (ev.description ? ' - ' + escHtml(ev.description) : '') + '</li>';
                });
                html += '</ul></div>';
            }

            if (dayTasks.length > 0) {
                html += '<div style="margin-bottom:14px;"><strong>Tasks Due:</strong><ul style="margin:6px 0 0 18px; padding:0; font-size:13px; color:var(--text-main);">';
                dayTasks.forEach(function(t) {
                    html += '<li style="margin-bottom:4px;"><strong>' + escHtml(t.task_name) + '</strong> - Progress: ' + t.progress + '% (' + escHtml(t.status) + ')</li>';
                });
                html += '</ul></div>';
            }

            if (dayAppts.length > 0) {
                html += '<div style="margin-bottom:14px;"><strong>Appointments:</strong><ul style="margin:6px 0 0 18px; padding:0; font-size:13px; color:var(--text-main);">';
                dayAppts.forEach(function(a) {
                    var slotText = a.time_slot ? ' (' + escHtml(a.time_slot) + ')' : (a.start_time && a.end_time ? ' (' + escHtml(a.start_time) + ' - ' + escHtml(a.end_time) + ')' : '');
                    var isPast = a.is_past || (dateStr < todayStr);
                    var effSt = (isPast && (a.status === 'pending' || a.status === 'expired')) ? 'Expired' : (a.status ? a.status.charAt(0).toUpperCase() + a.status.slice(1) : 'Pending');
                    if (isPast && effSt !== 'Expired') {
                        effSt += ' - Past';
                    }
                    var isExpired = (effSt === 'Expired');
                    var liStyle = isExpired ? 'margin-bottom:4px; opacity:0.5; color:#94a3b8;' : 'margin-bottom:4px;';
                    var nameStyle = isExpired ? 'color:#94a3b8;' : '';
                    var statusStyle = isExpired ? 'text-transform:capitalize; background:#f1f5f9; color:#64748b; padding:1px 6px; border-radius:3px; font-size:11px; border:1px solid #cbd5e1;' : 'text-transform:capitalize;';
                    
                    if (isAdmin || a.is_own) {
                        var displayName = a.is_own ? ('You (' + (a.user_name || 'FAE') + ')') : (a.user_name || 'User');
                        html += '<li style="' + liStyle + '"><strong style="' + nameStyle + '">' + escHtml(displayName) + '</strong>' + slotText + (a.reason ? ': ' + escHtml(a.reason) : '') + ' [<span style="' + statusStyle + '">' + escHtml(effSt) + '</span>]</li>';
                    } else {
                        html += '<li style="' + liStyle + '"><strong style="' + nameStyle + '">🔒 Slot Taken</strong>' + slotText + ' [<span style="' + statusStyle + '">' + escHtml(effSt) + '</span>]</li>';
                    }
                });
                html += '</ul></div>';
            }

            if (dayAdminEvents.length === 0 && dayTasks.length === 0 && dayAppts.length === 0) {
                html = '<p style="color:var(--text-light); margin:0 0 12px; font-size:13px;">No tasks, events, or appointments scheduled for this date.</p>';
            }

            if (isAdmin && dateStr >= todayStr) {
                html += '<div style="margin-top:16px; padding-top:12px; border-top:1px solid var(--border); text-align:right;">' +
                        '<button type="button" class="primary-button btn-sm" onclick="closeAllCalModals(); if(addEventModalDate){ addEventModalDate.value=\'' + dateStr + '\'; addEventModalDate.min=\'' + todayStr + '\'; } if(addEventModalEndDate){ addEventModalEndDate.value=\'' + dateStr + '\'; addEventModalEndDate.min=\'' + dateStr + '\'; } openCalModal(addEventOverlay);">' +
                        '+ Add Event on ' + dateStr + '</button></div>';
            }

            calModalBody.innerHTML = html;
            openCalModal(calOverlay);
        }
    });
});
</script>
@endpush
