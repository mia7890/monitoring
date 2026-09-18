@extends('layouts.app')

@section('title', 'Contacts Management')
@section('breadcrumb', 'Contacts')

@push('styles')
<style>
    /* Contacts Mobile & Responsive Enhancements */
    .fae-page-section {
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
    }

    .fae-page-intro {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 16px;
    }

    .fae-page-intro h1 {
        font-size: 20px;
        margin: 2px 0 4px 0;
        letter-spacing: -0.3px;
    }

    .fae-tabs-nav {
        display: flex;
        gap: 6px;
        margin-bottom: 16px;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 2px;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        white-space: nowrap;
    }

    .tab-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 16px;
        font-size: 13px;
        font-weight: 700;
        border: none;
        background: none;
        cursor: pointer;
        color: #64748b;
        border-bottom: 3px solid transparent;
        margin-bottom: -5px;
        transition: all 0.15s ease;
        border-radius: 4px 4px 0 0;
    }

    .tab-btn.active {
        color: #0f172a;
        border-bottom-color: var(--primary, #b52f32);
    }

    .fae-filter-panel {
        margin-bottom: 16px;
        padding: 12px 16px;
    }

    .fae-filter-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
    }

    .fae-filter-inputs {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        flex: 1;
        min-width: 240px;
    }

    .fae-search-box {
        position: relative;
        flex: 1;
        min-width: 200px;
        max-width: 360px;
    }

    .fae-dept-filter-group {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .fae-dept-filter-group label {
        font-size: 12px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    .fae-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 14px;
        margin-bottom: 16px;
    }

    .fae-card {
        background: white;
        border: 1px solid var(--border);
        border-radius: 8px;
        padding: 14px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: border-color 0.15s, box-shadow 0.15s;
        box-sizing: border-box;
    }

    .fae-card-actions {
        display: flex;
        align-items: center;
        gap: 5px;
        flex-wrap: nowrap;
        margin-top: 12px;
        padding-top: 10px;
        border-top: 1px solid #f1f5f9;
        position: relative;
        z-index: 1;
    }

    .fae-card-actions > * {
        position: relative;
        z-index: 2;
        pointer-events: auto;
        cursor: pointer;
    }

    .fae-action-icon-btn,
    .btn-add-task-fae {
        pointer-events: auto;
        cursor: pointer;
    }

    .modal-backdrop-custom {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.5);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        backdrop-filter: blur(3px);
        padding: 12px;
        box-sizing: border-box;
    }

    .modal-box-custom {
        background: white;
        border-radius: 12px;
        max-width: 480px;
        width: 100%;
        max-height: calc(100vh - 24px);
        max-height: calc(100dvh - 24px);
        overflow-y: auto;
        padding: 20px 18px;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.15);
        border: 1px solid #e2e8f0;
        animation: modalPop 0.2s ease;
        box-sizing: border-box;
    }

    .modal-form-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }

    /* Mobile Responsive Rules (Phones <= 640px) */
    @media (max-width: 640px) {
        .fae-page-intro h1 {
            font-size: 18px;
        }

        .fae-page-intro p {
            font-size: 12px;
        }

        .fae-tabs-nav {
            width: 100%;
            gap: 4px;
        }

        .tab-btn {
            flex: 1 1 50%;
            justify-content: center;
            padding: 8px 6px;
            font-size: 12px;
            gap: 6px;
        }

        .tab-btn span:first-child {
            white-space: nowrap;
        }

        .fae-filter-panel {
            padding: 12px;
        }

        .fae-filter-row {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }

        .fae-filter-inputs {
            flex-direction: column;
            align-items: stretch;
            min-width: 0;
            width: 100%;
            gap: 8px;
        }

        .fae-search-box {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
        }

        .fae-search-box input {
            font-size: 14px; /* Prevent iOS auto zoom */
            height: 38px;
        }

        .fae-dept-filter-group {
            width: 100%;
            flex-direction: column;
            align-items: stretch;
            gap: 4px;
        }

        .fae-dept-filter-group label {
            font-size: 11px;
        }

        .fae-dept-filter-group select {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            font-size: 14px; /* Prevent iOS auto zoom */
            height: 38px;
        }

        .fae-count-text {
            font-size: 11px !important;
            text-align: right;
        }

        .fae-grid {
            grid-template-columns: 1fr;
            gap: 12px;
        }

        .fae-card-actions {
            flex-wrap: wrap;
            gap: 6px;
        }

        .btn-add-task-fae {
            flex: 1 1 100%;
            order: 1;
            padding: 8px 10px;
            font-size: 12px;
            min-height: 36px;
        }

        .fae-action-icon-btn {
            flex: 1 1 20%;
            order: 2;
            min-height: 36px;
            padding: 6px !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
        }

        .fae-pagination-row {
            flex-direction: column;
            gap: 10px;
            align-items: center;
            text-align: center;
            padding: 12px 10px !important;
        }

        .modal-box-custom {
            padding: 16px 14px;
        }

        .modal-form-grid-2 {
            grid-template-columns: 1fr;
            gap: 8px;
        }

        .modal-box-custom input,
        .modal-box-custom select,
        .modal-box-custom textarea {
            font-size: 14px; /* Prevent iOS auto zoom */
        }

        /* Mobile pending table adaptation */
        .pending-table-cell {
            padding: 10px 12px !important;
            font-size: 12px !important;
        }
    }
</style>
@endpush

@section('content')
<section class="content fae-page-section">

    <!-- PAGE INTRO -->
    <div class="fae-page-intro">
        <div>
            <span class="welcome-label">TEAM &amp; ACCESS MANAGEMENT</span>
            <h1>Contacts Management</h1>
            <p>Manage approved contacts, generate access codes, send emails, and review pending registrations.</p>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="fae-tabs-nav">
        <button type="button" class="tab-btn active" id="tabBtnActive" onclick="switchTab('active')">
            <span>Active Contacts</span>
            <span style="background:#f1f5f9; color:#475569; font-size:11px; font-weight:800; padding:2px 8px; border-radius:12px; border:1px solid #cbd5e1;">{{ count($faeList) }}</span>
        </button>
        <button type="button" class="tab-btn" id="tabBtnPending" onclick="switchTab('pending')">
            <span>Pending Registrations</span>
            @if(count($pendingList) > 0)
                <span style="background:#ef4444; color:#ffffff; font-size:11px; font-weight:800; padding:2px 8px; border-radius:12px;">{{ count($pendingList) }}</span>
            @else
                <span style="background:#f1f5f9; color:#94a3b8; font-size:11px; font-weight:800; padding:2px 8px; border-radius:12px;">0</span>
            @endif
        </button>
    </div>

    <!-- TAB 1: ACTIVE CONTACTS -->
    <div id="tabContentActive">
        <!-- Filter & Search Toolbar -->
        <div class="panel fae-filter-panel">
            <div class="fae-filter-row">
                <div class="fae-filter-inputs">
                    <div class="fae-search-box">
                        <input type="text" id="faeSearchInput" class="form-control" placeholder="Search by name, email, code..." style="padding-left:34px; font-size:13px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2" style="position:absolute; left:10px; top:50%; transform:translateY(-50%);"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </div>

                    <div class="fae-dept-filter-group">
                        <label for="faeDeptSelect">Department:</label>
                        <select id="faeDeptSelect" class="form-select" style="min-width:160px; font-size:13px; padding:7px 12px; border-radius:8px; border:1px solid #cbd5e1; background-color:#ffffff; cursor:pointer;">
                            <option value="all">All Departments</option>
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}">{{ $d->department_name }}</option>
                            @endforeach
                            <option value="none">Unassigned</option>
                        </select>
                    </div>
                </div>

                <div class="fae-count-text" style="font-size:12px; color:#64748b; font-weight:600;">
                    Showing <span id="faeVisibleCount">{{ count($faeList) }}</span> of {{ count($faeList) }} contacts
                </div>
            </div>
        </div>

        <div class="panel" style="padding:16px;">
            <div class="panel-header" style="margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid #f1f5f9;">
                <div>
                    <h2 style="font-size:15px; font-weight:700; color:#0f172a; margin:0;">Active Contacts ({{ count($faeList) }})</h2>
                    <p style="font-size:11.5px; color:#64748b; margin:2px 0 0 0;">Team members with Access Codes</p>
                </div>
            </div>

            <div class="fae-grid" id="faeCardGrid">
                @forelse($faeList as $fae)
                    @php
                        $avgProgress = round($fae->avg_progress);
                    @endphp
                    <article class="fae-card" 
                             data-id="{{ $fae->id }}"
                             data-name="{{ strtolower($fae->name) }}"
                             data-email="{{ strtolower($fae->email ?? '') }}"
                             data-code="{{ strtolower($fae->fae_code) }}"
                             data-deptid="{{ $fae->department_id ? $fae->department_id : 'none' }}">
                        <div>
                            <div class="fae-card-top" style="display:flex; align-items:flex-start; gap:12px; margin-bottom:10px;">
                                <div class="fae-avatar-box" style="background:var(--primary); color:white; width:42px; height:42px; border-radius:8px; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:14px; flex-shrink:0; overflow:hidden;">
                                    @if(!empty($fae->profile_image))
                                        <img class="fae-profile-image" src="{{ \App\Services\UploadService::url($fae->profile_image) }}" alt="{{ $fae->name }}" style="width:100%; height:100%; object-fit:cover;">
                                    @else
                                        {{ strtoupper(substr($fae->name, 0, 2)) }}
                                    @endif
                                </div>
                                <div class="fae-info" style="flex:1; min-width:0;">
                                    <h3 style="margin:0 0 2px 0; font-size:13.5px; font-weight:700; color:#0f172a; word-break:break-word;">{{ $fae->name }}</h3>
                                    <span class="fae-code-badge" style="display:inline-block; background:#f1f5f9; color:#475569; font-size:10px; font-weight:700; padding:1px 6px; border-radius:4px; border:1px solid #e2e8f0;">{{ $fae->fae_code }}</span>
                                    <div class="fae-dept" style="margin-top:3px; font-size:11px; color:#64748b; word-break:break-word;">{{ $fae->department_name }}</div>
                                    @if($fae->email)
                                        <div style="font-size:11px; color:#64748b; margin-top:2px; word-break:break-all;">
                                            ✉ {{ $fae->email }}
                                        </div>
                                    @endif
                                    @if($fae->phone)
                                        <div style="font-size:11px; color:#64748b; margin-top:1px;">
                                            📞 {{ $fae->phone }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="fae-stats-row" style="display:grid; grid-template-columns:1fr 1fr; gap:6px; background:#f8fafc; border-radius:6px; padding:8px 10px; margin-bottom:10px; border:1px solid #e2e8f0;">
                                <div class="fae-stat-col" style="text-align:center;">
                                    <span style="display:block; font-size:9.5px; color:#64748b; text-transform:uppercase; font-weight:700;">Tasks</span>
                                    <strong style="display:block; font-size:13px; font-weight:700; color:#0f172a;">{{ (int)$fae->total_tasks }}</strong>
                                </div>
                                <div class="fae-stat-col" style="text-align:center;">
                                    <span style="display:block; font-size:9.5px; color:#64748b; text-transform:uppercase; font-weight:700;">Completed</span>
                                    <strong style="display:block; font-size:13px; font-weight:700; color:var(--green, #16a34a);">{{ (int)$fae->completed_tasks }}</strong>
                                </div>
                            </div>

                            <div class="fae-progress-bar-wrap" style="width:100%; margin-bottom:8px;">
                                <div class="fae-progress-label" style="display:flex; justify-content:space-between; font-size:10px; font-weight:700; color:#64748b; margin-bottom:4px;">
                                    <span>Average progress</span>
                                    <strong>{{ $avgProgress }}%</strong>
                                </div>
                                <div class="progress-bar" style="width:100%; height:6px; background:#e2e8f0; border-radius:3px; overflow:hidden;">
                                    @php $faeBarClass = $avgProgress <= 25 ? 'progress-red' : ($avgProgress <= 50 ? 'progress-orange' : ($avgProgress <= 75 ? 'progress-gold' : 'progress-green')); @endphp
                                    <div class="{{ $faeBarClass }}" style="width:{{ $avgProgress }}%; height:100%;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="fae-card-actions">
                            <a href="{{ route('tasks.index', ['assign_fae' => $fae->id]) }}" class="btn-add-task-fae primary-button" title="Assign a new task to {{ $fae->name }}" style="text-decoration:none; text-align:center;">
                                ＋ Task
                            </a>
                            <a href="{{ route('fae.link', ['code' => $fae->fae_code]) }}" class="outline-button fae-action-icon-btn" target="_blank" rel="noopener" title="Open Direct Portal Link" style="text-decoration:none;">
                                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
                            </a>
                            @if($fae->email)
                                <button type="button" class="btn-email-fae outline-button fae-action-icon-btn" 
                                        data-id="{{ $fae->id }}" 
                                        data-name="{{ $fae->name }}" 
                                        data-email="{{ $fae->email }}"
                                        title="Send direct email" 
                                        style="color:#2563eb; border-color:#bfdbfe; background:#eff6ff;">
                                    ✉
                                </button>
                            @endif
                            <button type="button" class="btn-edit-fae outline-button fae-action-icon-btn" 
                                    data-id="{{ $fae->id }}"
                                    data-name="{{ $fae->name }}"
                                    data-code="{{ $fae->fae_code }}"
                                    data-email="{{ $fae->email ?? '' }}"
                                    data-phone="{{ $fae->phone ?? '' }}"
                                    data-deptid="{{ $fae->department_id ?? '' }}"
                                    title="Edit contact information">
                                ✏
                            </button>
                            <button type="button" class="btn-delete-fae btn-danger-outline fae-action-icon-btn"
                                    data-id="{{ $fae->id }}"
                                    data-name="{{ $fae->name }}"
                                    data-code="{{ $fae->fae_code }}"
                                    data-tasks="{{ $fae->total_tasks }}"
                                    title="Delete contact">
                                🗑
                            </button>
                        </div>
                    </article>
                @empty
                    <div style="grid-column: 1 / -1; text-align:center; padding:36px; color:var(--text-light);">
                        <p>No active contacts registered yet. New contacts will appear here once approved from the Pending Registrations tab.</p>
                    </div>
                @endforelse
            </div>

            <!-- Responsive Client-Side Pagination Controls (6 per page) -->
            <div id="faePaginationContainer" class="fae-pagination-row" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; padding:14px 16px; border-top:1px solid #f1f5f9; margin-top:8px;">
                <div style="font-size:12px; color:#64748b; font-weight:600;">
                    Showing <span id="pageItemRangeSpan">1–6</span> of <span id="totalItemsCountSpan">{{ count($faeList) }}</span> contacts
                </div>
                <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap; justify-content:center;" id="paginationControlsGroup">
                    <button type="button" id="prevPageBtn" class="outline-button btn-sm" style="display:inline-flex; align-items:center; gap:4px; min-height:32px;" disabled>
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"></polyline></svg>
                        Prev
                    </button>
                    <div id="paginationNumbersWrap" style="display:inline-flex; align-items:center; gap:4px; flex-wrap:wrap;"></div>
                    <button type="button" id="nextPageBtn" class="outline-button btn-sm" style="display:inline-flex; align-items:center; gap:4px; min-height:32px;">
                        Next
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"></polyline></svg>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: PENDING REGISTRATIONS -->
    <div id="tabContentPending" style="display:none;">
        <div class="panel" style="padding:16px;">
            <div class="panel-header" style="margin-bottom:14px; padding-bottom:10px; border-bottom:1px solid #f1f5f9;">
                <div>
                    <h2 style="font-size:15px; font-weight:700; color:#0f172a; margin:0;">Pending Registrations ({{ count($pendingList) }})</h2>
                    <p style="font-size:11.5px; color:#64748b; margin:2px 0 0 0;">Review self-registration requests from new users. Approving will generate and email their Access Code.</p>
                </div>
            </div>

            @if(count($pendingList) > 0)
                <div style="overflow-x:auto; -webkit-overflow-scrolling:touch; width:100%; border:1px solid #e2e8f0; border-radius:8px;">
                    <table style="width:100%; border-collapse:collapse; font-size:13px; text-align:left; min-width:580px;">
                        <thead>
                            <tr style="background:#f8fafc; border-bottom:2px solid #e2e8f0; color:#475569; font-size:11px; text-transform:uppercase; letter-spacing:0.5px;">
                                <th style="padding:10px 14px;">Applicant</th>
                                <th style="padding:10px 14px;">Email &amp; Phone</th>
                                <th style="padding:10px 14px;">Department</th>
                                <th style="padding:10px 14px;">Registered On</th>
                                <th style="padding:10px 14px; text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingList as $pending)
                                <tr style="border-bottom:1px solid #e2e8f0; transition:background 0.15s ease;">
                                    <td class="pending-table-cell" style="padding:12px 14px;">
                                        <div style="display:flex; align-items:center; gap:8px;">
                                            <div style="width:34px; height:34px; border-radius:50%; background:#e0e7ff; color:#3730a3; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:12px; flex-shrink:0;">
                                                {{ strtoupper(substr($pending->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <strong style="color:#0f172a; font-size:13px; display:block;">{{ $pending->name }}</strong>
                                                <span style="font-size:10.5px; color:#94a3b8;">ID: #{{ $pending->id }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="pending-table-cell" style="padding:12px 14px;">
                                        <div style="color:#0f172a; font-weight:600; font-size:12.5px;">{{ $pending->email ?: '—' }}</div>
                                        @if($pending->phone)
                                            <div style="font-size:11px; color:#64748b; margin-top:2px;">📞 {{ $pending->phone }}</div>
                                        @endif
                                    </td>
                                    <td class="pending-table-cell" style="padding:12px 14px;">
                                        @if($pending->department)
                                            <span style="display:inline-block; padding:2px 8px; background:#f1f5f9; color:#334155; border-radius:4px; font-size:11px; font-weight:600; border:1px solid #e2e8f0;">
                                                {{ $pending->department->department_name }}
                                            </span>
                                        @else
                                            <span style="color:#94a3b8; font-size:11.5px;">Unassigned</span>
                                        @endif
                                    </td>
                                    <td class="pending-table-cell" style="padding:12px 14px; font-size:11.5px; color:#64748b;">
                                        <div>{{ $pending->created_at ? $pending->created_at->format('M d, Y') : '—' }}</div>
                                        <div style="font-size:10px; color:#94a3b8;">{{ $pending->created_at ? $pending->created_at->diffForHumans() : '' }}</div>
                                    </td>
                                    <td class="pending-table-cell" style="padding:12px 14px; text-align:right;">
                                        <div style="display:inline-flex; gap:6px; align-items:center; flex-wrap:wrap; justify-content:flex-end;">
                                            <!-- Approve Form -->
                                            <form method="POST" action="{{ route('fae.approve', $pending->id) }}" onsubmit="return confirm('Approve registration for {{ addslashes($pending->name) }}? An Access Code will be generated and emailed.');" style="display:inline-block; margin:0; position:relative; z-index:1;">
                                                @csrf
                                                <button type="submit" style="display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:7px 14px; background:#059669; color:#ffffff; border:none; border-radius:6px; font-size:11.5px; font-weight:700; cursor:pointer; transition:all 0.15s ease; pointer-events:auto; min-width:96px;">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                                                    Approve &amp; Send Code
                                                </button>
                                            </form>

                                            <!-- Reject Form -->
                                            <form method="POST" action="{{ route('fae.reject', $pending->id) }}" onsubmit="return confirm('Reject registration for {{ addslashes($pending->name) }}?');" style="display:inline-block; margin:0; position:relative; z-index:1;">
                                                @csrf
                                                <button type="submit" style="display:inline-flex; align-items:center; justify-content:center; gap:6px; padding:7px 14px; background:#ffffff; color:#dc2626; border:1px solid #fca5a5; border-radius:6px; font-size:11.5px; font-weight:600; cursor:pointer; transition:all 0.15s ease; pointer-events:auto; min-width:88px;">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                                    Reject
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div style="text-align:center; padding:40px 16px; color:#64748b;">
                    <div style="width:50px; height:50px; border-radius:50%; background:#f0fdf4; color:#16a34a; display:inline-flex; align-items:center; justify-content:center; margin-bottom:10px;">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <h3 style="margin:0 0 4px 0; font-size:15px; color:#0f172a; font-weight:700;">No Pending Registrations</h3>
                    <p style="margin:0; font-size:12.5px; color:#94a3b8;">All registration requests have been reviewed and approved.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- =========================================================
         MODALS (RESPONSIVE FOR MOBILE / PHONES)
         ========================================================= -->

    <!-- Modal 1: Add Contact Modal -->
    <div id="addFaeModal" class="modal-backdrop-custom">
        <div class="modal-box-custom">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; padding-bottom:8px; border-bottom:1px solid #f1f5f9;">
                <h3 style="margin:0; font-size:16px; font-weight:800; color:#0f172a;">Add New Contact</h3>
                <button type="button" onclick="closeModal('addFaeModal')" style="background:none; border:none; color:#94a3b8; cursor:pointer; font-size:20px; line-height:1; padding:4px 8px;">✕</button>
            </div>
            <form method="POST" action="{{ route('fae.store') }}" enctype="multipart/form-data" id="addFaeForm">
                @csrf
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <div class="form-group">
                        <label class="form-label" style="font-size:12px; font-weight:700; color:#334155; margin-bottom:4px; display:block;">Full Name <span style="color:#b52f32;">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Maria Santos" required>
                    </div>

                    <div class="modal-form-grid-2">
                        <div class="form-group">
                            <label class="form-label" style="font-size:12px; font-weight:700; color:#334155; margin-bottom:4px; display:block;">Access Code</label>
                            <input type="text" name="fae_code" class="form-control" placeholder="Auto-generated if empty">
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size:12px; font-weight:700; color:#334155; margin-bottom:4px; display:block;">Department</label>
                            <select name="department_id" class="form-control">
                                <option value="">— Select Department —</option>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}">{{ $d->department_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="modal-form-grid-2">
                        <div class="form-group">
                            <label class="form-label" style="font-size:12px; font-weight:700; color:#334155; margin-bottom:4px; display:block;">Email Address</label>
                            <input type="email" name="email" class="form-control" placeholder="user@company.com">
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size:12px; font-weight:700; color:#334155; margin-bottom:4px; display:block;">Phone Number</label>
                            <input type="tel" name="phone" class="form-control" placeholder="0917-123-4567">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-size:12px; font-weight:700; color:#334155; margin-bottom:4px; display:block;">Profile Picture</label>
                        <input type="file" name="profile_image" class="form-control" accept="image/*">
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:18px; padding-top:12px; border-top:1px solid #f1f5f9;">
                    <button type="button" onclick="closeModal('addFaeModal')" class="outline-button btn-sm" style="min-height:36px;">Cancel</button>
                    <button type="submit" class="primary-button btn-sm" style="min-height:36px;">Create Contact</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: Edit Contact Modal -->
    <div id="editFaeModal" class="modal-backdrop-custom">
        <div class="modal-box-custom">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; padding-bottom:8px; border-bottom:1px solid #f1f5f9;">
                <h3 style="margin:0; font-size:16px; font-weight:800; color:#0f172a;">Edit Contact</h3>
                <button type="button" onclick="closeModal('editFaeModal')" style="background:none; border:none; color:#94a3b8; cursor:pointer; font-size:20px; line-height:1; padding:4px 8px;">✕</button>
            </div>
            <form method="POST" action="" enctype="multipart/form-data" id="editFaeForm">
                @csrf
                @method('PUT')
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <div class="form-group">
                        <label class="form-label" style="font-size:12px; font-weight:700; color:#334155; margin-bottom:4px; display:block;">Full Name <span style="color:#b52f32;">*</span></label>
                        <input type="text" id="editFaeName" name="name" class="form-control" required>
                    </div>

                    <div class="modal-form-grid-2">
                        <div class="form-group">
                            <label class="form-label" style="font-size:12px; font-weight:700; color:#334155; margin-bottom:4px; display:block;">Access Code <span style="color:#b52f32;">*</span></label>
                            <input type="text" id="editFaeCode" name="fae_code" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size:12px; font-weight:700; color:#334155; margin-bottom:4px; display:block;">Department</label>
                            <select id="editFaeDept" name="department_id" class="form-control">
                                <option value="">— Select Department —</option>
                                @foreach($departments as $d)
                                    <option value="{{ $d->id }}">{{ $d->department_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="modal-form-grid-2">
                        <div class="form-group">
                            <label class="form-label" style="font-size:12px; font-weight:700; color:#334155; margin-bottom:4px; display:block;">Email Address</label>
                            <input type="email" id="editFaeEmail" name="email" class="form-control">
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size:12px; font-weight:700; color:#334155; margin-bottom:4px; display:block;">Phone Number</label>
                            <input type="tel" id="editFaePhone" name="phone" class="form-control">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-size:12px; font-weight:700; color:#334155; margin-bottom:4px; display:block;">Update Profile Picture</label>
                        <input type="file" name="profile_image" class="form-control" accept="image/*">
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:18px; padding-top:12px; border-top:1px solid #f1f5f9;">
                    <button type="button" onclick="closeModal('editFaeModal')" class="outline-button btn-sm" style="min-height:36px;">Cancel</button>
                    <button type="submit" class="primary-button btn-sm" style="min-height:36px;">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 3: Delete Contact Confirmation Modal -->
    <div id="deleteFaeModal" class="modal-backdrop-custom">
        <div class="modal-box-custom" style="max-width:420px;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
                <h3 style="margin:0; font-size:16px; font-weight:800; color:#b91c1c;">Confirm Contact Deletion</h3>
                <button type="button" onclick="closeModal('deleteFaeModal')" style="background:none; border:none; color:#94a3b8; cursor:pointer; font-size:20px; line-height:1; padding:4px 8px;">✕</button>
            </div>
            
            <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:12px; margin-bottom:14px;">
                <p style="margin:0 0 6px 0; font-size:13px; color:#991b1b; font-weight:700;">
                    Are you sure you want to remove <span id="delFaeName">—</span> (<span id="delFaeCode">—</span>)?
                </p>
                <div style="font-size:11.5px; color:#7f1d1d; line-height:1.4;">
                    <strong>Deletion Impact:</strong>
                    <ul style="margin:4px 0 0 16px; padding:0;">
                        <li><strong id="delFaeTasksCount">0</strong> assigned task(s) will become unassigned.</li>
                        <li>This contact's login Access Code will be deactivated immediately.</li>
                    </ul>
                </div>
            </div>

            <form id="deleteFaeForm" method="POST" action="">
                @csrf
                @method('DELETE')
                <div style="display:flex; justify-content:flex-end; gap:8px;">
                    <button type="button" onclick="closeModal('deleteFaeModal')" class="outline-button btn-sm" style="min-height:36px;">Cancel</button>
                    <button type="submit" class="btn-danger-outline btn-sm" style="background:#dc2626; color:#ffffff; border:none; min-height:36px;">Confirm Deletion</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 4: Send Direct Email Modal -->
    <div id="emailFaeModal" class="modal-backdrop-custom">
        <div class="modal-box-custom">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:14px; padding-bottom:8px; border-bottom:1px solid #f1f5f9;">
                <h3 style="margin:0; font-size:16px; font-weight:800; color:#0f172a;">Send Email to Contact</h3>
                <button type="button" onclick="closeModal('emailFaeModal')" style="background:none; border:none; color:#94a3b8; cursor:pointer; font-size:20px; line-height:1; padding:4px 8px;">✕</button>
            </div>
            <form method="POST" action="" id="emailFaeForm" onsubmit="const btn = this.querySelector('button[type=submit]'); btn.disabled = true; btn.textContent = 'Sending...';">
                @csrf
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:10px 12px; font-size:12.5px; color:#334155;">
                        <div>Recipient: <strong id="emailFaeRecipientName">—</strong></div>
                        <div style="font-size:11.5px; color:#64748b;" id="emailFaeRecipientEmail">—</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-size:12px; font-weight:700; color:#334155; margin-bottom:4px; display:block;">Subject <span style="color:#b52f32;">*</span></label>
                        <input type="text" name="subject" class="form-control" placeholder="e.g. Schedule Update / Task Notice" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" style="font-size:12px; font-weight:700; color:#334155; margin-bottom:4px; display:block;">Message Body <span style="color:#b52f32;">*</span></label>
                        <textarea name="message" class="form-control" rows="4" placeholder="Type your email message to this contact..." required></textarea>
                    </div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:18px; padding-top:12px; border-top:1px solid #f1f5f9;">
                    <button type="button" onclick="closeModal('emailFaeModal')" class="outline-button btn-sm" style="min-height:36px;">Cancel</button>
                    <button type="submit" class="primary-button btn-sm" style="min-height:36px;">Send Email</button>
                </div>
            </form>
        </div>
    </div>

    <!-- FOOTER -->
    <footer>
        <span>Monitoring System © 2026</span>
        <span>Contacts management</span>
    </footer>
</section>
@endsection

@push('scripts')
<script>
    function closeModal(id) {
        const m = document.getElementById(id);
        if (m) m.style.display = 'none';
    }

    function openModal(id) {
        const m = document.getElementById(id);
        if (m) m.style.display = 'flex';
    }

    function switchTab(tab) {
        const tabBtnActive = document.getElementById('tabBtnActive');
        const tabBtnPending = document.getElementById('tabBtnPending');
        const contentActive = document.getElementById('tabContentActive');
        const contentPending = document.getElementById('tabContentPending');

        if (tab === 'pending') {
            if (tabBtnActive) tabBtnActive.classList.remove('active');
            if (tabBtnPending) tabBtnPending.classList.add('active');
            if (contentActive) contentActive.style.display = 'none';
            if (contentPending) contentPending.style.display = 'block';
            window.location.hash = 'pending';
        } else {
            if (tabBtnActive) tabBtnActive.classList.add('active');
            if (tabBtnPending) tabBtnPending.classList.remove('active');
            if (contentActive) contentActive.style.display = 'block';
            if (contentPending) contentPending.style.display = 'none';
            window.location.hash = 'active';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const faeGrid = document.getElementById('faeCardGrid');

        document.addEventListener('click', function(event) {
            const editBtn = event.target.closest('.btn-edit-fae');
            if (editBtn) {
                const id = editBtn.dataset.id;
                const form = document.getElementById('editFaeForm');
                if (form) form.action = "{{ url('fae') }}/" + id;

                const nameField = document.getElementById('editFaeName');
                const codeField = document.getElementById('editFaeCode');
                const emailField = document.getElementById('editFaeEmail');
                const phoneField = document.getElementById('editFaePhone');
                const deptField = document.getElementById('editFaeDept');

                if (nameField) nameField.value = editBtn.dataset.name || '';
                if (codeField) codeField.value = editBtn.dataset.code || '';
                if (emailField) emailField.value = editBtn.dataset.email || '';
                if (phoneField) phoneField.value = editBtn.dataset.phone || '';
                if (deptField) deptField.value = editBtn.dataset.deptid || '';

                openModal('editFaeModal');
                return;
            }

            const deleteBtn = event.target.closest('.btn-delete-fae');
            if (deleteBtn) {
                const id = deleteBtn.dataset.id;
                const form = document.getElementById('deleteFaeForm');
                if (form) form.action = "{{ url('fae') }}/" + id;

                const nameField = document.getElementById('delFaeName');
                const codeField = document.getElementById('delFaeCode');
                const tasksField = document.getElementById('delFaeTasksCount');

                if (nameField) nameField.textContent = deleteBtn.dataset.name || '—';
                if (codeField) codeField.textContent = deleteBtn.dataset.code || '—';
                if (tasksField) tasksField.textContent = deleteBtn.dataset.tasks || '0';

                openModal('deleteFaeModal');
                return;
            }

            const emailBtn = event.target.closest('.btn-email-fae');
            if (emailBtn) {
                const id = emailBtn.dataset.id;
                const form = document.getElementById('emailFaeForm');
                if (form) form.action = "{{ url('fae') }}/" + id + "/send-email";

                const recipientName = document.getElementById('emailFaeRecipientName');
                const recipientEmail = document.getElementById('emailFaeRecipientEmail');

                if (recipientName) recipientName.textContent = emailBtn.dataset.name || '—';
                if (recipientEmail) recipientEmail.textContent = emailBtn.dataset.email || '—';

                openModal('emailFaeModal');
            }
        });

        // Close on backdrop click
        ['addFaeModal', 'editFaeModal', 'deleteFaeModal', 'emailFaeModal'].forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                el.addEventListener('click', function(e) {
                    if (e.target === el) closeModal(id);
                });
            }
        });

        // Filter & Search & Responsive 6-Per-Page Pagination Logic
        const searchInput = document.getElementById('faeSearchInput');
        const deptSelect = document.getElementById('faeDeptSelect');
        const cards = Array.from(document.querySelectorAll('.fae-card'));
        const visibleCountSpan = document.getElementById('faeVisibleCount');
        const pageSize = 6;
        let currentPage = 1;

        function getFilteredCards() {
            const query = (searchInput ? searchInput.value : '').trim().toLowerCase();
            const selectedDept = deptSelect ? deptSelect.value : 'all';

            return cards.filter(card => {
                const name = card.dataset.name || '';
                const email = card.dataset.email || '';
                const code = card.dataset.code || '';
                const dept = card.dataset.deptid || '';

                const matchesQuery = !query || name.includes(query) || email.includes(query) || code.includes(query);
                const matchesDept = (selectedDept === 'all') || (dept === selectedDept);

                return matchesQuery && matchesDept;
            });
        }

        function renderPagination() {
            const filtered = getFilteredCards();
            const totalItems = filtered.length;
            const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
            if (currentPage > totalPages) currentPage = totalPages;

            cards.forEach(card => card.style.display = 'none');

            const startIdx = (currentPage - 1) * pageSize;
            const endIdx = Math.min(startIdx + pageSize, totalItems);
            filtered.slice(startIdx, endIdx).forEach(card => card.style.display = '');

            if (visibleCountSpan) visibleCountSpan.textContent = totalItems;
            
            const rangeSpan = document.getElementById('pageItemRangeSpan');
            if (rangeSpan) {
                rangeSpan.textContent = totalItems === 0 ? '0' : (startIdx + 1) + '–' + endIdx;
            }
            const totalCountSpan = document.getElementById('totalItemsCountSpan');
            if (totalCountSpan) {
                totalCountSpan.textContent = totalItems;
            }

            const prevBtn = document.getElementById('prevPageBtn');
            const nextBtn = document.getElementById('nextPageBtn');
            if (prevBtn) prevBtn.disabled = (currentPage <= 1);
            if (nextBtn) nextBtn.disabled = (currentPage >= totalPages);

            // Render page number buttons
            const numbersWrap = document.getElementById('paginationNumbersWrap');
            if (numbersWrap) {
                numbersWrap.innerHTML = '';
                if (totalPages > 1) {
                    for (let p = 1; p <= totalPages; p++) {
                        const pageBtn = document.createElement('button');
                        pageBtn.type = 'button';
                        pageBtn.textContent = p;
                        pageBtn.className = (p === currentPage) ? 'primary-button btn-sm' : 'outline-button btn-sm';
                        pageBtn.style.padding = '4px 10px';
                        pageBtn.style.fontSize = '12px';
                        pageBtn.style.minWidth = '30px';
                        pageBtn.style.textAlign = 'center';
                        pageBtn.style.minHeight = '32px';
                        pageBtn.addEventListener('click', function() {
                            currentPage = p;
                            renderPagination();
                            const gridEl = document.getElementById('faeCardGrid');
                            if (gridEl) {
                                gridEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                            }
                        });
                        numbersWrap.appendChild(pageBtn);
                    }
                }
            }
        }

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                currentPage = 1;
                renderPagination();
            });
        }

        if (deptSelect) {
            deptSelect.addEventListener('change', function() {
                currentPage = 1;
                renderPagination();
            });
        }

        const prevBtnEl = document.getElementById('prevPageBtn');
        if (prevBtnEl) {
            prevBtnEl.addEventListener('click', function() {
                if (currentPage > 1) {
                    currentPage--;
                    renderPagination();
                    const gridEl = document.getElementById('faeCardGrid');
                    if (gridEl) gridEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });
        }

        const nextBtnEl = document.getElementById('nextPageBtn');
        if (nextBtnEl) {
            nextBtnEl.addEventListener('click', function() {
                const totalPages = Math.ceil(getFilteredCards().length / pageSize);
                if (currentPage < totalPages) {
                    currentPage++;
                    renderPagination();
                    const gridEl = document.getElementById('faeCardGrid');
                    if (gridEl) gridEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            });
        }

        renderPagination();

        // Check hash on load
        if (window.location.hash === '#pending' || {{ count($pendingList) > 0 && count($faeList) === 0 ? 'true' : 'false' }}) {
            switchTab('pending');
        }
    });
</script>
@endpush
