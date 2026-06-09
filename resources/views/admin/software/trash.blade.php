<div class="tn-modal-overlay">

    <div class="glass card tn-modal-card">

        <div class="tn-modal-header">

            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">
                    Recycle Bin
                </h2>

                <p class="tn-modal-subtitle">
                    Deleted softwares can be restored.
                </p>
            </div>

            <button
                type="button"
                class="icon-btn close-modal">

                <i data-lucide="x"></i>

            </button>

        </div>

        <form
            action="{{ route('software.restoreAll') }}"
            method="POST">

            @csrf
            @method('PUT')

            <button
                class="btn-primary"
                data-tn-confirm
                data-tn-type="success"
                data-tn-title="Restore all software?"
                data-tn-message="All software in the recycle bin will be restored."
                data-tn-proceed-text="Restore All">

                <i data-lucide="rotate-ccw"></i>
                Restore All

            </button>

        </form>

        <div
            style="
                display:flex;
                flex-direction:column;
                gap:12px;
            ">

            @forelse($softwares as $software)

            <div
                class="glass"
                style="
                    padding:16px;
                    border-radius:18px;
                    display:flex;
                    justify-content:space-between;
                    align-items:center;
                ">

                <div>

                    <strong>
                        {{ $software->name }}
                    </strong>

                    <br>

                    <small
                        style="color:var(--text-light)">

                        {{ $software->developer ?? '-' }}

                    </small>

                </div>

                <form
                    action="{{ route('software.restore',$software->id) }}"
                    method="POST">

                    @csrf
                    @method('PUT')

                    <button
                        class="btn-primary"
                        data-tn-confirm
                        data-tn-type="success"
                        data-tn-title="Restore this software?"
                        data-tn-message="The deleted software will be returned to the active list."
                        data-tn-proceed-text="Restore">

                        <i data-lucide="rotate-ccw"></i>
                        Restore

                    </button>

                </form>

            </div>

            @empty

            <div
                style="
                    text-align:center;
                    padding:30px;
                ">

                Recycle bin is empty.

            </div>

            @endforelse

        </div>

    </div>

</div>