<?php
session_start();
require_once __DIR__ . '/log_activity.php';

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

// Filter for this doctor (Dr. Vistal)
$doctorName = 'Dr. Vistal';
$today = '2026-09-17';

$todayPatients = [];
$upcomingPatients = [];
$completedCount = 0;

foreach ($appointments as $appt) {
    if ($appt['doctor'] !== $doctorName) continue;

    $datePart = substr($appt['datetime'], 0, 10);
    $status = strtolower($appt['status'] ?? '');

    if ($datePart === $today) {
        $todayPatients[] = $appt;
        if ($status === 'completed') {
            $completedCount++;
        }
    } elseif ($datePart > $today) {
        $upcomingPatients[] = $appt;
    }
}

// Sort today's patients by time
usort($todayPatients, function ($a, $b) {
    return strcmp($a['datetime'], $b['datetime']);
});

// Handle status update (Done / No-show)
$success = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $id     = (int)($_POST['id'] ?? 0);
    $status = strtolower(trim($_POST['status'] ?? ''));

    $allowed = ['completed', 'no-show'];
    if (!in_array($status, $allowed)) {
        $errors[] = "Invalid status.";
    } else {
        $found = false;
        foreach ($appointments as &$a) {
            if ((int)$a['id'] === $id && $a['doctor'] === $doctorName) {
                $a['status'] = $status;
                $found = true;
                break;
            }
        }
        unset($a);

        if ($found) {
            file_put_contents($appointmentsFile, json_encode(array_values($appointments), JSON_PRETTY_PRINT));
            $actorName = $_SESSION['user']['name'] ?? 'Doctor';
            $statusLabel = $status === 'no-show' ? 'Marked appointment as No-show' : 'Completed session for appointment #' . $id;
            log_activity($actorName, $statusLabel, $status === 'no-show' ? 'danger' : 'primary');
            $success = "Status updated successfully!";
            // Refresh data
            header('Location: doctor-dashboard.php');
            exit;
        } else {
            $errors[] = "Appointment not found.";
        }
    }
}

// Greeting based on time
$hour = (int)date('H');
if ($hour < 12) {
    $greeting = 'Good morning';
} elseif ($hour < 17) {
    $greeting = 'Good afternoon';
} else {
    $greeting = 'Good evening';
}

$todayCount = count($todayPatients);
$upcomingCount = count($upcomingPatients);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - JJJ GentleCare</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        /* ========== DOCTOR DASHBOARD STYLES ========== */
        .doctor-banner {
            background: linear-gradient(135deg, #0d9488, #0f766e);
            border-radius: 18px;
            padding: 24px 28px;
            color: white;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .doctor-banner-avatar {
            width: 56px;
            height: 56px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .doctor-banner h2 {
            font-size: 1.35rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .doctor-banner p {
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .doctor-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 28px;
        }

        .doctor-stat-card {
            background: white;
            border-radius: 16px;
            padding: 22px 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
            text-align: center;
        }

        .doctor-stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.2;
        }

        .doctor-stat-label {
            font-size: 0.88rem;
            color: #64748b;
            margin-top: 4px;
        }

        .section-card {
            background: white;
            border-radius: 18px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
            margin-bottom: 24px;
        }

        .section-card h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 20px;
        }

        .patient-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .patient-row:last-child {
            border-bottom: none;
        }

        .patient-avatar {
            width: 42px;
            height: 42px;
            background: #ccfbf1;
            color: #0f766e;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 0.85rem;
            flex-shrink: 0;
        }

        .patient-info {
            flex: 1;
        }

        .patient-name {
            font-weight: 600;
            font-size: 0.95rem;
            color: #1e293b;
        }

        .patient-meta {
            font-size: 0.82rem;
            color: #64748b;
            margin-top: 2px;
        }

        .patient-actions {
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
            transition: background 0.15s;
        }

        .btn-done:hover {
            background: #0f766e;
        }

        .btn-noshow {
            background: #f1f5f9;
            color: #64748b;
            border: 1.5px solid #e2e8f0;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.15s;
        }

        .btn-noshow:hover {
            background: #fef2f2;
            color: #dc2626;
            border-color: #fecaca;
        }

        /* Sidebar override for doctor */
        .sidebar .nav-item.active {
            background: #0d9488;
            color: white;
        }

        @media (max-width: 900px) {
            .doctor-stats {
                grid-template-columns: 1fr;
            }

            .patient-row {
                flex-wrap: wrap;
            }

            .patient-actions {
                width: 100%;
                justify-content: flex-end;
                margin-top: 8px;
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
                <a href="doctor-dashboard.php" class="nav-item active">
                    <span class="nav-icon">▦</span> Dashboard
                </a>
                <a href="doctor-calendar.php" class="nav-item">
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
            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-bottom:20px;"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error" style="margin-bottom:20px;">
                    <?php foreach ($errors as $e): ?>
                        <div><?php echo htmlspecialchars($e); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Greeting Banner -->
            <div class="doctor-banner">
                <div class="doctor-banner-avatar">👨‍⚕️</div>
                <div>
                    <h2><?php echo $greeting; ?>, <?php echo htmlspecialchars($user['name']); ?></h2>
                    <p>General Medicine · <?php echo $todayCount; ?> patients today</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="doctor-stats">
                <div class="doctor-stat-card">
                    <div class="doctor-stat-value"><?php echo $todayCount; ?></div>
                    <div class="doctor-stat-label">Today's Patients</div>
                </div>
                <div class="doctor-stat-card">
                    <div class="doctor-stat-value"><?php echo $upcomingCount; ?></div>
                    <div class="doctor-stat-label">Upcoming</div>
                </div>
                <div class="doctor-stat-card">
                    <div class="doctor-stat-value"><?php echo $completedCount; ?></div>
                    <div class="doctor-stat-label">Completed</div>
                </div>
            </div>

            <!-- Today's Patients -->
            <div class="section-card">
                <h3>Today's Patients</h3>

                <?php if (empty($todayPatients)): ?>
                    <p style="color:#94a3b8; text-align:center; padding:20px 0;">No patients scheduled for today.</p>
                <?php else: ?>
                    <?php foreach ($todayPatients as $p): ?>
                        <?php
                        $status = strtolower($p['status']);
                        $label = ucwords(str_replace('-', ' ', $status));
                        $timePart = '';
                        $parts = explode(' ', $p['datetime'], 2);
                        if (isset($parts[1])) $timePart = $parts[1];
                        ?>
                        <div class="patient-row">
                            <div class="patient-avatar">
                                <?php echo strtoupper(substr($p['patient'], 0, 1)); ?>
                            </div>
                            <div class="patient-info">
                                <div class="patient-name"><?php echo htmlspecialchars($p['patient']); ?></div>
                                <div class="patient-meta"><?php echo htmlspecialchars($timePart); ?> · <?php echo htmlspecialchars($p['type']); ?></div>
                            </div>
                            <div class="patient-actions">
                                <span class="appt-status status-<?php echo $status; ?>">
                                    <span class="status-dot"></span>
                                    <?php echo htmlspecialchars($label); ?>
                                </span>

                                <?php if (!in_array($status, ['completed', 'no-show', 'cancelled'])): ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Mark as completed?');">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                        <input type="hidden" name="status" value="completed">
                                        <button type="submit" class="btn-done">Done</button>
                                    </form>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Mark as No-show?');">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                        <input type="hidden" name="status" value="no-show">
                                        <button type="submit" class="btn-noshow">No-show</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Upcoming Patients -->
            <div class="section-card">
                <h3>Upcoming Patients</h3>

                <?php if (empty($upcomingPatients)): ?>
                    <p style="color:#94a3b8; text-align:center; padding:20px 0;">No upcoming patients.</p>
                <?php else: ?>
                    <?php foreach ($upcomingPatients as $p): ?>
                        <?php
                        $status = strtolower($p['status']);
                        $label = ucwords(str_replace('-', ' ', $status));
                        ?>
                        <div class="patient-row">
                            <div class="patient-avatar">
                                <?php echo strtoupper(substr($p['patient'], 0, 1)); ?>
                            </div>
                            <div class="patient-info">
                                <div class="patient-name"><?php echo htmlspecialchars($p['patient']); ?></div>
                                <div class="patient-meta"><?php echo htmlspecialchars($p['datetime']); ?></div>
                            </div>
                            <div class="patient-actions">
                                <span class="appt-status status-<?php echo $status; ?>">
                                    <span class="status-dot"></span>
                                    <?php echo htmlspecialchars($label); ?>
                                </span>
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