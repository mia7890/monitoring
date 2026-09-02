@extends('layouts.app')

@section('title', 'Calendar & Schedule')
@section('breadcrumb', 'Calendar')

@push('styles')
<link rel="stylesheet" href="{{ asset('calendar.css') }}?v={{ time() }}">
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

        // Flatten month appointments for the table
        $monthAppts = collect();
        foreach ($apptsByDay as $appts) {
            foreach ($appts as $a) {
                $monthAppts->push($a);
            }
        }

        // Flatten month admin events for the sidebar
        $monthAdminEvents = collect();
        foreach ($adminEventsByDay as $events) {
            foreach ($events as $e) {
                $monthAdminEvents->push($e);
            }
        }
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
                                        @endphp
                                        <div class="cal-event {{ $catClass }} ev-admin"
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
                                            $apptClass = ($aData->status === 'rejected') ? 'ev-overdue' : (($aData->status === 'accepted') ? 'ev-completed' : 'ev-appt');
                                        @endphp
                                        <div class="cal-event {{ $apptClass }}"
                                             title="Appointment: {{ $aData->reason }} ({{ $aData->status }})">
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

        <!-- RIGHT: Sidebar -->
        <div class="cal-side">
            <!-- Calendar Guide / Legend (FAE only) -->
            <div class="panel" style="padding:16px 20px;">
                @if(!$isAdmin)
                    <h3 style="font-size:13px; font-weight:700; margin-bottom:8px;">How to Book</h3>
                    <p style="font-size:12px; color:var(--text-light); line-height:1.5; margin-bottom:12px;">
                        Click directly on any available date on the calendar grid to request an appointment.
                    </p>
                    <div style="display:flex; flex-direction:column; gap:6px; font-size:11px; margin-bottom:12px;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:var(--primary);"></span>
                            <span>Tasks Due</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#8b5cf6;"></span>
                            <span>Appointments</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px;">
                            <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#ef4444;"></span>
                            <span>Booked / Unavailable</span>
                        </div>
                    </div>
                @endif

                <div class="google-tools">
                    <strong>Google Workspace</strong>
                    <span>Sync appointments and task deadlines directly to Google Calendar.</span>
                    <div style="display:flex; gap:6px; margin-top:8px;">
                        <a href="https://calendar.google.com" target="_blank" rel="noopener" class="google-calendar-link">Google Calendar</a>
                    </div>
                </div>
            </div>

            <!-- Admin Events List -->
            @if($monthAdminEvents->count() > 0)
                <div class="panel" style="padding:16px 20px;">
                    <h3 style="font-size:13px; font-weight:700; margin-bottom:10px;">Events This Month</h3>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        @foreach ($monthAdminEvents as $ev)
                            @php $evData = is_object($ev) ? $ev : (object)$ev; @endphp
                            <div style="display:flex; justify-content:space-between; align-items:center; background:#f8fafc; padding:8px 10px; border-radius:7px; border:1px solid var(--border);">
                                <div>
                                    <strong style="font-size:12px; display:block;">{{ $evData->title }}</strong>
                                    <small style="color:var(--text-light); font-size:10px;">{{ \Carbon\Carbon::parse($evData->event_date)->format('M d, Y') }} &bull; {{ ucfirst($evData->category) }}</small>
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
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Admin Notes</th>
                            <th>Calendar</th>
                            @if($isAdmin)
                                <th style="text-align:right;">Actions</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($monthAppts as $ap)
                            @php
                                $st = $ap->status;
                                $bCls = $st === 'accepted' ? 'complete-badge' : ($st === 'rejected' ? 'overdue-badge' : 'pending-badge');
                                $googleDate = \Carbon\Carbon::parse($ap->appointment_date)->format('Ymd');
                                $googleEnd  = \Carbon\Carbon::parse($ap->appointment_date)->addDay()->format('Ymd');
                                $googleUrl  = 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' . urlencode('Meeting with Admin') . '&dates=' . $googleDate . '/' . $googleEnd . '&details=' . urlencode($ap->reason);
                            @endphp
                            <tr>
                                <td><strong>{{ $ap->user_name }}</strong></td>
                                <td>{{ \Carbon\Carbon::parse($ap->appointment_date)->format('M d, Y') }}</td>
                                <td>{{ $ap->reason }}</td>
                                <td><span class="badge {{ $bCls }}">{{ ucfirst($st) }}</span></td>
                                <td><small style="color:var(--text-light);">{{ $ap->admin_comment ?? '—' }}</small></td>
                                <td><a class="google-calendar-link" href="{{ $googleUrl }}" target="_blank" rel="noopener">Google Calendar</a></td>
                                @if($isAdmin)
                                    <td style="text-align:right;">
                                        @if($st === 'pending')
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
                                    </td>
                                @endif
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
                        <label class="form-label">Date <span class="req">*</span></label>
                        <input type="date" name="event_date" id="addEventModalDate" class="form-control" required value="{{ $today }}">
                    </div>
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
                    <input type="date" name="appointment_date" id="bookModalDate" class="form-control" required readonly style="background:#f8fafc;">
                </div>
                <div class="form-group">
                    <label class="form-label">Discussion Topic <span class="req">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" required placeholder="Describe what you want to consult..."></textarea>
                </div>
            </div>
            <div class="custom-modal-footer">
                <button type="button" class="secondary-button btn-sm" id="cancelBookBtn">Cancel</button>
                <button type="submit" class="primary-button btn-sm">Submit Request</button>
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
    [calOverlay, addEventOverlay, bookOverlay, rejectOverlay].forEach(function(overlay) {
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

if (openAddEventBtn) {
    openAddEventBtn.addEventListener("click", function() {
        openCalModal(addEventOverlay);
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
                    html += '<li style="margin-bottom:4px;"><strong>' + escHtml(a.user_name || 'User') + '</strong>: ' + escHtml(a.reason) + ' [<span style="text-transform:capitalize;">' + escHtml(a.status) + '</span>]</li>';
                });
                html += '</ul></div>';
            }

            if (dayAdminEvents.length === 0 && dayTasks.length === 0 && dayAppts.length === 0) {
                html = '<p style="color:var(--text-light); margin:0 0 12px; font-size:13px;">No tasks, events, or appointments scheduled for this date.</p>';
            }

            if (isAdmin) {
                html += '<div style="margin-top:16px; padding-top:12px; border-top:1px solid var(--border); text-align:right;">' +
                        '<button type="button" class="primary-button btn-sm" onclick="closeAllCalModals(); if(addEventModalDate) addEventModalDate.value=\'' + dateStr + '\'; openCalModal(addEventOverlay);">' +
                        '+ Add Event on ' + dateStr + '</button></div>';
            }

            calModalBody.innerHTML = html;
            openCalModal(calOverlay);
        }
    });
});
</script>
@endpush
