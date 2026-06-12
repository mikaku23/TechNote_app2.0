<div class="tn-modal-overlay">

    <div class="glass card tn-modal-card">

        <div class="tn-modal-header">

            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">Update Ticket Status</h2>
                <p class="tn-modal-subtitle">
                    Change ticket data and write a log note.
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>

        </div>

        <form
            action="{{ route('ticket.update', $ticket->id) }}"
            method="POST"
            class="tn-modal-form">

            @csrf
            @method('PUT')

            <div class="tn-modal-grid">

                <div class="tn-modal-group">
                    <label>Type</label>
                    <select name="type" class="tn-modal-control" required>
                        <option value="installation" {{ old('type', $ticket->type) === 'installation' ? 'selected' : '' }}>Installation</option>
                        <option value="repair" {{ old('type', $ticket->type) === 'repair' ? 'selected' : '' }}>Repair</option>
                    </select>
                </div>

                <div class="tn-modal-group">
                    <label>User</label>
                    <select name="user_id" class="tn-modal-control" required>
                        @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('user_id', $ticket->user_id) == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="tn-modal-group">
                    <label>Status</label>
                    <select name="status" class="tn-modal-control" required>
                        <option value="waiting" {{ old('status', $ticket->status) === 'waiting' ? 'selected' : '' }}>Waiting</option>
                        <option value="diagnosis" {{ old('status', $ticket->status) === 'diagnosis' ? 'selected' : '' }}>Diagnosis</option>
                        <option value="processing" {{ old('status', $ticket->status) === 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="testing" {{ old('status', $ticket->status) === 'testing' ? 'selected' : '' }}>Testing</option>
                        <option value="completed" {{ old('status', $ticket->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="failed" {{ old('status', $ticket->status) === 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>

                <div class="tn-modal-group">
                    <label>Priority</label>
                    <select name="priority" class="tn-modal-control" required>
                        <option value="normal" {{ old('priority', $ticket->priority) === 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="high" {{ old('priority', $ticket->priority) === 'high' ? 'selected' : '' }}>High</option>
                        <option value="urgent" {{ old('priority', $ticket->priority) === 'urgent' ? 'selected' : '' }}>Urgent</option>
                    </select>
                </div>

                <div class="tn-modal-group">
                    <label>Estimated Finish</label>
                    <input
                        type="datetime-local"
                        name="estimated_finish"
                        class="tn-modal-control"
                        value="{{ old('estimated_finish', optional($ticket->estimated_finish)->format('Y-m-d\TH:i')) }}">
                </div>

                <div class="tn-modal-group">
                    <label>Public Ticket</label>
                    <select name="is_public" class="tn-modal-control" required>
                        <option value="1" {{ old('is_public', $ticket->is_public ? 1 : 0) == 1 ? 'selected' : '' }}>Public</option>
                        <option value="0" {{ old('is_public', $ticket->is_public ? 1 : 0) == 0 ? 'selected' : '' }}>Private</option>
                    </select>
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
                    data-tn-title="Update ticket status?"
                    data-tn-message="This will update the ticket and create a status log."
                    data-tn-proceed-text="Update">

                    <i data-lucide="save"></i>
                    Update Ticket
                </button>
            </div>

        </form>

    </div>

</div>