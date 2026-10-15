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

$appointmentsFile = __DIR__ . '/appointments.json';

// Load appointments
$appointments = [];
if (file_exists($appointmentsFile)) {
    $appointments = json_decode(file_get_contents($appointmentsFile), true) ?? [];
}

$doctorName = 'Dr. Vistal';

// Handle status update (Done)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $id     = (int)($_POST['id'] ?? 0);
    $status = strtolower(trim($_POST['status'] ?? ''));
    $redirectDay = $_POST['redirect_day'] ?? '2026-09-17';

    $allowed = ['completed', 'no-show'];
    if (in_array($status, $allowed)) {
        foreach ($appointments as &$a) {
            if ((int)$a['id'] === $id && $a['doctor'] === $doctorName) {
                $a['status'] = $status;
                break;
            }
        }
        unset($a);
        file_put_contents($appointmentsFile, json_encode(array_values($appointments), JSON_PRETTY_PRINT));
    }
    header('Location: doctor-calendar.php?day=' . urlencode($redirectDay));
    exit;
}

// Selected day (default to 2026-09-17)
$selectedDay = isset($_GET['day']) ? $_GET['day'] : '2026-09-17';
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $selectedDay)) {
    $selectedDay = '2026-09-17';
}

// Week range (Sep 13-19, 2026) to match screenshot
$weekStart = new DateTime('2026-09-13');
$weekDays = [];
for ($i = 0; $i < 7; $i++) {
    $d = clone $weekStart;
    $d->modify("+$i days");
    $weekDays[] = [
        'date' => $d->format('Y-m-d'),
        'day'  => $d->format('j'),          // 13, 14, ...
        'dow'  => strtoupper($d->format('D')), // SUN, MON...
        'full' => $d->format('F j'),        // September 17
    ];
}

// Count appointments per day for the week (for dots)
$apptCountByDay = [];
foreach ($appointments as $appt) {
    if ($appt['doctor'] !== $doctorName) continue;
    $datePart = substr($appt['datetime'], 0, 10);
    if (!isset($apptCountByDay[$datePart])) {
        $apptCountByDay[$datePart] = 0;
    }
    $apptCountByDay[$datePart]++;
}

// Appointments for selected day
$dayAppointments = [];
foreach ($appointments as $appt) {
    if ($appt['doctor'] !== $doctorName) continue;
    if (strpos($appt['datetime'], $selectedDay) === 0) {
        $dayAppointments[] = $appt;
    }
}

// Sort by time
usort($dayAppointments, function ($a, $b) {
    return strcmp($a['datetime'], $b['datetime']);
});

$selectedFull = '';
foreach ($weekDays as $wd) {
    if ($wd['date'] === $selectedDay) {
        $selectedFull = $wd['full'];
        break;
    }
}
if (!$selectedFull) {
    $selectedFull = date('F j', strtotime($selectedDay));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Calendar - JJJ GentleCare</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        /* ========== DOCTOR CALENDAR STYLES ========== */
        .week-strip {
            display: flex;
            gap: 8px;
            background: white;
            border-radius: 16px;
            padding: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
            margin-bottom: 28px;
            overflow-x: auto;
        }

        .week-day {
            flex: 1;
            min-width: 64px;
            text-align: center;
            padding: 12px 8px;
            border-radius: 12px;
            text-decoration: none;
            color: #64748b;
            transition: all 0.15s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
        }

        .week-day:hover {
            background: #f0fdfa;
            color: #0d9488;
        }

        .week-day.active {
            background: #0d9488;
            color: white;
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
        }

        .week-day .dow {
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.03em;
        }

        .week-day .day-num {
            font-size: 1.15rem;
            font-weight: 700;
        }

        .week-day .dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: #0d9488;
            margin-top: 2px;
        }

        .week-day.active .dot {
            background: white;
        }

        .week-day.no-appt .dot {
            visibility: hidden;
        }

        .day-header {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 18px;
        }

        .calendar-list {
            background: white;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }

        .cal-row {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px 22px;
            border-bottom: 1px solid #f1f5f9;
        }

        .cal-row:last-child {
            border-bottom: none;
        }

        .cal-time {
            width: 60px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #64748b;
            flex-shrink: 0;
            line-height: 1.3;
        }

        .cal-avatar {
            width: 40px;
            height: 40px;
            background: #ccfbf1;
            color: #0f766e;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .cal-info {
            flex: 1;
        }

        .cal-name {
            font-weight: 600;
            font-size: 0.95rem;
            color: #1e293b;
        }

        .cal-type {
            font-size: 0.82rem;
            color: #64748b;
            margin-top: 2px;
        }

        .cal-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }

        .btn-done {
            background: #0d9488;
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
        }

        .btn-done:hover {
            background: #0f766e;
        }

        @media (max-width: 700px) {
            .cal-row {
                flex-wrap: wrap;
                gap: 10px;
            }

            .cal-actions {
                width: 100%;
                justify-content: flex-end;
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
                <a href="doctor-calendar.php" class="nav-item active">
                    <span class="nav-icon">📅</span> My Calendar
                </a>
                <a href="doctor-patients.php" class="nav-item">
                    <span class="nav-icon">👥</span> My Patients
                </a>
                <a href="doctor-availability.php" class="nav-item">
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
                    <h1>My Calendar</h1>
                    <p class="date">Week of September 13–19, 2026</p>
                </div>
            </header>

            <!-- Week Strip -->
            <div class="week-strip">
                <?php foreach ($weekDays as $wd): ?>
                    <?php
                    $isActive = ($wd['date'] === $selectedDay);
                    $hasAppt  = isset($apptCountByDay[$wd['date']]) && $apptCountByDay[$wd['date']] > 0;
                    $classes  = 'week-day';
                    if ($isActive) $classes .= ' active';
                    if (!$hasAppt) $classes .= ' no-appt';
                    ?>
                    <a href="?day=<?php echo $wd['date']; ?>" class="<?php echo $classes; ?>">
                        <span class="dow"><?php echo $wd['dow']; ?></span>
                        <span class="day-num"><?php echo $wd['day']; ?></span>
                        <span class="dot"></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Day Appointments -->
            <div class="day-header">
                <?php echo htmlspecialchars($selectedFull); ?> – <?php echo count($dayAppointments); ?> appointment<?php echo count($dayAppointments) !== 1 ? 's' : ''; ?>
            </div>

            <div class="calendar-list">
                <?php if (empty($dayAppointments)): ?>
                    <div class="cal-row">
                        <div style="width:100%; text-align:center; color:#94a3b8; padding:20px 0;">
                            No appointments on this day.
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($dayAppointments as $appt): ?>
                        <?php
                        $status = strtolower($appt['status']);
                        $label  = ucwords(str_replace('-', ' ', $status));
                        $timePart = '';
                        $parts = explode(' ', $appt['datetime'], 2);
                        if (isset($parts[1])) {
                            // Show only hour part like "09 AM"
                            $t = DateTime::createFromFormat('h:i A', $parts[1]);
                            $timePart = $t ? $t->format('h A') : $parts[1];
                        }
                        ?>
                        <div class="cal-row">
                            <div class="cal-time"><?php echo htmlspecialchars($timePart); ?></div>
                            <div class="cal-avatar">
                                <?php echo strtoupper(substr($appt['patient'], 0, 1)); ?>
                            </div>
                            <div class="cal-info">
                                <div class="cal-name"><?php echo htmlspecialchars($appt['patient']); ?></div>
                                <div class="cal-type"><?php echo htmlspecialchars($appt['type']); ?></div>
                            </div>
                            <div class="cal-actions">
                                <span class="appt-status status-<?php echo $status; ?>">
                                    <span class="status-dot"></span>
                                    <?php echo htmlspecialchars($label); ?>
                                </span>
                                <?php if (!in_array($status, ['completed', 'no-show', 'cancelled'])): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Mark as completed?');">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="id" value="<?php echo (int)$appt['id']; ?>">
                                        <input type="hidden" name="status" value="completed">
                                        <input type="hidden" name="redirect_day" value="<?php echo htmlspecialchars($selectedDay); ?>">
                                        <button type="submit" class="btn-done">Done</button>
                                    </form>
                                <?php endif; ?>
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