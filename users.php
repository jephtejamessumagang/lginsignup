<?php
session_start();
require_once __DIR__ . '/log_activity.php';

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];

// Sidebar avatar + name sync
$sidebarAvatar = '';
$profilesFile = __DIR__ . '/profiles.json';
$allProfiles = [];
if (file_exists($profilesFile)) {
    $allProfiles = json_decode(file_get_contents($profilesFile), true) ?? [];
    $key = strtolower($user['email'] ?? $user['name'] ?? '');
    if (isset($allProfiles[$key]['avatar']) && $allProfiles[$key]['avatar'] && file_exists(__DIR__ . '/' . $allProfiles[$key]['avatar'])) {
        $sidebarAvatar = $allProfiles[$key]['avatar'];
    }
    if (!empty($allProfiles[$key]['full_name'])) {
        $_SESSION['user']['name'] = $allProfiles[$key]['full_name'];
        $user['name'] = $allProfiles[$key]['full_name'];
    }
}

$usersFile = __DIR__ . '/users.json';

function loadUsersFile($usersFile)
{
    if (!file_exists($usersFile)) {
        return [];
    }
    $data = json_decode(file_get_contents($usersFile), true);
    return is_array($data) ? $data : [];
}

function saveUsersFile($usersFile, $users)
{
    file_put_contents($usersFile, json_encode(array_values($users), JSON_PRETTY_PRINT));
}

$registeredUsers = loadUsersFile($usersFile);

// Demo users (shown if not also in users.json)
$demoUsers = [
    [
        'name'   => 'James Carl Vistal',
        'email'  => 'jvistal@jjgentlecare.com',
        'role'   => 'doctor',
        'status' => 'active',
        'source' => 'demo',
    ],
    [
        'name'   => 'Jack Norris Inihao',
        'email'  => 'jinihao@jjgentlecare.com',
        'role'   => 'staff',
        'status' => 'active',
        'source' => 'demo',
    ],
    [
        'name'   => 'Jephte James Sumagang',
        'email'  => 'jephte.patient@gmail.com',
        'role'   => 'patient',
        'status' => 'active',
        'source' => 'demo',
    ],
    [
        'name'   => 'Maria Santos',
        'email'  => 'msantos@gmail.com',
        'role'   => 'patient',
        'status' => 'active',
        'source' => 'demo',
    ],
    [
        'name'   => 'Roberto Cruz',
        'email'  => 'rcruz@gmail.com',
        'role'   => 'patient',
        'status' => 'inactive',
        'source' => 'demo',
    ],
];

$errors = [];
$success = '';

// ========== POST ACTIONS ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // ----- ADD USER (admin-created = auto approved, role patient) -----
    if ($action === 'add_user') {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if ($name === '') {
            $errors[] = 'Full name is required.';
        } elseif (!preg_match("/^[A-Za-z .'-]{2,50}$/", $name)) {
            $errors[] = "Name must be 2-50 letters (spaces, dots, apostrophes allowed).";
        }

        if ($email === '') {
            $errors[] = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Valid email address is required.';
        }

        if ($password === '') {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) !== 7) {
            $errors[] = 'Password must be exactly 7 characters.';
        }

        $emailLower = strtolower($email);
        $demoEmails = array_map(function ($d) {
            return strtolower($d['email']);
        }, $demoUsers);
        $demoEmails = array_merge($demoEmails, [
            'admin@gentlecare.com',
            'doctor@gentlecare.com',
            'staff@gentlecare.com',
            'patient@gentlecare.com',
        ]);

        if (empty($errors)) {
            if (in_array($emailLower, $demoEmails, true)) {
                $errors[] = 'This email is already registered.';
            }
            foreach ($registeredUsers as $existing) {
                if (strtolower($existing['email'] ?? '') === $emailLower) {
                    $errors[] = 'This email is already registered.';
                    break;
                }
            }
        }

        if (empty($errors)) {
            $registeredUsers[] = [
                'name'     => $name,
                'email'    => $email,
                'role'     => 'patient',
                'status'   => 'active',
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'created'  => date('Y-m-d H:i:s'),
            ];
            saveUsersFile($usersFile, $registeredUsers);
            log_activity($user['name'] ?? 'Admin', 'Added new user: ' . $name . ' (patient)', 'info');
            $success = 'User added successfully!';
            $registeredUsers = loadUsersFile($usersFile);
        }
    }

    // ----- APPROVE / BAN / UNBAN -----
    if (in_array($action, ['approve_user', 'ban_user', 'unban_user'], true)) {
        $targetEmail = strtolower(trim($_POST['email'] ?? ''));
        if ($targetEmail === '') {
            $errors[] = 'User email is missing.';
        } else {
            $found = false;
            foreach ($registeredUsers as &$ru) {
                if (strtolower($ru['email'] ?? '') === $targetEmail) {
                    $found = true;
                    if ($action === 'approve_user') {
                        $ru['status'] = 'active';
                        log_activity($user['name'] ?? 'Admin', 'Approved account: ' . ($ru['name'] ?? $targetEmail), 'success');
                        $success = 'User approved successfully.';
                    } elseif ($action === 'ban_user') {
                        $ru['status'] = 'banned';
                        log_activity($user['name'] ?? 'Admin', 'Banned account: ' . ($ru['name'] ?? $targetEmail), 'danger');
                        $success = 'User banned successfully.';
                    } else {
                        $ru['status'] = 'active';
                        log_activity($user['name'] ?? 'Admin', 'Unbanned account: ' . ($ru['name'] ?? $targetEmail), 'success');
                        $success = 'User unbanned successfully.';
                    }
                    break;
                }
            }
            unset($ru);

            // Demo user ban/approve → store override in users.json
            if (!$found) {
                foreach ($demoUsers as $du) {
                    if (strtolower($du['email']) === $targetEmail) {
                        $found = true;
                        $newStatus = $action === 'ban_user' ? 'banned' : 'active';
                        $registeredUsers[] = [
                            'name'     => $du['name'],
                            'email'    => $du['email'],
                            'role'     => $du['role'],
                            'status'   => $newStatus,
                            'password' => '',
                            'created'  => date('Y-m-d H:i:s'),
                            'is_demo_override' => true,
                        ];
                        if ($action === 'ban_user') {
                            log_activity($user['name'] ?? 'Admin', 'Banned account: ' . $du['name'], 'danger');
                            $success = 'User banned successfully.';
                        } else {
                            log_activity($user['name'] ?? 'Admin', 'Approved account: ' . $du['name'], 'success');
                            $success = 'User updated successfully.';
                        }
                        break;
                    }
                }
            }

            if ($found) {
                saveUsersFile($usersFile, $registeredUsers);
                $registeredUsers = loadUsersFile($usersFile);
            } else {
                $errors[] = 'User not found.';
            }
        }
    }
}

// Build display list: registered first (newest first), then demos not overridden
$regByEmail = [];
foreach ($registeredUsers as $ru) {
    $regByEmail[strtolower($ru['email'] ?? '')] = $ru;
}

$allUsers = [];
// Registered users (reverse so newest appear first)
foreach (array_reverse($registeredUsers) as $ru) {
    if (!empty($ru['is_demo_override'])) {
        continue; // shown via demo merge below
    }
    $emailKey = strtolower($ru['email'] ?? '');
    $avatar = '';
    if (isset($allProfiles[$emailKey]['avatar']) && $allProfiles[$emailKey]['avatar'] && file_exists(__DIR__ . '/' . $allProfiles[$emailKey]['avatar'])) {
        $avatar = $allProfiles[$emailKey]['avatar'];
    }
    $displayName = $allProfiles[$emailKey]['full_name'] ?? ($ru['name'] ?? 'Unknown');
    $allUsers[] = [
        'name'   => $displayName,
        'email'  => $ru['email'] ?? '',
        'role'   => $ru['role'] ?? 'patient',
        'status' => $ru['status'] ?? 'pending',
        'avatar' => $avatar,
        'source' => 'registered',
    ];
}

foreach ($demoUsers as $du) {
    $emailKey = strtolower($du['email']);
    $status = $du['status'];
    if (isset($regByEmail[$emailKey])) {
        $status = $regByEmail[$emailKey]['status'] ?? $status;
    }
    $avatar = '';
    if (isset($allProfiles[$emailKey]['avatar']) && $allProfiles[$emailKey]['avatar'] && file_exists(__DIR__ . '/' . $allProfiles[$emailKey]['avatar'])) {
        $avatar = $allProfiles[$emailKey]['avatar'];
    }
    // Also try demo login emails for profiles
    $displayName = $allProfiles[$emailKey]['full_name'] ?? $du['name'];
    $allUsers[] = [
        'name'   => $displayName,
        'email'  => $du['email'],
        'role'   => $du['role'],
        'status' => $status,
        'avatar' => $avatar,
        'source' => 'demo',
    ];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - JJJ GentleCare</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .table-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #ccfbf1;
            color: #0f766e;
            display: grid;
            place-items: center;
            font-weight: 700;
            font-size: 0.9rem;
            overflow: hidden;
            flex-shrink: 0;
        }

        .table-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .user-cell {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .status-badge.status-pending {
            background: #fff7ed;
            color: #c2410c;
            border: 1px solid #fed7aa;
        }

        .status-badge.status-banned {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .status-badge.status-inactive {
            background: #f1f5f9;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }

        .status-badge.status-active {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        .btn-row {
            display: flex;
            gap: 6px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .btn-sm {
            padding: 5px 10px;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
            border: 1.5px solid transparent;
            cursor: pointer;
            font-family: inherit;
            background: #f8fafc;
            color: #64748b;
            border-color: #e2e8f0;
        }

        .btn-sm:hover {
            transform: translateY(-1px);
        }

        .btn-approve {
            background: #ecfdf5;
            color: #047857;
            border-color: #a7f3d0;
        }

        .btn-ban {
            background: #fef2f2;
            color: #b91c1c;
            border-color: #fecaca;
        }

        .btn-unban {
            background: #eff6ff;
            color: #1d4ed8;
            border-color: #bfdbfe;
        }

        .users-table th:last-child,
        .users-table td:last-child {
            text-align: right;
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
                    <div class="user-role"><?php echo ucfirst($user['role']); ?></div>
                </div>
            </div>

            <nav class="nav">
                <a href="dashboard.php" class="nav-item"><span class="nav-icon">▦</span> Dashboard</a>
                <a href="users.php" class="nav-item active"><span class="nav-icon">👤</span> Users</a>
                <a href="appointments.php" class="nav-item"><span class="nav-icon">📅</span> Appointments</a>
                <a href="schedules.php" class="nav-item"><span class="nav-icon">🕒</span> Schedules</a>
                <a href="reports.php" class="nav-item"><span class="nav-icon">📊</span> Reports</a>
                <a href="activity.php" class="nav-item"><span class="nav-icon">📋</span> Activity Log</a>
                <a href="settings.php" class="nav-item"><span class="nav-icon">⚙️</span> Settings</a>
                <a href="profile.php" class="nav-item"><span class="nav-icon">👤</span> Profile</a>
            </nav>

            <div class="sidebar-footer">
                <button type="button" class="logout-btn" onclick="openLogoutModal()">
                    <span>↪</span> Log Out
                </button>
            </div>
        </aside>

        <main class="main">
            <header class="topbar users-topbar">
                <div>
                    <h1>User Management</h1>
                    <p class="date">Manage all system users</p>
                </div>
                <button class="btn-add" onclick="openAddUserModal()">+ Add User</button>
            </header>

            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-bottom:20px;"><?php echo htmlspecialchars($success); ?></div>
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
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allUsers as $u): ?>
                            <?php
                            $st = strtolower($u['status'] ?? 'active');
                            $initial = strtoupper(substr($u['name'], 0, 1));
                            ?>
                            <tr>
                                <td>
                                    <div class="user-cell">
                                        <div class="table-avatar">
                                            <?php if (!empty($u['avatar'])): ?>
                                                <img src="<?php echo htmlspecialchars($u['avatar']); ?>" alt="">
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($initial); ?>
                                            <?php endif; ?>
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
                                    <span class="status-badge status-<?php echo htmlspecialchars($st); ?>">
                                        <?php echo ucfirst($st); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-row">
                                        <?php if ($st === 'pending'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="approve_user">
                                                <input type="hidden" name="email" value="<?php echo htmlspecialchars($u['email']); ?>">
                                                <button type="submit" class="btn-sm btn-approve">Approve</button>
                                            </form>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Ban this user?');">
                                                <input type="hidden" name="action" value="ban_user">
                                                <input type="hidden" name="email" value="<?php echo htmlspecialchars($u['email']); ?>">
                                                <button type="submit" class="btn-sm btn-ban">Ban</button>
                                            </form>
                                        <?php elseif ($st === 'banned'): ?>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="unban_user">
                                                <input type="hidden" name="email" value="<?php echo htmlspecialchars($u['email']); ?>">
                                                <button type="submit" class="btn-sm btn-unban">Unban</button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Ban this user? They will not be able to log in.');">
                                                <input type="hidden" name="action" value="ban_user">
                                                <input type="hidden" name="email" value="<?php echo htmlspecialchars($u['email']); ?>">
                                                <button type="submit" class="btn-sm btn-ban">Ban</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- ADD USER MODAL — name, email, password only -->
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
                    <input type="password" name="password" placeholder="•••••••" maxlength="7" minlength="7" required>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-stay" onclick="closeAddUserModal()">Cancel</button>
                    <button type="submit" class="btn-primary">Add User</button>
                </div>
            </form>
        </div>
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
                if (e.target === this) this.classList.remove('show');
            });
        });
        // Password max 7 in modal
        (function() {
            var input = document.querySelector('#addUserModal input[name="password"]');
            if (!input) return;
            input.addEventListener('input', function() {
                if (input.value.length > 7) input.value = input.value.slice(0, 7);
            });
        })();
    </script>
</body>

</html>