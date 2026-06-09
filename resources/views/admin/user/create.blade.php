<div class="tn-modal-overlay">

    <div class="glass card tn-modal-card">

        <div class="tn-modal-header">

            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">Add User</h2>
                <p class="tn-modal-subtitle">
                    Create a new user account for the system.
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>

        </div>

        <form
            action="{{ route('user.store') }}"
            method="POST"
            enctype="multipart/form-data"
            class="tn-modal-form">

            @csrf

            @if ($errors->any())
            <div class="tn-alert tn-alert-error">
                <strong>There are errors in your submission.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="tn-modal-grid">

                <div class="tn-modal-group">
                    <label>Role</label>
                    <select name="role_id" class="tn-modal-control" required>
                        <option value="" disabled {{ old('role_id') ? '' : 'selected' }}>
                            Select Role
                        </option>
                        @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                            {{ $role->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('role_id')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group">
                    <label>Name</label>
                    <input
                        type="text"
                        name="name"
                        class="tn-modal-control"
                        value="{{ old('name') }}"
                        placeholder="Full Name"
                        required>
                    @error('name')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group">
                    <label>Username</label>
                    <input
                        type="text"
                        name="username"
                        class="tn-modal-control"
                        value="{{ old('username') }}"
                        placeholder="username123"
                        required>
                    @error('username')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group">
                    <label>No HP</label>
                    <input
                        type="text"
                        name="no_hp"
                        class="tn-modal-control"
                        value="{{ old('no_hp') }}"
                        placeholder="08xxxxxxxxxx"
                        required>
                    @error('no_hp')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group">
                    <label>Email</label>
                    <input
                        type="email"
                        name="email"
                        class="tn-modal-control"
                        value="{{ old('email') }}"
                        placeholder="name@email.com">
                    @error('email')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group">
                    <label>NIM</label>
                    <input
                        type="text"
                        name="nim"
                        class="tn-modal-control"
                        value="{{ old('nim') }}"
                        placeholder="Optional for student">
                    @error('nim')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group">
                    <label>NIP</label>
                    <input
                        type="text"
                        name="nip"
                        class="tn-modal-control"
                        value="{{ old('nip') }}"
                        placeholder="Optional for lecturer">
                    @error('nip')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group">
                    <label>Avatar</label>
                    <input
                        type="file"
                        name="avatar"
                        class="tn-modal-control">
                    @error('avatar')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group">
                    <label>Password</label>
                    <input
                        type="password"
                        name="password"
                        class="tn-modal-control"
                        placeholder="Minimum 8 characters"
                        required>
                    @error('password')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group">
                    <label>Confirm Password</label>
                    <input
                        type="password"
                        name="password_confirmation"
                        class="tn-modal-control"
                        placeholder="Repeat password"
                        required>
                    @error('password_confirmation')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group tn-modal-full">
                    <label>Security Question</label>
                    <input
                        type="text"
                        name="security_question"
                        class="tn-modal-control"
                        value="{{ old('security_question') }}"
                        placeholder="Your first school name?">
                    @error('security_question')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group tn-modal-full">
                    <label>Security Answer</label>
                    <input
                        type="text"
                        name="security_answer"
                        class="tn-modal-control"
                        value="{{ old('security_answer') }}"
                        placeholder="Answer for password recovery">
                    @error('security_answer')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

            </div>

            <div class="tn-modal-actions">
                <button type="button" class="btn-secondary close-modal">
                    <i data-lucide="x"></i>
                    Cancel
                </button>

                <button type="submit" class="btn-primary">
                    <i data-lucide="save"></i>
                    Save User
                </button>
            </div>

        </form>

    </div>

</div>