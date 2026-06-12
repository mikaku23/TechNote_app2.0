@extends('template_admin.layout')
@section('title', 'Repair Management')

@section('content')

<div class="page-header">

    <div>
        <h1>Repair Management</h1>
        <p>Manage repair service records.</p>
    </div>

    <button
        type="button"
        class="btn-primary open-modal"
        data-url="{{ route('perbaikan.create') }}">

        <i data-lucide="plus"></i>
        <span>Add Repair</span>

    </button>

</div>

@if (session('success'))
<div class="tn-alert tn-alert-success">
    <strong>Success!</strong>
    <p>{{ session('success') }}</p>
</div>
@endif

@if (session('edit'))
<div class="tn-alert tn-alert-edit">
    <strong>Updated!</strong>
    <p>{{ session('edit') }}</p>
</div>
@endif

@if ($errors->any())
<div class="tn-alert tn-alert-error">
    <strong>Oops! Please correct the following errors:</strong>
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="glass table-card motion-card">

    <div class="table-toolbar">

        <div class="search-box">
            <i data-lucide="search"></i>
            <input
                type="text"
                id="perbaikanSearch"
                placeholder="Search repair...">
        </div>

        <div class="table-footer-actions">

            <button
                type="button"
                class="btn-secondary open-modal"
                data-url="{{ route('perbaikan.trash') }}">

                <i data-lucide="archive-restore"></i>
                Recycle Bin

            </button>

            <span style="color:var(--text-light)">
                Total: {{ $perbaikans->total() }} Repairs
            </span>

        </div>

    </div>

    <table>

        <thead>
            <tr>
                <th>Ticket</th>
                <th>User</th>
                <th>Item</th>
                <th>Status</th>
                <th>Result</th>
                <th>Force Update</th>
                <th width="220">Action</th>
            </tr>
        </thead>

        <tbody>

            @forelse($perbaikans as $perbaikan)

            @php
            $ticketStatus = $perbaikan->ticket?->status;
            $isDone = in_array($ticketStatus, ['completed', 'failed', 'cancelled']);
            @endphp

            <tr class="perbaikan-row">

                <td>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div
                            class="glass"
                            style="width:42px;height:42px;border-radius:14px;display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="wrench"></i>
                        </div>

                        <div>
                            <strong>{{ $perbaikan->ticket->ticket_number ?? 'No Ticket' }}</strong>
                            <br>
                            <small style="color:var(--text-light);">
                                {{ $perbaikan->ticket->type ?? 'repair' }}
                            </small>
                        </div>
                    </div>
                </td>

                <td>
                    {{ $perbaikan->user->name ?? '-' }}
                </td>

                <td>
                    <strong>{{ $perbaikan->item_name ?? '-' }}</strong>
                    <br>
                    <small style="color:var(--text-light);">
                        {{ $perbaikan->item_location ?? '-' }}
                    </small>
                </td>

                <td>
                    @if($ticketStatus === 'completed')
                    <span class="badge success">Completed</span>
                    @elseif($ticketStatus === 'failed')
                    <span class="badge danger">Failed</span>
                    @elseif($ticketStatus === 'cancelled')
                    <span class="badge danger">Cancelled</span>
                    @elseif($ticketStatus === 'processing')
                    <span class="badge warning">Processing</span>
                    @elseif($ticketStatus === 'diagnosis')
                    <span class="badge warning">Diagnosis</span>
                    @else
                    <span class="badge warning">Waiting</span>
                    @endif
                </td>

                <td>
                    @if($perbaikan->repair_result === 'success')
                    <span class="badge success">Success</span>
                    @elseif($perbaikan->repair_result === 'failed')
                    <span class="badge danger">Failed</span>
                    @elseif($perbaikan->repair_result === 'unrepairable')
                    <span class="badge danger">Unrepairable</span>
                    @else
                    <span class="badge warning">Pending</span>
                    @endif
                </td>

                <td>
                    <div class="table-actions">

                        {{-- FORCE COMPLETE --}}
                        <form
                            action="{{ route('perbaikan.complete', $perbaikan->id) }}"
                            method="POST">

                            @csrf
                            @method('PATCH')

                            <button
                                type="submit"
                                class="btn-icon success {{ $isDone ? 'disabled-action' : '' }}"
                                title="Selesaikan Ticket"
                                @disabled($isDone)
                                data-tn-confirm
                                data-tn-type="success"
                                data-tn-title="Selesaikan Ticket?"
                                data-tn-message="Ticket repair akan diubah menjadi selesai."
                                data-tn-proceed-text="Selesaikan">

                                <i data-lucide="check"></i>

                            </button>

                        </form>

                        {{-- FORCE FAILED --}}
                        <form
                            action="{{ route('perbaikan.failed', $perbaikan->id) }}"
                            method="POST">

                            @csrf
                            @method('PATCH')

                            <button
                                type="submit"
                                class="btn-icon danger {{ $isDone ? 'disabled-action' : '' }}"
                                title="Gagalkan Ticket"
                                @disabled($isDone)
                                data-tn-confirm
                                data-tn-type="danger"
                                data-tn-title="Gagal Selesaikan Ticket?"
                                data-tn-message="Ticket akan ditandai gagal."
                                data-tn-proceed-text="Tandai Gagal">

                                <i data-lucide="x"></i>

                            </button>

                        </form>

                    </div>
                </td>

                <td>
                    <div class="table-actions">

                        {{-- DETAIL --}}
                        <button
                            type="button"
                            class="btn-secondary open-modal"
                            data-url="{{ route('perbaikan.show', $perbaikan->id) }}">

                            <i data-lucide="eye"></i>

                        </button>

                        {{-- EDIT --}}
                        <button
                            type="button"
                            class="btn-secondary open-modal {{ $isDone ? 'disabled-action' : '' }}"
                            data-url="{{ route('perbaikan.edit', $perbaikan->id) }}"
                            @disabled($isDone)
                            title="{{ $isDone ? 'Ticket sudah selesai' : 'Edit Ticket' }}">

                            <i data-lucide="pencil"></i>

                        </button>

                        {{-- DELETE --}}
                        <form
                            action="{{ route('perbaikan.destroy', $perbaikan->id) }}"
                            method="POST">

                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="btn-secondary"
                                data-tn-confirm
                                data-tn-type="danger"
                                data-tn-title="Move repair to recycle bin?"
                                data-tn-message="This repair record will be moved to the recycle bin."
                                data-tn-proceed-text="Move to Bin">

                                <i data-lucide="trash-2"></i>

                            </button>

                        </form>

                    </div>
                </td>

            </tr>

            @empty

            <tr>
                <td colspan="7" style="text-align:center;padding:40px">
                    No repair available.
                </td>
            </tr>

            @endforelse

        </tbody>

    </table>

</div>

<div class="pagination-wrapper">
    {{ $perbaikans->links() }}
</div>

<div id="modalContainer"></div>

@endsection