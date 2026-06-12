<?php

namespace App\Http\Controllers;

use App\Models\Penginstalan;
use App\Models\Software;
use App\Models\User;
use App\Services\InstallationBookingService;
use App\Services\TicketFlowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PenginstalanController extends Controller
{
    public function __construct(
        private TicketFlowService $ticketFlowService,
        private InstallationBookingService $bookingService
    ) {}
   

    public function index()
    {
        $penginstalans = Penginstalan::with(['ticket', 'user', 'software'])
            ->latest()
            ->paginate(10);

        return view('admin.penginstalan.index', [
            'menu' => 'penginstalan',
            'title' => 'Data Penginstalan',
            'penginstalans' => $penginstalans,
        ]);
    }

    public function create()
    {
        $users = User::orderBy('name')->get();
        $softwares = Software::orderBy('name')->get();

        return view('admin.penginstalan.create', compact('users', 'softwares'));
    }

    public function store(Request $request)
    {
        $messages = [
            'user_id.required' => 'Please select a user.',
            'software_id.required' => 'Please select a software.',
            'installation_result.in' => 'Installation result must be success or failed.',
            'priority.in' => 'Priority must be normal, high, or urgent.',
            'estimated_finish.date' => 'Estimated finish must be a valid date.',
            'is_public.boolean' => 'Public status must be true or false.',
        ];

        $validator = Validator::make($request->all(), [
            'user_id'             => ['required', 'exists:users,id'],
            'software_id'         => ['required', 'exists:software,id'],
            'installation_result'  => ['nullable', Rule::in(['success', 'failed'])],
            'note'                => ['nullable', 'string'],
            'priority'            => ['nullable', Rule::in(['normal', 'high', 'urgent'])],
            'estimated_finish'    => ['nullable', 'date'],
            'is_public'           => ['nullable', 'boolean'],
        ], $messages);

        if ($validator->fails()) {
            return redirect()
                ->route('penginstalan.index')
                ->withErrors($validator)
                ->withInput();
        }

        $this->ticketFlowService->createInstallation([
            'user_id'             => $request->user_id,
            'software_id'         => $request->software_id,
            'installation_result' => $request->installation_result,
            'note'                => $request->note,
            'priority'            => $request->priority ?? 'normal',
            'estimated_finish'    => $request->estimated_finish,
            'is_public'           => $request->boolean('is_public', true),
            'changed_by'          => "1",
        ]);

        return redirect()
            ->route('penginstalan.index')
            ->with('success', 'Installation and ticket created successfully.');
    }

    public function show(Penginstalan $penginstalan)
    {
        $penginstalan->load(['ticket', 'user', 'software']);

        return view('admin.penginstalan.show', compact('penginstalan'));
    }

    public function edit(Penginstalan $penginstalan)
    {
        $penginstalan->load(['ticket', 'user', 'software']);

        $users = User::orderBy('name')->get();
        $softwares = Software::orderBy('name')->get();

        return view('admin.penginstalan.edit', compact('penginstalan', 'users', 'softwares'));
    }

    public function update(Request $request, Penginstalan $penginstalan)
    {
        $messages = [
            'user_id.required' => 'Please select a user.',
            'software_id.required' => 'Please select a software.',
            'installation_result.in' => 'Installation result must be success or failed.',
            'priority.in' => 'Priority must be normal, high, or urgent.',
            'estimated_finish.date' => 'Estimated finish must be a valid date.',
            'is_public.boolean' => 'Public status must be true or false.',
        ];

        $validator = Validator::make($request->all(), [
            'user_id'             => ['required', 'exists:users,id'],
            'software_id'         => ['required', 'exists:software,id'],
            'installation_result'  => ['nullable', Rule::in(['success', 'failed'])],
            'note'                => ['nullable', 'string'],
            'priority'            => ['nullable', Rule::in(['normal', 'high', 'urgent'])],
            'estimated_finish'    => ['nullable', 'date'],
            'is_public'           => ['nullable', 'boolean'],
        ], $messages);

        if ($validator->fails()) {
            return redirect()
                ->route('penginstalan.index')
                ->withErrors($validator)
                ->withInput();
        }

        $this->ticketFlowService->updateInstallation($penginstalan, [
            'user_id'             => $request->user_id,
            'software_id'         => $request->software_id,
            'installation_result' => $request->installation_result,
            'note'                => $request->note,
            'priority'            => $request->priority ?? 'normal',
            'estimated_finish'    => $request->estimated_finish,
            'is_public'           => $request->boolean('is_public', true),
        ]);

        return redirect()
            ->route('penginstalan.index')
            ->with('edit', 'Installation updated successfully.');
    }

    public function destroy(Penginstalan $penginstalan)
    {
        $penginstalan->delete();

        return redirect()
            ->route('penginstalan.index')
            ->with('success', 'Installation moved to recycle bin.');
    }

    public function trash()
    {
        $penginstalans = Penginstalan::onlyTrashed()
            ->with(['ticket', 'user', 'software'])
            ->latest()
            ->get();

        return view('admin.penginstalan.trash', compact('penginstalans'));
    }

    public function restore($id)
    {
        Penginstalan::onlyTrashed()
            ->findOrFail($id)
            ->restore();

        return back()->with('success', 'Installation restored successfully.');
    }

    public function restoreAll()
    {
        Penginstalan::onlyTrashed()->restore();

        return back()->with('success', 'All installations restored successfully.');
    }

    public function forceComplete(Penginstalan $penginstalan)
    {
        if (!$penginstalan->ticket) {
            return back()->withErrors([
                'ticket' => 'Ticket tidak ditemukan.'
            ]);
        }

        if (in_array($penginstalan->ticket->status, [
            'completed',
            'failed',
            'cancelled'
        ])) {
            return back()->withErrors([
                'ticket' => 'Ticket sudah selesai.'
            ]);
        }

        $this->bookingService->finishTicket(
            $penginstalan->ticket,
            'completed',
            Auth::id()
        );

        return back()->with(
            'success',
            'Ticket berhasil diselesaikan.'
        );
    }

    public function forceFailed(Penginstalan $penginstalan)
    {
        if (!$penginstalan->ticket) {
            return back()->withErrors([
                'ticket' => 'Ticket tidak ditemukan.'
            ]);
        }

        if (in_array($penginstalan->ticket->status, [
            'completed',
            'failed',
            'cancelled'
        ])) {
            return back()->withErrors([
                'ticket' => 'Ticket sudah selesai.'
            ]);
        }

        $this->bookingService->finishTicket(
            $penginstalan->ticket,
            'failed',
            Auth::id()
        );

        return back()->with(
            'success',
            'Ticket berhasil ditandai gagal.'
        );
    }
}
