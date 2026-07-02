<header class="navbar glass">
    <div class="navbar-left">
        <div class="logo" style="padding-top: 40px; z-index:999;">
            <div class="logo-icon">T</div>

          
        </div>
        <div class="search-box">
            <i data-lucide="search"></i>
            <input
                type="text"
                placeholder="Search...">
        </div>

    </div>



    <div class="navbar-right">

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