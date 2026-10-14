<?php
session_start();

// Protect page - only logged-in admin can access
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
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

$settingsFile = __DIR__ . '/settings.json';

// Load current settings
$settings = [
    'clinic_name' => 'JJJ GentleCare',
    'address'     => '123 Medical Drive, Health City',
    'phone'       => '+63 912 345 6789',
    'email'       => 'info@jjgentlecare.com',
    'hours'       => [
        'monday_friday' => '8:00 AM – 5:00 PM',
        'saturday'      => '8:00 AM – 12:00 PM',
        'sunday'        => 'Closed'
    ]
];

if (file_exists($settingsFile)) {
    $loaded = json_decode(file_get_contents($settingsFile), true);
    if ($loaded) {
        $settings = array_merge($settings, $loaded);
    }
}

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    $clinicName = trim($_POST['clinic_name'] ?? '');
    $address    = trim($_POST['address'] ?? '');
    $phone      = trim($_POST['phone'] ?? '');
    $email      = trim($_POST['email'] ?? '');

    // Validations
    if ($clinicName === '') {
        $errors[] = "Clinic name is required.";
    } elseif (strlen($clinicName) < 2 || strlen($clinicName) > 80) {
        $errors[] = "Clinic name must be between 2 and 80 characters.";
    }

    if ($address === '') {
        $errors[] = "Address is required.";
    } elseif (strlen($address) < 5 || strlen($address) > 150) {
        $errors[] = "Address must be between 5 and 150 characters.";
    }

    if ($phone === '') {
        $errors[] = "Phone number is required.";
    } elseif (!preg_match('/^[\d\s\+\-\(\)]{7,20}$/', $phone)) {
        $errors[] = "Please enter a valid phone number.";
    }

    if ($email === '') {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (empty($errors)) {
        $settings['clinic_name'] = $clinicName;
        $settings['address']     = $address;
        $settings['phone']       = $phone;
        $settings['email']       = $email;

        file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
        $success = "Clinic information saved successfully!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - JJJ GentleCare</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        /* ========== SETTINGS PAGE STYLES ========== */
        .settings-card {
            background: white;
            border-radius: 18px;
            padding: 28px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
            margin: 0 auto 24px auto;
            max-width: 560px;
        }

        .settings-card h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 22px;
        }

        .settings-card .form-group {
            margin-bottom: 18px;
        }

        .settings-card .form-group label {
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: #475569;
            margin-bottom: 7px;
            text-transform: uppercase;
        }

        .settings-card .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            color: #1e293b;
            background: white;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .settings-card .form-group input:focus {
            outline: none;
            border-color: #0d9488;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.12);
        }

        .btn-save {
            background: linear-gradient(135deg, #0d9488, #0f766e);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.15s, box-shadow 0.2s;
            font-family: inherit;
        }

        .btn-save:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(13, 148, 136, 0.3);
        }

        /* Clinic Hours */
        .hours-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .hours-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.95rem;
        }

        .hours-day {
            color: #475569;
            font-weight: 500;
        }

        .hours-time {
            color: #1e293b;
            font-weight: 600;
        }

        .hours-time.closed {
            color: #94a3b8;
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
                <a href="schedules.php" class="nav-item">
                    <span class="nav-icon">🕒</span> Schedules
                </a>
                <a href="reports.php" class="nav-item">
                    <span class="nav-icon">📊</span> Reports
                </a>
                <a href="activity.php" class="nav-item">
                    <span class="nav-icon">📋</span> Activity Log
                </a>
                <a href="settings.php" class="nav-item active">
                    <span class="nav-icon">⚙️</span> Settings
                </a>
                <a href="profile.php" class="nav-item">
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
                    <h1>Settings</h1>
                    <p class="date">System configuration</p>
                </div>
            </header>

            <?php if ($success): ?>
                <div class="alert alert-success" style="margin:0 auto 20px auto; max-width:560px;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error" style="margin:0 auto 20px auto; max-width:560px;">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Clinic Information -->
            <div class="settings-card">
                <h3>Clinic Information</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="save_settings">

                    <div class="form-group">
                        <label>CLINIC NAME</label>
                        <input type="text" name="clinic_name"
                            value="<?php echo htmlspecialchars($settings['clinic_name']); ?>"
                            placeholder="JJJ GentleCare" required>
                    </div>

                    <div class="form-group">
                        <label>ADDRESS</label>
                        <input type="text" name="address"
                            value="<?php echo htmlspecialchars($settings['address']); ?>"
                            placeholder="123 Medical Drive, Health City" required>
                    </div>

                    <div class="form-group">
                        <label>PHONE</label>
                        <input type="text" name="phone"
                            value="<?php echo htmlspecialchars($settings['phone']); ?>"
                            placeholder="+63 912 345 6789" required>
                    </div>

                    <div class="form-group">
                        <label>EMAIL</label>
                        <input type="email" name="email"
                            value="<?php echo htmlspecialchars($settings['email']); ?>"
                            placeholder="info@jjgentlecare.com" required>
                    </div>

                    <button type="submit" class="btn-save">Save Changes</button>
                </form>
            </div>

            <!-- Clinic Hours -->
            <div class="settings-card">
                <h3>Clinic Hours</h3>
                <div class="hours-list">
                    <div class="hours-row">
                        <span class="hours-day">Monday–Friday</span>
                        <span class="hours-time"><?php echo htmlspecialchars($settings['hours']['monday_friday']); ?></span>
                    </div>
                    <div class="hours-row">
                        <span class="hours-day">Saturday</span>
                        <span class="hours-time"><?php echo htmlspecialchars($settings['hours']['saturday']); ?></span>
                    </div>
                    <div class="hours-row">
                        <span class="hours-day">Sunday</span>
                        <span class="hours-time closed"><?php echo htmlspecialchars($settings['hours']['sunday']); ?></span>
                    </div>
                </div>
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