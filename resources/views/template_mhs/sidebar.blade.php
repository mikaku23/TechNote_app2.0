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
        <a href="{{ route('mahasiswa.booking.index') }}" class="nav-item {{ $menu == 'booking' ? 'active' : '' }}" data-nav="booking">
            <i data-lucide="calendar"></i>
            <span class="nav-label">Booking</span>
        </a>

    </nav>

</aside>