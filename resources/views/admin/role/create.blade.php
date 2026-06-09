<div class="tn-modal-overlay">

    <div class="glass card tn-modal-card">

        <div class="tn-modal-header">

            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">Add Role</h2>
                <p class="tn-modal-subtitle">
                    Create a new system role.
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>

        </div>

        <form
            action="{{ route('role.store') }}"
            method="POST"
            class="tn-modal-form">

            @csrf

            <div class="tn-modal-grid">

                <div class="tn-modal-group">
                    <label>Role Name</label>
                    <input
                        type="text"
                        name="name"
                        class="tn-modal-control"
                        placeholder="Admin"
                        required>
                </div>

                <div class="tn-modal-group">
                    <label>Status</label>
                    <input
                        type="text"
                        class="tn-modal-control"
                        value="Ready"
                        readonly>
                </div>

                <div class="tn-modal-group tn-modal-full">
                    <label>Description</label>

                    <textarea
                        name="description"
                        class="tn-modal-control"
                        placeholder="Role description..."></textarea>

                </div>

            </div>

            <div class="tn-modal-actions">

                <button
                    type="button"
                    class="btn-secondary close-modal">

                    <i data-lucide="x"></i>
                    Cancel

                </button>

                <button
                    type="submit"
                    class="btn-primary">

                    <i data-lucide="save"></i>
                    Save Role

                </button>

            </div>

        </form>

    </div>

</div>