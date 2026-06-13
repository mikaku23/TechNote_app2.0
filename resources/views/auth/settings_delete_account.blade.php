<div class="tn-modal-overlay">

    <div class="glass card tn-modal-card">

        <div class="tn-modal-header">

            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">Delete Account</h2>

                <p class="tn-modal-subtitle">
                    This action is permanent and cannot be undone.
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>

        </div>

        <div class="tn-alert tn-alert-error">
            <strong>Warning!</strong>
            <p>
                Deleting your account will permanently remove your access to the system.
            </p>
        </div>

        <form
            action="{{ route('settings.destroy') }}"
            method="POST"
            class="tn-modal-form">

            @csrf
            @method('DELETE')

            <div class="tn-modal-grid">

                <div class="tn-modal-group tn-modal-full">
                    <label>Security Question</label>

                    <input
                        type="text"
                        class="tn-modal-control"
                        value="{{ auth()->user()->security_question ?? 'No security question configured.' }}"
                        readonly>
                </div>

                <div class="tn-modal-group tn-modal-full">
                    <label>Security Answer</label>

                    <input
                        type="text"
                        name="security_answer"
                        class="tn-modal-control"
                        placeholder="Enter your security answer"
                        required>
                </div>

                <div class="tn-modal-group tn-modal-full">
                    <label>Password</label>

                    <input
                        type="password"
                        name="password"
                        class="tn-modal-control"
                        placeholder="Enter your password"
                        required>
                </div>

                <div class="tn-modal-group tn-modal-full">
                    <label>Confirmation</label>

                    <input
                        type="text"
                        name="confirmation_text"
                        class="tn-modal-control"
                        placeholder="Type DELETE MY ACCOUNT"
                        required>
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
                    class="btn-secondary"
                    data-tn-confirm
                    data-tn-type="danger"
                    data-tn-title="Delete Account?"
                    data-tn-message="This action is permanent and cannot be undone."
                    data-tn-proceed-text="Delete Account">

                    <i data-lucide="trash-2"></i>
                    Delete Account

                </button>

            </div>

        </form>

    </div>

</div>