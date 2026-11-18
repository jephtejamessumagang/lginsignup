<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JJJ GentleCare - Create Account</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Role selector — signup only */
        .role-group {
            margin-bottom: 20px;
        }

        .role-group>label {
            display: block;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.07em;
            text-transform: uppercase;
            color: #475569;
            margin-bottom: 10px;
        }

        .role-options {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .role-option {
            position: relative;
            cursor: pointer;
        }

        .role-option input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .role-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 999px;
            border: 1.5px solid #e2e8f0;
            background: #f8fafc;
            font-size: 0.82rem;
            font-weight: 600;
            color: #64748b;
            transition: all 0.15s ease;
            user-select: none;
        }

        .role-option input:checked+.role-pill.role-patient {
            background: #0d9488;
            border-color: #0d9488;
            color: #fff;
            box-shadow: 0 2px 8px rgba(13, 148, 136, 0.25);
        }

        .role-option input:checked+.role-pill.role-doctor {
            background: #059669;
            border-color: #059669;
            color: #fff;
            box-shadow: 0 2px 8px rgba(5, 150, 105, 0.25);
        }

        .role-option input:checked+.role-pill.role-staff {
            background: #4f46e5;
            border-color: #4f46e5;
            color: #fff;
            box-shadow: 0 2px 8px rgba(79, 70, 229, 0.25);
        }

        .role-option:hover .role-pill {
            border-color: #99f6e4;
        }

        .role-hint {
            margin-top: 8px;
            font-size: 0.75rem;
            color: #94a3b8;
            line-height: 1.4;
        }

        .role-desc {
            font-weight: 500;
            color: #64748b;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Left Panel -->
        <div class="left-panel">
            <div class="logo">
                <div class="logo-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" fill="white" />
                    </svg>
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

            <div class="hero-image">
                <img src="photo.jpg" alt="Medical professional working">
            </div>
        </div>

        <!-- Right Panel -->
        <div class="right-panel">
            <div class="signup-card">
                <h2>Create Account</h2>
                <p class="subtitle">Join JJJ GentleCare today</p>

                <?php
                $errors = [];
                $name = '';
                $email = '';
                $age = '';
                $role = 'patient';
                $password = '';
                $confirm = '';
                $success = false;
                require_once __DIR__ . '/log_activity.php';
                require_once __DIR__ . '/db.php';

                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $name     = trim($_POST['name'] ?? '');
                    $email    = trim($_POST['email'] ?? '');
                    $age      = trim($_POST['age'] ?? '');
                    $role     = strtolower(trim($_POST['role'] ?? 'patient'));
                    $password = trim($_POST['password'] ?? '');
                    $confirm  = trim($_POST['confirm'] ?? '');

                    $allowedRoles = ['patient', 'doctor', 'staff'];
                    if (!in_array($role, $allowedRoles, true)) {
                        $role = 'patient';
                    }

                    if ($name === '') {
                        $errors[] = "Name is required.";
                    }
                    if ($email === '') {
                        $errors[] = "Email address is required.";
                    }
                    if ($age === '') {
                        $errors[] = "Age is required.";
                    }
                    if ($password === '') {
                        $errors[] = "Password is required.";
                    }
                    if ($confirm === '') {
                        $errors[] = "Please confirm your password.";
                    }

                    if ($name !== '' && !preg_match("/^[A-Za-z .'-]{2,50}$/", $name)) {
                        $errors[] = "Name must be 2-50 letters (spaces, dots, apostrophes allowed).";
                    }

                    if ($password !== '' && strlen($password) !== 7) {
                        $errors[] = "Password must be exactly 7 characters.";
                    }

                    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = "Valid email address is required.";
                    }

                    if ($age !== '') {
                        $age_val = filter_var($age, FILTER_VALIDATE_INT, [
                            'options' => ['min_range' => 1, 'max_range' => 80]
                        ]);
                        if ($age_val === false) {
                            $errors[] = "Age must be a whole number from 1 to 80.";
                        }
                    }

                    if ($password !== '' && $confirm !== '' && $password !== $confirm) {
                        $errors[] = "Passwords do not match.";
                    }

                    if (empty($errors)) {
                        try {
                            $stmt = db()->prepare('SELECT id FROM users WHERE LOWER(email) = LOWER(?) LIMIT 1');
                            $stmt->execute([$email]);
                            if ($stmt->fetch()) {
                                $errors[] = "This email is already registered.";
                            }
                        } catch (Exception $e) {
                            $errors[] = "Could not verify email. Please try again.";
                        }
                    }

                    if (empty($errors)) {
                        try {
                            $hash = password_hash($password, PASSWORD_DEFAULT);
                            $stmt = db()->prepare(
                                'INSERT INTO users (name, email, password, role, status, age) VALUES (?, ?, ?, ?, ?, ?)'
                            );
                            $stmt->execute([
                                $name,
                                strtolower($email),
                                $hash,
                                $role,
                                'pending',
                                (int)$age
                            ]);
                            $userId = (int) db()->lastInsertId();

                            $stmt2 = db()->prepare(
                                'INSERT INTO profiles (user_id, full_name, phone) VALUES (?, ?, ?)'
                            );
                            $stmt2->execute([$userId, $name, '']);

                            $success = true;
                            if (function_exists('log_activity')) {
                                log_activity($name, 'Requested a new ' . $role . ' account (pending approval)', 'info');
                            }
                        } catch (PDOException $e) {
                            if ((int)$e->getCode() === 23000) {
                                $errors[] = "This email is already registered.";
                            } else {
                                $errors[] = "Could not create account. Please try again.";
                            }
                        }
                    }
                }
                ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Account created! Please wait for an administrator to approve your account before signing in.
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!$success): ?>
                    <form method="POST" action="" novalidate>
                        <div class="form-group">
                            <label for="name">FULL NAME</label>
                            <input type="text" id="name" name="name" placeholder="James Sumalinog" value="<?php echo htmlspecialchars($name); ?>">
                        </div>

                        <div class="form-group">
                            <label for="email">EMAIL ADDRESS</label>
                            <input type="email" id="email" name="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($email); ?>">
                        </div>

                        <div class="form-group">
                            <label for="age">AGE</label>
                            <input type="text" id="age" name="age" placeholder="25" value="<?php echo htmlspecialchars($age); ?>">
                        </div>

                        <!-- ACCOUNT ROLE -->
                        <div class="role-group">
                            <label>ACCOUNT ROLE</label>
                            <div class="role-options">
                                <label class="role-option">
                                    <input type="radio" name="role" value="patient" <?php echo $role === 'patient' ? 'checked' : ''; ?>>
                                    <span class="role-pill role-patient">PATIENT</span>
                                </label>
                                <label class="role-option">
                                    <input type="radio" name="role" value="doctor" <?php echo $role === 'doctor' ? 'checked' : ''; ?>>
                                    <span class="role-pill role-doctor">DOCTOR</span>
                                </label>
                                <label class="role-option">
                                    <input type="radio" name="role" value="staff" <?php echo $role === 'staff' ? 'checked' : ''; ?>>
                                    <span class="role-pill role-staff">STAFF</span>
                                </label>
                            </div>
                            <p class="role-hint">
                                <span class="role-desc" id="roleDesc">Public patient portal access</span>
                            </p>
                        </div>

                        <div class="form-group">
                            <label for="password">PASSWORD (exactly 7 characters)</label>
                            <div class="password-wrapper">
                                <input type="password" id="password" name="password" placeholder="•••••••" maxlength="7" minlength="7" autocomplete="new-password">
                                <button type="button" class="toggle-password" onclick="togglePass('password','eye1')" aria-label="Toggle password">
                                    <svg id="eye1" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm">CONFIRM PASSWORD</label>
                            <div class="password-wrapper">
                                <input type="password" id="confirm" name="confirm" placeholder="•••••••" maxlength="7" minlength="7" autocomplete="new-password">
                                <button type="button" class="toggle-password" onclick="togglePass('confirm','eye2')" aria-label="Toggle password">
                                    <svg id="eye2" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <button type="submit" class="btn-signin">Create Account</button>
                    </form>
                <?php endif; ?>

                <p class="signup-text">
                    Already have an account? <a href="index.php">Sign in</a>
                </p>
            </div>
        </div>
    </div>

    <script>
        function togglePass(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            if (input.type === 'password') {
                input.type = 'text';
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
            } else {
                input.type = 'password';
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
            }
        }

        // Role description
        (function() {
            var desc = document.getElementById('roleDesc');
            if (!desc) return;
            var texts = {
                patient: 'Public patient portal access',
                doctor: 'Physician portal access',
                staff: 'Clinic staff / reception access'
            };
            document.querySelectorAll('input[name="role"]').forEach(function(radio) {
                radio.addEventListener('change', function() {
                    desc.textContent = texts[this.value] || '';
                });
                if (radio.checked) {
                    desc.textContent = texts[radio.value] || '';
                }
            });
        })();

        // 7-character password limit
        (function() {
            var passwordInput = document.getElementById('password');
            var confirmInput = document.getElementById('confirm');
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

            if (confirmInput) {
                confirmInput.addEventListener('input', function() {
                    enforceMax7(confirmInput);
                });
                confirmInput.addEventListener('keydown', function(e) {
                    blockOver7(e, confirmInput);
                });
                confirmInput.addEventListener('paste', function(e) {
                    e.preventDefault();
                    var text = (e.clipboardData || window.clipboardData).getData('text') || '';
                    confirmInput.value = text.slice(0, 7);
                });
            }
        })();
    </script>
</body>

</html>