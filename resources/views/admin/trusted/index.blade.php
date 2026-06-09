@extends('template_admin.layout')
@section('title', 'Trusted Websites')
@section('content')

<div class="page-header">

    <div>
        <h1>Trusted Websites</h1>
        <p>Manage official trusted websites used by the system.</p>
    </div>

    <button
        type="button"
        class="btn-primary open-modal"
        data-url="{{ route('trusted.create') }}">

        <i data-lucide="plus"></i>
        <span>Add Trusted Website</span>

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
                id="trustedWebsiteSearch"
                placeholder="Search trusted website...">

        </div>

        <span style="color:var(--text-light)">
            Total: {{ $trustedWebsites->total() }} Website
        </span>

    </div>

    <table>

        <thead>

            <tr>
                <th>Name</th>
                <th>URL</th>
                <th>Description</th>
                <th>Status</th>
                <th>Created</th>
                <th width="170">Action</th>
            </tr>

        </thead>

        <tbody>

            @forelse($trustedWebsites as $trusted)

            <tr class="trusted-row">

                <td>

                    <div style="display:flex;align-items:center;gap:12px;">

                        <div
                            class="glass"
                            style="width:42px;height:42px;border-radius:14px;display:flex;align-items:center;justify-content:center;">

                            <i data-lucide="globe"></i>

                        </div>

                        <div>

                            <strong>{{ $trusted->name }}</strong>

                            <br>

                            <small style="color:var(--text-light);">
                                Trusted Source
                            </small>

                        </div>

                    </div>

                </td>

                <td style="max-width:280px;word-break:break-all;">
                    <a
                        href="{{ $trusted->url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        style="color:var(--primary);text-decoration:none;">
                        {{ $trusted->url }}
                    </a>
                </td>

                <td>
                    {{ $trusted->description ?? '-' }}
                </td>

                <td>
                    @if($trusted->is_active)
                    <span class="badge success">Active</span>
                    @else
                    <span class="badge danger">Inactive</span>
                    @endif
                </td>

                <td>
                    {{ $trusted->created_at->format('d M Y') }}
                </td>

                <td>

                    <div class="table-actions">

                        <button
                            type="button"
                            class="btn-secondary open-modal"
                            data-url="{{ route('trusted.show', $trusted->id) }}">

                            <i data-lucide="eye"></i>

                        </button>

                        <button
                            type="button"
                            class="btn-secondary open-modal"
                            data-url="{{ route('trusted.edit', $trusted->id) }}">

                            <i data-lucide="pencil"></i>

                        </button>

                        <form
                            action="{{ route('trusted.destroy', $trusted->id) }}"
                            method="POST">

                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="btn-secondary"
                                data-tn-confirm
                                data-tn-type="danger"
                                data-tn-title="Move trusted website to recycle bin?"
                                data-tn-message="This trusted website will be moved to the recycle bin. The installation can still be restored later."
                                data-tn-proceed-text="Move to Bin">

                                <i data-lucide="trash-2"></i>

                            </button>

                        </form>

                    </div>

                </td>

            </tr>

            @empty

            <tr>

                <td colspan="6" style="text-align:center;padding:40px;">
                    No trusted website available.
                </td>

            </tr>

            @endforelse

        </tbody>

    </table>

</div>

<div class="pagination-wrapper">
    {{ $trustedWebsites->links() }}
</div>

<div id="modalContainer"></div>

@endsection