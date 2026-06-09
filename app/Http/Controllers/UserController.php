<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('role')->latest()->paginate(10);

        return view('admin.user.index', [
            'menu'  => 'user',
            'title' => 'User Management',
            'users' => $users,
        ]);
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();

        return view('admin.user.create', [
            'roles' => $roles,
        ]);
    }

    public function store(Request $request)
    {
        $messages = [
            'role_id.required'            => 'Please select a role.',
            'role_id.exists'              => 'Selected role is invalid.',
            'name.required'               => 'Please enter the user name.',
            'name.max'                    => 'Name may not be greater than :max characters.',
            'username.required'           => 'Please enter a username.',
            'username.max'                => 'Username may not be greater than :max characters.',
            'username.unique'             => 'This username is already taken.',
            'email.email'                 => 'Please enter a valid email address.',
            'email.max'                   => 'Email may not be greater than :max characters.',
            'email.unique'                => 'This email is already registered.',
            'nim.max'                     => 'NIM may not be greater than :max characters.',
            'nim.unique'                  => 'This NIM is already in use.',
            'nip.max'                     => 'NIP may not be greater than :max characters.',
            'nip.unique'                  => 'This NIP is already in use.',
            'no_hp.required'              => 'Please enter a phone number.',
            'no_hp.max'                   => 'Phone number may not be greater than :max characters.',
            'password.required'           => 'Please enter a password.',
            'password.min'                => 'Password must be at least :min characters long.',
            'password.confirmed'          => 'Password confirmation does not match.',
            'security_question.max'       => 'Security question may not be greater than :max characters.',
            'security_answer.max'         => 'Security answer may not be greater than :max characters.',
            'avatar.image'                => 'Avatar must be an image file.',
            'avatar.max'                  => 'Avatar must not be greater than :max kilobytes.',
        ];

        $validator = Validator::make($request->all(), [
            'role_id'              => ['required', 'exists:roles,id'],
            'name'                 => ['required', 'string', 'max:255'],
            'username'             => ['required', 'string', 'max:255', 'unique:users,username'],
            'email'                => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'nim'                  => ['nullable', 'string', 'max:255', 'unique:users,nim'],
            'nip'                  => ['nullable', 'string', 'max:255', 'unique:users,nip'],
            'no_hp'                => ['required', 'string', 'max:255'],
            'password'             => ['required', 'string', 'min:3', 'confirmed'],
            'security_question'    => ['nullable', 'string', 'max:255'],
            'security_answer'      => ['nullable', 'string', 'max:255'],
            'avatar'               => ['nullable', 'image', 'max:2048'],
        ], $messages);

        if ($validator->fails()) {
            return redirect()
                ->route('user.index')
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $data = [
            'role_id'                 => $validated['role_id'],
            'name'                    => $validated['name'],
            'username'                => $validated['username'],
            'email'                   => $validated['email'] ?? null,
            'nim'                     => $validated['nim'] ?? null,
            'nip'                     => $validated['nip'] ?? null,
            'no_hp'                   => $validated['no_hp'],
            'password'                => Hash::make($validated['password']),
            'security_question'       => $validated['security_question'] ?? null,
            'security_answer'         => $validated['security_answer'] ?? null,
            'last_password_changed_at' => now(),
        ];

        if ($request->hasFile('avatar')) {
            $data['avatar'] = $request->file('avatar')->store('avatars/users', 'public');
        }

        User::create($data);

        return redirect()
            ->route('user.index')
            ->with('success', 'User created successfully.');
    }

    public function show(User $user)
    {
        $user->load('role');

        return view('admin.user.show', [
            'user' => $user,
        ]);
    }

    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();

        return view('admin.user.edit', [
            'user'  => $user,
            'roles' => $roles,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $messages = [
            'role_id.required'            => 'Please select a role.',
            'role_id.exists'              => 'Selected role is invalid.',
            'name.required'               => 'Please enter the user name.',
            'name.max'                    => 'Name may not be greater than :max characters.',
            'username.required'           => 'Please enter a username.',
            'username.max'                => 'Username may not be greater than :max characters.',
            'username.unique'             => 'This username is already taken.',
            'email.email'                 => 'Please enter a valid email address.',
            'email.max'                   => 'Email may not be greater than :max characters.',
            'email.unique'                => 'This email is already registered.',
            'nim.max'                     => 'NIM may not be greater than :max characters.',
            'nim.unique'                  => 'This NIM is already in use.',
            'nip.max'                     => 'NIP may not be greater than :max characters.',
            'nip.unique'                  => 'This NIP is already in use.',
            'no_hp.required'              => 'Please enter a phone number.',
            'no_hp.max'                   => 'Phone number may not be greater than :max characters.',
            'password.min'                => 'Password must be at least :min characters long.',
            'password.confirmed'          => 'Password confirmation does not match.',
            'security_question.max'       => 'Security question may not be greater than :max characters.',
            'security_answer.max'         => 'Security answer may not be greater than :max characters.',
            'avatar.image'                => 'Avatar must be an image file.',
            'avatar.max'                  => 'Avatar must not be greater than :max kilobytes.',
        ];

        $validated = $request->validate([
            'role_id'              => ['required', 'exists:roles,id'],
            'name'                 => ['required', 'string', 'max:255'],
            'username'             => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email'                => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'nim'                  => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'nim')->ignore($user->id),
            ],
            'nip'                  => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'nip')->ignore($user->id),
            ],
            'no_hp'                => ['required', 'string', 'max:255'],
            'password'             => ['nullable', 'string', 'min:3', 'confirmed'],
            'security_question'    => ['nullable', 'string', 'max:255'],
            'security_answer'      => ['nullable', 'string', 'max:255'],
            'avatar'               => ['nullable', 'image', 'max:2048'],
        ], $messages);

        $data = [
            'role_id'           => $validated['role_id'],
            'name'              => $validated['name'],
            'username'          => $validated['username'],
            'email'             => $validated['email'] ?? null,
            'nim'               => $validated['nim'] ?? null,
            'nip'               => $validated['nip'] ?? null,
            'no_hp'             => $validated['no_hp'],
            'security_question' => $validated['security_question'] ?? null,
            'security_answer'   => $validated['security_answer'] ?? null,
        ];

        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
            $data['last_password_changed_at'] = now();
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $data['avatar'] = $request->file('avatar')->store('avatars/users', 'public');
        }

        $user->update($data);

        return redirect()
            ->route('user.index')
            ->with('edit', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()
            ->route('user.index')
            ->with('success', 'User deleted successfully.');
    }

    public function trash()
    {
        $users = User::onlyTrashed()
            ->latest()
            ->get();

        return view(
            'admin.user.trash',
            compact('users')
        );
    }

    public function restore($id)
    {
        User::onlyTrashed()
            ->findOrFail($id)
            ->restore();

        return back()->with(
            'success',
            'User restored successfully.'
        );
    }

    public function restoreAll()
    {
        User::onlyTrashed()
            ->restore();

        return back()->with(
            'success',
            'All users restored successfully.'
        );
    }
    public function destroyAll()
    {
        User::query()->delete();

        return redirect()
            ->route('user.index')
            ->with(
                'success',
            'All Users deleted successfully.'
            );
    }
}
