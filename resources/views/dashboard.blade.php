@php
    use App\Services\MonitoringAuth;
@endphp
@extends('layouts.app')

@section('title', 'Dashboard')
@section('breadcrumb', 'Dashboard')

@section('content')
<section class="content">

    <!-- PAGE INTRO -->
    <div class="page-intro">
        <div>
            <h1>Overview</h1>
            <p>Here's an overview of current contact members and task monitoring activities.</p>
        </div>

        <div class="date-box">
            <span>Today</span>
            <strong id="currentDate">{{ date('F d, Y') }}</strong>
        </div>
    </div>

    <!-- ================= STATISTICS ================= -->
    <div class="stats-grid">
        @if($isAdmin)
            <a href="{{ route('fae.index') }}" class="stat-card clickable-stat-card" title="View all contact members" style="text-decoration:none; color:inherit;">
                <div class="stat-top">
                    <div class="stat-icon blue">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
                    </div>
                    <span class="trend positive">Active</span>
                </div>
                <div class="stat-value">{{ $totalFAE }}</div>
                <div class="stat-label">Total Contacts</div>
                <div class="stat-description">Active contact members &rarr;</div>
            </a>

            <a href="{{ route('tasks.index') }}" class="stat-card clickable-stat-card" title="View all tasks" style="text-decoration:none; color:inherit;">
                <div class="stat-top">
                    <div class="stat-icon purple">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                    </div>
                    <span class="trend positive">Total</span>
                </div>
                <div class="stat-value">{{ $totalTasks }}</div>
                <div class="stat-label">Total Tasks</div>
                <div class="stat-description">Tasks currently being monitored &rarr;</div>
            </a>
        @endif

        <a href="{{ route('tasks.index', ['status' => 'Completed']) }}" class="stat-card clickable-stat-card" title="Filter completed tasks" style="text-decoration:none; color:inherit;">
            <div class="stat-top">
                <div class="stat-icon green">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                </div>
                <span class="trend positive">Done</span>
            </div>
            <div class="stat-value">{{ $completedTasks }}</div>
            <div class="stat-label">Completed</div>
            <div class="stat-description">Successfully completed tasks &rarr;</div>
        </a>

        <a href="{{ route('tasks.index', ['status' => 'In Progress']) }}" class="stat-card clickable-stat-card" title="Filter in-progress tasks" style="text-decoration:none; color:inherit;">
            <div class="stat-top">
                <div class="stat-icon orange">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </div>
                <span class="trend neutral">Ongoing</span>
            </div>
            <div class="stat-value">{{ $inProgressTasks }}</div>
            <div class="stat-label">In Progress</div>
            <div class="stat-description">Tasks currently in progress &rarr;</div>
        </a>

        @if(!$isAdmin)
            <a href="{{ route('tasks.index', ['status' => 'Pending']) }}" class="stat-card clickable-stat-card" title="Filter pending tasks" style="text-decoration:none; color:inherit;">
                <div class="stat-top">
                    <div class="stat-icon yellow" style="background:rgba(245,158,11,0.1); color:#f59e0b;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </div>
                    <span class="trend neutral">Waiting</span>
                </div>
                <div class="stat-value">{{ $pendingTasks }}</div>
                <div class="stat-label">Pending</div>
                <div class="stat-description">Tasks pending start &rarr;</div>
            </a>

            <a href="{{ route('tasks.index', ['status' => 'Overdue']) }}" class="stat-card clickable-stat-card" title="Filter overdue tasks" style="text-decoration:none; color:inherit;">
                <div class="stat-top">
                    <div class="stat-icon red" style="background:rgba(220,38,38,0.1); color:#dc2626;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </div>
                    <span class="trend negative">Attention</span>
                </div>
                <div class="stat-value">{{ $overdueTasks }}</div>
                <div class="stat-label">Overdue</div>
                <div class="stat-description">Tasks past deadline &rarr;</div>
            </a>
        @endif
    </div>

    <!-- ================= CHART & ACTIVITY SECTION ================= -->
    <div class="dashboard-grid">
        <!-- 1. LINE CHART -->
        <div class="panel chart-panel">
            <div class="panel-header" style="align-items: center; padding: 12px 18px 10px;">
                <div>
                    <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                        <h2 style="margin: 0; font-size: 14px; font-weight: 700; color: #0f172a;">Task Progress</h2>
                        <div style="display: flex; align-items: center; gap: 12px; font-size: 11px; font-weight: 600; color: #334155;">
                            <span style="display: inline-flex; align-items: center; gap: 5px;">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background: #2563eb; display: inline-block;"></span>
                                Completed
                            </span>
                            <span style="display: inline-flex; align-items: center; gap: 5px;">
                                <span style="width: 8px; height: 8px; border-radius: 50%; background: #10b981; display: inline-block;"></span>
                                Total Tasks
                            </span>
                        </div>
                    </div>
                    <p id="chartDateRangeSubtext" style="margin: 4px 0 0 0; font-size: 11px; font-weight: 600; color: #475569;">
                        {{ $progressChartData['week']['subtext'] ?? date('F Y') . ' (Weekly Progress)' }}
                    </p>
                </div>
                <div class="chart-pill-group" id="chartPillGroup">
                    <button type="button" class="chart-pill" data-period="day">Day</button>
                    <button type="button" class="chart-pill active" data-period="week">Week</button>
                    <button type="button" class="chart-pill" data-period="month">Month</button>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="progressChart"></canvas>
            </div>
        </div>

        <!-- 2. STATUS DONUT PANEL -->
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

        <!-- 3. UPCOMING TO-DO -->
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
                                <small>{{ date('M d, Y', strtotime($item['date'])) }}{{ !empty($item['time_slot']) ? ' • ' . $item['time_slot'] : '' }}</small>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    <!-- ================= RECENT MONITORING ================= -->
    <div class="panel monitoring-panel">
        <div class="panel-header" style="flex-wrap:wrap; gap:12px; align-items:center;">
            <div>
                <h2>Recent Monitoring</h2>
                <p>Latest activities and task progress</p>
            </div>
            <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
                <!-- Filter by Day Range (3, 7, 12 Days, All) -->
                <div class="chart-pill-group" id="monitoringDayFilterGroup" style="background:#f1f5f9; padding:2px; border-radius:6px; display:inline-flex;">
                    <button type="button" class="chart-pill active" data-days="all" style="font-size:11px; padding:4px 10px;">All</button>
                    <button type="button" class="chart-pill" data-days="3" style="font-size:11px; padding:4px 10px;">3 Days</button>
                    <button type="button" class="chart-pill" data-days="7" style="font-size:11px; padding:4px 10px;">7 Days</button>
                    <button type="button" class="chart-pill" data-days="12" style="font-size:11px; padding:4px 10px;">12 Days</button>
                </div>
                <a href="{{ route('tasks.index') }}" class="outline-button btn-sm" style="text-decoration:none; display:inline-flex; align-items:center; gap:4px;">
                    View All Tasks →
                </a>
            </div>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Contact</th>
                        <th>Region</th>
                        <th>Course</th>
                        <th>Task</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody id="monitoringTableBody">
                    @forelse($tasks as $row)
                        @php
                            $rawStatus = $row->status;
                            $normalizedStatus = strtolower(str_replace(' ', '', $rawStatus));
                            $badgeClass = ($normalizedStatus === 'inprogress') ? 'progress-badge' : $normalizedStatus . '-badge';
                            $progressPct = (int)($row->progress ?? 0);
                            $barClass = $progressPct <= 25 ? 'progress-red' : ($progressPct <= 50 ? 'progress-orange' : ($progressPct <= 75 ? 'progress-gold' : 'progress-green'));
                            $avatarColors = ['avatar-blue', 'avatar-purple', 'avatar-orange', 'avatar-green'];
                            $avatarColor = $avatarColors[$row->id % count($avatarColors)];
                            $nameParts = explode(' ', trim($row->fae->name ?? 'Unassigned'));
                            $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
                            $updateCount = (int)$row->updates_count;
                            $rowCreated = $row->created_at ?? $row->updated_at;
                            $daysAgo = $rowCreated ? (int)now()->startOfDay()->diffInDays($rowCreated->startOfDay()) : 999;
                        @endphp
                        <tr class="monitoring-table-row" data-days-ago="{{ $daysAgo }}" data-task-id="{{ $row->id }}">
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
                        <tr id="emptyMonitoringRow">
                            <td colspan="8" style="text-align: center; padding: 24px; color: #6b7280;">
                                No task records found in the database.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Monitoring Interactive Pagination Footer -->
        <div id="monitoringPaginationContainer" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; padding:12px 16px; border-top:1px solid #f1f5f9;">
            <div style="font-size:12px; color:#64748b; font-weight:600;">
                Showing <span id="monitoringPageRangeSpan">1–6</span> of <span id="monitoringTotalSpan">{{ count($tasks) }}</span> records
            </div>
            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;" id="monitoringPaginationControls">
                <button type="button" id="prevMonitoringBtn" class="outline-button btn-sm" style="display:inline-flex; align-items:center; gap:4px; min-height:30px;" disabled>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    Prev
                </button>
                <div id="monitoringNumbersWrap" style="display:inline-flex; align-items:center; gap:4px; flex-wrap:wrap;"></div>
                <button type="button" id="nextMonitoringBtn" class="outline-button btn-sm" style="display:inline-flex; align-items:center; gap:4px; min-height:30px;">
                    Next
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
            </div>
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
            <div style="display:flex; align-items:center; gap:8px;">
                <a href="#" target="_blank" id="reportModalPdfLink" class="outline-button btn-sm" title="Generate and print official PDF Task Report" style="display:inline-flex; align-items:center; gap:4px; text-decoration:none;">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                    PDF Report
                </a>
                <button type="button" class="custom-modal-close" data-close="taskReportsModal">&times;</button>
            </div>
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

                <div id="reportModalLinksWrap" style="margin-top:8px; padding-top:8px; border-top:1px solid #eee; display:none;">
                    <span style="font-size:10px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.3px; display:block;">Reference Links</span>
                    <div id="reportModalLinksList" style="margin-top:4px; display:flex; flex-direction:column; gap:4px;"></div>
                </div>

                <div id="reportModalAttachmentsWrap" style="margin-top:8px; padding-top:8px; border-top:1px solid #eee; display:none;">
                    <span style="font-size:10px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:0.3px; display:block;">Supervisor Reference Photos &amp; Attachments</span>
                    <div id="reportModalAttachmentsList" style="margin-top:6px; display:flex; flex-wrap:wrap; gap:8px;"></div>
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

            <!-- COMPLETED LOCKED NOTICE -->
            <div id="reportModalLockedNotice" style="display:none; background:rgba(22,163,74,0.08); border:1px solid #86efac; border-radius:8px; padding:14px; text-align:center; color:#15803d; font-weight:600; font-size:12px; margin-top:14px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="display:inline-block; vertical-align:middle; margin-right:6px;"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                This task is 100% completed. Progress updates and report submissions are closed.
            </div>

            <!-- COMPOSER: SUBMIT NEW WORK REPORT / PROGRESS NOTE (FAE ONLY) -->
            @if(!$isAdmin)
            <div class="report-composer-box" id="reportComposerBox">
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
                                <option value="Completed">Completed (Auto sets 100%)</option>
                                <option value="Pending">Pending</option>
                                <option value="Overdue">Overdue</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Progress Percentage</label>
                            <div class="range-slider-wrapper">
                                <input type="range" name="progress" id="reportFormProgressRange" min="0" max="100" value="0">
                                <span class="range-val-badge" id="reportFormProgressVal">0%</span>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Work Accomplished / Detailed Remarks <span class="req">*</span></label>
                        <textarea name="message" id="reportFormMessage" class="form-control" rows="3" placeholder="Describe work done, testing results, client feedback, or current blockers..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Photo Attachments (Multiple Supported)</label>
                        <input type="file" name="attachments[]" id="reportFormFiles" class="form-control" multiple accept="image/*,.pdf,.doc,.docx,.zip">
                        <small style="font-size:10.5px; color:var(--text-secondary); margin-top:2px; display:block;">Select one or multiple photos to attach. Live previews appear below.</small>
                        
                        <!-- Live Image Previews Container -->
                        <div id="reportFilesPreview" style="display:flex; flex-wrap:wrap; gap:8px; margin-top:8px;"></div>
                    </div>
                </form>
            </div>
            @endif
        </div>

        <div class="custom-modal-footer">
            <button type="button" class="secondary-button btn-sm" data-close="taskReportsModal">Close</button>
            @if(!$isAdmin)
            <button type="submit" form="taskReportForm" class="primary-button btn-sm" id="reportSubmitBtn" style="display:inline-flex; align-items:center; gap:6px;">
                <span>Post Work Report</span>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </button>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>

    // Task Progress Bar Graph
    const progressCanvas = document.getElementById("progressChart");
    const chartAllData = @json($progressChartData ?? null);

    if (progressCanvas && chartAllData) {
        const initialData = chartAllData.week || chartAllData.this_month;
        const maxTotal = Math.max.apply(null, initialData.total.concat([5]));

        const progressChart = new Chart(progressCanvas, {
            type: "bar",
            data: {
                labels: initialData.labels,
                datasets: [
                    {
                        label: "Completed Tasks",
                        data: initialData.completed,
                        backgroundColor: "#2563eb",
                        borderRadius: 6,
                        borderSkipped: false,
                        barPercentage: 0.65,
                        categoryPercentage: 0.65
                    },
                    {
                        label: "Total Tasks",
                        data: initialData.total,
                        backgroundColor: "#10b981",
                        borderRadius: 6,
                        borderSkipped: false,
                        barPercentage: 0.65,
                        categoryPercentage: 0.65
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: true,
                        backgroundColor: '#ffffff',
                        titleColor: '#64748b',
                        bodyColor: '#0f172a',
                        borderColor: '#e2e8f0',
                        borderWidth: 1,
                        padding: 10,
                        cornerRadius: 8,
                        boxWidth: 8,
                        boxHeight: 8,
                        boxPadding: 4,
                        usePointStyle: true,
                        titleFont: { size: 10, weight: '700' },
                        bodyFont: { size: 11, weight: '600' },
                        callbacks: {
                            title: function(items) {
                                if (!items.length) return '';
                                return items[0].label;
                            },
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const val = context.parsed.y;
                                return ' ' + label + ': ' + val;
                            },
                            afterBody: function(contexts) {
                                if (contexts.length >= 2) {
                                    const completed = contexts[0].parsed.y;
                                    const total = contexts[1].parsed.y;
                                    if (total > 0) {
                                        const rate = Math.round((completed / total) * 100);
                                        return '\n Completion Rate: ' + rate + '%';
                                    }
                                }
                                return '';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        suggestedMax: Math.max(maxTotal + 1, 5),
                        grid: { color: "#f1f5f9", drawBorder: false },
                        ticks: {
                            precision: 0,
                            font: { size: 10 },
                            color: "#94a3b8",
                            padding: 8
                        }
                    },
                    x: {
                        grid: { display: false, drawBorder: false },
                        ticks: {
                            font: { size: 10, weight: '500' },
                            color: "#94a3b8",
                            padding: 8
                        }
                    }
                }
            }
        });

        const pillButtons = document.querySelectorAll('#chartPillGroup .chart-pill');
        const subtextEl = document.getElementById('chartDateRangeSubtext');

        pillButtons.forEach(function(pill) {
            pill.addEventListener('click', function() {
                pillButtons.forEach(function(p) { p.classList.remove('active'); });
                this.classList.add('active');

                const periodKey = this.getAttribute('data-period');
                const periodData = chartAllData[periodKey];
                if (periodData) {
                    progressChart.data.labels = periodData.labels;
                    progressChart.data.datasets[0].data = periodData.completed;
                    progressChart.data.datasets[1].data = periodData.total;
                    const newMax = Math.max.apply(null, periodData.total.concat([5]));
                    progressChart.options.scales.y.suggestedMax = Math.max(newMax + 1, 5);
                    progressChart.update();

                    if (subtextEl && periodData.subtext) {
                        subtextEl.textContent = periodData.subtext;
                    }
                }
            });
        });
    }

    // Recent Monitoring: Day Range Filtering & Pagination
    const monitoringRows = Array.from(document.querySelectorAll('.monitoring-table-row'));
    const monitoringDayPills = document.querySelectorAll('#monitoringDayFilterGroup .chart-pill');
    const monitoringPerPage = 6;
    let currentMonitoringPage = 1;
    let activeDayFilter = 'all';

    function getFilteredMonitoringRows() {
        return monitoringRows.filter(row => {
            if (activeDayFilter === 'all') return true;
            const daysAgo = parseInt(row.getAttribute('data-days-ago') || '999');
            const maxDays = parseInt(activeDayFilter);
            return daysAgo <= maxDays;
        });
    }

    function renderMonitoringPagination() {
        const filtered = getFilteredMonitoringRows();
        const total = filtered.length;
        const totalPages = Math.max(1, Math.ceil(total / monitoringPerPage));
        if (currentMonitoringPage > totalPages) currentMonitoringPage = totalPages;
        if (currentMonitoringPage < 1) currentMonitoringPage = 1;

        monitoringRows.forEach(r => r.style.display = 'none');

        const startIdx = (currentMonitoringPage - 1) * monitoringPerPage;
        const endIdx = Math.min(startIdx + monitoringPerPage, total);
        filtered.slice(startIdx, endIdx).forEach(r => r.style.display = '');

        // Update empty message row if total === 0
        let noRowsEl = document.getElementById('noMonitoringFilterRows');
        const emptyStaticRow = document.getElementById('emptyMonitoringRow');
        
        if (total === 0) {
            if (!emptyStaticRow) {
                if (!noRowsEl) {
                    const tbody = document.getElementById('monitoringTableBody');
                    noRowsEl = document.createElement('tr');
                    noRowsEl.id = 'noMonitoringFilterRows';
                    noRowsEl.innerHTML = '<td colspan="8" style="text-align:center; padding:24px; color:#64748b;">No activities found for the selected day range.</td>';
                    if (tbody) tbody.appendChild(noRowsEl);
                } else {
                    noRowsEl.style.display = '';
                }
            }
        } else if (noRowsEl) {
            noRowsEl.style.display = 'none';
        }

        const rangeSpan = document.getElementById('monitoringPageRangeSpan');
        const totalSpan = document.getElementById('monitoringTotalSpan');
        if (rangeSpan) rangeSpan.textContent = total === 0 ? '0' : (startIdx + 1) + '–' + endIdx;
        if (totalSpan) totalSpan.textContent = total;

        const prevBtn = document.getElementById('prevMonitoringBtn');
        const nextBtn = document.getElementById('nextMonitoringBtn');
        if (prevBtn) prevBtn.disabled = (currentMonitoringPage <= 1);
        if (nextBtn) nextBtn.disabled = (currentMonitoringPage >= totalPages || total === 0);

        const numbersWrap = document.getElementById('monitoringNumbersWrap');
        if (numbersWrap) {
            numbersWrap.innerHTML = '';
            for (let p = 1; p <= totalPages; p++) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = (p === currentMonitoringPage) ? 'primary-button btn-sm' : 'outline-button btn-sm';
                btn.style.padding = '3px 8px';
                btn.style.fontSize = '11.5px';
                btn.style.minWidth = '28px';
                btn.style.minHeight = '28px';
                btn.textContent = p;
                btn.addEventListener('click', () => {
                    currentMonitoringPage = p;
                    renderMonitoringPagination();
                });
                numbersWrap.appendChild(btn);
            }
        }
    }

    monitoringDayPills.forEach(pill => {
        pill.addEventListener('click', function() {
            monitoringDayPills.forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            activeDayFilter = this.getAttribute('data-days');
            currentMonitoringPage = 1;
            renderMonitoringPagination();
        });
    });

    const prevMonBtn = document.getElementById('prevMonitoringBtn');
    const nextMonBtn = document.getElementById('nextMonitoringBtn');
    if (prevMonBtn) {
        prevMonBtn.addEventListener('click', () => {
            if (currentMonitoringPage > 1) {
                currentMonitoringPage--;
                renderMonitoringPagination();
            }
        });
    }
    if (nextMonBtn) {
        nextMonBtn.addEventListener('click', () => {
            const total = getFilteredMonitoringRows().length;
            const totalPages = Math.ceil(total / monitoringPerPage);
            if (currentMonitoringPage < totalPages) {
                currentMonitoringPage++;
                renderMonitoringPagination();
            }
        });
    }

    // Initial render of monitoring pagination
    renderMonitoringPagination();

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
        if (text === null || text === undefined) return '';
        const str = typeof text === 'string' ? text : String(text);
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return str.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }

    // TASK REPORTS PROGRESS AUTO-SYNC & MULTIPLE ATTACHMENT LIVE PREVIEWS
    const reportFormStatus = document.getElementById("reportFormStatus");
    const reportFormProgressRange = document.getElementById("reportFormProgressRange");
    const reportFormProgressVal = document.getElementById("reportFormProgressVal");

    if (reportFormProgressRange && reportFormProgressVal) {
        reportFormProgressRange.addEventListener("input", function() {
            reportFormProgressVal.textContent = this.value + "%";
            if (reportFormStatus) {
                if (parseInt(this.value) >= 100) {
                    reportFormStatus.value = "Completed";
                } else if (parseInt(this.value) > 0 && reportFormStatus.value === "Pending") {
                    reportFormStatus.value = "In Progress";
                }
            }
        });
    }

    if (reportFormStatus) {
        reportFormStatus.addEventListener("change", function() {
            if (this.value === "Completed" && reportFormProgressRange && reportFormProgressVal) {
                reportFormProgressRange.value = 100;
                reportFormProgressVal.textContent = "100%";
            }
        });
    }

    // Multiple Files Live Preview
    const reportFilesInput = document.getElementById("reportFormFiles");
    const reportFilesPreview = document.getElementById("reportFilesPreview");

    if (reportFilesInput && reportFilesPreview) {
        reportFilesInput.addEventListener("change", function() {
            reportFilesPreview.innerHTML = "";
            const files = Array.from(this.files);

            if (files.length === 0) return;

            files.forEach(file => {
                const itemDiv = document.createElement("div");
                itemDiv.style.cssText = "position:relative; border:1px solid #cbd5e1; border-radius:6px; padding:4px; background:#ffffff; max-width:120px; font-size:10px; text-align:center;";

                if (file.type.startsWith("image/")) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        itemDiv.innerHTML = '<img src="' + e.target.result + '" style="width:100%; height:60px; object-fit:cover; border-radius:4px; display:block; margin-bottom:3px;">' +
                                            '<div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-weight:600; color:#475569;">' + escapeHtml(file.name) + '</div>';
                    };
                    reader.readAsDataURL(file);
                } else {
                    itemDiv.innerHTML = '<div style="height:60px; display:flex; align-items:center; justify-content:center; background:#f1f5f9; border-radius:4px; font-size:20px; margin-bottom:3px;">📄</div>' +
                                        '<div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-weight:600; color:#475569;">' + escapeHtml(file.name) + '</div>';
                }
                reportFilesPreview.appendChild(itemDiv);
            });
        });
    }

    function openTaskTimeline(taskId) {
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

                // PDF report link
                const pdfLink = document.getElementById("reportModalPdfLink");
                if (pdfLink) {
                    pdfLink.href = "{{ url('tasks') }}/" + encodeURIComponent(t.id) + "/report";
                }

                const pct = parseInt(t.progress) || 0;
                const isCompleted = pct >= 100 || (t.status && t.status.toLowerCase() === 'completed');

                const fillEl = document.getElementById("reportModalProgressFill");
                if (fillEl) {
                    fillEl.style.width = pct + '%';
                    fillEl.className = pct <= 25 ? 'progress-red' : (pct <= 50 ? 'progress-orange' : (pct <= 75 ? 'progress-gold' : 'progress-green'));
                }
                document.getElementById("reportModalProgressPct").textContent = pct + '%';

                if (t.description) {
                    document.getElementById("reportModalDescription").textContent = t.description;
                    document.getElementById("reportModalDescWrap").style.display = "block";
                } else {
                    document.getElementById("reportModalDescWrap").style.display = "none";
                }

                // Render Reference Links
                const linksWrap = document.getElementById("reportModalLinksWrap");
                const linksListEl = document.getElementById("reportModalLinksList");
                const linksArray = t.links_list || [];
                if (linksWrap && linksListEl) {
                    if (linksArray.length > 0) {
                        linksListEl.innerHTML = linksArray.map(l => {
                            return '<a href="' + escapeHtml(l) + '" target="_blank" style="display:inline-flex; align-items:center; gap:5px; color:var(--primary); font-size:11.5px; text-decoration:none; background:#f8fafc; border:1px solid #e2e8f0; padding:4px 8px; border-radius:4px; font-weight:500;" onmouseover="this.style.background=\'#eff6ff\'" onmouseout="this.style.background=\'#f8fafc\'">' +
                                        '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></svg>' +
                                        '<span>' + escapeHtml(l) + '</span>' +
                                    '</a>';
                        }).join('');
                        linksWrap.style.display = "block";
                    } else {
                        linksWrap.style.display = "none";
                    }
                }

                // Render Supervisor Attachments
                const attWrap = document.getElementById("reportModalAttachmentsWrap");
                const attListEl = document.getElementById("reportModalAttachmentsList");
                const attArray = t.attachments || [];
                if (attWrap && attListEl) {
                    if (attArray.length > 0) {
                        attListEl.innerHTML = attArray.map(att => {
                            if (att.is_image) {
                                return '<a href="' + escapeHtml(att.url) + '" target="_blank" style="display:inline-block; border:1px solid #cbd5e1; border-radius:6px; overflow:hidden; background:#fff; text-decoration:none; box-shadow:0 1px 3px rgba(0,0,0,0.08);">' +
                                            '<img src="' + escapeHtml(att.url) + '" alt="' + escapeHtml(att.filename) + '" style="height:80px; width:110px; object-fit:cover; display:block;">' +
                                            '<div style="font-size:9.5px; color:#475569; padding:2px 4px; text-overflow:ellipsis; overflow:hidden; white-space:nowrap; max-width:110px; text-align:center;">' + escapeHtml(att.filename) + '</div>' +
                                        '</a>';
                            } else {
                                return '<a href="' + escapeHtml(att.url) + '" target="_blank" class="outline-button btn-sm" style="display:inline-flex; align-items:center; gap:4px; text-decoration:none; padding:4px 8px; font-size:11px;">' +
                                            '📄 ' + escapeHtml(att.filename) +
                                        '</a>';
                            }
                        }).join('');
                        attWrap.style.display = "block";
                    } else {
                        attWrap.style.display = "none";
                    }
                }

                // Status & priority badges
                const badgesWrap = document.getElementById("reportModalTaskBadges");
                let normStatus = (t.status || 'Pending').toLowerCase().replace(/\s+/g, '');
                let badgeClass = normStatus === 'inprogress' ? 'progress-badge' : normStatus + '-badge';
                badgesWrap.innerHTML = '<span class="badge ' + badgeClass + '">' + escapeHtml(t.status) + '</span>' +
                                      '<span class="badge priority-' + (t.priority || 'Medium').toLowerCase() + '">' + escapeHtml(t.priority || 'Medium') + '</span>';

                // Lock update form if 100% completed
                const lockedEl = document.getElementById("reportModalLockedNotice");
                const formBox = document.getElementById("reportComposerBox");
                if (lockedEl) lockedEl.style.display = isCompleted ? 'block' : 'none';
                if (formBox) formBox.style.display = isCompleted ? 'none' : 'block';

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

                        // Attachments handling (supports multiple attachments JSON or single string)
                        if (u.attachment) {
                            let attList = [];
                            try {
                                if (u.attachment.startsWith('[') && u.attachment.endsWith(']')) {
                                    attList = JSON.parse(u.attachment);
                                } else {
                                    attList = [u.attachment];
                                }
                            } catch(e) {
                                attList = [u.attachment];
                            }

                            if (attList.length > 0) {
                                html += '<div style="margin-top:10px; display:flex; flex-wrap:wrap; gap:8px;">';
                                attList.forEach(attPath => {
                                    const ext = attPath.split('.').pop().toLowerCase();
                                    const isImg = ['jpg','jpeg','png','gif','webp','bmp'].includes(ext);
                                    const assetUrl = '{{ url('files') }}/' + attPath.split('/').map(s => encodeURIComponent(s)).join('/');

                                    if (isImg) {
                                        html += '<a href="' + assetUrl + '" target="_blank" style="display:inline-block; border:1px solid #cbd5e1; border-radius:6px; overflow:hidden; background:#fff; text-decoration:none;">' +
                                                    '<img src="' + assetUrl + '" alt="Report photo" style="height:90px; width:120px; object-fit:cover; display:block;">' +
                                                '</a>';
                                    } else {
                                        html += '<a href="' + assetUrl + '" target="_blank" class="outline-button btn-sm" style="display:inline-flex; align-items:center; gap:4px; text-decoration:none; padding:4px 8px;">' +
                                                    '📎 ' + escapeHtml(attPath.split('/').pop()) +
                                                '</a>';
                                    }
                                });
                                html += '</div>';
                            }
                        }

                        html += '</div>';
                    });
                    timelineContainer.innerHTML = html;
                }
            })
            .catch(err => {
                console.error("Timeline error:", err);
                timelineContainer.innerHTML = '<div class="alert-banner error">Failed to load timeline updates.</div>';
            });
    }

    // AJAX Timeline Trigger
    document.querySelectorAll(".btn-view-reports-trigger").forEach(function(btn) {
        btn.addEventListener("click", function() {
            const taskId = this.getAttribute("data-id");
            openTaskTimeline(taskId);
        });
    });
</script>
@endpush
