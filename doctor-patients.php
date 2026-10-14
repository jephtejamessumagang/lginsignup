<?php
session_start();

// Protect page - only doctor can access
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'doctor') {
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

$appointmentsFile = __DIR__ . '/appointments.json';

// Load appointments
$appointments = [];
if (file_exists($appointmentsFile)) {
    $appointments = json_decode(file_get_contents($appointmentsFile), true) ?? [];
}

$doctorName = 'Dr. Vistal';

// Group by patient
$patientsMap = [];

foreach ($appointments as $appt) {
    if ($appt['doctor'] !== $doctorName) continue;

    $name = $appt['patient'];
    $datePart = substr($appt['datetime'], 0, 10);
    $status = strtolower($appt['status'] ?? 'pending');

    if (!isset($patientsMap[$name])) {
        $patientsMap[$name] = [
            'name'       => $name,
            'visits'     => 0,
            'last_visit' => $datePart,
            'status'     => $status,
            'last_dt'    => $appt['datetime'],
        ];
    }

    $patientsMap[$name]['visits']++;

    // Keep the most recent visit
    if ($datePart > $patientsMap[$name]['last_visit']) {
        $patientsMap[$name]['last_visit'] = $datePart;
        $patientsMap[$name]['status']     = $status;
        $patientsMap[$name]['last_dt']    = $appt['datetime'];
    }
}

// Convert to array and sort by last visit (newest first)
$patients = array_values($patientsMap);
usort($patients, function ($a, $b) {
    return strcmp($b['last_visit'], $a['last_visit']);
});

$totalPatients = count($patients);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Patients - JJJ GentleCare</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        /* ========== MY PATIENTS STYLES ========== */
        .patients-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .patient-card {
            background: white;
            border-radius: 16px;
            padding: 18px 22px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
            display: flex;
            align-items: center;
            gap: 16px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: default;
        }

        /* Hover animation */
        .patient-card:hover {
            transform: translateY(-3px) scale(1.01);
            box-shadow: 0 12px 28px rgba(13, 148, 136, 0.12);
            border-color: #99f6e4;
            background: #f0fdfa;
        }

        .patient-card:hover .patient-avatar {
            transform: scale(1.08);
            background: #0d9488;
            color: white;
        }

        .patient-avatar {
            width: 48px;
            height: 48px;
            background: #ccfbf1;
            color: #0f766e;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 1rem;
            flex-shrink: 0;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .patient-info {
            flex: 1;
        }

        .patient-name {
            font-weight: 600;
            font-size: 1rem;
            color: #1e293b;
            margin-bottom: 3px;
            transition: color 0.2s;
        }

        .patient-card:hover .patient-name {
            color: #0f766e;
        }

        .patient-meta {
            font-size: 0.85rem;
            color: #64748b;
        }

        .patient-status {
            flex-shrink: 0;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 48px 20px;
            color: #94a3b8;
            background: white;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
        }

        @media (max-width: 600px) {
            .patient-card {
                flex-wrap: wrap;
            }

            .patient-status {
                width: 100%;
                display: flex;
                justify-content: flex-end;
                margin-top: 4px;
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
                <a href="doctor-patients.php" class="nav-item active">
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
                    <h1>My Patients</h1>
                    <p class="date"><?php echo $totalPatients; ?> unique patient<?php echo $totalPatients !== 1 ? 's' : ''; ?></p>
                </div>
            </header>

            <div class="patients-list">
                <?php if (empty($patients)): ?>
                    <div class="empty-state">
                        No patients found.
                    </div>
                <?php else: ?>
                    <?php foreach ($patients as $p): ?>
                        <?php
                        $status = $p['status'];
                        $label  = ucwords(str_replace('-', ' ', $status));
                        $visitText = $p['visits'] === 1 ? '1 total visit' : $p['visits'] . ' total visits';
                        ?>
                        <div class="patient-card">
                            <div class="patient-avatar">
                                <?php echo strtoupper(substr($p['name'], 0, 1)); ?>
                            </div>
                            <div class="patient-info">
                                <div class="patient-name"><?php echo htmlspecialchars($p['name']); ?></div>
                                <div class="patient-meta">
                                    Last visit: <?php echo htmlspecialchars($p['last_visit']); ?> · <?php echo $visitText; ?>
                                </div>
                            </div>
                            <div class="patient-status">
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