<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
$profilesFile = __DIR__ . '/profiles.json';
$uploadDir = __DIR__ . '/uploads';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$profiles = [];
if (file_exists($profilesFile)) {
    $profiles = json_decode(file_get_contents($profilesFile), true) ?? [];
}

$emailKey = strtolower($user['email'] ?? $user['name']);

$profile = [
    'full_name'  => $user['name'] ?? 'Jephte Sumagang',
    'email'      => $user['email'] ?? 'admin@gentlecare.com',
    'phone'      => '+63 912 345 6789',
    'position'   => 'System Administrator',
    'avatar'     => ''
];

if (isset($profiles[$emailKey])) {
    $profile = array_merge($profile, $profiles[$emailKey]);
}

// Sidebar avatar
$sidebarAvatar = '';
if (!empty($profile['avatar']) && file_exists(__DIR__ . '/' . $profile['avatar'])) {
    $sidebarAvatar = $profile['avatar'];
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_profile') {
    $fullName  = trim($_POST['full_name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $position  = trim($_POST['position'] ?? '');

    if ($fullName === '') {
        $errors[] = "Full name is required.";
    } elseif (strlen($fullName) < 2 || strlen($fullName) > 60) {
        $errors[] = "Full name must be between 2 and 60 characters.";
    }

    if ($email === '') {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if ($phone === '') {
        $errors[] = "Phone is required.";
    } elseif (!preg_match('/^[\d\s\+\-\(\)]{7,20}$/', $phone)) {
        $errors[] = "Please enter a valid phone number.";
    }

    if ($position === '') {
        $errors[] = "Position is required.";
    }

    $avatarPath = $profile['avatar'] ?? '';
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowed)) {
            $errors[] = "Profile picture must be JPG, PNG, GIF, or WebP.";
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $errors[] = "Profile picture must be under 2MB.";
        } else {
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $newName = 'avatar_' . preg_replace('/[^a-z0-9]/i', '_', $emailKey) . '_' . time() . '.' . strtolower($ext);
            $dest = $uploadDir . '/' . $newName;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                if (!empty($avatarPath) && strpos($avatarPath, 'uploads/') === 0) {
                    $old = __DIR__ . '/' . $avatarPath;
                    if (file_exists($old)) @unlink($old);
                }
                $avatarPath = 'uploads/' . $newName;
            } else {
                $errors[] = "Failed to upload profile picture.";
            }
        }
    }

    if (empty($errors)) {
        $profile = [
            'full_name'  => $fullName,
            'email'      => $email,
            'phone'      => $phone,
            'position'   => $position,
            'avatar'     => $avatarPath
        ];
        $profiles[$emailKey] = $profile;
        file_put_contents($profilesFile, json_encode($profiles, JSON_PRETTY_PRINT));

        $_SESSION['user']['name'] = $fullName;
        if (!empty($email)) {
            $_SESSION['user']['email'] = $email;
        }

        $success = "Profile saved successfully!";
        $user = $_SESSION['user'];
        if (!empty($avatarPath) && file_exists(__DIR__ . '/' . $avatarPath)) {
            $sidebarAvatar = $avatarPath;
        }
    }
}

$avatarUrl = '';
if (!empty($profile['avatar']) && file_exists(__DIR__ . '/' . $profile['avatar'])) {
    $avatarUrl = $profile['avatar'];
}
$initial = strtoupper(substr($profile['full_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - JJJ GentleCare</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .profile-wrap {
            max-width: 440px;
            margin: 0 auto;
        }

        .profile-card {
            background: white;
            border-radius: 18px;
            padding: 32px 28px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
            text-align: center;
        }

        .avatar-wrap {
            position: relative;
            width: 88px;
            height: 88px;
            margin: 0 auto 14px auto;
        }

        .avatar-circle {
            width: 88px;
            height: 88px;
            border-radius: 50%;
            background: #ccfbf1;
            color: #0f766e;
            display: grid;
            place-items: center;
            font-size: 2rem;
            font-weight: 700;
            overflow: hidden;
            border: 3px solid #e2e8f0;
        }

        .avatar-circle img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-edit {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 30px;
            height: 30px;
            background: #0d9488;
            border: 2px solid white;
            border-radius: 50%;
            display: grid;
            place-items: center;
            cursor: pointer;
            color: white;
            font-size: 0.85rem;
            transition: background 0.15s;
        }

        .avatar-edit:hover {
            background: #0f766e;
        }

        #avatarInput {
            display: none;
        }

        .profile-name {
            font-size: 1.2rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 2px;
        }

        .profile-role {
            font-size: 0.88rem;
            color: #0d9488;
            font-weight: 600;
            margin-bottom: 24px;
        }

        .form-group {
            text-align: left;
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .form-group input {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            color: #1e293b;
            background: white;
            font-family: inherit;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        .form-group input:focus {
            outline: none;
            border-color: #0d9488;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.12);
        }

        .btn-save {
            width: 100%;
            background: #0d9488;
            color: white;
            border: none;
            padding: 13px;
            border-radius: 12px;
            font-size: 0.98rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            margin-top: 8px;
            transition: background 0.15s, transform 0.15s;
        }

        .btn-save:hover {
            background: #0f766e;
            transform: translateY(-1px);
        }

        .avatar {
            overflow: hidden;
            padding: 0;
        }

        .avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
            display: block;
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
                <div class="avatar">
                    <?php if (!empty($sidebarAvatar)): ?>
                        <img src="<?php echo htmlspecialchars($sidebarAvatar); ?>" alt="">
                    <?php else: ?>
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                    <div class="user-role">Admin</div>
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
                <a href="settings.php" class="nav-item">
                    <span class="nav-icon">⚙️</span> Settings
                </a>
                <a href="profile.php" class="nav-item active">
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
            <header class="topbar" style="text-align:center;">
                <div>
                    <h1>My Profile</h1>
                </div>
            </header>

            <div class="profile-wrap">
                <?php if ($success): ?>
                    <div class="alert alert-success" style="margin-bottom:16px; text-align:center;">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error" style="margin-bottom:16px;">
                        <?php foreach ($errors as $e): ?>
                            <div><?php echo htmlspecialchars($e); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="profile-card">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="save_profile">

                        <div class="avatar-wrap">
                            <div class="avatar-circle" id="avatarPreview">
                                <?php if ($avatarUrl): ?>
                                    <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="Profile">
                                <?php else: ?>
                                    <?php echo $initial; ?>
                                <?php endif; ?>
                            </div>
                            <label class="avatar-edit" for="avatarInput" title="Change photo">📷</label>
                            <input type="file" name="avatar" id="avatarInput" accept="image/jpeg,image/png,image/gif,image/webp">
                        </div>

                        <div class="profile-name"><?php echo htmlspecialchars($profile['full_name']); ?></div>
                        <div class="profile-role">Admin</div>

                        <div class="form-group">
                            <label>FULL NAME</label>
                            <input type="text" name="full_name"
                                value="<?php echo htmlspecialchars($profile['full_name']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>EMAIL</label>
                            <input type="email" name="email"
                                value="<?php echo htmlspecialchars($profile['email']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>PHONE</label>
                            <input type="text" name="phone"
                                value="<?php echo htmlspecialchars($profile['phone']); ?>" required>
                        </div>

                        <div class="form-group">
                            <label>POSITION</label>
                            <input type="text" name="position"
                                value="<?php echo htmlspecialchars($profile['position'] ?? 'System Administrator'); ?>" required>
                        </div>

                        <button type="submit" class="btn-save">Save Changes</button>
                    </form>
                </div>
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

        document.getElementById('avatarInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(ev) {
                document.getElementById('avatarPreview').innerHTML =
                    '<img src="' + ev.target.result + '" alt="Profile">';
            };
            reader.readAsDataURL(file);
        });
    </script>
</body>

</html>