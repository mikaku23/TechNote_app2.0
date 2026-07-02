<aside class="sidebar glass">

    <div class="logo">
        <div class="logo-icon">T</div>

        <div class="logo-text">
            TechNote
            <span>Dosen</span>
        </div>
    </div>

    <nav class="sidebar-nav">

        <!-- DASHBOARD -->
        <a href="{{ route('dosen.index') }}" class="nav-item {{ $menu == 'dosen' ? 'active' : '' }}" data-nav="booking">
            <i data-lucide="calendar"></i>
            <span class="nav-label">Booking</span>
        </a>

    </nav>

</aside>