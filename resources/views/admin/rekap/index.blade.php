@extends('template_admin.layout')
@section('title', 'Rekap Management')

@section('content')

<div class="page-header">

    <div>
        <h1>Rekap Management</h1>
        <p>Monitor daily ticket summary across installations and repairs.</p>
    </div>
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
                id="rekapSearch"
                placeholder="Search rekap...">
        </div>

        <div class="table-footer-actions">

            <span style="color:var(--text-light)">
                Total: {{ $rekaps->total() }} Rekap
            </span>

        </div>

    </div>

    <table>

        <thead>
            <tr>
                <th>Date</th>
                <th>Installation</th>
                <th>Repair</th>
                <th>Completed</th>
                <th>Failed</th>
                <th>Pending</th>
               
            </tr>
        </thead>

        <tbody>

            @forelse($rekaps as $rekap)

            <tr class="rekap-row">

                <td>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div
                            class="glass"
                            style="width:42px;height:42px;border-radius:14px;display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="calendar-days"></i>
                        </div>

                        <div>
                            <strong>{{ $rekap->rekap_date->format('d M Y') }}</strong>
                            <br>
                            <small style="color:var(--text-light);">
                                Daily summary
                            </small>
                        </div>
                    </div>
                </td>

                <td>
                    <span class="badge success">
                        {{ $rekap->total_installations }}
                    </span>
                </td>

                <td>
                    <span class="badge warning">
                        {{ $rekap->total_repairs }}
                    </span>
                </td>

                <td>
                    <span class="badge success">
                        {{ $rekap->completed_tickets }}
                    </span>
                </td>

                <td>
                    <span class="badge danger">
                        {{ $rekap->failed_tickets }}
                    </span>
                </td>

                <td>
                    <span class="badge warning">
                        {{ $rekap->pending_tickets }}
                    </span>
                </td>

             
            </tr>

            @empty

            <tr>
                <td colspan="7" style="text-align:center;padding:40px">
                    No rekap available.
                </td>
            </tr>

            @endforelse

        </tbody>

    </table>

</div>

<div class="pagination-wrapper">
    {{ $rekaps->links() }}
</div>

<div id="modalContainer"></div>

@endsection