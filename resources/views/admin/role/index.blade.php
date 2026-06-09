@extends('template_admin.layout')
@section('title', 'Role Management')

@section('content')

<div class="page-header">

    <div>
        <h1>Role Management</h1>
        <p>Manage system roles and permissions.</p>
    </div>

    @if($roles->total() < 3)
        <button
        type="button"
        class="btn-primary open-modal"
        data-url="{{ route('role.create') }}">

        <i data-lucide="plus"></i>
        <span>Add Role</span>

        </button>
        @endif

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
                id="roleSearch"
                placeholder="Search role...">
        </div>

        <span style="color:var(--text-light)">
            Total: {{ $roles->total() }} Roles
        </span>

    </div>

    <table>

        <thead>
            <tr>
                <th>Role</th>
                <th>Description</th>
                <th>Created</th>
                <th width="170">Action</th>
            </tr>
        </thead>

        <tbody>

            @forelse($roles as $role)

            <tr>

                <td>

                    <div
                        style="
                            display:flex;
                            align-items:center;
                            gap:12px;
                        ">

                        <div
                            class="glass"
                            style="
                                width:42px;
                                height:42px;
                                border-radius:14px;
                                display:flex;
                                align-items:center;
                                justify-content:center;
                            ">

                            <i data-lucide="shield"></i>

                        </div>

                        <strong>{{ $role->name }}</strong>

                    </div>

                </td>

                <td>
                    {{ $role->description ?? '-' }}
                </td>

                <td>
                    {{ $role->created_at->format('d M Y') }}
                </td>

                <td>

                    <div class="table-actions">

                        <button
                            type="button"
                            class="btn-secondary open-modal"
                            data-url="{{ route('role.show',$role->id) }}">

                            <i data-lucide="eye"></i>

                        </button>





                    </div>

                </td>

            </tr>

            @empty

            <tr>

                <td colspan="4" style="text-align:center;padding:40px">
                    No role available.
                </td>

            </tr>

            @endforelse

        </tbody>

    </table>

</div>

<div class="pagination-wrapper">
    {{ $roles->links() }}
</div>

<div id="modalContainer"></div>

@endsection