@extends('layouts.app')

@section('title', 'Department Management')
@section('breadcrumb', 'Department Management')

@section('content')
<section class="content">
    <div class="page-intro">
        <div>
            <span class="welcome-label">ADMIN SETTINGS</span>
            <h1>Department Management</h1>
            <p>Manage departments and department-based filtering.</p>
        </div>
        <div style="display:flex; align-items:center; gap:8px;">
            <button type="button" class="primary-button" onclick="openAddDeptModal()" style="display:inline-flex; align-items:center; gap:6px;">
                <span>+</span> Add Department
            </button>
            <a href="{{ route('fae.index') }}" class="outline-button" style="text-decoration:none;">Back to Contacts</a>
        </div>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <h2>Departments</h2>
                <p>{{ $departments->count() }} department entries</p>
            </div>
        </div>

        <div class="table-wrapper table-wrap" style="overflow-x:auto; -webkit-overflow-scrolling:touch; width:100%;">
            <table class="table-style">
                <thead>
                    <tr>
                        <th style="cursor:pointer; user-select:none;">Department</th>
                        <th>Status</th>
                        <th>Contacts</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $department)
                        <tr class="department-row" data-department-id="{{ $department->id }}" style="cursor:pointer;">
                            <td style="padding-top:12px; padding-bottom:12px;">
                                <strong>{{ $department->department_name }}</strong>
                                @if($department->faes_count > 0)
                                    <span style="display:inline-block; margin-left:8px; font-size:11px; padding:2px 6px; background:rgba(59,130,246,0.12); border-radius:999px; color:var(--primary);">
                                        Click to view members
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="status-badge {{ $department->is_active ? 'success' : 'muted' }}">
                                    {{ $department->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td><strong>{{ $department->faes_count }}</strong></td>
                            <td onclick="event.stopPropagation();">
                                <div style="display:flex; gap:6px; align-items:center;">
                                    <button type="button" class="btn-icon btn-edit-dept" 
                                            data-id="{{ $department->id }}" 
                                            data-name="{{ $department->department_name }}" 
                                            data-active="{{ $department->is_active ? '1' : '0' }}"
                                            title="Edit Department"
                                            style="background:#f1f5f9; border:1px solid #cbd5e1; border-radius:6px; padding:6px 8px; cursor:pointer; color:#334155; display:inline-flex; align-items:center; justify-content:center; transition:all 0.15s ease;">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>

                                    <form method="POST" action="{{ route('departments.destroy', $department->id) }}" onsubmit="return confirm('Are you sure you want to delete this department?');" style="margin:0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-icon" title="Delete Department" style="background:#fef2f2; border:1px solid #fecaca; border-radius:6px; padding:6px 8px; cursor:pointer; color:#b91c1c; display:inline-flex; align-items:center; justify-content:center; transition:all 0.15s ease;">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>

                        <!-- Members Expansion Row -->
                        <tr class="members-expansion-row members-row-{{ $department->id }}" style="display:none;">
                            <td colspan="4" style="padding:0; border:none; background:transparent;">
                                <div class="members-panel">
                                    <div class="members-panel-header">
                                        <h4 style="margin:0 0 12px; font-size:14px; font-weight:600;">Members in {{ $department->department_name }}</h4>
                                    </div>

                                    @if($department->faes->isNotEmpty())
                                        <div class="members-list">
                                            @foreach($department->faes as $fae)
                                                <div class="member-badge">
                                                    <div style="flex-shrink:0;">
                                                        @if(!empty($fae->profile_image))
                                                            <img src="{{ \App\Services\UploadService::url($fae->profile_image) }}" alt="{{ $fae->name }}" style="width:34px; height:34px; border-radius:50%; object-fit:cover; border:1px solid var(--border); display:block;">
                                                        @else
                                                            <div style="width:34px; height:34px; border-radius:50%; background:var(--primary); color:white; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:700;">
                                                                {{ strtoupper(substr($fae->name, 0, 2)) }}
                                                            </div>
                                                        @endif
                                                    </div>
                                                    <div class="member-details" style="flex:1; min-width:0;">
                                                        <div style="display:flex; align-items:center; justify-content:space-between; gap:6px;">
                                                            <strong style="font-size:13px; color:var(--text); white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="{{ $fae->name }}">{{ $fae->name }}</strong>
                                                            @if(!empty($fae->fae_code))
                                                                <span style="background:#e0f2fe; color:#0369a1; font-size:10px; font-weight:700; padding:1px 6px; border-radius:4px; flex-shrink:0;">{{ $fae->fae_code }}</span>
                                                            @endif
                                                        </div>
                                                        @php
                                                            $displayEmail = $fae->email;
                                                        @endphp
                                                        @if(!empty($displayEmail))
                                                            <span style="display:block; font-size:11px; color:var(--text-light); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-top:2px;" title="{{ $displayEmail }}">
                                                                {{ $displayEmail }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div style="padding:12px; text-align:center; color:var(--text-light); font-size:13px;">
                                            No members assigned to this department.
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" style="text-align:center; color:var(--text-light); padding:24px;">
                                No departments created yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Styles for Department Member Expansion -->
    <style>
        .department-row {
            transition: background-color 0.2s ease;
        }

        .department-row:hover {
            background-color: rgba(59, 130, 246, 0.05);
        }

        .department-row.expanded {
            background-color: rgba(59, 130, 246, 0.08);
            font-weight: 500;
        }

        .members-expansion-row {
            transition: all 0.2s ease;
        }

        .members-panel {
            padding: 16px 12px;
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.06) 0%, rgba(17, 24, 39, 0.08) 100%);
            border-left: 3px solid var(--primary);
            border-radius: 0 8px 8px 0;
            margin: 8px 0;
        }

        .members-panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 8px;
            border-bottom: 1px solid rgba(59, 130, 246, 0.15);
            margin-bottom: 12px;
        }

        .members-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 12px;
            margin-top: 12px;
        }

        .member-badge {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            background: white;
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .member-badge:hover {
            border-color: rgba(59, 130, 246, 0.4);
            background: rgba(59, 130, 246, 0.02);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08);
        }

        @media (max-width: 768px) {
            .members-list {
                grid-template-columns: 1fr;
            }

            .members-panel {
                padding: 12px;
            }
        }
    </style>

    <!-- Add Department Modal -->
    <div id="addDeptModal" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(3px);">
        <div style="background:white; border-radius:12px; max-width:440px; width:90%; padding:24px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); border:1px solid #e2e8f0; animation:modalPop 0.2s ease;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h3 style="margin:0; font-size:16px; font-weight:700; color:#0f172a;">Add New Department</h3>
                <button type="button" onclick="closeAddDeptModal()" style="background:none; border:none; color:#94a3b8; cursor:pointer; font-size:18px; padding:2px 6px;">✕</button>
            </div>
            <form id="addDeptForm" method="POST" action="{{ route('departments.store') }}">
                @csrf
                <div class="form-group" style="margin-bottom:14px;">
                    <label class="form-label" style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Department Name <span style="color:#b52f32;">*</span></label>
                    <input type="text" name="department_name" class="form-control" placeholder="e.g. Technical Support & Field Eng." required style="width:100%; box-sizing:border-box;">
                </div>
                <div class="form-group" style="margin-bottom:20px;">
                    <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:#334155; cursor:pointer;">
                        <input type="checkbox" name="is_active" value="1" checked>
                        Active Status
                    </label>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:8px;">
                    <button type="button" onclick="closeAddDeptModal()" class="outline-button btn-sm">Cancel</button>
                    <button type="submit" class="primary-button btn-sm">Create Department</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Department Modal -->
    <div id="editDeptModal" class="modal-backdrop" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center; backdrop-filter:blur(3px);">
        <div style="background:white; border-radius:12px; max-width:440px; width:90%; padding:24px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1); border:1px solid #e2e8f0; animation:modalPop 0.2s ease;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
                <h3 style="margin:0; font-size:16px; font-weight:700; color:#0f172a;">Edit Department</h3>
                <button type="button" onclick="closeEditDeptModal()" style="background:none; border:none; color:#94a3b8; cursor:pointer; font-size:18px; padding:2px 6px;">✕</button>
            </div>
            <form id="editDeptForm" method="POST" action="">
                @csrf
                @method('PUT')
                <div class="form-group" style="margin-bottom:14px;">
                    <label class="form-label" style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Department Name <span style="color:#b52f32;">*</span></label>
                    <input type="text" id="editDeptName" name="department_name" class="form-control" required style="width:100%; box-sizing:border-box;">
                </div>
                <div class="form-group" style="margin-bottom:20px;">
                    <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:#334155; cursor:pointer;">
                        <input type="checkbox" id="editDeptActive" name="is_active" value="1">
                        Active Status
                    </label>
                </div>
                <div style="display:flex; justify-content:flex-end; gap:8px;">
                    <button type="button" onclick="closeEditDeptModal()" class="outline-button btn-sm">Cancel</button>
                    <button type="submit" class="primary-button btn-sm">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <footer>
        <span>Monitoring System © 2026</span>
        <span>Department management</span>
    </footer>
</section>

<!-- JavaScript for Department Member Expansion/Collapse & Modals -->
<script>
    function openAddDeptModal() {
        document.getElementById('addDeptModal').style.display = 'flex';
    }

    function closeAddDeptModal() {
        document.getElementById('addDeptModal').style.display = 'none';
    }

    function openEditDeptModal(id, name, active) {
        const modal = document.getElementById('editDeptModal');
        const form = document.getElementById('editDeptForm');
        const nameInput = document.getElementById('editDeptName');
        const activeInput = document.getElementById('editDeptActive');

        form.action = "{{ url('departments') }}/" + id;
        nameInput.value = name;
        activeInput.checked = (active === '1' || active === 1 || active === true);

        modal.style.display = 'flex';
    }

    function closeEditDeptModal() {
        document.getElementById('editDeptModal').style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Edit Department Buttons
        document.querySelectorAll('.btn-edit-dept').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                openEditDeptModal(this.dataset.id, this.dataset.name, this.dataset.active);
            });
        });

        // Close modals on backdrop click
        ['addDeptModal', 'editDeptModal'].forEach(id => {
            const modalEl = document.getElementById(id);
            if (modalEl) {
                modalEl.addEventListener('click', function(e) {
                    if (e.target === this) this.style.display = 'none';
                });
            }
        });

        const departmentRows = document.querySelectorAll('.department-row');
        departmentRows.forEach(row => {
            row.addEventListener('click', function (e) {
                // Don't trigger expand if user clicked on a form or action button
                if (e.target.closest('form, button, input, a')) {
                    return;
                }

                const deptId = this.dataset.departmentId;
                const memberRow = document.querySelector(`.members-row-${deptId}`);
                
                if (!memberRow) return;

                const isExpanded = this.classList.contains('expanded');

                // Close all other expanded rows
                departmentRows.forEach(otherRow => {
                    const otherDeptId = otherRow.dataset.departmentId;
                    const otherMemberRow = document.querySelector(`.members-row-${otherDeptId}`);
                    
                    if (otherRow !== this && otherRow.classList.contains('expanded')) {
                        otherRow.classList.remove('expanded');
                        if (otherMemberRow) {
                            otherMemberRow.style.display = 'none';
                        }
                    }
                });

                // Toggle current row
                if (isExpanded) {
                    this.classList.remove('expanded');
                    memberRow.style.display = 'none';
                } else {
                    this.classList.add('expanded');
                    memberRow.style.display = 'table-row';
                }
            });
        });
    });
</script>
@endsection
