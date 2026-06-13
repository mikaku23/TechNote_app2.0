<?php

namespace App\Http\Controllers;

use App\Models\Software;
use App\Models\user_activitie;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SoftwareController extends Controller
{
    public function index()
    {
        $softwares = Software::latest()->paginate(10);

        return view('admin.software.index', [
            'menu'      => 'software',
            'title'     => 'Data Software',
            'softwares' => $softwares,
        ]);
    }

    public function create()
    {
        return view('admin.software.create');
    }

    public function store(Request $request)
    {
        $messages = [
            'name.required'              => 'Please enter the software name.',
            'name.max'                   => 'Software name may not be greater than :max characters.',
            'developer.max'              => 'Developer name may not be greater than :max characters.',
            'version.max'                => 'Version may not be greater than :max characters.',
            'description.string'         => 'Description must be text.',
            'estimated_minutes.numeric'  => 'Estimated minutes must be a number.',
            'estimated_minutes.min'      => 'Estimated minutes must be at least 1.',
        ];

        $validator = Validator::make($request->all(), [
            'name'               => 'required|string|max:255',
            'developer'          => 'nullable|string|max:255',
            'version'            => 'nullable|string|max:255',
            'description'        => 'nullable|string',
            'estimated_minutes'  => 'nullable|numeric|min:1',
        ], $messages);

        if ($validator->fails()) {
            return redirect()
                ->route('software.index')
                ->withErrors($validator)
                ->withInput();
        }

        Software::create([
            'name'               => $request->name,
            'developer'          => $request->developer,
            'version'            => $request->version,
            'description'        => $request->description,
            'estimated_minutes'  => $request->estimated_minutes ?? 30,
        ]);

        user_activitie::create([
            'user_id' => Auth::id(),
            'module' => 'Software',
            'activity' => 'create',
            'description' => 'menambahkan data software baru.',
            'old_data' => null,
            'new_data' => null,
        ]);

        return redirect()
            ->route('software.index')
            ->with('success', 'Software created successfully.');
    }

    public function show(Software $software)
    {
        return view('admin.software.show', [
            'menu'     => 'software',
            'title'    => 'Detail Software',
            'software' => $software,
        ]);
    }

    public function edit(Software $software)
    {
        return view('admin.software.edit', [
            'software' => $software,
        ]);
    }

    public function update(Request $request, Software $software)
    {
        $messages = [
            'name.required'              => 'Please enter the software name.',
            'name.max'                   => 'Software name may not be greater than :max characters.',
            'developer.max'              => 'Developer name may not be greater than :max characters.',
            'version.max'                => 'Version may not be greater than :max characters.',
            'description.string'         => 'Description must be text.',
            'estimated_minutes.numeric'  => 'Estimated minutes must be a number.',
            'estimated_minutes.min'      => 'Estimated minutes must be at least 1.',
        ];

        $validated = $request->validate([
            'name'               => 'required|string|max:255',
            'developer'          => 'nullable|string|max:255',
            'version'            => 'nullable|string|max:255',
            'description'        => 'nullable|string',
            'estimated_minutes'  => 'nullable|numeric|min:1',
        ], $messages);

        $software->update([
            'name'               => $validated['name'],
            'developer'          => $validated['developer'],
            'version'            => $validated['version'],
            'description'        => $validated['description'],
            'estimated_minutes'  => $validated['estimated_minutes'] ?? $software->estimated_minutes,
        ]);

        user_activitie::create([
            'user_id' => Auth::id(),
            'module' => 'Software',
            'activity' => 'update',
            'description' => 'mengupdate data software.',
            'old_data' => null,
            'new_data' => null,
        ]);

        return redirect()
            ->route('software.index')
            ->with('edit', 'Software updated successfully.');
    }

    public function destroy(Software $software)
    {
        $software->delete();

        user_activitie::create([
            'user_id' => Auth::id(),
            'module' => 'Software',
            'activity' => 'delete',
            'description' => 'menghapus data software.',
            'old_data' => null,
            'new_data' => null,
        ]);

        return redirect()
            ->route('software.index')
            ->with(
                'success',
                'Software deleted successfully.'
            );
    }
    public function trash()
    {
        $softwares = Software::onlyTrashed()
            ->latest()
            ->get();

        return view(
            'admin.software.trash',
            compact('softwares')
        );
    }
    public function restore($id)
    {
        Software::onlyTrashed()
            ->findOrFail($id)
            ->restore();

        user_activitie::create([
            'user_id' => Auth::id(),
            'module' => 'Software',
            'activity' => 'restore',
            'description' => 'mengembalikan data software yang dihapus.',
            'old_data' => null,
            'new_data' => null,
        ]);

        return back()->with(
            'success',
            'Software restored successfully.'
        );
    }
    public function restoreAll()
    {
        Software::onlyTrashed()
            ->restore();

        user_activitie::create([
            'user_id' => Auth::id(),
            'module' => 'Software',
            'activity' => 'restore all',
            'description' => 'mengembalikan semua data software yang dihapus.',
            'old_data' => null,
            'new_data' => null,
        ]);

        return back()->with(
            'success',
            'All software restored successfully.'
        );
    }
    public function destroyAll()
    {
        Software::query()->delete();

        user_activitie::create([
            'user_id' => Auth::id(),
            'module' => 'Software',
            'activity' => 'delete all',
            'description' => 'menghapus semua data software.',
            'old_data' => null,
            'new_data' => null,
        ]);

        return redirect()
            ->route('software.index')
            ->with(
                'success',
                'All Softwares deleted successfully.'
            );
    }
}
