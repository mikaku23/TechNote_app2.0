<?php

namespace App\Http\Controllers;

use App\Models\Perbaikan;
use App\Models\User;
use App\Models\user_activitie;
use App\Services\RepairService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PerbaikanController extends Controller
{
    public function __construct(private RepairService $repairService) {}

    public function index()
    {
        $this->repairService->syncRepairState();

        $perbaikans = Perbaikan::with(['user', 'ticket'])
            ->latest()
            ->paginate(10);

        return view('admin.perbaikan.index', [
            'menu' => 'perbaikan',
            'perbaikans' => $perbaikans,
        ]);
    }

    public function create()
    {
        $users = User::orderBy('name')->get();

        return view('admin.perbaikan.create', [
            'menu' => 'perbaikan',
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'user_id'            => ['required', 'exists:users,id'],
            'item_name'          => ['required', 'string', 'max:255'],
            'item_location'      => ['nullable', 'string', 'max:255'],
            'damage_description' => ['required', 'string'],
            'priority'           => ['required', 'in:normal,high,urgent'],
            'estimated_finish'   => ['nullable', 'date'],
            'is_public'          => ['required', 'in:0,1'],
            'note'               => ['nullable', 'string', 'max:1000'],
        ])->validate();

        $this->repairService->createRepair([
            'user_id'            => (int) $validated['user_id'],
            'item_name'          => $validated['item_name'],
            'item_location'      => $validated['item_location'] ?? null,
            'damage_description' => $validated['damage_description'],
            'priority'           => $validated['priority'],
            'estimated_finish'   => $validated['estimated_finish'] ?? null,
            'is_public'          => (int) $validated['is_public'],
            'note'               => $validated['note'] ?? null,
            'changed_by'         => Auth::id(),
        ]);

        user_activitie::create([
            'user_id' => Auth::id(),
            'module' => 'Perbaikan',
            'activity' => 'create',
            'description' => 'menambahkan data perbaikan baru.',
            'old_data' => null,
            'new_data' => null,
        ]);

        return redirect()
            ->route('perbaikan.index')
            ->with('success', 'Repair ticket berhasil dibuat.');
    }

    public function show(Perbaikan $perbaikan)
    {
        $this->repairService->syncRepairState();

        $perbaikan->load([
            'user',
            'ticket.statusLogs',
            'ticket.comments.user',
        ]);

        return view('admin.perbaikan.show', [
            'menu' => 'perbaikan',
            'perbaikan' => $perbaikan,
        ]);
    }

    public function edit(Perbaikan $perbaikan)
    {
        $this->repairService->syncRepairState();

        $perbaikan->load(['user', 'ticket']);
        $users = User::orderBy('name')->get();

        return view('admin.perbaikan.edit', [
            'menu' => 'perbaikan',
            'perbaikan' => $perbaikan,
            'users' => $users,
        ]);
    }

    public function update(Request $request, Perbaikan $perbaikan)
    {
        $validated = Validator::make($request->all(), [
            'user_id'            => ['required', 'exists:users,id'],
            'item_name'          => ['required', 'string', 'max:255'],
            'item_location'      => ['nullable', 'string', 'max:255'],
            'damage_description' => ['required', 'string'],
            'repair_action'      => ['nullable', 'string'],
            'repair_result'      => ['nullable', 'in:success,failed,unrepairable'],
            'status'             => ['required', 'in:waiting,diagnosis,processing,testing,completed,failed,cancelled'],
            'priority'           => ['required', 'in:normal,high,urgent'],
            'estimated_finish'   => ['nullable', 'date'],
            'is_public'          => ['required', 'in:0,1'],
            'note'               => ['nullable', 'string', 'max:1000'],
        ])->validate();

        $this->repairService->updateRepair($perbaikan, [
            'user_id'            => (int) $validated['user_id'],
            'item_name'          => $validated['item_name'],
            'item_location'      => $validated['item_location'] ?? null,
            'damage_description' => $validated['damage_description'],
            'repair_action'      => $validated['repair_action'] ?? null,
            'repair_result'      => $validated['repair_result'] ?? null,
            'status'             => $validated['status'],
            'priority'           => $validated['priority'],
            'estimated_finish'   => $validated['estimated_finish'] ?? null,
            'is_public'          => (int) $validated['is_public'],
            'note'               => $validated['note'] ?? null,
            'changed_by'         => Auth::id(),
        ]);

        user_activitie::create([
            'user_id' => Auth::id(),
            'module' => 'Perbaikan',
            'activity' => 'update',
            'description' => 'mengupdate data perbaikan.',
            'old_data' => null,
            'new_data' => null,
        ]);

        return redirect()
            ->route('perbaikan.index')
            ->with('edit', 'Repair updated successfully.');
    }

    public function destroy(Perbaikan $perbaikan)
    {
        $this->repairService->deleteRepair($perbaikan, Auth::id());

        user_activitie::create([
            'user_id' => Auth::id(),
            'module' => 'Perbaikan',
            'activity' => 'delete',
            'description' => 'menghapus data perbaikan.',
            'old_data' => null,
            'new_data' => null,
        ]);

        return redirect()
            ->route('perbaikan.index')
            ->with('success', 'Repair moved to recycle bin.');
    }

    public function trash()
    {
        $perbaikans = Perbaikan::onlyTrashed()
            ->with(['ticket', 'user'])
            ->latest()
            ->get();

        return view('admin.perbaikan.trash', [
            'menu' => 'perbaikan',
            'perbaikans' => $perbaikans,
        ]);
    }

    public function restore($id)
    {
        $perbaikan = Perbaikan::onlyTrashed()->findOrFail($id);

        $this->repairService->restoreRepair($perbaikan, Auth::id());

        user_activitie::create([
            'user_id' => Auth::id(),
            'module' => 'Perbaikan',
            'activity' => 'restore',
            'description' => 'mengembalikan data perbaikan.',
            'old_data' => null,
            'new_data' => null,
        ]);

        return back()->with('success', 'Repair restored successfully.');
    }

    public function restoreAll()
    {
        $trashItems = Perbaikan::onlyTrashed()->get();

        foreach ($trashItems as $perbaikan) {
            $this->repairService->restoreRepair($perbaikan, Auth::id());
        }

        user_activitie::create([
            'user_id' => Auth::id(),
            'module' => 'Perbaikan',
            'activity' => 'restore all',
            'description' => 'mengembalikan semua data perbaikan.',
            'old_data' => null,
            'new_data' => null,
        ]);

        return back()->with('success', 'All repairs restored successfully.');
    }

    public function complete(Perbaikan $perbaikan)
    {
        if (! $perbaikan->ticket) {
            return back()->withErrors(['ticket' => 'Ticket tidak ditemukan.']);
        }

        if (in_array($perbaikan->ticket->status, ['completed', 'failed', 'cancelled'])) {
            return back()->withErrors(['ticket' => 'Ticket sudah selesai.']);
        }

        $this->repairService->finishTicket($perbaikan->ticket, 'completed', Auth::id());

        user_activitie::create([
            'user_id' => Auth::id(),
            'module' => 'Perbaikan',
            'activity' => 'complete force ticket',
            'description' => 'menyelesaikan secara paksa status data perbaikan.',
            'old_data' => null,
            'new_data' => null,
        ]);

        return back()->with('success', 'Ticket berhasil diselesaikan.');
    }

    public function failed(Perbaikan $perbaikan)
    {
        if (! $perbaikan->ticket) {
            return back()->withErrors(['ticket' => 'Ticket tidak ditemukan.']);
        }

        if (in_array($perbaikan->ticket->status, ['completed', 'failed', 'cancelled'])) {
            return back()->withErrors(['ticket' => 'Ticket sudah selesai.']);
        }

        $this->repairService->finishTicket($perbaikan->ticket, 'failed', Auth::id());

        user_activitie::create([
            'user_id' => Auth::id(),
            'module' => 'Perbaikan',
            'activity' => 'failed force ticket',
            'description' => 'menandai data perbaikan sebagai gagal secara paksa.',
            'old_data' => null,
            'new_data' => null,
        ]);

        return back()->with('success', 'Ticket berhasil ditandai gagal.');
    }
}
