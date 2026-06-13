<div class="tn-modal-overlay">
    <div class="glass card tn-modal-card">
        <div class="tn-modal-header">
            <div>
                <h2 class="tn-modal-title">Booking Penginstalan</h2>
                <p class="tn-modal-subtitle">
                    Buat jadwal instalasi software untuk hari ini.
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>
        </div>

        @php
        $defaultSession = $morningAvailable ? 'morning' : ($afternoonAvailable ? 'afternoon' : null);
        @endphp

        <form action="{{ route('mahasiswa.booking.store') }}" method="POST">
            @csrf

            <input type="hidden" name="booking_date" value="{{ now()->toDateString() }}">

            <div class="tn-modal-grid">
                <div class="tn-modal-group tn-modal-full">
                    <label>Tanggal</label>
                    <div class="tn-modal-control tn-modal-readonly">
                        {{ now()->format('d M Y') }}
                    </div>
                </div>

                <div class="tn-modal-group tn-modal-full">
                    <label>Sesi</label>
                    <select name="session" class="tn-modal-control" required>
                        <option value="morning" @disabled(!$morningAvailable) @selected(old('session', $defaultSession)==='morning' )>
                            Pagi (08.00 - 11.00)
                        </option>
                        <option value="afternoon" @disabled(!$afternoonAvailable) @selected(old('session', $defaultSession)==='afternoon' )>
                            Siang (14.00 - 19.00)
                        </option>
                    </select>

                    <small style="color:var(--text-light);display:block;margin-top:6px;">
                        Sesi 1 ditutup jam 10:00, sesi 2 ditutup jam 20:00.
                    </small>

                    @if(!$morningAvailable && !$afternoonAvailable)
                    <small style="color:var(--text-light);display:block;margin-top:6px;">
                        Sesi telah berakhir, silahkan booking besok.
                    </small>
                    @endif
                </div>

                <div class="tn-modal-group tn-modal-full">
                    <label>Software</label>
                    <select name="software_id" class="tn-modal-control" required>
                        <option disabled selected>Pilih Software</option>
                        @foreach($softwares as $software)
                        <option value="{{ $software->id }}">
                            {{ $software->name }} (versi {{ $software->version }}) 
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="tn-modal-group tn-modal-full">
                    <label>Catatan</label>
                    <textarea name="note" class="tn-modal-control" placeholder="Catatan tambahan...">{{ old('note') }}</textarea>
                </div>
            </div>

            <div class="tn-modal-actions">
                <button type="button" class="btn-secondary close-modal">
                    Batal
                </button>

                @if($morningAvailable || $afternoonAvailable)
                <button
                    type="submit"
                    class="btn-primary">
                    Buat Booking
                </button>
                @else
                <button
                    type="button"
                    class="btn-primary"
                    data-tn-blocked
                    data-tn-only-cancel="true"
                    data-tn-type="warning"
                    data-tn-title="Sesi telah berakhir"
                    data-tn-message="Sesi telah berakhir, silahkan booking besok">
                    Buat Booking
                </button>
                @endif
            </div>
        </form>
    </div>
</div>