<?php

namespace App\Http\Controllers;

use App\Models\Software;
use Illuminate\Http\Request;

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
        $request->validate([
            'name'        => 'required|string|max:255',
            'developer'   => 'nullable|string|max:255',
            'version'     => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        Software::create([
            'name'        => $request->name,
            'developer'   => $request->developer,
            'version'     => $request->version,
            'description' => $request->description,
        ]);

        return redirect()
            ->route('software.index')
            ->with(
                'success',
                'Software created successfully.'
            );
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
        $request->validate([
            'name'        => 'required|string|max:255',
            'developer'   => 'nullable|string|max:255',
            'version'     => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        $software->update([
            'name'        => $request->name,
            'developer'   => $request->developer,
            'version'     => $request->version,
            'description' => $request->description,
        ]);

        return redirect()
            ->route('software.index')
            ->with(
                'success',
                'Software updated successfully.'
            );
    }

    public function destroy(Software $software)
    {
        $software->delete();

        return redirect()
            ->route('software.index')
            ->with(
                'success',
                'Software deleted successfully.'
            );
    }
}
