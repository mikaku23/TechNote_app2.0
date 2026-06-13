@extends('template_admin.layout')

@section('title', 'Software Management')

@section('content')
<div class="page-header">
    <div>
        <h1>Software Management</h1>
        <p>Manage software and application records.</p>
    </div>

    <button
        type="button"
        class="btn-primary open-modal"
        data-url="{{ route('software.create') }}">
        <i data-lucide="plus"></i>
        <span>Add Software</span>
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
                id="softwareSearch"
                placeholder="Search software...">
        </div>

        <div class="table-footer-actions">
            <button
                type="button"
                class="btn-secondary open-modal"
                data-url="{{ route('software.trash') }}">
                <i data-lucide="archive-restore"></i>
                Recycle Bin
            </button>

            <form action="{{ route('software.destroyAll') }}" method="POST">
                @csrf
                @method('DELETE')

                <button
                    type="submit"
                    class="btn-secondary"
                    data-tn-confirm
                    data-tn-type="danger"
                    data-tn-title="Move all software to recycle bin?"
                    data-tn-message="All active software records will be moved to the recycle bin."
                    data-tn-proceed-text="Delete All">
                    <i data-lucide="trash-2"></i>
                    Delete All
                </button>
            </form>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Software Name</th>
                <th>Developer</th>
                <th>Version</th>
                <th>Description</th>
                <th>Est. Minutes</th>
                <th>Created</th>
                <th width="170">Action</th>
            </tr>
        </thead>

        <tbody>
            @forelse($softwares as $software)
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div
                            class="glass"
                            style="width:42px;height:42px;border-radius:14px;display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="package"></i>
                        </div>

                        <div>
                            <strong>{{ $software->name }}</strong>
                        </div>
                    </div>
                </td>

                <td>{{ $software->developer ?? '-' }}</td>

                <td>
                    <strong>{{ $software->version ?? '-' }}</strong>
                </td>

                <td>
                    {{ \Illuminate\Support\Str::limit($software->description ?? '-', 50) }}
                </td>

                <td>
                    {{ $software->estimated_minutes ?? 30 }} min
                </td>

                <td>
                    {{ $software->created_at ? $software->created_at->format('d M Y') : '-' }}
                </td>

                <td>
                    <div class="table-actions">
                        <button
                            type="button"
                            class="btn-secondary open-modal"
                            data-url="{{ route('software.show', $software->id) }}">
                            <i data-lucide="eye"></i>
                        </button>

                        <button
                            type="button"
                            class="btn-secondary open-modal"
                            data-url="{{ route('software.edit', $software->id) }}">
                            <i data-lucide="pencil"></i>
                        </button>

                        <form action="{{ route('software.destroy', $software->id) }}" method="POST">
                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="btn-secondary"
                                data-tn-confirm
                                data-tn-type="danger"
                                data-tn-title="Move software to recycle bin?"
                                data-tn-message="This software record will be moved to the recycle bin and can be restored later."
                                data-tn-proceed-text="Move to Bin">
                                <i data-lucide="trash-2"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" style="text-align:center;padding:40px;">
                    No software available.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination-wrapper">
    {{ $softwares->links() }}
</div>

<div id="modalContainer"></div>
@endsection