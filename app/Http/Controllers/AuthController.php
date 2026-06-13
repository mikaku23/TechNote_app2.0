<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\login_log;
use App\Models\user_activitie;
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
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'accuracy_m' => ['nullable', 'numeric'],
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
                'identity' => 'Maaf sistem dalam maintenance, silahkan coba lagi nanti.',
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

        $anchorLat = (float) config('services.login_anchor.latitude');
        $anchorLng = (float) config('services.login_anchor.longitude');
        $radius = (float) config('services.login_anchor.radius', 50);

        $latitude = $request->filled('latitude') ? (float) $request->latitude : null;
        $longitude = $request->filled('longitude') ? (float) $request->longitude : null;
        $accuracyM = $request->filled('accuracy_m') ? (float) $request->accuracy_m : null;

        $distance = null;
        $locationStatus = 'unknown';

        if (!is_null($latitude) && !is_null($longitude) && !empty($anchorLat) && !empty($anchorLng)) {
            $distance = $this->haversineMeters($anchorLat, $anchorLng, $latitude, $longitude);
            $locationStatus = $distance <= $radius ? 'inside' : 'outside';
        }

        login_log::create([
            'user_id' => $user->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'status' => 'online',
            'login_at' => now(),
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy_m' => $accuracyM,
            'distance_from_anchor_m' => $distance,
            'location_status' => $locationStatus,
        ]);

        user_activitie::create([
            'user_id' => Auth::id(),
            'module' => 'Authentication',
            'activity' => 'login',
            'description' => 'melakukan login ke sistem.',
            'old_data' => null,
            'new_data' => null,
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
        $user = Auth::user();

        if ($user) {
            login_log::where('user_id', $user->id)
                ->where('status', 'online')
                ->latest('login_at')
                ->first()
                ?->update([
                    'status' => 'offline',
                    'logout_at' => now(),
                ]);

            user_activitie::create([
                'user_id' => Auth::id(),
                'module' => 'Authentication',
                'activity' => 'logout',
                'description' => 'melakukan logout dari sistem.',
                'old_data' => null,
                'new_data' => null,
            ]);
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function haversineMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;

        $latFrom = deg2rad($lat1);
        $lngFrom = deg2rad($lng1);
        $latTo = deg2rad($lat2);
        $lngTo = deg2rad($lng2);

        $latDelta = $latTo - $latFrom;
        $lngDelta = $lngTo - $lngFrom;

        $a = sin($latDelta / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($lngDelta / 2) ** 2;

        $c = 2 * asin(min(1, sqrt($a)));

        return $earthRadius * $c;
    }
}
