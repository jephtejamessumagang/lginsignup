<?php
session_start();

if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];

// Sample appointments data
$appointments = [
    ['patient' => 'Jephte Sumagang', 'doctor' => 'Dr. Vistal', 'date' => '2026-09-17', 'time' => '09:00 AM', 'type' => 'Follow-up', 'status' => 'confirmed'],
    ['patient' => 'Maria Santos', 'doctor' => 'Dr. Vistal', 'date' => '2026-09-17', 'time' => '10:30 AM', 'type' => 'Consultation', 'status' => 'checked-in'],
    ['patient' => 'Roberto Cruz', 'doctor' => 'Dr. Vistal', 'date' => '2026-09-17', 'time' => '11:00 AM', 'type' => 'New Patient', 'status' => 'pending'],
    ['patient' => 'Ana Reyes', 'doctor' => 'Dr. Vistal', 'date' => '2026-09-17', 'time' => '01:00 PM', 'type' => 'Check-up', 'status' => 'confirmed'],
    ['patient' => 'Carlos Mendoza', 'doctor' => 'Dr. Vistal', 'date' => '2026-09-16', 'time' => '09:00 AM', 'type' => 'Follow-up', 'status' => 'completed'],
    ['patient' => 'Lena Garcia', 'doctor' => 'Dr. Vistal', 'date' => '2026-09-15', 'time' => '02:00 PM', 'type' => 'Consultation', 'status' => 'no-show'],
    ['patient' => 'Jephte Sumagang', 'doctor' => 'Dr. Vistal', 'date' => '2026-09-20', 'time' => '03:00 PM', 'type' => 'Follow-up', 'status' => 'pending'],
    ['patient' => 'Jephte Sumagang', 'doctor' => 'Dr. Vistal', 'date' => '2026-08-30', 'time' => '10:00 AM', 'type' => 'Annual Check-up', 'status' => 'completed'],
    ['patient' => 'Jephte Sumagang', 'doctor' => 'Dr. Vistal', 'date' => '2026-08-10', 'time' => '09:00 AM', 'type' => 'Consultation', 'status' => 'cancelled'],
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Appointments - JJJ GentleCare</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        /* Extra safety styles in case dashboard.css has issues */
        .filter-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 18px;
            border: 1.5px solid #e2e8f0;
            background: white;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            color: #64748b;
            cursor: pointer;
            font-family: inherit;
        }

        .filter-btn.active {
            background: #0d9488;
            border-color: #0d9488;
            color: white;
        }

        .status-confirmed {
            background: #ecfdf5;
            color: #059669;
        }

        .status-pending {
            background: #fef3c7;
            color: #d97706;
        }

        .status-checked-in {
            background: #eff6ff;
            color: #2563eb;
        }

        .status-completed {
            background: #e0e7ff;
            color: #4338ca;
        }

        .status-cancelled {
            background: #fef2f2;
            color: #dc2626;
        }

        .status-no-show {
            background: #f1f5f9;
            color: #64748b;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .date-time .time {
            font-size: 0.82rem;
            color: #64748b;
            margin-top: 2px;
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
                <a href="appointments.php" class="nav-item active">
                    <span class="nav-icon">📅</span> Appointments
                </a>
                <a href="#" class="nav-item">
                    <span class="nav-icon">🕒</span> Schedules
                </a>
                <a href="#" class="nav-item">
                    <span class="nav-icon">📊</span> Reports
                </a>
                <a href="#" class="nav-item">
                    <span class="nav-icon">📋</span> Activity Log
                </a>
                <a href="#" class="nav-item">
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
                    <h1>All Appointments</h1>
                    <p class="date"><?php echo count($appointments); ?> total appointments</p>
                </div>
            </header>

            <!-- FILTER TABS -->
            <div class="filter-tabs">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="confirmed">Confirmed</button>
                <button class="filter-btn" data-filter="pending">Pending</button>
                <button class="filter-btn" data-filter="checked-in">Checked-in</button>
                <button class="filter-btn" data-filter="completed">Completed</button>
                <button class="filter-btn" data-filter="cancelled">Cancelled</button>
                <button class="filter-btn" data-filter="no-show">No-Show</button>
            </div>

            <!-- TABLE -->
            <div class="card table-card">
                <table class="users-table" id="appointmentsTable">
                    <thead>
                        <tr>
                            <th>PATIENT</th>
                            <th>DOCTOR</th>
                            <th>DATE & TIME</th>
                            <th>TYPE</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appt): ?>
                            <tr data-status="<?php echo $appt['status']; ?>">
                                <td>
                                    <div class="user-cell">
                                        <div class="table-avatar">
                                            <?php echo strtoupper(substr($appt['patient'], 0, 1)); ?>
                                        </div>
                                        <span><?php echo htmlspecialchars($appt['patient']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($appt['doctor']); ?></td>
                                <td>
                                    <div class="date-time">
                                        <div><?php echo $appt['date']; ?></div>
                                        <div class="time"><?php echo $appt['time']; ?></div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($appt['type']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $appt['status']; ?>">
                                        <?php echo ucwords(str_replace('-', ' ', $appt['status'])); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
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
        // Filter buttons
        document.querySelectorAll('.filter-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(function(b) {
                    b.classList.remove('active');
                });
                this.classList.add('active');

                var filter = this.getAttribute('data-filter');
                document.querySelectorAll('#appointmentsTable tbody tr').forEach(function(row) {
                    if (filter === 'all' || row.getAttribute('data-status') === filter) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });

        // Logout modal
        function openLogoutModal() {
            document.getElementById('logoutModal').classList.add('show');
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.remove('show');
        }
        document.getElementById('logoutModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeLogoutModal();
            }
        });
    </script>
</body>

</html>