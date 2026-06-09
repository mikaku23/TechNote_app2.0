<div class="tn-modal-overlay">

    <div class="glass card tn-modal-card">

        <div class="tn-modal-header">

            <div class="tn-modal-header-left">
                <h2 class="tn-modal-title">
                    Recycle Bin
                </h2>

                <p class="tn-modal-subtitle">
                    Deleted users can be restored.
                </p>
            </div>

            <button
                type="button"
                class="icon-btn close-modal">

                <i data-lucide="x"></i>

            </button>

        </div>

        <form
            action="{{ route('user.restoreAll') }}"
            method="POST">

            @csrf
            @method('PUT')

            <button class="btn-primary">

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

            @forelse($users as $user)

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
                        {{ $user->name }}
                    </strong>

                    <br>

                    <small
                        style="color:var(--text-light)">

                        {{ $user->username }}

                    </small>

                </div>

                <form
                    action="{{ route('user.restore',$user->id) }}"
                    method="POST">

                    @csrf
                    @method('PUT')

                    <button class="btn-primary">

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