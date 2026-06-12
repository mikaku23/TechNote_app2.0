<div class="tn-modal-overlay">

    <div class="glass card tn-modal-card">

        <div class="tn-modal-header">

            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">Add Installation</h2>
                <p class="tn-modal-subtitle">
                    Create a new software installation record. Ticket will be generated automatically.
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>

        </div>

        <form
            action="{{ route('penginstalan.store') }}"
            method="POST"
            class="tn-modal-form">

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
                            Select User
                        </option>
                        @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
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
                        <option value="" disabled {{ old('software_id') ? '' : 'selected' }}>
                            Select Software
                        </option>
                        @foreach($softwares as $software)
                        <option value="{{ $software->id }}" {{ old('software_id') == $software->id ? 'selected' : '' }}>
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
                        <option value="normal" {{ old('priority', 'normal') == 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="high" {{ old('priority') == 'high' ? 'selected' : '' }}>High</option>
                        <option value="urgent" {{ old('priority') == 'urgent' ? 'selected' : '' }}>Urgent</option>
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
                        value="{{ old('estimated_finish') }}">
                    @error('estimated_finish')
                    <span class="tn-field-error">{{ $message }}</span>
                    @enderror
                </div>

                <div class="tn-modal-group">
                    <label>Installation Result</label>
                    <select name="installation_result" class="tn-modal-control">
                        <option value="" {{ old('installation_result') ? '' : 'selected' }}>Pending</option>
                        <option value="success" {{ old('installation_result') === 'success' ? 'selected' : '' }}>
                            Success
                        </option>
                        <option value="failed" {{ old('installation_result') === 'failed' ? 'selected' : '' }}>
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
                        placeholder="Installation note...">{{ old('note') }}</textarea>
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
                    data-tn-type="success"
                    data-tn-title="Save installation?"
                    data-tn-message="The installation record and ticket will be created automatically."
                    data-tn-proceed-text="Save">

                    <i data-lucide="save"></i>
                    Save Installation
                </button>
            </div>

        </form>

    </div>

</div>