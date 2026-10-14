<?php
session_start();

// Protect page - only logged-in admin can access
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];

// Load profile avatar for sidebar
$sidebarAvatar = '';
$profilesFileSidebar = __DIR__ . '/profiles.json';
if (file_exists($profilesFileSidebar)) {
    $allProfiles = json_decode(file_get_contents($profilesFileSidebar), true) ?? [];
    $key = strtolower($user['email'] ?? $user['name'] ?? '');
    if (isset($allProfiles[$key]['avatar']) && $allProfiles[$key]['avatar'] && file_exists(__DIR__ . '/' . $allProfiles[$key]['avatar'])) {
        $sidebarAvatar = $allProfiles[$key]['avatar'];
    }
}

$activityFile = __DIR__ . '/activity.json';

// Load activity log
$activities = [];
if (file_exists($activityFile)) {
    $activities = json_decode(file_get_contents($activityFile), true) ?? [];
}

// Icon mapping based on type
function getActivityIcon($type)
{
    switch ($type) {
        case 'success':
            return '✓';
        case 'danger':
            return '✕';
        case 'info':
            return '+';
        case 'primary':
            return '●';
        case 'warning':
            return '✕';
        default:
            return '•';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - JJJ GentleCare</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        /* ========== ACTIVITY LOG STYLES ========== */
        .activity-list {
            background: white;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 18px 24px;
            border-bottom: 1px solid #f1f5f9;
            transition: background 0.15s;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-item:hover {
            background: #f8fafc;
        }

        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 0.9rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .activity-icon.success {
            background: #ecfdf5;
            color: #059669;
        }

        .activity-icon.danger {
            background: #fef2f2;
            color: #dc2626;
        }

        .activity-icon.info {
            background: #ecfdf5;
            color: #0d9488;
        }

        .activity-icon.primary {
            background: #eff6ff;
            color: #2563eb;
        }

        .activity-icon.warning {
            background: #fef2f2;
            color: #ef4444;
        }

        .activity-content {
            flex: 1;
        }

        .activity-text {
            font-size: 0.95rem;
            color: #1e293b;
        }

        .activity-text strong {
            font-weight: 600;
        }

        .activity-time {
            font-size: 0.85rem;
            color: #94a3b8;
            flex-shrink: 0;
            white-space: nowrap;
        }

        @media (max-width: 600px) {
            .activity-item {
                flex-wrap: wrap;
                gap: 10px;
            }

            .activity-time {
                width: 100%;
                padding-left: 52px;
            }
        }
    </style>
</head>

<body>
    <div class="layout">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <div class="logo-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="#0d9488" />
                        </svg>
                    </div>
                    <div>
                        <div class="logo-text">JJJ GentleCare</div>
                        <div class="logo-sub">Clinic System</div>
                    </div>
                </div>
            </div>

            <div class="user-card">
                <div class="avatar" style="overflow:hidden;padding:0;">
                    <?php if (!empty($sidebarAvatar)): ?>
                        <img src="<?php echo htmlspecialchars($sidebarAvatar); ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;">
                    <?php else: ?>
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                    <div class="user-role"><?php echo ucfirst($user['role']); ?></div>
                </div>
            </div>

            <nav class="nav">
                <a href="dashboard.php" class="nav-item">
                    <span class="nav-icon">▦</span> Dashboard
                </a>
                <a href="users.php" class="nav-item">
                    <span class="nav-icon">👤</span> Users
                </a>
                <a href="appointments.php" class="nav-item">
                    <span class="nav-icon">📅</span> Appointments
                </a>
                <a href="schedules.php" class="nav-item">
                    <span class="nav-icon">🕒</span> Schedules
                </a>
                <a href="reports.php" class="nav-item">
                    <span class="nav-icon">📊</span> Reports
                </a>
                <a href="activity.php" class="nav-item active">
                    <span class="nav-icon">📋</span> Activity Log
                </a>
                <a href="settings.php" class="nav-item">
                    <span class="nav-icon">⚙️</span> Settings
                </a>
                <a href="profile.php" class="nav-item">
                    <span class="nav-icon">👤</span> Profile
                </a>
            </nav>

            <div class="sidebar-footer">
                <button type="button" class="logout-btn" onclick="openLogoutModal()">
                    <span>↪</span> Log Out
                </button>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main">
            <header class="topbar">
                <div>
                    <h1>Activity Log</h1>
                    <p class="date">Recent system activity</p>
                </div>
            </header>

            <div class="activity-list">
                <?php if (empty($activities)): ?>
                    <div class="activity-item">
                        <div class="activity-content" style="text-align:center; color:#94a3b8; width:100%;">
                            No activity recorded yet.
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($activities as $item): ?>
                        <div class="activity-item">
                            <div class="activity-icon <?php echo htmlspecialchars($item['type']); ?>">
                                <?php echo getActivityIcon($item['type']); ?>
                            </div>
                            <div class="activity-content">
                                <div class="activity-text">
                                    <strong><?php echo htmlspecialchars($item['actor']); ?></strong>
                                    <?php echo htmlspecialchars($item['action']); ?>
                                </div>
                            </div>
                            <div class="activity-time">
                                <?php echo htmlspecialchars($item['time']); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- LOGOUT MODAL -->
    <div class="modal-overlay" id="logoutModal">
        <div class="modal">
            <div class="modal-icon">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </div>
            <h3>Sign Out?</h3>
            <p>Are you sure you want to log out of JJJ GentleCare?</p>
            <div class="modal-actions">
                <button type="button" class="btn-stay" onclick="closeLogoutModal()">Stay</button>
                <a href="logout.php" class="btn-logout">Log Out</a>
            </div>
        </div>
    </div>

    <script>
        function openLogoutModal() {
            document.getElementById('logoutModal').classList.add('show');
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.remove('show');
        }
        document.getElementById('logoutModal').addEventListener('click', function(e) {
            if (e.target === this) closeLogoutModal();
        });
    </script>
</body>

</html>