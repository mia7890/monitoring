@php
    use App\Services\MonitoringAuth;
@endphp
@extends('layouts.app')

@section('title', 'Tasks & Contacts Management')
@section('breadcrumb', 'Tasks & Contacts')


@section('content')
<section class="content">

    <!-- PAGE INTRO -->
    <div class="page-intro">
        <div>
            <span class="welcome-label" style="color:var(--primary); font-size:10px; font-weight:700; letter-spacing:1px;">WORKSPACE</span>
            <h1>Tasks &amp; Contacts Directory</h1>
            <p>Manage Contact members, assign regional tasks, and track real-time execution progress.</p>
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

            <div style="display:flex; align-items:center; gap:8px;">
                <a href="{{ route('tasks.exportReport') }}" target="_blank" class="outline-button btn-sm" title="Generate and print executive PDF summary of all tasks">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right:4px;"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                    PDF Summary
                </a>
                @if($isAdmin)
                    <button type="button" class="primary-button btn-sm" id="openAddTaskModalBtn">
                        <span>+</span> Add New Task
                    </button>
                @endif
            </div>
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
                    <option value="Pending" {{ request('status') === 'Pending' ? 'selected' : '' }}>Pending</option>
                    <option value="In Progress" {{ request('status') === 'In Progress' ? 'selected' : '' }}>In Progress</option>
                    <option value="Completed" {{ request('status') === 'Completed' ? 'selected' : '' }}>Completed</option>
                    <option value="Overdue" {{ request('status') === 'Overdue' ? 'selected' : '' }}>Overdue</option>
                </select>

                <select class="filter-select" id="faeFilter">
                    <option value="">All Contact Members</option>
                    <option value="unassigned">Unassigned</option>
                    @foreach($allFaeForDropdown as $fae)
                        <option value="{{ $fae->id }}" {{ (string)$preselectedFaeId === (string)$fae->id ? 'selected' : '' }}>
                            {{ $fae->name }}{{ !empty($fae->fae_code) ? ' (' . $fae->fae_code . ')' : '' }}
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
                        <th>Assigned Contact</th>
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
                            $progressPct = (int)($row->progress ?? 0);
                            $barClass = $progressPct <= 25 ? 'progress-red' : ($progressPct <= 50 ? 'progress-orange' : ($progressPct <= 75 ? 'progress-gold' : 'progress-green'));
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
                            data-is-overdue="{{ $isOverdue ? '1' : '0' }}"
                            data-fae-id="{{ $row->fae_id ?? 'unassigned' }}"
                            data-priority="{{ $priority }}">
                            <td>
                                <div class="user-cell">
                                    @if($row->fae && $row->fae->profile_image)
                                        <img src="{{ \App\Services\UploadService::url($row->fae->profile_image) }}" alt="{{ $row->fae->name }}" class="small-avatar" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
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
                                <div style="display:inline-flex; align-items:center; justify-content:flex-end; gap:4px;">
                                    <a href="{{ route('tasks.report', $row->id) }}" target="_blank" class="btn-icon-square" title="Generate & Print PDF Report" style="display:inline-flex; align-items:center; justify-content:center; text-decoration:none; color:var(--text);">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
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
                                                data-links="{{ $row->links ?? '' }}"
                                                data-attachment-count="{{ count($row->attachments_list) }}"
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
                                </div>
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

        <!-- RESPONSIVE TASKS PAGINATION CONTROLS -->
        <div id="tasksPaginationWrap" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; padding:14px 20px; border-top:1px solid #f1f5f9; background:#ffffff;">
            <div style="font-size:12px; color:#64748b; font-weight:600;">
                Showing <span id="tasksPageRangeSpan">1–10</span> of <span id="tasksTotalCountSpan">{{ $tasksList->count() }}</span> tasks
            </div>
            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;" id="tasksPaginationControls">
                <button type="button" id="prevTaskPageBtn" class="outline-button btn-sm" style="display:inline-flex; align-items:center; gap:4px;" disabled>
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    Previous
                </button>
                <div id="tasksPaginationNumbers" style="display:inline-flex; align-items:center; gap:4px; flex-wrap:wrap;"></div>
                <button type="button" id="nextTaskPageBtn" class="outline-button btn-sm" style="display:inline-flex; align-items:center; gap:4px;">
                    Next
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        <span>Monitoring System © 2026</span>
        <span>Tasks &amp; Contacts System Active</span>
    </footer>
</section>

<!-- =========================================================
     MODALS SECTION
     ========================================================= -->

<!-- 1. TASK CREATION / EDIT UNIFIED MODAL -->
<div class="custom-modal-overlay" id="addTaskModal">
    <div class="custom-modal" style="max-width: 580px; max-height: 90vh; display: flex; flex-direction: column;">
        <div class="custom-modal-header" style="flex-shrink: 0;">
            <div>
                <h3 id="taskModalHeaderTitle">Create New Task</h3>
                <p id="taskModalHeaderSubtitle" style="font-size:11px; color:var(--text-light); margin:2px 0 0 0;">Fill in the task details and assign an engineer.</p>
            </div>
            <button type="button" class="custom-modal-close" data-close="addTaskModal">&times;</button>
        </div>

        <form method="POST" action="{{ route('tasks.store') }}" id="addTaskForm" enctype="multipart/form-data" style="display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden; margin: 0;">
            @csrf
            <input type="hidden" name="_method" id="taskFormMethod" value="POST">

            <div class="custom-modal-body" style="overflow-y: auto; flex: 1; min-height: 0; padding: 18px;">
                <div class="form-group">
                    <label class="form-label">Assign To Contact(s) <span class="req">*</span></label>
                    <div style="position:relative;" id="faeMultiSelectWrap">
                        <div id="faeMultiSelectToggle" class="form-select" style="padding-left:36px; font-weight:600; font-size:13px; cursor:pointer; min-height:38px; display:flex; align-items:center; flex-wrap:wrap; gap:4px; user-select:none;">
                            <span id="faeMultiPlaceholder" style="color:#94a3b8; font-weight:400;">-- Select Contact Member(s) --</span>
                        </div>
                        <span style="position:absolute; left:12px; top:11px; color:var(--primary); font-size:14px; pointer-events:none;">👤</span>
                        <div id="faeMultiDropdown" style="display:none; position:absolute; top:100%; left:0; right:0; z-index:99; background:#fff; border:1px solid #cbd5e1; border-top:none; border-radius:0 0 8px 8px; max-height:220px; overflow-y:auto; box-shadow:0 8px 20px rgba(0,0,0,0.12);">
                            @foreach($allFaeForDropdown as $fae)
                                <label style="display:flex; align-items:center; gap:8px; padding:8px 12px; cursor:pointer; font-size:12.5px; font-weight:500; color:#0f172a; transition:background 0.1s;" class="fae-checkbox-option" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                                    <input type="checkbox" name="fae_id[]" value="{{ $fae->id }}" class="fae-multi-checkbox" {{ (string)$preselectedFaeId === (string)$fae->id ? 'checked' : '' }} style="accent-color:var(--primary); width:15px; height:15px; cursor:pointer;">
                                    <span>{{ $fae->name }}@if(!empty($fae->fae_code)) <span style="color:#64748b; font-weight:400;">({{ $fae->fae_code }})</span>@endif</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <small style="font-size:10.5px; color:var(--text-secondary); margin-top:3px; display:block;">Select one or more contact members. A task copy will be created for each.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Task Name / Deliverable <span class="req">*</span></label>
                    <input type="text" name="task_name" id="addTaskName" class="form-control" placeholder="e.g. PLC Maintenance & System Health Check" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Region</label>
                        <select name="region" id="addTaskRegion" class="form-select">
                            <option value="">-- Select Region --</option>
                            <option value="NCR">NCR (National Capital Region)</option>
                            <option value="Region 1">Region 1 (Ilocos Region)</option>
                            <option value="Region 2">Region 2 (Cagayan Valley)</option>
                            <option value="Region 3">Region 3 (Central Luzon)</option>
                            <option value="Region 4A">Region 4A (CALABARZON)</option>
                            <option value="Region 4B">Region 4B (MIMAROPA)</option>
                            <option value="Region 5">Region 5 (Bicol Region)</option>
                            <option value="Region 6">Region 6 (Western Visayas)</option>
                            <option value="Region 7">Region 7 (Central Visayas)</option>
                            <option value="Region 8">Region 8 (Eastern Visayas)</option>
                            <option value="Region 9">Region 9 (Zamboanga Peninsula)</option>
                            <option value="Region 10">Region 10 (Northern Mindanao)</option>
                            <option value="Region 11">Region 11 (Davao Region)</option>
                            <option value="Region 12">Region 12 (SOCCSKSARGEN)</option>
                            <option value="CAR">CAR (Cordillera Admin Region)</option>
                            <option value="BARMM">BARMM (Bangsamoro)</option>
                            <option value="Other">Other / International</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Course / Program / Module</label>
                        <input type="text" name="course" id="addTaskCourse" class="form-control" placeholder="e.g. Mechatronics, Industrial Auto">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Deadline <span class="req">*</span></label>
                        <input type="date" name="deadline" id="addTaskDeadline" class="form-control" value="{{ date('Y-m-d', strtotime('+7 days')) }}" min="{{ date('Y-m-d') }}" required>
                        <small style="font-size:10px; color:var(--text-secondary); margin-top:2px; display:block;">Must be today or a future date.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Priority</label>
                        <select name="priority" id="addTaskPriority" class="form-select">
                            <option value="Low">Low</option>
                            <option value="Medium" selected>Medium</option>
                            <option value="High">High</option>
                            <option value="Urgent">Urgent</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Description / Instructions</label>
                    <textarea name="description" id="addTaskDescription" class="form-control" rows="2" placeholder="Optional background details, deliverables scope or site contact..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Reference Links / Cloud URLs <span style="font-size:10.5px; color:var(--text-secondary); font-weight:400;">(Optional)</span></label>
                    <textarea name="links" id="addTaskLinks" class="form-control" rows="2" placeholder="e.g. https://drive.google.com/folder..., https://docs.google.com/... (Separate multiple links by new lines or commas)"></textarea>
                    <small style="font-size:10px; color:var(--text-secondary); margin-top:2px; display:block;">Add any documentation, external links, or drive folders for the assigned engineer.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Reference Pictures / Attachments <span style="font-size:10.5px; color:var(--text-secondary); font-weight:400;">(Optional)</span></label>
                    <input type="file" name="attachments[]" id="addTaskAttachments" class="form-control" multiple accept="image/*,.pdf,.doc,.docx,.zip">
                    <small style="font-size:10.5px; color:var(--text-secondary); margin-top:2px; display:block;">Attach photos, schematics, or reference files. Multiple files supported.</small>
                    <div id="addTaskFilesPreview" style="display:flex; flex-wrap:wrap; gap:8px; margin-top:8px;"></div>
                    <div id="addTaskExistingFilesWrap" style="display:none; margin-top:8px; padding:8px 12px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; font-size:11.5px;">
                        <span style="color:#64748b; font-weight:600;">Current Attachments:</span> <span id="addTaskExistingFilesCount" style="font-weight:700; color:#0f172a;">0</span> file(s) attached.
                        <label style="display:inline-flex; align-items:center; gap:5px; margin-left:14px; color:#dc2626; font-size:11px; cursor:pointer; font-weight:600;">
                            <input type="checkbox" name="remove_existing_attachments" value="1" id="removeExistingAttachmentsCb" style="accent-color:#dc2626;"> Remove existing attachments
                        </label>
                    </div>
                </div>
            </div>

            <div class="custom-modal-footer" style="flex-shrink: 0; padding: 12px 18px; border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: flex-end; gap: 8px; background: #fafafa;">
                <button type="button" class="secondary-button btn-sm" data-close="addTaskModal">Cancel</button>
                <button type="button" class="primary-button btn-sm" id="btnReviewTaskSummary">
                    Review &amp; Summary →
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 1B. TASK CREATION / EDIT SUMMARY CONFIRMATION MODAL -->
<div class="custom-modal-overlay" id="taskSummaryModal">
    <div class="custom-modal" style="max-width: 480px; max-height: 90vh; display: flex; flex-direction: column;">
        <div class="custom-modal-header" style="flex-shrink: 0;">
            <div>
                <h3 id="sumModalHeaderTitle">Confirm Task Details</h3>
                <p id="sumModalHeaderSubtitle" style="font-size:11px; color:var(--text-light); margin:2px 0 0 0;">Please review the summary below before proceeding.</p>
            </div>
            <button type="button" class="custom-modal-close" data-close="taskSummaryModal">&times;</button>
        </div>

        <div class="custom-modal-body" style="overflow-y: auto; flex: 1; min-height: 0; padding: 18px;">
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:16px; margin-bottom:12px;">
                <div style="font-size:11px; text-transform:uppercase; font-weight:700; color:var(--primary); margin-bottom:8px; letter-spacing:0.5px;">Task Overview</div>
                <h4 id="sumTaskName" style="margin:0 0 10px 0; font-size:15px; color:#0f172a;"></h4>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; font-size:12px; margin-bottom:10px;">
                    <div>
                        <span style="color:#64748b; font-size:11px; display:block;">Assigned Contact:</span>
                        <strong id="sumFaeName" style="color:#0f172a;"></strong>
                    </div>
                    <div>
                        <span style="color:#64748b; font-size:11px; display:block;">Priority:</span>
                        <strong id="sumPriority" style="color:#0f172a;"></strong>
                    </div>
                    <div>
                        <span style="color:#64748b; font-size:11px; display:block;">Region:</span>
                        <span id="sumRegion" style="color:#0f172a; font-weight:600;"></span>
                    </div>
                    <div>
                        <span style="color:#64748b; font-size:11px; display:block;">Course:</span>
                        <span id="sumCourse" style="color:#0f172a; font-weight:600;"></span>
                    </div>
                    <div style="grid-column:1 / -1;">
                        <span style="color:#64748b; font-size:11px; display:block;">Deadline:</span>
                        <strong id="sumDeadline" style="color:#0f172a;"></strong>
                    </div>
                </div>

                <div id="sumDescWrap" style="border-top:1px dashed #cbd5e1; padding-top:8px; margin-top:8px; display:none;">
                    <span style="color:#64748b; font-size:11px; display:block;">Instructions / Description:</span>
                    <p id="sumDescription" style="margin:4px 0 0 0; font-size:11.5px; color:#334155; line-height:1.4;"></p>
                </div>

                <div id="sumLinksWrap" style="border-top:1px dashed #cbd5e1; padding-top:8px; margin-top:8px; display:none;">
                    <span style="color:#64748b; font-size:11px; display:block;">Reference Links:</span>
                    <div id="sumLinks" style="margin:4px 0 0 0; font-size:11.5px; color:var(--primary); word-break:break-all; line-height:1.4;"></div>
                </div>

                <div id="sumAttachmentsWrap" style="border-top:1px dashed #cbd5e1; padding-top:8px; margin-top:8px; display:none;">
                    <span style="color:#64748b; font-size:11px; display:block;">Pictures &amp; Attachments:</span>
                    <span id="sumAttachmentsCount" style="font-weight:600; font-size:11.5px; color:#0f172a;"></span>
                </div>
            </div>
        </div>

        <div class="custom-modal-footer" style="flex-shrink: 0; padding: 12px 18px; border-top: 1px solid var(--border); display: flex; align-items: center; justify-content: flex-end; gap: 8px; background: #fafafa;">
            <button type="button" class="secondary-button btn-sm" id="btnBackToEditTask">← Back to Edit</button>
            <button type="button" class="primary-button btn-sm" id="btnConfirmSubmitTask">
                ✓ Confirm &amp; Submit Task
            </button>
        </div>
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
                    <input type="hidden" name="redirect_to" value="tasks.index">

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

    // Unified Task Modal Mode Controller
    let isTaskEditMode = false;

    function resetTaskModalToAddMode() {
        isTaskEditMode = false;
        const titleEl = document.getElementById("taskModalHeaderTitle");
        const subTitleEl = document.getElementById("taskModalHeaderSubtitle");
        const formEl = document.getElementById("addTaskForm");
        const methodEl = document.getElementById("taskFormMethod");

        if (titleEl) titleEl.textContent = "Create New Task";
        if (subTitleEl) subTitleEl.textContent = "Fill in the task details and assign an engineer.";
        if (formEl) formEl.action = "{{ route('tasks.store') }}";
        if (methodEl) methodEl.value = "POST";

        document.getElementById("addTaskName").value = "";
        document.getElementById("addTaskRegion").value = "";
        document.getElementById("addTaskCourse").value = "";
        document.getElementById("addTaskDeadline").value = "{{ date('Y-m-d', strtotime('+7 days')) }}";
        document.getElementById("addTaskPriority").value = "Medium";
        document.getElementById("addTaskDescription").value = "";
        
        const linksEl = document.getElementById("addTaskLinks");
        if (linksEl) linksEl.value = "";

        const filesInput = document.getElementById("addTaskAttachments");
        if (filesInput) filesInput.value = "";

        const filesPreview = document.getElementById("addTaskFilesPreview");
        if (filesPreview) filesPreview.innerHTML = "";

        const existingWrap = document.getElementById("addTaskExistingFilesWrap");
        if (existingWrap) existingWrap.style.display = "none";

        const removeCb = document.getElementById("removeExistingAttachmentsCb");
        if (removeCb) removeCb.checked = false;

        if (typeof faeCheckboxes !== 'undefined') {
            faeCheckboxes.forEach(cb => cb.checked = false);
            updateFaeMultiDisplay();
        }
    }

    // Add Task Modal Trigger
    const openAddTaskBtn = document.getElementById("openAddTaskModalBtn");
    if (openAddTaskBtn) {
        openAddTaskBtn.addEventListener("click", function() {
            resetTaskModalToAddMode();
            openModal("addTaskModal");
        });
    }

    // Auto-open Add Task modal if assign_fae was passed in URL
    @if(!empty($preselectedFaeId))
        resetTaskModalToAddMode();
        openModal("addTaskModal");
    @endif

    // Multi-Select FAE Dropdown Logic
    const faeMultiToggle = document.getElementById("faeMultiSelectToggle");
    const faeMultiDropdown = document.getElementById("faeMultiDropdown");
    const faeMultiWrap = document.getElementById("faeMultiSelectWrap");
    const faeCheckboxes = document.querySelectorAll(".fae-multi-checkbox");

    function updateFaeMultiDisplay() {
        if (!faeMultiToggle) return;
        const checked = Array.from(faeCheckboxes).filter(cb => cb.checked);
        if (checked.length === 0) {
            faeMultiToggle.innerHTML = '<span id="faeMultiPlaceholder" style="color:#94a3b8; font-weight:400;">-- Select Contact Member(s) --</span>';
        } else {
            const names = checked.map(cb => {
                const label = cb.closest('label');
                const nameSpan = label ? label.querySelector('span') : null;
                return nameSpan ? nameSpan.innerText.trim() : 'FAE #' + cb.value;
            });
            faeMultiToggle.innerHTML = names.map(n => 
                `<span style="background:var(--primary-subtle, #eff6ff); color:var(--primary, #2563eb); border:1px solid rgba(37,99,235,0.2); padding:2px 8px; border-radius:4px; font-size:11.5px; font-weight:600; display:inline-flex; align-items:center;">${n}</span>`
            ).join('');
        }
    }

    if (faeMultiToggle && faeMultiDropdown) {
        faeMultiToggle.addEventListener("click", function(e) {
            e.stopPropagation();
            const isOpen = faeMultiDropdown.style.display === "block";
            faeMultiDropdown.style.display = isOpen ? "none" : "block";
        });

        document.addEventListener("click", function(e) {
            if (faeMultiWrap && !faeMultiWrap.contains(e.target)) {
                faeMultiDropdown.style.display = "none";
            }
        });

        faeCheckboxes.forEach(cb => {
            cb.addEventListener("change", updateFaeMultiDisplay);
        });

        // Initialize display if preselected
        updateFaeMultiDisplay();
    }

    // TASK VALIDATION & SUMMARY FLOW (Unified for Add & Edit)
    const btnReviewSummary = document.getElementById("btnReviewTaskSummary");
    const addTaskForm = document.getElementById("addTaskForm");
    const todayYmd = "{{ date('Y-m-d') }}";

    if (btnReviewSummary && addTaskForm) {
        btnReviewSummary.addEventListener("click", function() {
            const checkedFaeBoxes = Array.from(document.querySelectorAll(".fae-multi-checkbox:checked"));
            const taskNameInput = document.getElementById("addTaskName");
            const deadlineInput = document.getElementById("addTaskDeadline");
            const regionSelect = document.getElementById("addTaskRegion");
            const courseInput = document.getElementById("addTaskCourse");
            const prioritySelect = document.getElementById("addTaskPriority");
            const descInput = document.getElementById("addTaskDescription");
            const linksInput = document.getElementById("addTaskLinks");
            const attachmentsInput = document.getElementById("addTaskAttachments");

            const taskName = taskNameInput ? taskNameInput.value.trim() : "";
            const deadline = deadlineInput ? deadlineInput.value : "";

            // Check Contact assignment required
            if (checkedFaeBoxes.length === 0) {
                alert("Please select and assign at least one Contact person to this task.");
                if (faeMultiToggle) {
                    if (faeMultiDropdown) faeMultiDropdown.style.display = "block";
                    faeMultiToggle.focus();
                }
                return;
            }

            // Check Task Name required
            if (!taskName) {
                alert("Please enter the task name or deliverable title.");
                if (taskNameInput) taskNameInput.focus();
                return;
            }

            // Check Overdue Deadline Validation (Only for creating or if deadline changed)
            if (deadline && deadline < todayYmd && !isTaskEditMode) {
                document.getElementById("deadlineAlertMessage").textContent = 
                    "The selected deadline (" + deadline + ") is in the past. Tasks cannot be created with an overdue date. Please select today or a future date.";
                openModal("deadlineAlertModal");
                return;
            }

            // Populate Summary Modal
            const selectedFaeNames = checkedFaeBoxes.map(cb => {
                const label = cb.closest('label');
                const nameSpan = label ? label.querySelector('span') : null;
                return nameSpan ? nameSpan.innerText.trim() : 'FAE #' + cb.value;
            }).join(', ');

            const sumHeaderTitle = document.getElementById("sumModalHeaderTitle");
            const sumHeaderSub = document.getElementById("sumModalHeaderSubtitle");
            const confirmBtn = document.getElementById("btnConfirmSubmitTask");

            if (sumHeaderTitle) sumHeaderTitle.textContent = isTaskEditMode ? "Confirm Task Update" : "Confirm Task Creation";
            if (sumHeaderSub) sumHeaderSub.textContent = isTaskEditMode ? "Please review the updated details below before saving changes." : "Please review the summary below before creating the task.";
            if (confirmBtn) confirmBtn.textContent = isTaskEditMode ? "✓ Confirm & Save Changes" : "✓ Confirm & Create Task";

            document.getElementById("sumTaskName").textContent = taskName;
            document.getElementById("sumFaeName").textContent = selectedFaeNames;
            document.getElementById("sumPriority").textContent = prioritySelect ? prioritySelect.value : 'Medium';
            document.getElementById("sumRegion").textContent = (regionSelect && regionSelect.value) ? regionSelect.value : '—';
            document.getElementById("sumCourse").textContent = (courseInput && courseInput.value) ? courseInput.value : '—';
            document.getElementById("sumDeadline").textContent = deadline || 'No deadline';

            const descVal = descInput ? descInput.value.trim() : "";
            const descWrap = document.getElementById("sumDescWrap");
            if (descVal) {
                document.getElementById("sumDescription").textContent = descVal;
                descWrap.style.display = "block";
            } else {
                descWrap.style.display = "none";
            }

            // Links Summary
            const linksVal = linksInput ? linksInput.value.trim() : "";
            const linksWrap = document.getElementById("sumLinksWrap");
            const sumLinksEl = document.getElementById("sumLinks");
            if (linksVal) {
                const linkItems = linksVal.split(/[\r\n,]+/).map(s => s.trim()).filter(Boolean);
                sumLinksEl.innerHTML = linkItems.map(l => `<a href="${escapeHtml(l)}" target="_blank" style="display:block; color:var(--primary); text-decoration:underline; margin-bottom:2px;">🔗 ${escapeHtml(l)}</a>`).join('');
                linksWrap.style.display = "block";
            } else {
                linksWrap.style.display = "none";
            }

            // Attachments Summary
            const filesCount = attachmentsInput && attachmentsInput.files ? attachmentsInput.files.length : 0;
            const existingCount = parseInt(document.getElementById("addTaskExistingFilesCount")?.textContent || "0");
            const removeCbChecked = document.getElementById("removeExistingAttachmentsCb")?.checked;
            const effectiveCount = (removeCbChecked ? 0 : existingCount) + filesCount;
            const attWrap = document.getElementById("sumAttachmentsWrap");
            const sumAttEl = document.getElementById("sumAttachmentsCount");

            if (effectiveCount > 0) {
                sumAttEl.textContent = `${effectiveCount} file(s) attached` + (filesCount > 0 ? ` (${filesCount} newly selected)` : '');
                attWrap.style.display = "block";
            } else {
                attWrap.style.display = "none";
            }

            closeModal("addTaskModal");
            openModal("taskSummaryModal");
        });
    }

    const btnBackToEdit = document.getElementById("btnBackToEditTask");
    if (btnBackToEdit) {
        btnBackToEdit.addEventListener("click", function() {
            closeModal("taskSummaryModal");
            openModal("addTaskModal");
        });
    }

    const btnConfirmSubmit = document.getElementById("btnConfirmSubmitTask");
    if (btnConfirmSubmit && addTaskForm) {
        btnConfirmSubmit.addEventListener("click", function() {
            this.disabled = true;
            this.innerHTML = isTaskEditMode ? "Saving Changes..." : "Creating Task...";
            addTaskForm.submit();
        });
    }

    // Edit Task Trigger (Re-uses the EXACT SAME addTaskModal)
    document.querySelectorAll(".btn-edit-task-trigger").forEach(function(btn) {
        btn.addEventListener("click", function() {
            isTaskEditMode = true;
            const taskId = this.getAttribute("data-id");
            const faeId = this.getAttribute("data-fae-id");
            const taskName = this.getAttribute("data-task-name");
            const region = this.getAttribute("data-region");
            const course = this.getAttribute("data-course");
            const deadline = this.getAttribute("data-deadline");
            const priority = this.getAttribute("data-priority");
            const description = this.getAttribute("data-description");
            const links = this.getAttribute("data-links") || "";
            const attachmentCount = parseInt(this.getAttribute("data-attachment-count") || "0");

            const titleEl = document.getElementById("taskModalHeaderTitle");
            const subTitleEl = document.getElementById("taskModalHeaderSubtitle");
            const formEl = document.getElementById("addTaskForm");
            const methodEl = document.getElementById("taskFormMethod");

            if (titleEl) titleEl.textContent = "Edit Task Details";
            if (subTitleEl) subTitleEl.textContent = "Update task information, assigned FAE, deadline, attachments, or priority.";
            if (formEl) formEl.action = "{{ url('tasks') }}/" + taskId;
            if (methodEl) methodEl.value = "POST"; // Handled via POST with _method=PUT

            document.getElementById("addTaskName").value = taskName || "";
            document.getElementById("addTaskRegion").value = region || "";
            document.getElementById("addTaskCourse").value = course || "";
            document.getElementById("addTaskDeadline").value = deadline || "";
            document.getElementById("addTaskPriority").value = priority || "Medium";
            document.getElementById("addTaskDescription").value = description || "";
            
            const linksInput = document.getElementById("addTaskLinks");
            if (linksInput) linksInput.value = links;

            const filesInput = document.getElementById("addTaskAttachments");
            if (filesInput) filesInput.value = "";

            const filesPreview = document.getElementById("addTaskFilesPreview");
            if (filesPreview) filesPreview.innerHTML = "";

            const existingWrap = document.getElementById("addTaskExistingFilesWrap");
            const existingCountEl = document.getElementById("addTaskExistingFilesCount");
            const removeCb = document.getElementById("removeExistingAttachmentsCb");

            if (attachmentCount > 0) {
                if (existingWrap) existingWrap.style.display = "block";
                if (existingCountEl) existingCountEl.textContent = attachmentCount;
                if (removeCb) removeCb.checked = false;
            } else {
                if (existingWrap) existingWrap.style.display = "none";
            }

            // Pre-select assigned FAE in multi-select checkbox list
            faeCheckboxes.forEach(cb => {
                cb.checked = (String(cb.value) === String(faeId));
            });
            updateFaeMultiDisplay();

            openModal("addTaskModal");
        });
    });

    // Add Task Attachments Live Preview
    const addTaskFilesInput = document.getElementById("addTaskAttachments");
    const addTaskFilesPreview = document.getElementById("addTaskFilesPreview");

    if (addTaskFilesInput && addTaskFilesPreview) {
        addTaskFilesInput.addEventListener("change", function() {
            addTaskFilesPreview.innerHTML = "";
            const files = Array.from(this.files);

            if (files.length === 0) return;

            files.forEach(file => {
                const itemDiv = document.createElement("div");
                itemDiv.style.cssText = "position:relative; border:1px solid #cbd5e1; border-radius:6px; padding:4px; background:#ffffff; max-width:110px; font-size:10px; text-align:center;";

                if (file.type.startsWith("image/")) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        itemDiv.innerHTML = '<img src="' + e.target.result + '" style="width:100%; height:55px; object-fit:cover; border-radius:4px; display:block; margin-bottom:3px;">' +
                                            '<div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-weight:600; color:#475569;">' + escapeHtml(file.name) + '</div>';
                    };
                    reader.readAsDataURL(file);
                } else {
                    itemDiv.innerHTML = '<div style="height:55px; display:flex; align-items:center; justify-content:center; background:#f1f5f9; border-radius:4px; font-size:18px; margin-bottom:3px;">📄</div>' +
                                        '<div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-weight:600; color:#475569;">' + escapeHtml(file.name) + '</div>';
                }
                addTaskFilesPreview.appendChild(itemDiv);
            });
        });
    }

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

    // Search and Filter + Responsive Pagination Logic
    const searchInput = document.getElementById("taskSearchInput");
    const statusFilter = document.getElementById("statusFilter");
    const faeFilter = document.getElementById("faeFilter");
    const priorityFilter = document.getElementById("priorityFilter");

    let currentTaskPage = 1;
    let tasksPerPage = 10;
    let matchingRows = [];

    function calculateTasksPerPage() {
        const width = window.innerWidth;
        if (width < 768) return 6;
        if (width < 1200) return 8;
        return 10;
    }

    function applyTaskFilters() {
        const query = searchInput ? searchInput.value.toLowerCase().trim() : "";
        const statusVal = statusFilter ? statusFilter.value : "";
        const faeVal = faeFilter ? faeFilter.value : "";
        const priorityVal = priorityFilter ? priorityFilter.value : "";

        const allRows = Array.from(document.querySelectorAll(".task-table-row"));
        matchingRows = [];

        allRows.forEach(function(row) {
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

            let matchesStatus = true;
            if (statusVal === 'Overdue') {
                const isOverdue = row.getAttribute("data-is-overdue") === "1" || status.toLowerCase() === "overdue";
                matchesStatus = isOverdue;
            } else if (statusVal) {
                matchesStatus = (status.toLowerCase() === statusVal.toLowerCase());
            }
            const matchesFae = !faeVal || faeId === faeVal;
            const matchesPriority = !priorityVal || priority === priorityVal;

            if (matchesQuery && matchesStatus && matchesFae && matchesPriority) {
                matchingRows.push(row);
            } else {
                row.style.display = "none";
            }
        });

        currentTaskPage = 1;
        renderTasksPage();
    }

    function renderTasksPage() {
        tasksPerPage = calculateTasksPerPage();
        const total = matchingRows.length;
        const totalPages = Math.ceil(total / tasksPerPage) || 1;

        if (currentTaskPage > totalPages) currentTaskPage = totalPages;
        if (currentTaskPage < 1) currentTaskPage = 1;

        const startIndex = (currentTaskPage - 1) * tasksPerPage;
        const endIndex = startIndex + tasksPerPage;

        matchingRows.forEach((row, idx) => {
            if (idx >= startIndex && idx < endIndex) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        });

        // Update range display
        const rangeSpan = document.getElementById("tasksPageRangeSpan");
        const totalSpan = document.getElementById("tasksTotalCountSpan");
        if (rangeSpan && totalSpan) {
            if (total === 0) {
                rangeSpan.textContent = "0";
            } else {
                rangeSpan.textContent = (startIndex + 1) + "–" + Math.min(endIndex, total);
            }
            totalSpan.textContent = total;
        }

        // Update prev/next buttons
        const prevBtn = document.getElementById("prevTaskPageBtn");
        const nextBtn = document.getElementById("nextTaskPageBtn");
        if (prevBtn) prevBtn.disabled = (currentTaskPage <= 1);
        if (nextBtn) nextBtn.disabled = (currentTaskPage >= totalPages || total === 0);

        // Render page buttons
        const numbersWrap = document.getElementById("tasksPaginationNumbers");
        if (numbersWrap) {
            numbersWrap.innerHTML = "";
            for (let p = 1; p <= totalPages; p++) {
                const btn = document.createElement("button");
                btn.type = "button";
                btn.className = "btn-sm " + (p === currentTaskPage ? "primary-button" : "outline-button");
                btn.style.padding = "4px 10px";
                btn.textContent = p;
                btn.addEventListener("click", () => {
                    currentTaskPage = p;
                    renderTasksPage();
                });
                numbersWrap.appendChild(btn);
            }
        }
    }

    const prevTaskBtn = document.getElementById("prevTaskPageBtn");
    const nextTaskBtn = document.getElementById("nextTaskPageBtn");
    if (prevTaskBtn) {
        prevTaskBtn.addEventListener("click", () => {
            if (currentTaskPage > 1) {
                currentTaskPage--;
                renderTasksPage();
            }
        });
    }
    if (nextTaskBtn) {
        nextTaskBtn.addEventListener("click", () => {
            currentTaskPage++;
            renderTasksPage();
        });
    }

    window.addEventListener("resize", () => {
        renderTasksPage();
    });

    if (searchInput) searchInput.addEventListener("input", applyTaskFilters);
    if (statusFilter) statusFilter.addEventListener("change", applyTaskFilters);
    if (faeFilter) faeFilter.addEventListener("change", applyTaskFilters);
    if (priorityFilter) priorityFilter.addEventListener("change", applyTaskFilters);

    // Initial task filter/pagination run
    applyTaskFilters();

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
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
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
                fillEl.style.width = pct + '%';
                fillEl.className = pct <= 25 ? 'progress-red' : (pct <= 50 ? 'progress-orange' : (pct <= 75 ? 'progress-gold' : 'progress-green'));
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

                // Render Supervisor Attachments
                const attWrap = document.getElementById("reportModalAttachmentsWrap");
                const attListEl = document.getElementById("reportModalAttachmentsList");
                const attArray = t.attachments || [];
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
                                    const assetUrl = '{{ url('files') }}/' + encodeURIComponent(attPath);

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

    // Auto-open modal if URL has view_task or task_id parameter
    const urlParams = new URLSearchParams(window.location.search);
    const autoTaskId = urlParams.get('view_task') || urlParams.get('task_id');
    if (autoTaskId) {
        openTaskTimeline(autoTaskId);
    }
</script>
@endpush
