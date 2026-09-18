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
                    'fae_name'  => $t->fae->name ?? 'Unassigned',
                    'deadline'  => $t->deadline ? $t->deadline->format('Y-m-d') : null,
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
                    'id'          => $e->id,
                    'title'       => $e->title,
                    'category'    => $e->category,
                    'location'    => $e->location,
                    'description' => $e->description,
                    'event_date'  => $e->event_date?->format('Y-m-d') ?: (string)$e->event_date,
                    'end_date'    => $e->end_date?->format('Y-m-d') ?: (string)$e->end_date,
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
                            $isLimitReached = !empty($limitReachedDays[$day]);
                        @endphp

                        <div class="{{ $cls }}"
                             data-date="{{ $dateStr }}"
                             data-day="{{ $day }}"
                             data-booked="{{ $isBooked ? '1' : '0' }}"
                             data-busy="{{ isset($busyDays[$day]) ? '1' : '0' }}"
                             data-limitreached="{{ $isLimitReached ? '1' : '0' }}"
                             data-schedcount="{{ $dayScheduleCounts[$day] ?? 0 }}">

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
                                            $isPastTaskDate = !empty($tData->deadline) ? (\Carbon\Carbon::parse($tData->deadline)->format('Y-m-d') < date('Y-m-d')) : false;
                                            $sc = strtolower(str_replace(' ', '', $tData->status ?? 'pending'));
                                            $ec = ($tData->status === 'Completed') ? 'ev-completed' : ($isPastTaskDate ? 'ev-expired' : (($sc === 'inprogress') ? 'ev-progress' : 'ev-' . $sc));
                                            $faeDisplayName = $tData->fae->name ?? ($tData->fae_name ?? '');
                                            $taskLabel = $tData->task_name . (!empty($faeDisplayName) ? " ({$faeDisplayName})" : '');
                                        @endphp
                                        <div class="cal-event {{ $ec }}"
                                             title="Task: {{ $taskLabel }} ({{ (int)($tData->progress ?? 0) }}% - {{ $tData->status ?? 'Pending' }})">
                                            ✓ {{ mb_strimwidth($taskLabel, 0, 16, '...') }}
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
                                            $apptClass = ($effSt === 'expired') ? 'ev-expired' : (($effSt === 'rejected') ? 'ev-overdue' : (($effSt === 'accepted') ? 'ev-accepted' : 'ev-appt'));
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
                            <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:#94a3b8;"></span>
                            <span>Completed Tasks (Archived)</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:6px;">
                            <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:#10b981;"></span>
                            <span>Accepted Bookings</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:6px;">
                            <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:#ef4444;"></span>
                            <span>Unavailable / Booked</span>
                        </div>
                    </div>
                </div>
            @endif



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
                            <div style="display:flex; justify-content:space-between; align-items:flex-start; background:#f8fafc; padding:10px 12px; border-radius:8px; border:1px solid var(--border); gap:8px;">
                                <div style="flex:1; min-width:0;">
                                    <strong style="font-size:12px; display:flex; align-items:center; gap:6px; color:#0f172a; word-break:break-word;">
                                        <span style="display:inline-block; width:7px; height:7px; border-radius:50%; background:{{ $catColor }}; flex-shrink:0;"></span>
                                        {{ $evData->title }}
                                    </strong>
                                    <div style="color:var(--text-light); font-size:10.5px; margin-top:2px;">
                                        {{ \Carbon\Carbon::parse($evData->event_date)->format('M d, Y') }}
                                        @if(!empty($evData->end_date) && \Carbon\Carbon::parse($evData->event_date)->format('Y-m-d') !== \Carbon\Carbon::parse($evData->end_date)->format('Y-m-d'))
                                            - {{ \Carbon\Carbon::parse($evData->end_date)->format('M d, Y') }}
                                        @endif
                                        &bull; {{ ucfirst($evData->category ?? 'event') }}
                                    </div>
                                    @if(!empty($evData->location))
                                        <div style="font-size:10.5px; color:#475569; display:flex; align-items:center; gap:4px; margin-top:2px;">
                                            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                            <span>{{ $evData->location }}</span>
                                        </div>
                                    @endif
                                </div>
                                @if($isAdmin)
                                    <div style="display:flex; gap:4px; align-items:center; flex-shrink:0;">
                                        <button type="button" class="btn-icon btn-edit-event"
                                                data-id="{{ $evData->id }}"
                                                data-title="{{ $evData->title }}"
                                                data-date="{{ \Carbon\Carbon::parse($evData->event_date)->format('Y-m-d') }}"
                                                data-enddate="{{ $evData->end_date ? \Carbon\Carbon::parse($evData->end_date)->format('Y-m-d') : '' }}"
                                                data-category="{{ $evData->category ?? 'other' }}"
                                                data-location="{{ $evData->location ?? '' }}"
                                                data-description="{{ $evData->description ?? '' }}"
                                                title="Edit Event"
                                                style="background:white; border:1px solid #cbd5e1; border-radius:4px; padding:4px 6px; cursor:pointer; color:#334155;">
                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                        </button>
                                        <form method="POST" action="{{ route('calendar.events.destroy') }}" onsubmit="return confirm('Delete this event?');" style="margin:0;">
                                            @csrf
                                            <input type="hidden" name="event_id" value="{{ $evData->id }}">
                                            <button type="submit" class="btn-icon" title="Delete Event" style="background:#fef2f2; border:1px solid #fecaca; border-radius:4px; padding:4px 6px; cursor:pointer; color:#b91c1c;">
                                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                            </button>
                                        </form>
                                    </div>
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
            <div class="panel-header" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
                <div>
                    <h2>Month Appointments &amp; Meeting Confirmations</h2>
                    <p>All appointment requests and meeting status tracking for {{ date('F Y', mktime(0,0,0,$month,1,$year)) }}</p>
                </div>
                <div style="display:flex; align-items:center; gap:8px;">
                    <label for="apptStatusFilter" style="font-size:12px; font-weight:600; color:var(--text-light);">Filter Status:</label>
                    <select id="apptStatusFilter" class="form-control" style="font-size:12px; padding:5px 10px; width:auto; border-radius:6px;">
                        <option value="all">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="accepted">Accepted</option>
                        <option value="completed">Completed</option>
                        <option value="not_completed">Not Completed</option>
                        <option value="rescheduled">Rescheduled</option>
                        <option value="rejected">Rejected</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
            </div>

            <div class="table-wrapper">
                <table id="monthApptsTable">
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th>Date</th>
                            <th>Time Slot</th>
                            <th>Reason</th>
                            <th>Status / Confirmation</th>
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
                                $effectiveSt = ($isPast && $st === 'pending') ? 'expired' : $st;
                                $bCls = match($effectiveSt) {
                                    'accepted', 'completed' => 'complete-badge',
                                    'rescheduled' => 'progress-badge',
                                    'not_completed', 'rejected' => 'overdue-badge',
                                    'expired', 'cancelled' => 'expired-badge',
                                    default => 'pending-badge'
                                };
                                $stLabel = match($effectiveSt) {
                                    'completed' => 'Completed',
                                    'not_completed' => 'Not Completed',
                                    'rescheduled' => 'Rescheduled',
                                    'accepted' => 'Accepted',
                                    'rejected' => 'Rejected',
                                    'cancelled' => 'Cancelled',
                                    'expired' => 'Expired',
                                    default => ucfirst($effectiveSt)
                                };
                                
                                if ($ap->start_time && $ap->end_time) {
                                    $dateStr = \Carbon\Carbon::parse($ap->appointment_date)->format('Y-m-d');
                                    $googleDate = \Carbon\Carbon::parse($dateStr . ' ' . $ap->start_time)->format('Ymd\THis');
                                    $googleEnd  = \Carbon\Carbon::parse($dateStr . ' ' . $ap->end_time)->format('Ymd\THis');
                                } else {
                                    $googleDate = \Carbon\Carbon::parse($ap->appointment_date)->format('Ymd');
                                    $googleEnd  = \Carbon\Carbon::parse($ap->appointment_date)->addDay()->format('Ymd');
                                }
                                $googleUrl  = 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' . urlencode('Meeting with Admin') . '&dates=' . $googleDate . '/' . $googleEnd . '&details=' . urlencode($ap->reason);
                                $slotStr = $ap->time_slot ?: ($ap->start_time && $ap->end_time ? $ap->start_time . ' - ' . $ap->end_time : 'All Day');
                            @endphp
                            <tr class="appt-row appt-row-{{ $effectiveSt }} {{ $effectiveSt === 'expired' ? 'row-expired' : '' }}" 
                                data-status="{{ $effectiveSt }}"
                                style="{{ $effectiveSt === 'expired' ? 'opacity:0.55; background:#f8fafc;' : '' }}">
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
                                <td style="{{ $effectiveSt === 'expired' ? 'color:#94a3b8; text-decoration:line-through;' : '' }} word-break:break-word;">{{ $ap->reason }}</td>
                                <td><span class="badge {{ $bCls }}">{{ $stLabel }}</span></td>
                                <td><small style="color:{{ $effectiveSt === 'expired' ? '#cbd5e1' : 'var(--text-light)' }};">{{ $ap->admin_comment ?? '—' }}</small></td>
                                <td>
                                    @if($effectiveSt !== 'expired' && $effectiveSt !== 'cancelled' && $effectiveSt !== 'rejected')
                                        <a class="google-calendar-link" href="{{ $googleUrl }}" target="_blank" rel="noopener" style="font-size:11px;">Link</a>
                                    @else
                                        <small style="color:#cbd5e1;">—</small>
                                    @endif
                                </td>

                                <td style="text-align:right;">
                                    <button type="button" class="tbl-action-btn btn-manage-appt"
                                            data-id="{{ $ap->id }}"
                                            data-user="{{ $ap->user_name }}"
                                            data-date="{{ \Carbon\Carbon::parse($ap->appointment_date)->format('M d, Y') }}"
                                            data-rawdate="{{ \Carbon\Carbon::parse($ap->appointment_date)->format('Y-m-d') }}"
                                            data-starttime="{{ $ap->start_time ? substr($ap->start_time, 0, 5) : '09:00' }}"
                                            data-endtime="{{ $ap->end_time ? substr($ap->end_time, 0, 5) : '10:00' }}"
                                            data-timeslot="{{ $slotStr }}"
                                            data-reason="{{ $ap->reason }}"
                                            data-status="{{ $effectiveSt }}"
                                            data-rawstatus="{{ $st }}"
                                            data-admincomment="{{ $ap->admin_comment ?? '' }}"
                                            data-ispast="{{ $isPast ? '1' : '0' }}"
                                            data-isown="{{ (!$isAdmin && isset($currentFaeId) && $ap->fae_id == $currentFaeId) ? '1' : '0' }}"
                                            style="background:#f1f5f9; color:#0f172a; border:1px solid #cbd5e1; font-size:11px; font-weight:600; display:inline-flex; align-items:center; gap:4px; padding:4px 9px; border-radius:6px; cursor:pointer;"
                                            title="Manage Appointment Details">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                                        Manage
                                    </button>
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
                        <label class="form-label">Category <span class="req">*</span></label>
                        <select name="event_category" class="form-select">
                            <option value="meeting">Meeting</option>
                            <option value="busy">Busy / Unavailable (Blocks Booking)</option>
                            <option value="reminder">Reminder</option>
                            <option value="other" selected>Event</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <input type="text" name="event_location" class="form-control" placeholder="e.g. Conference Room / Zoom">
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

<!-- Edit Admin Event Modal -->
<div class="custom-modal-overlay" id="editEventModalOverlay">
    <div class="custom-modal">
        <div class="custom-modal-header">
            <h3>Edit Event</h3>
            <button type="button" class="custom-modal-close" id="editEventModalClose">&times;</button>
        </div>
        <form id="editEventForm" method="POST" action="">
            @csrf
            @method('PUT')
            <div class="custom-modal-body">
                <div class="form-group">
                    <label class="form-label">Event Title <span class="req">*</span></label>
                    <input type="text" id="editEventTitle" name="event_title" class="form-control" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Start Date <span class="req">*</span></label>
                        <input type="date" id="editEventDate" name="event_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Date</label>
                        <input type="date" id="editEventEndDate" name="end_date" class="form-control">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Category <span class="req">*</span></label>
                        <select id="editEventCategory" name="event_category" class="form-select">
                            <option value="meeting">Meeting</option>
                            <option value="busy">Busy / Unavailable (Blocks Booking)</option>
                            <option value="reminder">Reminder</option>
                            <option value="other">Event</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Location</label>
                        <input type="text" id="editEventLocation" name="event_location" class="form-control" placeholder="e.g. Conference Room / Zoom">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description / Notes</label>
                    <textarea id="editEventDescription" name="event_description" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="custom-modal-footer">
                <button type="button" class="secondary-button btn-sm" id="cancelEditEventBtn">Cancel</button>
                <button type="submit" class="primary-button btn-sm">Update Event</button>
            </div>
        </form>
    </div>
</div>

<!-- Manage Appointment Modal -->
<div class="custom-modal-overlay" id="manageApptModalOverlay">
    <div class="custom-modal" style="max-width:520px;">
        <div class="custom-modal-header">
            <h3>Manage Appointment &amp; Confirmation</h3>
            <button type="button" class="custom-modal-close" id="manageApptModalClose">&times;</button>
        </div>
        <div class="custom-modal-body">
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:14px; margin-bottom:16px;">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:8px;">
                    <div>
                        <strong id="manageApptUser" style="font-size:14px; color:#0f172a; display:block;">—</strong>
                        <span id="manageApptDateTime" style="font-size:12px; color:#64748b;">—</span>
                    </div>
                    <span id="manageApptStatusBadge" class="badge">Pending</span>
                </div>
                <div style="font-size:12.5px; color:#334155; margin-top:8px; line-height:1.4;">
                    <strong style="display:block; font-size:11px; color:#64748b; text-transform:uppercase;">Discussion Topic:</strong>
                    <p id="manageApptReason" style="margin:4px 0 0 0;">—</p>
                </div>
                <div id="manageApptAdminNoteWrapper" style="display:none; margin-top:10px; padding-top:8px; border-top:1px dashed #cbd5e1; font-size:12px;">
                    <strong style="color:#b91c1c; font-size:11px; text-transform:uppercase;">Admin Note / Remarks:</strong>
                    <p id="manageApptAdminNote" style="margin:2px 0 0 0; color:#475569;">—</p>
                </div>
            </div>

            <!-- Admin Actions Container -->
            <div id="manageApptAdminActions" style="display:none;">
                <form method="POST" action="{{ route('calendar.updateAppointmentStatus') }}" id="acceptApptForm" style="display:inline;">
                    @csrf
                    <input type="hidden" name="appt_id" id="manageApptIdAccept">
                    <input type="hidden" name="status" value="accepted">
                </form>

                <div style="display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap;">
                    <button type="button" class="btn-danger-outline btn-sm" id="btnManageTriggerReject" style="display:none;">Reject Request</button>
                    <button type="button" class="primary-button btn-sm" id="btnManageAccept" onclick="document.getElementById('acceptApptForm').submit();" style="display:none;">Accept Appointment</button>
                    <button type="button" class="outline-button btn-sm" id="btnManageTriggerResched" style="display:none; color:#b45309; border-color:#fde68a; background:#fef3c7;">Reschedule</button>
                    <button type="button" class="outline-button btn-sm" id="btnManageTriggerNotCompleted" style="display:none; color:#991b1b; border-color:#fecaca; background:#fee2e2;">Not Completed</button>
                    <button type="button" class="primary-button btn-sm" id="btnManageTriggerComplete" style="display:none; background:#16a34a; border-color:#15803d;">Mark Completed</button>
                </div>
            </div>

            <!-- FAE Actions Container -->
            <div id="manageApptFaeActions" style="display:none;">
                <div style="display:flex; gap:8px; justify-content:flex-end;">
                    <form method="POST" action="{{ route('calendar.cancelAppointment') }}" id="cancelApptForm" style="display:none;" onsubmit="return confirm('Are you sure you want to cancel your appointment?');">
                        @csrf
                        <input type="hidden" name="appt_id" id="manageApptIdCancel">
                        <button type="submit" class="btn-danger-outline btn-sm">Cancel Booking</button>
                    </form>

                    <form method="POST" action="{{ route('calendar.appointment.destroy') }}" id="deleteApptForm" style="display:none;" onsubmit="return confirm('Permanently delete this appointment?');">
                        @csrf
                        <input type="hidden" name="appt_id" id="manageApptIdDelete">
                        <button type="submit" class="btn-danger-outline btn-sm">Delete Appointment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Complete Confirmation Modal -->
<div class="custom-modal-overlay" id="completeModalOverlay">
    <div class="custom-modal" style="max-width:440px;">
        <div class="custom-modal-header">
            <h3 style="color:#15803d;">Confirm Meeting Completion</h3>
            <button type="button" class="custom-modal-close" id="completeModalClose">&times;</button>
        </div>
        <form method="POST" action="{{ route('calendar.updateAppointmentStatus') }}">
            @csrf
            <input type="hidden" name="status" value="completed">
            <input type="hidden" name="appt_id" id="completeApptId" value="">
            <div class="custom-modal-body">
                <p style="font-size:13px; color:#334155; margin-bottom:12px;">Confirm that this meeting schedule has been successfully completed.</p>
                <div class="form-group">
                    <label class="form-label">Meeting Outcome / Completion Remarks</label>
                    <textarea name="admin_comment" class="form-control" rows="3" placeholder="e.g. Discussed regional project milestones and approved task execution plan..."></textarea>
                </div>
            </div>
            <div class="custom-modal-footer">
                <button type="button" class="secondary-button btn-sm" id="cancelCompleteBtn">Cancel</button>
                <button type="submit" class="primary-button btn-sm" style="background:#16a34a; border-color:#15803d;">Confirm Completed</button>
            </div>
        </form>
    </div>
</div>

<!-- Not Completed Modal -->
<div class="custom-modal-overlay" id="notCompletedModalOverlay">
    <div class="custom-modal" style="max-width:440px;">
        <div class="custom-modal-header">
            <h3 style="color:#dc2626;">Mark Meeting as Not Completed</h3>
            <button type="button" class="custom-modal-close" id="notCompletedModalClose">&times;</button>
        </div>
        <form method="POST" action="{{ route('calendar.updateAppointmentStatus') }}">
            @csrf
            <input type="hidden" name="status" value="not_completed">
            <input type="hidden" name="appt_id" id="notCompletedApptId" value="">
            <div class="custom-modal-body">
                <p style="font-size:13px; color:#334155; margin-bottom:12px;">Mark this meeting as not held, cancelled, or no-show.</p>
                <div class="form-group">
                    <label class="form-label">Reason / Remarks <span class="req">*</span></label>
                    <textarea name="admin_comment" class="form-control" rows="3" required placeholder="Explain why the meeting was not completed (e.g. Client unavailable, emergency schedule conflict)..."></textarea>
                </div>
            </div>
            <div class="custom-modal-footer">
                <button type="button" class="secondary-button btn-sm" id="cancelNotCompletedBtn">Cancel</button>
                <button type="submit" class="btn-danger-outline btn-sm">Confirm Not Completed</button>
            </div>
        </form>
    </div>
</div>

<!-- Reschedule Modal -->
<div class="custom-modal-overlay" id="rescheduleModalOverlay">
    <div class="custom-modal" style="max-width:480px;">
        <div class="custom-modal-header">
            <h3 style="color:#b45309;">Reschedule Appointment</h3>
            <button type="button" class="custom-modal-close" id="rescheduleModalClose">&times;</button>
        </div>
        <form method="POST" action="{{ route('calendar.updateAppointmentStatus') }}">
            @csrf
            <input type="hidden" name="status" value="rescheduled">
            <input type="hidden" name="appt_id" id="rescheduleApptId" value="">
            <div class="custom-modal-body">
                <div class="form-group">
                    <label class="form-label">New Appointment Date <span class="req">*</span></label>
                    <input type="date" name="rescheduled_date" id="rescheduleModalDate" class="form-control" required min="{{ $today }}" value="{{ $today }}">
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
                    <div class="form-group">
                        <label class="form-label">Start Time <span class="req">*</span></label>
                        <input type="time" name="rescheduled_start_time" id="rescheduleModalStartTime" class="form-control" value="09:00" required step="1800">
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Time <span class="req">*</span></label>
                        <input type="time" name="rescheduled_end_time" id="rescheduleModalEndTime" class="form-control" value="10:00" required step="1800">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Reschedule Reason / Comment <span class="req">*</span></label>
                    <textarea name="admin_comment" class="form-control" rows="3" required placeholder="Explain why the meeting was moved to this new schedule..."></textarea>
                </div>
            </div>
            <div class="custom-modal-footer">
                <button type="button" class="secondary-button btn-sm" id="cancelRescheduleBtn">Cancel</button>
                <button type="submit" class="primary-button btn-sm" style="background:#d97706; border-color:#b45309;">Save Reschedule</button>
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
                            <strong id="alreadyBookedWarningTitle" style="display:block; margin-bottom:2px; font-size:13px;">Booking Restricted</strong>
                            <span id="alreadyBookedWarningMsg">You already have an appointment booked on this date.</span>
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
var limitReachedData = @json($limitReachedDays ?? []);
var dayScheduleCounts = @json($dayScheduleCounts ?? []);
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
var addEventModalEndDate = document.getElementById("addEventModalEndDate");

var editEventOverlay = document.getElementById("editEventModalOverlay");
var editEventClose = document.getElementById("editEventModalClose");
var cancelEditEventBtn = document.getElementById("cancelEditEventBtn");

var manageApptOverlay = document.getElementById("manageApptModalOverlay");
var manageApptClose = document.getElementById("manageApptModalClose");

var completeOverlay = document.getElementById("completeModalOverlay");
var completeClose = document.getElementById("completeModalClose");
var cancelCompleteBtn = document.getElementById("cancelCompleteBtn");
var completeApptId = document.getElementById("completeApptId");

var notCompletedOverlay = document.getElementById("notCompletedModalOverlay");
var notCompletedClose = document.getElementById("notCompletedModalClose");
var cancelNotCompletedBtn = document.getElementById("cancelNotCompletedBtn");
var notCompletedApptId = document.getElementById("notCompletedApptId");

var rescheduleOverlay = document.getElementById("rescheduleModalOverlay");
var rescheduleClose = document.getElementById("rescheduleModalClose");
var cancelRescheduleBtn = document.getElementById("cancelRescheduleBtn");
var rescheduleApptId = document.getElementById("rescheduleApptId");
var rescheduleModalDate = document.getElementById("rescheduleModalDate");
var rescheduleModalStartTime = document.getElementById("rescheduleModalStartTime");
var rescheduleModalEndTime = document.getElementById("rescheduleModalEndTime");

var bookOverlay = document.getElementById("bookModalOverlay");
var bookModalClose = document.getElementById("bookModalClose");
var bookModalDate = document.getElementById("bookModalDate");
var cancelBookBtn = document.getElementById("cancelBookBtn");

var rejectOverlay = document.getElementById("rejectModalOverlay");
var rejectModalClose = document.getElementById("rejectModalClose");
var cancelRejectBtn = document.getElementById("cancelRejectBtn");
var rejectApptId = document.getElementById("rejectApptId");

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
        overlay.style.display = "flex";
    }
}

function closeAllCalModals() {
    [calOverlay, addEventOverlay, editEventOverlay, manageApptOverlay, completeOverlay, notCompletedOverlay, rescheduleOverlay, bookOverlay, rejectOverlay].forEach(function(overlay) {
        if (overlay) {
            overlay.classList.remove("active");
            overlay.classList.remove("open");
            overlay.style.display = "none";
        }
    });
}

if (calModalClose) calModalClose.addEventListener("click", closeAllCalModals);
if (addEventClose) addEventClose.addEventListener("click", closeAllCalModals);
if (cancelEventBtn) cancelEventBtn.addEventListener("click", closeAllCalModals);
if (editEventClose) editEventClose.addEventListener("click", closeAllCalModals);
if (cancelEditEventBtn) cancelEditEventBtn.addEventListener("click", closeAllCalModals);
if (manageApptClose) manageApptClose.addEventListener("click", closeAllCalModals);
if (completeClose) completeClose.addEventListener("click", closeAllCalModals);
if (cancelCompleteBtn) cancelCompleteBtn.addEventListener("click", closeAllCalModals);
if (notCompletedClose) notCompletedClose.addEventListener("click", closeAllCalModals);
if (cancelNotCompletedBtn) cancelNotCompletedBtn.addEventListener("click", closeAllCalModals);
if (rescheduleClose) rescheduleClose.addEventListener("click", closeAllCalModals);
if (cancelRescheduleBtn) cancelRescheduleBtn.addEventListener("click", closeAllCalModals);
if (bookModalClose) bookModalClose.addEventListener("click", closeAllCalModals);
if (cancelBookBtn) cancelBookBtn.addEventListener("click", closeAllCalModals);
if (rejectModalClose) rejectModalClose.addEventListener("click", closeAllCalModals);
if (cancelRejectBtn) cancelRejectBtn.addEventListener("click", closeAllCalModals);

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

// Edit Event Buttons
document.querySelectorAll(".btn-edit-event").forEach(function(btn) {
    btn.addEventListener("click", function() {
        var eventId = this.getAttribute("data-id");
        var form = document.getElementById("editEventForm");
        form.action = "{{ url('calendar/events') }}/" + eventId;

        document.getElementById("editEventTitle").value = this.getAttribute("data-title") || "";
        document.getElementById("editEventDate").value = this.getAttribute("data-date") || "";
        document.getElementById("editEventEndDate").value = this.getAttribute("data-enddate") || "";
        document.getElementById("editEventCategory").value = this.getAttribute("data-category") || "other";
        document.getElementById("editEventLocation").value = this.getAttribute("data-location") || "";
        document.getElementById("editEventDescription").value = this.getAttribute("data-description") || "";

        openCalModal(editEventOverlay);
    });
});

// Manage Appointment Modal Triggers
document.querySelectorAll(".btn-manage-appt").forEach(function(btn) {
    btn.addEventListener("click", function() {
        var apptId = this.getAttribute("data-id");
        var user = this.getAttribute("data-user");
        var date = this.getAttribute("data-date");
        var rawDate = this.getAttribute("data-rawdate") || todayStr;
        var startTime = this.getAttribute("data-starttime") || "09:00";
        var endTime = this.getAttribute("data-endtime") || "10:00";
        var timeSlot = this.getAttribute("data-timeslot");
        var reason = this.getAttribute("data-reason");
        var status = this.getAttribute("data-status");
        var rawStatus = this.getAttribute("data-rawstatus");
        var adminComment = this.getAttribute("data-admincomment");
        var isPast = this.getAttribute("data-ispast") === "1";
        var isOwn = this.getAttribute("data-isown") === "1";

        document.getElementById("manageApptUser").textContent = user;
        document.getElementById("manageApptDateTime").textContent = date + " • " + timeSlot;
        document.getElementById("manageApptReason").textContent = reason;

        var statusBadge = document.getElementById("manageApptStatusBadge");
        var statusLabel = status;
        if (status === 'completed') statusLabel = 'Completed';
        else if (status === 'not_completed') statusLabel = 'Not Completed';
        else if (status === 'rescheduled') statusLabel = 'Rescheduled';
        else if (status) statusLabel = status.charAt(0).toUpperCase() + status.slice(1);
        else statusLabel = "Pending";

        statusBadge.textContent = statusLabel;
        var bClass = "pending-badge";
        if (status === 'accepted' || status === 'completed') bClass = 'complete-badge';
        else if (status === 'rescheduled') bClass = 'progress-badge';
        else if (status === 'rejected' || status === 'not_completed') bClass = 'overdue-badge';
        else if (status === 'expired' || status === 'cancelled') bClass = 'expired-badge';
        statusBadge.className = "badge " + bClass;

        var noteWrapper = document.getElementById("manageApptAdminNoteWrapper");
        var noteText = document.getElementById("manageApptAdminNote");
        if (adminComment && adminComment.trim().length > 0) {
            noteText.textContent = adminComment;
            noteWrapper.style.display = "block";
        } else {
            noteWrapper.style.display = "none";
        }

        var adminActions = document.getElementById("manageApptAdminActions");
        var faeActions = document.getElementById("manageApptFaeActions");

        var btnAccept = document.getElementById("btnManageAccept");
        var btnReject = document.getElementById("btnManageTriggerReject");
        var btnResched = document.getElementById("btnManageTriggerResched");
        var btnNotCompleted = document.getElementById("btnManageTriggerNotCompleted");
        var btnComplete = document.getElementById("btnManageTriggerComplete");

        if (isAdmin) {
            adminActions.style.display = "block";
            faeActions.style.display = "none";
            document.getElementById("manageApptIdAccept").value = apptId;

            // Display contextual admin action buttons
            if (rawStatus === 'pending' && !isPast) {
                if (btnAccept) btnAccept.style.display = "inline-flex";
                if (btnReject) btnReject.style.display = "inline-flex";
                if (btnResched) btnResched.style.display = "inline-flex";
                if (btnComplete) btnComplete.style.display = "none";
                if (btnNotCompleted) btnNotCompleted.style.display = "none";
            } else if (rawStatus === 'accepted' || rawStatus === 'rescheduled') {
                if (btnAccept) btnAccept.style.display = "none";
                if (btnReject) btnReject.style.display = "none";
                if (btnResched) btnResched.style.display = "inline-flex";
                if (btnComplete) btnComplete.style.display = "inline-flex";
                if (btnNotCompleted) btnNotCompleted.style.display = "inline-flex";
            } else {
                if (btnAccept) btnAccept.style.display = "none";
                if (btnReject) btnReject.style.display = "none";
                if (btnResched) btnResched.style.display = "none";
                if (btnComplete) btnComplete.style.display = "none";
                if (btnNotCompleted) btnNotCompleted.style.display = "none";
            }

            if (btnReject) {
                btnReject.onclick = function() {
                    closeAllCalModals();
                    if (rejectApptId) rejectApptId.value = apptId;
                    openCalModal(rejectOverlay);
                };
            }

            if (btnComplete) {
                btnComplete.onclick = function() {
                    closeAllCalModals();
                    if (completeApptId) completeApptId.value = apptId;
                    openCalModal(completeOverlay);
                };
            }

            if (btnNotCompleted) {
                btnNotCompleted.onclick = function() {
                    closeAllCalModals();
                    if (notCompletedApptId) notCompletedApptId.value = apptId;
                    openCalModal(notCompletedOverlay);
                };
            }

            if (btnResched) {
                btnResched.onclick = function() {
                    closeAllCalModals();
                    if (rescheduleApptId) rescheduleApptId.value = apptId;
                    if (rescheduleModalDate) {
                        rescheduleModalDate.value = rawDate >= todayStr ? rawDate : todayStr;
                        rescheduleModalDate.min = todayStr;
                    }
                    if (rescheduleModalStartTime) rescheduleModalStartTime.value = startTime;
                    if (rescheduleModalEndTime) rescheduleModalEndTime.value = endTime;
                    openCalModal(rescheduleOverlay);
                };
            }
        } else if (isOwn) {
            adminActions.style.display = "none";
            faeActions.style.display = "block";

            var cancelForm = document.getElementById("cancelApptForm");
            var deleteForm = document.getElementById("deleteApptForm");

            if (['pending', 'accepted', 'rescheduled'].includes(rawStatus) && !isPast) {
                cancelForm.style.display = "inline";
                document.getElementById("manageApptIdCancel").value = apptId;
            } else {
                cancelForm.style.display = "none";
            }

            if (['cancelled', 'rejected', 'expired'].includes(rawStatus) || isPast) {
                deleteForm.style.display = "inline";
                document.getElementById("manageApptIdDelete").value = apptId;
            } else {
                deleteForm.style.display = "none";
            }
        } else {
            adminActions.style.display = "none";
            faeActions.style.display = "none";
        }

        openCalModal(manageApptOverlay);
    });
});

// Month Appointments Status Filter
var apptFilter = document.getElementById("apptStatusFilter");
if (apptFilter) {
    apptFilter.addEventListener("change", function() {
        var selectedStatus = this.value.toLowerCase();
        document.querySelectorAll(".appt-row").forEach(function(row) {
            var rowStatus = (row.getAttribute("data-status") || "").toLowerCase();
            if (selectedStatus === "all" || rowStatus === selectedStatus) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });
    });
}

[calOverlay, addEventOverlay, editEventOverlay, manageApptOverlay, completeOverlay, notCompletedOverlay, rescheduleOverlay, bookOverlay, rejectOverlay].forEach(function(overlay) {
    if (overlay) {
        overlay.addEventListener("click", function(e) {
            if (e.target === overlay) closeAllCalModals();
        });
    }
});

// Click cell to open appointment modal or event details
document.querySelectorAll(".cal-cell:not(.cal-cell--empty)").forEach(function(cell) {
    cell.addEventListener("click", function(e) {
        var isPastCell = this.classList.contains('cal-cell--past');
        var dateStr = this.getAttribute("data-date");
        var dayNum = parseInt(this.getAttribute("data-day"), 10);
        var isLimitReached = this.getAttribute("data-limitreached") === "1" || (limitReachedData && limitReachedData[dayNum]);

        var dayTasks = tasksData[dayNum] || [];
        var dayAppts = apptsData[dayNum] || [];
        var dayAdminEvents = adminEventsData[dayNum] || [];

        // For non-admin users, clicking an open date cell opens the Appointment Booking modal
        if (!isAdmin && bookOverlay && bookModalDate) {
            if (isPastCell) {
                // Past days are gray and cannot be booked
                return;
            }

            bookModalDate.value = dateStr;

            var warningEl = document.getElementById("alreadyBookedWarning");
            var warningTitleEl = document.getElementById("alreadyBookedWarningTitle");
            var warningMsgEl = document.getElementById("alreadyBookedWarningMsg");
            var noticeEl = document.getElementById("bookedSlotsNotice");
            var listEl = document.getElementById("bookedSlotsList");
            var submitBtn = document.getElementById("submitBookBtn");
            var startTimeInput = document.getElementById("bookModalStartTime");
            var endTimeInput = document.getElementById("bookModalEndTime");
            var reasonInput = document.getElementById("bookModalReason");

            // Check if Admin is Busy on this date
            var isAdminBusy = dayAdminEvents.some(function(ev) {
                return ev.category === 'busy';
            });

            // Check if THIS FAE already has any appointment on this date (pending, accepted, or rescheduled)
            var myExistingBooking = dayAppts.find(function(a) {
                return (a.status === 'pending' || a.status === 'accepted' || a.status === 'rescheduled') && currentFaeId && a.fae_id == currentFaeId;
            });

            if (isAdminBusy) {
                if (warningEl && warningMsgEl) {
                    if (warningTitleEl) warningTitleEl.textContent = "Admin Unavailable";
                    warningMsgEl.textContent = "The Administrator is marked as BUSY / Unavailable on this date. Appointment booking is disabled.";
                    warningEl.style.display = "block";
                }
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = "0.5";
                    submitBtn.style.cursor = "not-allowed";
                    submitBtn.title = "Admin is busy on this date";
                }
                if (startTimeInput) startTimeInput.disabled = true;
                if (endTimeInput) endTimeInput.disabled = true;
                if (reasonInput) reasonInput.disabled = true;
            } else if (isLimitReached) {
                // 2 SCHEDULES LIMITATION
                if (warningEl && warningMsgEl) {
                    if (warningTitleEl) warningTitleEl.textContent = "Daily Limit Reached (2 Schedules Max)";
                    warningMsgEl.textContent = "This date has reached the maximum limitation of 2 schedules per day. Adding another appointment is disabled.";
                    warningEl.style.display = "block";
                }
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.style.opacity = "0.5";
                    submitBtn.style.cursor = "not-allowed";
                    submitBtn.title = "Maximum 2 appointments per day reached";
                }
                if (startTimeInput) startTimeInput.disabled = true;
                if (endTimeInput) endTimeInput.disabled = true;
                if (reasonInput) reasonInput.disabled = true;
            } else if (myExistingBooking) {
                var mySlot = myExistingBooking.time_slot || (myExistingBooking.start_time && myExistingBooking.end_time ? myExistingBooking.start_time + ' - ' + myExistingBooking.end_time : "Active Booking");
                var mySt = myExistingBooking.status ? myExistingBooking.status.charAt(0).toUpperCase() + myExistingBooking.status.slice(1) : "Pending";
                if (warningEl && warningMsgEl) {
                    if (warningTitleEl) warningTitleEl.textContent = "Double Booking Restricted";
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
                    return a.status === 'pending' || a.status === 'accepted' || a.status === 'rescheduled';
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
                    var locStr = ev.location ? ' <span style="color:#64748b; font-size:11.5px;">(@ ' + escHtml(ev.location) + ')</span>' : '';
                    html += '<li style="margin-bottom:4px;"><strong>' + escHtml(ev.title) + '</strong> (' + escHtml(ev.category) + ')' + locStr + (ev.description ? ' - ' + escHtml(ev.description) : '') + '</li>';
                });
                html += '</ul></div>';
            }

            if (dayTasks.length > 0) {
                html += '<div style="margin-bottom:14px;"><strong>Tasks Due:</strong><ul style="margin:6px 0 0 18px; padding:0; font-size:13px; color:var(--text-main);">';
                dayTasks.forEach(function(t) {
                    var isPastTask = t.deadline ? (t.deadline < todayStr) : (dateStr < todayStr);
                    var isCompleted = t.status === 'Completed';
                    var isExpired = isPastTask && !isCompleted;
                    var faeSuffix = t.fae_name ? ' <span style="color:#2563eb; font-weight:600;">(' + escHtml(t.fae_name) + ')</span>' : '';
                    var taskLiStyle = (isCompleted || isExpired) ? 'margin-bottom:4px; color:#64748b; text-decoration:line-through; opacity:0.8;' : 'margin-bottom:4px;';
                    html += '<li style="' + taskLiStyle + '"><strong>' + escHtml(t.task_name) + '</strong>' + faeSuffix + ' - Progress: ' + t.progress + '% (' + escHtml(t.status) + ')</li>';
                });
                html += '</ul></div>';
            }

            if (dayAppts.length > 0) {
                html += '<div style="margin-bottom:14px;"><strong>Appointments & Confirmations:</strong><ul style="margin:6px 0 0 18px; padding:0; font-size:13px; color:var(--text-main);">';
                dayAppts.forEach(function(a) {
                    var slotText = a.time_slot ? ' (' + escHtml(a.time_slot) + ')' : (a.start_time && a.end_time ? ' (' + escHtml(a.start_time) + ' - ' + escHtml(a.end_time) + ')' : '');
                    var isPast = a.is_past || (dateStr < todayStr);
                    var effSt = (isPast && (a.status === 'pending' || a.status === 'expired')) ? 'Expired' : (a.status ? (a.status === 'completed' ? 'Completed' : (a.status === 'not_completed' ? 'Not Completed' : (a.status === 'rescheduled' ? 'Rescheduled' : a.status.charAt(0).toUpperCase() + a.status.slice(1)))) : 'Pending');
                    if (isPast && effSt !== 'Expired' && effSt !== 'Completed' && effSt !== 'Not Completed') {
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
