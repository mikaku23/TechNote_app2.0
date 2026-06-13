<div class="tn-modal-overlay">
    <div class="glass card tn-modal-card">
        <div class="tn-modal-header">
            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">Settings</h2>
                <p class="tn-modal-subtitle">
                    Manage your account, security, and privacy.
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>
        </div>

        @php
        $user = Auth::user();
        @endphp

        <div class="tn-settings-grid">
            <button
                type="button"
                class="tn-settings-card open-modal"
                data-url="{{ route('settings.profile') }}">
                <div class="tn-settings-card-icon">
                    <i data-lucide="user-round-cog"></i>
                </div>
                <div class="tn-settings-card-text">
                    <h3>Edit Profile</h3>
                    <p>Nama, email, no HP, dan avatar</p>
                </div>
                <i data-lucide="chevron-right" class="tn-settings-arrow"></i>
            </button>

            <button
                type="button"
                class="tn-settings-card open-modal"
                data-url="{{ route('settings.password') }}">
                <div class="tn-settings-card-icon">
                    <i data-lucide="lock-keyhole"></i>
                </div>
                <div class="tn-settings-card-text">
                    <h3>Change Password</h3>
                    <p>Password lama, password baru, konfirmasi</p>
                </div>
                <i data-lucide="chevron-right" class="tn-settings-arrow"></i>
            </button>

            <button
                type="button"
                class="tn-settings-card open-modal"
                data-url="{{ route('settings.security') }}">
                <div class="tn-settings-card-icon">
                    <i data-lucide="shield-check"></i>
                </div>
                <div class="tn-settings-card-text">
                    <h3>Security Question</h3>
                    <p>Pertanyaan dan jawaban keamanan</p>
                </div>
                <i data-lucide="chevron-right" class="tn-settings-arrow"></i>
            </button>
        </div>

        <div class="tn-settings-danger-box">
            <div class="tn-settings-danger-header">
                <div class="tn-settings-danger-icon">
                    <i data-lucide="triangle-alert"></i>
                </div>
                <div>
                    <h3>Delete Account</h3>
                    <p>Hapus akun ini bersifat permanen dan tidak bisa dibatalkan.</p>
                </div>
            </div>

            <form action="{{ route('settings.destroy') }}" method="POST">
                @csrf
                @method('DELETE')

                <button
                    type="button"
                    class="btn-danger-full open-modal"
                    data-url="{{ route('settings.delete') }}">

                    <i data-lucide="trash-2"></i>
                    Delete My Account

                </button>
            </form>
        </div>

        <div class="tn-modal-actions">
            <button type="button" class="btn-secondary close-modal">
                <i data-lucide="x"></i>
                Close
            </button>
        </div>
    </div>
</div>