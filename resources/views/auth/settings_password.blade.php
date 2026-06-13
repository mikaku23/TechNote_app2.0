<div class="tn-modal-overlay">

    <div class="glass card tn-modal-card">

        <div class="tn-modal-header">

            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">Change Password</h2>
                <p class="tn-modal-subtitle">
                    Update your account password.
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>

        </div>

        <form
            action="{{ route('settings.password.update') }}"
            method="POST"
            class="tn-modal-form">

            @csrf
            @method('PUT')

            <div class="tn-modal-grid">

                <div class="tn-modal-group tn-modal-full">
                    <label>Current Password</label>
                    <input
                        type="password"
                        name="current_password"
                        class="tn-modal-control"
                        required>
                </div>

                <div class="tn-modal-group">
                    <label>New Password</label>
                    <input
                        type="password"
                        name="password"
                        class="tn-modal-control"
                        required>
                </div>

                <div class="tn-modal-group">
                    <label>Confirm Password</label>
                    <input
                        type="password"
                        name="password_confirmation"
                        class="tn-modal-control"
                        required>
                </div>

            </div>

            <div class="tn-modal-actions">

                <button type="button" class="btn-secondary close-modal">
                    <i data-lucide="x"></i>
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn-primary"
                    data-tn-confirm
                    data-tn-type="warning"
                    data-tn-title="Change password?"
                    data-tn-message="You will need to use the new password on your next login."
                    data-tn-proceed-text="Change Password">

                    <i data-lucide="key-round"></i>
                    Update Password

                </button>

            </div>

        </form>

    </div>

</div>