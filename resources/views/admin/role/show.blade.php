<div class="tn-modal-overlay">

    <div class="glass card tn-modal-card">

        <div class="tn-modal-header">

            <div class="tn-modal-header-left">

                <h2 class="tn-modal-title">
                    {{ $role->name }}
                </h2>

                <p class="tn-modal-subtitle">
                    Role Information
                </p>

            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>

        </div>

        <div class="glass tn-modal-info-box">

            <div class="tn-modal-info-row">

                <div class="glass tn-modal-icon-box">
                    <i data-lucide="shield"></i>
                </div>

                <div>

                    <h3>{{ $role->name }}</h3>

                    <p class="tn-modal-subtitle">
                        System Role
                    </p>

                </div>

            </div>

        </div>

        <div class="tn-modal-grid">

            <div class="tn-modal-group">
                <label>Role Name</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $role->name }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Status</label>
                <div class="tn-modal-control tn-modal-readonly">
                    @if($role->is_active)
                    <span class="badge success">Active</span>
                    @else
                    <span class="badge danger">Inactive</span>
                    @endif
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Created At</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $role->created_at->format('d M Y H:i') }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Updated At</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $role->updated_at->format('d M Y H:i') }}
                </div>
            </div>

            <div class="tn-modal-group tn-modal-full">
                <label>Description</label>
                <div class="tn-modal-control tn-modal-readonly tn-modal-description">
                    {{ $role->description ?? 'No description available.' }}
                </div>
            </div>

        </div>

        <div class="tn-modal-actions">
            <button
                type="button"
                class="btn-secondary close-modal">

                <i data-lucide="x"></i>
                Close

            </button>
        </div>

    </div>

</div>