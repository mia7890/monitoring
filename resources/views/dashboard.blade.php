@extends('layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')
<section class="content">

    <!-- PAGE INTRO -->
    <div class="page-intro">
        <div>
            <h1>Overview</h1>
            <p>Here's an overview of the current FAE and task monitoring activities.</p>
        </div>

        <div class="date-box">
            <span>Today</span>
            <strong id="currentDate">{{ date('F d, Y') }}</strong>
        </div>
    </div>

    <!-- ================= STATISTICS ================= -->
    <div class="stats-grid">
        @if($isAdmin)
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon blue">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    </div>
                    <span class="trend positive">Active</span>
                </div>
                <div class="stat-value">{{ $totalFAE }}</div>
                <div class="stat-label">Total FAE</div>
                <div class="stat-description">Active field application engineers</div>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon purple">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    </div>
                    <span class="trend positive">Total</span>
                </div>
                <div class="stat-value">{{ $totalTasks }}</div>
                <div class="stat-label">Total Tasks</div>
                <div class="stat-description">Tasks currently being monitored</div>
            </div>
        @endif

        <div class="stat-card">
            <div class="stat-top">
                <div class="stat-icon green">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <span class="trend positive">Done</span>
            </div>
            <div class="stat-value">{{ $completedTasks }}</div>
            <div class="stat-label">Completed</div>
            <div class="stat-description">Successfully completed tasks</div>
        </div>

        <div class="stat-card">
            <div class="stat-top">
                <div class="stat-icon orange">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <span class="trend neutral">Ongoing</span>
            </div>
            <div class="stat-value">{{ $inProgressTasks }}</div>
            <div class="stat-label">In Progress</div>
            <div class="stat-description">Tasks currently in progress</div>
        </div>

        @if(!$isAdmin)
            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon yellow" style="background:rgba(245,158,11,0.1); color:#f59e0b;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <span class="trend neutral">Waiting</span>
                </div>
                <div class="stat-value">{{ $pendingTasks }}</div>
                <div class="stat-label">Pending</div>
                <div class="stat-description">Tasks pending start</div>
            </div>

            <div class="stat-card">
                <div class="stat-top">
                    <div class="stat-icon red" style="background:rgba(220,38,38,0.1); color:#dc2626;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                    <span class="trend negative">Attention</span>
                </div>
                <div class="stat-value">{{ $overdueTasks }}</div>
                <div class="stat-label">Overdue</div>
                <div class="stat-description">Tasks past deadline</div>
            </div>
        @endif
    </div>

    <!-- ================= CHART & ACTIVITY SECTION ================= -->
    <div class="dashboard-grid">
        <!-- LINE CHART -->
        <div class="panel chart-panel">
            <div class="panel-header">
                <div>
                    <h2>Task Progress</h2>
                    <p>Overall task completion overview</p>
                </div>
                <select class="period-select">
                    <option>This Month</option>
                    <option>Last Month</option>
                    <option>This Year</option>
                </select>
            </div>
            <div class="chart-container">
                <canvas id="progressChart"></canvas>
            </div>
        </div>

        <!-- RIGHT COLUMN: UPCOMING & STATUS DONUT -->
        <div style="display: flex; flex-direction: column; gap: 20px;">
            <!-- UPCOMING TO-DO -->
            <div class="panel activity-panel">
                <div class="panel-header">
                    <div>
                        <h2>Upcoming To-Do</h2>
                        <p>Due within next 7 days</p>
                    </div>
                </div>
                <div class="activity-list">
                    @if(empty($upcomingItems))
                        <div style="text-align:center; padding: 20px; font-size: 10px; color: #6b7280;">No upcoming to-dos for this week.</div>
                    @else
                        @foreach($upcomingItems as $item)
                            @php
                                $iconClass = match($item['type']) {
                                    'task'        => 'blue',
                                    'appointment' => 'purple',
                                    'event'       => ($item['category'] === 'busy' ? 'orange' : ($item['category'] === 'meeting' ? 'blue' : 'green')),
                                    default       => 'blue'
                                };
                                $typeSubtitle = match($item['type']) {
                                    'task'        => 'Task Due',
                                    'appointment' => ($item['category'] === 'accepted' ? 'Confirmed Appointment' : 'Appointment Request'),
                                    'event'       => match($item['category']) {
                                        'meeting'  => 'Admin Meeting',
                                        'busy'     => 'Busy / Unavailable',
                                        'reminder' => 'Reminder',
                                        default    => 'Admin Event'
                                    },
                                    default       => 'Upcoming Event'
                                };
                            @endphp
                            <div class="activity">
                                <div class="activity-icon {{ $iconClass }}">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                </div>
                                <div class="activity-content">
                                    <strong>{{ $item['title'] }}</strong>
                                    <span>{{ $typeSubtitle }}</span>
                                    <small>{{ date('M d, Y', strtotime($item['date'])) }}</small>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>

            <!-- STATUS DONUT PANEL -->
            <div class="panel status-panel">
                <div class="panel-header">
                    <div>
                        <h2>Task Status</h2>
                        <p>Current task distribution</p>
                    </div>
                </div>
                <div class="donut-container">
                    <canvas id="statusChart"></canvas>
                    <div class="donut-center">
                        <strong>{{ $totalTasks }}</strong>
                        <span>Total Tasks</span>
                    </div>
                </div>
                <div class="status-list">
                    <div class="status-item">
                        <div><span class="legend green"></span>Completed</div>
                        <strong>{{ $completedTasks }}</strong>
                    </div>
                    <div class="status-item">
                        <div><span class="legend blue"></span>In Progress</div>
                        <strong>{{ $inProgressTasks }}</strong>
                    </div>
                    <div class="status-item">
                        <div><span class="legend orange"></span>Pending</div>
                        <strong>{{ $pendingTasks }}</strong>
                    </div>
                    <div class="status-item">
                        <div><span class="legend red"></span>Overdue</div>
                        <strong>{{ $overdueTasks }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ================= RECENT MONITORING ================= -->
    <div class="panel monitoring-panel">
        <div class="panel-header">
            <div>
                <h2>Recent Monitoring</h2>
                <p>Latest activities and task progress</p>
            </div>
            <a href="{{ route('tasks.index') }}" class="outline-button" style="text-decoration:none;">
                View All Tasks →
            </a>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>FAE</th>
                        <th>Region</th>
                        <th>Course</th>
                        <th>Task</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasks as $row)
                        @php
                            $rawStatus = $row->status;
                            $normalizedStatus = strtolower(str_replace(' ', '', $rawStatus));
                            $badgeClass = ($normalizedStatus === 'inprogress') ? 'progress-badge' : $normalizedStatus . '-badge';
                            $barClass = ($normalizedStatus === 'completed') ? 'complete' : (($normalizedStatus === 'inprogress') ? '' : $normalizedStatus);
                            $avatarColors = ['avatar-blue', 'avatar-purple', 'avatar-orange', 'avatar-green'];
                            $avatarColor = $avatarColors[$row->id % count($avatarColors)];
                            $nameParts = explode(' ', trim($row->fae->name ?? 'Unassigned'));
                            $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
                            $updateCount = (int)$row->updates_count;
                        @endphp
                        <tr>
                            <td>
                                <div class="user-cell">
                                    <div class="small-avatar {{ $avatarColor }}">
                                        {{ $initials }}
                                    </div>
                                    <div>
                                        <strong>{{ $row->fae->name ?? 'Unassigned' }}</strong>
                                        <span>{{ $row->fae->fae_code ?? 'N/A' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $row->region ?? 'N/A' }}</td>
                            <td>{{ $row->course ?? 'N/A' }}</td>
                            <td>
                                <a href="javascript:void(0)" class="btn-view-reports-trigger" data-id="{{ $row->id }}" style="color:inherit; font-weight:600; text-decoration:none;">
                                    {{ $row->task_name }}
                                </a>
                            </td>
                            <td>{{ $row->deadline ? $row->deadline->format('M d, Y') : 'N/A' }}</td>
                            <td>
                                <span class="badge {{ $badgeClass }}">
                                    {{ $rawStatus }}
                                </span>
                            </td>
                            <td>
                                <div class="progress-cell">
                                    <div class="progress-bar">
                                        <div class="{{ $barClass }}" style="width:{{ (int)$row->progress }}%"></div>
                                    </div>
                                    <span>{{ (int)$row->progress }}%</span>
                                </div>
                            </td>
                            <td style="text-align:right; white-space:nowrap;">
                                <button type="button" 
                                        class="outline-button btn-view-reports-trigger btn-sm" 
                                        data-id="{{ $row->id }}"
                                        title="View task reports & activity">
                                    Reports {{ $updateCount > 0 ? "($updateCount)" : '' }}
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 24px; color: #6b7280;">
                                No task records found in the database.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        <span>Monitoring System © 2026</span>
        <span>Dashboard Live</span>
    </footer>
</section>

<!-- =========================================================
     TASK DETAILS & PROGRESS REPORTS TIMELINE MODAL
     ========================================================= -->
<div class="custom-modal-overlay" id="taskReportsModal">
    <div class="custom-modal task-modal-lg">
        <div class="custom-modal-header">
            <div>
                <h3 id="reportModalTaskTitle" style="font-size:14px; font-weight:700;">Task Details &amp; Reports</h3>
                <div id="reportModalTaskBadges" style="display:flex; align-items:center; gap:6px; margin-top:4px;"></div>
            </div>
            <button type="button" class="custom-modal-close" data-close="taskReportsModal">&times;</button>
        </div>

        <div class="custom-modal-body">
            <!-- TASK OVERVIEW SUMMARY CARD -->
            <div class="task-summary-box">
                <div style="display:flex; justify-content:space-between; align-items:flex-start;">
                    <div>
                        <strong id="reportModalFaeName" style="font-size:12px; color:var(--text);">Unassigned</strong>
                        <span id="reportModalFaeCode" style="font-size:10px; color:var(--text-secondary); margin-left:4px;"></span>
                    </div>
                    <div id="reportModalProgressBar" style="width:120px;">
                        <div class="progress-cell">
                            <div class="progress-bar" style="width:100%;">
                                <div id="reportModalProgressFill" style="width:0%;"></div>
                            </div>
                            <span id="reportModalProgressPct" style="font-weight:700;">0%</span>
                        </div>
                    </div>
                </div>

                <div class="task-summary-grid">
                    <div>
                        <span>Region</span>
                        <strong id="reportModalRegion">—</strong>
                    </div>
                    <div>
                        <span>Course / Module</span>
                        <strong id="reportModalCourse">—</strong>
                    </div>
                    <div>
                        <span>Deadline</span>
                        <strong id="reportModalDeadline">—</strong>
                    </div>
                </div>

                <div id="reportModalDescWrap" style="margin-top:8px; padding-top:8px; border-top:1px solid #eee; display:none;">
                    <span style="font-size:10px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.3px; display:block;">Task Instructions</span>
                    <p id="reportModalDescription" class="task-desc-text"></p>
                </div>
            </div>

            <!-- CHRONOLOGICAL ACTIVITY LOG / REPORTS TIMELINE -->
            <div class="section-subheading">
                <span>Progress Reports &amp; Activity Log</span>
                <span id="reportModalUpdateCount" style="font-size:11px; font-weight:600; color:var(--text-secondary);">0 updates</span>
            </div>

            <div id="reportModalTimeline" class="timeline-list">
                <div style="text-align:center; padding:20px; color:var(--text-secondary); font-size:11px;">
                    Loading report timeline...
                </div>
            </div>

            <!-- COMPOSER: SUBMIT NEW WORK REPORT / PROGRESS NOTE (FAE ONLY) -->
            @if(!$isAdmin)
            <div class="report-composer-box">
                <div class="report-composer-title">
                    Submit Work Report / Progress Note
                </div>

                <form method="POST" action="{{ route('tasks.storeUpdate') }}" enctype="multipart/form-data" id="taskReportForm">
                    @csrf
                    <input type="hidden" name="task_id" id="reportFormTaskId" value="">
                    <input type="hidden" name="redirect_to" value="dashboard">

                    <div class="form-row" style="margin-bottom:10px;">
                        <div class="form-group">
                            <label class="form-label">Current Task Status</label>
                            <select name="status" id="reportFormStatus" class="form-select">
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                                <option value="Pending">Pending</option>
                                <option value="Overdue">Overdue</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Progress Percentage</label>
                            <div class="range-slider-wrapper">
                                <input type="range" name="progress" id="reportFormProgressRange" min="0" max="100" value="0" oninput="document.getElementById('reportFormProgressVal').textContent = this.value + '%'">
                                <span class="range-val-badge" id="reportFormProgressVal">0%</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Work Accomplished / Detailed Remarks <span class="req">*</span></label>
                        <textarea name="message" id="reportFormMessage" class="form-control" rows="3" placeholder="Describe work done, testing results, client feedback, or current blockers..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Photo / Attachment (Optional)</label>
                        <input type="file" name="attachment" id="reportFormFile" class="form-control" accept="image/*,.pdf,.doc,.docx,.zip">
                        <small style="font-size:10px; color:var(--text-secondary); margin-top:2px; display:block;">Attach photos of site work, inspection sheets, or report files (Max 10MB).</small>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:12px;">
                        <button type="submit" class="primary-button btn-sm">
                            Post Work Report
                        </button>
                    </div>
                </form>
            </div>
            @endif
        </div>

        <div class="custom-modal-footer">
            <button type="button" class="secondary-button btn-sm" data-close="taskReportsModal">Close</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Task Progress Line Chart
    const progressCanvas = document.getElementById("progressChart");
    if (progressCanvas) {
        new Chart(progressCanvas, {
            type: "line",
            data: {
                labels: ["Week 1", "Week 2", "Week 3", "Week 4", "Week 5", "Week 6"],
                datasets: [
                    {
                        label: "Completed Tasks",
                        data: [18, 27, 35, 42, 48, {{ $completedTasks }}],
                        borderColor: "#2563eb",
                        backgroundColor: "rgba(37, 99, 235, 0.08)",
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 3,
                        pointHoverRadius: 5
                    },
                    {
                        label: "Total Tasks",
                        data: [35, 48, 58, 67, 76, {{ $totalTasks }}],
                        borderColor: "#d1d5db",
                        borderWidth: 2,
                        borderDash: [5, 5],
                        fill: false,
                        tension: 0.4,
                        pointRadius: 2
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: "bottom",
                        labels: {
                            usePointStyle: true,
                            boxWidth: 6,
                            font: { size: 9 }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: "#f1f3f6" },
                        ticks: { font: { size: 9 }, color: "#9ca3af" }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 9 }, color: "#9ca3af" }
                    }
                }
            }
        });
    }

    // Task Status Doughnut Chart
    const statusCanvas = document.getElementById("statusChart");
    if (statusCanvas) {
        new Chart(statusCanvas, {
            type: "doughnut",
            data: {
                labels: ["Completed", "In Progress", "Pending", "Overdue"],
                datasets: [
                    {
                        data: [
                            {{ $completedTasks }},
                            {{ $inProgressTasks }},
                            {{ $pendingTasks }},
                            {{ $overdueTasks }}
                        ],
                        backgroundColor: ["#16a34a", "#2563eb", "#f59e0b", "#dc2626"],
                        borderWidth: 0,
                        hoverOffset: 5
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: "76%",
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: true }
                }
            }
        });
    }

    // Modal Helpers
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.classList.add("active");
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) modal.classList.remove("active");
    }

    document.querySelectorAll("[data-close]").forEach(function (btn) {
        btn.addEventListener("click", function () {
            closeModal(btn.getAttribute("data-close"));
        });
    });

    document.querySelectorAll(".custom-modal-overlay").forEach(function (overlay) {
        overlay.addEventListener("click", function (e) {
            if (e.target === overlay) overlay.classList.remove("active");
        });
    });

    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            document.querySelectorAll(".custom-modal-overlay.active").forEach(function (m) {
                m.classList.remove("active");
            });
        }
    });

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }

    // AJAX Timeline Loader
    document.querySelectorAll(".btn-view-reports-trigger").forEach(function(btn) {
        btn.addEventListener("click", function() {
            const taskId = this.getAttribute("data-id");
            if (!taskId) return;

            openModal("taskReportsModal");
            const timelineContainer = document.getElementById("reportModalTimeline");
            timelineContainer.innerHTML = '<div style="text-align:center; padding:20px; color:var(--text-secondary); font-size:11px;">Loading report timeline...</div>';

            fetch("{{ route('tasks.timeline') }}?task_id=" + encodeURIComponent(taskId))
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        timelineContainer.innerHTML = '<div class="alert-banner error">' + escapeHtml(data.error || 'Failed to load task updates.') + '</div>';
                        return;
                    }

                    const t = data.task;
                    document.getElementById("reportModalTaskTitle").textContent = t.task_name;
                    document.getElementById("reportModalFaeName").textContent = t.fae_name || 'Unassigned';
                    document.getElementById("reportModalFaeCode").textContent = t.fae_code ? '(' + t.fae_code + ')' : '';
                    document.getElementById("reportModalRegion").textContent = t.region || '—';
                    document.getElementById("reportModalCourse").textContent = t.course || '—';
                    document.getElementById("reportModalDeadline").textContent = t.deadline || '—';

                    const pct = parseInt(t.progress) || 0;
                    document.getElementById("reportModalProgressFill").style.width = pct + '%';
                    document.getElementById("reportModalProgressPct").textContent = pct + '%';

                    if (t.description) {
                        document.getElementById("reportModalDescription").textContent = t.description;
                        document.getElementById("reportModalDescWrap").style.display = "block";
                    } else {
                        document.getElementById("reportModalDescWrap").style.display = "none";
                    }

                    // Status & priority badges
                    const badgesWrap = document.getElementById("reportModalTaskBadges");
                    let normStatus = (t.status || 'Pending').toLowerCase().replace(/\s+/g, '');
                    let badgeClass = normStatus === 'inprogress' ? 'progress-badge' : normStatus + '-badge';
                    badgesWrap.innerHTML = '<span class="badge ' + badgeClass + '">' + escapeHtml(t.status) + '</span>' +
                                          '<span class="badge priority-' + (t.priority || 'Medium').toLowerCase() + '">' + escapeHtml(t.priority || 'Medium') + '</span>';

                    // Prepare form
                    const formTaskId = document.getElementById("reportFormTaskId");
                    if (formTaskId) formTaskId.value = t.id;
                    const formStatus = document.getElementById("reportFormStatus");
                    if (formStatus) formStatus.value = t.status;
                    const formProgress = document.getElementById("reportFormProgressRange");
                    if (formProgress) {
                        formProgress.value = pct;
                        document.getElementById("reportFormProgressVal").textContent = pct + '%';
                    }

                    // Render updates list
                    const updates = data.updates || [];
                    document.getElementById("reportModalUpdateCount").textContent = updates.length + (updates.length === 1 ? ' update' : ' updates');

                    if (updates.length === 0) {
                        timelineContainer.innerHTML = '<div style="text-align:center; padding:20px; color:var(--text-secondary); font-size:11px;">No work reports or progress notes logged yet.</div>';
                    } else {
                        let html = '';
                        updates.forEach(u => {
                            const isAdminAuthor = u.author_role === 'admin';
                            const authorBadge = isAdminAuthor ? 'Supervisor' : 'FAE';
                            const badgeColor = isAdminAuthor ? '#ef4444' : '#3b82f6';
                            const backgroundColor = isAdminAuthor ? 'rgba(239,68,68,0.05)' : 'rgba(59,130,246,0.05)';
                            const borderColor = isAdminAuthor ? '#fca5a5' : '#93c5fd';

                            html += '<div style="background:' + backgroundColor + '; border:1px solid ' + borderColor + '; border-radius:8px; padding:12px; margin-bottom:12px; transition:all 0.2s ease;">' +
                                        '<div style="display:flex; align-items:center; gap:8px; margin-bottom:10px;">' +
                                            '<strong style="font-size:13px; color:var(--text);">' + escapeHtml(u.author_name) + '</strong>' +
                                            '<span style="background:' + badgeColor + '; color:white; font-size:9px; font-weight:700; padding:3px 8px; border-radius:4px;">' + authorBadge + '</span>' +
                                        '</div>' +
                                        '<div style="font-size:11px; color:var(--text); line-height:1.5; margin-bottom:10px;">' +
                                            '<span style="color:var(--text-light); font-weight:600;">Message:</span><br>' +
                                            '<p style="margin:4px 0 0 0; color:var(--text);">' + escapeHtml(u.message).replace(/\n/g, '<br>') + '</p>' +
                                        '</div>';

                            html += '<div style="display:flex; align-items:center; gap:12px; font-size:9px; color:var(--text-light); margin-bottom:10px; padding-top:10px; border-top:1px solid rgba(0,0,0,0.05);">' +
                                        '<span><strong>Date:</strong> ' + formatDate(u.created_at) + '</span>' +
                                    '</div>';

                            if (u.progress_at_update !== null || u.status_at_update) {
                                html += '<div style="display:flex; gap:12px; font-size:10px; flex-wrap:wrap;">';
                                if (u.progress_at_update !== null) {
                                    html += '<span style="background:rgba(34,197,94,0.1); color:#16a34a; padding:4px 8px; border-radius:4px; font-weight:600;"><strong>Progress:</strong> ' + parseInt(u.progress_at_update) + '%</span>';
                                }
                                if (u.status_at_update) {
                                    const statusColor = u.status_at_update === 'Completed' ? '#16a34a' : (u.status_at_update === 'Overdue' ? '#dc2626' : '#f59e0b');
                                    const statusBg = u.status_at_update === 'Completed' ? 'rgba(22,163,74,0.1)' : (u.status_at_update === 'Overdue' ? 'rgba(220,38,38,0.1)' : 'rgba(245,158,11,0.1)');
                                    html += '<span style="background:' + statusBg + '; color:' + statusColor + '; padding:4px 8px; border-radius:4px; font-weight:600;"><strong>Status:</strong> ' + escapeHtml(u.status_at_update) + '</span>';
                                }
                                html += '</div>';
                            }

                            if (u.attachment) {
                                const ext = u.attachment.split('.').pop().toLowerCase();
                                const isImg = ['jpg','jpeg','png','gif','webp','bmp'].includes(ext);
                                const assetUrl = '{{ url('files') }}/' + u.attachment;
                                if (isImg) {
                                    html += '<div style="margin-top:10px;"><a href="' + assetUrl + '" target="_blank"><img src="' + assetUrl + '" alt="Attachment preview" style="max-height:150px; border-radius:6px; border:1px solid #ddd; display:block;"></a></div>';
                                } else {
                                    html += '<div style="margin-top:10px;"><a href="' + assetUrl + '" target="_blank" class="outline-button btn-sm" style="display:inline-flex; align-items:center; gap:4px; text-decoration:none;">Download ' + escapeHtml(u.attachment.split('/').pop()) + '</a></div>';
                                }
                            }

                            html += '</div>';
                        });
                        timelineContainer.innerHTML = html;
                    }
                })
                .catch(err => {
                    timelineContainer.innerHTML = '<div class="alert-banner error">Failed to connect to server.</div>';
                });
        });
    });
</script>
@endpush
