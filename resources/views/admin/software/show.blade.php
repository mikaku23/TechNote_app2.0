<div class="tn-modal-overlay">

    <div class="glass card tn-modal-card">

        <div class="tn-modal-header">

            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">
                    {{ $software->name }}
                </h2>

                <p class="tn-modal-subtitle">
                    Software Information
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>

        </div>

        <div class="glass tn-modal-info-box">

            <div class="tn-modal-info-row">

                <div class="glass tn-modal-icon-box">
                    <i data-lucide="package" class="tn-modal-package-icon"></i>
                </div>

                <div>
                    <h3>{{ $software->name }}</h3>
                    <p class="tn-modal-subtitle">
                        {{ $software->developer ?? 'Unknown Developer' }}
                    </p>
                </div>

            </div>

        </div>

        <div class="tn-modal-grid">

            <div class="tn-modal-group">
                <label>Software Name</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $software->name }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Developer</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $software->developer ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Version</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $software->version ?? '-' }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Estimated Minutes</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $software->estimated_minutes }} minutes
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Created At</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $software->created_at->format('d M Y H:i') }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Updated At</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $software->updated_at->format('d M Y H:i') }}
                </div>
            </div>

            <div class="tn-modal-group tn-modal-full">
                <label>Description</label>
                <div class="tn-modal-control tn-modal-readonly tn-modal-description">
                    {{ $software->description ?? 'No description available.' }}
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
                data-url="{{ route('software.edit', $software->id) }}">
                <i data-lucide="pencil"></i>
                Edit Software
            </button>
        </div>

    </div>

</div>