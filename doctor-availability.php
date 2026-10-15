<?php
session_start();

// Protect page - only doctor can access
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
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

$availabilityFile = __DIR__ . '/availability.json';

// Default structure
$defaultAvailability = [
    'MON' => ['8 AM' => 'open', '9 AM' => 'open', '10 AM' => 'open', '11 AM' => 'open', '1 PM' => 'open', '2 PM' => 'open', '3 PM' => 'open', '4 PM' => 'open'],
    'TUE' => ['8 AM' => 'open', '9 AM' => 'open', '10 AM' => 'open', '11 AM' => 'open', '1 PM' => 'open', '2 PM' => 'open', '3 PM' => 'open', '4 PM' => 'open'],
    'WED' => ['8 AM' => 'open', '9 AM' => 'open', '10 AM' => 'open', '11 AM' => 'open', '1 PM' => 'open', '2 PM' => 'open', '3 PM' => 'open', '4 PM' => 'open'],
    'THU' => ['8 AM' => 'open', '9 AM' => 'open', '10 AM' => 'open', '11 AM' => 'open', '1 PM' => 'open', '2 PM' => 'open', '3 PM' => 'open', '4 PM' => 'open'],
    'FRI' => ['8 AM' => 'open', '9 AM' => 'open', '10 AM' => 'open', '11 AM' => 'open', '1 PM' => 'open', '2 PM' => 'open', '3 PM' => 'open', '4 PM' => 'open'],
];

// Load availability
$availability = $defaultAvailability;
if (file_exists($availabilityFile)) {
    $loaded = json_decode(file_get_contents($availabilityFile), true);
    if ($loaded) {
        $availability = $loaded;
    }
}

// Handle toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle') {
    $day  = $_POST['day'] ?? '';
    $time = $_POST['time'] ?? '';

    $validDays  = ['MON', 'TUE', 'WED', 'THU', 'FRI'];
    $validTimes = ['8 AM', '9 AM', '10 AM', '11 AM', '1 PM', '2 PM', '3 PM', '4 PM'];

    if (in_array($day, $validDays) && in_array($time, $validTimes)) {
        $current = $availability[$day][$time] ?? 'open';
        $availability[$day][$time] = ($current === 'open') ? 'blocked' : 'open';
        file_put_contents($availabilityFile, json_encode($availability, JSON_PRETTY_PRINT));
    }

    header('Location: doctor-availability.php');
    exit;
}

$days  = ['MON', 'TUE', 'WED', 'THU', 'FRI'];
$times = ['8 AM', '9 AM', '10 AM', '11 AM', '1 PM', '2 PM', '3 PM', '4 PM'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Availability - JJJ GentleCare</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        /* ========== AVAILABILITY STYLES ========== */
        .availability-card {
            background: white;
            border-radius: 18px;
            padding: 32px 36px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
            width: 100%;
            max-width: 960px;
            margin: 0 auto;
        }

        .avail-grid {
            display: grid;
            grid-template-columns: 80px repeat(5, 1fr);
            gap: 12px;
            margin-bottom: 28px;
            width: 100%;
        }

        .avail-header {
            text-align: center;
            font-size: 0.8rem;
            font-weight: 700;
            color: #64748b;
            letter-spacing: 0.04em;
            padding: 8px 0;
        }

        .avail-time {
            font-size: 0.85rem;
            font-weight: 600;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            padding-right: 8px;
        }

        .slot-btn {
            display: block;
            width: 100%;
            padding: 16px 10px;
            border-radius: 12px;
            font-size: 0.9rem;
            font-weight: 600;
            text-align: center;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: inherit;
            min-height: 48px;
        }

        .slot-btn.open {
            background: #ccfbf1;
            color: #0f766e;
        }

        .slot-btn.open:hover {
            background: #99f6e4;
            transform: scale(1.04);
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.2);
        }

        .slot-btn.blocked {
            background: #f1f5f9;
            color: #94a3b8;
        }

        .slot-btn.blocked:hover {
            background: #e2e8f0;
            transform: scale(1.04);
        }

        .legend {
            display: flex;
            gap: 24px;
            font-size: 0.85rem;
            color: #64748b;
            justify-content: center;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .legend-swatch {
            width: 18px;
            height: 18px;
            border-radius: 6px;
        }

        .legend-swatch.open {
            background: #ccfbf1;
            border: 1px solid #99f6e4;
        }

        .legend-swatch.blocked {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
        }

        @media (max-width: 700px) {
            .availability-card {
                padding: 16px;
                overflow-x: auto;
            }

            .avail-grid {
                min-width: 520px;
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
                    <div class="user-role">Physician</div>
                </div>
            </div>

            <nav class="nav">
                <a href="doctor-dashboard.php" class="nav-item">
                    <span class="nav-icon">▦</span> Dashboard
                </a>
                <a href="doctor-calendar.php" class="nav-item">
                    <span class="nav-icon">📅</span> My Calendar
                </a>
                <a href="doctor-patients.php" class="nav-item">
                    <span class="nav-icon">👥</span> My Patients
                </a>
                <a href="doctor-availability.php" class="nav-item active">
                    <span class="nav-icon">🕒</span> Availability
                </a>
                <a href="doctor-profile.php" class="nav-item">
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
                    <h1>My Availability</h1>
                    <p class="date">Click slots to toggle availability</p>
                </div>
            </header>

            <div class="availability-card">
                <div class="avail-grid">
                    <!-- Header row -->
                    <div></div>
                    <?php foreach ($days as $day): ?>
                        <div class="avail-header"><?php echo $day; ?></div>
                    <?php endforeach; ?>

                    <!-- Time rows -->
                    <?php foreach ($times as $time): ?>
                        <div class="avail-time"><?php echo $time; ?></div>
                        <?php foreach ($days as $day): ?>
                            <?php
                            $status = $availability[$day][$time] ?? 'open';
                            $label  = $status === 'open' ? 'Open' : 'Blocked';
                            ?>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="day" value="<?php echo $day; ?>">
                                <input type="hidden" name="time" value="<?php echo $time; ?>">
                                <button type="submit" class="slot-btn <?php echo $status; ?>">
                                    <?php echo $label; ?>
                                </button>
                            </form>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Legend -->
                <div class="legend">
                    <div class="legend-item">
                        <div class="legend-swatch open"></div>
                        <span>Available</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-swatch blocked"></div>
                        <span>Blocked</span>
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