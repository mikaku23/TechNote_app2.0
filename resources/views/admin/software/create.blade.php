<div class="tn-modal-overlay">

    <div class="glass card tn-modal-card">

        <div class="tn-modal-header">

            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">Add Software</h2>
                <p class="tn-modal-subtitle">
                    Add a software that can be installed by technicians.
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>

        </div>

        <form
            action="{{ route('software.store') }}"
            method="POST"
            class="tn-modal-form">

            @csrf

            <div class="tn-modal-grid">

                <div class="tn-modal-group">
                    <label>Software Name</label>
                    <input
                        type="text"
                        name="name"
                        class="tn-modal-control"
                        placeholder="Visual Studio Code"
                        required>
                </div>

                <div class="tn-modal-group">
                    <label>Developer</label>
                    <input
                        type="text"
                        name="developer"
                        class="tn-modal-control"
                        placeholder="Microsoft">
                </div>

                <div class="tn-modal-group">
                    <label>Version</label>
                    <input
                        type="text"
                        name="version"
                        class="tn-modal-control"
                        placeholder="1.102">
                </div>

                <div class="tn-modal-group">
                    <label>Status</label>
                    <input
                        type="text"
                        class="tn-modal-control"
                        value="Ready For Installation"
                        readonly>
                </div>

                <div class="tn-modal-group tn-modal-full">
                    <label>Description</label>
                    <textarea
                        name="description"
                        class="tn-modal-control"
                        placeholder="Software description..."></textarea>
                </div>

            </div>

            <div class="tn-modal-actions">
                <button type="button" class="btn-secondary close-modal">
                    <i data-lucide="x"></i>
                    Cancel
                </button>

                <button type="submit" class="btn-primary">
                    <i data-lucide="save"></i>
                    Save Software
                </button>
            </div>

        </form>

    </div>

</div>