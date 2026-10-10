<?php
session_start();


if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];
$appointmentsFile = __DIR__ . '/appointments.json';


$appointments = [];
if (file_exists($appointmentsFile)) {
    $appointments = json_decode(file_get_contents($appointmentsFile), true) ?? [];
}


function saveAppointments($file, $data)
{
    file_put_contents($file, json_encode(array_values($data), JSON_PRETTY_PRINT));
}


$errors = [];
$success = '';
$editId = null;
$editData = null;

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // ---------- ADD APPOINTMENT ----------
    if ($action === 'add_appointment') {
        $patient  = trim($_POST['patient'] ?? '');
        $doctor   = trim($_POST['doctor'] ?? '');
        $date     = trim($_POST['date'] ?? '');
        $time     = trim($_POST['time'] ?? '');
        $type     = trim($_POST['type'] ?? '');
        $status   = strtolower(trim($_POST['status'] ?? 'pending'));

        // Validations
        if ($patient === '') {
            $errors[] = "Patient name is required.";
        } elseif (!preg_match("/^[A-Za-z .'-]{2,50}$/", $patient)) {
            $errors[] = "Patient name must be 2-50 letters (spaces, dots, apostrophes allowed).";
        }

        if ($doctor === '') {
            $errors[] = "Doctor is required.";
        }

        if ($date === '') {
            $errors[] = "Date is required.";
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $errors[] = "Date must be in YYYY-MM-DD format.";
        }

        if ($time === '') {
            $errors[] = "Time is required.";
        }

        if ($type === '') {
            $errors[] = "Appointment type is required.";
        }

        $allowedStatuses = ['confirmed', 'pending', 'checked-in', 'completed', 'cancelled', 'no-show'];
        if (!in_array($status, $allowedStatuses)) {
            $errors[] = "Invalid status selected.";
        }

        if (empty($errors)) {
            $timeObj = DateTime::createFromFormat('H:i', $time);
            $displayTime = $timeObj ? $timeObj->format('h:i A') : $time;
            $datetime = $date . ' ' . $displayTime;

            $maxId = 0;
            foreach ($appointments as $a) {
                if (isset($a['id']) && $a['id'] > $maxId) $maxId = $a['id'];
            }

            $appointments[] = [
                'id'       => $maxId + 1,
                'patient'  => $patient,
                'doctor'   => $doctor,
                'datetime' => $datetime,
                'type'     => $type,
                'status'   => $status
            ];

            saveAppointments($appointmentsFile, $appointments);
            $success = "Appointment added successfully!";
        }
    }

    // ---------- EDIT APPOINTMENT ----------
    if ($action === 'edit_appointment') {
        $id       = (int)($_POST['id'] ?? 0);
        $patient  = trim($_POST['patient'] ?? '');
        $doctor   = trim($_POST['doctor'] ?? '');
        $date     = trim($_POST['date'] ?? '');
        $time     = trim($_POST['time'] ?? '');
        $type     = trim($_POST['type'] ?? '');
        $status   = strtolower(trim($_POST['status'] ?? 'pending'));

        if ($patient === '') {
            $errors[] = "Patient name is required.";
        } elseif (!preg_match("/^[A-Za-z .'-]{2,50}$/", $patient)) {
            $errors[] = "Patient name must be 2-50 letters (spaces, dots, apostrophes allowed).";
        }

        if ($doctor === '') {
            $errors[] = "Doctor is required.";
        }

        if ($date === '') {
            $errors[] = "Date is required.";
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $errors[] = "Date must be in YYYY-MM-DD format.";
        }

        if ($time === '') {
            $errors[] = "Time is required.";
        }

        if ($type === '') {
            $errors[] = "Appointment type is required.";
        }

        $allowedStatuses = ['confirmed', 'pending', 'checked-in', 'completed', 'cancelled', 'no-show'];
        if (!in_array($status, $allowedStatuses)) {
            $errors[] = "Invalid status selected.";
        }

        if (empty($errors)) {
            $timeObj = DateTime::createFromFormat('H:i', $time);
            $displayTime = $timeObj ? $timeObj->format('h:i A') : $time;
            $datetime = $date . ' ' . $displayTime;

            foreach ($appointments as &$a) {
                if ((int)$a['id'] === $id) {
                    $a['patient']  = $patient;
                    $a['doctor']   = $doctor;
                    $a['datetime'] = $datetime;
                    $a['type']     = $type;
                    $a['status']   = $status;
                    break;
                }
            }
            unset($a);

            saveAppointments($appointmentsFile, $appointments);
            $success = "Appointment updated successfully!";
        }
    }

    // ---------- QUICK STATUS UPDATE ----------
    if ($action === 'update_status') {
        $id     = (int)($_POST['id'] ?? 0);
        $status = strtolower(trim($_POST['status'] ?? ''));

        $allowedStatuses = ['confirmed', 'pending', 'checked-in', 'completed', 'cancelled', 'no-show'];
        if (!in_array($status, $allowedStatuses)) {
            $errors[] = "Invalid status.";
        } else {
            $found = false;
            foreach ($appointments as &$a) {
                if ((int)$a['id'] === $id) {
                    $a['status'] = $status;
                    $found = true;
                    break;
                }
            }
            unset($a);

            if ($found) {
                saveAppointments($appointmentsFile, $appointments);
                $success = "Status updated to " . ucwords(str_replace('-', ' ', $status)) . "!";
            } else {
                $errors[] = "Appointment not found.";
            }
        }
    }

    // ---------- DELETE APPOINTMENT ----------
    if ($action === 'delete_appointment') {
        $id = (int)($_POST['id'] ?? 0);
        $appointments = array_filter($appointments, function ($a) use ($id) {
            return (int)$a['id'] !== $id;
        });
        saveAppointments($appointmentsFile, $appointments);
        $success = "Appointment deleted successfully!";
    }
}

// Reload after any changes
if (file_exists($appointmentsFile)) {
    $appointments = json_decode(file_get_contents($appointmentsFile), true) ?? [];
}

// ========== FILTER ==========
$currentFilter = isset($_GET['filter']) ? strtolower(trim($_GET['filter'])) : 'all';
$allowedFilters = ['all', 'confirmed', 'pending', 'checked-in', 'completed', 'cancelled', 'no-show'];
if (!in_array($currentFilter, $allowedFilters)) {
    $currentFilter = 'all';
}

$filtered = $appointments;
if ($currentFilter !== 'all') {
    $filtered = array_filter($appointments, function ($a) use ($currentFilter) {
        return strtolower($a['status'] ?? '') === $currentFilter;
    });
}
$filtered = array_values($filtered);
$totalFiltered = count($filtered);

// Pre-fill edit form if ?edit=ID
if (isset($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    foreach ($appointments as $a) {
        if ((int)$a['id'] === $editId) {
            $editData = $a;
            $parts = explode(' ', $a['datetime'], 2);
            $editData['date'] = $parts[0] ?? '';
            if (isset($parts[1])) {
                $t = DateTime::createFromFormat('h:i A', $parts[1]);
                $editData['time'] = $t ? $t->format('H:i') : '';
            }
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <style>
        /* FORCE FILTER TABS */
        .filter-tabs {
            display: flex !important;
            flex-wrap: wrap !important;
            gap: 8px !important;
            margin-bottom: 24px !important;
            background: transparent !important;
            padding: 0 !important;
            border: none !important;
            box-shadow: none !important;
        }

        .filter-tab {
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 8px 18px !important;
            border-radius: 9999px !important;
            font-size: 0.875rem !important;
            font-weight: 600 !important;
            color: #64748b !important;
            text-decoration: none !important;
            background: #ffffff !important;
            border: 1.5px solid #e2e8f0 !important;
            transition: all 0.15s ease !important;
            white-space: nowrap !important;
        }

        .filter-tab:hover {
            background: #f0fdfa !important;
            border-color: #99f6e4 !important;
            color: #0d9488 !important;
        }

        .filter-tab.active {
            background: #0d9488 !important;
            color: #ffffff !important;
            border-color: #0d9488 !important;
            box-shadow: 0 2px 8px rgba(13, 148, 136, 0.25) !important;
        }
    </style>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Appointments - JJJ GentleCare</title>
    <link rel="stylesheet" href="dashboard.css">
</head>

<body>
    <div class="layout">
        <!-- SIDEBAR (same as before) -->
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
                    <div class="user-role"><?php echo ucfirst($user['role']); ?></div>
                </div>
            </div>

            <nav class="nav">
                <a href="dashboard.php" class="nav-item"><span class="nav-icon">▦</span> Dashboard</a>
                <a href="users.php" class="nav-item"><span class="nav-icon">👤</span> Users</a>
                <a href="appointments.php" class="nav-item active"><span class="nav-icon">📅</span> Appointments</a>
                <a href="#" class="nav-item"><span class="nav-icon">🕒</span> Schedules</a>
                <a href="#" class="nav-item"><span class="nav-icon">📊</span> Reports</a>
                <a href="#" class="nav-item"><span class="nav-icon">📋</span> Activity Log</a>
                <a href="#" class="nav-item"><span class="nav-icon">⚙️</span> Settings</a>
            </nav>

            <div class="sidebar-footer">
                <button type="button" class="logout-btn" onclick="openLogoutModal()"><span>↪</span> Log Out</button>
            </div>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main">
            <header class="topbar users-topbar">
                <div>
                    <h1>All Appointments</h1>
                    <p class="date"><?php echo $totalFiltered; ?> total appointments</p>
                </div>
                <button class="btn-add" onclick="openAddModal()">+ Add Appointment</button>
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

            <!-- FILTER TABS -->
            <div class="filter-tabs">
                <a href="?filter=all" class="filter-tab <?php echo $currentFilter === 'all' ? 'active' : ''; ?>">All</a>
                <a href="?filter=confirmed" class="filter-tab <?php echo $currentFilter === 'confirmed' ? 'active' : ''; ?>">Confirmed</a>
                <a href="?filter=pending" class="filter-tab <?php echo $currentFilter === 'pending' ? 'active' : ''; ?>">Pending</a>
                <a href="?filter=checked-in" class="filter-tab <?php echo $currentFilter === 'checked-in' ? 'active' : ''; ?>">Checked-In</a>
                <a href="?filter=completed" class="filter-tab <?php echo $currentFilter === 'completed' ? 'active' : ''; ?>">Completed</a>
                <a href="?filter=cancelled" class="filter-tab <?php echo $currentFilter === 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
                <a href="?filter=no-show" class="filter-tab <?php echo $currentFilter === 'no-show' ? 'active' : ''; ?>">No-Show</a>
            </div>

            <!-- TABLE -->
            <div class="card table-card">
                <table class="users-table appointments-table">
                    <thead>
                        <tr>
                            <th>PATIENT</th>
                            <th>DOCTOR</th>
                            <th>DATE & TIME</th>
                            <th>TYPE</th>
                            <th>STATUS</th>
                            <th>ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($filtered)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center; padding:40px; color:#64748b;">
                                    No appointments found for this filter.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($filtered as $appt): ?>
                                <tr>
                                    <td>
                                        <div class="user-cell">
                                            <div class="table-avatar"><?php echo strtoupper(substr($appt['patient'], 0, 1)); ?></div>
                                            <span><?php echo htmlspecialchars($appt['patient']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($appt['doctor']); ?></td>
                                    <td><?php echo htmlspecialchars($appt['datetime']); ?></td>
                                    <td><?php echo htmlspecialchars($appt['type']); ?></td>
                                    <td>
                                        <?php
                                        $status = strtolower($appt['status'] ?? '');
                                        $label = ucwords(str_replace('-', ' ', $status));
                                        ?>
                                        <span class="appt-status status-<?php echo $status; ?>">
                                            <span class="status-dot"></span>
                                            <?php echo htmlspecialchars($label); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="?edit=<?php echo (int)$appt['id']; ?>&filter=<?php echo urlencode($currentFilter); ?>"
                                                class="btn-action btn-edit-action">Edit</a>

                                            <?php if (!in_array($status, ['checked-in', 'completed', 'cancelled', 'no-show'])): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Check-in this patient?');">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="id" value="<?php echo (int)$appt['id']; ?>">
                                                    <input type="hidden" name="status" value="checked-in">
                                                    <button type="submit" class="btn-action btn-checkin">Check-in</button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if (!in_array($status, ['cancelled', 'completed', 'no-show'])): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Cancel this appointment?');">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="id" value="<?php echo (int)$appt['id']; ?>">
                                                    <input type="hidden" name="status" value="cancelled">
                                                    <button type="submit" class="btn-action btn-cancel">Cancel</button>
                                                </form>
                                            <?php endif; ?>

                                            <?php if ($status === 'checked-in'): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Mark as completed?');">
                                                    <input type="hidden" name="action" value="update_status">
                                                    <input type="hidden" name="id" value="<?php echo (int)$appt['id']; ?>">
                                                    <input type="hidden" name="status" value="completed">
                                                    <button type="submit" class="btn-action btn-complete">Complete</button>
                                                </form>
                                            <?php endif; ?>

                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently delete this appointment?');">
                                                <input type="hidden" name="action" value="delete_appointment">
                                                <input type="hidden" name="id" value="<?php echo (int)$appt['id']; ?>">
                                                <button type="submit" class="btn-action btn-delete">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- ADD / EDIT MODAL -->
    <div class="modal-overlay" id="appointmentModal">
        <div class="modal add-user-modal" style="max-width:520px;">
            <div class="modal-header">
                <h3 id="modalTitle">Add New Appointment</h3>
                <button class="modal-close" onclick="closeAppointmentModal()">×</button>
            </div>
            <form method="POST" id="appointmentForm">
                <input type="hidden" name="action" id="formAction" value="add_appointment">
                <input type="hidden" name="id" id="formId" value="">

                <div class="form-group">
                    <label>PATIENT NAME</label>
                    <input type="text" name="patient" id="formPatient" placeholder="Jephte Sumagang" required
                        pattern="[A-Za-z .'-]{2,50}" title="2-50 letters, spaces, dots, apostrophes">
                </div>

                <div class="form-group">
                    <label>DOCTOR</label>
                    <select name="doctor" id="formDoctor" required>
                        <option value="">Select doctor</option>
                        <option value="Dr. Vistal">Dr. Vistal</option>
                        <option value="Dr. Santos">Dr. Santos</option>
                        <option value="Dr. Reyes">Dr. Reyes</option>
                        <option value="Dr. Cruz">Dr. Cruz</option>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>DATE</label>
                        <input type="date" name="date" id="formDate" required>
                    </div>
                    <div class="form-group">
                        <label>TIME</label>
                        <input type="time" name="time" id="formTime" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>TYPE</label>
                        <select name="type" id="formType" required>
                            <option value="">Select type</option>
                            <option value="Consultation">Consultation</option>
                            <option value="Follow-up">Follow-up</option>
                            <option value="Check-up">Check-up</option>
                            <option value="New Patient">New Patient</option>
                            <option value="Annual Check-up">Annual Check-up</option>
                            <option value="Emergency">Emergency</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>STATUS</label>
                        <select name="status" id="formStatus" required>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="checked-in">Checked-In</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="no-show">No-Show</option>
                        </select>
                    </div>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-stay" onclick="closeAppointmentModal()">Cancel</button>
                    <button type="submit" class="btn-primary" id="submitBtn">Add Appointment</button>
                </div>
            </form>
        </div>
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
        function openAddModal() {
            document.getElementById('modalTitle').textContent = 'Add New Appointment';
            document.getElementById('formAction').value = 'add_appointment';
            document.getElementById('formId').value = '';
            document.getElementById('submitBtn').textContent = 'Add Appointment';
            document.getElementById('formPatient').value = '';
            document.getElementById('formDoctor').value = '';
            document.getElementById('formDate').value = '';
            document.getElementById('formTime').value = '';
            document.getElementById('formType').value = '';
            document.getElementById('formStatus').value = 'pending';
            document.getElementById('appointmentModal').classList.add('show');
        }

        function openEditModal(data) {
            document.getElementById('modalTitle').textContent = 'Edit Appointment';
            document.getElementById('formAction').value = 'edit_appointment';
            document.getElementById('formId').value = data.id;
            document.getElementById('submitBtn').textContent = 'Save Changes';
            document.getElementById('formPatient').value = data.patient || '';
            document.getElementById('formDoctor').value = data.doctor || '';
            document.getElementById('formDate').value = data.date || '';
            document.getElementById('formTime').value = data.time || '';
            document.getElementById('formType').value = data.type || '';
            document.getElementById('formStatus').value = data.status || 'pending';
            document.getElementById('appointmentModal').classList.add('show');
        }

        function closeAppointmentModal() {
            document.getElementById('appointmentModal').classList.remove('show');
            if (window.location.search.includes('edit=')) {
                const url = new URL(window.location);
                url.searchParams.delete('edit');
                window.history.replaceState({}, '', url);
            }
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

        <?php if ($editData): ?>
            document.addEventListener('DOMContentLoaded', function() {
                openEditModal(<?php echo json_encode([
                                    'id'      => $editData['id'],
                                    'patient' => $editData['patient'],
                                    'doctor'  => $editData['doctor'],
                                    'date'    => $editData['date'] ?? '',
                                    'time'    => $editData['time'] ?? '',
                                    'type'    => $editData['type'],
                                    'status'  => $editData['status']
                                ]); ?>);
            });
        <?php endif; ?>
    </script>
</body>

</html>