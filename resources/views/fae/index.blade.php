@extends('layouts.app')

@section('title', 'Contacts Management')
@section('breadcrumb', 'Contacts')

@section('content')
<section class="content">

    <div class="page-intro">
        <div>
            <span class="welcome-label">TEAM &amp; ACCESS MANAGEMENT</span>
            <h1>Contacts Management</h1>
            <p>Manage approved contacts, generate access codes, and review pending registrations.</p>
        </div>
        <div style="display:flex; gap:10px; align-items:center;">
            <a href="{{ route('tasks.index') }}" class="outline-button" style="text-decoration:none;">View Tasks</a>
        </div>
    </div>

    {{-- Navigation Tabs --}}
    <div style="display:flex; gap:8px; margin-bottom:20px; border-bottom:2px solid #e2e8f0; padding-bottom:2px;">
        <button type="button" class="tab-btn active" id="tabBtnActive" onclick="switchTab('active')" style="display:inline-flex; align-items:center; gap:8px; padding:10px 18px; font-size:13px; font-weight:700; border:none; background:none; cursor:pointer; color:#0f172a; border-bottom:3px solid var(--primary, #b52f32); margin-bottom:-5px; transition:all 0.15s ease;">
            <span>Active Contacts</span>
            <span style="background:#f1f5f9; color:#475569; font-size:11px; font-weight:800; padding:2px 8px; border-radius:12px; border:1px solid #cbd5e1;">{{ count($faeList) }}</span>
        </button>
        <button type="button" class="tab-btn" id="tabBtnPending" onclick="switchTab('pending')" style="display:inline-flex; align-items:center; gap:8px; padding:10px 18px; font-size:13px; font-weight:700; border:none; background:none; cursor:pointer; color:#64748b; border-bottom:3px solid transparent; margin-bottom:-5px; transition:all 0.15s ease;">
            <span>Pending Registrations</span>
            @if(count($pendingList) > 0)
                <span style="background:#ef4444; color:#ffffff; font-size:11px; font-weight:800; padding:2px 8px; border-radius:12px;">{{ count($pendingList) }}</span>
            @else
                <span style="background:#f1f5f9; color:#94a3b8; font-size:11px; font-weight:800; padding:2px 8px; border-radius:12px;">0</span>
            @endif
        </button>
    </div>

    {{-- TAB 1: ACTIVE CONTACTS --}}
    <div id="tabContentActive">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2>Active Contacts ({{ count($faeList) }})</h2>
                    <p>Field application engineers and approved team members with Access Codes</p>
                </div>
            </div>

            <div class="fae-grid">
                @forelse($faeList as $fae)
                    @php
                        $avgProgress = round($fae->avg_progress);
                    @endphp
                    <article class="fae-card">
                        <div>
                            <div class="fae-card-top">
                                <div class="fae-avatar-box" style="background:var(--primary);color:white;">
                                    @if(!empty($fae->profile_image))
                                        <img class="fae-profile-image" src="{{ \App\Services\UploadService::url($fae->profile_image) }}" alt="{{ $fae->name }} profile picture">
                                    @else
                                        {{ strtoupper(substr($fae->name, 0, 2)) }}
                                    @endif
                                </div>
                                <div class="fae-info">
                                    <h3>{{ $fae->name }}</h3>
                                    <span class="fae-code-badge">{{ $fae->fae_code }}</span>
                                    <div class="fae-dept">{{ $fae->department_name }}</div>
                                    @if($fae->email)
                                        <div style="font-size:11px; color:var(--text-light); margin-top:2px; word-break:break-all;">
                                            ✉ {{ $fae->email }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="fae-stats-row">
                                <div class="fae-stat-col">
                                    <span>Tasks</span>
                                    <strong>{{ (int)$fae->total_tasks }}</strong>
                                </div>
                                <div class="fae-stat-col">
                                    <span>Completed</span>
                                    <strong style="color:var(--green);">{{ (int)$fae->completed_tasks }}</strong>
                                </div>
                            </div>

                            <div class="fae-progress-bar-wrap">
                                <div class="fae-progress-label">
                                    <span>Average progress</span>
                                    <strong>{{ $avgProgress }}%</strong>
                                </div>
                                <div class="progress-bar">
                                    @php $faeBarClass = $avgProgress <= 25 ? 'progress-red' : ($avgProgress <= 50 ? 'progress-orange' : ($avgProgress <= 75 ? 'progress-gold' : 'progress-green')); @endphp
                                    <div class="{{ $faeBarClass }}" style="width:{{ $avgProgress }}%;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="fae-card-actions">
                            <a href="{{ route('tasks.index', ['assign_fae' => $fae->id]) }}" class="btn-add-task-fae" style="text-decoration:none;">
                                ＋ Add task
                            </a>
                            <a href="{{ route('fae.link', ['code' => $fae->fae_code]) }}" class="outline-button" target="_blank" rel="noopener" style="text-decoration:none;">
                                Direct link
                            </a>
                            <form method="POST" action="{{ route('fae.destroy', $fae->id) }}" onsubmit="return confirm('Remove this Contact? Assigned tasks will become unassigned.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger-outline btn-sm">Remove</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div style="grid-column: 1 / -1; text-align:center; padding:36px; color:var(--text-light);">
                        <p>No active contacts registered yet. New contacts will appear here once approved from the Pending Registrations tab.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- TAB 2: PENDING REGISTRATIONS --}}
    <div id="tabContentPending" style="display:none;">
        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2>Pending Registrations ({{ count($pendingList) }})</h2>
                    <p>Review self-registration requests from new users. Approving will generate and email their Access Code.</p>
                </div>
            </div>

            @if(count($pendingList) > 0)
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; font-size:13px; text-align:left;">
                        <thead>
                            <tr style="background:#f8fafc; border-bottom:2px solid #e2e8f0; color:#475569; font-size:11px; text-transform:uppercase; letter-spacing:0.5px;">
                                <th style="padding:12px 16px;">Applicant</th>
                                <th style="padding:12px 16px;">Email &amp; Phone</th>
                                <th style="padding:12px 16px;">Department</th>
                                <th style="padding:12px 16px;">Registered On</th>
                                <th style="padding:12px 16px; text-align:right;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pendingList as $pending)
                                <tr style="border-bottom:1px solid #e2e8f0; transition:background 0.15s ease;">
                                    <td style="padding:14px 16px;">
                                        <div style="display:flex; align-items:center; gap:10px;">
                                            <div style="width:36px; height:36px; border-radius:50%; background:#e0e7ff; color:#3730a3; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:13px;">
                                                {{ strtoupper(substr($pending->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <strong style="color:#0f172a; font-size:13px; display:block;">{{ $pending->name }}</strong>
                                                <span style="font-size:11px; color:#94a3b8;">ID: #{{ $pending->id }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="padding:14px 16px;">
                                        <div style="color:#0f172a; font-weight:600;">{{ $pending->email ?: '—' }}</div>
                                        @if($pending->phone)
                                            <div style="font-size:11px; color:#64748b; margin-top:2px;">📞 {{ $pending->phone }}</div>
                                        @endif
                                    </td>
                                    <td style="padding:14px 16px;">
                                        @if($pending->department)
                                            <span style="display:inline-block; padding:3px 10px; background:#f1f5f9; color:#334155; border-radius:6px; font-size:11px; font-weight:600; border:1px solid #e2e8f0;">
                                                {{ $pending->department->department_name }}
                                            </span>
                                        @else
                                            <span style="color:#94a3b8; font-size:12px;">Unassigned</span>
                                        @endif
                                    </td>
                                    <td style="padding:14px 16px; font-size:12px; color:#64748b;">
                                        <div>{{ $pending->created_at ? $pending->created_at->format('M d, Y') : '—' }}</div>
                                        <div style="font-size:10px; color:#94a3b8;">{{ $pending->created_at ? $pending->created_at->diffForHumans() : '' }}</div>
                                    </td>
                                    <td style="padding:14px 16px; text-align:right;">
                                        <div style="display:inline-flex; gap:8px; align-items:center;">
                                            {{-- Approve Form --}}
                                            <form method="POST" action="{{ route('fae.approve', $pending->id) }}" onsubmit="return confirm('Approve registration for {{ addslashes($pending->name) }}? An Access Code will be generated and emailed.');">
                                                @csrf
                                                <button type="submit" style="display:inline-flex; align-items:center; gap:5px; padding:7px 14px; background:#059669; color:#ffffff; border:none; border-radius:6px; font-size:12px; font-weight:700; cursor:pointer; transition:all 0.15s ease; box-shadow:0 1px 3px rgba(5,150,105,0.2);">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                                                    Approve &amp; Send Code
                                                </button>
                                            </form>

                                            {{-- Reject Form --}}
                                            <form method="POST" action="{{ route('fae.reject', $pending->id) }}" onsubmit="return confirm('Reject registration for {{ addslashes($pending->name) }}?');">
                                                @csrf
                                                <button type="submit" style="display:inline-flex; align-items:center; gap:4px; padding:7px 12px; background:#ffffff; color:#dc2626; border:1px solid #fca5a5; border-radius:6px; font-size:12px; font-weight:600; cursor:pointer; transition:all 0.15s ease;">
                                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
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
                <div style="text-align:center; padding:48px 24px; color:#64748b;">
                    <div style="width:56px; height:56px; border-radius:50%; background:#f0fdf4; color:#16a34a; display:inline-flex; align-items:center; justify-content:center; margin-bottom:12px;">
                        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <h3 style="margin:0 0 6px 0; font-size:16px; color:#0f172a; font-weight:700;">No Pending Registrations</h3>
                    <p style="margin:0; font-size:13px; color:#94a3b8;">All registration requests have been reviewed and approved.</p>
                </div>
            @endif
        </div>
    </div>

    <footer>
        <span>Monitoring System © 2026</span>
        <span>Contacts management</span>
    </footer>
</section>
@endsection

@push('scripts')
<script>
    function switchTab(tab) {
        const tabBtnActive = document.getElementById('tabBtnActive');
        const tabBtnPending = document.getElementById('tabBtnPending');
        const contentActive = document.getElementById('tabContentActive');
        const contentPending = document.getElementById('tabContentPending');

        if (tab === 'pending') {
            tabBtnActive.style.color = '#64748b';
            tabBtnActive.style.borderBottomColor = 'transparent';
            tabBtnPending.style.color = '#0f172a';
            tabBtnPending.style.borderBottomColor = 'var(--primary, #b52f32)';
            contentActive.style.display = 'none';
            contentPending.style.display = 'block';
            window.location.hash = 'pending';
        } else {
            tabBtnActive.style.color = '#0f172a';
            tabBtnActive.style.borderBottomColor = 'var(--primary, #b52f32)';
            tabBtnPending.style.color = '#64748b';
            tabBtnPending.style.borderBottomColor = 'transparent';
            contentActive.style.display = 'block';
            contentPending.style.display = 'none';
            window.location.hash = 'active';
        }
    }

    // Check hash on load
    if (window.location.hash === '#pending' || {{ count($pendingList) > 0 && count($faeList) === 0 ? 'true' : 'false' }}) {
        switchTab('pending');
    }
</script>
@endpush
