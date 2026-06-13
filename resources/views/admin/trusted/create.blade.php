<div class="tn-modal-overlay">

    <div class="glass card tn-modal-card">

        <div class="tn-modal-header">

            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">Add Trusted Website</h2>
                <p class="tn-modal-subtitle">
                    Add an official trusted website for internal system use.
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>

        </div>

        <form
            action="{{ route('trusted.store') }}"
            method="POST"
            class="tn-modal-form">

            @csrf

            <div class="tn-modal-grid">

                <div class="tn-modal-group">
                    <label>Name</label>
                    <input
                        type="text"
                        name="name"
                        class="tn-modal-control"
                        placeholder="OpenAI"
                        required>
                </div>

                <div class="tn-modal-group">
                    <label>URL</label>
                    <input
                        type="url"
                        name="url"
                        class="tn-modal-control"
                        placeholder="https://example.com"
                        required>
                </div>

                <div class="tn-modal-group">
                    <label>Status</label>
                    <select name="is_active" class="tn-modal-control" required>
                        <option value="1" selected>Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>

                <div class="tn-modal-group tn-modal-full">
                    <label>Description</label>
                    <textarea
                        name="description"
                        class="tn-modal-control"
                        placeholder="Trusted website description..."></textarea>
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
                   >

                    <i data-lucide="save"></i>
                    Save Trusted Website
                </button>
            </div>

        </form>

    </div>

</div>