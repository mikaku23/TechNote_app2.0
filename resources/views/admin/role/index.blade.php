@extends('template_admin.layout')
@section('title', 'Role Management')

@section('content')

<div class="page-header">

    <div>
        <h1>Role Management</h1>
        <p>Manage system roles and permissions.</p>
    </div>

    @if($roles->total() >= 3)
    <button
        type="button"
        class="btn-primary"
        data-tn-blocked
        data-tn-type="warning"
        data-tn-title="Role limit reached"
        data-tn-message="Maximum of 3 roles allowed. Remove an existing role to add a new one."
        data-tn-only-cancel="true">

        <i data-lucide="plus"></i>
        <span>Add Role</span>

    </button>
    @else
    <button
        type="button"
        class="btn-primary open-modal"
        data-url="{{ route('role.create') }}">

        <i data-lucide="plus"></i>
        <span>Add Role</span>

    </button>
    @endif

</div>

<div class="glass table-card motion-card">

    <div class="table-toolbar">

        <div class="search-box">
            <i data-lucide="search"></i>
            <input
                type="text"
                id="roleSearch"
                placeholder="Search role...">
        </div>

        <div style="display:flex;align-items:center;gap:10px;">

            <button
                type="button"
                class="btn-secondary open-modal"
                data-url="{{ route('role.trash') }}">

                <i data-lucide="archive-restore"></i>

            </button>

            <span style="color:var(--text-light)">
                Total: {{ $roles->total() }} Roles
            </span>

        </div>
    </div>

    <table>

        <thead>
            <tr>
                <th>Role</th>
                <th>Description</th>
                <th>Status</th>
                <th>Created</th>
                <th width="220">Action</th>
            </tr>
        </thead>

        <tbody>

            @forelse($roles as $role)

            <tr>

                <td>
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div
                            class="glass"
                            style="width:42px;height:42px;border-radius:14px;display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="shield"></i>
                        </div>

                        <strong>{{ $role->name }}</strong>
                    </div>
                </td>

                <td>
                    {{ $role->description ?? '-' }}
                </td>

                <td>
                    @if($role->is_active)
                    <span class="badge success">Active</span>
                    @else
                    <span class="badge danger">Inactive</span>
                    @endif
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

                        <form
                            action="{{ route('role.toggleStatus', $role->id) }}"
                            method="POST">

                            @csrf

                            <button
                                type="submit"
                                class="btn-secondary"
                                data-tn-confirm
                                data-tn-type="warning"
                                data-tn-title="{{ $role->is_active ? 'Deactivate this role?' : 'Activate this role?' }}"
                                data-tn-message="{{ $role->is_active ? 'Users with this role will be blocked from logging in.' : 'Users with this role will be allowed to log in again.' }}"
                                data-tn-proceed-text="{{ $role->is_active ? 'Deactivate' : 'Activate' }}">

                                <i data-lucide="{{ $role->is_active ? 'toggle-left' : 'toggle-right' }}"></i>

                            </button>

                        </form>

                        <form
                            action="{{ route('role.destroy',$role->id) }}"
                            method="POST">

                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="btn-secondary"
                                data-tn-confirm
                                data-tn-type="danger"
                                data-tn-title="Move role to recycle bin?"
                                data-tn-message="This role will be moved to the recycle bin. Related users will remain, but login depends on role status and availability."
                                data-tn-proceed-text="Move to Bin">

                                <i data-lucide="trash-2"></i>

                            </button>

                        </form>

                    </div>

                </td>

            </tr>

            @empty

            <tr>
                <td colspan="5" style="text-align:center;padding:40px">
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