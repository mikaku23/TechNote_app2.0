

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TechNote 2.0 Admin</title>

    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Lucide -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
</head>

<body>

    <!-- Floating Background -->
    <div class="bg-gradient"></div>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <!-- Sidebar -->
    <aside class="sidebar glass">

        <div class="logo">
            <div class="logo-icon">
                T
            </div>

            <div class="logo-text">
                TechNote
                <span>Admin Panel</span>
            </div>
        </div>

        <nav>

            <div class="nav-title">
                MAIN
            </div>

            <a href="#" class="nav-item active">
                <i data-lucide="layout-dashboard"></i>
                <span class="nav-label">
                    Dashboard
                </span>
            </a>

            <a href="#" class="nav-item">
                <i data-lucide="users"></i>

                <span class="nav-label">
                    Users
                </span>
            </a>

            <a href="#" class="nav-item">
                <i data-lucide="ticket"></i>

                <span class="nav-label">
                    Tickets
                </span>
            </a>

            <a href="#" class="nav-item">
                <i data-lucide="monitor-smartphone"></i>

                <span class="nav-label">
                    Software
                </span>
            </a>

            <div class="nav-title">
                AI SYSTEM
            </div>

            <a href="#" class="nav-item">
                <i data-lucide="bot"></i>
                <span class="nav-label">
                    AI Dashboard
                </span>
            </a>

            <a href="#" class="nav-item">
                <i data-lucide="sparkles"></i>
                <span class="nav-label">
                    AI Tasks
                </span>
            </a>

            <a href="#" class="nav-item">
                <i data-lucide="brain-circuit"></i>
                <span class="nav-label">
                    AI Logs
                </span>
            </a>

            <div class="nav-title">
                SYSTEM
            </div>

            <a href="#" class="nav-item">
                <i data-lucide="settings"></i>
                <span class="nav-label">
                    Settings
                </span>
            </a>

            <a href="#" class="nav-item">
                <i data-lucide="shield"></i>
                <span class="nav-label">
                    Anti AI Mode
                </span>
            </a>

        </nav>

    </aside>

    <!-- Main -->
    <main class="main">

        <!-- Navbar -->
        <header class="navbar glass">

            <div class="navbar-left">

                <button id="sidebarToggle" class="icon-btn">
                    <i data-lucide="menu"></i>
                </button>

                <div class="search-box">
                    <i data-lucide="search"></i>
                    <input
                        type="text"
                        placeholder="Search...">
                </div>

            </div>

            <div class="navbar-right">

                <button class="icon-btn">
                    <i data-lucide="bell"></i>
                </button>

                <button id="themeToggle" class="icon-btn">
                    <i data-lucide="moon"></i>
                </button>

                <div class="profile-wrapper">

                    <div class="profile" id="profileMenuButton">

                        <img
                            src="https://i.pravatar.cc/100"
                            alt="">

                        <div>
                            <h4>Administrator</h4>
                            <span>Super Admin</span>
                        </div>

                    </div>

                    <div
                        class="profile-dropdown glass"
                        id="profileDropdown">

                        <a href="#">
                            <i data-lucide="user"></i>
                            <span>View Profile</span>
                        </a>

                        <a href="#">
                            <i data-lucide="settings"></i>
                            <span>Settings</span>
                        </a>

                        <hr>

                        <a href="#">
                            <i data-lucide="log-out"></i>
                            <span>Logout</span>
                        </a>

                    </div>

                </div>

            </div>

        </header>

        <!-- Dashboard -->
        <section class="dashboard">

            <div class="page-header">

                <div>
                    <h1>Dashboard</h1>
                    <p>
                        Welcome back to TechNote 2.0
                    </p>
                </div>

                <button class="btn-primary">
                    <i data-lucide="plus"></i>
                    New Ticket
                </button>

            </div>

            <!-- Stats -->

            <div class="stats-grid">

                <div class="card glass stat-card motion-card">
                    <div>
                        <span>Total Users</span>
                        <h2>1,245</h2>
                    </div>
                    <i data-lucide="users"></i>
                </div>

                <div class="card glass stat-card motion-card">
                    <div>
                        <span>Total Tickets</span>
                        <h2>865</h2>
                    </div>
                    <i data-lucide="ticket"></i>
                </div>

                <div class="card glass stat-card motion-card">
                    <div>
                        <span>Installations</span>
                        <h2>412</h2>
                    </div>
                    <i data-lucide="download"></i>
                </div>

                <div class="card glass stat-card motion-card">
                    <div>
                        <span>Repairs</span>
                        <h2>453</h2>
                    </div>
                    <i data-lucide="wrench"></i>
                </div>

            </div>

            <!-- AI Summary -->

            <div class="glass ai-summary motion-card">

                <div class="ai-header">

                    <div>
                        <h2>
                            TechNote AI Summary
                        </h2>

                        <p>
                            AI Agent Insights Today
                        </p>
                    </div>

                    <i data-lucide="sparkles"></i>

                </div>

                <div class="ai-content">

                    <div class="ai-box">
                        <span>Active Tickets</span>
                        <h3>12</h3>
                    </div>

                    <div class="ai-box">
                        <span>Delayed</span>
                        <h3>2</h3>
                    </div>

                    <div class="ai-box">
                        <span>Recommendation</span>
                        <h3>
                            Prioritize SRV-2026-0023
                        </h3>
                    </div>

                </div>

            </div>

            <!-- Content -->

            <div class="content-grid">

                <!-- Table -->

                <div class="glass table-card motion-card">

                    <div class="card-header">

                        <h3>
                            Recent Tickets
                        </h3>

                        <button class="btn-secondary">
                            View All
                        </button>

                    </div>

                    <table>

                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>User</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>

                        <tbody>

                            <tr>
                                <td>SRV-2026-001</td>
                                <td>Haliq</td>
                                <td>
                                    <span class="badge warning">
                                        Processing
                                    </span>
                                </td>
                                <td>Today</td>
                            </tr>

                            <tr>
                                <td>INS-2026-002</td>
                                <td>Ahmad</td>
                                <td>
                                    <span class="badge success">
                                        Completed
                                    </span>
                                </td>
                                <td>Today</td>
                            </tr>

                            <tr>
                                <td>SRV-2026-003</td>
                                <td>Dosen A</td>
                                <td>
                                    <span class="badge danger">
                                        Delayed
                                    </span>
                                </td>
                                <td>Today</td>
                            </tr>

                        </tbody>

                    </table>

                </div>

                <!-- Activity -->

                <div class="glass activity-card motion-card">

                    <div class="card-header">
                        <h3>
                            Activity
                        </h3>
                    </div>

                    <div class="activity-list">

                        <div class="activity-item">
                            <i data-lucide="check-circle"></i>
                            Ticket Completed
                        </div>

                        <div class="activity-item">
                            <i data-lucide="bot"></i>
                            AI Analysis Generated
                        </div>

                        <div class="activity-item">
                            <i data-lucide="user-plus"></i>
                            New User Registered
                        </div>

                        <div class="activity-item">
                            <i data-lucide="shield"></i>
                            Anti AI Enabled
                        </div>

                    </div>

                </div>

            </div>

        </section>

        <!-- Footer -->

        <footer class="footer glass">

            <p>
                © 2026 TechNote 2.0
            </p>

            <span>
                Built for Muhammad Haliq Maulana
            </span>

        </footer>

    </main>

    <script src="{{ asset('assets/js/script.js') }}"></script>

</body>

</html>