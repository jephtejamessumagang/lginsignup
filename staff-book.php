<?php
session_start();

// Protect page - only staff can access
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'staff') {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
$appointmentsFile = __DIR__ . '/appointments.json';

// Load appointments
$appointments = [];
if (file_exists($appointmentsFile)) {
    $appointments = json_decode(file_get_contents($appointmentsFile), true) ?? [];
}

$errors = [];
$success = '';

// Form values
$patient  = '';
$date     = '';
$time     = '08:00 AM';
$type     = 'Consultation';
$notes    = '';
$doctor   = 'Dr. Vistal';

// Time options
$timeOptions = [
    '08:00 AM',
    '09:00 AM',
    '10:00 AM',
    '11:00 AM',
    '01:00 PM',
    '02:00 PM',
    '03:00 PM',
    '04:00 PM'
];

// Type options
$typeOptions = [
    'Consultation',
    'Follow-up',
    'Check-up',
    'New Patient',
    'Emergency'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book') {
    $patient = trim($_POST['patient'] ?? '');
    $date    = trim($_POST['date'] ?? '');
    $time    = trim($_POST['time'] ?? '');
    $type    = trim($_POST['type'] ?? '');
    $notes   = trim($_POST['notes'] ?? '');
    $doctor  = trim($_POST['doctor'] ?? 'Dr. Vistal');

    // Validations
    if ($patient === '') {
        $errors[] = "Patient name is required.";
    } elseif (strlen($patient) < 2 || strlen($patient) > 60) {
        $errors[] = "Patient name must be between 2 and 60 characters.";
    } elseif (!preg_match("/^[a-zA-Z\s\.\-']+$/", $patient)) {
        $errors[] = "Patient name contains invalid characters.";
    }

    if ($date === '') {
        $errors[] = "Date is required.";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $errors[] = "Invalid date format.";
    }

    if ($time === '' || !in_array($time, $timeOptions)) {
        $errors[] = "Please select a valid time.";
    }

    if ($type === '' || !in_array($type, $typeOptions)) {
        $errors[] = "Please select a valid appointment type.";
    }

    if (strlen($notes) > 200) {
        $errors[] = "Notes must not exceed 200 characters.";
    }

    // Check for duplicate slot
    if (empty($errors)) {
        $datetime = $date . ' ' . $time;
        foreach ($appointments as $a) {
            if ($a['datetime'] === $datetime && $a['doctor'] === $doctor && !in_array(strtolower($a['status']), ['cancelled', 'no-show'])) {
                $errors[] = "This time slot is already booked for {$doctor}.";
                break;
            }
        }
    }

    if (empty($errors)) {
        // Generate new ID
        $maxId = 0;
        foreach ($appointments as $a) {
            if ((int)$a['id'] > $maxId) $maxId = (int)$a['id'];
        }
        $newId = $maxId + 1;

        $newAppt = [
            'id'       => $newId,
            'patient'  => $patient,
            'doctor'   => $doctor,
            'datetime' => $date . ' ' . $time,
            'type'     => $type,
            'status'   => 'pending',
            'notes'    => $notes
        ];

        $appointments[] = $newAppt;
        file_put_contents($appointmentsFile, json_encode(array_values($appointments), JSON_PRETTY_PRINT));

        $success = "Appointment booked successfully for {$patient}!";
        // Clear form
        $patient = $date = $notes = '';
        $time = '08:00 AM';
        $type = 'Consultation';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - JJJ GentleCare</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .book-card {
            background: white;
            border-radius: 18px;
            padding: 32px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
            max-width: 480px;
            margin: 0 auto;
        }

        .book-card .form-group {
            margin-bottom: 18px;
        }

        .book-card .form-group label {
            display: block;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: #475569;
            margin-bottom: 7px;
            text-transform: uppercase;
        }

        .book-card .form-group input,
        .book-card .form-group select,
        .book-card .form-group textarea {
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

        .book-card .form-group input:focus,
        .book-card .form-group select:focus,
        .book-card .form-group textarea:focus {
            outline: none;
            border-color: #0d9488;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.12);
        }

        .book-card .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .btn-book {
            width: 100%;
            background: linear-gradient(135deg, #0d9488, #0f766e);
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

        .btn-book:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(13, 148, 136, 0.3);
        }

        @media (max-width: 500px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .book-card {
                padding: 22px;
            }
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
                <div class="avatar"><?php echo strtoupper(substr($user['name'], 0, 1)); ?></div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                    <div class="user-role">Clinic Staff</div>
                </div>
            </div>

            <nav class="nav">
                <a href="staff-schedule.php" class="nav-item">
                    <span class="nav-icon">🕒</span> Today's Schedule
                </a>
                <a href="staff-book.php" class="nav-item active">
                    <span class="nav-icon">＋</span> Book Appointment
                </a>
                <a href="staff-queue.php" class="nav-item">
                    <span class="nav-icon">👥</span> Patient Queue
                </a>
                <a href="staff-appointments.php" class="nav-item">
                    <span class="nav-icon">📋</span> All Appointments
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
                    <h1>Book Appointment</h1>
                    <p class="date">Schedule a new appointment</p>
                </div>
            </header>

            <?php if ($success): ?>
                <div class="alert alert-success" style="margin-bottom:20px; max-width:480px; margin-left:auto; margin-right:auto;">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-error" style="margin-bottom:20px; max-width:480px; margin-left:auto; margin-right:auto;">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="book-card">
                <form method="POST">
                    <input type="hidden" name="action" value="book">

                    <div class="form-group">
                        <label>PATIENT NAME</label>
                        <input type="text" name="patient" placeholder="Search or enter patient name"
                            value="<?php echo htmlspecialchars($patient); ?>" required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>DATE</label>
                            <input type="date" name="date" value="<?php echo htmlspecialchars($date); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>TIME</label>
                            <select name="time" required>
                                <?php foreach ($timeOptions as $t): ?>
                                    <option value="<?php echo $t; ?>" <?php echo $time === $t ? 'selected' : ''; ?>>
                                        <?php echo $t; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>APPOINTMENT TYPE</label>
                        <select name="type" required>
                            <?php foreach ($typeOptions as $ty): ?>
                                <option value="<?php echo $ty; ?>" <?php echo $type === $ty ? 'selected' : ''; ?>>
                                    <?php echo $ty; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>NOTES (OPTIONAL)</label>
                        <textarea name="notes" placeholder="Any relevant notes..."><?php echo htmlspecialchars($notes); ?></textarea>
                    </div>

                    <button type="submit" class="btn-book">Book Appointment</button>
                </form>
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