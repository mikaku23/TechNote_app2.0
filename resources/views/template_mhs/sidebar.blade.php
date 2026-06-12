<aside class="sidebar glass">

    <div class="logo">
        <div class="logo-icon">T</div>

        <div class="logo-text">
            TechNote
            <span>Mahasiswa</span>
        </div>
    </div>

    <nav class="sidebar-nav">

        <!-- DASHBOARD -->
        <a href="{{ route('dashboard.mhs') }}" class="nav-item {{ $menu == 'dashboardMhs' ? 'active' : '' }}" data-nav="dashboard">
            <i data-lucide="layout-dashboard"></i>
            <span class="nav-label">Dashboard</span>
        </a>
        <a href="{{ route('mahasiswa.booking.index') }}" class="nav-item {{ $menu == 'booking' ? 'active' : '' }}" data-nav="dashboard">
            <i data-lucide="layout-dashboard"></i>
            <span class="nav-label">Dashboard</span>
        </a>

    </nav>

</aside>