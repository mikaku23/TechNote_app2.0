<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::latest()->paginate(10);

        return view('admin.role.index', [
            'menu'  => 'role',
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
            'name.required'     => 'Please enter a role name.',
            'name.max'          => 'Role name may not be greater than :max characters.',
            'name.unique'       => 'This role name is already in use.',
            'description.string' => 'Description must be a valid text.',
            'is_active.boolean' => 'The active status must be true or false.',
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
            'is_active'   => true,
        ]);

        return redirect()
            ->route('role.index')
            ->with('success', 'Role created successfully.');
    }

    public function show(Role $role)
    {
        return view('admin.role.show', [
            'role' => $role,
        ]);
    }

    public function toggleStatus(Role $role)
    {
        $role->update([
            'is_active' => ! $role->is_active,
        ]);

        return back()->with(
            'success',
            $role->is_active
                ? 'Role activated successfully.'
                : 'Role deactivated successfully.'
        );
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return redirect()
            ->route('role.index')
            ->with('success', 'Role deleted successfully.');
    }

    public function trash()
    {
        $roles = Role::onlyTrashed()
            ->latest()
            ->get();

        return view('admin.role.trash', compact('roles'));
    }

    public function restore($id)
    {
        Role::onlyTrashed()
            ->findOrFail($id)
            ->restore();

        return back()->with('success', 'Role restored successfully.');
    }

    public function restoreAll()
    {
        Role::onlyTrashed()->restore();

        return back()->with('success', 'All roles restored successfully.');
    }
}
