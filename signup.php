<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JJJ GentleCare - Create Account</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Signup password strength only — does not affect login */
        .password-strength {
            margin-top: 10px;
        }

        .strength-bars {
            display: flex;
            gap: 6px;
            margin-bottom: 6px;
        }

        .strength-bar {
            flex: 1;
            height: 5px;
            border-radius: 999px;
            background: #e2e8f0;
            transition: background 0.2s ease;
        }

        .strength-bar.active-weak {
            background: #ef4444;
        }

        .strength-bar.active-mid {
            background: #f97316;
        }

        .strength-bar.active-strong {
            background: #22c55e;
        }

        .strength-label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #94a3b8;
            min-height: 1.1em;
        }

        .strength-label.weak {
            color: #ef4444;
        }

        .strength-label.mid {
            color: #f97316;
        }

        .strength-label.strong {
            color: #22c55e;
        }

        .char-count {
            font-size: 0.75rem;
            color: #94a3b8;
            text-align: right;
            margin-top: 4px;
        }

        .char-count.full {
            color: #0d9488;
            font-weight: 600;
        }

        .password-hint {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 4px;
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
                <img src="https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?w=600&q=80" alt="Medical professional working">
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
                $password = '';
                $confirm = '';
                $success = false;
                $usersFile = __DIR__ . '/users.json';

                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    $name     = trim($_POST['name'] ?? '');
                    $email    = trim($_POST['email'] ?? '');
                    $age      = trim($_POST['age'] ?? '');
                    $password = trim($_POST['password'] ?? '');
                    $confirm  = trim($_POST['confirm'] ?? '');

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

                    // Exactly 7 characters only
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
                        $demo_emails = [
                            'admin@gentlecare.com',
                            'doctor@gentlecare.com',
                            'staff@gentlecare.com',
                            'patient@gentlecare.com',
                        ];

                        if (in_array(strtolower($email), $demo_emails)) {
                            $errors[] = "This email is already registered.";
                        } else {
                            $users = [];
                            if (file_exists($usersFile)) {
                                $users = json_decode(file_get_contents($usersFile), true) ?? [];
                            }
                            foreach ($users as $user) {
                                if (strtolower($user['email']) === strtolower($email)) {
                                    $errors[] = "This email is already registered.";
                                    break;
                                }
                            }
                        }
                    }

                    if (empty($errors)) {
                        $users = [];
                        if (file_exists($usersFile)) {
                            $users = json_decode(file_get_contents($usersFile), true) ?? [];
                        }

                        $users[] = [
                            'name'     => $name,
                            'email'    => $email,
                            'age'      => (int)$age,
                            'password' => password_hash($password, PASSWORD_DEFAULT),
                            'created'  => date('Y-m-d H:i:s'),
                        ];

                        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
                        $success = true;
                    }
                }
                ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        Account created successfully! You can now <a href="index.php" style="color:#16a34a;font-weight:600;">sign in</a>.
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
                            <div class="char-count" id="charCount">0 / 7</div>
                            <div class="password-strength">
                                <div class="strength-bars">
                                    <div class="strength-bar" id="bar1"></div>
                                    <div class="strength-bar" id="bar2"></div>
                                    <div class="strength-bar" id="bar3"></div>
                                </div>
                                <div class="strength-label" id="strengthLabel">Enter password</div>
                            </div>
                            <p class="password-hint">Password must be exactly 7 characters. 7 characters = Strong.</p>
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

        (function() {
            var passwordInput = document.getElementById('password');
            var confirmInput = document.getElementById('confirm');
            if (!passwordInput) return;

            var bar1 = document.getElementById('bar1');
            var bar2 = document.getElementById('bar2');
            var bar3 = document.getElementById('bar3');
            var strengthLabel = document.getElementById('strengthLabel');
            var charCount = document.getElementById('charCount');

            function enforceMax7(input) {
                if (input.value.length > 7) {
                    input.value = input.value.slice(0, 7);
                }
            }

            function updateStrength() {
                enforceMax7(passwordInput);
                var len = passwordInput.value.length;
                charCount.textContent = len + ' / 7';
                charCount.classList.toggle('full', len === 7);

                [bar1, bar2, bar3].forEach(function(b) {
                    b.className = 'strength-bar';
                });
                strengthLabel.className = 'strength-label';
                strengthLabel.textContent = 'Enter password';

                if (len === 0) return;

                // 1–2 Weak (red) | 3–5 Medium (orange) | 6–7 Strong (green)
                if (len <= 2) {
                    bar1.classList.add('active-weak');
                    strengthLabel.textContent = 'Weak';
                    strengthLabel.classList.add('weak');
                } else if (len <= 5) {
                    bar1.classList.add('active-mid');
                    bar2.classList.add('active-mid');
                    strengthLabel.textContent = 'Medium';
                    strengthLabel.classList.add('mid');
                } else {
                    bar1.classList.add('active-strong');
                    bar2.classList.add('active-strong');
                    bar3.classList.add('active-strong');
                    strengthLabel.textContent = len === 7 ? 'Strong' : 'Almost strong';
                    strengthLabel.classList.add('strong');
                }
            }

            function blockOver7(e, input) {
                var allowed = ['Backspace', 'Delete', 'ArrowLeft', 'ArrowRight', 'Tab', 'Home', 'End'];
                if (input.value.length >= 7 && !allowed.includes(e.key) && !e.ctrlKey && !e.metaKey) {
                    e.preventDefault();
                }
            }

            passwordInput.addEventListener('input', updateStrength);
            passwordInput.addEventListener('keydown', function(e) {
                blockOver7(e, passwordInput);
            });
            passwordInput.addEventListener('paste', function(e) {
                e.preventDefault();
                var text = (e.clipboardData || window.clipboardData).getData('text') || '';
                passwordInput.value = text.slice(0, 7);
                updateStrength();
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

            updateStrength();
        })();
    </script>
</body>

</html>