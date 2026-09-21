<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tasks Summary Report | Hytec Power Inc.</title>
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
            font-size: 12px;
            line-height: 1.5;
            padding: 24px;
        }

        .no-print-toolbar {
            max-width: 1000px;
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
            border: none;
            transition: all 0.15s ease;
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

        .report-sheet {
            max-width: 1000px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 12px;
            padding: 36px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid var(--border);
        }

        .doc-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--primary);
            margin-bottom: 20px;
        }

        .company-brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .company-logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }

        .company-info h1 {
            font-size: 18px;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -0.3px;
        }

        .company-info span {
            font-size: 10px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .doc-meta {
            text-align: right;
            font-size: 10px;
            color: var(--text-muted);
        }

        .doc-meta strong {
            display: block;
            font-size: 13px;
            color: var(--text-main);
            margin-bottom: 2px;
        }

        /* KPI Cards */
        .kpi-row {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }

        .kpi-card {
            background: var(--bg-light);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }

        .kpi-card .val {
            font-size: 18px;
            font-weight: 800;
            color: var(--text-main);
        }

        .kpi-card .lbl {
            font-size: 10px;
            font-weight: 600;
            color: var(--text-light);
            text-transform: uppercase;
            margin-top: 2px;
        }

        /* Data Table */
        .report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin-bottom: 24px;
        }

        .report-table th {
            background: var(--bg-light);
            text-align: left;
            padding: 8px 10px;
            font-weight: 700;
            font-size: 10px;
            color: var(--text-muted);
            text-transform: uppercase;
            border-bottom: 2px solid var(--border);
        }

        .report-table td {
            padding: 9px 10px;
            border-bottom: 1px solid var(--border);
            vertical-align: middle;
        }

        .report-table tr:nth-child(even) {
            background: #fafbfc;
        }

        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-completed { background: rgba(22,163,74,0.12); color: #16a34a; }
        .badge-inprogress { background: rgba(37,99,235,0.12); color: #2563eb; }
        .badge-pending { background: rgba(245,158,11,0.12); color: #d97706; }
        .badge-overdue { background: rgba(220,38,38,0.12); color: #dc2626; }

        .progress-bar-mini {
            width: 70px;
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
            display: inline-block;
            vertical-align: middle;
            margin-right: 6px;
        }

        .progress-fill-mini {
            height: 100%;
            border-radius: 3px;
        }

        .doc-footer {
            margin-top: 24px;
            text-align: center;
            font-size: 10px;
            color: var(--text-light);
            border-top: 1px solid var(--border);
            padding-top: 12px;
        }

        @media print {
            body { background: #ffffff; padding: 0; }
            .no-print-toolbar { display: none !important; }
            .report-sheet { max-width: 100%; box-shadow: none; border: none; padding: 10px 0; }
            @page { size: A4 landscape; margin: 12mm; }
        }
    </style>
</head>
<body>

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

    <div class="report-sheet">
        <div class="doc-header">
            <div class="company-brand">
                <img src="{{ asset_versioned('logo.jpg') }}" alt="Hytec Power Inc." class="company-logo" onerror="this.style.display='none'">
                <div class="company-info">
                    <h1>HYTEC POWER INC.</h1>
                    <span>Task Directory &amp; Performance Summary</span>
                </div>
            </div>

            <div class="doc-meta">
                <strong>EXECUTIVE SUMMARY REPORT</strong>
                <div>Generated: {{ date('F d, Y • h:i A') }}</div>
                <div>User Role: {{ $isAdmin ? 'Administrator' : 'Field Application Engineer' }}</div>
            </div>
        </div>

        <div class="kpi-row">
            <div class="kpi-card">
                <div class="val">{{ $totalTasks }}</div>
                <div class="lbl">Total Tasks</div>
            </div>
            <div class="kpi-card">
                <div class="val" style="color:#16a34a;">{{ $completedTasks }}</div>
                <div class="lbl">Completed</div>
            </div>
            <div class="kpi-card">
                <div class="val" style="color:#2563eb;">{{ $inProgressTasks }}</div>
                <div class="lbl">In Progress</div>
            </div>
            <div class="kpi-card">
                <div class="val" style="color:#d97706;">{{ $pendingTasks }}</div>
                <div class="lbl">Pending</div>
            </div>
            <div class="kpi-card">
                <div class="val" style="color:#dc2626;">{{ $overdueTasks }}</div>
                <div class="lbl">Overdue</div>
            </div>
        </div>

        <table class="report-table">
            <thead>
                <tr>
                    <th style="width: 35px;">#</th>
                    <th>Task Name</th>
                    <th>Assigned Contact</th>
                    <th>Region</th>
                    <th>Course</th>
                    <th>Priority</th>
                    <th>Deadline</th>
                    <th>Status</th>
                    <th style="text-align:right;">Progress</th>
                </tr>
            </thead>
            <tbody>
                @forelse($tasksList as $idx => $t)
                    @php
                        $rawStatus = $t->status ?? 'Pending';
                        $normStatus = strtolower(str_replace(' ', '', $rawStatus));
                        $badgeClass = 'badge-' . $normStatus;
                        $pct = (int)($t->progress ?? 0);
                        $bgPct = $pct <= 25 ? '#ef4444' : ($pct <= 50 ? '#f59e0b' : ($pct <= 75 ? '#eab308' : '#16a34a'));
                    @endphp
                    <tr>
                        <td style="color:var(--text-light); font-weight:600;">{{ $idx + 1 }}</td>
                        <td>
                            <strong>{{ $t->task_name }}</strong>
                        </td>
                        <td>{{ $t->fae->name ?? 'Unassigned' }}</td>
                        <td>{{ $t->region ?? '—' }}</td>
                        <td>{{ $t->course ?? '—' }}</td>
                        <td>
                            <span style="font-weight:600;">{{ $t->priority ?? 'Medium' }}</span>
                        </td>
                        <td>{{ $t->deadline ? $t->deadline->format('M d, Y') : '—' }}</td>
                        <td>
                            <span class="badge {{ $badgeClass }}">{{ $rawStatus }}</span>
                        </td>
                        <td style="text-align:right; white-space:nowrap;">
                            <div class="progress-bar-mini">
                                <div class="progress-fill-mini" style="width:{{ $pct }}%; background:{{ $bgPct }};"></div>
                            </div>
                            <span style="font-weight:700;">{{ $pct }}%</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align:center; padding: 20px; color: var(--text-light);">
                            No task records found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="doc-footer">
            <span>Hytec Power Inc. • Monitoring &amp; Field Application Engineering Management System</span>
        </div>
    </div>

</body>
</html>
