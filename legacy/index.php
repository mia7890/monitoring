<?php
require_once 'db.php';
require_once 'auth.php';
requireAccess();

$isAdmin = isAdmin();
$currentFaeId = currentFaeId();
$roleParam = '';
$notificationItems = notificationItems($pdo, $isAdmin, $currentFaeId);
$profileImage = null;
if (!$isAdmin && $currentFaeId) {
    $nameStmt = $pdo->prepare('SELECT name, fae_code, profile_image FROM fae_users WHERE id = ?');
    $nameStmt->execute([$currentFaeId]);
    $faeProfile = $nameStmt->fetch();
    if (empty($_SESSION['monitoring_fae_name'])) {
        $_SESSION['monitoring_fae_name'] = (string)($faeProfile['name'] ?? 'FAE User');
        $_SESSION['monitoring_fae_code'] = (string)($faeProfile['fae_code'] ?? '');
    }
    $profileImage = $faeProfile['profile_image'] ?? null;
}

// Fetch live counts for the current workspace.
$faeScope = $isAdmin ? ' WHERE 1 = 1' : ' WHERE id = :fae_id';
$taskScope = $isAdmin ? ' WHERE 1 = 1' : ' WHERE fae_id = :fae_id';
$countParams = $isAdmin ? [] : ['fae_id' => $currentFaeId];
$countQuery = function (string $sql) use ($pdo, $countParams): int {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($countParams);
    return (int)$stmt->fetchColumn();
};
$totalFAE = $countQuery("SELECT COUNT(*) FROM fae_users$faeScope");
$totalTasks = $countQuery("SELECT COUNT(*) FROM tasks$taskScope");
$completedTasks = $countQuery("SELECT COUNT(*) FROM tasks$taskScope AND status = 'Completed'");
$inProgressTasks = $countQuery("SELECT COUNT(*) FROM tasks$taskScope AND status = 'In Progress'");
$pendingTasks = $countQuery("SELECT COUNT(*) FROM tasks$taskScope AND status = 'Pending'");
$overdueTasks = $countQuery("SELECT COUNT(*) FROM tasks$taskScope AND status = 'Overdue'");

// Fetch Live Tasks for the Table
$stmt = $pdo->prepare("
    SELECT
        tasks.*,
        fae_users.name,
        fae_users.fae_code,
        (SELECT COUNT(*) FROM task_updates tu WHERE tu.task_id = tasks.id) as update_count
    FROM tasks
    LEFT JOIN fae_users 
        ON tasks.fae_id = fae_users.id
    WHERE :is_admin = 1 OR tasks.fae_id = :fae_id
    ORDER BY tasks.id DESC
");
$stmt->execute(['is_admin' => $isAdmin ? 1 : 0, 'fae_id' => $currentFaeId]);
$tasks = $stmt->fetchAll();

// Fetch Upcoming To-Do (Next 7 Days)
if ($isAdmin) {
    $upcomingStmt = $pdo->query("
        SELECT 'task' as type, task_name as title, deadline as date, 'task' as category 
        FROM tasks 
        WHERE deadline >= CURDATE() AND deadline <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status != 'Completed'
        UNION ALL
        SELECT 'appointment' as type, reason as title, appointment_date as date, status as category 
        FROM appointments 
        WHERE appointment_date >= CURDATE() AND appointment_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status = 'pending'
        UNION ALL
        SELECT 'event' as type, title as title, event_date as date, category as category 
        FROM admin_events 
        WHERE event_date >= CURDATE() AND event_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ORDER BY date ASC
        LIMIT 6
    ");
    $upcomingItems = $upcomingStmt->fetchAll();
} else {
    $upcomingStmt = $pdo->prepare("
        SELECT 'task' as type, task_name as title, deadline as date, 'task' as category 
        FROM tasks 
        WHERE deadline >= CURDATE() AND deadline <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status != 'Completed' AND fae_id = ?
        UNION ALL
        SELECT 'appointment' as type, reason as title, appointment_date as date, status as category 
        FROM appointments 
        WHERE appointment_date >= CURDATE() AND appointment_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND status = 'accepted' AND fae_id = ?
        UNION ALL
        SELECT 'event' as type, title as title, event_date as date, category as category 
        FROM admin_events 
        WHERE event_date >= CURDATE() AND event_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        ORDER BY date ASC
        LIMIT 6
    ");
    $upcomingStmt->execute([$currentFaeId, $currentFaeId]);
    $upcomingItems = $upcomingStmt->fetchAll();
}
$appBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/monitoring')), '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?= $appBase ?>/">
    <title>Monitoring System | Dashboard</title>

    <link rel="stylesheet" href="<?= $appBase ?>/style.css?v=<?= time() ?>">

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<div class="app">

    <!-- ================= SIDEBAR ================= -->
    <aside class="sidebar" id="sidebar">

        <div class="logo">
            <div class="logo-icon">
                <img src="logo.png" alt="Monitoring System logo">
            </div>

            <div class="logo-text">
                <h2>Hytec Power inc.</h2>
                <span>Monitoring</span>
            </div>
        </div>

        <nav class="navigation">

            <a href="index.php<?= $roleParam ?>" class="nav-item active">
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

            <a href="calendar.php<?= $roleParam ?>" class="nav-item">
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


    <!-- ================= MAIN CONTENT ================= -->
    <main class="main">

        <!-- TOP HEADER -->
        <header class="topbar">

            <button class="menu-toggle" id="menuToggle">
                ☰
            </button>

            <div class="breadcrumb">
                <span>Home</span>
                <b>/</b>
                <strong>Dashboard</strong>
            </div>

            <div class="topbar-right">

                <!-- Role Badge (Admin Only) -->
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
                    <?php if (!$isAdmin): ?>
                        <form method="post" action="tasks.php" enctype="multipart/form-data" class="profile-picture-form">
                            <input type="hidden" name="action" value="update_profile">
                            <input id="profile_image" type="file" name="profile_image" accept="image/jpeg,image/png,image/gif,image/webp" required onchange="this.form.submit()">
                            <label for="profile_image" class="avatar profile-avatar-trigger" title="Change profile picture">
                                <?php if ($profileImage): ?><img class="profile-avatar-image" src="<?= htmlspecialchars($profileImage) ?>" alt="<?= htmlspecialchars(currentFaeName()) ?> profile picture"><?php else: ?><?= htmlspecialchars(strtoupper(substr(currentFaeName(), 0, 2))) ?><?php endif; ?>
                            </label>
                        </form>
                    <?php else: ?>
                        <div class="avatar">AD</div>
                    <?php endif; ?>

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
                    

                    <h1>Overview</h1>

                    <p>
                        Here's an overview of the current FAE and task
                        monitoring activities.
                    </p>
                </div>

                <div class="date-box">
                    <span>Today</span>
                    <strong id="currentDate"><?= date('F d, Y'); ?></strong>
                </div>

            </div>


            <!-- ================= STATISTICS ================= -->
            <div class="stats-grid">

                <div class="stat-card">

                    <div class="stat-top">
                        <div class="stat-icon blue">
                            ♙
                        </div>

                        <span class="trend positive">
                            Active
                        </span>
                    </div>

                    <div class="stat-value"><?= $totalFAE; ?></div>

                    <div class="stat-label">
                        Total FAE
                    </div>

                    <div class="stat-description">
                        Active field application engineers
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-top">
                        <div class="stat-icon purple">
                            ✓
                        </div>

                        <span class="trend positive">
                            Total
                        </span>
                    </div>

                    <div class="stat-value"><?= $totalTasks; ?></div>

                    <div class="stat-label">
                        Total Tasks
                    </div>

                    <div class="stat-description">
                        Tasks currently being monitored
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-top">
                        <div class="stat-icon green">
                            ✓
                        </div>

                        <span class="trend positive">
                            Done
                        </span>
                    </div>

                    <div class="stat-value"><?= $completedTasks; ?></div>

                    <div class="stat-label">
                        Completed
                    </div>

                    <div class="stat-description">
                        Successfully completed tasks
                    </div>

                </div>


                <div class="stat-card">

                    <div class="stat-top">
                        <div class="stat-icon orange">
                            ◷
                        </div>

                        <span class="trend neutral">
                            Ongoing
                        </span>
                    </div>

                    <div class="stat-value"><?= $inProgressTasks; ?></div>

                    <div class="stat-label">
                        In Progress
                    </div>

                    <div class="stat-description">
                        Tasks currently in progress
                    </div>

                </div>

            </div>


            <!-- ================= CHART SECTION ================= -->
            <div class="dashboard-grid">

                <div class="panel chart-panel">

                    <div class="panel-header">

                        <div>
                            <h2>Task Progress</h2>
                            <p>Overall task completion overview</p>
                        </div>

                        <select class="period-select">
                            <option>This Month</option>
                            <option>Last Month</option>
                            <option>This Year</option>
                        </select>

                    </div>

                    <div class="chart-container">
                        <canvas id="progressChart"></canvas>
                    </div>

                </div>


                <div style="display: flex; flex-direction: column; gap: 20px;">

                    <!-- UPCOMING TO-DO -->
                    <div class="panel activity-panel">
                        <div class="panel-header">
                            <div>
                                <h2>Upcoming To-Do</h2>
                                <p>Due within next 7 days</p>
                            </div>
                        </div>
                        <div class="activity-list">
                            <?php if (empty($upcomingItems)): ?>
                                <div style="text-align:center; padding: 20px; font-size: 10px; color: #6b7280;">No upcoming to-dos for this week.</div>
                            <?php else: ?>
                                <?php foreach ($upcomingItems as $item): ?>
                                    <?php
                                    $iconClass = match($item['type']) {
                                        'task'        => 'blue',
                                        'appointment' => 'purple',
                                        'event'       => ($item['category'] === 'busy' ? 'orange' : ($item['category'] === 'meeting' ? 'blue' : 'green')),
                                        default       => 'blue'
                                    };
                                    $iconSymbol = match($item['type']) {
                                        'task'        => '✓',
                                        'appointment' => '📅',
                                        'event'       => match($item['category']) {
                                            'meeting'  => '👥',
                                            'busy'     => '⛔',
                                            'reminder' => '🔔',
                                            default    => '📌'
                                        },
                                        default       => '📌'
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
                                    ?>
                                    <div class="activity">
                                        <div class="activity-icon <?= $iconClass ?>">
                                            <?= $iconSymbol ?>
                                        </div>
                                        <div class="activity-content">
                                            <strong><?= htmlspecialchars($item['title']) ?></strong>
                                            <span><?= htmlspecialchars($typeSubtitle) ?></span>
                                            <small><?= date('M d, Y', strtotime($item['date'])) ?></small>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- STATUS PANEL -->
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
                                <strong><?= $totalTasks; ?></strong>
                                <span>Total Tasks</span>
                            </div>
                        </div>
                        <div class="status-list">
                            <div class="status-item">
                                <div><span class="legend green"></span>Completed</div>
                                <strong><?= $completedTasks; ?></strong>
                            </div>
                            <div class="status-item">
                                <div><span class="legend blue"></span>In Progress</div>
                                <strong><?= $inProgressTasks; ?></strong>
                            </div>
                            <div class="status-item">
                                <div><span class="legend orange"></span>Pending</div>
                                <strong><?= $pendingTasks; ?></strong>
                            </div>
                            <div class="status-item">
                                <div><span class="legend red"></span>Overdue</div>
                                <strong><?= $overdueTasks; ?></strong>
                            </div>
                        </div>
                    </div>

                </div>

            </div>


            <!-- ================= RECENT MONITORING ================= -->
            <div class="panel monitoring-panel">

                <div class="panel-header">

                    <div>
                        <h2>Recent Monitoring</h2>
                        <p>Latest activities and task progress</p>
                    </div>

                    <a href="tasks.php<?= $roleParam ?>" class="outline-button" style="text-decoration:none;">
                        View All Tasks →
                    </a>

                </div>


                <div class="table-wrapper">

                    <table>

                        <thead>
                            <tr>
                                <th>FAE</th>
                                <th>Region</th>
                                <th>Course</th>
                                <th>Task</th>
                                <th>Deadline</th>
                                <th>Status</th>
                                <th>Progress</th>
                                <th style="text-align:right;">Action</th>
                            </tr>
                        </thead>

                        <tbody>

                            <?php if (!empty($tasks)): ?>
                                <?php foreach ($tasks as $row): 
                                    $rawStatus = $row['status'];
                                    $normalizedStatus = strtolower(str_replace(' ', '', $rawStatus));
                                    
                                    $badgeClass = ($normalizedStatus === 'inprogress') ? 'progress-badge' : $normalizedStatus . '-badge';
                                    $barClass = ($normalizedStatus === 'completed') ? 'complete' : (($normalizedStatus === 'inprogress') ? '' : $normalizedStatus);
                                    
                                    $avatarColors = ['avatar-blue', 'avatar-purple', 'avatar-orange', 'avatar-green'];
                                    $avatarColor = $avatarColors[$row['id'] % count($avatarColors)];
                                    
                                    $nameParts = explode(' ', trim($row['name'] ?? 'U A'));
                                    $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
                                    $updateCount = (int)($row['update_count'] ?? 0);
                                ?>
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <div class="small-avatar <?= $avatarColor; ?>">
                                                <?= htmlspecialchars($initials); ?>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($row['name'] ?? 'Unassigned'); ?></strong>
                                                <span><?= htmlspecialchars($row['fae_code'] ?? 'N/A'); ?></span>
                                            </div>
                                        </div>
                                    </td>

                                    <td><?= htmlspecialchars($row['region'] ?? 'N/A'); ?></td>
                                    <td><?= htmlspecialchars($row['course'] ?? 'N/A'); ?></td>
                                    <td>
                                        <a href="javascript:void(0)" class="btn-view-reports-trigger" data-id="<?= $row['id'] ?>" style="color:inherit; font-weight:600; text-decoration:none;">
                                            <?= htmlspecialchars($row['task_name'] ?? 'N/A'); ?>
                                        </a>
                                    </td>
                                    <td><?= !empty($row['deadline']) ? date('M d, Y', strtotime($row['deadline'])) : 'N/A'; ?></td>

                                    <td>
                                        <span class="badge <?= $badgeClass; ?>">
                                            <?= htmlspecialchars($rawStatus); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="progress-cell">
                                            <div class="progress-bar">
                                                <div class="<?= $barClass; ?>" style="width:<?= (int)$row['progress']; ?>%"></div>
                                            </div>
                                            <span><?= (int)$row['progress']; ?>%</span>
                                        </div>
                                    </td>

                                    <td style="text-align:right; white-space:nowrap;">
                                        <button type="button" 
                                                class="outline-button btn-view-reports-trigger btn-sm" 
                                                data-id="<?= $row['id'] ?>"
                                                title="View task reports & activity">
                                            📋 Reports <?= $updateCount > 0 ? "($updateCount)" : '' ?>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center; padding: 24px; color: #6b7280;">
                                        No task records found in the database.
                                    </td>
                                </tr>
                            <?php endif; ?>

                        </tbody>

                    </table>

                </div>

            </div>


            <!-- FOOTER -->
            <footer>
                <span>Monitoring System © 2026</span>
                <span>Dashboard Live</span>
            </footer>

        </section>

    </main>

</div>

<script>
    /* =========================================================
   MONITORING SYSTEM
   Dashboard JavaScript
   ========================================================= */

// =========================================================
// MOBILE SIDEBAR
// =========================================================

const menuToggle = document.getElementById("menuToggle");
const sidebar = document.getElementById("sidebar");

menuToggle.addEventListener("click", function () {
    sidebar.classList.toggle("open");
});


// =========================================================
// CURRENT DATE
// =========================================================

const currentDate = document.getElementById("currentDate");

const today = new Date();

const dateOptions = {
    year: "numeric",
    month: "long",
    day: "numeric"
};

currentDate.textContent = today.toLocaleDateString(
    "en-US",
    dateOptions
);


// =========================================================
// TASK PROGRESS CHART
// =========================================================

const progressCanvas = document.getElementById("progressChart");

new Chart(progressCanvas, {

    type: "line",

    data: {

        labels: [
            "Week 1",
            "Week 2",
            "Week 3",
            "Week 4",
            "Week 5",
            "Week 6"
        ],

        datasets: [

            {
                label: "Completed Tasks",

                data: [
                    18,
                    27,
                    35,
                    42,
                    48,
                    52
                ],

                borderColor: "#2563eb",

                backgroundColor:
                    "rgba(37, 99, 235, 0.08)",

                borderWidth: 2,

                fill: true,

                tension: 0.4,

                pointRadius: 3,

                pointHoverRadius: 5
            },

            {
                label: "Total Tasks",

                data: [
                    35,
                    48,
                    58,
                    67,
                    76,
                    86
                ],

                borderColor: "#d1d5db",

                borderWidth: 2,

                borderDash: [5, 5],

                fill: false,

                tension: 0.4,

                pointRadius: 2
            }

        ]

    },

    options: {

        responsive: true,

        maintainAspectRatio: false,

        plugins: {

            legend: {
                position: "bottom",

                labels: {
                    usePointStyle: true,
                    boxWidth: 6,
                    font: {
                        size: 9
                    }
                }
            }

        },

        scales: {

            y: {

                beginAtZero: true,

                grid: {
                    color: "#f1f3f6"
                },

                ticks: {
                    font: {
                        size: 9
                    },

                    color: "#9ca3af"
                }

            },

            x: {

                grid: {
                    display: false
                },

                ticks: {
                    font: {
                        size: 9
                    },

                    color: "#9ca3af"
                }

            }

        }

    }

});


// =========================================================
// TASK STATUS DONUT
// =========================================================

const statusCanvas = document.getElementById("statusChart");

new Chart(statusCanvas, {

    type: "doughnut",

    data: {

        labels: [
            "Completed",
            "In Progress",
            "Pending",
            "Overdue"
        ],

        datasets: [

            {
                data: [
                    52,
                    21,
                    10,
                    3
                ],

                backgroundColor: [
                    "#16a34a",
                    "#2563eb",
                    "#f59e0b",
                    "#dc2626"
                ],

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

            legend: {
                display: false
            },

            tooltip: {
                enabled: true
            }

        }

    }

});


// =========================================================
// NAVIGATION DEMO
// =========================================================

const navigationItems =
    document.querySelectorAll(".nav-item");

navigationItems.forEach(function (item) {

    item.addEventListener("click", function (event) {

        if (item.getAttribute("href") === "#") {
            event.preventDefault();
        }

        navigationItems.forEach(function (nav) {
            nav.classList.remove("active");
        });

        item.classList.add("active");

    });

});
</script>

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
            <?php if (!$isAdmin): ?>
            <div class="report-composer-box">
                <div class="report-composer-title">
                    <span>📝</span> Submit Work Report / Progress Note
                </div>

                <form method="POST" action="tasks.php<?= $roleParam ?>" enctype="multipart/form-data" id="taskReportForm">
                    <input type="hidden" name="action" value="add_task_update">
                    <input type="hidden" name="task_id" id="reportFormTaskId" value="">
                    <input type="hidden" name="redirect_to" value="index.php">

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
                            <span>📤</span> Post Work Report
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <div class="custom-modal-footer">
            <button type="button" class="secondary-button btn-sm" data-close="taskReportsModal">Close</button>
        </div>
    </div>
</div>

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

    function escapeHtml(text) {
        if (!text) return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr.replace(/-/g, '/'));
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    function openTaskReports(taskId) {
        const modal = document.getElementById("taskReportsModal");
        if (!modal) return;

        // Reset form & set task ID if composer form is present (FAE role)
        const formTaskId = document.getElementById("reportFormTaskId");
        if (formTaskId) {
            formTaskId.value = taskId;
            const msgEl = document.getElementById("reportFormMessage");
            if (msgEl) msgEl.value = "";
            const fileEl = document.getElementById("reportFormFile");
            if (fileEl) fileEl.value = "";
        }

        const timelineContainer = document.getElementById("reportModalTimeline");
        timelineContainer.innerHTML = '<div style="text-align:center; padding:20px; color:var(--text-secondary); font-size:11px;">Loading report timeline...</div>';

        openModal("taskReportsModal");

        fetch('tasks.php?action=get_task_timeline&task_id=' + encodeURIComponent(taskId))
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    timelineContainer.innerHTML = '<div style="text-align:center; padding:20px; color:var(--red); font-size:11px;">' + escapeHtml(data.error || 'Failed to load task details.') + '</div>';
                    return;
                }

                const task = data.task;
                const updates = data.updates || [];

                document.getElementById("reportModalTaskTitle").textContent = task.task_name;
                
                const badgesWrap = document.getElementById("reportModalTaskBadges");
                const normStatus = (task.status || 'pending').toLowerCase().replace(/\s+/g, '');
                const statusBadgeClass = normStatus === 'inprogress' ? 'progress-badge' : (normStatus + '-badge');
                const priorityClass = 'priority-' + (task.priority || 'medium').toLowerCase();
                
                badgesWrap.innerHTML = 
                    '<span class="badge ' + statusBadgeClass + '">' + escapeHtml(task.status) + '</span> ' +
                    '<span class="priority-badge ' + priorityClass + '">' + escapeHtml(task.priority || 'Medium') + '</span>';

                document.getElementById("reportModalFaeName").textContent = task.fae_name ? task.fae_name : 'Unassigned';
                document.getElementById("reportModalFaeCode").textContent = task.fae_code ? '(' + task.fae_code + ')' : '';
                document.getElementById("reportModalRegion").textContent = task.region || '—';
                document.getElementById("reportModalCourse").textContent = task.course || '—';
                document.getElementById("reportModalDeadline").textContent = task.deadline ? task.deadline : '—';

                const progress = parseInt(task.progress || 0);
                document.getElementById("reportModalProgressPct").textContent = progress + '%';
                const fill = document.getElementById("reportModalProgressFill");
                fill.style.width = progress + '%';
                fill.className = progress >= 100 ? 'complete' : (progress > 0 ? '' : 'pending');

                const descWrap = document.getElementById("reportModalDescWrap");
                const descText = document.getElementById("reportModalDescription");
                if (task.description && task.description.trim() !== '') {
                    descText.textContent = task.description;
                    descWrap.style.display = 'block';
                } else {
                    descWrap.style.display = 'none';
                }

                // Pre-fill composer values if present (FAE)
                const rangeEl = document.getElementById("reportFormProgressRange");
                if (rangeEl) {
                    rangeEl.value = progress;
                    document.getElementById("reportFormProgressVal").textContent = progress + '%';
                    document.getElementById("reportFormStatus").value = task.status || 'In Progress';
                }

                document.getElementById("reportModalUpdateCount").textContent = updates.length + (updates.length === 1 ? ' report' : ' reports');

                if (updates.length === 0) {
                    timelineContainer.innerHTML = 
                        '<div style="text-align:center; padding:24px 16px; color:var(--text-secondary); background:#fafafa; border-radius:6px; border:1px dashed var(--border); font-size:11px;">' +
                        '📋 No work reports logged yet for this task.' +
                        (data.is_admin ? '' : '<br>Use the form below to submit the first update.') +
                        '</div>';
                } else {
                    let html = '';
                    updates.forEach(function (up) {
                        const attachmentUrl = up.attachment_url || up.attachment;
                        const isImg = attachmentUrl && /\.(jpe?g|png|gif|webp|bmp)$/i.test(attachmentUrl);
                        const isDoc = attachmentUrl && !isImg;
                        const roleClass = (up.author_role || 'fae') === 'admin' ? 'admin' : 'fae';
                        const roleLabel = (up.author_role || 'fae') === 'admin' ? 'Admin' : 'FAE';

                        html += '<div class="timeline-card">';
                        html += '  <div class="timeline-card-header">';
                        html += '    <div class="timeline-author-info">';
                        html += '      <span class="timeline-author-name">' + escapeHtml(up.author_name) + '</span>';
                        html += '      <span class="author-role-badge ' + roleClass + '">' + roleLabel + '</span>';
                        html += '    </div>';
                        html += '    <span class="timeline-date">' + formatDate(up.created_at) + '</span>';
                        html += '  </div>';

                        html += '  <p class="timeline-message">' + escapeHtml(up.message) + '</p>';

                        if (attachmentUrl) {
                            html += '<div class="timeline-attachment-box">';
                            if (isImg) {
                                html += '<div style="font-size:10px; font-weight:700; color:var(--text-secondary); margin-bottom:4px;">📸 Attached Photo:</div>';
                                html += '<a href="' + escapeHtml(attachmentUrl) + '" target="_blank" title="Click to view full photo in new tab" style="display:inline-block;">';
                                html += '  <img src="' + escapeHtml(attachmentUrl) + '" alt="Report Attachment" class="timeline-attachment-img">';
                                html += '</a>';
                            } else if (isDoc) {
                                const filename = attachmentUrl.split('/').pop();
                                html += '<a href="' + escapeHtml(attachmentUrl) + '" target="_blank" class="timeline-attachment-file" download>';
                                html += '  📎 Download ' + escapeHtml(filename);
                                html += '</a>';
                            }
                            html += '</div>';
                        }

                        if (up.progress_at_update !== null || up.status_at_update) {
                            html += '<div class="timeline-meta-row">';
                            if (up.progress_at_update !== null) {
                                html += '<span><strong>Progress:</strong> ' + parseInt(up.progress_at_update) + '%</span>';
                            }
                            if (up.status_at_update) {
                                html += '<span><strong>Status:</strong> ' + escapeHtml(up.status_at_update) + '</span>';
                            }
                            html += '</div>';
                        }

                        html += '</div>';
                    });
                    timelineContainer.innerHTML = html;
                }
            })
            .catch(err => {
                timelineContainer.innerHTML = '<div style="text-align:center; padding:20px; color:var(--red); font-size:11px;">Error loading reports.</div>';
            });
    }

    document.querySelectorAll(".btn-view-reports-trigger").forEach(function (btn) {
        btn.addEventListener("click", function (e) {
            e.preventDefault();
            const taskId = btn.getAttribute("data-id");
            if (taskId) openTaskReports(taskId);
        });
    });
</script>

</body>
</html>
