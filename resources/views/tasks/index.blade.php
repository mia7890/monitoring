@extends('layouts.app')

@section('title', 'Tasks & FAE Management')
@section('breadcrumb', 'Tasks & FAE')

@section('content')
<section class="content">

    <!-- PAGE INTRO -->
    <div class="page-intro">
        <div>
            <span class="welcome-label" style="color:var(--primary); font-size:10px; font-weight:700; letter-spacing:1px;">WORKSPACE</span>
            <h1>Tasks &amp; FAE Directory</h1>
            <p>Manage Field Application Engineers, assign regional tasks, and track real-time execution progress.</p>
        </div>

        <div style="display:flex; align-items:center; gap:10px;">
            <div class="date-box">
                <span>Today</span>
                <strong id="currentDate">{{ date('F d, Y') }}</strong>
            </div>
        </div>
    </div>

    <!-- ================= TASKS LIST PANEL ================= -->
    <div class="panel">
        <div class="panel-header">
            <div>
                <h2>Task Directory &amp; Assignments</h2>
                <p>Track, filter and update all assigned field activities</p>
            </div>

            @if($isAdmin)
                <button type="button" class="primary-button btn-sm" id="openAddTaskModalBtn">
                    <span>+</span> Add New Task
                </button>
            @endif
        </div>

        <!-- FILTER TOOLBAR -->
        <div class="tasks-toolbar">
            <div class="search-box-wrap">
                <span class="search-box-icon">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </span>
                <input type="text" id="taskSearchInput" placeholder="Search tasks, FAE, region, or course...">
            </div>

            <div class="filters-row">
                <select class="filter-select" id="statusFilter">
                    <option value="">All Statuses</option>
                    <option value="Pending">Pending</option>
                    <option value="In Progress">In Progress</option>
                    <option value="Completed">Completed</option>
                    <option value="Overdue">Overdue</option>
                </select>

                <select class="filter-select" id="faeFilter">
                    <option value="">All FAE Members</option>
                    <option value="unassigned">Unassigned</option>
                    @foreach($allFaeForDropdown as $fae)
                        <option value="{{ $fae->id }}" {{ (string)$preselectedFaeId === (string)$fae->id ? 'selected' : '' }}>
                            {{ $fae->name }} ({{ $fae->fae_code }})
                        </option>
                    @endforeach
                </select>

                <select class="filter-select" id="priorityFilter">
                    <option value="">All Priorities</option>
                    <option value="Urgent">Urgent</option>
                    <option value="High">High</option>
                    <option value="Medium">Medium</option>
                    <option value="Low">Low</option>
                </select>
            </div>
        </div>

        <!-- TASKS TABLE -->
        <div class="table-wrapper">
            <table id="tasksTable">
                <thead>
                    <tr>
                        <th>Assigned FAE</th>
                        <th>Task Name</th>
                        <th>Region</th>
                        <th>Course</th>
                        <th>Priority</th>
                        <th>Deadline</th>
                        <th>Status</th>
                        <th>Progress</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @php $todayStr = date('Y-m-d'); @endphp
                    @forelse($tasksList as $row)
                        @php
                            $rawStatus = $row->status ?? 'Pending';
                            $normalizedStatus = strtolower(str_replace(' ', '', $rawStatus));
                            $badgeClass = ($normalizedStatus === 'inprogress') ? 'progress-badge' : $normalizedStatus . '-badge';
                            $barClass = ($normalizedStatus === 'completed') ? 'complete' : (($normalizedStatus === 'inprogress') ? '' : $normalizedStatus);
                            $priority = $row->priority ?? 'Medium';
                            $priorityClass = 'priority-' . strtolower($priority);
                            $isOverdue = (!empty($row->deadline) && $row->deadline->format('Y-m-d') < $todayStr && $rawStatus !== 'Completed');
                            
                            $faeInitials = 'UA';
                            if (!empty($row->fae->name)) {
                                $np = explode(' ', trim($row->fae->name));
                                $faeInitials = strtoupper(substr($np[0], 0, 1) . (isset($np[1]) ? substr($np[1], 0, 1) : ''));
                            }
                            $avatarColors = ['#2563eb', '#7c3aed', '#f59e0b', '#16a34a', '#0891b2', '#db2777', '#4f46e5'];
                            $avatarCol = $avatarColors[($row->fae_id ?? 0) % count($avatarColors)];
                            $updateCount = (int)$row->updates_count;
                        @endphp
                        <tr class="task-table-row" 
                            data-task-name="{{ strtolower($row->task_name) }}"
                            data-fae-name="{{ strtolower($row->fae->name ?? 'unassigned') }}"
                            data-region="{{ strtolower($row->region ?? '') }}"
                            data-course="{{ strtolower($row->course ?? '') }}"
                            data-status="{{ $rawStatus }}"
                            data-fae-id="{{ $row->fae_id ?? 'unassigned' }}"
                            data-priority="{{ $priority }}">
                            <td>
                                <div class="user-cell">
                                    @if($row->fae && $row->fae->profile_image)
                                        <img src="{{ asset($row->fae->profile_image) }}" alt="{{ $row->fae->name }}" class="small-avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                    @else
                                        <div class="small-avatar" style="background: {{ $avatarCol }}; color:white;">
                                            {{ $faeInitials }}
                                        </div>
                                    @endif
                                    <div>
                                        <strong>{{ $row->fae->name ?? 'Unassigned' }}</strong>
                                        <span>{{ $row->fae->fae_code ?? 'None' }}</span>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <strong style="color:var(--text); font-size:12px; display:block;">
                                    <a href="javascript:void(0)" 
                                       class="btn-view-reports-trigger" 
                                       data-id="{{ $row->id }}"
                                       style="color:inherit; text-decoration:none;">
                                        {{ $row->task_name }}
                                    </a>
                                </strong>
                                @if(!empty($row->description))
                                    <small class="task-description">
                                        {{ $row->description }}
                                    </small>
                                @endif
                            </td>

                            <td>{{ $row->region ?? '—' }}</td>
                            <td>{{ $row->course ?? '—' }}</td>

                            <td>
                                <span class="priority-badge {{ $priorityClass }}">
                                    {{ $priority }}
                                </span>
                            </td>

                            <td>
                                <span class="{{ $isOverdue ? 'deadline-overdue' : '' }}">
                                    {{ $row->deadline ? $row->deadline->format('M d, Y') : '—' }}
                                </span>
                                @if($isOverdue)
                                    <small style="display:block; color:var(--red); font-size:9px; font-weight:700;">Overdue</small>
                                @endif
                            </td>

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
                                @php
                                    $gTitle = urlencode('Task Deadline: ' . $row->task_name);
                                    $gDate = $row->deadline ? $row->deadline->format('Ymd') : date('Ymd');
                                    $gEnd = $row->deadline ? $row->deadline->copy()->addDay()->format('Ymd') : date('Ymd');
                                    $gDetails = urlencode("Task: {$row->task_name}\nRegion: {$row->region}\nCourse: {$row->course}\nDescription: " . ($row->description ?? 'N/A'));
                                    $gUrl = "https://calendar.google.com/calendar/render?action=TEMPLATE&text={$gTitle}&dates={$gDate}/{$gEnd}&details={$gDetails}";
                                @endphp

                                <a href="{{ $gUrl }}" target="_blank" rel="noopener" class="google-calendar-link" title="Sync deadline to Google Calendar">
                                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                    Sync Cal
                                </a>

                                <button type="button" 
                                        class="outline-button btn-view-reports-trigger btn-sm" 
                                        data-id="{{ $row->id }}"
                                        title="View task details, progress logs, and submit reports">
                                    Reports {{ $updateCount > 0 ? "($updateCount)" : '' }}
                                </button>

                                @if($isAdmin)
                                    <button type="button" 
                                            class="btn-icon-square btn-edit-task-trigger" 
                                            data-id="{{ $row->id }}"
                                            data-fae-id="{{ $row->fae_id ?? '' }}"
                                            data-task-name="{{ $row->task_name }}"
                                            data-region="{{ $row->region ?? '' }}"
                                            data-course="{{ $row->course ?? '' }}"
                                            data-deadline="{{ $row->deadline ? $row->deadline->format('Y-m-d') : '' }}"
                                            data-status="{{ $row->status ?? 'Pending' }}"
                                            data-progress="{{ (int)$row->progress }}"
                                            data-priority="{{ $row->priority ?? 'Medium' }}"
                                            data-description="{{ $row->description ?? '' }}"
                                            title="Edit Task"
                                            style="display:inline-flex; align-items:center; justify-content:center;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>

                                    <button type="button" 
                                            class="btn-icon-square danger btn-delete-task-trigger" 
                                            data-id="{{ $row->id }}"
                                            data-name="{{ $row->task_name }}"
                                            title="Delete Task"
                                            style="display:inline-flex; align-items:center; justify-content:center;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr id="noTasksRow">
                            <td colspan="9" style="text-align:center; padding:36px 20px; color:var(--text-light);">
                                <p>No tasks found in the database. Use the button above to create one.</p>
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
        <span>Task &amp; FAE System Active</span>
    </footer>
</section>

<!-- =========================================================
     MODALS SECTION
     ========================================================= -->

<!-- 1. ADD TASK MODAL -->
<div class="custom-modal-overlay" id="addTaskModal">
    <div class="custom-modal">
        <div class="custom-modal-header">
            <h3>Create New Task</h3>
            <button type="button" class="custom-modal-close" data-close="addTaskModal">&times;</button>
        </div>

        <form method="POST" action="{{ route('tasks.store') }}" id="addTaskForm">
            @csrf

            <div class="custom-modal-body">
                <div class="form-group">
                    <label class="form-label">Assign To FAE</label>
                    <select name="fae_id" id="addTaskFaeSelect" class="form-select">
                        <option value="">-- Unassigned / General Task --</option>
                        @foreach($allFaeForDropdown as $fae)
                            <option value="{{ $fae->id }}" {{ (string)$preselectedFaeId === (string)$fae->id ? 'selected' : '' }}>
                                {{ $fae->name }} ({{ $fae->fae_code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Task Name / Deliverable <span class="req">*</span></label>
                    <input type="text" name="task_name" class="form-control" placeholder="e.g. PLC Maintenance & System Health Check" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Region</label>
                        <input type="text" name="region" class="form-control" placeholder="e.g. NCR, Region 3, Region 4A" list="regionOptions">
                        <datalist id="regionOptions">
                            <option value="NCR"><option value="Region 1"><option value="Region 2"><option value="Region 3">
                            <option value="Region 4A"><option value="Region 4B"><option value="Region 5"><option value="Region 6">
                            <option value="Region 7"><option value="Region 8"><option value="Region 9"><option value="Region 10">
                            <option value="Region 11"><option value="Region 12"><option value="CAR"><option value="BARMM">
                        </datalist>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Course / Program / Module</label>
                        <input type="text" name="course" class="form-control" placeholder="e.g. Mechatronics, Industrial Auto">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Deadline</label>
                        <input type="date" name="deadline" class="form-control" value="{{ date('Y-m-d', strtotime('+7 days')) }}">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-select">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                            <option value="Urgent">Urgent</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description / Instructions</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Optional background details or scope..."></textarea>
                </div>
            </div>

            <div class="custom-modal-footer">
                <button type="button" class="secondary-button btn-sm" data-close="addTaskModal">Cancel</button>
                <button type="submit" class="primary-button btn-sm">Create Task</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. EDIT TASK MODAL -->
<div class="custom-modal-overlay" id="editTaskModal">
    <div class="custom-modal">
        <div class="custom-modal-header">
            <h3>Edit Task Details</h3>
            <button type="button" class="custom-modal-close" data-close="editTaskModal">&times;</button>
        </div>

        <form method="POST" action="" id="editTaskForm">
            @csrf
            @method('PUT')

            <div class="custom-modal-body">
                <div class="form-group">
                    <label class="form-label">Assigned FAE</label>
                    <select name="fae_id" id="editTaskFaeSelect" class="form-select">
                        <option value="">-- Unassigned --</option>
                        @foreach($allFaeForDropdown as $fae)
                            <option value="{{ $fae->id }}">{{ $fae->name }} ({{ $fae->fae_code }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Task Name <span class="req">*</span></label>
                    <input type="text" name="task_name" id="editTaskName" class="form-control" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Region</label>
                        <input type="text" name="region" id="editTaskRegion" class="form-control" list="regionOptions">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Course / Program</label>
                        <input type="text" name="course" id="editTaskCourse" class="form-control">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Deadline</label>
                        <input type="date" name="deadline" id="editTaskDeadline" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select name="priority" id="editTaskPriority" class="form-select">
                            <option value="Low">Low</option>
                            <option value="Medium">Medium</option>
                            <option value="High">High</option>
                            <option value="Urgent">Urgent</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description / Instructions</label>
                    <textarea name="description" id="editTaskDescription" class="form-control" rows="2"></textarea>
                </div>
            </div>

            <div class="custom-modal-footer">
                <button type="button" class="secondary-button btn-sm" data-close="editTaskModal">Cancel</button>
                <button type="submit" class="primary-button btn-sm">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- 3. DELETE TASK CONFIRMATION MODAL -->
<div class="custom-modal-overlay" id="deleteTaskModal">
    <div class="custom-modal" style="max-width: 420px;">
        <div class="custom-modal-header">
            <h3 style="color:var(--red);">Confirm Deletion</h3>
            <button type="button" class="custom-modal-close" data-close="deleteTaskModal">&times;</button>
        </div>

        <form method="POST" action="" id="deleteTaskForm">
            @csrf
            @method('DELETE')

            <div class="custom-modal-body">
                <p id="deleteTaskModalText" style="font-size:13px; color:var(--text); line-height:1.5;">
                    Are you sure you want to delete this task?
                </p>
            </div>

            <div class="custom-modal-footer">
                <button type="button" class="secondary-button btn-sm" data-close="deleteTaskModal">Cancel</button>
                <button type="submit" class="btn-danger-outline btn-sm">Yes, Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- 4. TASK DETAILS & PROGRESS REPORTS TIMELINE MODAL -->
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
                    <input type="hidden" name="redirect_to" value="tasks.index">

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

    // Add Task Modal Trigger
    const openAddTaskBtn = document.getElementById("openAddTaskModalBtn");
    if (openAddTaskBtn) {
        openAddTaskBtn.addEventListener("click", function() {
            openModal("addTaskModal");
        });
    }

    // Auto-open Add Task modal if assign_fae was passed in URL
    @if(!empty($preselectedFaeId))
        openModal("addTaskModal");
    @endif

    // Edit Task Trigger
    document.querySelectorAll(".btn-edit-task-trigger").forEach(function(btn) {
        btn.addEventListener("click", function() {
            const taskId = this.getAttribute("data-id");
            const faeId = this.getAttribute("data-fae-id");
            const taskName = this.getAttribute("data-task-name");
            const region = this.getAttribute("data-region");
            const course = this.getAttribute("data-course");
            const deadline = this.getAttribute("data-deadline");
            const priority = this.getAttribute("data-priority");
            const description = this.getAttribute("data-description");

            document.getElementById("editTaskForm").action = "{{ url('tasks') }}/" + taskId;
            document.getElementById("editTaskFaeSelect").value = faeId || "";
            document.getElementById("editTaskName").value = taskName || "";
            document.getElementById("editTaskRegion").value = region || "";
            document.getElementById("editTaskCourse").value = course || "";
            document.getElementById("editTaskDeadline").value = deadline || "";
            document.getElementById("editTaskPriority").value = priority || "Medium";
            document.getElementById("editTaskDescription").value = description || "";

            openModal("editTaskModal");
        });
    });

    // Delete Task Trigger
    document.querySelectorAll(".btn-delete-task-trigger").forEach(function(btn) {
        btn.addEventListener("click", function() {
            const taskId = this.getAttribute("data-id");
            const taskName = this.getAttribute("data-name");

            document.getElementById("deleteTaskForm").action = "{{ url('tasks') }}/" + taskId;
            document.getElementById("deleteTaskModalText").innerHTML = "Are you sure you want to permanently delete task <strong>" + escapeHtml(taskName) + "</strong>?";
            openModal("deleteTaskModal");
        });
    });

    // Search and Filter logic
    const searchInput = document.getElementById("taskSearchInput");
    const statusFilter = document.getElementById("statusFilter");
    const faeFilter = document.getElementById("faeFilter");
    const priorityFilter = document.getElementById("priorityFilter");

    function applyTaskFilters() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : "";
        const statusVal = statusFilter ? statusFilter.value : "";
        const faeVal = faeFilter ? faeFilter.value : "";
        const priorityVal = priorityFilter ? priorityFilter.value : "";

        const rows = document.querySelectorAll(".task-table-row");
        let visibleCount = 0;

        rows.forEach(function(row) {
            const taskName = row.getAttribute("data-task-name") || "";
            const faeName = row.getAttribute("data-fae-name") || "";
            const region = row.getAttribute("data-region") || "";
            const course = row.getAttribute("data-course") || "";
            const status = row.getAttribute("data-status") || "";
            const faeId = row.getAttribute("data-fae-id") || "";
            const priority = row.getAttribute("data-priority") || "";

            const matchesQuery = !query || 
                                 taskName.includes(query) || 
                                 faeName.includes(query) || 
                                 region.includes(query) || 
                                 course.includes(query);

            const matchesStatus = !statusVal || status === statusVal;
            const matchesFae = !faeVal || faeId === faeVal;
            const matchesPriority = !priorityVal || priority === priorityVal;

            if (matchesQuery && matchesStatus && matchesFae && matchesPriority) {
                row.style.display = "";
                visibleCount++;
            } else {
                row.style.display = "none";
            }
        });
    }

    if (searchInput) searchInput.addEventListener("input", applyTaskFilters);
    if (statusFilter) statusFilter.addEventListener("change", applyTaskFilters);
    if (faeFilter) faeFilter.addEventListener("change", applyTaskFilters);
    if (priorityFilter) priorityFilter.addEventListener("change", applyTaskFilters);

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
                                const assetUrl = '{{ asset("") }}' + u.attachment;
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
