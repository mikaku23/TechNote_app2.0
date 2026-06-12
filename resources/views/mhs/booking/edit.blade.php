<div class="tn-modal-overlay">
    <div class="glass card tn-modal-card">
        <div class="tn-modal-header">
            <div>
                <h2 class="tn-modal-title">Edit Booking</h2>
                <p class="tn-modal-subtitle">
                    Update jadwal booking sebelum waktu pengerjaan dimulai.
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>
        </div>

        <form action="{{ route('mahasiswa.booking.update', $ticket->id) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="tn-modal-grid">
                <div class="tn-modal-group tn-modal-full">
                    <label>Tanggal</label>
                    <div class="tn-modal-control tn-modal-readonly">
                        {{ \Carbon\Carbon::parse($ticket->booking_date)->format('d M Y') }}
                    </div>
                </div>

                <div class="tn-modal-group">
                    <label>Sesi</label>
                    <select name="session" class="tn-modal-control" required>
                        <option value="morning" @selected(old('session', $ticket->session) === 'morning')>
                            Pagi
                        </option>
                        <option value="afternoon" @selected(old('session', $ticket->session) === 'afternoon')>
                            Siang
                        </option>
                    </select>
                </div>

                <div class="tn-modal-group tn-modal-full">
                    <label>Software</label>
                    <select name="software_id" class="tn-modal-control" required>
                        @foreach($softwares as $software)
                        <option
                            value="{{ $software->id }}"
                            @selected((int) old('software_id', $ticket->penginstalan?->software_id) === (int) $software->id)>
                            {{ $software->name }} ({{ $software->estimated_minutes }} menit)
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="tn-modal-group tn-modal-full">
                    <label>Catatan Tambahan</label>
                    <textarea name="note" class="tn-modal-control" placeholder="Catatan tambahan...">{{ old('note', $ticket->comments->first()?->comment) }}</textarea>
                </div>
            </div>

            <div class="tn-modal-actions">
                <button type="button" class="btn-secondary close-modal">
                    Tutup
                </button>

                <button
                    type="submit"
                    class="btn-primary"
                    data-tn-confirm
                    data-tn-type="warning"
                    data-tn-title="Update booking?"
                    data-tn-message="Pastikan perubahan sudah benar sebelum disimpan."
                    data-tn-proceed-text="Update">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>