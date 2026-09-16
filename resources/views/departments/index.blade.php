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
        <a href="{{ route('fae.index') }}" class="outline-button" style="text-decoration:none;">Back to FAE</a>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <h2>Add Department</h2>
                <p>Create a new department entry.</p>
            </div>
        </div>

        <form method="POST" action="{{ route('departments.store') }}" class="fae-add-form" style="display:grid; grid-template-columns: 1.4fr 150px auto; gap:12px; align-items:center;">
            @csrf
            <input class="form-control" name="department_name" placeholder="Department name" required>
            <label style="display:flex; align-items:center; gap:8px; font-size:13px; color:var(--text-light); margin:0;">
                <input type="checkbox" name="is_active" value="1" checked>
                Active
            </label>
            <button class="primary-button btn-sm" type="submit">＋ Add department</button>
        </form>
    </div>

    <div class="panel">
        <div class="panel-header">
            <div>
                <h2>Departments</h2>
                <p>{{ $departments->count() }} department entries</p>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table-style">
                <thead>
                    <tr>
                        <th style="cursor:pointer; user-select:none;">Department</th>
                        <th>Status</th>
                        <th>FAEs</th>
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
                                <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                                    <form method="POST" action="{{ route('departments.update', $department->id) }}" style="display:flex; gap:8px; align-items:center;" onclick="event.stopPropagation();">
                                        @csrf
                                        @method('PUT')
                                        <input type="text" name="department_name" value="{{ $department->department_name }}" class="form-control" required style="min-width:160px;">
                                        <label style="display:flex; align-items:center; gap:6px; font-size:12px; margin:0;">
                                            <input type="checkbox" name="is_active" value="1" {{ $department->is_active ? 'checked' : '' }}>
                                            Active
                                        </label>
                                        <button type="submit" class="outline-button btn-sm">Save</button>
                                    </form>

                                    <form method="POST" action="{{ route('departments.destroy', $department->id) }}" onsubmit="return confirm('Delete this department?');" onclick="event.stopPropagation();">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger-outline btn-sm">Delete</button>
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
                                                            <span style="background:#e0f2fe; color:#0369a1; font-size:10px; font-weight:700; padding:1px 6px; border-radius:4px; flex-shrink:0;">{{ $fae->fae_code }}</span>
                                                        </div>
                                                        @php
                                                            $displayEmail = $fae->email ?: $fae->google_email;
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

    <footer>
        <span>Monitoring System © 2026</span>
        <span>Department management</span>
    </footer>
</section>

<!-- JavaScript for Department Member Expansion/Collapse -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const departmentRows = document.querySelectorAll('.department-row');

        departmentRows.forEach(row => {
            row.addEventListener('click', function (e) {
                // Don't trigger expand if user clicked on a form or action button
                if (e.target.closest('form, button, input')) {
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
