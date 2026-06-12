<div class="tn-modal-overlay">
    <div class="glass card tn-modal-card">

        <div class="tn-modal-header">
            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">Recycle Bin</h2>
                <p class="tn-modal-subtitle">
                    Deleted repair records can be restored.
                </p>
            </div>

            <button type="button" class="icon-btn close-modal">
                <i data-lucide="x"></i>
            </button>
        </div>

        <form action="{{ route('perbaikan.restoreAll') }}" method="POST">
            @csrf
            @method('PUT')

            <button
                class="btn-primary"
                data-tn-confirm
                data-tn-type="success"
                data-tn-title="Restore all repairs?"
                data-tn-message="All repair records in the recycle bin will be restored."
                data-tn-proceed-text="Restore All">
                <i data-lucide="rotate-ccw"></i>
                Restore All
            </button>
        </form>

        <div style="display:flex;flex-direction:column;gap:12px;">
            @forelse($perbaikans as $perbaikan)
            <div
                class="glass"
                style="padding:16px;border-radius:18px;display:flex;justify-content:space-between;align-items:center;gap:16px;">

                <div>
                    <strong>
                        {{ $perbaikan->ticket->ticket_number ?? 'No Ticket' }}
                    </strong>

                    <br>

                    <small style="color:var(--text-light)">
                        {{ $perbaikan->item_name ?? '-' }} — {{ $perbaikan->user->name ?? '-' }}
                    </small>
                </div>

                <form
                    action="{{ route('perbaikan.restore', $perbaikan->id) }}"
                    method="POST">
                    @csrf
                    @method('PUT')

                    <button
                        class="btn-primary"
                        data-tn-confirm
                        data-tn-type="success"
                        data-tn-title="Restore this repair?"
                        data-tn-message="The deleted repair will be returned to the active list."
                        data-tn-proceed-text="Restore">
                        <i data-lucide="rotate-ccw"></i>
                        Restore
                    </button>
                </form>
            </div>
            @empty
            <div style="text-align:center;padding:30px;">
                Recycle bin is empty.
            </div>
            @endforelse
        </div>

    </div>
</div>