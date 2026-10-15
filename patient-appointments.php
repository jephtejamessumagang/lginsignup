<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
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

$appointments = [];
if (file_exists($appointmentsFile)) {
    $appointments = json_decode(file_get_contents($appointmentsFile), true) ?? [];
}

$patientName = $user['name'];

// Handle cancel
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        foreach ($appointments as &$a) {
            if ((int)$a['id'] === $id && strcasecmp(trim($a['patient'] ?? ''), trim($patientName)) === 0) {
                $status = strtolower($a['status'] ?? '');
                if (in_array($status, ['pending', 'confirmed', 'checked-in'])) {
                    $a['status'] = 'cancelled';
                }
                break;
            }
        }
        unset($a);
        file_put_contents($appointmentsFile, json_encode(array_values($appointments), JSON_PRETTY_PRINT));
    }
    header('Location: patient-appointments.php');
    exit;
}

// Filter this patient's appointments
$myAppointments = [];
foreach ($appointments as $appt) {
    if (strcasecmp(trim($appt['patient'] ?? ''), trim($patientName)) === 0) {
        $myAppointments[] = $appt;
    }
}

// Newest first
usort($myAppointments, function ($a, $b) {
    return strcmp($b['datetime'], $a['datetime']);
});

// Demo data if empty (matches screenshot)
if (empty($myAppointments)) {
    $myAppointments = [
        [
            'id' => 101,
            'patient' => $patientName,
            'doctor' => 'Dr. Vistal',
            'datetime' => '2026-09-20 03:00 PM',
            'type' => 'Follow-up',
            'status' => 'pending',
            'notes' => 'Lab results review.'
        ],
        [
            'id' => 102,
            'patient' => $patientName,
            'doctor' => 'Dr. Vistal',
            'datetime' => '2026-09-17 09:00 AM',
            'type' => 'Follow-up',
            'status' => 'confirmed',
            'notes' => 'Blood pressure check and medication review.'
        ],
        [
            'id' => 103,
            'patient' => $patientName,
            'doctor' => 'Dr. Vistal',
            'datetime' => '2026-08-30 10:00 AM',
            'type' => 'Annual Check-up',
            'status' => 'completed',
            'notes' => ''
        ],
        [
            'id' => 104,
            'patient' => $patientName,
            'doctor' => 'Dr. Vistal',
            'datetime' => '2026-08-10 09:00 AM',
            'type' => 'Consultation',
            'status' => 'cancelled',
            'notes' => ''
        ],
    ];
}

$total = count($myAppointments);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Appointments - JJJ GentleCare</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .appt-list {
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            gap: 14px;
            max-width: 640px;
        }

        .appt-card {
            background: white;
            border-radius: 16px;
            padding: 20px 22px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .appt-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(13, 148, 136, 0.14);
            border-color: #99f6e4;
            background: #f0fdfa;
        }

        .appt-top {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            margin-bottom: 4px;
        }

        .appt-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 1.1rem;
            flex-shrink: 0;
            transition: all 0.25s ease;
        }

        .appt-icon.pending {
            background: #fef3c7;
        }

        .appt-icon.confirmed {
            background: #ccfbf1;
        }

        .appt-icon.checked-in {
            background: #ccfbf1;
        }

        .appt-icon.completed {
            background: #dbeafe;
        }

        .appt-icon.cancelled {
            background: #fee2e2;
        }

        .appt-icon.no-show {
            background: #f1f5f9;
        }

        .appt-card:hover .appt-icon {
            transform: scale(1.06);
        }

        .appt-info {
            flex: 1;
            min-width: 0;
        }

        .appt-type {
            font-weight: 700;
            font-size: 1rem;
            color: #1e293b;
            transition: color 0.2s;
        }

        .appt-card:hover .appt-type {
            color: #0f766e;
        }

        .appt-meta {
            font-size: 0.84rem;
            color: #64748b;
            margin-top: 3px;
        }

        .appt-notes {
            font-size: 0.82rem;
            color: #64748b;
            font-style: italic;
            margin-top: 4px;
        }

        .appt-status-wrap {
            flex-shrink: 0;
        }

        .btn-cancel-appt {
            width: 100%;
            margin-top: 14px;
            background: #fef2f2;
            color: #dc2626;
            border: 1.5px solid #fecaca;
            padding: 10px;
            border-radius: 10px;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.15s;
        }

        .btn-cancel-appt:hover {
            background: #fee2e2;
        }

        .empty-state {
            margin: 0 auto;
            text-align: center;
            padding: 48px 20px;
            color: #94a3b8;
            background: white;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            max-width: 640px;
        }
    </style>
</head>

<body>
    <div class="layout">
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
                        <img src="<?php echo htmlspecialchars($sidebarAvatar); ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                    <?php else: ?>
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                    <div class="user-role">Patient</div>
                </div>
            </div>

            <nav class="nav">
                <a href="patient-home.php" class="nav-item">
                    <span class="nav-icon">🏠</span> Home
                </a>
                <a href="patient-book.php" class="nav-item">
                    <span class="nav-icon">＋</span> Book Appointment
                </a>
                <a href="patient-appointments.php" class="nav-item active">
                    <span class="nav-icon">📅</span> My Appointments
                </a>
                <a href="patient-profile.php" class="nav-item">
                    <span class="nav-icon">👤</span> Profile
                </a>
            </nav>

            <div class="sidebar-footer">
                <button type="button" class="logout-btn" onclick="openLogoutModal()">
                    <span>↪</span> Log Out
                </button>
            </div>
        </aside>

        <main class="main">
            <header class="topbar">
                <div>
                    <h1>My Appointments</h1>
                    <p class="date"><?php echo $total; ?> total</p>
                </div>
            </header>

            <div class="appt-list">
                <?php if (empty($myAppointments)): ?>
                    <div class="empty-state">No appointments found.</div>
                <?php else: ?>
                    <?php foreach ($myAppointments as $appt): ?>
                        <?php
                        $status = strtolower($appt['status'] ?? 'pending');
                        $label  = ucwords(str_replace('-', ' ', $status));
                        $notes  = $appt['notes'] ?? '';
                        $canCancel = in_array($status, ['pending', 'confirmed', 'checked-in']);
                        $iconMap = [
                            'pending'    => '📅',
                            'confirmed'  => '📅',
                            'checked-in' => '📅',
                            'completed'  => '📋',
                            'cancelled'  => '📅',
                            'no-show'    => '📅',
                        ];
                        $icon = $iconMap[$status] ?? '📅';
                        ?>
                        <div class="appt-card">
                            <div class="appt-top">
                                <div class="appt-icon <?php echo $status; ?>"><?php echo $icon; ?></div>
                                <div class="appt-info">
                                    <div class="appt-type"><?php echo htmlspecialchars($appt['type']); ?></div>
                                    <div class="appt-meta">
                                        <?php echo htmlspecialchars($appt['doctor']); ?> ·
                                        <?php echo htmlspecialchars($appt['datetime']); ?>
                                    </div>
                                    <?php if ($notes): ?>
                                        <div class="appt-notes">"<?php echo htmlspecialchars($notes); ?>"</div>
                                    <?php endif; ?>
                                </div>
                                <div class="appt-status-wrap">
                                    <span class="appt-status status-<?php echo $status; ?>">
                                        <span class="status-dot"></span>
                                        <?php echo htmlspecialchars($label); ?>
                                    </span>
                                </div>
                            </div>

                            <?php if ($canCancel): ?>
                                <form method="POST" onsubmit="return confirm('Cancel this appointment?');">
                                    <input type="hidden" name="action" value="cancel">
                                    <input type="hidden" name="id" value="<?php echo (int)$appt['id']; ?>">
                                    <button type="submit" class="btn-cancel-appt">Cancel Appointment</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

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