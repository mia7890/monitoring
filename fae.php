<?php
require_once 'db.php';
require_once 'auth.php';

$faeCode = strtoupper(trim($_GET['code'] ?? ''));
if ($faeCode === '') {
    requireAccess();
    if (!isAdmin()) {
        header('Location: tasks.php');
        exit();
    }
} else {
    $stmt = $pdo->prepare('SELECT id, name FROM fae_users WHERE fae_code = ?');
    $stmt->execute([$faeCode]);
    $fae = $stmt->fetch();
    if (!$fae) {
        accessRedirect('This FAE link is invalid or no longer active.');
    }
    $_SESSION['monitoring_role'] = 'fae';
    $_SESSION['monitoring_fae_id'] = (int)$fae['id'];
    $_SESSION['monitoring_fae_name'] = $fae['name'];
    $_SESSION['monitoring_fae_code'] = $faeCode;
    header('Location: index.php');
    exit();
}

$faeQuery = $pdo->query("SELECT f.*, COUNT(t.id) AS total_tasks,
    COALESCE(AVG(t.progress), 0) AS avg_progress,
    SUM(CASE WHEN t.status = 'Completed' THEN 1 ELSE 0 END) AS completed_tasks
    FROM fae_users f LEFT JOIN tasks t ON t.fae_id = f.id
    GROUP BY f.id ORDER BY f.name ASC");
$faeList = $faeQuery->fetchAll();
$message = $_GET['msg'] ?? '';
$error = $_GET['err'] ?? '';
$appBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/monitoring')), '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAE Team | Monitoring System</title>
    <link rel="stylesheet" href="<?= $appBase ?>/style.css?v=<?= time() ?>">
</head>
<body>
<div class="app">
    <aside class="sidebar" id="sidebar">
        <div class="logo"><div class="logo-icon"><img src="logo.png" alt="Monitoring System logo"></div><div class="logo-text"><h2>Hytec Power inc.</h2><span>Monitoring</span></div></div>
        <nav class="navigation">
            <a href="index.php" class="nav-item"><span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg></span><span>Dashboard</span></a>
            <a href="fae.php" class="nav-item active"><span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg></span><span>FAE</span></a>
            <a href="tasks.php" class="nav-item"><span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg></span><span>Tasks</span></a>
            <a href="calendar.php" class="nav-item"><span class="nav-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg></span><span>Calendar</span></a>
        </nav>
        <div class="sidebar-bottom"><div class="sidebar-footer"><span>© 2026 Hytec Power Inc.</span></div></div>
    </aside>
    <main class="main">
        <header class="topbar">
            <button class="menu-toggle" id="menuToggle">☰</button>
            <div class="breadcrumb"><span>Home</span><b>/</b><strong>FAE Team</strong></div>
            <div class="topbar-right"><a href="access.php?logout=1" class="role-tag admin" style="text-decoration:none;">Sign out</a><div class="profile"><div class="avatar">AD</div><div class="profile-info"><strong>Administrator</strong><span>Full Management</span></div></div></div>
        </header>
        <section class="content">
            <?php if ($message || $error): ?><div class="alert-banner <?= $error ? 'error' : 'success' ?>"><?= htmlspecialchars($message ?: $error) ?></div><?php endif; ?>
            <div class="page-intro"><div><span class="welcome-label">TEAM MANAGEMENT</span><h1>FAE Team</h1><p>Manage field engineers and assign work directly from their profiles.</p></div><a href="tasks.php" class="outline-button" style="text-decoration:none;">View tasks</a></div>
            <div class="panel">
                <div class="panel-header"><div><h2>Team Members</h2><p><?= count($faeList) ?> registered field application engineers</p></div></div>
                <form method="post" action="tasks.php" class="fae-add-form">
                    <input type="hidden" name="action" value="add_fae">
                    <input class="form-control" name="name" required placeholder="Full name">
                    <input class="form-control" name="fae_code" required placeholder="FAE code">
                    <input class="form-control" name="department" placeholder="Department">
                    <button class="primary-button btn-sm" type="submit">＋ Add FAE</button>
                </form>
                <div class="fae-grid">
                    <?php foreach ($faeList as $fae): ?>
                        <article class="fae-card">
                            <div><div class="fae-card-top"><div class="fae-avatar-box" style="background:var(--primary);color:white;"><?php if (!empty($fae['profile_image'])): ?><img class="fae-profile-image" src="<?= htmlspecialchars($fae['profile_image']) ?>" alt="<?= htmlspecialchars($fae['name']) ?> profile picture"><?php else: ?><?= htmlspecialchars(strtoupper(substr($fae['name'], 0, 2))) ?><?php endif; ?></div><div class="fae-info"><h3><?= htmlspecialchars($fae['name']) ?></h3><span class="fae-code-badge"><?= htmlspecialchars($fae['fae_code']) ?></span><div class="fae-dept"><?= htmlspecialchars($fae['department'] ?? 'Field Engineering') ?></div></div></div><div class="fae-stats-row"><div class="fae-stat-col"><span>Tasks</span><strong><?= (int)$fae['total_tasks'] ?></strong></div><div class="fae-stat-col"><span>Completed</span><strong style="color:var(--green);"><?= (int)$fae['completed_tasks'] ?></strong></div></div><div class="fae-progress-bar-wrap"><div class="fae-progress-label"><span>Average progress</span><strong><?= round($fae['avg_progress']) ?>%</strong></div><div class="progress-bar"><div style="width:<?= round($fae['avg_progress']) ?>%;"></div></div></div></div>
                            <div class="fae-card-actions"><a href="tasks.php?assign_fae=<?= (int)$fae['id'] ?>" class="btn-add-task-fae" style="text-decoration:none;">＋ Add task for <?= htmlspecialchars($fae['name']) ?></a><a href="fae.php?code=<?= urlencode($fae['fae_code']) ?>" class="outline-button" target="_blank" rel="noopener" style="text-decoration:none;">Open FAE link</a><form method="post" action="tasks.php" onsubmit="return confirm('Remove this FAE? Assigned tasks will become unassigned.');"><input type="hidden" name="action" value="delete_fae"><input type="hidden" name="fae_id" value="<?= (int)$fae['id'] ?>"><button type="submit" class="btn-danger-outline btn-sm">Remove FAE</button></form></div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
            <footer><span>Monitoring System © 2026</span><span>FAE management</span></footer>
        </section>
    </main>
</div>
<script>const menuToggle=document.getElementById('menuToggle');const sidebar=document.getElementById('sidebar');if(menuToggle&&sidebar){menuToggle.addEventListener('click',()=>sidebar.classList.toggle('open'));}</script>
</body>
</html>
