<?php
session_start();

// Protect page - only admin can access
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
$usersFile = __DIR__ . '/users.json';

// ========== LOAD USERS ==========
$registeredUsers = [];
if (file_exists($usersFile)) {
    $registeredUsers = json_decode(file_get_contents($usersFile), true) ?? [];
}

// Demo users (always shown)
$demoUsers = [
    [
        'name'   => 'James Carl Vistal',
        'email'  => 'jvistal@jjgentlecare.com',
        'role'   => 'doctor',
        'status' => 'active'
    ],
    [
        'name'   => 'Jack Norris Inihao',
        'email'  => 'jinihao@jjgentlecare.com',
        'role'   => 'staff',
        'status' => 'active'
    ],
    [
        'name'   => 'Jephte James Sumagang',
        'email'  => 'jephte.patient@gmail.com',
        'role'   => 'patient',
        'status' => 'active'
    ],
    [
        'name'   => 'Maria Santos',
        'email'  => 'msantos@gmail.com',
        'role'   => 'patient',
        'status' => 'active'
    ],
    [
        'name'   => 'Roberto Cruz',
        'email'  => 'rcruz@gmail.com',
        'role'   => 'patient',
        'status' => 'inactive'
    ],
];

// Normalize registered users (add missing role & status)
$normalizedUsers = [];
foreach ($registeredUsers as $u) {
    $normalizedUsers[] = [
        'name'   => $u['name'] ?? 'Unknown',
        'email'  => $u['email'] ?? '',
        'role'   => $u['role'] ?? 'patient',      // default if missing
        'status' => $u['status'] ?? 'active',     // default if missing
    ];
}

// Final list
$allUsers = array_merge($demoUsers, $normalizedUsers);

// ========== ADD USER LOGIC ==========
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role     = $_POST['role'] ?? '';
    $status   = $_POST['status'] ?? 'active';
    $password = trim($_POST['password'] ?? '');

    // Validations
    if ($name === '') {
        $errors[] = "Full name is required.";
    } elseif (!preg_match("/^[A-Za-z .'-]{2,50}$/", $name)) {
        $errors[] = "Name must be 2-50 letters (spaces, dots, apostrophes allowed).";
    }

    if ($email === '') {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Valid email address is required.";
    }

    if ($password === '') {
        $errors[] = "Password is required.";
    } elseif (strlen($password) !== 7) {
        $errors[] = "Password must be exactly 7 characters.";
    }

    if (!in_array($role, ['admin', 'doctor', 'staff', 'patient'])) {
        $errors[] = "Please select a valid role.";
    }

    if (!in_array($status, ['active', 'inactive'])) {
        $errors[] = "Please select a valid status.";
    }

    // Check duplicate email
    if (empty($errors)) {
        $emailLower = strtolower($email);
        foreach ($allUsers as $existing) {
            if (strtolower($existing['email']) === $emailLower) {
                $errors[] = "This email is already registered.";
                break;
            }
        }
    }

    // Save new user
    if (empty($errors)) {
        $registeredUsers[] = [
            'name'     => $name,
            'email'    => $email,
            'role'     => $role,
            'status'   => $status,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'created'  => date('Y-m-d H:i:s')
        ];

        file_put_contents($usersFile, json_encode($registeredUsers, JSON_PRETTY_PRINT));
        $success = "User added successfully!";

        // Refresh list
        $normalizedUsers = [];
        foreach ($registeredUsers as $u) {
            $normalizedUsers[] = [
                'name'   => $u['name'],
                'email'  => $u['email'],
                'role'   => $u['role'] ?? 'patient',
                'status' => $u['status'] ?? 'active',
            ];
        }
        $allUsers = array_merge($demoUsers, $normalizedUsers);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - JJJ GentleCare</title>
    <link rel="stylesheet" href="dashboard.css">
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
                <a href="users.php" class="nav-item active">
                    <span class="nav-icon">👤</span> Users
                </a>
                <a href="appointments.php" class="nav-item">
                    <span class="nav-icon">📅</span> Appointments
                </a>
                <a href="schedules.php" class="nav-item">
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
            <header class="topbar users-topbar">
                <div>
                    <h1>User Management</h1>
                    <p class="date">Manage all system users</p>
                </div>
                <button class="btn-add" onclick="openAddUserModal()">+ Add User</button>
            </header>

            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-bottom:20px;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error" style="margin-bottom:20px;">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="card table-card">
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>USER</th>
                            <th>EMAIL</th>
                            <th>ROLE</th>
                            <th>STATUS</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allUsers as $u): ?>
                            <tr>
                                <td>
                                    <div class="user-cell">
                                        <div class="table-avatar">
                                            <?php echo strtoupper(substr($u['name'], 0, 1)); ?>
                                        </div>
                                        <span><?php echo htmlspecialchars($u['name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo htmlspecialchars($u['role']); ?>">
                                        <?php echo ucfirst($u['role']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo htmlspecialchars($u['status']); ?>">
                                        <?php echo ucfirst($u['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-edit">Edit</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- ADD USER MODAL -->
    <div class="modal-overlay" id="addUserModal">
        <div class="modal add-user-modal">
            <div class="modal-header">
                <h3>Add New User</h3>
                <button class="modal-close" onclick="closeAddUserModal()">×</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_user">

                <div class="form-group">
                    <label>FULL NAME</label>
                    <input type="text" name="name" placeholder="Juan Dela Cruz" required>
                </div>

                <div class="form-group">
                    <label>EMAIL ADDRESS</label>
                    <input type="email" name="email" placeholder="user@example.com" required>
                </div>

                <div class="form-group">
                    <label>PASSWORD (exactly 7 characters)</label>
                    <input type="password" name="password" placeholder="•••••••" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>ROLE</label>
                        <select name="role" required>
                            <option value="">Select role</option>
                            <option value="admin">Admin</option>
                            <option value="doctor">Doctor</option>
                            <option value="staff">Staff</option>
                            <option value="patient">Patient</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>STATUS</label>
                        <select name="status" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-stay" onclick="closeAddUserModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Add User</button>
                </div>
            </form>
        </div>
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
        function openAddUserModal() {
            document.getElementById('addUserModal').classList.add('show');
        }

        function closeAddUserModal() {
            document.getElementById('addUserModal').classList.remove('show');
        }

        function openLogoutModal() {
            document.getElementById('logoutModal').classList.add('show');
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.remove('show');
        }

        document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
            overlay.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('show');
                }
            });
        });
    </script>
</body>

</html>