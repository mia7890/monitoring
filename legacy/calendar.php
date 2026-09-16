<?php
require_once 'db.php';
require_once 'auth.php';
requireAccess();

$isAdmin = isAdmin();
$currentFaeId = currentFaeId();
$roleParam = '';
$notificationItems = notificationItems($pdo, $isAdmin, $currentFaeId);
if (!$isAdmin && $currentFaeId && empty($_SESSION['monitoring_fae_name'])) {
    $nameStmt = $pdo->prepare('SELECT name, fae_code FROM fae_users WHERE id = ?');
    $nameStmt->execute([$currentFaeId]);
    $faeProfile = $nameStmt->fetch();
    $_SESSION['monitoring_fae_name'] = (string)($faeProfile['name'] ?? 'FAE User');
    $_SESSION['monitoring_fae_code'] = (string)($faeProfile['fae_code'] ?? '');
}

// ── Month navigation ──────────────────────────────────────────────────────────
$month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('n');
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : (int)date('Y');

$today = date('Y-m-d');

if ($month < 1)  { $month = 12; $year--; }
if ($month > 12) { $month = 1;  $year++; }

$prevMonth = $month - 1; $prevYear = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }

$nextMonth = $month + 1; $nextYear = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

// ── Messages from GET ─────────────────────────────────────────────────────────
$formMessage = '';
$formError   = false;
if (isset($_GET['msg'])) {
    $formMessage = $_GET['msg'];
}
if (isset($_GET['err'])) {
    $formMessage = $_GET['err'];
    $formError   = true;
}

// ── Handle appointment form submission ────────────────────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['book_appointment'])) {
    if ($isAdmin) {
        header("Location: calendar.php?err=" . urlencode('Admin users cannot request appointments.'));
        exit();
    }
    $userName = $isAdmin ? 'Administrator' : ($_SESSION['monitoring_fae_name'] ?? 'FAE');
    if (!$isAdmin && empty($_SESSION['monitoring_fae_name'])) {
        $nameStmt = $pdo->prepare('SELECT name FROM fae_users WHERE id = ?');
        $nameStmt->execute([$currentFaeId]);
        $_SESSION['monitoring_fae_name'] = (string)$nameStmt->fetchColumn();
        $userName = $_SESSION['monitoring_fae_name'];
    }
    $apptDate = trim($_POST['appointment_date'] ?? '');
    $reason   = trim($_POST['reason'] ?? '');

    if ($userName && $apptDate && $reason) {
        if ($apptDate < date('Y-m-d')) {
            header("Location: calendar.php?err=" . urlencode('Past dates cannot be used for appointments.'));
            exit();
        }
        // Check if date is already booked (pending or accepted)
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND status IN ('pending', 'accepted')");
        $checkStmt->execute([$apptDate]);
        $alreadyBooked = $checkStmt->fetchColumn() > 0;

        $busyStmt = $pdo->prepare("SELECT COUNT(*) FROM admin_events WHERE event_date = ? AND category = 'busy'");
        $busyStmt->execute([$apptDate]);
        $adminBusy = $busyStmt->fetchColumn() > 0;

        if ($alreadyBooked || (!$isAdmin && $adminBusy)) {
            $bookingError = $adminBusy && !$isAdmin
                ? 'The admin is unavailable on this date. Please choose another date.'
                : 'This date is already booked! Please select another date.';
            header("Location: calendar.php?month={$month}&year={$year}" . ($isAdmin ? '&role=admin' : '') . "&err=" . urlencode($bookingError));
            exit();
        } else {
            $stmt = $pdo->prepare("INSERT INTO appointments (fae_id, user_name, appointment_date, reason) VALUES (?, ?, ?, ?)");
            $stmt->execute([$currentFaeId, $userName, $apptDate, $reason]);
            header("Location: calendar.php?month={$month}&year={$year}" . ($isAdmin ? '&role=admin' : '') . "&msg=" . urlencode('Appointment request submitted successfully!'));
            exit();
        }
    } else {
        header("Location: calendar.php?month={$month}&year={$year}" . ($isAdmin ? '&role=admin' : '') . "&err=" . urlencode('Please fill in all fields.'));
        exit();
    }
}

// ── Handle Accept/Reject Actions ──────────────────────────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['update_status'])) {
    if (!$isAdmin) {
        header("Location: calendar.php?month={$month}&year={$year}&err=" . urlencode('Unauthorized: Admin access required.'));
        exit();
    }
    $apptId = (int)$_POST['appt_id'];
    $newStatus = $_POST['status']; // 'accepted' or 'rejected'
    $adminComment = isset($_POST['admin_comment']) ? trim($_POST['admin_comment']) : null;

    if (in_array($newStatus, ['accepted', 'rejected'])) {
        if ($newStatus === 'accepted') {
            $dateStmt = $pdo->prepare("SELECT appointment_date FROM appointments WHERE id = ?");
            $dateStmt->execute([$apptId]);
            $apptDate = $dateStmt->fetchColumn();

            if ($apptDate) {
                $stmt = $pdo->prepare("UPDATE appointments SET status = 'accepted', admin_comment = NULL WHERE id = ?");
                $stmt->execute([$apptId]);

                $rejectOthers = $pdo->prepare("UPDATE appointments SET status = 'rejected', admin_comment = 'Date booked by another user' WHERE appointment_date = ? AND id != ? AND status = 'pending'");
                $rejectOthers->execute([$apptDate, $apptId]);
                
                header("Location: calendar.php?month={$month}&year={$year}&role=admin&msg=" . urlencode("Appointment accepted! Other requests on this day were automatically rejected."));
                exit();
            }
        } else {
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'rejected', admin_comment = ? WHERE id = ?");
            $stmt->execute([$adminComment, $apptId]);
            
            header("Location: calendar.php?month={$month}&year={$year}&role=admin&msg=" . urlencode("Appointment rejected with comment."));
            exit();
        }
    }
}

// ── Handle Add Admin Event ───────────────────────────────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['add_admin_event'])) {
    if (!$isAdmin) {
        header("Location: calendar.php?month={$month}&year={$year}&err=" . urlencode('Unauthorized: Admin access required.'));
        exit();
    }
    $evTitle    = trim($_POST['event_title'] ?? '');
    $evDate     = trim($_POST['event_date'] ?? '');
    $evDesc     = trim($_POST['event_description'] ?? '');
    $evCategory = trim($_POST['event_category'] ?? 'other');

    if ($evTitle && $evDate) {
        $stmt = $pdo->prepare("INSERT INTO admin_events (title, event_date, description, category) VALUES (?, ?, ?, ?)");
        $stmt->execute([$evTitle, $evDate, $evDesc ?: null, $evCategory]);
        header("Location: calendar.php?month={$month}&year={$year}&role=admin&msg=" . urlencode('Upcoming event added successfully!'));
        exit();
    } else {
        header("Location: calendar.php?month={$month}&year={$year}&role=admin&err=" . urlencode('Event title and date are required.'));
        exit();
    }
}

// ── Handle Delete Admin Event ────────────────────────────────────────────────
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['delete_admin_event'])) {
    if (!$isAdmin) {
        header("Location: calendar.php?month={$month}&year={$year}&err=" . urlencode('Unauthorized: Admin access required.'));
        exit();
    }
    $evId = (int)($_POST['event_id'] ?? 0);
    if ($evId > 0) {
        $stmt = $pdo->prepare("DELETE FROM admin_events WHERE id = ?");
        $stmt->execute([$evId]);
        header("Location: calendar.php?month={$month}&year={$year}&role=admin&msg=" . urlencode('Event deleted successfully.'));
        exit();
    }
}

// ── Fetch task deadlines for this month ──────────────────────────────────────
$taskStmt = $pdo->prepare("
    SELECT t.task_name, t.deadline, t.status, t.progress,
           f.name AS fae_name, f.fae_code
    FROM tasks t
    LEFT JOIN fae_users f ON t.fae_id = f.id
        WHERE MONTH(t.deadline) = ? AND YEAR(t.deadline) = ?
            AND (? = 1 OR t.fae_id = ?)
    ORDER BY t.deadline ASC
");
$taskStmt->execute([$month, $year, $isAdmin ? 1 : 0, $currentFaeId]);
$monthTasks = $taskStmt->fetchAll();

$tasksByDay = [];
foreach ($monthTasks as $task) {
    $day = (int)date('j', strtotime($task['deadline']));
    $tasksByDay[$day][] = $task;
}

// ── Fetch appointments for this month ────────────────────────────────────────
$apptStmt = $pdo->prepare("
    SELECT * FROM appointments
        WHERE MONTH(appointment_date) = ? AND YEAR(appointment_date) = ?
            AND (? = 1 OR fae_id = ?)
    ORDER BY appointment_date ASC
");
$apptStmt->execute([$month, $year, $isAdmin ? 1 : 0, $currentFaeId]);
$monthAppts = $apptStmt->fetchAll();

$apptsByDay = [];
$bookedDays = [];
foreach ($monthAppts as $appt) {
    $day = (int)date('j', strtotime($appt['appointment_date']));
    $apptsByDay[$day][] = $appt;
    if (in_array($appt['status'], ['pending', 'accepted'])) {
        $bookedDays[$day] = $appt;
    }
}

// ── Fetch admin events for this month ────────────────────────────────────────
$adminEvtStmt = $pdo->prepare("
    SELECT * FROM admin_events
    WHERE MONTH(event_date) = ? AND YEAR(event_date) = ?
    ORDER BY event_date ASC
");
$adminEvtStmt->execute([$month, $year]);
$monthAdminEvents = $adminEvtStmt->fetchAll();

$adminEventsByDay = [];
$busyDays = [];
foreach ($monthAdminEvents as $evt) {
    $day = (int)date('j', strtotime($evt['event_date']));
    $adminEventsByDay[$day][] = $evt;
    if (($evt['category'] ?? '') === 'busy') {
        $busyDays[$day] = true;
        $bookedDays[$day] = ['status' => 'busy'];
    }
}

// ── Calendar grid maths ─────────────────────────────────────────────────────
$firstDayRaw = (int)date('w', mktime(0, 0, 0, $month, 1, $year));
$daysInMonth = (int)date('t', mktime(0, 0, 0, $month, 1, $year));
$today = date('Y-m-d');
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/monitoring'));
$appBase = rtrim($scriptDir, '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?= $appBase ? $appBase . '/' : '/' ?>">
    <title>Calendar &amp; Schedule | Monitoring System</title>
    <meta name="description" content="View task deadlines and manage appointments on the interactive calendar.">

    <link rel="stylesheet" href="<?= $appBase ?>/style.css?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= $appBase ?>/calendar.css?v=<?= time() ?>">

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>

<body>
<div class="app">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <div class="logo-icon"><img src="logo.png" alt="Monitoring System logo"></div>
            <div class="logo-text">
                <h2>Hytec Power inc.</h2>
                <span>Monitoring</span>
            </div>
        </div>

        <nav class="navigation">
            <a href="index.php<?= $roleParam ?>" class="nav-item">
                <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span>
                <span>Dashboard</span>
            </a>
            <?php if ($isAdmin): ?>
                <a href="fae.php" class="nav-item">
                    <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg></span>
                    <span>FAE</span>
                </a>
            <?php endif; ?>
            <a href="tasks.php<?= $roleParam ?>" class="nav-item">
                <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg></span>
                <span>Tasks</span>
            </a>
            <a href="calendar.php?month=<?= $month ?>&year=<?= $year ?><?= $isAdmin ? '&role=admin' : '' ?>" class="nav-item active">
                <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span>
                <span>Calendar</span>
            </a>
        </nav>

        <div class="sidebar-bottom">
            <div class="sidebar-footer">
                <span>© 2026 Hytec Power Inc.</span>
            </div>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main">

        <!-- TOPBAR -->
        <header class="topbar">
            <button class="menu-toggle" id="menuToggle">☰</button>
            <div class="breadcrumb">
                <a href="index.php<?= $roleParam ?>" style="color:inherit; text-decoration:none;">Home</a>
                <b>/</b>
                <strong>Calendar</strong>
            </div>
            <div class="topbar-right">
                <?php if ($isAdmin): ?>
                    <a href="access.php?logout=1" class="role-tag admin" style="text-decoration:none;" title="Sign out of the admin workspace">
                        Sign out
                    </a>
                <?php else: ?>
                    <a href="access.php?logout=1" class="role-tag" style="text-decoration:none;" title="Sign out of the FAE workspace">
                        Sign out
                    </a>
                <?php endif; ?>

                <?= notificationMarkup($notificationItems) ?>
                <div class="profile">
                    <div class="avatar"><?= $isAdmin ? 'AD' : htmlspecialchars(strtoupper(substr(currentFaeName(), 0, 2))) ?></div>
                    <div class="profile-info">
                        <strong><?= $isAdmin ? 'Administrator' : htmlspecialchars(currentFaeName()) ?></strong>
                        <span><?= $isAdmin ? 'Full Management' : htmlspecialchars(currentFaeCode()) ?></span>
                    </div>
                </div>
            </div>
        </header>

        <!-- CONTENT -->
        <section class="content">

            <!-- PAGE INTRO -->
            <div class="page-intro">
                <div>
                    <span class="welcome-label">SCHEDULE</span>
                    <h1>Calendar &amp; Events</h1>
                    <p>View task deadlines, appointments and manage upcoming activities.</p>
                </div>
                <div class="date-box">
                    <span>Today</span>
                    <strong><?= date('F d, Y') ?></strong>
                </div>
            </div>

            <!-- GLOBAL MESSAGES -->
            <?php if ($formMessage): ?>
                <div class="form-msg <?= $formError ? 'form-msg--error' : 'form-msg--success' ?>" style="margin-bottom: 20px;">
                    <?= htmlspecialchars($formMessage) ?>
                </div>
            <?php endif; ?>

            <!-- CALENDAR LAYOUT -->
            <div class="cal-layout">

                <!-- LEFT: Main Calendar -->
                <div class="cal-main">
                    <div class="panel cal-panel">

                        <!-- Month header -->
                        <div class="cal-header">
                            <a href="calendar.php?month=<?= $prevMonth ?>&year=<?= $prevYear ?><?= $isAdmin ? '&role=admin' : '' ?>"
                               class="cal-nav-btn" title="Previous month">&#8249;</a>

                            <div class="cal-title">
                                <h2><?= date('F', mktime(0,0,0,$month,1,$year)) ?></h2>
                                <span><?= $year ?></span>
                            </div>

                            <div style="display: flex; align-items: center; gap: 8px;">
                                <?php if ($isAdmin): ?>
                                    <button type="button" class="btn-add-event" id="openAddEventModalBtn" title="Add upcoming busy schedule or event">
                                        ＋ Add Event
                                    </button>
                                <?php endif; ?>
                                <a href="calendar.php?month=<?= $nextMonth ?>&year=<?= $nextYear ?><?= $isAdmin ? '&role=admin' : '' ?>"
                                   class="cal-nav-btn" title="Next month">&#8250;</a>
                            </div>
                        </div>

                        <!-- Day labels -->
                        <div class="cal-day-labels">
                            <?php foreach (['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $dl): ?>
                                <div class="cal-day-label"><?= $dl ?></div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Grid -->
                        <div class="cal-grid">
                            <?php for ($i = 0; $i < $firstDayRaw; $i++): ?>
                                <div class="cal-cell cal-cell--empty"></div>
                            <?php endfor; ?>

                            <?php for ($day = 1; $day <= $daysInMonth; $day++):
                                $dateStr        = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                $isToday        = ($dateStr === $today);
                                $isPast         = ($dateStr < $today);
                                $hasTasks       = !empty($tasksByDay[$day]);
                                $hasAppts       = !empty($apptsByDay[$day]);
                                $hasAdminEvents = !empty($adminEventsByDay[$day]);
                                $isBooked       = isset($bookedDays[$day]);
                                $isAccepted     = $isBooked && (($bookedDays[$day]['status'] ?? '') === 'accepted');

                                $cls = 'cal-cell';
                                if ($isToday)               $cls .= ' cal-cell--today';
                                if ($isPast)                $cls .= ' cal-cell--past';
                                if ($hasTasks || $hasAppts || $hasAdminEvents) $cls .= ' cal-cell--has-events';
                                if ($isBooked)              $cls .= $isAccepted ? ' cal-cell--accepted' : ' cal-cell--booked';
                            ?>
                                <div class="<?= $cls ?>"
                                     data-date="<?= $dateStr ?>"
                                     data-day="<?= $day ?>"
                                     data-booked="<?= $isBooked ? '1' : '0' ?>"
                                     data-busy="<?= isset($busyDays[$day]) ? '1' : '0' ?>">

                                    <span class="cal-day-num"><?= $day ?></span>

                                    <?php if ($hasAdminEvents): ?>
                                        <div class="cal-events">
                                            <?php foreach (array_slice($adminEventsByDay[$day], 0, 2) as $ae):
                                                $catIcon = match($ae['category'] ?? 'other') {
                                                    'meeting'  => '👥',
                                                    'busy'     => '⛔',
                                                    'reminder' => '🔔',
                                                    default    => '📌'
                                                };
                                                $catClass = 'ev-admin-' . ($ae['category'] ?? 'other');
                                            ?>
                                                <div class="cal-event <?= $catClass ?> ev-admin"
                                                     title="Event: <?= htmlspecialchars($ae['title']) ?>">
                                                    <?= $catIcon ?> <?= htmlspecialchars(mb_strimwidth($ae['title'], 0, 12, '...')) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($hasTasks): ?>
                                        <div class="cal-events">
                                            <?php foreach (array_slice($tasksByDay[$day], 0, 2) as $t):
                                                $sc = strtolower(str_replace(' ', '', $t['status']));
                                                $ec = ($sc === 'inprogress') ? 'ev-progress' : 'ev-' . $sc;
                                            ?>
                                                <div class="cal-event <?= $ec ?>"
                                                     title="Task: <?= htmlspecialchars($t['task_name']) ?> (<?= $t['progress'] ?>%)">
                                                    ✓ <?= htmlspecialchars(mb_strimwidth($t['task_name'], 0, 12, '...')) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($hasAppts): ?>
                                        <div class="cal-events">
                                            <?php foreach (array_slice($apptsByDay[$day], 0, 2) as $a):
                                                $apptClass = $a['status'] === 'rejected' ? 'ev-overdue' : ($a['status'] === 'accepted' ? 'ev-completed' : 'ev-appt');
                                            ?>
                                                <div class="cal-event <?= $apptClass ?>"
                                                     title="Appointment: <?= htmlspecialchars($a['reason']) ?> (<?= $a['status'] ?>)">
                                                    📅 <?= htmlspecialchars(mb_strimwidth($a['reason'], 0, 12, '...')) ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                </div>
                            <?php endfor; ?>
                        </div>

                    </div>
                </div>

                <!-- RIGHT: Appointment Sidebar & Tables -->
                <div class="cal-side">
                    <!-- Calendar Guide / Legend -->
                    <?php if (!$isAdmin): ?>
                    <div class="panel" style="padding:16px 20px;">
                        <h3 style="font-size:13px; font-weight:700; margin-bottom:8px;">💡 How to Book</h3>
                        <p style="font-size:12px; color:var(--text-light); line-height:1.5; margin-bottom:12px;">
                            Click directly on any available date on the calendar grid to request an appointment.
                        </p>
                        <div style="display:flex; flex-direction:column; gap:6px; font-size:11px;">
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:var(--primary);"></span>
                                <span>Tasks Due</span>
                            </div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#8b5cf6;"></span>
                                <span>Appointments</span>
                            </div>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#ef4444;"></span>
                                <span>Booked / Unavailable</span>
                            </div>
                        </div>
                        <div class="google-tools">
                            <strong>Google tools</strong>
                            <span>Accepted appointments can be added to Google Calendar. Google Drive needs account authorization, so it is available as a manual workspace link.</span>
                            <a href="https://drive.google.com/drive/my-drive" target="_blank" rel="noopener">Open Google Drive</a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Admin Events List -->
                    <?php if (!empty($monthAdminEvents)): ?>
                        <div class="panel" style="padding:16px 20px;">
                            <h3 style="font-size:13px; font-weight:700; margin-bottom:10px;">Events This Month</h3>
                            <div style="display:flex; flex-direction:column; gap:8px;">
                                <?php foreach ($monthAdminEvents as $ev): ?>
                                    <div style="display:flex; justify-content:space-between; align-items:center; background:#f8fafc; padding:8px 10px; border-radius:7px; border:1px solid var(--border);">
                                        <div>
                                            <strong style="font-size:12px; display:block;"><?= htmlspecialchars($ev['title']) ?></strong>
                                            <small style="color:var(--text-light); font-size:10px;"><?= date('M d, Y', strtotime($ev['event_date'])) ?> &bull; <?= ucfirst($ev['category']) ?></small>
                                        </div>
                                        <?php if ($isAdmin): ?>
                                            <form method="POST" action="calendar.php?month=<?= $month ?>&year=<?= $year ?>&role=admin" onsubmit="return confirm('Delete this event?');">
                                                <input type="hidden" name="delete_admin_event" value="1">
                                                <input type="hidden" name="event_id" value="<?= $ev['id'] ?>">
                                                <button type="submit" style="background:none; border:none; color:var(--red); cursor:pointer; font-size:13px;">🗑️</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

            </div>

            <!-- APPOINTMENTS TABLE -->
            <?php if (!empty($monthAppts)): ?>
                <div class="panel" style="margin-top:20px;">
                    <div class="panel-header">
                        <div>
                            <h2>Month Appointments</h2>
                            <p>All appointment requests for <?= date('F Y', mktime(0,0,0,$month,1,$year)) ?></p>
                        </div>
                    </div>

                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>Applicant</th>
                                    <th>Date</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Admin Notes</th>
                                    <th>Calendar</th>
                                    <?php if ($isAdmin): ?>
                                        <th style="text-align:right;">Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($monthAppts as $ap): 
                                    $st = $ap['status'];
                                    $bCls = $st === 'accepted' ? 'complete-badge' : ($st === 'rejected' ? 'overdue-badge' : 'pending-badge');
                                    $googleDate = date('Ymd', strtotime($ap['appointment_date']));
                                    $googleEnd = date('Ymd', strtotime($ap['appointment_date'] . ' +1 day'));
                                    $googleUrl = 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' . urlencode('Meeting with Admin') . '&dates=' . $googleDate . '/' . $googleEnd . '&details=' . urlencode($ap['reason']);
                                ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($ap['user_name']) ?></strong></td>
                                    <td><?= date('M d, Y', strtotime($ap['appointment_date'])) ?></td>
                                    <td><?= htmlspecialchars($ap['reason']) ?></td>
                                    <td><span class="badge <?= $bCls ?>"><?= ucfirst($st) ?></span></td>
                                    <td><small style="color:var(--text-light);"><?= htmlspecialchars($ap['admin_comment'] ?? '—') ?></small></td>
                                    <td><a class="google-calendar-link" href="<?= htmlspecialchars($googleUrl) ?>" target="_blank" rel="noopener">Google Calendar</a></td>
                                    <?php if ($isAdmin): ?>
                                        <td style="text-align:right;">
                                            <?php if ($st === 'pending'): ?>
                                                <form method="POST" action="calendar.php?month=<?= $month ?>&year=<?= $year ?>&role=admin" style="display:inline;">
                                                    <input type="hidden" name="update_status" value="1">
                                                    <input type="hidden" name="appt_id" value="<?= $ap['id'] ?>">
                                                    <input type="hidden" name="status" value="accepted">
                                                    <button type="submit" class="tbl-action-btn tbl-btn-accept">Accept</button>
                                                </form>

                                                <button type="button" class="tbl-action-btn tbl-btn-reject btn-trigger-reject" data-id="<?= $ap['id'] ?>">
                                                    Reject
                                                </button>
                                            <?php else: ?>
                                                <small style="color:#9ca3af;">Handled</small>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <!-- FOOTER -->
            <footer>
                <span>Monitoring System © 2026</span>
                <span>Interactive Calendar Live</span>
            </footer>

        </section>
    </main>
</div>

<!-- =========================================================
     MODALS
     ========================================================= -->

<!-- Day Detail Modal -->
<div class="cal-modal-overlay" id="calModalOverlay">
    <div class="cal-modal">
        <div class="cal-modal-header">
            <h3 id="calModalTitle">Date Details</h3>
            <button type="button" class="cal-modal-close" id="calModalClose">&times;</button>
        </div>
        <div class="cal-modal-body" id="calModalBody"></div>
    </div>
</div>

<!-- Add Admin Event Modal -->
<div class="custom-modal-overlay" id="addEventModalOverlay">
    <div class="custom-modal">
        <div class="custom-modal-header">
            <h3><span>📅</span> Add Event / Busy Schedule</h3>
            <button type="button" class="custom-modal-close" id="addEventModalClose">&times;</button>
        </div>
        <form method="POST" action="calendar.php?month=<?= $month ?>&year=<?= $year ?>&role=admin">
            <input type="hidden" name="add_admin_event" value="1">
            <div class="custom-modal-body">
                <div class="form-group">
                    <label class="form-label">Event Title <span class="req">*</span></label>
                    <input type="text" name="event_title" class="form-control" placeholder="e.g. System Deployment / Executive Meeting" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Start Date <span class="req">*</span></label>
                        <input type="date" name="event_date" id="addEventModalDate" class="form-control" required value="<?= $today ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">End Date</label>
                        <input type="date" name="end_date" id="addEventModalEndDate" class="form-control" value="<?= $today ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <select name="event_category" class="form-select">
                            <option value="meeting">👥 Meeting</option>
                            <option value="busy">⛔ Busy / Unavailable</option>
                            <option value="reminder">🔔 Reminder</option>
                            <option value="other" selected>📌 Event</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description / Notes</label>
                    <textarea name="event_description" class="form-control" rows="2" placeholder="Optional notes..."></textarea>
                </div>
            </div>
            <div class="custom-modal-footer">
                <button type="button" class="secondary-button btn-sm" id="cancelEventBtn">Cancel</button>
                <button type="submit" class="primary-button btn-sm">Save Event</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div class="custom-modal-overlay" id="rejectModalOverlay">
    <div class="custom-modal" style="max-width:420px;">
        <div class="custom-modal-header">
            <h3 style="color:var(--red);"><span>⛔</span> Reject Appointment</h3>
            <button type="button" class="custom-modal-close" id="rejectModalClose">&times;</button>
        </div>
        <form method="POST" action="calendar.php?month=<?= $month ?>&year=<?= $year ?>&role=admin">
            <input type="hidden" name="update_status" value="1">
            <input type="hidden" name="status" value="rejected">
            <input type="hidden" name="appt_id" id="rejectApptId" value="">
            <div class="custom-modal-body">
                <div class="form-group">
                    <label class="form-label">Reason / Comment for Rejection</label>
                    <textarea name="admin_comment" class="form-control" rows="3" placeholder="Explain why the request was declined..."></textarea>
                </div>
            </div>
            <div class="custom-modal-footer">
                <button type="button" class="secondary-button btn-sm" id="cancelRejectBtn">Cancel</button>
                <button type="submit" class="btn-danger-outline btn-sm">Confirm Rejection</button>
            </div>
        </form>
    </div>
</div>

<?php if (!$isAdmin): ?>
<!-- Booking Modal (Triggered by clicking open date) -->
<div class="custom-modal-overlay" id="bookModalOverlay">
    <div class="custom-modal">
        <div class="custom-modal-header">
            <h3 id="bookModalTitle">Request Appointment</h3>
            <button type="button" class="custom-modal-close" id="bookModalClose">&times;</button>
        </div>
        <form method="POST" action="calendar.php?month=<?= $month ?>&year=<?= $year ?><?= $isAdmin ? '&role=admin' : '' ?>">
            <input type="hidden" name="book_appointment" value="1">
            <div class="custom-modal-body">
                <?php if (!$isAdmin): ?><p class="booking-identity">Requesting as <strong><?= htmlspecialchars($_SESSION['monitoring_fae_name'] ?? 'your FAE profile') ?></strong></p><?php endif; ?>
                <div class="form-group">
                    <label class="form-label">Selected Date</label>
                    <input type="date" name="appointment_date" id="bookModalDate" class="form-control" required readonly style="background:#f8fafc;">
                </div>
                <div class="form-group">
                    <label class="form-label">Discussion Topic <span class="req">*</span></label>
                    <textarea name="reason" class="form-control" rows="3" required placeholder="Describe what you want to consult..."></textarea>
                </div>
            </div>
            <div class="custom-modal-footer">
                <button type="button" class="secondary-button btn-sm" id="cancelBookBtn">Cancel</button>
                <button type="submit" class="primary-button btn-sm">Submit Request</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- =========================================================
     JAVASCRIPT
     ========================================================= -->
<script>
var tasksData = <?= json_encode($tasksByDay) ?>;
var apptsData = <?= json_encode($apptsByDay) ?>;
var adminEventsData = <?= json_encode($adminEventsByDay) ?>;
var isAdmin = <?= $isAdmin ? 'true' : 'false' ?>;
var todayStr = "<?= $today ?>";

var menuToggle = document.getElementById("menuToggle");
var sidebar = document.getElementById("sidebar");
if (menuToggle && sidebar) {
    menuToggle.addEventListener("click", function () { sidebar.classList.toggle("open"); });
}

var calOverlay = document.getElementById("calModalOverlay");
var calModalTitle = document.getElementById("calModalTitle");
var calModalBody = document.getElementById("calModalBody");
var calModalClose = document.getElementById("calModalClose");

var addEventOverlay = document.getElementById("addEventModalOverlay");
var openAddEventBtn = document.getElementById("openAddEventModalBtn");
var addEventClose = document.getElementById("addEventModalClose");
var cancelEventBtn = document.getElementById("cancelEventBtn");
var addEventModalDate = document.getElementById("addEventModalDate");

var bookOverlay = document.getElementById("bookModalOverlay");
var openBookBtn = document.getElementById("openBookModalBtn");
var bookModalClose = document.getElementById("bookModalClose");
var bookModalDate = document.getElementById("bookModalDate");
var cancelBookBtn = document.getElementById("cancelBookBtn");

var rejectOverlay = document.getElementById("rejectModalOverlay");
var rejectModalClose = document.getElementById("rejectModalClose");
var cancelRejectBtn = document.getElementById("cancelRejectBtn");
var rejectApptId = document.getElementById("rejectApptId");

function escHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function openModal(overlay) {
    if (overlay) {
        overlay.classList.add("active");
        overlay.classList.add("open");
    }
}

function closeAllModals() {
    [calOverlay, addEventOverlay, bookOverlay, rejectOverlay].forEach(function(overlay) {
        if (overlay) {
            overlay.classList.remove("active");
            overlay.classList.remove("open");
        }
    });
}

if (calModalClose) calModalClose.addEventListener("click", closeAllModals);
if (addEventClose) addEventClose.addEventListener("click", closeAllModals);
if (cancelEventBtn) cancelEventBtn.addEventListener("click", closeAllModals);
if (bookModalClose) bookModalClose.addEventListener("click", closeAllModals);
if (cancelBookBtn) cancelBookBtn.addEventListener("click", closeAllModals);
if (rejectModalClose) rejectModalClose.addEventListener("click", closeAllModals);
if (cancelRejectBtn) cancelRejectBtn.addEventListener("click", closeAllModals);

if (openAddEventBtn) {
    openAddEventBtn.addEventListener("click", function() {
        openModal(addEventOverlay);
    });
}

if (openBookBtn) {
    openBookBtn.addEventListener("click", function() {
        if (bookModalDate && !bookModalDate.value) {
            bookModalDate.value = todayStr;
        }
        openModal(bookOverlay);
    });
}

document.querySelectorAll(".btn-trigger-reject").forEach(function(btn) {
    btn.addEventListener("click", function() {
        var apptId = this.getAttribute("data-id");
        if (rejectApptId) rejectApptId.value = apptId;
        openModal(rejectOverlay);
    });
});

[calOverlay, addEventOverlay, bookOverlay, rejectOverlay].forEach(function(overlay) {
    if (overlay) {
        overlay.addEventListener("click", function(e) {
            if (e.target === overlay) closeAllModals();
        });
    }
});

// Click cell to open appointment modal or event details
document.querySelectorAll(".cal-cell:not(.cal-cell--empty)").forEach(function(cell) {
    cell.addEventListener("click", function(e) {
        var dateStr = this.getAttribute("data-date");
        var dayNum = parseInt(this.getAttribute("data-day"), 10);
        
        var dayTasks = tasksData[dayNum] || [];
        var dayAppts = apptsData[dayNum] || [];
        var dayAdminEvents = adminEventsData[dayNum] || [];

        // For non-admin users, clicking a date cell opens the Appointment Booking modal
        if (!isAdmin && bookOverlay && bookModalDate) {
            bookModalDate.value = dateStr;
            openModal(bookOverlay);
            return;
        }

        // For admin users, clicking a date cell sets the date in Add Event modal and shows details
        if (isAdmin && addEventModalDate) {
            addEventModalDate.value = dateStr;
        }

        // Open details modal
        if (calOverlay && calModalBody && calModalTitle) {
            calModalTitle.textContent = "Details for " + dateStr;
            var html = "";

            if (dayAdminEvents.length > 0) {
                html += '<div style="margin-bottom:14px;"><strong>📅 Events & Busy Schedules:</strong><ul style="margin:6px 0 0 18px; padding:0; font-size:13px; color:var(--text-main);">';
                dayAdminEvents.forEach(function(ev) {
                    html += '<li style="margin-bottom:4px;"><strong>' + escHtml(ev.title) + '</strong> (' + escHtml(ev.category) + ')' + (ev.description ? ' - ' + escHtml(ev.description) : '') + '</li>';
                });
                html += '</ul></div>';
            }

            if (dayTasks.length > 0) {
                html += '<div style="margin-bottom:14px;"><strong>✓ Tasks Due:</strong><ul style="margin:6px 0 0 18px; padding:0; font-size:13px; color:var(--text-main);">';
                dayTasks.forEach(function(t) {
                    html += '<li style="margin-bottom:4px;"><strong>' + escHtml(t.task_name) + '</strong> - Progress: ' + t.progress + '% (' + escHtml(t.status) + ')</li>';
                });
                html += '</ul></div>';
            }

            if (dayAppts.length > 0) {
                html += '<div style="margin-bottom:14px;"><strong>📋 Appointments:</strong><ul style="margin:6px 0 0 18px; padding:0; font-size:13px; color:var(--text-main);">';
                dayAppts.forEach(function(a) {
                    html += '<li style="margin-bottom:4px;"><strong>' + escHtml(a.user_name || 'User') + '</strong>: ' + escHtml(a.reason) + ' [<span style="text-transform:capitalize;">' + escHtml(a.status) + '</span>]</li>';
                });
                html += '</ul></div>';
            }

            if (dayAdminEvents.length === 0 && dayTasks.length === 0 && dayAppts.length === 0) {
                html = '<p style="color:var(--text-light); margin:0 0 12px; font-size:13px;">No tasks, events, or appointments scheduled for this date.</p>';
            }

            if (!isAdmin) {
                html += '<div style="margin-top:16px; padding-top:12px; border-top:1px solid var(--border); text-align:right;">' +
                        '<button type="button" class="primary-button btn-sm" onclick="closeAllModals(); if(bookModalDate) bookModalDate.value=\'' + dateStr + '\'; openModal(bookOverlay);">' +
                        '📅 Request Appointment for ' + dateStr + '</button></div>';
            } else {
                html += '<div style="margin-top:16px; padding-top:12px; border-top:1px solid var(--border); text-align:right;">' +
                        '<button type="button" class="primary-button btn-sm" onclick="closeAllModals(); if(addEventModalDate) addEventModalDate.value=\'' + dateStr + '\'; openModal(addEventOverlay);">' +
                        '＋ Add Event on ' + dateStr + '</button></div>';
            }

            calModalBody.innerHTML = html;
            openModal(calOverlay);
        }
    });
});
</script>

</body>
</html>
