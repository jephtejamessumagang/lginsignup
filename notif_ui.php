<?php
// Expects: $user already set, and log_notification.php already required
if (!isset($user)) {
    return;
}
if (!function_exists('get_user_notifications')) {
    require_once __DIR__ . '/log_notification.php';
}
$__notifs = get_user_notifications($user);
$__unread = 0;
foreach ($__notifs as $__n) {
    if (empty($__n['read'])) {
        $__unread++;
    }
}
// Mark read when panel is opened via ?mark_notifs=1
if (isset($_GET['mark_notifs']) && $_GET['mark_notifs'] === '1') {
    mark_notifications_read($user);
    $redir = strtok($_SERVER['REQUEST_URI'] ?? '', '?');
    header('Location: ' . $redir);
    exit;
}
?>
<style>
    .notif-wrap {
        position: relative;
        margin-left: auto;
    }

    .notif-btn {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        border: 1.5px solid #e2e8f0;
        background: #fff;
        cursor: pointer;
        display: grid;
        place-items: center;
        position: relative;
        font-size: 1.1rem;
        transition: all .15s;
    }

    .notif-btn:hover {
        border-color: #99f6e4;
        background: #f0fdfa;
    }

    .notif-badge {
        position: absolute;
        top: -4px;
        right: -4px;
        min-width: 18px;
        height: 18px;
        background: #ef4444;
        color: #fff;
        border-radius: 999px;
        font-size: 0.65rem;
        font-weight: 700;
        display: grid;
        place-items: center;
        padding: 0 4px;
    }

    .notif-panel {
        display: none;
        position: absolute;
        right: 0;
        top: 48px;
        width: 320px;
        max-height: 380px;
        overflow-y: auto;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 12px 32px rgba(15, 23, 42, .12);
        z-index: 50;
    }

    .notif-panel.show {
        display: block;
    }

    .notif-panel-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 14px;
        border-bottom: 1px solid #f1f5f9;
        font-weight: 700;
        font-size: 0.9rem;
    }

    .notif-panel-head a {
        font-size: 0.75rem;
        font-weight: 600;
        color: #0d9488;
        text-decoration: none;
    }

    .notif-item {
        padding: 12px 14px;
        border-bottom: 1px solid #f8fafc;
        font-size: 0.85rem;
        color: #334155;
    }

    .notif-item.unread {
        background: #f0fdfa;
    }

    .notif-item:last-child {
        border-bottom: none;
    }

    .notif-item .notif-time {
        font-size: 0.72rem;
        color: #94a3b8;
        margin-top: 4px;
    }

    .notif-empty {
        padding: 24px 14px;
        text-align: center;
        color: #94a3b8;
        font-size: 0.85rem;
    }

    .topbar {
        display: flex;
        align-items: flex-start;
        gap: 16px;
    }

    .topbar>div:first-child {
        flex: 1;
    }
</style>
<div class="notif-wrap">
    <button type="button" class="notif-btn" id="notifBtn" aria-label="Notifications">
        🔔
        <?php if ($__unread > 0): ?>
            <span class="notif-badge"><?php echo $__unread > 9 ? '9+' : $__unread; ?></span>
        <?php endif; ?>
    </button>
    <div class="notif-panel" id="notifPanel">
        <div class="notif-panel-head">
            <span>Notifications</span>
            <?php if ($__unread > 0): ?>
                <a href="?mark_notifs=1">Mark all read</a>
            <?php endif; ?>
        </div>
        <?php if (empty($__notifs)): ?>
            <div class="notif-empty">No notifications yet.</div>
        <?php else: ?>
            <?php foreach (array_slice($__notifs, 0, 20) as $__n): ?>
                <div class="notif-item <?php echo empty($__n['read']) ? 'unread' : ''; ?>">
                    <div><?php echo htmlspecialchars($__n['message'] ?? ''); ?></div>
                    <div class="notif-time"><?php echo htmlspecialchars(format_notif_time($__n)); ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<script>
    (function() {
        var btn = document.getElementById('notifBtn');
        var panel = document.getElementById('notifPanel');
        if (!btn || !panel) return;
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            panel.classList.toggle('show');
        });
        document.addEventListener('click', function(e) {
            if (!panel.contains(e.target) && e.target !== btn) {
                panel.classList.remove('show');
            }
        });
    })();
</script>