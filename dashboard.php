<?php
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

$user = $_SESSION['user'];

// Load profile avatar + sync name for sidebar
$sidebarAvatar = '';
$profilesFileSidebar = __DIR__ . '/profiles.json';
if (file_exists($profilesFileSidebar)) {
    $allProfiles = json_decode(file_get_contents($profilesFileSidebar), true) ?? [];
    $key = strtolower($user['email'] ?? $user['name'] ?? '');
    if (isset($allProfiles[$key]['avatar']) && $allProfiles[$key]['avatar'] && file_exists(__DIR__ . '/' . $allProfiles[$key]['avatar'])) {
        $sidebarAvatar = $allProfiles[$key]['avatar'];
    }
    // Show updated full name from Profile on every page
    if (!empty($allProfiles[$key]['full_name'])) {
        $_SESSION['user']['name'] = $allProfiles[$key]['full_name'];
        $user['name'] = $allProfiles[$key]['full_name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - JJJ GentleCare</title>
    <link rel="stylesheet" href="dashboard.css">
    <style>
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
                <div class="avatar">
                    <?php if (!empty($sidebarAvatar)): ?>
                        <img src="<?php echo htmlspecialchars($sidebarAvatar); ?>" alt="">
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
                <a href="dashboard.php" class="nav-item active">
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
                    <h1>Dashboard</h1>
                    <p class="date" id="selectedDateText">Wednesday, September 17, 2026</p>
                </div>
            </header>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon calendar">📅</div>
                    <div class="stat-info">
                        <div class="stat-value" id="statAppointments">12</div>
                        <div class="stat-label">Appointments</div>
                        <div class="stat-change up" id="statAppointmentsChange">+3 from yesterday</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon patients">👥</div>
                    <div class="stat-info">
                        <div class="stat-value" id="statPatients">348</div>
                        <div class="stat-label">Total Patients</div>
                        <div class="stat-change up" id="statPatientsChange">+12 this month</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon doctors">👨‍⚕️</div>
                    <div class="stat-info">
                        <div class="stat-value" id="statDoctors">6</div>
                        <div class="stat-label">Active Doctors</div>
                        <div class="stat-change neutral" id="statDoctorsChange">All on duty</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon completed">✅</div>
                    <div class="stat-info">
                        <div class="stat-value" id="statCompleted">7</div>
                        <div class="stat-label">Completed</div>
                        <div class="stat-change up" id="statCompletedChange">58% completion</div>
                    </div>
                </div>
            </div>

            <!-- Bottom Section -->
            <div class="bottom-grid">
                <!-- Calendar -->
                <div class="card calendar-card">
                    <div class="card-header">
                        <h3 id="calendarMonth">September 2026</h3>
                        <div class="calendar-nav">
                            <button type="button" onclick="changeMonth(-1)">‹</button>
                            <button type="button" onclick="changeMonth(1)">›</button>
                        </div>
                    </div>
                    <div class="calendar">
                        <div class="weekdays">
                            <span>SUN</span><span>MON</span><span>TUE</span><span>WED</span>
                            <span>THU</span><span>FRI</span><span>SAT</span>
                        </div>
                        <div class="days" id="calendarDays">
                            <!-- Days will be generated by JavaScript -->
                        </div>
                    </div>
                </div>

                <!-- Appointments List -->
                <div class="card appointments-card">
                    <div class="card-header">
                        <h3 id="appointmentsTitle">Appointments on Sep 17</h3>
                    </div>
                    <div class="appointments-list" id="appointmentsList">
                        <!-- Appointments will be generated by JavaScript -->
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
        // ========== SAMPLE DATA FOR DIFFERENT DAYS ==========
        const dayData = {
            "2026-09-10": {
                appointments: 8,
                patients: 340,
                doctors: 5,
                completed: 6,
                appChange: "-2 from previous",
                patChange: "+5 this month",
                docChange: "1 off duty",
                compChange: "75% completion",
                list: [{
                        name: "Maria Santos",
                        time: "09:00 AM · Consultation",
                        status: "confirmed",
                        initial: "MS"
                    },
                    {
                        name: "Roberto Cruz",
                        time: "10:30 AM · Follow-up",
                        status: "checkedin",
                        initial: "RC"
                    },
                    {
                        name: "Ana Reyes",
                        time: "01:00 PM · Check-up",
                        status: "confirmed",
                        initial: "AR"
                    }
                ]
            },
            "2026-09-15": {
                appointments: 15,
                patients: 345,
                doctors: 6,
                completed: 11,
                appChange: "+4 from previous",
                patChange: "+9 this month",
                docChange: "All on duty",
                compChange: "73% completion",
                list: [{
                        name: "Jephte Sumagang",
                        time: "08:30 AM · Follow-up",
                        status: "confirmed",
                        initial: "JS"
                    },
                    {
                        name: "James Vistal",
                        time: "10:00 AM · Consultation",
                        status: "checkedin",
                        initial: "JV"
                    },
                    {
                        name: "Maria Santos",
                        time: "11:30 AM · New Patient",
                        status: "pending",
                        initial: "MS"
                    },
                    {
                        name: "Ana Reyes",
                        time: "02:00 PM · Check-up",
                        status: "confirmed",
                        initial: "AR"
                    }
                ]
            },
            "2026-09-17": {
                appointments: 12,
                patients: 348,
                doctors: 6,
                completed: 7,
                appChange: "+3 from yesterday",
                patChange: "+12 this month",
                docChange: "All on duty",
                compChange: "58% completion",
                list: [{
                        name: "Jephte Sumagang",
                        time: "09:00 AM · Follow-up",
                        status: "confirmed",
                        initial: "JS"
                    },
                    {
                        name: "Maria Santos",
                        time: "10:30 AM · Consultation",
                        status: "checkedin",
                        initial: "MS"
                    },
                    {
                        name: "Roberto Cruz",
                        time: "11:00 AM · New Patient",
                        status: "pending",
                        initial: "RC"
                    },
                    {
                        name: "Ana Reyes",
                        time: "01:00 PM · Check-up",
                        status: "confirmed",
                        initial: "AR"
                    }
                ]
            },
            "2026-09-20": {
                appointments: 9,
                patients: 350,
                doctors: 6,
                completed: 0,
                appChange: "Upcoming",
                patChange: "+14 this month",
                docChange: "All on duty",
                compChange: "Not started",
                list: [{
                        name: "Jack Inihao",
                        time: "09:00 AM · Consultation",
                        status: "pending",
                        initial: "JI"
                    },
                    {
                        name: "Maria Santos",
                        time: "11:00 AM · Follow-up",
                        status: "pending",
                        initial: "MS"
                    },
                    {
                        name: "Roberto Cruz",
                        time: "02:30 PM · Check-up",
                        status: "pending",
                        initial: "RC"
                    }
                ]
            },
            "2026-09-25": {
                appointments: 14,
                patients: 355,
                doctors: 5,
                completed: 0,
                appChange: "Upcoming",
                patChange: "+19 this month",
                docChange: "1 off duty",
                compChange: "Not started",
                list: [{
                        name: "Jephte Sumagang",
                        time: "08:00 AM · New Patient",
                        status: "pending",
                        initial: "JS"
                    },
                    {
                        name: "Ana Reyes",
                        time: "10:00 AM · Consultation",
                        status: "pending",
                        initial: "AR"
                    },
                    {
                        name: "James Vistal",
                        time: "01:00 PM · Follow-up",
                        status: "pending",
                        initial: "JV"
                    },
                    {
                        name: "Maria Santos",
                        time: "03:00 PM · Check-up",
                        status: "pending",
                        initial: "MS"
                    }
                ]
            }
        };

        function getDefaultData(day) {
            const base = 5 + (day % 10);
            return {
                appointments: base + 3,
                patients: 340 + day,
                doctors: 5 + (day % 2),
                completed: Math.floor(base * 0.6),
                appChange: day < 17 ? "Past day" : "Upcoming",
                patChange: `+${day} this month`,
                docChange: (day % 2 === 0) ? "All on duty" : "1 off duty",
                compChange: day < 17 ? `${Math.floor(60 + day)}% completion` : "Not started",
                list: [{
                        name: "Sample Patient",
                        time: "09:00 AM · Consultation",
                        status: day < 17 ? "confirmed" : "pending",
                        initial: "SP"
                    },
                    {
                        name: "Another Patient",
                        time: "11:00 AM · Follow-up",
                        status: day < 17 ? "checkedin" : "pending",
                        initial: "AP"
                    }
                ]
            };
        }

        let currentYear = 2026;
        let currentMonth = 8;
        let selectedDay = 17;

        const monthNames = ["January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"
        ];
        const dayNames = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];

        function renderCalendar() {
            const daysContainer = document.getElementById('calendarDays');
            daysContainer.innerHTML = '';

            const firstDay = new Date(currentYear, currentMonth, 1).getDay();
            const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

            document.getElementById('calendarMonth').textContent = `${monthNames[currentMonth]} ${currentYear}`;

            for (let i = 0; i < firstDay; i++) {
                const empty = document.createElement('span');
                empty.className = 'empty';
                daysContainer.appendChild(empty);
            }

            for (let day = 1; day <= daysInMonth; day++) {
                const span = document.createElement('span');
                span.textContent = day;
                span.dataset.day = day;

                if (day === selectedDay && currentMonth === 8 && currentYear === 2026) {
                    span.classList.add('today');
                }

                span.addEventListener('click', function() {
                    selectDay(day);
                });

                daysContainer.appendChild(span);
            }
        }

        function selectDay(day) {
            selectedDay = day;

            document.querySelectorAll('#calendarDays span').forEach(span => {
                span.classList.remove('today');
                if (parseInt(span.dataset.day) === day) {
                    span.classList.add('today');
                }
            });

            const dateObj = new Date(currentYear, currentMonth, day);
            const dayName = dayNames[dateObj.getDay()];
            document.getElementById('selectedDateText').textContent =
                `${dayName}, ${monthNames[currentMonth]} ${day}, ${currentYear}`;

            document.getElementById('appointmentsTitle').textContent =
                `Appointments on ${monthNames[currentMonth].substring(0,3)} ${day}`;

            const dateKey = `${currentYear}-${String(currentMonth+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
            const data = dayData[dateKey] || getDefaultData(day);

            document.getElementById('statAppointments').textContent = data.appointments;
            document.getElementById('statAppointmentsChange').textContent = data.appChange;
            document.getElementById('statPatients').textContent = data.patients;
            document.getElementById('statPatientsChange').textContent = data.patChange;
            document.getElementById('statDoctors').textContent = data.doctors;
            document.getElementById('statDoctorsChange').textContent = data.docChange;
            document.getElementById('statCompleted').textContent = data.completed;
            document.getElementById('statCompletedChange').textContent = data.compChange;

            const listContainer = document.getElementById('appointmentsList');
            listContainer.innerHTML = '';

            data.list.forEach(item => {
                const div = document.createElement('div');
                div.className = 'appointment';
                div.innerHTML = `
                    <div class="appt-avatar">${item.initial}</div>
                    <div class="appt-info">
                        <div class="appt-name">${item.name}</div>
                        <div class="appt-time">${item.time}</div>
                    </div>
                    <span class="status ${item.status}">${item.status.charAt(0).toUpperCase() + item.status.slice(1)}</span>
                `;
                listContainer.appendChild(div);
            });
        }

        function changeMonth(direction) {
            currentMonth += direction;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            } else if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            selectedDay = 1;
            renderCalendar();
            selectDay(1);
        }

        function openLogoutModal() {
            document.getElementById('logoutModal').classList.add('show');
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.remove('show');
        }
        document.getElementById('logoutModal').addEventListener('click', function(e) {
            if (e.target === this) closeLogoutModal();
        });

        renderCalendar();
        selectDay(17);
    </script>
</body>

</html>