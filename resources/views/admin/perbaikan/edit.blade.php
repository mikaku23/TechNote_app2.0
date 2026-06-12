<div class="tn-modal-overlay">
    <div class="glass card tn-modal-card">
        <div class="tn-modal-header">
            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">Edit Repair</h2>
                <p class="tn-modal-subtitle">
                    Update repair data, ticket status, and repair outcome.
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>
        </div>

        <form action="{{ route('perbaikan.update', $perbaikan->id) }}" method="POST" class="tn-modal-form">
            @csrf
            @method('PUT')

            @if ($errors->any())
            <div class="tn-alert tn-alert-error">
                <strong>There are errors in your submission.</strong>
                <ul>
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="tn-modal-grid">
                <div class="tn-modal-group">
                    <label>User</label>
                    <select name="user_id" class="tn-modal-control" required>
                        @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('user_id', $perbaikan->user_id) == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('user_id')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group">
                    <label>Priority</label>
                    <select name="priority" class="tn-modal-control">
                        <option value="normal" {{ old('priority', $perbaikan->ticket->priority ?? 'normal') === 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="high" {{ old('priority', $perbaikan->ticket->priority ?? '') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="urgent" {{ old('priority', $perbaikan->ticket->priority ?? '') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                    </select>
                    @error('priority')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group">
                    <label>Ticket Status</label>
                    <select name="status" class="tn-modal-control">
                        <option value="waiting" {{ old('status', $perbaikan->ticket->status ?? 'waiting') === 'waiting' ? 'selected' : '' }}>Waiting</option>
                        <option value="diagnosis" {{ old('status', $perbaikan->ticket->status ?? '') === 'diagnosis' ? 'selected' : '' }}>Diagnosis</option>
                        <option value="processing" {{ old('status', $perbaikan->ticket->status ?? '') === 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="testing" {{ old('status', $perbaikan->ticket->status ?? '') === 'testing' ? 'selected' : '' }}>Testing</option>
                        <option value="completed" {{ old('status', $perbaikan->ticket->status ?? '') === 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="failed" {{ old('status', $perbaikan->ticket->status ?? '') === 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="cancelled" {{ old('status', $perbaikan->ticket->status ?? '') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    @error('status')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group">
                    <label>Repair Result</label>
                    <select name="repair_result" class="tn-modal-control">
                        <option value="" {{ old('repair_result', $perbaikan->repair_result) === null ? 'selected' : '' }}>Pending</option>
                        <option value="success" {{ old('repair_result', $perbaikan->repair_result) === 'success' ? 'selected' : '' }}>Success</option>
                        <option value="failed" {{ old('repair_result', $perbaikan->repair_result) === 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="unrepairable" {{ old('repair_result', $perbaikan->repair_result) === 'unrepairable' ? 'selected' : '' }}>Unrepairable</option>
                    </select>
                    @error('repair_result')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group">
                    <label>Item Name</label>
                    <input
                        type="text"
                        name="item_name"
                        class="tn-modal-control"
                        value="{{ old('item_name', $perbaikan->item_name) }}">
                    @error('item_name')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group">
                    <label>Location</label>
                    <input
                        type="text"
                        name="item_location"
                        class="tn-modal-control"
                        value="{{ old('item_location', $perbaikan->item_location) }}">
                    @error('item_location')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group tn-modal-full">
                    <label>Damage Description</label>
                    <textarea name="damage_description" class="tn-modal-control">{{ old('damage_description', $perbaikan->damage_description) }}</textarea>
                    @error('damage_description')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group tn-modal-full">
                    <label>Repair Action</label>
                    <textarea name="repair_action" class="tn-modal-control">{{ old('repair_action', $perbaikan->repair_action) }}</textarea>
                    @error('repair_action')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group">
                    <label>Estimated Finish</label>
                    <input
                        type="datetime-local"
                        name="estimated_finish"
                        class="tn-modal-control"
                        value="{{ old('estimated_finish', optional($perbaikan->ticket?->estimated_finish)->format('Y-m-d\TH:i')) }}">
                    @error('estimated_finish')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group">
                    <label>Public Ticket</label>
                    <select name="is_public" class="tn-modal-control">
                        <option value="1" {{ old('is_public', $perbaikan->ticket->is_public ?? 1) == 1 ? 'selected' : '' }}>Public</option>
                        <option value="0" {{ old('is_public', $perbaikan->ticket->is_public ?? 1) == 0 ? 'selected' : '' }}>Private</option>
                    </select>
                    @error('is_public')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group tn-modal-full">
                    <label>Note</label>
                    <textarea
                        name="note"
                        class="tn-modal-control"
                        placeholder="Additional note...">{{ old('note', $perbaikan->note) }}</textarea>
                    @error('note')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
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
                    data-tn-title="Update repair ticket?"
                    data-tn-message="Repair data and ticket fields will be updated."
                    data-tn-proceed-text="Update">
                    <i data-lucide="save"></i>
                    Update Repair
                </button>
            </div>
        </form>
    </div>
</div>