<div class="tn-modal-overlay">

    <div class="glass card tn-modal-card">

        <div class="tn-modal-header">

            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">Edit Installation</h2>
                <p class="tn-modal-subtitle">
                    Update installation record and its ticket properties.
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>

        </div>

        <form
            action="{{ route('penginstalan.update', $penginstalan->id) }}"
            method="POST"
            class="tn-modal-form">

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
                        <option value="{{ $user->id }}" {{ old('user_id', $penginstalan->user_id) == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('user_id')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group">
                    <label>Software</label>
                    <select name="software_id" class="tn-modal-control" required>
                        @foreach($softwares as $software)
                        <option value="{{ $software->id }}" {{ old('software_id', $penginstalan->software_id) == $software->id ? 'selected' : '' }}>
                            {{ $software->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('software_id')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group">
                    <label>Priority</label>
                    <select name="priority" class="tn-modal-control">
                        <option value="normal" {{ old('priority', $penginstalan->ticket->priority ?? 'normal') == 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="high" {{ old('priority', $penginstalan->ticket->priority ?? '') == 'high' ? 'selected' : '' }}>High</option>
                        <option value="urgent" {{ old('priority', $penginstalan->ticket->priority ?? '') == 'urgent' ? 'selected' : '' }}>Urgent</option>
                    </select>
                    @error('priority')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group">
                    <label>Estimated Finish</label>
                    <input
                        type="datetime-local"
                        name="estimated_finish"
                        class="tn-modal-control"
                        value="{{ old('estimated_finish', optional($penginstalan->ticket?->estimated_finish)->format('Y-m-d\TH:i')) }}">
                    @error('estimated_finish')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group">
                    <label>Installation Result</label>
                    <select name="installation_result" class="tn-modal-control">
                        <option value="" {{ old('installation_result', $penginstalan->installation_result) === null ? 'selected' : '' }}>
                            Pending
                        </option>
                        <option value="success" {{ old('installation_result', $penginstalan->installation_result) === 'success' ? 'selected' : '' }}>
                            Success
                        </option>
                        <option value="failed" {{ old('installation_result', $penginstalan->installation_result) === 'failed' ? 'selected' : '' }}>
                            Failed
                        </option>
                    </select>
                    @error('installation_result')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group">
                    <label>Public Ticket</label>
                    <select name="is_public" class="tn-modal-control">
                        <option value="1" {{ old('is_public', $penginstalan->ticket->is_public ?? 1) == 1 ? 'selected' : '' }}>Public</option>
                        <option value="0" {{ old('is_public', $penginstalan->ticket->is_public ?? 1) == 0 ? 'selected' : '' }}>Private</option>
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
                        placeholder="Installation note...">{{ old('note', $penginstalan->note) }}</textarea>
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
                    data-tn-title="Update this installation?"
                    data-tn-message="The installation and ticket properties will be updated."
                    data-tn-proceed-text="Update">

                    <i data-lucide="save"></i>
                    Update Installation
                </button>
            </div>

        </form>

    </div>

</div>