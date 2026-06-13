<div class="tn-modal-overlay">

    <div class="glass card tn-modal-card">

        <div class="tn-modal-header">

            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">Edit Profile</h2>
                <p class="tn-modal-subtitle">
                    Update your personal account information.
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>

        </div>

        <form
            action="{{ route('settings.profile.update') }}"
            method="POST"
            enctype="multipart/form-data"
            class="tn-modal-form">

            @csrf
            @method('PUT')

            <div class="tn-modal-grid">

                <div class="tn-modal-group tn-modal-full">
                    <label>Profile Picture</label>

                    <input
                        type="file"
                        name="avatar"
                        class="tn-modal-control"
                        accept="image/*">
                </div>

                <div class="tn-modal-group">
                    <label>Full Name</label>
                    <input
                        type="text"
                        name="name"
                        class="tn-modal-control"
                        value="{{ auth()->user()->name }}"
                        required>
                </div>

                <div class="tn-modal-group">
                    <label>Username</label>
                    <input
                        type="text"
                        name="username"
                        class="tn-modal-control"
                        value="{{ auth()->user()->username }}"
                        required>
                </div>

                <div class="tn-modal-group">
                    <label>Email</label>
                    <input
                        type="email"
                        name="email"
                        class="tn-modal-control"
                        value="{{ auth()->user()->email }}">
                </div>

                <div class="tn-modal-group">
                    <label>Phone Number</label>
                    <input
                        type="text"
                        name="no_hp"
                        class="tn-modal-control"
                        value="{{ auth()->user()->no_hp }}"
                        required>
                </div>

            </div>

            <div class="tn-modal-actions">

                <button type="button" class="btn-secondary close-modal">
                    <i data-lucide="x"></i>
                    Cancel
                </button>

                <button type="submit" class="btn-primary">
                    <i data-lucide="save"></i>
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>