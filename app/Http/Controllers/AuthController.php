<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'identity' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $identity = trim($request->identity);

        $user = User::with('role')
            ->where(function ($query) use ($identity) {
                $query->where('nim', $identity)
                    ->orWhere('nip', $identity)
                    ->orWhere('username', $identity);
            })
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'identity' => 'NIP/NIM yang Anda masukkan salah.',
            ]);
        }

        if (
            in_array($user->role?->name, ['Mahasiswa', 'Dosen'], true) &&
            ! $user->role?->is_active
        ) {
            throw ValidationException::withMessages([
                'identity' => 'Maaf sistem dalam maintenance, silahkan cobalagi nanti.',
            ]);
        }

        if (! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'password' => 'Password anda salah.',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $user->update([
            'last_login_at' => now(),
        ]);

        return match ($user->role?->name) {
            'Admin' => redirect()->route('dashboard.admin'),
            'Mahasiswa' => redirect()->route('dashboard.mhs'),
            'Dosen' => redirect()->route('#'),
            default => abort(403),
        };
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
