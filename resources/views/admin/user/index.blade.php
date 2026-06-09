@extends('template_admin.layout')
@section('title', 'User Management')
@section('content')

<div class="page-header">

    <div>
        <h1>User Management</h1>
        <p>Manage system users, access roles, and account information.</p>
    </div>

    <button
        type="button"
        class="btn-primary open-modal"
        data-url="{{ route('user.create') }}">

        <i data-lucide="plus"></i>
        <span>Add User</span>

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
                id="userSearch"
                placeholder="Search user...">
        </div>

        <div class="table-footer-actions">

            <button
                type="button"
                class="btn-secondary open-modal"
                data-url="{{ route('user.trash') }}">

                <i data-lucide="archive-restore"></i>
                Recycle Bin

            </button>

            <form
                action="{{ route('user.destroyAll') }}"
                method="POST">

                @csrf
                @method('DELETE')

                <button
                    class="btn-secondary"
                    data-tn-confirm
                    data-tn-type="danger"
                    data-tn-title="Move all users to recycle bin?"
                    data-tn-message="All active users will be moved to the recycle bin."
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
                <th>Name</th>
                <th>Role</th>
                <th>Username</th>
                <th>Identity</th>
                <th>Contact</th>
                <th width="170">Action</th>
            </tr>

        </thead>

        <tbody>

            @forelse($users as $user)

            <tr>

                <td>

                    <div style="display:flex;align-items:center;gap:12px;">

                        <div
                            class="glass"
                            style="width:42px;height:42px;border-radius:14px;display:flex;align-items:center;justify-content:center;overflow:hidden;">

                            @if($user->avatar)
                            <img
                                src="{{ asset('storage/'.$user->avatar) }}"
                                alt="{{ $user->name }}"
                                style="width:100%;height:100%;object-fit:cover;">
                            @else
                            <i data-lucide="user"></i>
                            @endif

                        </div>

                        <div>

                            <strong>{{ $user->name }}</strong>

                            <br>

                            <small style="color:var(--text-light);">
                                {{ $user->email ?? 'No email' }}
                            </small>

                        </div>

                    </div>

                </td>

                <td>
                    {{ $user->role->name ?? '-' }}
                </td>

                <td>
                    {{ $user->username }}
                </td>

                <td>
                    @if($user->nim)
                    NIM: {{ $user->nim }}
                    @elseif($user->nip)
                    NIP: {{ $user->nip }}
                    @else
                    -
                    @endif
                </td>

                <td>
                    {{ $user->no_hp }}
                </td>

                <td>

                    <div class="table-actions">

                        <button
                            type="button"
                            class="btn-secondary open-modal"
                            data-url="{{ route('user.show', $user->id) }}">

                            <i data-lucide="eye"></i>

                        </button>

                        <button
                            type="button"
                            class="btn-secondary open-modal"
                            data-url="{{ route('user.edit', $user->id) }}">

                            <i data-lucide="pencil"></i>

                        </button>

                        <form
                            action="{{ route('user.destroy', $user->id) }}"
                            method="POST">

                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="btn-secondary"
                                data-tn-confirm
                                data-tn-type="danger"
                                data-tn-title="Move user to recycle bin?"
                                data-tn-message="This user will be moved to the recycle bin. The account can still be restored later."
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
                    No user available.
                </td>

            </tr>

            @endforelse

        </tbody>

    </table>

</div>

<div class="pagination-wrapper">
    {{ $users->links() }}
</div>

<div id="modalContainer"></div>

@endsection