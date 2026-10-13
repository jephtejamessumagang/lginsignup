<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'patient') {
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

$appointmentsFile = __DIR__ . '/appointments.json';

$appointments = [];
if (file_exists($appointmentsFile)) {
    $appointments = json_decode(file_get_contents($appointmentsFile), true) ?? [];
}

$errors = [];
$success = '';
$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($step < 1 || $step > 2) $step = 1;

$typeOptions = [
    'Consultation'       => 'with Dr. Vistal · General Medicine',
    'Follow-up'          => 'with Dr. Vistal · General Medicine',
    'Check-up'           => 'with Dr. Vistal · General Medicine',
    'Lab Results Review' => 'with Dr. Vistal · General Medicine',
];

$timeOptions = ['09:00 AM', '10:00 AM', '11:00 AM', '01:00 PM', '02:00 PM', '03:00 PM'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'step1') {
        $type = trim($_POST['type'] ?? '');
        if ($type === '' || !isset($typeOptions[$type])) {
            $errors[] = "Please select an appointment type.";
            $step = 1;
        } else {
            $_SESSION['book_type'] = $type;
            header('Location: patient-book.php?step=2');
            exit;
        }
    }

    if ($action === 'step2') {
        $type  = $_SESSION['book_type'] ?? '';
        $date  = trim($_POST['date'] ?? '');
        $time  = trim($_POST['time'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $doctor = 'Dr. Vistal';

        if ($type === '' || !isset($typeOptions[$type])) {
            $errors[] = "Please go back and select an appointment type.";
            $step = 1;
        }
        if ($date === '') {
            $errors[] = "Date is required.";
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $errors[] = "Invalid date format.";
        }
        if ($time === '' || !in_array($time, $timeOptions)) {
            $errors[] = "Please select a preferred time.";
        }
        if (strlen($notes) > 200) {
            $errors[] = "Notes must not exceed 200 characters.";
        }

        if (empty($errors)) {
            $datetime = $date . ' ' . $time;
            foreach ($appointments as $a) {
                if ($a['datetime'] === $datetime && $a['doctor'] === $doctor && !in_array(strtolower($a['status']), ['cancelled', 'no-show'])) {
                    $errors[] = "This time slot is already booked.";
                    break;
                }
            }
        }

        if (empty($errors)) {
            $maxId = 0;
            foreach ($appointments as $a) {
                if ((int)$a['id'] > $maxId) $maxId = (int)$a['id'];
            }
            $newId = $maxId + 1;

            $appointments[] = [
                'id'       => $newId,
                'patient'  => $user['name'],
                'doctor'   => $doctor,
                'datetime' => $date . ' ' . $time,
                'type'     => $type,
                'status'   => 'pending',
                'notes'    => $notes
            ];
            file_put_contents($appointmentsFile, json_encode(array_values($appointments), JSON_PRETTY_PRINT));
            unset($_SESSION['book_type']);
            $success = "Appointment request submitted successfully!";
            $step = 2;
        } else {
            $step = 2;
        }
    }
}

$selectedType = $_SESSION['book_type'] ?? '';
$selectedTime = $_POST['time'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment - JJJ GentleCare</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
        .book-wrap {
            max-width: 460px;
            margin: 0 auto;
        }

        .page-title {
            text-align: center;
            margin-bottom: 6px;
        }

        .page-title h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
        }

        .step-label {
            text-align: center;
            font-size: 0.88rem;
            color: #64748b;
            margin-bottom: 12px;
        }

        .progress-bar {
            height: 5px;
            background: #e2e8f0;
            border-radius: 999px;
            margin: 0 auto 28px auto;
            max-width: 460px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: #0d9488;
            border-radius: 999px;
            transition: width 0.3s ease;
        }

        .progress-fill.step1 {
            width: 50%;
        }

        .progress-fill.step2 {
            width: 100%;
        }

        .book-card {
            background: white;
            border-radius: 18px;
            padding: 28px 26px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.04);
        }

        .book-card h3 {
            font-size: 1.05rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 18px;
        }

        /* Step 1 - Type options */
        .type-option {
            display: block;
            width: 100%;
            text-align: left;
            padding: 15px 18px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            background: white;
            cursor: pointer;
            margin-bottom: 12px;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .type-option:hover {
            border-color: #99f6e4;
            background: #f0fdfa;
        }

        .type-option.selected {
            border-color: #0d9488;
            background: #f0fdfa;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.12);
        }

        .type-option .type-name {
            font-weight: 600;
            font-size: 0.95rem;
            color: #1e293b;
            display: block;
        }

        .type-option .type-sub {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 3px;
            display: block;
        }

        .btn-next {
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

        .btn-next:hover {
            background: #0f766e;
            transform: translateY(-1px);
        }

        /* Step 2 */
        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            color: #64748b;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .form-group input[type="date"],
        .form-group textarea {
            width: 100%;
            padding: 12px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.95rem;
            color: #1e293b;
            background: white;
            font-family: inherit;
        }

        .form-group input[type="date"]:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #0d9488;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.12);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 72px;
        }

        .time-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .time-btn {
            padding: 9px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
            background: white;
            font-size: 0.85rem;
            font-weight: 500;
            color: #475569;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.15s;
        }

        .time-btn:hover {
            border-color: #99f6e4;
            background: #f0fdfa;
            color: #0d9488;
        }

        .time-btn.selected {
            border-color: #0d9488;
            background: #0d9488;
            color: white;
        }

        .step2-actions {
            display: flex;
            gap: 12px;
            margin-top: 8px;
        }

        .btn-back {
            flex: 1;
            padding: 12px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            background: #f8fafc;
            color: #64748b;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.15s;
        }

        .btn-back:hover {
            background: #f1f5f9;
        }

        .btn-submit {
            flex: 1.3;
            padding: 12px;
            border: none;
            border-radius: 12px;
            background: #0d9488;
            color: white;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            font-family: inherit;
            transition: background 0.15s;
        }

        .btn-submit:hover {
            background: #0f766e;
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
                        <img src="<?php echo htmlspecialchars($sidebarAvatar); ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                    <?php else: ?>
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($user['name']); ?></div>
                    <div class="user-role">Patient</div>
                </div>
            </div>

            <nav class="nav">
                <a href="patient-home.php" class="nav-item">
                    <span class="nav-icon">🏠</span> Home
                </a>
                <a href="patient-book.php" class="nav-item active">
                    <span class="nav-icon">＋</span> Book Appointment
                </a>
                <a href="patient-appointments.php" class="nav-item">
                    <span class="nav-icon">📅</span> My Appointments
                </a>
                <a href="patient-profile.php" class="nav-item">
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
            <div class="book-wrap">
                <div class="page-title">
                    <h1>Book Appointment</h1>
                </div>
                <p class="step-label">Step <?php echo $step; ?> of 2</p>
                <div class="progress-bar">
                    <div class="progress-fill step<?php echo $step; ?>"></div>
                </div>

                <?php if ($success): ?>
                    <div class="alert alert-success" style="margin-bottom:20px; text-align:center;">
                        <?php echo htmlspecialchars($success); ?>
                        <div style="margin-top:12px;">
                            <a href="patient-home.php" style="color:#0f766e;font-weight:600;">← Back to Home</a>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-error" style="margin-bottom:20px;">
                        <?php foreach ($errors as $e): ?>
                            <div><?php echo htmlspecialchars($e); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if ($step === 1 && !$success): ?>
                    <!-- STEP 1 -->
                    <div class="book-card">
                        <h3>Select Appointment Type</h3>
                        <form method="POST" id="step1Form">
                            <input type="hidden" name="action" value="step1">
                            <input type="hidden" name="type" id="selectedType" value="<?php echo htmlspecialchars($selectedType); ?>">

                            <?php foreach ($typeOptions as $name => $sub): ?>
                                <button type="button"
                                    class="type-option <?php echo $selectedType === $name ? 'selected' : ''; ?>"
                                    data-type="<?php echo htmlspecialchars($name); ?>"
                                    onclick="selectType(this)">
                                    <span class="type-name"><?php echo htmlspecialchars($name); ?></span>
                                    <span class="type-sub"><?php echo htmlspecialchars($sub); ?></span>
                                </button>
                            <?php endforeach; ?>

                            <button type="submit" class="btn-next">Next</button>
                        </form>
                    </div>

                <?php elseif ($step === 2 && !$success): ?>
                    <!-- STEP 2 -->
                    <div class="book-card">
                        <h3>Choose Date & Time</h3>
                        <form method="POST" id="step2Form">
                            <input type="hidden" name="action" value="step2">
                            <input type="hidden" name="time" id="selectedTime" value="<?php echo htmlspecialchars($selectedTime); ?>">

                            <div class="form-group">
                                <label>PREFERRED DATE</label>
                                <input type="date" name="date" required>
                            </div>

                            <div class="form-group">
                                <label>PREFERRED TIME</label>
                                <div class="time-grid">
                                    <?php foreach ($timeOptions as $t): ?>
                                        <button type="button"
                                            class="time-btn <?php echo $selectedTime === $t ? 'selected' : ''; ?>"
                                            data-time="<?php echo htmlspecialchars($t); ?>"
                                            onclick="selectTime(this)">
                                            <?php echo htmlspecialchars($t); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>NOTES (OPTIONAL)</label>
                                <textarea name="notes" placeholder="Describe your symptoms or reason for visit..."></textarea>
                            </div>

                            <div class="step2-actions">
                                <button type="button" class="btn-back" onclick="window.location='patient-book.php?step=1'">Back</button>
                                <button type="submit" class="btn-submit">Submit Request</button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
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

        function selectType(btn) {
            document.querySelectorAll('.type-option').forEach(function(el) {
                el.classList.remove('selected');
            });
            btn.classList.add('selected');
            document.getElementById('selectedType').value = btn.getAttribute('data-type');
        }

        function selectTime(btn) {
            document.querySelectorAll('.time-btn').forEach(function(el) {
                el.classList.remove('selected');
            });
            btn.classList.add('selected');
            document.getElementById('selectedTime').value = btn.getAttribute('data-time');
        }

        document.getElementById('step1Form')?.addEventListener('submit', function(e) {
            if (!document.getElementById('selectedType').value) {
                e.preventDefault();
                alert('Please select an appointment type.');
            }
        });

        document.getElementById('step2Form')?.addEventListener('submit', function(e) {
            if (!document.getElementById('selectedTime').value) {
                e.preventDefault();
                alert('Please select a preferred time.');
            }
        });
    </script>
</body>

</html>