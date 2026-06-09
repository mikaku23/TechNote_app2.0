<div class="tn-modal-overlay">

    <div class="glass card tn-modal-card">

        <div class="tn-modal-header">

            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">Edit Trusted Website</h2>
                <p class="tn-modal-subtitle">
                    Update trusted website information.
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>

        </div>

        <form
            action="{{ route('trusted.update', $trusted->id) }}"
            method="POST"
            class="tn-modal-form">

            @csrf
            @method('PUT')

            <div class="tn-modal-grid">

                <div class="tn-modal-group">
                    <label>Name</label>
                    <input
                        type="text"
                        name="name"
                        class="tn-modal-control"
                        value="{{ old('name', $trusted->name) }}"
                        placeholder="OpenAI"
                        required>
                </div>

                <div class="tn-modal-group">
                    <label>URL</label>
                    <input
                        type="url"
                        name="url"
                        class="tn-modal-control"
                        value="{{ old('url', $trusted->url) }}"
                        placeholder="https://example.com"
                        required>
                </div>

                <div class="tn-modal-group">
                    <label>Status</label>
                    <select name="is_active" class="tn-modal-control" required>
                        <option value="1" {{ old('is_active', $trusted->is_active ? 1 : 0) == 1 ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ old('is_active', $trusted->is_active ? 1 : 0) == 0 ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>

                <div class="tn-modal-group tn-modal-full">
                    <label>Description</label>
                    <textarea
                        name="description"
                        class="tn-modal-control"
                        placeholder="Trusted website description...">{{ old('description', $trusted->description) }}</textarea>
                </div>

            </div>

            <div class="tn-modal-actions">
                <button type="button" class="btn-secondary close-modal">
                    <i data-lucide="x"></i>
                    Cancel
                </button>

                <button type="submit" class="btn-primary">
                    <i data-lucide="save"></i>
                    Update Trusted Website
                </button>
            </div>

        </form>

    </div>

</div>