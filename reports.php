<?php
session_start();

// Protect page - only logged-in admin can access
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];

// Load profile avatar + sync name for sidebar
$sidebarAvatar = '';
$profilesFileSidebar = __DIR__ . '/profiles.json';
if (file_exists($profilesFileSidebar)) {
    $allProfiles = json_decode(file_get_contents($profilesFileSidebar), true) ?? [];
    $key = strtolower($user['email'] ?? $user['name'] ?? '');
    if (isset($allProfiles[$key]['avatar']) && $allProfiles[$key]['avatar'] && file_exists(__DIR__ . '/' . $allProfiles[$key]['avatar'])) {
        $sidebarAvatar = $allProfiles[$key]['avatar'];
    }
    // Show updated full name from Profile on every page
    if (!empty($allProfiles[$key]['full_name'])) {
        $_SESSION['user']['name'] = $allProfiles[$key]['full_name'];
        $user['name'] = $allProfiles[$key]['full_name'];
    }
}

$appointmentsFile = __DIR__ . '/appointments.json';

// Load appointments
$appointments = [];
if (file_exists($appointmentsFile)) {
    $appointments = json_decode(file_get_contents($appointmentsFile), true) ?? [];
}

$total = count($appointments);

// Count by status
$counts = [
    'confirmed'  => 0,
    'checked-in' => 0,
    'pending'    => 0,
    'completed'  => 0,
    'no-show'    => 0,
    'cancelled'  => 0,
];

foreach ($appointments as $appt) {
    $status = strtolower($appt['status'] ?? '');
    if (isset($counts[$status])) {
        $counts[$status]++;
    }
}

// Calculate percentages
function pct($count, $total)
{
    if ($total === 0) return 0;
    return round(($count / $total) * 100);
}

$percentages = [];
foreach ($counts as $key => $count) {
    $percentages[$key] = pct($count, $total);
}

// Summary metrics
$completionRate   = pct($counts['completed'], $total);
$noShowRate       = pct($counts['no-show'], $total);
$cancellationRate = pct($counts['cancelled'], $total);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - JJJ GentleCare</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        /* ========== REPORTS PAGE STYLES ========== */
        .reports-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr;
            gap: 24px;
        }

        .report-card {
            background: white;
            border-radius: 18px;
            padding: 28px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
        }

        .report-card h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 24px;
        }

        /* Status Breakdown */
        .status-row {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 18px;
        }

        .status-row:last-child {
            margin-bottom: 0;
        }

        .status-label {
            width: 110px;
            font-size: 0.9rem;
            font-weight: 500;
            color: #475569;
            flex-shrink: 0;
        }

        .status-bar-wrap {
            flex: 1;
            height: 10px;
            background: #f1f5f9;
            border-radius: 9999px;
            overflow: hidden;
        }

        .status-bar {
            height: 100%;
            border-radius: 9999px;
            transition: width 0.4s ease;
        }

        .status-bar.confirmed {
            background: #10b981;
        }

        .status-bar.checked-in {
            background: #14b8a6;
        }

        .status-bar.pending {
            background: #f59e0b;
        }

        .status-bar.completed {
            background: #3b82f6;
        }

        .status-bar.no-show {
            background: #94a3b8;
        }

        .status-bar.cancelled {
            background: #ef4444;
        }

        .status-count {
            width: 70px;
            text-align: right;
            font-size: 0.9rem;
            font-weight: 600;
            color: #1e293b;
            flex-shrink: 0;
        }

        /* Summary Card */
        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .summary-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .summary-item:first-child {
            padding-top: 0;
        }

        .summary-label {
            font-size: 0.95rem;
            color: #64748b;
        }

        .summary-label small {
            display: block;
            font-size: 0.8rem;
            color: #94a3b8;
            margin-top: 2px;
        }

        .summary-value {
            font-size: 1.4rem;
            font-weight: 700;
            color: #1e293b;
        }

        .summary-value.green {
            color: #059669;
        }

        .summary-value.gray {
            color: #64748b;
        }

        .summary-value.red {
            color: #dc2626;
        }

        /* Responsive */
        @media (max-width: 900px) {
            .reports-grid {
                grid-template-columns: 1fr;
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
                <a href="reports.php" class="nav-item active">
                    <span class="nav-icon">📊</span> Reports
                </a>
                <a href="activity.php" class="nav-item">
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
                    <h1>Reports</h1>
                    <p class="date">Clinic performance overview</p>
                </div>
            </header>

            <div class="reports-grid">
                <!-- Appointment Status Breakdown -->
                <div class="report-card">
                    <h3>Appointment Status Breakdown</h3>

                    <?php
                    $statusOrder = [
                        'confirmed'  => 'Confirmed',
                        'checked-in' => 'Checked-in',
                        'pending'    => 'Pending',
                        'completed'  => 'Completed',
                        'no-show'    => 'No-show',
                        'cancelled'  => 'Cancelled',
                    ];
                    foreach ($statusOrder as $key => $label):
                        $count = $counts[$key];
                        $pct   = $percentages[$key];
                    ?>
                        <div class="status-row">
                            <div class="status-label"><?php echo $label; ?></div>
                            <div class="status-bar-wrap">
                                <div class="status-bar <?php echo $key; ?>" style="width: <?php echo $pct; ?>%;"></div>
                            </div>
                            <div class="status-count"><?php echo $count; ?> (<?php echo $pct; ?>%)</div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Summary -->
                <div class="report-card">
                    <h3>Summary</h3>

                    <div class="summary-item">
                        <div class="summary-label">
                            Total Appointments
                            <small>All time</small>
                        </div>
                        <div class="summary-value"><?php echo $total; ?></div>
                    </div>

                    <div class="summary-item">
                        <div class="summary-label">
                            Completion Rate
                            <small>Of all appointments</small>
                        </div>
                        <div class="summary-value green"><?php echo $completionRate; ?>%</div>
                    </div>

                    <div class="summary-item">
                        <div class="summary-label">
                            No-show Rate
                            <small>Of all appointments</small>
                        </div>
                        <div class="summary-value gray"><?php echo $noShowRate; ?>%</div>
                    </div>

                    <div class="summary-item">
                        <div class="summary-label">
                            Cancellation Rate
                            <small>Of all appointments</small>
                        </div>
                        <div class="summary-value red"><?php echo $cancellationRate; ?>%</div>
                    </div>
                </div>
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