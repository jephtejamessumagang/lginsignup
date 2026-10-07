<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];

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

$total = count($appointments);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Appointments - JJJ GentleCare</title>
    <style>
        :root {
            --primary: #0d9488;
            --bg: #f0fdfa;
            --text: #1e293b;
            --muted: #64748b;
            --border: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        .layout {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 260px;
            background: #fff;
            border-right: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            padding: 24px 16px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 28px;
            padding: 0 8px;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: #ccfbf1;
            border-radius: 12px;
            display: grid;
            place-items: center;
        }

        .logo-text {
            font-size: 1.05rem;
            font-weight: 700;
        }

        .logo-sub {
            font-size: 0.75rem;
            color: var(--muted);
        }

        .user-card {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #f0fdfa;
            border-radius: 14px;
            padding: 12px 14px;
            margin-bottom: 28px;
        }

        .avatar {
            width: 40px;
            height: 40px;
            background: var(--primary);
            color: white;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-weight: 700;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .user-role {
            font-size: 0.78rem;
            color: var(--muted);
        }

        .nav {
            display: flex;
            flex-direction: column;
            gap: 4px;
            flex: 1;
        }

        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
            border-radius: 12px;
            color: var(--muted);
            text-decoration: none;
            font-size: 0.92rem;
            font-weight: 500;
        }

        .nav-item:hover {
            background: #f0fdfa;
            color: var(--primary);
        }

        .nav-item.active {
            background: var(--primary);
            color: white;
        }

        .nav-icon {
            font-size: 1.1rem;
            width: 22px;
            text-align: center;
        }

        .sidebar-footer {
            padding-top: 16px;
            border-top: 1px solid var(--border);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding: 12px 14px;
            color: #ef4444;
            background: transparent;
            border: none;
            font-weight: 500;
            font-size: 0.95rem;
            border-radius: 12px;
            cursor: pointer;
            font-family: inherit;
        }

        .logout-btn:hover {
            background: #fef2f2;
        }

        /* Main */
        .main {
            margin-left: 260px;
            flex: 1;
            padding: 32px 36px;
        }

        .topbar {
            margin-bottom: 28px;
        }

        .topbar h1 {
            font-size: 1.7rem;
            font-weight: 700;
        }

        .date {
            color: var(--muted);
            font-size: 0.95rem;
            margin-top: 4px;
        }

        /* Filters */
        .filter-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .filter-btn {
            padding: 8px 18px;
            border: 1.5px solid var(--border);
            background: white;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 500;
            color: var(--muted);
            cursor: pointer;
            font-family: inherit;
            transition: all 0.15s;
        }

        .filter-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .filter-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        /* Table */
        .card {
            background: white;
            border-radius: 18px;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table th {
            text-align: left;
            padding: 16px 24px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: #64748b;
            background: #f8fafc;
            border-bottom: 1px solid var(--border);
        }

        .users-table td {
            padding: 16px 24px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.92rem;
        }

        .users-table tr:last-child td {
            border-bottom: none;
        }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .table-avatar {
            width: 38px;
            height: 38px;
            background: #ccfbf1;
            color: #0f766e;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .date-time .time {
            font-size: 0.82rem;
            color: var(--muted);
            margin-top: 2px;
        }

        /* Status badges */
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 600;
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

        /* Modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.25s ease;
        }

        .modal-overlay.show {
            opacity: 1;
            visibility: visible;
        }

        .modal {
            background: white;
            border-radius: 20px;
            padding: 32px 28px;
            width: 90%;
            max-width: 360px;
            text-align: center;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.15);
            transform: scale(0.9);
            transition: transform 0.25s ease;
        }

        .modal-overlay.show .modal {
            transform: scale(1);
        }

        .modal-icon {
            width: 56px;
            height: 56px;
            background: #fef2f2;
            border-radius: 50%;
            display: grid;
            place-items: center;
            margin: 0 auto 18px;
        }

        .modal h3 {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .modal p {
            font-size: 0.95rem;
            color: var(--muted);
            margin-bottom: 28px;
        }

        .modal-actions {
            display: flex;
            gap: 12px;
        }

        .btn-stay {
            flex: 1;
            padding: 12px 16px;
            border: 1.5px solid var(--border);
            background: #f8fafc;
            color: var(--text);
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
        }

        .btn-logout {
            flex: 1;
            padding: 12px 16px;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .btn-logout:hover {
            background: #dc2626;
        }
    </style>
</head>

<body>
    <div class="layout">
        <aside class="sidebar">
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

            <div class="user-card">
                <div class="avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                    <div class="user-role"><?php echo ucfirst($user['role']); ?></div>
                </div>
            </div>

            <nav class="nav">
                <a href="dashboard.php" class="nav-item"><span class="nav-icon">▦</span> Dashboard</a>
                <a href="users.php" class="nav-item"><span class="nav-icon">👤</span> Users</a>
                <a href="appointments.php" class="nav-item active"><span class="nav-icon">📅</span> Appointments</a>
                <a href="#" class="nav-item"><span class="nav-icon">🕒</span> Schedules</a>
                <a href="#" class="nav-item"><span class="nav-icon">📊</span> Reports</a>
                <a href="#" class="nav-item"><span class="nav-icon">📋</span> Activity Log</a>
                <a href="#" class="nav-item"><span class="nav-icon">⚙️</span> Settings</a>
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
                    <h1>All Appointments</h1>
                    <p class="date"><?php echo $total; ?> total appointments</p>
                </div>
            </header>

            <div class="filter-tabs">
                <button class="filter-btn active" data-filter="all">All</button>
                <button class="filter-btn" data-filter="confirmed">Confirmed</button>
                <button class="filter-btn" data-filter="pending">Pending</button>
                <button class="filter-btn" data-filter="checked-in">Checked-in</button>
                <button class="filter-btn" data-filter="completed">Completed</button>
                <button class="filter-btn" data-filter="cancelled">Cancelled</button>
                <button class="filter-btn" data-filter="no-show">No-Show</button>
            </div>

            <div class="card">
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
                                        <div class="table-avatar"><?php echo strtoupper(substr($appt['patient'], 0, 1)); ?></div>
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
        const filterBtns = document.querySelectorAll('.filter-btn');
        const rows = document.querySelectorAll('#appointmentsTable tbody tr');

        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                const filter = this.dataset.filter;
                rows.forEach(row => {
                    row.style.display = (filter === 'all' || row.dataset.status === filter) ? '' : 'none';
                });
            });
        });

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