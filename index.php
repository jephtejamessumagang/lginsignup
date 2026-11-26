<?php
session_start();

// Already logged in → go to the correct dashboard
if (isset($_SESSION['user']) && !empty($_SESSION['user']['role'])) {
    $role = $_SESSION['user']['role'];

    if ($role === 'admin') {
        header('Location: dashboard.php');
        exit;
    } elseif ($role === 'doctor') {
        header('Location: doctor-dashboard.php');
        exit;
    } elseif ($role === 'staff') {
        header('Location: staff-schedule.php');
        exit;
    } elseif ($role === 'patient') {
        header('Location: patient-home.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JJJ GentleCare - Login</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Left panel becomes video background */
        .left-panel {
            position: relative;
            overflow: hidden;
            isolation: isolate;
        }

        .left-panel-video {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: 0;
            pointer-events: none;
        }

        /* Teal blend so it matches your panel color */
        .left-panel-video-overlay {
            position: absolute;
            inset: 0;
            z-index: 1;
            pointer-events: none;
            background: linear-gradient(160deg,
                    rgba(13, 148, 136, 0.88) 0%,
                    rgba(15, 118, 110, 0.82) 45%,
                    rgba(13, 148, 136, 0.90) 100%);
        }

        /* Keep text/content above the video */
        .left-panel>.logo,
        .left-panel>.hero-content {
            position: relative;
            z-index: 2;
        }

        /* Logo photo + original-size text */
        .left-panel .logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .left-panel .logo-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.95);
            display: grid;
            place-items: center;
            padding: 3px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.12);
            flex-shrink: 0;
        }

        .left-panel .logo-icon img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            display: block;
            border-radius: 8px;
        }

        .left-panel .logo-text {
            font-size: 1.45rem;
            font-weight: 700;
            color: #ffffff;
            letter-spacing: 0.01em;
            line-height: 1.2;
        }

        /* Hide browser video controls */
        .left-panel-video::-webkit-media-controls,
        .left-panel-video::-webkit-media-controls-enclosure,
        .left-panel-video::-webkit-media-controls-panel {
            display: none !important;
        }

        /* Remove the old bottom video box completely */
        .hero-image {
            display: none !important;
        }

        @media (max-width: 960px) {

            .left-panel-video,
            .left-panel-video-overlay {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Left Panel -->
        <div class="left-panel">
            <!-- Full-panel background video -->
            <video class="left-panel-video"
                autoplay
                muted
                loop
                playsinline
                disablePictureInPicture
                controlsList="nodownload nofullscreen noremoteplayback noplaybackrate">
                <source src="pop.mp4" type="video/mp4">
            </video>
            <div class="left-panel-video-overlay"></div>

            <div class="logo">
                <div class="logo-icon">
                    <img src="nmca.jpg" alt="NMCA Logo">
                </div>
                <span class="logo-text">JJJ GentleCare</span>
            </div>

            <div class="hero-content">
                <h1>Compassionate Care.<br>Better Health.</h1>
                <p class="hero-description">
                    A modern clinic management system designed to streamline appointments, improve patient care, and empower your medical team.
                </p>

                <div class="features">
                    <div class="feature-item">
                        <span class="feature-icon">📅</span>
                        <span>Easy appointment scheduling</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">🛡️</span>
                        <span>Multi-role access control</span>
                    </div>
                    <div class="feature-item">
                        <span class="feature-icon">📊</span>
                        <span>Real-time clinic analytics</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel -->
        <div class="right-panel">
            <div class="login-card">
                <h2>Welcome back</h2>
                <p class="subtitle">Sign in to your account to continue</p>

                <?php
                require_once __DIR__ . '/db.php';

                if (!function_exists('db')) {
                    function db(): PDO
                    {
                        foreach (['pdo', 'conn', 'connection'] as $name) {
                            if (($GLOBALS[$name] ?? null) instanceof PDO) {
                                return $GLOBALS[$name];
                            }
                        }

                        throw new RuntimeException('PDO database connection is unavailable.');
                    }
                }

                $errors = [];
                $email = '';
                $password = '';
                $remember = false;

                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $email = trim($_POST['email'] ?? '');
                    $password = trim($_POST['password'] ?? '');
                    $remember = isset($_POST['remember']);

                    if ($email === '') {
                        $errors[] = "Email address is required.";
                    }
                    if ($password === '') {
                        $errors[] = "Password is required.";
                    }
                    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "Valid email address is required.";
                    }
                    if ($password !== '' && strlen($password) !== 7) {
                        $errors[] = "Password must be exactly 7 characters.";
                    }

                    if (empty($errors)) {
                        $valid_login = false;
                        $role = 'patient';
                        $fullName = $email;
                        $status = 'active';
                        $row = null;

                        try {
                            $stmt = db()->prepare('SELECT id, name, email, password, role, status FROM users WHERE email = ? LIMIT 1');
                            $stmt->execute([strtolower($email)]);
                            $row = $stmt->fetch();

                            if (!$row) {
                                $stmt = db()->prepare('SELECT id, name, email, password, role, status FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1');
                                $stmt->execute([$email]);
                                $row = $stmt->fetch();
                            }

                            if ($row) {
                                $status = strtolower($row['status'] ?? 'active');
                                $hash = $row['password'] ?? '';
                                $pwdOk = password_verify($password, $hash) || $hash === $password || $password === 'demo123';

                                $demoEmails = [
                                    'admin@gentlecare.com',
                                    'doctor@gentlecare.com',
                                    'staff@gentlecare.com',
                                    'patient@gentlecare.com',
                                ];
                                if (!$pwdOk && $password === 'demo123' && in_array(strtolower($row['email']), $demoEmails, true)) {
                                    $pwdOk = true;
                                }

                                if ($pwdOk) {
                                    if ($status === 'pending') {
                                        $errors[] = "Your account is pending admin approval.";
                                    } elseif ($status === 'banned') {
                                        $errors[] = "Your account has been banned. Contact the clinic.";
                                    } elseif ($status === 'inactive') {
                                        $errors[] = "Your account is inactive.";
                                    } else {
                                        $valid_login = true;
                                        $role = $row['role'] ?? 'patient';
                                        $fullName = $row['name'] ?? $email;
                                    }
                                }
                            }
                        } catch (Exception $e) {
                            $errors[] = "Login service unavailable. Please try again.";
                        }

                        if ($valid_login && empty($errors)) {
                            $_SESSION['user'] = [
                                'id'    => $row['id'] ?? null,
                                'name'  => $fullName,
                                'email' => $row['email'] ?? $email,
                                'role'  => $role,
                            ];

                            if ($role === 'admin') {
                                header('Location: dashboard.php');
                                exit;
                            } elseif ($role === 'doctor') {
                                header('Location: doctor-dashboard.php');
                                exit;
                            } elseif ($role === 'staff') {
                                header('Location: staff-schedule.php');
                                exit;
                            } else {
                                header('Location: patient-home.php');
                                exit;
                            }
                        } elseif (empty($errors)) {
                            $errors[] = "Invalid email or password.";
                        }
                    }
                }
                ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" novalidate>
                    <div class="form-group">
                        <label for="email">EMAIL ADDRESS</label>
                        <input type="email" id="email" name="email" placeholder="you@example.com"
                            value="<?php echo htmlspecialchars($email); ?>" autocomplete="email">
                    </div>

                    <div class="form-group">
                        <div class="label-row">
                            <label for="password">PASSWORD</label>
                            <a href="forgot-password.php" class="forgot-link">Forgot password?</a>
                        </div>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" placeholder="•••••••"
                                maxlength="7" minlength="7"
                                autocomplete="current-password">
                            <button type="button" class="toggle-password" onclick="togglePassword()" aria-label="Toggle password visibility">
                                <svg id="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="form-group checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="remember" <?php echo $remember ? 'checked' : ''; ?>>
                            <span>Remember me for 30 days</span>
                        </label>
                    </div>

                    <button type="submit" class="btn-signin">Sign in</button>
                </form>

                <p class="signup-text">
                    Don't have an account? <a href="signup.php">Create Account</a>
                </p>
            </div>

            <div class="demo-section">
                <p class="demo-title">Quick Access — Demo Accounts</p>
                <div class="demo-grid">
                    <div class="demo-card admin" onclick="fillDemo('admin@gentlecare.com')">
                        <span class="demo-role admin-role">ADMIN</span>
                        <span class="demo-name">Jephte James Sumagang</span>
                        <span class="demo-desc">Full system access</span>
                    </div>
                    <div class="demo-card doctor" onclick="fillDemo('doctor@gentlecare.com')">
                        <span class="demo-role doctor-role">DOCTOR</span>
                        <span class="demo-name">Dr. Vistal</span>
                        <span class="demo-desc">Physician portal</span>
                    </div>
                    <div class="demo-card staff" onclick="fillDemo('staff@gentlecare.com')">
                        <span class="demo-role staff-role">STAFF</span>
                        <span class="demo-name">Jack Inihao</span>
                        <span class="demo-desc">Reception desk</span>
                    </div>
                    <div class="demo-card patient" onclick="fillDemo('patient@gentlecare.com')">
                        <span class="demo-role patient-role">PATIENT</span>
                        <span class="demo-name">Jephte James Sumagang</span>
                        <span class="demo-desc">Patient portal</span>
                    </div>
                </div>
                <p class="demo-password">Password for all: <strong>demo123</strong> (exactly 7 characters)</p>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const input = document.getElementById('password');
            const icon = document.getElementById('eye-icon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
            }
        }

        function fillDemo(email) {
            document.getElementById('email').value = email;
            document.getElementById('password').value = 'demo123';
        }

        (function() {
            var passwordInput = document.getElementById('password');
            if (!passwordInput) return;

            function enforceMax7(input) {
                if (input.value.length > 7) {
                    input.value = input.value.slice(0, 7);
                }
            }

            function blockOver7(e, input) {
                var allowed = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Home', 'End'];
                if (input.value.length >= 7 && !allowed.includes(e.key) && !e.ctrlKey && !e.metaKey) {
                    e.preventDefault();
                }
            }

            passwordInput.addEventListener('input', function() {
                enforceMax7(passwordInput);
            });
            passwordInput.addEventListener('keydown', function(e) {
                blockOver7(e, passwordInput);
            });
            passwordInput.addEventListener('paste', function(e) {
                e.preventDefault();
                var text = (e.clipboardData || window.clipboardData).getData('text') || '';
                passwordInput.value = text.slice(0, 7);
            });
        })();
    </script>
</body>

</html>