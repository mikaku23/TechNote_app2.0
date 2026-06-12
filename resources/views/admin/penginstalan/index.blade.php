@extends('template_admin.layout')
@section('title', 'Installation Management')
@section('content')

<div class="page-header">

    <div>
        <h1>Installation Management</h1>
        <p>Manage software installation records.</p>
    </div>

    <button
        type="button"
        class="btn-primary open-modal"
        data-url="{{ route('penginstalan.create') }}">

        <i data-lucide="plus"></i>
        <span>Add Installation</span>

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
                id="penginstalanSearch"
                placeholder="Search installation...">
        </div>

        <div class="table-footer-actions">

            <button
                type="button"
                class="btn-secondary open-modal"
                data-url="{{ route('penginstalan.trash') }}">

                <i data-lucide="archive-restore"></i>
                Recycle Bin

            </button>

            <span style="color:var(--text-light)">
                Total: {{ $penginstalans->total() }} Installation
            </span>

        </div>

    </div>

    <table>

        <thead>
            <tr>
                <th>Ticket</th>
                <th>User</th>
                <th>Software</th>
                <th>Status</th>
                <th>Result</th>
                <th>Created</th>
                <th width="170">Action</th>
            </tr>
        </thead>

        <tbody>

            @forelse($penginstalans as $penginstalan)

            <tr class="penginstalan-row">

                <td>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div
                            class="glass"
                            style="width:42px;height:42px;border-radius:14px;display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="ticket"></i>
                        </div>

                        <div>
                            <strong>{{ $penginstalan->ticket->ticket_number ?? 'No Ticket' }}</strong>
                            <br>
                            <small style="color:var(--text-light);">
                                {{ $penginstalan->ticket->type ?? 'installation' }}
                            </small>
                        </div>
                    </div>
                </td>

                <td>
                    {{ $penginstalan->user->name ?? '-' }}
                </td>

                <td>
                    {{ $penginstalan->software->name ?? '-' }}
                </td>



                <td>
                    {{ ucfirst($penginstalan->ticket->status ?? '-') }}
                </td>

                <td>
                    @if($penginstalan->installation_result === 'success')
                    <span class="badge success">Success</span>
                    @elseif($penginstalan->installation_result === 'failed')
                    <span class="badge danger">Failed</span>
                    @else
                    <span class="badge warning">Pending</span>
                    @endif
                </td>

                <td>
                    {{ $penginstalan->created_at->format('d M Y') }}
                </td>

                <td>
                    <div class="table-actions">

                        {{-- DETAIL --}}
                        <button
                            type="button"
                            class="btn-icon secondary open-modal"
                            data-url="{{ route('penginstalan.show', $penginstalan->id) }}"
                            title="Detail Ticket">

                            <i data-lucide="eye"></i>

                        </button>


                        @php
                        $ticketStatus = $penginstalan->ticket?->status;

                        $isDone = in_array($ticketStatus, [
                        'completed',
                        'failed'
                        ]);
                        @endphp


                        {{-- COMPLETE --}}
                        <form
                            action="{{ route('penginstalan.complete', $penginstalan->id) }}"
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
                                data-tn-message="Ticket penginstalan akan diubah menjadi selesai."
                                data-tn-proceed-text="Selesaikan">

                                <i data-lucide="check"></i>

                            </button>

                        </form>



                        {{-- FAILED --}}
                        <form
                            action="{{ route('penginstalan.failed', $penginstalan->id) }}"
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

            </tr>

            @empty

            <tr>
                <td colspan="8" style="text-align:center;padding:40px;">
                    No installation available.
                </td>
            </tr>

            @endforelse

        </tbody>

    </table>

</div>

<div class="pagination-wrapper">
    {{ $penginstalans->links() }}
</div>

<div id="modalContainer"></div>

@endsection