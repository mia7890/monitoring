<?php
require_once 'db.php';
require_once 'auth.php';
requireAccess();

$isAdmin = isAdmin();
$currentFaeId = currentFaeId();
$faeOnly = false;
$roleParam = '';
$roleQuery = '';
$notificationItems = notificationItems($pdo, $isAdmin, $currentFaeId);
if (!$isAdmin && $currentFaeId && empty($_SESSION['monitoring_fae_name'])) {
    $nameStmt = $pdo->prepare('SELECT name, fae_code FROM fae_users WHERE id = ?');
    $nameStmt->execute([$currentFaeId]);
    $faeProfile = $nameStmt->fetch();
    $_SESSION['monitoring_fae_name'] = (string)($faeProfile['name'] ?? 'FAE User');
    $_SESSION['monitoring_fae_code'] = (string)($faeProfile['fae_code'] ?? '');
}

// Flash message helper
$formMessage = $_GET['msg'] ?? '';
$formError = isset($_GET['err']);
if ($formError && empty($formMessage)) {
    $formMessage = $_GET['err'];
}

// Redirect helper
if (!function_exists('redirectWithMsg')) {
    function redirectWithMsg($page, $msg = '', $isError = false, $roleParam = '') {
        $param = $isError ? "err=" . urlencode($msg) : "msg=" . urlencode($msg);
        $glue = str_contains($page, '?') ? '&' : '?';
        $url = $page . $glue . $param;
        if ($roleParam && !str_contains($url, 'role=')) {
            $url .= (str_contains($url, '?') ? '&' : '?') . ltrim($roleParam, '?');
        }
        header("Location: $url");
        exit();
    }
}

// =========================================================
// AJAX ENDPOINTS (TIMELINE & DETAILS)
// =========================================================
if (isset($_GET['action']) && $_GET['action'] === 'get_task_timeline') {
    header('Content-Type: application/json');
    $taskId = (int)($_GET['task_id'] ?? 0);

    $tStmt = $pdo->prepare("
        SELECT t.*, f.name as fae_name, f.fae_code 
        FROM tasks t 
        LEFT JOIN fae_users f ON t.fae_id = f.id 
        WHERE t.id = :task_id AND (:is_admin = 1 OR t.fae_id = :fae_id)
    ");
    $tStmt->execute(['task_id' => $taskId, 'is_admin' => $isAdmin ? 1 : 0, 'fae_id' => $currentFaeId]);
    $task = $tStmt->fetch();

    if (!$task) {
        echo json_encode(['success' => false, 'error' => 'Task not found or access denied.']);
        exit();
    }

    $uStmt = $pdo->prepare("
        SELECT * FROM task_updates 
        WHERE task_id = ? 
        ORDER BY created_at DESC
    ");
    $uStmt->execute([$taskId]);
    $updates = $uStmt->fetchAll();

    echo json_encode([
        'success' => true,
        'task' => $task,
        'updates' => $updates,
        'current_user_name' => $isAdmin ? 'Administrator' : currentFaeName(),
        'is_admin' => $isAdmin
    ]);
    exit();
}

// =========================================================
// POST HANDLERS
// =========================================================
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = $_POST['action'] ?? '';

    // Work report / Task update (Accessible by both Admin and assigned FAE)
    if ($action === 'add_task_update') {
        $taskId = (int)($_POST['task_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        $progress = isset($_POST['progress']) && $_POST['progress'] !== '' ? max(0, min(100, (int)$_POST['progress'])) : null;
        $status = trim($_POST['status'] ?? '');

        if ($taskId <= 0 || empty($message)) {
            redirectWithMsg('tasks.php', 'Please write a progress note or report message.', true, $roleParam);
        }

        // Verify task exists and authorization
        $chkStmt = $pdo->prepare("SELECT * FROM tasks WHERE id = :task_id AND (:is_admin = 1 OR fae_id = :fae_id)");
        $chkStmt->execute(['task_id' => $taskId, 'is_admin' => $isAdmin ? 1 : 0, 'fae_id' => $currentFaeId]);
        $task = $chkStmt->fetch();
        if (!$task) {
            redirectWithMsg('tasks.php', 'Task not found or access denied.', true, $roleParam);
        }

        // Handle attachment upload if present
        $attachmentPath = null;
        if (isset($_FILES['attachment']) && ($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['attachment']['error'] !== UPLOAD_ERR_OK || empty($_FILES['attachment']['tmp_name'])) {
                redirectWithMsg('tasks.php', 'The attachment could not be uploaded. Please try a smaller file.', true, $roleParam);
            }
            if ($_FILES['attachment']['size'] > 10 * 1024 * 1024) {
                redirectWithMsg('tasks.php', 'Attachment must be 10 MB or smaller.', true, $roleParam);
            }

            $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
            $imageInfo = @getimagesize($_FILES['attachment']['tmp_name']);
            if ($imageInfo) {
                $imageExt = image_type_to_extension($imageInfo[2], false);
                $ext = $imageExt === 'jpeg' ? 'jpg' : $imageExt;
            }

            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'pdf', 'doc', 'docx', 'txt', 'zip'];
            if (!in_array($ext, $allowedExts, true)) {
                redirectWithMsg('tasks.php', 'Supported attachments are JPG, PNG, GIF, WEBP, BMP, PDF, DOC, DOCX, TXT, and ZIP.', true, $roleParam);
            }

            $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $filename = 'report_' . $taskId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
            if (!move_uploaded_file($_FILES['attachment']['tmp_name'], $uploadDir . DIRECTORY_SEPARATOR . $filename)) {
                redirectWithMsg('tasks.php', 'The attachment could not be saved.', true, $roleParam);
            }
            $attachmentPath = 'uploads/' . $filename;
        }

        $authorRole = $isAdmin ? 'admin' : 'fae';
        $authorName = $isAdmin ? 'Administrator' : currentFaeName();
        $authorFaeId = $isAdmin ? null : $currentFaeId;

        $newProgress = $progress !== null ? $progress : (int)$task['progress'];
        $newStatus = !empty($status) ? $status : $task['status'];
        if ($progress !== null && empty($status)) {
            $newStatus = $newProgress >= 100 ? 'Completed' : ($newProgress > 0 ? 'In Progress' : 'Pending');
        }

        // Insert into task_updates
        $insStmt = $pdo->prepare("
            INSERT INTO task_updates (task_id, fae_id, author_role, author_name, message, attachment, progress_at_update, status_at_update)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insStmt->execute([
            $taskId,
            $authorFaeId,
            $authorRole,
            $authorName,
            $message,
            $attachmentPath,
            $newProgress,
            $newStatus
        ]);

        // Update task status and progress
        $updStmt = $pdo->prepare("UPDATE tasks SET progress = ?, status = ? WHERE id = ?");
        $updStmt->execute([$newProgress, $newStatus, $taskId]);

        $redirectUrl = ($_POST['redirect_to'] ?? 'tasks.php');
        if (!in_array($redirectUrl, ['tasks.php', 'index.php'], true)) {
            $redirectUrl = 'tasks.php';
        }
        redirectWithMsg($redirectUrl, 'Work report update submitted successfully.', false, $roleParam);
    }

    // Check admin authorization for all write actions
    if (!$isAdmin) {
        if ($action === 'update_profile') {
            if (empty($_FILES['profile_image']['tmp_name']) || $_FILES['profile_image']['error'] !== UPLOAD_ERR_OK || $_FILES['profile_image']['size'] > 2 * 1024 * 1024) {
                redirectWithMsg('index.php', 'Choose a valid profile picture smaller than 2 MB.', true, $roleParam);
            }
            $imageInfo = @getimagesize($_FILES['profile_image']['tmp_name']);
            $extension = $imageInfo ? image_type_to_extension($imageInfo[2], false) : '';
            if (!$imageInfo || !in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                redirectWithMsg('index.php', 'Supported picture types are JPG, PNG, GIF, and WEBP.', true, $roleParam);
            }
            $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $profileImage = 'uploads/fae_' . bin2hex(random_bytes(12)) . '.' . $extension;
            if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], __DIR__ . DIRECTORY_SEPARATOR . $profileImage)) {
                redirectWithMsg('index.php', 'The profile picture could not be saved.', true, $roleParam);
            }
            $stmt = $pdo->prepare('UPDATE fae_users SET profile_image = ? WHERE id = ?');
            $stmt->execute([$profileImage, $currentFaeId]);
            redirectWithMsg('index.php', 'Profile picture updated.', false, $roleParam);
        }
        if ($action === 'update_progress') {
            $taskId = (int)($_POST['task_id'] ?? 0);
            $progress = max(0, min(100, (int)($_POST['progress'] ?? 0)));
            $status = $progress >= 100 ? 'Completed' : ($progress > 0 ? 'In Progress' : 'Pending');
            $stmt = $pdo->prepare('UPDATE tasks SET progress = ?, status = ? WHERE id = ? AND fae_id = ?');
            $stmt->execute([$progress, $status, $taskId, $currentFaeId]);
            redirectWithMsg('tasks.php', 'Task progress updated.', false, $roleParam);
        }
        redirectWithMsg('tasks.php', 'Unauthorized: Admin privileges required.', true, $roleParam);
    }

    // 1. ADD FAE PERSON
    if ($action === 'add_fae') {
        $name       = trim($_POST['name'] ?? '');
        $faeCode    = strtoupper(trim($_POST['fae_code'] ?? ''));
        $email      = trim($_POST['email'] ?? '') ?: null;
        $department = trim($_POST['department'] ?? '') ?: null;
        $phone      = trim($_POST['phone'] ?? '') ?: null;

        if (empty($name) || empty($faeCode)) {
            redirectWithMsg('tasks.php', 'Name and FAE Code are required.', true, $roleParam);
        }

        // Check duplicate code
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM fae_users WHERE fae_code = ?");
        $checkStmt->execute([$faeCode]);
        if ($checkStmt->fetchColumn() > 0) {
            redirectWithMsg('tasks.php', "FAE Code '$faeCode' is already assigned to another person.", true, $roleParam);
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO fae_users (name, fae_code, email, department, phone) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $faeCode, $email, $department, $phone]);
            redirectWithMsg('fae.php', "FAE '$name' ($faeCode) was added successfully!", false, $roleParam);
        } catch (PDOException $e) {
            redirectWithMsg('tasks.php', 'Database error: ' . $e->getMessage(), true, $roleParam);
        }
    }

    // 2. EDIT FAE PERSON
    if ($action === 'edit_fae') {
        $id         = (int)($_POST['fae_id'] ?? 0);
        $name       = trim($_POST['name'] ?? '');
        $faeCode    = strtoupper(trim($_POST['fae_code'] ?? ''));
        $email      = trim($_POST['email'] ?? '') ?: null;
        $department = trim($_POST['department'] ?? '') ?: null;
        $phone      = trim($_POST['phone'] ?? '') ?: null;

        if ($id <= 0 || empty($name) || empty($faeCode)) {
            redirectWithMsg('tasks.php', 'Valid ID, Name, and FAE Code are required.', true, $roleParam);
        }

        // Check duplicate code for other IDs
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM fae_users WHERE fae_code = ? AND id != ?");
        $checkStmt->execute([$faeCode, $id]);
        if ($checkStmt->fetchColumn() > 0) {
            redirectWithMsg('tasks.php', "FAE Code '$faeCode' is already in use by another person.", true, $roleParam);
        }

        try {
            $stmt = $pdo->prepare("UPDATE fae_users SET name = ?, fae_code = ?, email = ?, department = ?, phone = ? WHERE id = ?");
            $stmt->execute([$name, $faeCode, $email, $department, $phone, $id]);
            redirectWithMsg('fae.php', "FAE '$name' details updated successfully!", false, $roleParam);
        } catch (PDOException $e) {
            redirectWithMsg('tasks.php', 'Database error: ' . $e->getMessage(), true, $roleParam);
        }
    }

    // 3. DELETE FAE PERSON
    if ($action === 'delete_fae') {
        $id = (int)($_POST['fae_id'] ?? 0);
        if ($id > 0) {
            try {
                // First unassign any tasks belonging to this FAE
                $pdo->prepare("UPDATE tasks SET fae_id = NULL WHERE fae_id = ?")->execute([$id]);
                // Delete the FAE
                $pdo->prepare("DELETE FROM fae_users WHERE id = ?")->execute([$id]);
                redirectWithMsg('fae.php', "FAE member removed successfully. Assigned tasks were unassigned.", false, $roleParam);
            } catch (PDOException $e) {
                redirectWithMsg('tasks.php', 'Database error: ' . $e->getMessage(), true, $roleParam);
            }
        }
    }

    // 4. ADD TASK (OPTIONAL SPECIFIC FAE TARGET)
    if ($action === 'add_task') {
        $faeId       = !empty($_POST['fae_id']) ? (int)$_POST['fae_id'] : null;
        $taskName    = trim($_POST['task_name'] ?? '');
        $region      = trim($_POST['region'] ?? '') ?: null;
        $course      = trim($_POST['course'] ?? '') ?: null;
        $deadline    = trim($_POST['deadline'] ?? '') ?: null;
        $status      = 'Pending';
        $progress    = 0;
        $priority    = trim($_POST['priority'] ?? 'Medium');
        $description = trim($_POST['description'] ?? '') ?: null;

        if (empty($taskName)) {
            redirectWithMsg('tasks.php', 'Task name is required.', true, $roleParam);
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO tasks (fae_id, region, course, task_name, description, deadline, status, progress, priority)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$faeId, $region, $course, $taskName, $description, $deadline, $status, $progress, $priority]);
            redirectWithMsg('tasks.php', "Task '$taskName' created successfully!", false, $roleParam);
        } catch (PDOException $e) {
            redirectWithMsg('tasks.php', 'Database error: ' . $e->getMessage(), true, $roleParam);
        }
    }

    // 5. EDIT TASK
    if ($action === 'edit_task') {
        $id          = (int)($_POST['task_id'] ?? 0);
        $faeId       = !empty($_POST['fae_id']) ? (int)$_POST['fae_id'] : null;
        $taskName    = trim($_POST['task_name'] ?? '');
        $region      = trim($_POST['region'] ?? '') ?: null;
        $course      = trim($_POST['course'] ?? '') ?: null;
        $deadline    = trim($_POST['deadline'] ?? '') ?: null;
        $status      = '';
        $progress    = 0;
        $priority    = trim($_POST['priority'] ?? 'Medium');
        $description = trim($_POST['description'] ?? '') ?: null;

        if ($id <= 0 || empty($taskName)) {
            redirectWithMsg('tasks.php', 'Valid Task ID and Task Name are required.', true, $roleParam);
        }

        $currentTaskStmt = $pdo->prepare('SELECT status, progress FROM tasks WHERE id = ?');
        $currentTaskStmt->execute([$id]);
        $currentTask = $currentTaskStmt->fetch();
        if (!$currentTask) {
            redirectWithMsg('tasks.php', 'Task was not found.', true, $roleParam);
        }
        $status = $currentTask['status'];
        $progress = (int)$currentTask['progress'];

        try {
            $stmt = $pdo->prepare("
                UPDATE tasks 
                SET fae_id = ?, region = ?, course = ?, task_name = ?, description = ?, deadline = ?, status = ?, progress = ?, priority = ?
                WHERE id = ?
            ");
            $stmt->execute([$faeId, $region, $course, $taskName, $description, $deadline, $status, $progress, $priority, $id]);
            redirectWithMsg('tasks.php', "Task '$taskName' updated successfully!", false, $roleParam);
        } catch (PDOException $e) {
            redirectWithMsg('tasks.php', 'Database error: ' . $e->getMessage(), true, $roleParam);
        }
    }

    // 6. DELETE TASK
    if ($action === 'delete_task') {
        $id = (int)($_POST['task_id'] ?? 0);
        if ($id > 0) {
            try {
                $pdo->prepare("DELETE FROM tasks WHERE id = ?")->execute([$id]);
                redirectWithMsg('tasks.php', "Task deleted successfully.", false, $roleParam);
            } catch (PDOException $e) {
                redirectWithMsg('tasks.php', 'Database error: ' . $e->getMessage(), true, $roleParam);
            }
        }
    }
}

// =========================================================
// FETCH DATA FOR DISPLAY
// =========================================================

// Fetch all FAEs with task summary metrics
$faeQuery = $pdo->prepare("
    SELECT 
        f.*,
        COUNT(t.id) as total_assigned,
        SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) as completed_count,
        SUM(CASE WHEN t.status = 'In Progress' THEN 1 ELSE 0 END) as in_progress_count,
        SUM(CASE WHEN t.status = 'Pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN t.status = 'Overdue' OR (t.deadline < CURDATE() AND t.status != 'Completed') THEN 1 ELSE 0 END) as overdue_count,
        COALESCE(AVG(t.progress), 0) as avg_progress
    FROM fae_users f
    LEFT JOIN tasks t ON f.id = t.fae_id
    WHERE :is_admin = 1 OR f.id = :fae_id
    GROUP BY f.id
    ORDER BY f.name ASC
");
$faeQuery->execute(['is_admin' => $isAdmin ? 1 : 0, 'fae_id' => $currentFaeId]);
$faeList = $faeQuery->fetchAll();

// Fetch all Tasks with FAE info and update counts
$tasksQuery = $pdo->prepare("
    SELECT 
        t.*,
        f.name as fae_name,
        f.fae_code,
        f.department as fae_department,
        (SELECT COUNT(*) FROM task_updates tu WHERE tu.task_id = t.id) as update_count
    FROM tasks t
    LEFT JOIN fae_users f ON t.fae_id = f.id
    WHERE :is_admin = 1 OR t.fae_id = :fae_id
    ORDER BY t.id DESC
");
$tasksQuery->execute(['is_admin' => $isAdmin ? 1 : 0, 'fae_id' => $currentFaeId]);
$tasksList = $tasksQuery->fetchAll();

// Global Stats
$totalFAE        = count($faeList);
$totalTasks      = count($tasksList);
$completedTasks  = 0;
$inProgressTasks = 0;
$pendingTasks    = 0;
$overdueTasks    = 0;

$todayStr = date('Y-m-d');
foreach ($tasksList as $t) {
    if ($t['status'] === 'Completed') {
        $completedTasks++;
    } elseif ($t['status'] === 'In Progress') {
        $inProgressTasks++;
    } elseif ($t['status'] === 'Pending') {
        $pendingTasks++;
    }
    if ($t['status'] === 'Overdue' || ($t['deadline'] && $t['deadline'] < $todayStr && $t['status'] !== 'Completed')) {
        $overdueTasks++;
    }
}

$avatarColors = ['#2563eb', '#7c3aed', '#f59e0b', '#16a34a', '#0891b2', '#db2777', '#4f46e5'];
$appBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/monitoring')), '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?= $appBase ?>/">
    <title>Task &amp; FAE Management | Monitoring System</title>
    <meta name="description" content="Manage Field Application Engineers (FAE) and assign and track monitoring tasks.">

    <link rel="stylesheet" href="<?= $appBase ?>/style.css?v=<?= time() ?>">

    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="<?= $faeOnly ? 'fae-page' : '' ?>">

<div class="app">

    <!-- ================= SIDEBAR ================= -->
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
                <a href="fae.php" class="nav-item" id="navFaeLink">
                    <span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg></span>
                    <span>FAE</span>
                </a>
            <?php endif; ?>

            <a href="tasks.php<?= $roleParam ?>" class="nav-item <?= !$faeOnly ? 'active' : '' ?>">
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

        <!-- TOPBAR -->
        <header class="topbar">
            <button class="menu-toggle" id="menuToggle">☰</button>

            <div class="breadcrumb">
                <a href="index.php<?= $roleParam ?>" style="color:inherit; text-decoration:none;">Home</a>
                <b>/</b>
                <strong>Tasks &amp; FAE</strong>
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
                    <div class="avatar"><?= $isAdmin ? 'AD' : htmlspecialchars(strtoupper(substr(currentFaeName(), 0, 2))) ?></div>
                    <div class="profile-info">
                        <strong><?= $isAdmin ? 'Administrator' : htmlspecialchars(currentFaeName()) ?></strong>
                        <span><?= $isAdmin ? 'Full Management' : htmlspecialchars(currentFaeCode()) ?></span>
                    </div>
                </div>
            </div>
        </header>

        <!-- CONTENT SECTION -->
        <section class="content">

            <!-- FLASH ALERTS -->
            <?php if (!empty($formMessage)): ?>
                <div class="alert-banner <?= $formError ? 'error' : 'success' ?>">
                    <span><?= ($formError ? '⚠️ ' : '✅ ') . htmlspecialchars($formMessage) ?></span>
                    <button type="button" class="close-btn" onclick="this.parentElement.style.display='none';">&times;</button>
                </div>
            <?php endif; ?>

            <!-- PAGE INTRO -->
            <div class="page-intro">
                <div>
                    <span class="welcome-label" style="color:var(--primary); font-size:10px; font-weight:700; letter-spacing:1px;">WORKSPACE</span>
                    <h1><?= $faeOnly ? 'FAE Team Directory' : 'Tasks &amp; FAE Directory' ?></h1>
                    <p>Manage Field Application Engineers, assign regional tasks, and track real-time execution progress.</p>
                </div>

                <div style="display:flex; align-items:center; gap:10px;">
                    <div class="date-box">
                        <span>Today</span>
                        <strong id="currentDate"><?= date('F d, Y') ?></strong>
                    </div>
                </div>
            </div>

            <!-- ================= FAE PEOPLE DIRECTORY ================= -->
            <?php if ($isAdmin && $faeOnly): ?>
            <div class="panel" id="fae-section" style="margin-bottom: 26px;">
                <div class="panel-header">
                    <div class="fae-section-title">
                        <h2><span>♙</span> FAE Team Members</h2>
                        <p>Field Application Engineers and their active task assignments</p>
                    </div>

                    <?php if ($isAdmin): ?>
                        <button type="button" class="primary-button btn-sm" id="openAddFaeModalBtn2">
                            <span>＋</span> Add New Person
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (empty($faeList)): ?>
                    <div style="text-align:center; padding: 48px 20px; color: var(--text-light);">
                        <div style="font-size: 32px; margin-bottom: 10px;">👥</div>
                        <h3 style="font-size: 15px; font-weight:700; color: var(--text); margin-bottom:6px;">No FAE Members Registered Yet</h3>
                        <p style="font-size: 12px; max-width: 420px; margin: 0 auto 16px;">
                            Start by adding your Field Application Engineers so you can assign monitoring tasks to them.
                        </p>
                        <?php if ($isAdmin): ?>
                            <button type="button" class="primary-button" id="openAddFaeModalBtnEmpty">
                                ＋ Add First FAE Member
                            </button>
                        <?php else: ?>
                            <p style="font-size: 11px; color: var(--text-light);">No FAE team members are currently assigned.</p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="fae-grid">
                        <?php foreach ($faeList as $index => $fae): 
                            $avatarColor = $avatarColors[$index % count($avatarColors)];
                            $nameParts = explode(' ', trim($fae['name']));
                            $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
                            $avgProgress = round($fae['avg_progress']);
                        ?>
                        <div class="fae-card" data-fae-id="<?= $fae['id'] ?>">
                            <div>
                                <div class="fae-card-top">
                                    <div class="fae-avatar-box" style="background: <?= $avatarColor ?>;">
                                        <?= htmlspecialchars($initials) ?>
                                    </div>
                                    <div class="fae-info">
                                        <h3><?= htmlspecialchars($fae['name']) ?></h3>
                                        <span class="fae-code-badge"><?= htmlspecialchars($fae['fae_code']) ?></span>
                                        <div class="fae-dept">
                                            <?= htmlspecialchars($fae['department'] ?? 'Field Engineering') ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="fae-stats-row">
                                    <div class="fae-stat-col">
                                        <span>Active Tasks</span>
                                        <strong><?= (int)$fae['in_progress_count'] + (int)$fae['pending_count'] ?></strong>
                                    </div>
                                    <div class="fae-stat-col">
                                        <span>Completed</span>
                                        <strong style="color: var(--green);"><?= (int)$fae['completed_count'] ?></strong>
                                    </div>
                                </div>

                                <div class="fae-progress-bar-wrap">
                                    <div class="fae-progress-label">
                                        <span>Avg Completion</span>
                                        <strong><?= $avgProgress ?>%</strong>
                                    </div>
                                    <div class="progress-bar">
                                        <div class="<?= $avgProgress == 100 ? 'complete' : '' ?>" style="width: <?= $avgProgress ?>%;"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="fae-card-actions">
                                <?php if ($isAdmin): ?>
                                    <a href="fae.php?code=<?= urlencode($fae['fae_code']) ?>" target="_blank" class="outline-button fae-link-button" title="Open the private FAE webpage link">
                                        Open FAE page
                                    </a>

                                    <!-- ONE CLICK BUTTON TO ADD TASK TO THIS SPECIFIC FAE -->
                                    <button type="button" 
                                            class="btn-add-task-fae btn-assign-task-trigger" 
                                            data-fae-id="<?= $fae['id'] ?>" 
                                            data-fae-name="<?= htmlspecialchars($fae['name']) ?>"
                                            title="Add a task assigned directly to <?= htmlspecialchars($fae['name']) ?>">
                                        <span>＋</span> Add Task to FAE
                                    </button>

                                    <!-- Edit FAE Button -->
                                    <button type="button" 
                                            class="btn-icon-square btn-edit-fae-trigger" 
                                            data-id="<?= $fae['id'] ?>"
                                            data-name="<?= htmlspecialchars($fae['name']) ?>"
                                            data-code="<?= htmlspecialchars($fae['fae_code']) ?>"
                                            data-email="<?= htmlspecialchars($fae['email'] ?? '') ?>"
                                            data-dept="<?= htmlspecialchars($fae['department'] ?? '') ?>"
                                            data-phone="<?= htmlspecialchars($fae['phone'] ?? '') ?>"
                                            title="Edit FAE details">
                                        ✏️
                                    </button>

                                    <!-- Delete FAE Button -->
                                    <button type="button" 
                                            class="btn-icon-square danger btn-delete-fae-trigger" 
                                            data-id="<?= $fae['id'] ?>"
                                            data-name="<?= htmlspecialchars($fae['name']) ?>"
                                            title="Delete FAE">
                                        🗑️
                                    </button>
                                <?php else: ?>
                                    <button type="button" 
                                            class="btn-add-task-fae filter-tasks-by-fae-btn" 
                                            data-fae-id="<?= $fae['id'] ?>"
                                            title="Filter tasks assigned to <?= htmlspecialchars($fae['name']) ?>">
                                        🔍 View Tasks (<?= (int)$fae['total_assigned'] ?>)
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="fae-only-hidden">
            <!-- ================= TASKS LIST PANEL ================= -->
            <div class="panel">
                <div class="panel-header">
                    <div>
                        <h2>Task Directory &amp; Assignments</h2>
                        <p>Track, filter and update all assigned field activities</p>
                    </div>

                    <?php if ($isAdmin): ?>
                        <button type="button" class="primary-button btn-sm" id="openAddTaskModalBtn2">
                            <span>＋</span> Add New Task
                        </button>
                    <?php endif; ?>
                </div>

                <!-- FILTER TOOLBAR -->
                <div class="tasks-toolbar">
                    <div class="search-box-wrap">
                        <span class="search-box-icon">🔍</span>
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
                            <?php foreach ($faeList as $fae): ?>
                                <option value="<?= $fae['id'] ?>"><?= htmlspecialchars($fae['name']) ?> (<?= htmlspecialchars($fae['fae_code']) ?>)</option>
                            <?php endforeach; ?>
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
                            <?php if (!empty($tasksList)): ?>
                                <?php foreach ($tasksList as $row): 
                                    $rawStatus = $row['status'] ?? 'Pending';
                                    $normalizedStatus = strtolower(str_replace(' ', '', $rawStatus));
                                    
                                    $badgeClass = ($normalizedStatus === 'inprogress') ? 'progress-badge' : $normalizedStatus . '-badge';
                                    $barClass = ($normalizedStatus === 'completed') ? 'complete' : (($normalizedStatus === 'inprogress') ? '' : $normalizedStatus);
                                    
                                    $priority = $row['priority'] ?? 'Medium';
                                    $priorityClass = 'priority-' . strtolower($priority);

                                    $isOverdue = (!empty($row['deadline']) && $row['deadline'] < $todayStr && $rawStatus !== 'Completed');

                                    $faeInitials = 'UA';
                                    if (!empty($row['fae_name'])) {
                                        $np = explode(' ', trim($row['fae_name']));
                                        $faeInitials = strtoupper(substr($np[0], 0, 1) . (isset($np[1]) ? substr($np[1], 0, 1) : ''));
                                    }
                                    $avatarCol = $avatarColors[($row['fae_id'] ?? 0) % count($avatarColors)];
                                    $updateCount = (int)($row['update_count'] ?? 0);
                                ?>
                                <tr class="task-table-row" 
                                    data-task-name="<?= strtolower(htmlspecialchars($row['task_name'])) ?>"
                                    data-fae-name="<?= strtolower(htmlspecialchars($row['fae_name'] ?? 'unassigned')) ?>"
                                    data-region="<?= strtolower(htmlspecialchars($row['region'] ?? '')) ?>"
                                    data-course="<?= strtolower(htmlspecialchars($row['course'] ?? '')) ?>"
                                    data-status="<?= $rawStatus ?>"
                                    data-fae-id="<?= $row['fae_id'] ?? 'unassigned' ?>"
                                    data-priority="<?= $priority ?>">
                                    <td>
                                        <div class="user-cell">
                                            <div class="small-avatar" style="background: <?= $avatarCol ?>; color:white;">
                                                <?= htmlspecialchars($faeInitials) ?>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($row['fae_name'] ?? 'Unassigned') ?></strong>
                                                <span><?= htmlspecialchars($row['fae_code'] ?? 'None') ?></span>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <strong style="color:var(--text); font-size:12px; display:block;">
                                            <a href="javascript:void(0)" 
                                               class="btn-view-reports-trigger" 
                                               data-id="<?= $row['id'] ?>"
                                               style="color:inherit; text-decoration:none;">
                                                <?= htmlspecialchars($row['task_name']) ?>
                                            </a>
                                        </strong>
                                        <?php if (!empty($row['description'])): ?>
                                            <small class="task-description">
                                                <?= htmlspecialchars($row['description']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>

                                    <td><?= htmlspecialchars($row['region'] ?? '—') ?></td>
                                    <td><?= htmlspecialchars($row['course'] ?? '—') ?></td>

                                    <td>
                                        <span class="priority-badge <?= $priorityClass ?>">
                                            <?= htmlspecialchars($priority) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="<?= $isOverdue ? 'deadline-overdue' : '' ?>">
                                            <?= !empty($row['deadline']) ? date('M d, Y', strtotime($row['deadline'])) : '—' ?>
                                        </span>
                                        <?php if ($isOverdue): ?>
                                            <small style="display:block; color:var(--red); font-size:9px; font-weight:700;">Overdue</small>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="badge <?= $badgeClass ?>">
                                            <?= htmlspecialchars($rawStatus) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="progress-cell">
                                            <div class="progress-bar">
                                                <div class="<?= $barClass ?>" style="width:<?= (int)$row['progress'] ?>%"></div>
                                            </div>
                                            <span><?= (int)$row['progress'] ?>%</span>
                                        </div>
                                    </td>

                                    <td style="text-align:right; white-space:nowrap;">
                                        <button type="button" 
                                                class="outline-button btn-view-reports-trigger btn-sm" 
                                                data-id="<?= $row['id'] ?>"
                                                title="View task details, progress logs, and submit reports">
                                            📋 Reports <?= $updateCount > 0 ? "($updateCount)" : '' ?>
                                        </button>

                                        <?php if ($isAdmin): ?>
                                            <button type="button" 
                                                    class="btn-icon-square btn-edit-task-trigger" 
                                                    data-id="<?= $row['id'] ?>"
                                                    data-fae-id="<?= $row['fae_id'] ?? '' ?>"
                                                    data-task-name="<?= htmlspecialchars($row['task_name']) ?>"
                                                    data-region="<?= htmlspecialchars($row['region'] ?? '') ?>"
                                                    data-course="<?= htmlspecialchars($row['course'] ?? '') ?>"
                                                    data-deadline="<?= htmlspecialchars($row['deadline'] ?? '') ?>"
                                                    data-status="<?= htmlspecialchars($row['status'] ?? 'Pending') ?>"
                                                    data-progress="<?= (int)$row['progress'] ?>"
                                                    data-priority="<?= htmlspecialchars($row['priority'] ?? 'Medium') ?>"
                                                    data-description="<?= htmlspecialchars($row['description'] ?? '') ?>"
                                                    title="Edit Task"
                                                    style="display:inline-flex;">
                                                ✏️
                                            </button>

                                            <button type="button" 
                                                    class="btn-icon-square danger btn-delete-task-trigger" 
                                                    data-id="<?= $row['id'] ?>"
                                                    data-name="<?= htmlspecialchars($row['task_name']) ?>"
                                                    title="Delete Task"
                                                    style="display:inline-flex;">
                                                🗑️
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr id="noTasksRow">
                                    <td colspan="9" style="text-align:center; padding:36px 20px; color:var(--text-light);">
                                        <div style="font-size:24px; margin-bottom:6px;">📋</div>
                                        <p>No tasks found in the database. Use the button above to create one.</p>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            </div>

            <!-- FOOTER -->
            <footer>
                <span>Monitoring System © 2026</span>
                <span>Task &amp; FAE System Active</span>
            </footer>

        </section>
    </main>
</div>

<!-- =========================================================
     MODALS SECTION
     ========================================================= -->

<!-- 1. ADD FAE PERSON MODAL -->
<div class="custom-modal-overlay" id="addFaeModal">
    <div class="custom-modal">
        <div class="custom-modal-header">
            <h3><span>♙</span> Add FAE Team Member</h3>
            <button type="button" class="custom-modal-close" data-close="addFaeModal">&times;</button>
        </div>

        <form method="POST" action="tasks.php<?= $roleParam ?>">
            <input type="hidden" name="action" value="add_fae">

            <div class="custom-modal-body">
                <div class="form-group">
                    <label class="form-label">Full Name <span class="req">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. John Doe" required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label">FAE Code / Employee ID <span class="req">*</span></label>
                    <input type="text" name="fae_code" class="form-control" placeholder="e.g. FAE-101" required>
                    <small style="font-size:10px; color:var(--text-light); margin-top:3px; display:block;">Must be unique across all engineers.</small>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" placeholder="engineer@domain.com">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone / Contact</label>
                        <input type="text" name="phone" class="form-control" placeholder="+63 912 345 6789">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Department / Field Specialization</label>
                    <input type="text" name="department" class="form-control" placeholder="e.g. Industrial Automation, IT Systems">
                </div>
            </div>

            <div class="custom-modal-footer">
                <button type="button" class="secondary-button btn-sm" data-close="addFaeModal">Cancel</button>
                <button type="submit" class="primary-button btn-sm">Save FAE Member</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. EDIT FAE PERSON MODAL -->
<div class="custom-modal-overlay" id="editFaeModal">
    <div class="custom-modal">
        <div class="custom-modal-header">
            <h3><span>✏️</span> Edit FAE Member</h3>
            <button type="button" class="custom-modal-close" data-close="editFaeModal">&times;</button>
        </div>

        <form method="POST" action="tasks.php<?= $roleParam ?>">
            <input type="hidden" name="action" value="edit_fae">
            <input type="hidden" name="fae_id" id="editFaeId">

            <div class="custom-modal-body">
                <div class="form-group">
                    <label class="form-label">Full Name <span class="req">*</span></label>
                    <input type="text" name="name" id="editFaeName" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">FAE Code / Employee ID <span class="req">*</span></label>
                    <input type="text" name="fae_code" id="editFaeCode" class="form-control" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" id="editFaeEmail" class="form-control">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Phone / Contact</label>
                        <input type="text" name="phone" id="editFaePhone" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Department / Field Specialization</label>
                    <input type="text" name="department" id="editFaeDept" class="form-control">
                </div>
            </div>

            <div class="custom-modal-footer">
                <button type="button" class="secondary-button btn-sm" data-close="editFaeModal">Cancel</button>
                <button type="submit" class="primary-button btn-sm">Update FAE Member</button>
            </div>
        </form>
    </div>
</div>

<!-- 3. ADD TASK MODAL (AUTO PRE-SELECTS FAE WHEN CLICKED FROM FAE CARD) -->
<div class="custom-modal-overlay" id="addTaskModal">
    <div class="custom-modal">
        <div class="custom-modal-header">
            <h3 id="addTaskModalHeaderTitle"><span>✓</span> Create New Task</h3>
            <button type="button" class="custom-modal-close" data-close="addTaskModal">&times;</button>
        </div>

        <form method="POST" action="tasks.php<?= $roleParam ?>" id="addTaskForm">
            <input type="hidden" name="action" value="add_task">

            <div class="custom-modal-body">
                <div class="form-group">
                    <label class="form-label">Assign To FAE</label>
                    <select name="fae_id" id="addTaskFaeSelect" class="form-select">
                        <option value="">-- Unassigned / General Task --</option>
                        <?php foreach ($faeList as $fae): ?>
                            <option value="<?= $fae['id'] ?>"><?= htmlspecialchars($fae['name']) ?> (<?= htmlspecialchars($fae['fae_code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                    <small id="addTaskFaeNotice" style="font-size:10px; color:var(--primary); margin-top:3px; display:none; font-weight:600;">
                        🎯 Assigning directly to selected FAE.
                    </small>
                </div>

                <div class="form-group">
                    <label class="form-label">Task Name / Deliverable <span class="req">*</span></label>
                    <input type="text" name="task_name" class="form-control" placeholder="e.g. PLC Maintenance & System Health Check" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Region</label>
                        <input type="text" name="region" class="form-control" placeholder="e.g. Region 3, NCR, Region 4A" list="regionOptions">
                        <datalist id="regionOptions">
                            <option value="NCR">
                            <option value="Region 1">
                            <option value="Region 2">
                            <option value="Region 3">
                            <option value="Region 4A">
                            <option value="Region 4B">
                            <option value="Region 5">
                            <option value="Region 6">
                            <option value="Region 7">
                            <option value="Region 8">
                            <option value="Region 9">
                            <option value="Region 10">
                            <option value="Region 11">
                            <option value="Region 12">
                            <option value="CAR">
                            <option value="BARMM">
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
                        <input type="date" name="deadline" class="form-control" value="<?= date('Y-m-d', strtotime('+7 days')) ?>">
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

<!-- 4. EDIT TASK MODAL -->
<div class="custom-modal-overlay" id="editTaskModal">
    <div class="custom-modal">
        <div class="custom-modal-header">
            <h3><span>✏️</span> Edit Task Details</h3>
            <button type="button" class="custom-modal-close" data-close="editTaskModal">&times;</button>
        </div>

        <form method="POST" action="tasks.php<?= $roleParam ?>">
            <input type="hidden" name="action" value="edit_task">
            <input type="hidden" name="task_id" id="editTaskId">

            <div class="custom-modal-body">
                <div class="form-group">
                    <label class="form-label">Assigned FAE</label>
                    <select name="fae_id" id="editTaskFaeSelect" class="form-select">
                        <option value="">-- Unassigned --</option>
                        <?php foreach ($faeList as $fae): ?>
                            <option value="<?= $fae['id'] ?>"><?= htmlspecialchars($fae['name']) ?> (<?= htmlspecialchars($fae['fae_code']) ?>)</option>
                        <?php endforeach; ?>
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

<!-- 5. DELETE CONFIRMATION MODAL -->
<div class="custom-modal-overlay" id="deleteModal">
    <div class="custom-modal" style="max-width: 420px;">
        <div class="custom-modal-header">
            <h3 style="color:var(--red);"><span>⚠️</span> Confirm Deletion</h3>
            <button type="button" class="custom-modal-close" data-close="deleteModal">&times;</button>
        </div>

        <form method="POST" action="tasks.php<?= $roleParam ?>" id="deleteForm">
            <input type="hidden" name="action" id="deleteAction" value="">
            <input type="hidden" name="fae_id" id="deleteFaeId" value="">
            <input type="hidden" name="task_id" id="deleteTaskId" value="">

            <div class="custom-modal-body">
                <p id="deleteModalText" style="font-size:13px; color:var(--text); line-height:1.5;">
                    Are you sure you want to delete this item?
                </p>
            </div>

            <div class="custom-modal-footer">
                <button type="button" class="secondary-button btn-sm" data-close="deleteModal">Cancel</button>
                <button type="submit" class="btn-danger-outline btn-sm">Yes, Delete</button>
            </div>
        </form>
    </div>
</div>

<!-- 6. TASK DETAILS & PROGRESS REPORTS TIMELINE MODAL -->
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
                    <input type="hidden" name="redirect_to" value="tasks.php">

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

<!-- =========================================================
     JAVASCRIPT LOGIC
     ========================================================= -->
<script>
document.addEventListener("DOMContentLoaded", function () {

    // Sidebar Mobile Toggle
    const menuToggle = document.getElementById("menuToggle");
    const sidebar = document.getElementById("sidebar");
    if (menuToggle && sidebar) {
        menuToggle.addEventListener("click", function () {
            sidebar.classList.toggle("open");
        });
    }

    // Modal Helpers
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add("active");
        }
    }

    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove("active");
        }
    }

    // Generic Close Buttons
    document.querySelectorAll("[data-close]").forEach(function (btn) {
        btn.addEventListener("click", function () {
            const targetId = btn.getAttribute("data-close");
            closeModal(targetId);
        });
    });

    // Close on Backdrop Click
    document.querySelectorAll(".custom-modal-overlay").forEach(function (overlay) {
        overlay.addEventListener("click", function (e) {
            if (e.target === overlay) {
                overlay.classList.remove("active");
            }
        });
    });

    // Close on Escape
    document.addEventListener("keydown", function (e) {
        if (e.key === "Escape") {
            document.querySelectorAll(".custom-modal-overlay.active").forEach(function (m) {
                m.classList.remove("active");
            });
        }
    });

    // Open Add FAE Modal
    const addFaeBtns = [
        document.getElementById("openAddFaeModalBtn"),
        document.getElementById("openAddFaeModalBtn2"),
        document.getElementById("openAddFaeModalBtnEmpty")
    ];
    addFaeBtns.forEach(function (btn) {
        if (btn) {
            btn.addEventListener("click", function () {
                openModal("addFaeModal");
            });
        }
    });

    // Open General Add Task Modal
    const addTaskBtns = [document.getElementById("openAddTaskModalBtn2")];
    addTaskBtns.forEach(function (btn) {
        if (btn) {
            btn.addEventListener("click", function () {
                const select = document.getElementById("addTaskFaeSelect");
                if (select) select.value = "";
                const notice = document.getElementById("addTaskFaeNotice");
                if (notice) notice.style.display = "none";
                document.getElementById("addTaskModalHeaderTitle").innerHTML = "<span>✓</span> Create New Task";
                openModal("addTaskModal");
            });
        }
    });

    // =========================================================
    // "ADD TASK TO HIS FAE" BUTTON TRIGGER
    // =========================================================
    document.querySelectorAll(".btn-assign-task-trigger").forEach(function (btn) {
        btn.addEventListener("click", function () {
            const faeId = btn.getAttribute("data-fae-id");
            const faeName = btn.getAttribute("data-fae-name");

            const select = document.getElementById("addTaskFaeSelect");
            if (select) {
                select.value = faeId;
            }

            const notice = document.getElementById("addTaskFaeNotice");
            if (notice) {
                notice.style.display = "block";
                notice.textContent = "🎯 Assigning directly to " + faeName;
            }

            const title = document.getElementById("addTaskModalHeaderTitle");
            if (title) {
                title.innerHTML = "<span>✓</span> Add Task for <strong>" + faeName + "</strong>";
            }

            openModal("addTaskModal");
        });
    });

    // Edit FAE Trigger
    document.querySelectorAll(".btn-edit-fae-trigger").forEach(function (btn) {
        btn.addEventListener("click", function () {
            document.getElementById("editFaeId").value = btn.dataset.id;
            document.getElementById("editFaeName").value = btn.dataset.name;
            document.getElementById("editFaeCode").value = btn.dataset.code;
            document.getElementById("editFaeEmail").value = btn.dataset.email;
            document.getElementById("editFaeDept").value = btn.dataset.dept;
            document.getElementById("editFaePhone").value = btn.dataset.phone;
            openModal("editFaeModal");
        });
    });

    // Delete FAE Trigger
    document.querySelectorAll(".btn-delete-fae-trigger").forEach(function (btn) {
        btn.addEventListener("click", function () {
            document.getElementById("deleteAction").value = "delete_fae";
            document.getElementById("deleteFaeId").value = btn.dataset.id;
            document.getElementById("deleteTaskId").value = "";
            document.getElementById("deleteModalText").innerHTML = 
                "Are you sure you want to delete FAE <strong>" + btn.dataset.name + "</strong>?<br><small style='color:var(--text-light);'>Any tasks currently assigned to this engineer will become Unassigned.</small>";
            openModal("deleteModal");
        });
    });

    // Edit Task Trigger
    document.querySelectorAll(".btn-edit-task-trigger").forEach(function (btn) {
        btn.addEventListener("click", function () {
            document.getElementById("editTaskId").value = btn.dataset.id;
            document.getElementById("editTaskFaeSelect").value = btn.dataset.faeId || "";
            document.getElementById("editTaskName").value = btn.dataset.taskName;
            document.getElementById("editTaskRegion").value = btn.dataset.region;
            document.getElementById("editTaskCourse").value = btn.dataset.course;
            document.getElementById("editTaskDeadline").value = btn.dataset.deadline;
            document.getElementById("editTaskPriority").value = btn.dataset.priority;
            document.getElementById("editTaskDescription").value = btn.dataset.description;

            openModal("editTaskModal");
        });
    });

    // Delete Task Trigger
    document.querySelectorAll(".btn-delete-task-trigger").forEach(function (btn) {
        btn.addEventListener("click", function () {
            document.getElementById("deleteAction").value = "delete_task";
            document.getElementById("deleteTaskId").value = btn.dataset.id;
            document.getElementById("deleteFaeId").value = "";
            document.getElementById("deleteModalText").innerHTML = 
                "Are you sure you want to delete the task <strong>" + btn.dataset.name + "</strong>? This cannot be undone.";
            openModal("deleteModal");
        });
    });

    // =========================================================
    // TASK REPORTS & TIMELINE TRIGGER
    // =========================================================
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

                // Header
                document.getElementById("reportModalTaskTitle").textContent = task.task_name;
                
                const badgesWrap = document.getElementById("reportModalTaskBadges");
                const normStatus = (task.status || 'pending').toLowerCase().replace(/\s+/g, '');
                const statusBadgeClass = normStatus === 'inprogress' ? 'progress-badge' : (normStatus + '-badge');
                const priorityClass = 'priority-' + (task.priority || 'medium').toLowerCase();
                
                badgesWrap.innerHTML = 
                    '<span class="badge ' + statusBadgeClass + '">' + escapeHtml(task.status) + '</span> ' +
                    '<span class="priority-badge ' + priorityClass + '">' + escapeHtml(task.priority || 'Medium') + '</span>';

                // Summary
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

                // Render Timeline
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

    // =========================================================
    // SEARCH & FILTER ENGINE FOR TASKS TABLE
    // =========================================================
    const searchInput = document.getElementById("taskSearchInput");
    const statusFilter = document.getElementById("statusFilter");
    const faeFilter = document.getElementById("faeFilter");
    const priorityFilter = document.getElementById("priorityFilter");
    const rows = document.querySelectorAll(".task-table-row");

    function filterTasks() {
        const query = (searchInput ? searchInput.value : "").trim().toLowerCase();
        const selectedStatus = statusFilter ? statusFilter.value : "";
        const selectedFae = faeFilter ? faeFilter.value : "";
        const selectedPriority = priorityFilter ? priorityFilter.value : "";

        let visibleCount = 0;

        rows.forEach(function (row) {
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

            const matchesStatus = !selectedStatus || status === selectedStatus;
            const matchesFae = !selectedFae || faeId === selectedFae;
            const matchesPriority = !selectedPriority || priority === selectedPriority;

            if (matchesQuery && matchesStatus && matchesFae && matchesPriority) {
                row.style.display = "";
                visibleCount++;
            } else {
                row.style.display = "none";
            }
        });
    }

    if (searchInput) searchInput.addEventListener("input", filterTasks);
    if (statusFilter) statusFilter.addEventListener("change", filterTasks);
    if (faeFilter) faeFilter.addEventListener("change", filterTasks);
    if (priorityFilter) priorityFilter.addEventListener("change", filterTasks);

    // Filter by FAE Button on Card (User Mode)
    document.querySelectorAll(".filter-tasks-by-fae-btn").forEach(function (btn) {
        btn.addEventListener("click", function () {
            const faeId = btn.getAttribute("data-fae-id");
            if (faeFilter) {
                faeFilter.value = faeId;
                filterTasks();
                const table = document.getElementById("tasksTable");
                if (table) table.scrollIntoView({ behavior: "smooth", block: "start" });
            }
        });
    });

    // Smooth scroll for nav FAE link
    const navFaeLink = document.getElementById("navFaeLink");
    if (navFaeLink) {
        navFaeLink.addEventListener("click", function (e) {
            const faeSec = document.getElementById("fae-section");
            if (faeSec) {
                e.preventDefault();
                faeSec.scrollIntoView({ behavior: "smooth" });
            }
        });
    }

});
</script>

</body>
</html>
