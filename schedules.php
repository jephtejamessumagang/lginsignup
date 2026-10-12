<?php
session_start();

// Protect page - only logged-in admin can access
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
$appointmentsFile = __DIR__ . '/appointments.json';

// Load appointments
$appointments = [];
if (file_exists($appointmentsFile)) {
    $appointments = json_decode(file_get_contents($appointmentsFile), true) ?? [];
}

// Today's date (matching the screenshot)
$today = '2026-09-17';
$todayDisplay = 'September 17, 2026';

// Filter appointments for today + Dr. Vistal
$todayAppointments = [];
foreach ($appointments as $appt) {
    if (strpos($appt['datetime'], $today) === 0 && $appt['doctor'] === 'Dr. Vistal') {
        // Extract time
        $parts = explode(' ', $appt['datetime'], 2);
        $time = $parts[1] ?? '';
        $todayAppointments[$time] = $appt;
    }
}

// Define time slots (matching screenshot)
$timeSlots = [
    '08:00 AM',
    '09:00 AM',
    '10:00 AM',
    '11:00 AM',
    '12:00 PM',
    '01:00 PM',
    '02:00 PM',
    '03:00 PM',
    '04:00 PM',
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Schedule - JJJ GentleCare</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        /* ========== SCHEDULE PAGE STYLES ========== */
        .schedule-doctor-card {
            background: white;
            border-radius: 16px;
            padding: 18px 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 28px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
        }

        .doctor-avatar {
            width: 48px;
            height: 48px;
            background: #ccfbf1;
            color: #0f766e;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .doctor-info h3 {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 2px;
        }

        .doctor-info p {
            font-size: 0.85rem;
            color: #64748b;
        }

        .schedule-timeline {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .time-slot {
            display: flex;
            align-items: stretch;
            gap: 16px;
        }

        .time-label {
            width: 90px;
            flex-shrink: 0;
            font-size: 0.9rem;
            font-weight: 600;
            color: #64748b;
            padding-top: 18px;
            text-align: right;
        }

        .slot-content {
            flex: 1;
            background: white;
            border-radius: 14px;
            padding: 16px 20px;
            border: 1.5px solid #e2e8f0;
            min-height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.15s;
        }

        .slot-content.available {
            border-style: dashed;
            background: #f8fafc;
            color: #94a3b8;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .slot-content.booked {
            background: #f0fdfa;
            border-color: #99f6e4;
        }

        .slot-patient {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .slot-avatar {
            width: 40px;
            height: 40px;
            background: #ccfbf1;
            color: #0f766e;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .slot-details .patient-name {
            font-weight: 600;
            font-size: 0.95rem;
            color: #1e293b;
        }

        .slot-details .appt-type {
            font-size: 0.82rem;
            color: #64748b;
            margin-top: 2px;
        }

        .slot-status {
            flex-shrink: 0;
        }

        /* Responsive for schedule */
        @media (max-width: 768px) {
            .time-slot {
                flex-direction: column;
                gap: 6px;
            }

            .time-label {
                width: auto;
                text-align: left;
                padding-top: 0;
                font-size: 0.85rem;
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
                <div class="avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
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
                <a href="schedules.php" class="nav-item active">
                    <span class="nav-icon">🕒</span> Schedules
                </a>
                <a href="reports.php" class="nav-item">
                    <span class="nav-icon">📊</span> Reports
                </a>
                <a href="activity.php" class="nav-item">
                    <span class="nav-icon">📋</span> Activity Log
                </a>
                <a href="settings.php" class="nav-item">
                    <span class="nav-icon">⚙️</span> Settings
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
                    <h1>Clinic Schedule</h1>
                    <p class="date">Today — <?php echo $todayDisplay; ?></p>
                </div>
            </header>

            <!-- Doctor Card -->
            <div class="schedule-doctor-card">
                <div class="doctor-avatar">D</div>
                <div class="doctor-info">
                    <h3>Dr. Vistal</h3>
                    <p>General Medicine</p>
                </div>
            </div>

            <!-- Timeline -->
            <div class="schedule-timeline">
                <?php foreach ($timeSlots as $slot): ?>
                    <?php
                    $appt = $todayAppointments[$slot] ?? null;
                    $isBooked = $appt !== null;
                    ?>
                    <div class="time-slot">
                        <div class="time-label"><?php echo $slot; ?></div>

                        <?php if ($isBooked): ?>
                            <div class="slot-content booked">
                                <div class="slot-patient">
                                    <div class="slot-avatar">
                                        <?php echo strtoupper(substr($appt['patient'], 0, 1)); ?>
                                    </div>
                                    <div class="slot-details">
                                        <div class="patient-name"><?php echo htmlspecialchars($appt['patient']); ?></div>
                                        <div class="appt-type"><?php echo htmlspecialchars($appt['type']); ?></div>
                                    </div>
                                </div>
                                <div class="slot-status">
                                    <?php
                                    $status = strtolower($appt['status']);
                                    $label = ucwords(str_replace('-', ' ', $status));
                                    ?>
                                    <span class="appt-status status-<?php echo $status; ?>">
                                        <span class="status-dot"></span>
                                        <?php echo htmlspecialchars($label); ?>
                                    </span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="slot-content available">
                                Available
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
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