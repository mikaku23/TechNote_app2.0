<?php

namespace App\Http\Controllers;

use App\Services\LogicAIforUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserAiController extends Controller
{
    public function context(): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (! $this->isAllowedRole($user)) {
            return response()->json(['message' => 'Role not allowed.'], 403);
        }

        return response()->json([
            'ok' => true,
            'widget' => [
                'title' => $this->widgetTitle($user),
                'subtitle' => $this->widgetSubtitle($user),
                'user' => $this->userPayload($user),
                'trusted_websites' => $this->trustedWebsitesPreview(),
            ],
        ]);
    }

    public function chat(Request $request, LogicAIforUser $ai): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            return response()->json([
                'reply' => 'Sesi login tidak ditemukan. Silakan login ulang.',
                'action' => 'unauthenticated',
                'source' => 'system',
            ], 401);
        }

        if (! $this->isAllowedRole($user)) {
            return response()->json([
                'reply' => 'Fitur ini hanya tersedia untuk mahasiswa dan dosen.',
                'action' => 'forbidden',
                'source' => 'system',
            ], 403);
        }

        $data = $request->validate([
            'question' => ['required', 'string', 'min:1', 'max:1000'],
        ]);

        $result = $ai->handle($data['question'], $user);

        return response()->json($result);
    }

    protected function isAllowedRole($user): bool
    {
        return in_array($this->resolveRoleName($user), ['mahasiswa', 'dosen'], true);
    }

    protected function widgetTitle($user): string
    {
        return $this->resolveRoleName($user) === 'dosen' ? 'AI Dosen' : 'AI Mahasiswa';
    }

    protected function widgetSubtitle($user): string
    {
        return $this->resolveRoleName($user) === 'dosen'
            ? 'Bantuan untuk data akun sendiri dan website tepercaya.'
            : 'Bantuan untuk profil login sendiri dan website tepercaya.';
    }

    protected function userPayload($user): array
    {
        return [
            'id' => $user->id ?? null,
            'name' => $user->name ?? null,
            'username' => $user->username ?? null,
            'nim' => $user->nim ?? null,
            'nip' => $user->nip ?? null,
            'email' => $user->email ?? null,
            'no_hp' => $user->no_hp ?? null,
            'role_name' => $this->resolveRoleName($user),
            'last_login_at' => optional($user->last_login_at ?? null)?->format('Y-m-d H:i:s') ?? (string) ($user->last_login_at ?? null),
        ];
    }

    protected function resolveRoleName($user): string
    {
        $roleName = strtolower(trim((string) data_get($user, 'role_name', '')));
        if ($roleName !== '') {
            return $roleName;
        }

        $relationName = strtolower(trim((string) data_get($user, 'role.name', '')));
        if ($relationName !== '') {
            return $relationName;
        }

        if (isset($user->role_id)) {
            $role = DB::table('roles')->where('id', $user->role_id)->value('name');
            return strtolower(trim((string) $role));
        }

        return 'user';
    }

    protected function trustedWebsitesPreview(): array
    {
        if (! Schema::hasTable('trusted_websites')) {
            return [];
        }

        return DB::table('trusted_websites')
            ->where('is_active', 1)
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'url', 'description'])
            ->map(fn($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'url' => $row->url,
                'description' => $row->description,
            ])
            ->all();
    }
}
