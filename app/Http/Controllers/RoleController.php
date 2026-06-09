<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::latest()->paginate(10);

        return view('admin.role.index', [
            'menu' => 'role',
            'title' => 'Role Management',
            'roles' => $roles,
        ]);
    }

    public function create()
    {
        return view('admin.role.create');
    }

    public function store(Request $request)
    {
        $messages = [
            'name.required'        => 'Please enter a role name.',
            'name.max'             => 'Role name may not be greater than :max characters.',
            'name.unique'          => 'This role name is already in use.',
            'description.nullable' => 'Description is optional.',
        ];

        $validator = Validator::make($request->all(), [
            'name'        => 'required|max:255|unique:roles,name',
            'description' => 'nullable|string',
        ], $messages);

        if ($validator->fails()) {
            return redirect()
                ->route('role.index')
                ->withErrors($validator)
                ->withInput();
        }

        Role::create([
            'name'        => $request->name,
            'description' => $request->description,
        ]);

        return redirect()
            ->route('role.index')
            ->with('success', 'Role created successfully.');
    }

    public function show(Role $role)
    {
        return view('admin.role.show', [
            'role' => $role
        ]);
    }

    public function edit(Role $role)
    {
        return view('admin.role.edit', [
            'role' => $role
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $messages = [
            'name.required'        => 'Please enter a role name.',
            'name.max'             => 'Role name may not be greater than :max characters.',
            'name.unique'          => 'This role name is already in use.',
            'description.nullable' => 'Description is optional.',
        ];

        $validated = $request->validate([
            'name'        => ['required', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'description' => 'nullable|string',
        ], $messages);

        $role->update([
            'name'        => $validated['name'],
            'description' => $validated['description'],
        ]);

        return redirect()
            ->route('role.index')
            ->with('edit', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return redirect()
            ->route('role.index')
            ->with('success', 'Role deleted successfully.');
    }
}
