<?php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function currentRole(): ?string
{
    return $_SESSION['monitoring_role'] ?? null;
}

function isAdmin(): bool
{
    return currentRole() === 'admin';
}

function currentFaeId(): ?int
{
    return currentRole() === 'fae' ? (int)($_SESSION['monitoring_fae_id'] ?? 0) : null;
}

function currentFaeName(): string
{
    return (string)($_SESSION['monitoring_fae_name'] ?? 'FAE User');
}

function currentFaeCode(): string
{
    return (string)($_SESSION['monitoring_fae_code'] ?? '');
}

function notificationItems(PDO $pdo, bool $admin, ?int $faeId): array
{
    $items = [];
    // Only Admin receives notifications for pending appointment requests from FAEs
    if ($admin) {
        $appointmentSql = "SELECT 'appointment' AS type, reason AS title, appointment_date AS item_date FROM appointments WHERE status = 'pending' ORDER BY created_at DESC";
        $appointmentStmt = $pdo->query($appointmentSql);
        foreach ($appointmentStmt->fetchAll() as $item) {
            $items[] = ['type' => 'Appointment request', 'title' => $item['title'], 'date' => $item['item_date']];
        }

        // Recent FAE Task Reports
        try {
            $updateSql = "SELECT tu.message, tu.author_name, t.task_name, tu.created_at 
                          FROM task_updates tu 
                          JOIN tasks t ON tu.task_id = t.id 
                          WHERE tu.author_role = 'fae' 
                          ORDER BY tu.created_at DESC LIMIT 5";
            $updateStmt = $pdo->query($updateSql);
            foreach ($updateStmt->fetchAll() as $up) {
                $items[] = ['type' => 'FAE Progress Report', 'title' => $up['author_name'] . ' on "' . $up['task_name'] . '": ' . substr($up['message'], 0, 50), 'date' => $up['created_at']];
            }
        } catch (Exception $e) {}
    }

    if (!$admin && $faeId) {
        $assignedTaskStmt = $pdo->prepare("SELECT task_name AS title, deadline AS item_date FROM tasks WHERE fae_id = ? AND status IN ('Pending', 'In Progress') ORDER BY created_at DESC");
        $assignedTaskStmt->execute([$faeId]);
        foreach ($assignedTaskStmt->fetchAll() as $item) {
            $items[] = ['type' => 'Assigned task', 'title' => $item['title'], 'date' => $item['item_date']];
        }

        // Admin notes on FAE tasks
        try {
            $adminNoteStmt = $pdo->prepare("SELECT tu.message, t.task_name, tu.created_at 
                                            FROM task_updates tu 
                                            JOIN tasks t ON tu.task_id = t.id 
                                            WHERE t.fae_id = ? AND tu.author_role = 'admin' 
                                            ORDER BY tu.created_at DESC LIMIT 5");
            $adminNoteStmt->execute([$faeId]);
            foreach ($adminNoteStmt->fetchAll() as $an) {
                $items[] = ['type' => 'Admin Remark', 'title' => 'Admin on "' . $an['task_name'] . '": ' . substr($an['message'], 0, 50), 'date' => $an['created_at']];
            }
        } catch (Exception $e) {}
    }

    $taskSql = "SELECT 'task' AS type, task_name AS title, deadline AS item_date FROM tasks WHERE (status = 'Overdue' OR (deadline < CURDATE() AND status != 'Completed'))" . ($admin ? '' : ' AND fae_id = ?');
    $taskStmt = $pdo->prepare($taskSql);
    $taskStmt->execute($admin ? [] : [$faeId]);
    foreach ($taskStmt->fetchAll() as $item) {
        $items[] = ['type' => 'Overdue task', 'title' => $item['title'], 'date' => $item['item_date']];
    }
    return $items;
}

function notificationMarkup(array $items): string
{
    $count = count($items);
    $notificationKey = hash('sha256', json_encode($items));
    $html = '<div class="notification-wrap" id="notificationWrap" data-notification-key="' . htmlspecialchars($notificationKey) . '">';
    $html .= '<button type="button" class="icon-button notification-button" id="notificationBtn" title="Notifications" aria-label="Notifications" onclick="event.stopPropagation(); document.getElementById(\'notificationWrap\').classList.toggle(\'notification-open\');">';
    $html .= '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="display:block; color:var(--text-light);"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>';
    if ($count > 0) {
        $html .= '<span class="notification-count">' . ($count > 99 ? '99+' : $count) . '</span>';
    }
    $html .= '</button>';
    $html .= '<div class="notification-menu" onclick="event.stopPropagation();">';
    $html .= '<div class="notification-header" style="display:flex; align-items:center; justify-content:space-between; padding-bottom:8px; border-bottom:1px solid var(--border); margin-bottom:4px;">';
    $html .= '<strong style="font-size:12px; font-weight:700;">Notifications</strong>';
    if ($count > 0) {
        $html .= '<span style="font-size:10px; font-weight:700; color:var(--primary); background:rgba(181,47,50,0.1); padding:2px 6px; border-radius:10px;">' . $count . ' active</span>';
    }
    $html .= '</div>';
    if ($count === 0) {
        $html .= '<span class="notification-empty">No new notifications.</span>';
    } else {
        foreach (array_slice($items, 0, 8) as $item) {
            $html .= '<div class="notification-item">';
            $html .= '<span style="color:var(--primary); font-size:9px; font-weight:700; text-transform:uppercase;">' . htmlspecialchars($item['type']) . '</span>';
            $html .= '<strong style="font-size:11px; line-height:1.35; color:var(--text);">' . htmlspecialchars($item['title']) . '</strong>';
            if (!empty($item['date'])) {
                $html .= '<small style="color:var(--text-light); font-size:9px;">' . htmlspecialchars(date('M d, Y', strtotime($item['date']))) . '</small>';
            }
            $html .= '</div>';
        }
    }
    $html .= '</div></div>';
    $html .= '<script>
        var notificationWrap = document.getElementById("notificationWrap");
        var notificationKey = notificationWrap ? notificationWrap.getAttribute("data-notification-key") : "";
        var viewedKey = localStorage.getItem("monitoring_notifications_viewed_key");
        var countBadge = document.querySelector(".notification-count");
        if (countBadge && notificationKey && viewedKey === notificationKey) {
            countBadge.style.display = "none";
        }

        document.addEventListener("click", function(e) {
            var wrap = document.getElementById("notificationWrap");
            if (wrap && !wrap.contains(e.target)) {
                wrap.classList.remove("notification-open");
            }
        });

        var notificationBtn = document.getElementById("notificationBtn");
        if (notificationBtn) {
            notificationBtn.addEventListener("click", function() {
                var countBadge = this.querySelector(".notification-count");
                if (countBadge) {
                    countBadge.style.display = "none";
                    if (notificationKey) {
                        localStorage.setItem("monitoring_notifications_viewed_key", notificationKey);
                    }
                }
            });
        }
    </script>';
    return $html;
}

function requireAccess(): void
{
    if (!currentRole()) {
        header('Location: access.php');
        exit();
    }
}

function accessRedirect(string $message = ''): void
{
    $suffix = $message !== '' ? '?err=' . urlencode($message) : '';
    header('Location: access.php' . $suffix);
    exit();
}

function adminAccessKey(): string
{
    // Change this value if MONITORING_ADMIN_KEY is not configured in Apache/PHP.
    $fallbackAdminKey = 'Admin12345!';
    return getenv('MONITORING_ADMIN_KEY') ?: $fallbackAdminKey;
}

?>
