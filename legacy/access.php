<?php
require_once 'db.php';
require_once 'auth.php';

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: access.php');
    exit();
}

$error = $_GET['err'] ?? '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $accessType = $_POST['access_type'] ?? '';

    if ($accessType === 'admin') {
        if (hash_equals(adminAccessKey(), (string)($_POST['admin_key'] ?? ''))) {
            $_SESSION['monitoring_role'] = 'admin';
            unset($_SESSION['monitoring_fae_id']);
            header('Location: index.php');
            exit();
        }
        $error = 'The admin access key is not valid.';
    }

    if ($accessType === 'fae') {
        $faeCode = strtoupper(trim($_POST['fae_code'] ?? ''));
        $stmt = $pdo->prepare('SELECT id, name, fae_code FROM fae_users WHERE fae_code = ?');
        $stmt->execute([$faeCode]);
        $fae = $stmt->fetch();
        if ($fae) {
            $_SESSION['monitoring_role'] = 'fae';
            $_SESSION['monitoring_fae_id'] = (int)$fae['id'];
            $_SESSION['monitoring_fae_name'] = $fae['name'];
            $_SESSION['monitoring_fae_code'] = $fae['fae_code'];
            header('Location: index.php');
            exit();
        }
        $error = 'That FAE code was not found.';
    }
}

$appBase = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/monitoring')), '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access | Monitoring System</title>
    <link rel="stylesheet" href="<?= $appBase ?>/style.css?v=<?= time() ?>">
</head>
<body class="access-page">
    <main class="access-shell">
        <div class="access-mark"><img src="logo.png" alt="Monitoring System logo"></div>
        <p class="welcome-label">MONITORING SYSTEM</p>
        <h1>Choose your workspace</h1>
        <p class="access-intro">Use the access details provided by your supervisor.</p>
        <?php if ($error): ?>
            <div class="alert-banner error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="access-grid">
            <form method="post" class="access-card">
                <input type="hidden" name="access_type" value="fae">
                <span class="access-kicker">FIELD ENGINEER</span>
                <h2>FAE workspace</h2>
                <p>See only your assigned work and request time with the admin.</p>
                <label class="form-label" for="fae_code">FAE code</label>
                <input class="form-control" id="fae_code" name="fae_code" required autocomplete="off" placeholder="e.g. FAE-001">
                <button class="primary-button access-button" type="submit">Open my workspace</button>
            </form>

            <form method="post" class="access-card access-card--admin">
                <input type="hidden" name="access_type" value="admin">
                <span class="access-kicker">SUPERVISOR</span>
                <h2>Admin workspace</h2>
                <p>Manage FAEs, assign tasks, review progress, and handle appointments.</p>
                <label class="form-label" for="admin_key">Admin access key</label>
                <input class="form-control" id="admin_key" name="admin_key" type="password" required autocomplete="current-password">
                <button class="primary-button access-button" type="submit">Open admin workspace</button>
            </form>
        </div>
       
    </main>
</body>
</html>
