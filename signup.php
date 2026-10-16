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
                $usersFile = __DIR__ . '/users.json';

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
                            'role'     => $role,
                            'status'   => 'active',
                            'password' => password_hash($password, PASSWORD_DEFAULT),
                            'created'  => date('Y-m-d H:i:s'),
                        ];

                        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT));
                        $success = true;
                        log_activity($name, 'Created a new ' . $role . ' account', 'info');
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
                            <input type="email" id="email" name="email" placeholder="james@example.com" value="<?php echo htmlspecialchars($email); ?>">
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>