<div class="tn-modal-overlay">

    <div class="glass card tn-modal-card">

        <div class="tn-modal-header">

            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">
                    {{ $trusted->name }}
                </h2>

                <p class="tn-modal-subtitle">
                    Trusted Website Information
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>

        </div>

        <div class="glass tn-modal-info-box">

            <div class="tn-modal-info-row">

                <div class="glass tn-modal-icon-box">
                    <i data-lucide="globe" class="tn-modal-package-icon"></i>
                </div>

                <div>
                    <h3>{{ $trusted->name }}</h3>
                    <p class="tn-modal-subtitle">
                        {{ $trusted->is_active ? 'Active Trusted Website' : 'Inactive Trusted Website' }}
                    </p>
                </div>

            </div>

        </div>

        <div class="tn-modal-grid">

            <div class="tn-modal-group">
                <label>Name</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $trusted->name }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>URL</label>
                <div class="tn-modal-control tn-modal-readonly">
                    <a
                        href="{{ $trusted->url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        style="color:var(--primary);text-decoration:none;">
                        {{ $trusted->url }}
                    </a>
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Status</label>
                <div class="tn-modal-control tn-modal-readonly">
                    @if($trusted->is_active)
                    <span class="badge success">Active</span>
                    @else
                    <span class="badge danger">Inactive</span>
                    @endif
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Created At</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $trusted->created_at->format('d M Y H:i') }}
                </div>
            </div>

            <div class="tn-modal-group">
                <label>Updated At</label>
                <div class="tn-modal-control tn-modal-readonly">
                    {{ $trusted->updated_at->format('d M Y H:i') }}
                </div>
            </div>

            <div class="tn-modal-group tn-modal-full">
                <label>Description</label>
                <div class="tn-modal-control tn-modal-readonly tn-modal-description">
                    {{ $trusted->description ?? 'No description available.' }}
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
                data-url="{{ route('trusted.edit', $trusted->id) }}">
                <i data-lucide="pencil"></i>
                Edit Trusted Website
            </button>
        </div>

    </div>

</div>