<div class="tn-modal-overlay">

    <div class="glass card tn-modal-card">

        <div class="tn-modal-header">

            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">Security Question</h2>
                <p class="tn-modal-subtitle">
                    Configure account recovery verification.
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>

        </div>

        <form
            action="{{ route('settings.security.update') }}"
            method="POST"
            class="tn-modal-form">

            @csrf
            @method('PUT')

            <div class="tn-modal-grid">

                <div class="tn-modal-group tn-modal-full">
                    <label>Security Question</label>

                    <input
                        type="text"
                        name="security_question"
                        class="tn-modal-control"
                        value="{{ auth()->user()->security_question }}"
                        placeholder="What is your favorite teacher's name?"
                        required>
                </div>

                <div class="tn-modal-group tn-modal-full">
                    <label>Security Answer</label>

                    <input
                        type="text"
                        name="security_answer"
                        class="tn-modal-control"
                        placeholder="Enter your answer"
                        required>
                </div>

            </div>

            <div class="tn-modal-actions">

                <button type="button" class="btn-secondary close-modal">
                    <i data-lucide="x"></i>
                    Cancel
                </button>

                <button
                    type="submit"
                    class="btn-primary">

                    <i data-lucide="shield-check"></i>
                    Save Security Question

                </button>

            </div>

        </form>

    </div>

</div>