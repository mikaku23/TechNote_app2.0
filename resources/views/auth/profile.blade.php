<div class="tn-modal-overlay">
    <div class="glass card tn-modal-card">
        <div class="tn-modal-header">
            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">My Profile</h2>
                <p class="tn-modal-subtitle">
                    Your account information and role details.
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>
        </div>

        @php
        $user = Auth::user();

        $foto = $user->avatar && file_exists(public_path('storage/' . $user->avatar))
        ? asset('storage/' . $user->avatar)
        : asset('assets/images/default.png');
        @endphp

        <div class="tn-profile-hero">
            <img src="{{ $foto }}" alt="Profile Photo" class="tn-profile-avatar">

            <div class="tn-profile-hero-text">
                <h3>{{ $user->name }}</h3>
                <p>{{ $user->role->name ?? '-' }}</p>
                <span>{{ $user->username }}</span>
            </div>
        </div>

        <div class="tn-modal-grid">
            <div class="tn-modal-group">
                <label>Full Name</label>
                <div class="detail-box">{{ $user->name }}</div>
            </div>

            <div class="tn-modal-group">
                <label>Role</label>
                <div class="detail-box">{{ $user->role->name ?? '-' }}</div>
            </div>

            <div class="tn-modal-group">
                <label>Username</label>
                <div class="detail-box">{{ $user->username }}</div>
            </div>

            <div class="tn-modal-group">
                <label>Email</label>
                <div class="detail-box">{{ $user->email ?? '-' }}</div>
            </div>

            <div class="tn-modal-group">
                <label>NIM</label>
                <div class="detail-box">{{ $user->nim ?? '-' }}</div>
            </div>

            <div class="tn-modal-group">
                <label>NIP</label>
                <div class="detail-box">{{ $user->nip ?? '-' }}</div>
            </div>

            <div class="tn-modal-group">
                <label>No HP</label>
                <div class="detail-box">{{ $user->no_hp }}</div>
            </div>

            <div class="tn-modal-group">
                <label>Last Login</label>
                <div class="detail-box">
                    {{ $user->last_login_at ? $user->last_login_at->format('d M Y H:i') : '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Password Changed</label>
                <div class="detail-box">
                    {{ $user->last_password_changed_at ? $user->last_password_changed_at->format('d M Y H:i') : '-' }}
                </div>
            </div>

            <div class="tn-modal-group tn-modal-full">
                <label>Security Question</label>
                <div class="detail-box">{{ $user->security_question ?? '-' }}</div>
            </div>

        </div>

        <div class="tn-modal-actions">
            <button type="button" class="btn-secondary close-modal">
                <i data-lucide="x"></i>
                Close
            </button>
        </div>
    </div>
</div>