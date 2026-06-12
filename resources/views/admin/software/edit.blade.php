<div class="tn-modal-overlay">

    <div class="glass card tn-modal-card">

        <div class="tn-modal-header">

            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">Edit Software</h2>
                <p class="tn-modal-subtitle">
                    Update software information for technician installation services.
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>

        </div>

        <form
            action="{{ route('software.update', $software->id) }}"
            method="POST"
            class="tn-modal-form">

            @csrf
            @method('PUT')

            <div class="tn-modal-grid">

                <div class="tn-modal-group">
                    <label>Software Name</label>
                    <input
                        type="text"
                        name="name"
                        class="tn-modal-control"
                        value="{{ old('name', $software->name) }}"
                        placeholder="Visual Studio Code"
                        required>
                </div>

                <div class="tn-modal-group">
                    <label>Developer</label>
                    <input
                        type="text"
                        name="developer"
                        class="tn-modal-control"
                        value="{{ old('developer', $software->developer) }}"
                        placeholder="Microsoft">
                </div>

                <div class="tn-modal-group">
                    <label>Version</label>
                    <input
                        type="text"
                        name="version"
                        class="tn-modal-control"
                        value="{{ old('version', $software->version) }}"
                        placeholder="1.102">
                </div>

                <div class="tn-modal-group">
                    <label>Estimated Minutes</label>
                    <input
                        type="number"
                        name="estimated_minutes"
                        class="tn-modal-control"
                        value="{{ old('estimated_minutes', $software->estimated_minutes) }}"
                        placeholder="30"
                        min="1">
                </div>

                <div class="tn-modal-group tn-modal-full">
                    <label>Description</label>
                    <textarea
                        name="description"
                        class="tn-modal-control"
                        placeholder="Software description...">{{ old('description', $software->description) }}</textarea>
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
                    data-tn-title="Update this software?"
                    data-tn-message="Please make sure all changes are correct before updating."
                    data-tn-proceed-text="Update">

                    <i data-lucide="save"></i>
                    Update Software
                </button>
            </div>

        </form>

    </div>

</div>