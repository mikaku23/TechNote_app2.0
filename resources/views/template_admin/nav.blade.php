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

        <div class="notification-wrapper">

            <button id="notificationButton" class="icon-btn">
                <i data-lucide="bell"></i>
            </button>

            <div class="notification-dropdown glass" id="notificationDropdown">

                <div class="notification-header">
                    <h4>Notifications</h4>
                    <span>4 New</span>
                </div>

                <div class="notification-list">

                    <div class="notification-item">
                        <i data-lucide="ticket"></i>
                        <div>
                            <strong>New Ticket</strong>
                            <p>SRV-2026-004 created</p>
                        </div>
                    </div>

                    <div class="notification-item">
                        <i data-lucide="check-circle"></i>
                        <div>
                            <strong>Ticket Completed</strong>
                            <p>INS-2026-002 finished</p>
                        </div>
                    </div>

                    <div class="notification-item">
                        <i data-lucide="user-plus"></i>
                        <div>
                            <strong>New User</strong>
                            <p>Mahasiswa baru terdaftar</p>
                        </div>
                    </div>

                    <div class="notification-item">
                        <i data-lucide="bot"></i>
                        <div>
                            <strong>AI Report</strong>
                            <p>Daily summary generated</p>
                        </div>
                    </div>

                </div>

            </div>

        </div>

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