<?php
session_start();

// Protect page - only patient can access
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
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

// Filter for this patient (match by name)
$patientName = $user['name'];
$myAppointments = [];
foreach ($appointments as $appt) {
    if (strcasecmp(trim($appt['patient'] ?? ''), trim($patientName)) === 0) {
        $myAppointments[] = $appt;
    }
}

// Sort by datetime
usort($myAppointments, function ($a, $b) {
    return strcmp($a['datetime'], $b['datetime']);
});

$refDate = '2026-09-17';

// Next upcoming appointment
$nextAppt = null;
foreach ($myAppointments as $appt) {
    $status = strtolower($appt['status'] ?? '');
    $datePart = substr($appt['datetime'], 0, 10);
    if (in_array($status, ['confirmed', 'pending', 'checked-in']) && $datePart >= $refDate) {
        $nextAppt = $appt;
        break;
    }
}

// Past visits
$pastVisits = [];
foreach ($myAppointments as $appt) {
    $status = strtolower($appt['status'] ?? '');
    if (in_array($status, ['completed', 'no-show', 'cancelled'])) {
        $pastVisits[] = $appt;
    }
}
usort($pastVisits, function ($a, $b) {
    return strcmp($b['datetime'], $a['datetime']);
});

// Handle cancel next appointment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_next') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        foreach ($appointments as &$a) {
            if ((int)$a['id'] === $id && strcasecmp(trim($a['patient'] ?? ''), trim($patientName)) === 0) {
                $a['status'] = 'cancelled';
                break;
            }
        }
        unset($a);
        file_put_contents($appointmentsFile, json_encode(array_values($appointments), JSON_PRETTY_PRINT));
    }
    header('Location: patient-home.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - JJJ GentleCare</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .patient-banner {
            background: linear-gradient(135deg, #0d9488, #0f766e);
            border-radius: 18px;
            padding: 22px 26px;
            color: white;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            position: relative;
            overflow: hidden;
        }

        .patient-banner::after {
            content: '';
            position: absolute;
            right: -20px;
            top: -20px;
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
        }

        .patient-banner-avatar {
            width: 52px;
            height: 52px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .patient-banner h2 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 2px;
        }

        .patient-banner p {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .next-card {
            background: white;
            border-radius: 16px;
            padding: 22px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
            margin-bottom: 20px;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .next-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(13, 148, 136, 0.12);
            border-color: #99f6e4;
        }

        .next-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .next-card-header h3 {
            font-size: 1rem;
            font-weight: 700;
            color: #1e293b;
        }

        .next-body {
            display: flex;
            align-items: center;
            gap: 14px;
            background: #f0fdfa;
            border-radius: 12px;
            padding: 14px 16px;
            margin-bottom: 14px;
        }

        .next-icon {
            width: 40px;
            height: 40px;
            background: #ccfbf1;
            color: #0f766e;
            border-radius: 10px;
            display: grid;
            place-items: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .next-info .doctor {
            font-weight: 600;
            color: #1e293b;
            font-size: 0.95rem;
        }

        .next-info .meta {
            font-size: 0.85rem;
            color: #64748b;
            margin-top: 2px;
        }

        .btn-cancel-next {
            width: 100%;
            background: #fef2f2;
            color: #dc2626;
            border: 1.5px solid #fecaca;
            padding: 10px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.15s;
        }

        .btn-cancel-next:hover {
            background: #fee2e2;
        }

        .action-row {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }

        .action-card {
            border-radius: 16px;
            padding: 22px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            text-decoration: none;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            border: 1px solid transparent;
        }

        .action-card.primary {
            background: linear-gradient(135deg, #0d9488, #0f766e);
            color: white;
        }

        .action-card.primary:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 14px 32px rgba(13, 148, 136, 0.3);
        }

        .action-card.secondary {
            background: white;
            border-color: #e2e8f0;
            color: #1e293b;
        }

        .action-card.secondary:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 28px rgba(13, 148, 136, 0.12);
            border-color: #99f6e4;
            background: #f0fdfa;
        }

        .action-card .icon {
            font-size: 1.4rem;
            margin-bottom: 4px;
        }

        .action-card .title {
            font-weight: 700;
            font-size: 1.05rem;
        }

        .action-card .sub {
            font-size: 0.85rem;
            opacity: 0.85;
        }

        .action-card.secondary .sub {
            color: #64748b;
        }

        .section-card {
            background: white;
            border-radius: 16px;
            padding: 22px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(15, 23, 42, 0.04);
            margin-bottom: 20px;
        }

        .section-card h3 {
            font-size: 1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 16px;
        }

        .visit-row {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .visit-row:last-child {
            border-bottom: none;
        }

        .visit-icon {
            width: 36px;
            height: 36px;
            background: #ecfdf5;
            color: #059669;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-size: 0.9rem;
            flex-shrink: 0;
        }

        .visit-info {
            flex: 1;
        }

        .visit-title {
            font-weight: 600;
            font-size: 0.92rem;
            color: #1e293b;
        }

        .visit-meta {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 2px;
        }

        #historyPanel {
            display: none;
        }

        #historyPanel.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .empty-msg {
            text-align: center;
            color: #94a3b8;
            padding: 20px 0;
            font-size: 0.9rem;
        }

        @media (max-width: 700px) {
            .action-row {
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
                <div class="avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                    <div class="user-role">Patient</div>
                </div>
            </div>

            <nav class="nav">
                <a href="patient-home.php" class="nav-item active">
                    <span class="nav-icon">🏠</span> Home
                </a>
                <a href="patient-book.php" class="nav-item">
                    <span class="nav-icon">＋</span> Book Appointment
                </a>
                <a href="patient-appointments.php" class="nav-item">
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

        <!-- MAIN -->
        <main class="main">
            <!-- Greeting Banner -->
            <div class="patient-banner">
                <div class="patient-banner-avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                <div>
                    <h2>Hello, <?php echo htmlspecialchars($user['name']); ?></h2>
                    <p>Take care of your health today 💚</p>
                </div>
            </div>

            <!-- Next Appointment -->
            <div class="next-card">
                <div class="next-card-header">
                    <h3>Next Appointment</h3>
                    <?php if ($nextAppt): ?>
                        <?php
                        $st = strtolower($nextAppt['status']);
                        $lab = ucwords(str_replace('-', ' ', $st));
                        ?>
                        <span class="appt-status status-<?php echo $st; ?>">
                            <span class="status-dot"></span>
                            <?php echo htmlspecialchars($lab); ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($nextAppt): ?>
                    <div class="next-body">
                        <div class="next-icon">📅</div>
                        <div class="next-info">
                            <div class="doctor"><?php echo htmlspecialchars($nextAppt['doctor']); ?></div>
                            <div class="meta">
                                <?php echo htmlspecialchars($nextAppt['type']); ?> ·
                                <?php echo htmlspecialchars($nextAppt['datetime']); ?>
                            </div>
                        </div>
                    </div>
                    <form method="POST" onsubmit="return confirm('Cancel this appointment?');">
                        <input type="hidden" name="action" value="cancel_next">
                        <input type="hidden" name="id" value="<?php echo (int)$nextAppt['id']; ?>">
                        <button type="submit" class="btn-cancel-next">Cancel</button>
                    </form>
                <?php else: ?>
                    <p class="empty-msg">No upcoming appointments.</p>
                <?php endif; ?>
            </div>

            <!-- Action cards -->
            <div class="action-row">
                <a href="patient-book.php" class="action-card primary">
                    <div class="icon">＋</div>
                    <div class="title">Book Appointment</div>
                    <div class="sub">Schedule a visit</div>
                </a>
                <div class="action-card secondary" id="historyBtn" onclick="toggleHistory()">
                    <div class="icon">🕐</div>
                    <div class="title">History</div>
                    <div class="sub">Past visits</div>
                </div>
            </div>

            <!-- Recent Visits (right below Book / History) -->
            <div class="section-card">
                <h3>Recent Visits</h3>
                <?php
                $recent = array_slice($pastVisits, 0, 3);
                if (empty($recent)):
                ?>
                    <p class="empty-msg">No recent visits.</p>
                <?php else: ?>
                    <?php foreach ($recent as $v): ?>
                        <?php
                        $st = strtolower($v['status']);
                        $lab = ucwords(str_replace('-', ' ', $st));
                        ?>
                        <div class="visit-row">
                            <div class="visit-icon">✓</div>
                            <div class="visit-info">
                                <div class="visit-title"><?php echo htmlspecialchars($v['type']); ?></div>
                                <div class="visit-meta">
                                    <?php echo htmlspecialchars(substr($v['datetime'], 0, 10)); ?> · <?php echo htmlspecialchars($v['doctor']); ?>
                                </div>
                            </div>
                            <span class="appt-status status-<?php echo $st; ?>">
                                <span class="status-dot"></span>
                                <?php echo htmlspecialchars($lab); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Full History Panel (shown when History is clicked) -->
            <div class="section-card" id="historyPanel">
                <h3>Past Visits</h3>
                <?php if (empty($pastVisits)): ?>
                    <p class="empty-msg">No past visits yet.</p>
                <?php else: ?>
                    <?php foreach ($pastVisits as $v): ?>
                        <?php
                        $st = strtolower($v['status']);
                        $lab = ucwords(str_replace('-', ' ', $st));
                        ?>
                        <div class="visit-row">
                            <div class="visit-icon">✓</div>
                            <div class="visit-info">
                                <div class="visit-title"><?php echo htmlspecialchars($v['type']); ?></div>
                                <div class="visit-meta">
                                    <?php echo htmlspecialchars($v['datetime']); ?> · <?php echo htmlspecialchars($v['doctor']); ?>
                                </div>
                            </div>
                            <span class="appt-status status-<?php echo $st; ?>">
                                <span class="status-dot"></span>
                                <?php echo htmlspecialchars($lab); ?>
                            </span>
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

        function toggleHistory() {
            const panel = document.getElementById('historyPanel');
            panel.classList.toggle('show');
            if (panel.classList.contains('show')) {
                panel.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest'
                });
            }
        }
    </script>
</body>

</html>