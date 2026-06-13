<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

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
        if ($user->role && $user->role->name === 'Admin') {
            return back()->withErrors([
                'error' => 'Admin account cannot be deleted.'
            ]);
        }

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
        User::whereHas('role', function ($q) {
            $q->where('name', '!=', 'Admin');
        })->delete();

        return redirect()
            ->route('user.index')
            ->with('success', 'Non-admin users deleted successfully.');
    }

    public function updateProfile(Request $request)
    {
        $user = User::findOrFail(Auth::id());

        $messages = [
            'name.required'     => 'Please enter your name.',
            'name.max'          => 'Name may not be greater than :max characters.',
            'email.email'       => 'Please enter a valid email address.',
            'email.max'         => 'Email may not be greater than :max characters.',
            'email.unique'      => 'This email is already registered.',
            'no_hp.required'    => 'Please enter your phone number.',
            'no_hp.max'         => 'Phone number may not be greater than :max characters.',
            'avatar.image'      => 'Avatar must be an image file.',
            'avatar.max'        => 'Avatar must not be greater than :max kilobytes.',
        ];

        $validated = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'email'  => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'no_hp'  => ['required', 'string', 'max:255'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ], $messages);

        $data = [
            'name'   => $validated['name'],
            'email'  => $validated['email'] ?? null,
            'no_hp'  => $validated['no_hp'],
        ];

        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $data['avatar'] = $request->file('avatar')->store('avatars/users', 'public');
        }

        $user->update($data);

        return back()->with('edit', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $user = User::findOrFail(Auth::id());

        $messages = [
            'current_password.required' => 'Please enter your current password.',
            'password.required'        => 'Please enter a new password.',
            'password.min'             => 'Password must be at least :min characters long.',
            'password.confirmed'       => 'Password confirmation does not match.',
        ];

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password'         => ['required', 'string', 'min:3', 'confirmed'],
        ], $messages);

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()
                ->withErrors(['current_password' => 'Current password is incorrect.'])
                ->withInput();
        }

        $user->update([
            'password' => Hash::make($validated['password']),
            'last_password_changed_at' => now(),
        ]);

        return back()->with('edit', 'Password updated successfully.');
    }

    public function updateSecurityQuestion(Request $request)
    {
        $user = User::findOrFail(Auth::id());

        $messages = [
            'security_question.required' => 'Please enter a security question.',
            'security_question.max'      => 'Security question may not be greater than :max characters.',
            'security_answer.required'   => 'Please enter a security answer.',
            'security_answer.max'        => 'Security answer may not be greater than :max characters.',
        ];

        $validated = $request->validate([
            'security_question' => ['required', 'string', 'max:255'],
            'security_answer'   => ['required', 'string', 'max:255'],
        ], $messages);

        $user->update([
            'security_question' => $validated['security_question'],
            'security_answer'   => $validated['security_answer'],
        ]);

        return back()->with('edit', 'Security question updated successfully.');
    }
    public function destroyOwnAccount(Request $request)
    {
        $messages = [
            'password.required'          => 'Please enter your password.',
            'security_answer.required'   => 'Please answer the security question.',
            'confirmation_text.required' => 'Please type the confirmation text.',
        ];

        $validated = $request->validate([
            'password'          => ['required'],
            'security_answer'   => ['required'],
            'confirmation_text' => ['required'],
        ], $messages);

        $user = User::findOrFail(Auth::id());

        if ($user->role && $user->role->name === 'Admin') {
            return back()->withErrors([
                'account' => 'Admin account cannot be deleted.',
            ]);
        }

        /*
    |--------------------------------------------------------------------------
    | Check Password
    |--------------------------------------------------------------------------
    */
        if (!Hash::check(
            $validated['password'],
            $user->password
        )) {
            return back()->withErrors([
                'password' => 'Password is incorrect.',
            ]);
        }

        /*
    |--------------------------------------------------------------------------
    | Check Security Answer
    |--------------------------------------------------------------------------
    */
        if (
            strtolower(trim($validated['security_answer'])) !==
            strtolower(trim($user->security_answer))
        ) {
            return back()->withErrors([
                'security_answer' => 'Security answer is incorrect.',
            ]);
        }

        /*
    |--------------------------------------------------------------------------
    | Check Confirmation Text
    |--------------------------------------------------------------------------
    */
        if (
            trim($validated['confirmation_text'])
            !== 'DELETE MY ACCOUNT'
        ) {
            return back()->withErrors([
                'confirmation_text' =>
                'Please type DELETE MY ACCOUNT exactly.',
            ]);
        }

        /*
    |--------------------------------------------------------------------------
    | Delete Avatar
    |--------------------------------------------------------------------------
    */
        if (
            $user->avatar &&
            Storage::disk('public')->exists($user->avatar)
        ) {
            Storage::disk('public')->delete($user->avatar);
        }

        /*
    |--------------------------------------------------------------------------
    | Logout & Delete Account
    |--------------------------------------------------------------------------
    */
        Auth::logout();

        User::findOrFail($user->id)->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with(
                'success',
                'Your account has been deleted successfully.'
            );
    }
}
