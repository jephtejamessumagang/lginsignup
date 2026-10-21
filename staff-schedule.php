<?php
session_start();
require_once __DIR__ . '/log_activity.php';
require_once __DIR__ . '/log_notification.php';

// Protect page - only staff can access
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'staff') {
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

$today = '2026-09-17';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $id     = (int)($_POST['id'] ?? 0);
    $status = strtolower(trim($_POST['status'] ?? ''));

    $allowed = ['confirmed', 'checked-in', 'completed', 'no-show', 'cancelled', 'pending'];
    if (in_array($status, $allowed, true) && $id > 0) {
        foreach ($appointments as &$a) {
            if ((int)$a['id'] === $id) {
                $a['status'] = $status;
                break;
            }
        }
        unset($a);
        file_put_contents($appointmentsFile, json_encode(array_values($appointments), JSON_PRETTY_PRINT));
        $actorName = $_SESSION['user']['name'] ?? 'Staff';
        $statusLabel = ucfirst(str_replace('-', ' ', $status));
        $logType = in_array($status, ['cancelled', 'no-show'], true) ? 'danger' : ($status === 'completed' ? 'primary' : 'success');
        log_activity($actorName, $statusLabel . ' appointment #' . $id, $logType);

        $patientName = '';
        $doctorName = 'Dr. Vistal';
        foreach ($appointments as $a) {
            if ((int)$a['id'] === $id) {
                $patientName = $a['patient'] ?? '';
                $doctorName = $a['doctor'] ?? 'Dr. Vistal';
                break;
            }
        }
        if ($status === 'confirmed') {
            if ($patientName !== '') {
                log_notification(strtolower($patientName), 'Your appointment #' . $id . ' was approved by clinic staff.', 'success', ['appointment_id' => $id]);
            }
            log_notification('doctor', 'Appointment #' . $id . ' for ' . $patientName . ' was approved.', 'primary', ['appointment_id' => $id]);
        } elseif ($status === 'cancelled') {
            if ($patientName !== '') {
                log_notification(strtolower($patientName), 'Your appointment #' . $id . ' was denied/cancelled by clinic staff.', 'danger', ['appointment_id' => $id]);
            }
            log_notification('doctor', 'Appointment #' . $id . ' for ' . $patientName . ' was cancelled.', 'danger', ['appointment_id' => $id]);
        } elseif ($status === 'checked-in') {
            log_notification('doctor', $patientName . ' has checked in for appointment #' . $id . '.', 'info', ['appointment_id' => $id]);
        } elseif ($status === 'completed') {
            if ($patientName !== '') {
                log_notification(strtolower($patientName), 'Your appointment #' . $id . ' was marked completed.', 'success', ['appointment_id' => $id]);
            }
        }
    }
    header('Location: staff-schedule.php');
    exit;
}

// Today's appointments
$todayAppointments = [];
foreach ($appointments as $appt) {
    if (strpos($appt['datetime'] ?? '', $today) === 0) {
        $todayAppointments[] = $appt;
    }
}

usort($todayAppointments, function ($a, $b) {
    return strcmp($a['datetime'] ?? '', $b['datetime'] ?? '');
});

$count = count($todayAppointments);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Today's Schedule - JJJ GentleCare</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .staff-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .staff-card {
            background: white;
            border-radius: 16px;
            padding: 20px 22px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
            display: flex;
            flex-direction: column;
            gap: 14px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .staff-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(13, 148, 136, 0.14);
            border-color: #99f6e4;
            background: #f0fdfa;
        }

        .staff-card-top {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .staff-avatar {
            width: 46px;
            height: 46px;
            background: #ccfbf1;
            color: #0f766e;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 0.95rem;
            flex-shrink: 0;
            transition: all 0.25s ease;
        }

        .staff-card:hover .staff-avatar {
            background: #0d9488;
            color: white;
            transform: scale(1.06);
        }

        .staff-info {
            flex: 1;
        }

        .staff-name {
            font-weight: 600;
            font-size: 1rem;
            color: #1e293b;
            transition: color 0.2s;
        }

        .staff-card:hover .staff-name {
            color: #0f766e;
        }

        .staff-meta {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 2px;
        }

        .staff-note {
            font-size: 0.82rem;
            color: #64748b;
            font-style: italic;
            margin-top: 2px;
        }

        .staff-status {
            flex-shrink: 0;
        }

        .staff-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding-left: 60px;
        }

        .btn-staff {
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.15s;
        }

        .btn-checkin {
            background: #0d9488;
            color: white;
        }

        .btn-checkin:hover {
            background: #0f766e;
        }

        .btn-complete {
            background: #2563eb;
            color: white;
        }

        .btn-complete:hover {
            background: #1d4ed8;
        }

        .btn-confirm {
            background: #0d9488;
            color: white;
        }

        .btn-confirm:hover {
            background: #0f766e;
        }

        .btn-noshow {
            background: #f1f5f9;
            color: #64748b;
            border: 1.5px solid #e2e8f0;
        }

        .btn-noshow:hover {
            background: #fef2f2;
            color: #dc2626;
            border-color: #fecaca;
        }

        .btn-cancel {
            background: #fef2f2;
            color: #dc2626;
            border: 1.5px solid #fecaca;
        }

        .btn-cancel:hover {
            background: #fee2e2;
        }

        .empty-schedule {
            text-align: center;
            padding: 48px 20px;
            color: #94a3b8;
            background: white;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
        }

        @media (max-width: 600px) {
            .staff-actions {
                padding-left: 0;
            }

            .staff-card-top {
                flex-wrap: wrap;
            }
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
                        <img src="<?php echo htmlspecialchars($sidebarAvatar); ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;">
                    <?php else: ?>
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                    <div class="user-role">Clinic Staff</div>
                </div>
            </div>

            <nav class="nav">
                <a href="staff-schedule.php" class="nav-item active">
                    <span class="nav-icon">🕒</span> Today's Schedule
                </a>
                <a href="staff-queue.php" class="nav-item">
                    <span class="nav-icon">👥</span> Patient Queue
                </a>
                <a href="staff-appointments.php" class="nav-item">
                    <span class="nav-icon">📋</span> All Appointments
                </a>
                <a href="staff-profile.php" class="nav-item">
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
                    <h1>Today's Schedule</h1>
                    <p class="date"><?php echo $count; ?> appointment<?php echo $count !== 1 ? 's' : ''; ?> today</p>
                </div>
                <?php require __DIR__ . '/notif_ui.php'; ?>
            </header>

            <div class="staff-list">
                <?php if (empty($todayAppointments)): ?>
                    <div class="empty-schedule">
                        No appointments scheduled for today.
                    </div>
                <?php else: ?>
                    <?php foreach ($todayAppointments as $appt): ?>
                        <?php
                        $status = strtolower($appt['status'] ?? 'pending');
                        $label  = ucwords(str_replace('-', ' ', $status));
                        $timePart = '';
                        $parts = explode(' ', $appt['datetime'] ?? '', 2);
                        if (isset($parts[1])) {
                            $timePart = $parts[1];
                        }
                        $patient = $appt['patient'] ?? 'Unknown';
                        $type = $appt['type'] ?? 'Consultation';
                        $doctor = $appt['doctor'] ?? '';
                        $notes = $appt['notes'] ?? '';
                        ?>
                        <div class="staff-card">
                            <div class="staff-card-top">
                                <div class="staff-avatar">
                                    <?php echo strtoupper(substr($patient, 0, 1)); ?>
                                </div>
                                <div class="staff-info">
                                    <div class="staff-name"><?php echo htmlspecialchars($patient); ?></div>
                                    <div class="staff-meta">
                                        <?php echo htmlspecialchars($timePart); ?>
                                        · <?php echo htmlspecialchars($type); ?>
                                        <?php if ($doctor !== ''): ?>
                                            · <?php echo htmlspecialchars($doctor); ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($notes !== ''): ?>
                                        <div class="staff-note"><?php echo htmlspecialchars($notes); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="staff-status">
                                    <span class="appt-status status-<?php echo htmlspecialchars($status); ?>">
                                        <span class="status-dot"></span>
                                        <?php echo htmlspecialchars($label); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="staff-actions">
                                <?php if (in_array($status, ['pending', 'confirmed'], true)): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="id" value="<?php echo (int)$appt['id']; ?>">
                                        <input type="hidden" name="status" value="checked-in">
                                        <button type="submit" class="btn-staff btn-checkin">Check-in</button>
                                    </form>
                                    <?php if ($status === 'pending'): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="id" value="<?php echo (int)$appt['id']; ?>">
                                            <input type="hidden" name="status" value="confirmed">
                                            <button type="submit" class="btn-staff btn-confirm">Confirm</button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Mark as No-show?');">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="id" value="<?php echo (int)$appt['id']; ?>">
                                        <input type="hidden" name="status" value="no-show">
                                        <button type="submit" class="btn-staff btn-noshow">No-show</button>
                                    </form>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this appointment?');">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="id" value="<?php echo (int)$appt['id']; ?>">
                                        <input type="hidden" name="status" value="cancelled">
                                        <button type="submit" class="btn-staff btn-cancel">Cancel</button>
                                    </form>
                                <?php elseif ($status === 'checked-in'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="id" value="<?php echo (int)$appt['id']; ?>">
                                        <input type="hidden" name="status" value="completed">
                                        <button type="submit" class="btn-staff btn-complete">Complete</button>
                                    </form>
                                <?php endif; ?>
                            </div>
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