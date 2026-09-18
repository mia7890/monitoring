<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Task Report #{{ $task->id }} - {{ $task->task_name }} | Hytec Power Inc.</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #b52f32;
            --primary-dark: #8c2426;
            --text-main: #0f172a;
            --text-muted: #475569;
            --text-light: #64748b;
            --border: #e2e8f0;
            --bg-light: #f8fafc;
            --bg-card: #ffffff;
            --green: #16a34a;
            --blue: #2563eb;
            --orange: #f59e0b;
            --red: #dc2626;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            background-color: #f1f5f9;
            color: var(--text-main);
            font-size: 13px;
            line-height: 1.5;
            padding: 24px;
        }

        /* Top Action Bar for screen view */
        .no-print-toolbar {
            max-width: 850px;
            margin: 0 auto 20px auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #ffffff;
            padding: 12px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid var(--border);
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s ease;
            border: none;
        }

        .btn-print {
            background: var(--primary);
            color: #ffffff;
        }

        .btn-print:hover {
            background: var(--primary-dark);
        }

        .btn-back {
            background: #f1f5f9;
            color: var(--text-muted);
            border: 1px solid var(--border);
        }

        .btn-back:hover {
            background: #e2e8f0;
            color: var(--text-main);
        }

        /* Printable Document Sheet */
        .report-sheet {
            max-width: 850px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid var(--border);
        }

        /* Document Header */
        .doc-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding-bottom: 24px;
            border-bottom: 2px solid #b52f32;
            margin-bottom: 24px;
        }

        .company-brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .company-logo {
            width: 54px;
            height: 54px;
            object-fit: contain;
        }

        .company-info h1 {
            font-size: 20px;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.3px;
            line-height: 1.1;
        }

        .company-info span {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .doc-meta {
            text-align: right;
            font-size: 11px;
            color: var(--text-muted);
        }

        .doc-meta strong {
            display: block;
            font-size: 13px;
            color: var(--text-main);
            margin-bottom: 2px;
        }

        /* Task Title Banner */
        .task-hero {
            background: var(--bg-light);
            border: 1px solid var(--border);
            border-left: 4px solid var(--primary);
            border-radius: 8px;
            padding: 16px 20px;
            margin-bottom: 24px;
        }

        .task-hero-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 6px;
        }

        .task-hero h2 {
            font-size: 18px;
            font-weight: 700;
            color: var(--text-main);
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge-completed { background: rgba(22,163,74,0.12); color: #16a34a; border: 1px solid rgba(22,163,74,0.3); }
        .badge-inprogress { background: rgba(37,99,235,0.12); color: #2563eb; border: 1px solid rgba(37,99,235,0.3); }
        .badge-pending { background: rgba(245,158,11,0.12); color: #d97706; border: 1px solid rgba(245,158,11,0.3); }
        .badge-overdue { background: rgba(220,38,38,0.12); color: #dc2626; border: 1px solid rgba(220,38,38,0.3); }

        .priority-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .priority-urgent { background: #fee2e2; color: #991b1b; }
        .priority-high { background: #ffedd5; color: #9a3412; }
        .priority-medium { background: #fef3c7; color: #92400e; }
        .priority-low { background: #f1f5f9; color: #475569; }

        /* Meta Grid */
        .grid-meta {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            background: #ffffff;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }

        .grid-meta-item span {
            font-size: 10px;
            font-weight: 600;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 4px;
        }

        .grid-meta-item strong {
            font-size: 13px;
            color: var(--text-main);
            font-weight: 600;
        }

        /* Progress Bar */
        .progress-wrap {
            margin-top: 10px;
        }

        .progress-track {
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 4px;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
        }

        /* Section Headings */
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: var(--text-main);
            border-bottom: 1px solid var(--border);
            padding-bottom: 8px;
            margin-bottom: 14px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .description-box {
            background: var(--bg-light);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 14px;
            font-size: 12px;
            color: var(--text-muted);
            line-height: 1.6;
            margin-bottom: 24px;
            white-space: pre-line;
        }

        /* Timeline / Updates Table */
        .timeline-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 30px;
        }

        .timeline-table th {
            background: var(--bg-light);
            text-align: left;
            padding: 10px 12px;
            font-weight: 700;
            font-size: 11px;
            color: var(--text-muted);
            text-transform: uppercase;
            border-bottom: 2px solid var(--border);
        }

        .timeline-table td {
            padding: 12px;
            border-bottom: 1px solid var(--border);
            vertical-align: top;
        }

        .timeline-table tr:nth-child(even) {
            background: #fafbfc;
        }

        /* Sign-off signatures */
        .signoff-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
            padding-top: 24px;
            border-top: 1px dashed var(--border);
        }

        .signoff-box {
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 16px;
            background: var(--bg-light);
        }

        .signoff-line {
            height: 48px;
            border-bottom: 1px solid #94a3b8;
            margin-bottom: 8px;
        }

        .signoff-label {
            font-size: 11px;
            font-weight: 600;
            color: var(--text-muted);
            text-align: center;
        }

        /* Footer */
        .doc-footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: var(--text-light);
            border-top: 1px solid var(--border);
            padding-top: 14px;
        }

        /* PRINT STYLES */
        @media print {
            body {
                background: #ffffff;
                padding: 0;
            }
            .no-print-toolbar {
                display: none !important;
            }
            .report-sheet {
                max-width: 100%;
                box-shadow: none;
                border: none;
                padding: 20px 0;
            }
            @page {
                size: A4 portrait;
                margin: 15mm;
            }
        }
    </style>
</head>
<body>

    <!-- ACTION TOOLBAR (HIDDEN ON PRINT) -->
    <div class="no-print-toolbar">
        <a href="{{ route('tasks.index') }}" class="btn-action btn-back">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
            Back to Tasks
        </a>

        <button type="button" class="btn-action btn-print" onclick="window.print()">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
            Print / Save as PDF
        </button>
    </div>

    <!-- PRINTABLE REPORT SHEET -->
    <div class="report-sheet">
        <!-- HEADER -->
        <div class="doc-header">
            <div class="company-brand">
                <img src="{{ asset('logo.png') }}" alt="Hytec Power Inc." class="company-logo" onerror="this.style.display='none'">
                <div class="company-info">
                    <h1>HYTEC POWER INC.</h1>
                    <span>Field Application Engineering Monitoring System</span>
                </div>
            </div>

            <div class="doc-meta">
                <strong>OFFICIAL TASK REPORT</strong>
                <div>Ref ID: #TASK-{{ str_pad($task->id, 5, '0', STR_PAD_LEFT) }}</div>
                <div>Generated: {{ date('F d, Y • h:i A') }}</div>
            </div>
        </div>

        @php
            $rawStatus = $task->status ?? 'Pending';
            $normStatus = strtolower(str_replace(' ', '', $rawStatus));
            $badgeClass = 'badge-' . $normStatus;
            $priority = $task->priority ?? 'Medium';
            $pClass = 'priority-' . strtolower($priority);
            $progressPct = (int)($task->progress ?? 0);
            $progressBg = $progressPct <= 25 ? '#ef4444' : ($progressPct <= 50 ? '#f59e0b' : ($progressPct <= 75 ? '#eab308' : '#16a34a'));
            $isOverdue = (!empty($task->deadline) && $task->deadline->format('Y-m-d') < date('Y-m-d') && $rawStatus !== 'Completed');
        @endphp

        <!-- TASK TITLE HERO -->
        <div class="task-hero">
            <div class="task-hero-top">
                <h2>{{ $task->task_name }}</h2>
                <div>
                    <span class="priority-badge {{ $pClass }}">{{ $priority }} Priority</span>
                    <span class="badge {{ $badgeClass }}">{{ $rawStatus }}</span>
                </div>
            </div>
            <div style="font-size: 11px; color: var(--text-muted);">
                Assigned to: <strong>{{ $task->fae->name ?? 'Unassigned' }}</strong> 
                @if($task->fae && $task->fae->fae_code)
                    ({{ $task->fae->fae_code }})
                @endif
                @if($task->fae && $task->fae->department)
                    • Department: <strong>{{ $task->fae->department->name }}</strong>
                @endif
            </div>
        </div>

        <!-- METADATA GRID -->
        <div class="grid-meta">
            <div class="grid-meta-item">
                <span>Assigned Contact</span>
                <strong>{{ $task->fae->name ?? 'Unassigned' }}</strong>
            </div>
            <div class="grid-meta-item">
                <span>Region</span>
                <strong>{{ $task->region ?? 'N/A' }}</strong>
            </div>
            <div class="grid-meta-item">
                <span>Course / Module</span>
                <strong>{{ $task->course ?? 'N/A' }}</strong>
            </div>
            <div class="grid-meta-item">
                <span>Deadline</span>
                <strong style="{{ $isOverdue ? 'color:var(--red); font-weight:700;' : '' }}">
                    {{ $task->deadline ? $task->deadline->format('M d, Y') : 'None specified' }}
                    @if($isOverdue) (Overdue) @endif
                </strong>
            </div>
        </div>

        <!-- PROGRESS BAR -->
        <div style="background: #ffffff; border: 1px solid var(--border); border-radius: 8px; padding: 14px 16px; margin-bottom: 24px;">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <span style="font-size:11px; font-weight:700; color:var(--text-muted); text-transform:uppercase;">Overall Execution Progress</span>
                <strong style="font-size:14px; font-weight:800; color:{{ $progressBg }}">{{ $progressPct }}% Completed</strong>
            </div>
            <div class="progress-track">
                <div class="progress-fill" style="width: {{ $progressPct }}%; background: {{ $progressBg }};"></div>
            </div>
        </div>

        <!-- INSTRUCTIONS / SCOPE -->
        @if(!empty($task->description))
            <div class="section-title">Task Scope &amp; Instructions</div>
            <div class="description-box">{{ $task->description }}</div>
        @endif

        <!-- WORK REPORTS & ACTIVITY TIMELINE -->
        <div class="section-title">Work Reports &amp; Field Execution Log ({{ $task->updates->count() }} Entries)</div>
        
        @if($task->updates->isEmpty())
            <div style="text-align:center; padding: 24px; color: var(--text-light); font-size:12px; border: 1px dashed var(--border); border-radius: 8px; margin-bottom: 24px;">
                No field progress reports or supervisor notes recorded yet for this task.
            </div>
        @else
            <table class="timeline-table">
                <thead>
                    <tr>
                        <th style="width: 140px;">Date &amp; Time</th>
                        <th style="width: 130px;">Author</th>
                        <th>Work Accomplished / Remarks</th>
                        <th style="width: 90px; text-align:right;">Progress</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($task->updates->sortByDesc('created_at') as $update)
                        @php
                            $isAdminAuthor = $update->author_role === 'admin';
                        @endphp
                        <tr>
                            <td style="color:var(--text-light); font-size:11px;">
                                {{ $update->created_at ? $update->created_at->format('M d, Y • h:i A') : 'N/A' }}
                            </td>
                            <td>
                                <strong>{{ $update->author_name }}</strong>
                                <small style="display:block; font-size:9px; font-weight:700; color:{{ $isAdminAuthor ? '#dc2626' : '#2563eb' }}; text-transform:uppercase;">
                                    {{ $isAdminAuthor ? 'Supervisor' : 'FAE' }}
                                </small>
                            </td>
                            <td>
                                <div style="line-height:1.5; color:var(--text-main);">
                                    {!! nl2br(e($update->message)) !!}
                                </div>
                                @if(!empty($update->attachments_list))
                                    <div style="margin-top:8px; display:flex; flex-wrap:wrap; gap:8px; align-items:flex-start;">
                                        @foreach($update->attachments_list as $att)
                                            @php
                                                $ext = strtolower(pathinfo($att, PATHINFO_EXTENSION));
                                                $isImg = in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true);
                                                $fileUrl = \App\Services\UploadService::url($att);
                                            @endphp
                                            @if($isImg)
                                                <div style="display:inline-block; text-align:center; border:1px solid #e2e8f0; border-radius:6px; padding:4px; background:#f8fafc;">
                                                    <img src="{{ $fileUrl }}" alt="Attachment" style="max-height:100px; max-width:160px; object-fit:contain; border-radius:4px; display:block; margin:0 auto;">
                                                    <span style="font-size:9px; color:var(--text-light); display:block; margin-top:2px; max-width:150px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">{{ basename($att) }}</span>
                                                </div>
                                            @else
                                                <a href="{{ $fileUrl }}" target="_blank" style="display:inline-flex; align-items:center; gap:4px; font-size:10px; color:var(--primary); font-weight:600; text-decoration:none; padding:4px 8px; border:1px solid #e2e8f0; border-radius:6px; background:#f8fafc;">
                                                    📎 {{ basename($att) }}
                                                </a>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td style="text-align:right; font-weight:700; color:var(--text-main);">
                                @if($update->progress_at_update !== null)
                                    {{ (int)$update->progress_at_update }}%
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <!-- SIGNOFF SECTION -->
        <div class="signoff-grid">
            <div class="signoff-box">
                <div class="signoff-line"></div>
                <div class="signoff-label">
                    <strong>{{ $task->fae->name ?? 'Assigned Field Application Engineer' }}</strong><br>
                    <span>Field Engineer Signature &amp; Date</span>
                </div>
            </div>

            <div class="signoff-box">
                <div class="signoff-line"></div>
                <div class="signoff-label">
                    <strong>Technical Operations Supervisor</strong><br>
                    <span>Management Approval &amp; Date</span>
                </div>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="doc-footer">
            <span>Hytec Power Inc. • Monitoring &amp; Field Engineering Management System • Confidential Report</span>
        </div>
    </div>

</body>
</html>
