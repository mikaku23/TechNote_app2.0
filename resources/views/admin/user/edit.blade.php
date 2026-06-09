<div class="tn-modal-overlay">

    <div class="glass card tn-modal-card">

        <div class="tn-modal-header">

            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">Edit User</h2>
                <p class="tn-modal-subtitle">
                    Update user account information.
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>

        </div>

        <form
            action="{{ route('user.update', $user->id) }}"
            method="POST"
            enctype="multipart/form-data"
            class="tn-modal-form">

            @csrf
            @method('PUT')

            <div class="tn-modal-grid">

                <div class="tn-modal-group">
                    <label>Role</label>
                    <select name="role_id" class="tn-modal-control" required>
                        <option value="">Select Role</option>
                        @foreach($roles as $role)
                        <option
                            value="{{ $role->id }}"
                            {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                            {{ $role->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="tn-modal-group">
                    <label>Name</label>
                    <input
                        type="text"
                        name="name"
                        class="tn-modal-control"
                        value="{{ old('name', $user->name) }}"
                        placeholder="Full Name"
                        required>
                </div>

                <div class="tn-modal-group">
                    <label>Username</label>
                    <input
                        type="text"
                        name="username"
                        class="tn-modal-control"
                        value="{{ old('username', $user->username) }}"
                        placeholder="username123"
                        required>
                </div>

                <div class="tn-modal-group">
                    <label>No HP</label>
                    <input
                        type="text"
                        name="no_hp"
                        class="tn-modal-control"
                        value="{{ old('no_hp', $user->no_hp) }}"
                        placeholder="08xxxxxxxxxx"
                        required>
                </div>

                <div class="tn-modal-group">
                    <label>Email</label>
                    <input
                        type="email"
                        name="email"
                        class="tn-modal-control"
                        value="{{ old('email', $user->email) }}"
                        placeholder="name@email.com">
                </div>

                <div class="tn-modal-group">
                    <label>NIM</label>
                    <input
                        type="text"
                        name="nim"
                        class="tn-modal-control"
                        value="{{ old('nim', $user->nim) }}"
                        placeholder="Optional for student">
                </div>

                <div class="tn-modal-group">
                    <label>NIP</label>
                    <input
                        type="text"
                        name="nip"
                        class="tn-modal-control"
                        value="{{ old('nip', $user->nip) }}"
                        placeholder="Optional for lecturer">
                </div>

                <div class="tn-modal-group">
                    <label>Avatar</label>
                    <input
                        type="file"
                        name="avatar"
                        class="tn-modal-control">
                </div>

                <div class="tn-modal-group">
                    <label>Password</label>
                    <input
                        type="password"
                        name="password"
                        class="tn-modal-control"
                        placeholder="Leave blank to keep current password">
                </div>

                <div class="tn-modal-group">
                    <label>Confirm Password</label>
                    <input
                        type="password"
                        name="password_confirmation"
                        class="tn-modal-control"
                        placeholder="Repeat password">
                </div>

                <div class="tn-modal-group tn-modal-full">
                    <label>Security Question</label>
                    <input
                        type="text"
                        name="security_question"
                        class="tn-modal-control"
                        value="{{ old('security_question', $user->security_question) }}"
                        placeholder="Your first school name?">
                </div>

                <div class="tn-modal-group tn-modal-full">
                    <label>Security Answer</label>
                    <input
                        type="text"
                        name="security_answer"
                        class="tn-modal-control"
                        value="{{ old('security_answer', $user->security_answer) }}"
                        placeholder="Answer for password recovery">
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
                    data-tn-title="Update this user?"
                    data-tn-message="Please make sure all changes are correct before updating."
                    data-tn-proceed-text="Update">

                    <i data-lucide="save"></i>
                    Update User
                </button>
            </div>

        </form>

    </div>

</div>