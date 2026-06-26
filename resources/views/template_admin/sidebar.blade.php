<aside class="sidebar glass">

    <div class="logo">
        <div class="logo-icon">T</div>

        <div class="logo-text">
            TechNote
            <span>Admin Panel</span>
        </div>
    </div>

    <nav class="sidebar-nav">

        <!-- DASHBOARD -->
        <a href="{{ route('dashboard.admin') }}" class="nav-item {{ $menu == 'dashboardAdmin' ? 'active' : '' }}" data-nav="dashboard">
            <i data-lucide="layout-dashboard"></i>
            <span class="nav-label">Dashboard</span>
        </a>

        <!-- SERVICE -->
        <div class="nav-group {{ in_array($menu, ['ticket', 'penginstalan', 'perbaikan', 'rekap']) ? 'active open' : '' }}"
            data-group="service">

            <button class="nav-dropdown"
                type="button"
                aria-expanded="{{ in_array($menu, ['ticket', 'penginstalan', 'perbaikan', 'rekap']) ? 'true' : 'false' }}">

                <div class="nav-dropdown-left">
                    <i data-lucide="ticket"></i>
                    <span class="nav-label">Service</span>
                </div>

                <i class="dropdown-arrow" data-lucide="chevron-down"></i>
            </button>

            <!-- SERVICE -->
            <div class="dropdown-menu">
                <a href="{{ route('ticket.index') }}" class="dropdown-item {{ $menu == 'ticket' ? 'active' : '' }}" data-parent="service">
                    <i data-lucide="ticket"></i>
                    <span>Tickets</span>
                </a>

                <a href="{{ route('penginstalan.index') }}" class="dropdown-item {{ $menu == 'penginstalan' ? 'active' : '' }}" data-parent="service">
                    <i data-lucide="download"></i>
                    <span>Penginstalan</span>
                </a>

                <a href="{{ route('perbaikan.index') }}" class="dropdown-item {{ $menu == 'perbaikan' ? 'active' : '' }}" data-parent="service">
                    <i data-lucide="wrench"></i>
                    <span>Perbaikan</span>
                </a>

                <a href="{{ route('rekap.index') }}" class="dropdown-item {{ $menu == 'rekap' ? 'active' : '' }}" data-parent="service">
                    <i data-lucide="clipboard-list"></i>
                    <span>Rekap</span>
                </a>
            </div>

        </div>

        <!-- USERS -->
        <div class="nav-group {{ in_array($menu, ['user', 'role']) ? 'active open' : '' }}">

            <button class="nav-dropdown">

                <div class="nav-dropdown-left">
                    <i data-lucide="users"></i>
                    <span class="nav-label">Users</span>
                </div>

                <i data-lucide="chevron-down" class="dropdown-arrow"></i>

            </button>

            <div class="dropdown-menu">

                <a href="{{ route('user.index') }}"
                    class="dropdown-item {{ $menu == 'user' ? 'active' : '' }}"
                    data-parent="users">

                    <i data-lucide="users-round"></i>
                    <span>User Management</span>

                </a>

                <a href="{{ route('role.index') }}"
                    class="dropdown-item {{ $menu == 'role' ? 'active' : '' }}"
                    data-parent="users">

                    <i data-lucide="shield-check"></i>
                    <span>Roles & Permissions</span>

                </a>

            </div>

        </div>

        <!-- AI -->
        <div class="nav-group {{ in_array($menu, ['ai', 'tasks', 'rekom', 'logs']) ? 'active open' : '' }}">


            <button class="nav-dropdown" type="button" aria-expanded="false">
                <div class="nav-dropdown-left">
                    <i data-lucide="bot"></i>
                    <span class="nav-label">AI System</span>
                </div>

                <i class="dropdown-arrow" data-lucide="chevron-down"></i>
            </button>

            <!-- AI -->
            <div class="dropdown-menu">
                <a href="{{ route('admin.ai.index') }}"
                    class="dropdown-item {{ $menu == 'ai' ? 'active' : '' }}"
                    data-parent="ai">
                    <i data-lucide="layout-dashboard"></i>
                    <span>AI Dashboard</span>
                </a>

                <a href="{{ route('ai.tasks') }}"
                    class="dropdown-item {{ $menu == 'tasks' ? 'active' : '' }}" data-parent="ai">
                    <i data-lucide="list-todo"></i>
                    <span>AI Tasks</span>
                </a>

                <a href="{{ route('ai.rekom') }}"
                    class="dropdown-item {{ $menu == 'rekom' ? 'active' : '' }}" data-parent="ai">
                    <i data-lucide="sparkles"></i>
                    <span>AI Recommendations</span>
                </a>

                <a href="{{ route('ai.log') }}"
                    class="dropdown-item {{ $menu == 'logs' ? 'active' : '' }}" data-parent="ai">
                    <i data-lucide="file-text"></i>
                    <span>AI Logs</span>
                </a>
            </div>

        </div>

        <!-- MONITORING -->
        <div class="nav-group {{ in_array($menu, ['login-log', 'user-activity', 'notifications']) ? 'active open' : '' }}">

            <button class="nav-dropdown" type="button" aria-expanded="false">
                <div class="nav-dropdown-left">
                    <i data-lucide="activity"></i>
                    <span class="nav-label">Monitoring</span>
                </div>

                <i class="dropdown-arrow" data-lucide="chevron-down"></i>
            </button>

            <!-- MONITORING -->
            <div class="dropdown-menu ">
                <a href="{{ route('notifications.index') }}"
                    class="dropdown-item {{ $menu == 'notifications' ? 'active' : '' }}" data-parent="monitoring">
                    <i data-lucide="bell"></i>
                    <span>Notifications</span>
                </a>

                <a href="{{ route('login-log.index') }}"
                    class="dropdown-item {{ $menu == 'login-log' ? 'active' : '' }}" data-parent="monitoring">
                    <i data-lucide="log-in"></i>
                    <span>Login Logs</span>
                </a>

                <a href="{{ route('user-activity.index') }}"
                    class="dropdown-item {{ $menu == 'user-activity' ? 'active' : '' }}" data-parent="monitoring">
                    <i data-lucide="history"></i>
                    <span>User Activities</span>
                </a>
            </div>
        </div>

        <!-- MASTER DATA -->
        <div class="nav-group {{ in_array($menu, ['software', 'trusted']) ? 'active open' : '' }}"
            data-group="master-data">

            <button class="nav-dropdown"
                type="button"
                aria-expanded="{{ in_array($menu, ['software', 'trusted']) ? 'true' : 'false' }}">

                <div class="nav-dropdown-left">
                    <i data-lucide="database"></i>
                    <span class="nav-label">Master Data</span>
                </div>

                <i class="dropdown-arrow" data-lucide="chevron-down"></i>

            </button>

            <div class="dropdown-menu">

                <a href="{{ route('software.index') }}"
                    class="dropdown-item {{ $menu == 'software' ? 'active' : '' }}"
                    data-parent="master-data">

                    <i data-lucide="package"></i>
                    <span>Software</span>

                </a>

                <a href="{{ route('trusted.index') }}"
                    class="dropdown-item {{ $menu == 'trusted' ? 'active' : '' }}"
                    data-parent="master-data">

                    <i data-lucide="globe"></i>
                    <span>Trusted Websites</span>

                </a>

            </div>

        </div>

        <!-- SYSTEM -->
        <div class="nav-group {{ in_array($menu, ['system', 'maintenance']) ? 'active open' : '' }}" data-group="system">

            <button class="nav-dropdown" type="button" aria-expanded="false">
                <div class="nav-dropdown-left">
                    <i data-lucide="settings"></i>
                    <span class="nav-label">System</span>
                </div>

                <i class="dropdown-arrow" data-lucide="chevron-down"></i>
            </button>

            <!-- SYSTEM -->
            <div class="dropdown-menu">
                <a href="{{ route('setting.sistem.index') }}"
                    class="dropdown-item {{ $menu == 'system' ? 'active' : '' }}" data-parent="system">
                    <i data-lucide="settings-2"></i>
                    <span>Settings</span>
                </a>

                <a href="{{ route('setting.maintenance.index') }}"
                    class="dropdown-item {{ $menu == 'maintenance' ? 'active' : '' }}    " data-parent="system">
                    <i data-lucide="shield-alert"></i>
                    <span>Maintenance</span>
                </a>
            </div>

        </div>

    </nav>

</aside>