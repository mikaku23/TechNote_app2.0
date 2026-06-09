<div class="tn-modal-overlay">

    <div class="glass card tn-modal-card">

        <div class="tn-modal-header">

            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">
                    {{ $user->name }}
                </h2>

                <p class="tn-modal-subtitle">
                    User Information
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>

        </div>

        <div class="glass tn-modal-info-box">

            <div class="tn-modal-info-row">

                <div class="glass tn-modal-icon-box" style="overflow:hidden;">
                    @if($user->avatar)
                    <img
                        src="{{ asset('storage/'.$user->avatar) }}"
                        alt="{{ $user->name }}"
                        style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">
                    @else
                    <i data-lucide="user" class="tn-modal-package-icon"></i>
                    @endif
                </div>

                <div>
                    <h3>{{ $user->name }}</h3>
                    <p class="tn-modal-subtitle">
                        {{ $user->role->name ?? 'No Role' }}
                    </p>
                </div>

            </div>

        </div>

        <div class="tn-modal-grid">

            <div class="tn-modal-group">
                <label>Name</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $user->name }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Role</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $user->role->name ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Username</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $user->username }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Email</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $user->email ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>No HP</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $user->no_hp }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Identity</label>
                <div class="tn-modal-control tn-modal-readonly">
                    @if($user->nim)
                    NIM: {{ $user->nim }}
                    @elseif($user->nip)
                    NIP: {{ $user->nip }}
                    @else
                    -
                    @endif
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Last Login At</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ optional($user->last_login_at)->format('d M Y H:i') ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Last Password Changed</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ optional($user->last_password_changed_at)->format('d M Y H:i') ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Created At</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ optional($user->created_at)->format('d M Y H:i') ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Updated At</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ optional($user->updated_at)->format('d M Y H:i') ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group tn-modal-full">
                <label>Security Question</label>
                <div class="tn-modal-control tn-modal-readonly tn-modal-description">
                    {{ $user->security_question ?? 'No security question available.' }}
                </div>
            </div>

            <div class="tn-modal-group tn-modal-full">
                <label>Security Answer</label>
                <div class="tn-modal-control tn-modal-readonly tn-modal-description">
                    Protected
                </div>
            </div>

        </div>

        <div class="tn-modal-actions">
            <button type="button" class="btn-secondary close-modal">
                <i data-lucide="x"></i>
                Close
            </button>

            <button
                type="button"
                class="btn-primary open-modal"
                data-url="{{ route('user.edit', $user->id) }}">
                <i data-lucide="pencil"></i>
                Edit User
            </button>
        </div>

    </div>

</div>