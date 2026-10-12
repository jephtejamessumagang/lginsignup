<?php
session_start();

// If already logged in, redirect
if (isset($_SESSION['user'])) {
    $role = $_SESSION['user']['role'] ?? 'patient';
    if ($role === 'admin') {
        header('Location: dashboard.php');
    } elseif ($role === 'doctor') {
        header('Location: doctor-dashboard.php');
    } elseif ($role === 'staff') {
        header('Location: staff-schedule.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

$errors = [];
$success = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    } else {
        // Check if email exists in demo accounts or users.json
        $found = false;
        $demo_emails = [
            'admin@gentlecare.com',
            'doctor@gentlecare.com',
            'staff@gentlecare.com',
            'patient@gentlecare.com'
        ];

        if (in_array(strtolower($email), $demo_emails)) {
            $found = true;
        } else {
            $usersFile = __DIR__ . '/users.json';
            if (file_exists($usersFile)) {
                $users = json_decode(file_get_contents($usersFile), true) ?? [];
                foreach ($users as $user) {
                    if (strtolower($user['email']) === strtolower($email)) {
                        $found = true;
                        break;
                    }
                }
            }
        }

        if ($found) {
            $success = "A password reset link has been sent to <strong>" . htmlspecialchars($email) . "</strong>. Please check your inbox.";
            $email = '';
        } else {
            $errors[] = "No account found with that email address.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - JJJ GentleCare</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 0.88rem;
            color: #64748b;
            text-decoration: none;
            margin-bottom: 24px;
            transition: color 0.15s;
        }

        .back-link:hover {
            color: #0d9488;
        }

        .lock-icon {
            width: 48px;
            height: 48px;
            background: #f0fdfa;
            border-radius: 14px;
            display: grid;
            place-items: center;
            margin-bottom: 18px;
        }

        .forgot-card h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .forgot-card .desc {
            font-size: 0.92rem;
            color: #64748b;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .btn-reset {
            width: 100%;
            background: linear-gradient(135deg, #0d9488, #14b8a6);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: transform 0.15s, box-shadow 0.2s;
            margin-top: 8px;
        }

        .btn-reset:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(13, 148, 136, 0.3);
        }

        .remember-link {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
            color: #64748b;
        }

        .remember-link a {
            color: #0d9488;
            text-decoration: none;
            font-weight: 600;
        }

        .remember-link a:hover {
            text-decoration: underline;
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
            <div class="login-card forgot-card">
                <a href="index.php" class="back-link">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 12H5M12 19l-7-7 7-7" />
                    </svg>
                    Back to Login
                </a>

                <div class="lock-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0d9488" stroke-width="2">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                </div>

                <h2>Forgot Password</h2>
                <p class="desc">Enter your email address and we'll send you a link to reset your password.</p>

                <?php if ($success): ?>
                    <div class="alert alert-success" style="margin-bottom:18px;">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error" style="margin-bottom:18px;">
                        <?php foreach ($errors as $error): ?>
                            <div><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" novalidate>
                    <div class="form-group">
                        <label for="email">EMAIL ADDRESS</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="you@example.com"
                            value="<?php echo htmlspecialchars($email); ?>"
                            autocomplete="email"
                            required>
                    </div>

                    <button type="submit" class="btn-reset">Send Reset Link</button>
                </form>

                <p class="remember-link">
                    Remembered it? <a href="index.php">Back to Login</a>
                </p>
            </div>
        </div>
    </div>
</body>

</html>