<div class="tn-modal-overlay">
    <div class="glass card tn-modal-card">
        <div class="tn-modal-header">
            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">Add Repair</h2>
                <p class="tn-modal-subtitle">
                    Create repair ticket manually. No booking is used for repair flow.
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>
        </div>

        <form action="{{ route('perbaikan.store') }}" method="POST" class="tn-modal-form">
            @csrf

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
                        <option value="" disabled {{ old('user_id') ? '' : 'selected' }}>
                            Select Lecturer
                        </option>

                        @foreach($users as $user)
                        @if($user->role && $user->role->name === 'Dosen')
                        <option value="{{ $user->id }}"
                            {{ old('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                        @endif
                        @endforeach
                    </select>

                    @error('user_id')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group">
                    <label>Priority</label>
                    <select name="priority" class="tn-modal-control">
                        <option value="normal" {{ old('priority', 'normal') === 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                    </select>
                    @error('priority')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group">
                    <label>Item Name</label>
                    <input
                        type="text"
                        name="item_name"
                        class="tn-modal-control"
                        value="{{ old('item_name') }}"
                        placeholder="Laptop, printer, PC, monitor">
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
                        value="{{ old('item_location') }}"
                        placeholder="Lab, office, room, desk">
                    @error('item_location')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group tn-modal-full">
                    <label>Damage Description</label>
                    <textarea
                        name="damage_description"
                        class="tn-modal-control"
                        placeholder="Describe the damage...">{{ old('damage_description') }}</textarea>
                    @error('damage_description')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group">
                    <label>Estimated Finish</label>
                    <input
                        type="datetime-local"
                        name="estimated_finish"
                        class="tn-modal-control"
                        value="{{ old('estimated_finish') }}">
                    @error('estimated_finish')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group">
                    <label>Public Ticket</label>
                    <select name="is_public" class="tn-modal-control">
                        <option value="1" {{ old('is_public', 1) == 1 ? 'selected' : '' }}>Public</option>
                        <option value="0" {{ old('is_public') == 0 ? 'selected' : '' }}>Private</option>
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
                        placeholder="Additional note...">{{ old('note') }}</textarea>
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
                    class="btn-primary">
                    <i data-lucide="save"></i>
                    Save Repair
                </button>
            </div>
        </form>
    </div>
</div>