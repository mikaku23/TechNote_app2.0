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



            <form
                action="{{ route('perbaikan.destroyAll') }}"
                method="POST">


                @csrf
                @method('DELETE')


                <button
                    class="btn-secondary"

                    data-tn-confirm
                    data-tn-type="danger"
                    data-tn-title="Move all repairs to recycle bin?"
                    data-tn-message="All active repair records will be moved to the recycle bin."
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

                <th>Ticket</th>

                <th>User</th>

                <th>Item</th>

                <th>Status</th>

                <th>Result</th>

                <th>Created</th>

                <th width="170">Action</th>


            </tr>


        </thead>




        <tbody>



            @forelse($perbaikans as $perbaikan)



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

                            <i data-lucide="wrench"></i>


                        </div>



                        <div>


                            <strong>

                                {{ $perbaikan->ticket->ticket_number ?? 'No Ticket' }}

                            </strong>



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


                    <strong>

                        {{ $perbaikan->item_name ?? '-' }}

                    </strong>


                    <br>


                    <small style="color:var(--text-light);">

                        {{ $perbaikan->item_location ?? '-' }}

                    </small>



                </td>





                <td>

                    {{ ucfirst($perbaikan->ticket->status ?? '-') }}


                </td>





                <td>



                    @if($perbaikan->repair_result === 'success')


                    <span class="badge success">

                        Success

                    </span>



                    @elseif($perbaikan->repair_result === 'failed')


                    <span class="badge danger">

                        Failed

                    </span>



                    @elseif($perbaikan->repair_result === 'unrepairable')


                    <span class="badge danger">

                        Unrepairable

                    </span>



                    @else


                    <span class="badge warning">

                        Pending

                    </span>



                    @endif



                </td>





                <td>

                    {{ $perbaikan->created_at->format('d M Y') }}

                </td>






                <td>


                    <div class="table-actions">





                        <button

                            type="button"

                            class="btn-secondary open-modal"

                            data-url="{{ route('perbaikan.show',$perbaikan->id) }}">


                            <i data-lucide="eye"></i>


                        </button>






                        <button

                            type="button"

                            class="btn-secondary open-modal"

                            data-url="{{ route('perbaikan.edit',$perbaikan->id) }}">


                            <i data-lucide="pencil"></i>


                        </button>







                        <form

                            action="{{ route('perbaikan.destroy',$perbaikan->id) }}"

                            method="POST">


                            @csrf

                            @method('DELETE')



                            <button

                                class="btn-secondary"


                                data-tn-confirm

                                data-tn-type="danger"

                                data-tn-title="Move repair to recycle bin?"

                                data-tn-message="This repair record will be moved to the recycle bin and can be restored later."

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