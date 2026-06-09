@extends('template_admin.layout')
@section('title', 'Software Library')
@section('content')

<div class="page-header">

    <div>
        <h1>Software Library</h1>
        <p>Manage software available for installation services.</p>
    </div>

    <button
        type="button"
        class="btn-primary open-modal"
        data-url="{{ route('software.create') }}">

        <i data-lucide="plus"></i>
        <span>Add Software</span>

    </button>

</div>

<div class="glass table-card motion-card">

    <div class="table-toolbar">

        <div class="search-box">

            <i data-lucide="search"></i>

            <input
                type="text"
                id="softwareSearch"
                placeholder="Search software...">

        </div>

        <span style="color:var(--text-light)">
            Total: {{ $softwares->total() }} Software
        </span>

    </div>

    <table>

        <thead>

            <tr>
                <th>Name</th>
                <th>Developer</th>
                <th>Version</th>
                <th>Created</th>
                <th width="170">Action</th>
            </tr>

        </thead>

        <tbody>

            @forelse($softwares as $software)

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

                            <i data-lucide="package"></i>

                        </div>

                        <div>

                            <strong>
                                {{ $software->name }}
                            </strong>

                            <br>

                            <small
                                style="
                                    color:var(--text-light);
                                ">
                                Software Installation
                            </small>

                        </div>

                    </div>

                </td>

                <td>
                    {{ $software->developer ?? '-' }}
                </td>

                <td>
                    {{ $software->version ?? '-' }}
                </td>

                <td>
                    {{ $software->created_at->format('d M Y') }}
                </td>

                <td>

                    <div class="table-actions">

                        <button
                            type="button"
                            class="btn-secondary open-modal"
                            data-url="{{ route('software.show',$software->id) }}">

                            <i data-lucide="eye"></i>

                        </button>

                        <button
                            type="button"
                            class="btn-secondary open-modal"
                            data-url="{{ route('software.edit',$software->id) }}">

                            <i data-lucide="pencil"></i>

                        </button>

                        <form
                            action="{{ route('software.destroy',$software->id) }}"
                            method="POST">

                            @csrf
                            @method('DELETE')

                            <button
                                type="submit"
                                class="btn-secondary">

                                <i data-lucide="trash-2"></i>

                            </button>

                        </form>

                    </div>

                </td>

            </tr>

            @empty

            <tr>

                <td
                    colspan="5"
                    style="
                        text-align:center;
                        padding:40px;
                    ">

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