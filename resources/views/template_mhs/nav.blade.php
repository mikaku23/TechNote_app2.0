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

        @if(Auth::check())
        <div class="profile-wrapper">
            @php
            $user = Auth::user();

            $foto = $user->avatar && file_exists(public_path('storage/' . $user->avatar))
            ? asset('storage/' . $user->avatar)
            : asset('assets/images/default.png');
            @endphp
            <div class="profile" id="profileMenuButton">

                <img src="{{ $foto }}" class="avatar rounded-circle me-2" alt="profile">

                <div>
                    <h6 class="mb-0 caption-title">{{ $user->name }}</h6>
                    <p class="mb-0 caption-sub-title text-muted">
                        {{ $user->role->name ?? '-' }}
                    </p>
                </div>

            </div>

            <div class="profile-dropdown glass" id="profileDropdown">
                <button
                    type="button"
                    class="profile-dropdown-link open-modal"
                    data-url="{{ route('profile.show') }}">
                    <i data-lucide="user"></i>
                    <span>View Profile</span>
                </button>

                <button
                    type="button"
                    class="profile-dropdown-link open-modal"
                    data-url="{{ route('settings.show') }}">
                    <i data-lucide="settings"></i>
                    <span>Settings</span>
                </button>

                <hr>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf

                    <button type="submit" class="profile-dropdown-link logout">
                        <i data-lucide="log-out"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>

        </div>
        @endif
    </div>

</header>