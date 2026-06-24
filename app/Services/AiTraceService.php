<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Throwable;

class AiTraceService
{
    public function recordInteraction(
        $user,
        string $question,
        string $answer,
        ?string $action,
        string $source
    ): int {
        return (int) DB::table('ai_logs')->insertGetId([
            'user_id' => data_get($user, 'id'),
            'role' => $this->resolveRoleName($user),
            'question' => $question,
            'answer' => $answer,
            'action' => $action,
            'source' => $source,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function recordAction(?int $aiLogId, string $actionType, array $actionData, string $result): void
    {
        DB::table('ai_action_logs')->insert([
            'ai_log_id' => $aiLogId,
            'action_type' => $actionType,
            'action_data' => json_encode($actionData, JSON_UNESCAPED_UNICODE),
            'result' => $result,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function recordRecommendation(
        ?int $ticketId,
        string $recommendation,
        string $reason,
        string $status = 'pending'
    ): ?int {
        $ticketId = $ticketId ? (int) $ticketId : null;
        $recommendation = trim($recommendation);
        $reason = trim($reason);

        if ($recommendation === '' || $reason === '') {
            return null;
        }

        if ($ticketId !== null) {
            $alreadyExists = DB::table('ai_recommendations')
                ->where('ticket_id', $ticketId)
                ->where('recommendation', $recommendation)
                ->whereDate('created_at', now()->toDateString())
                ->exists();

            if ($alreadyExists) {
                return null;
            }
        }

        return (int) DB::table('ai_recommendations')->insertGetId([
            'ticket_id' => $ticketId,
            'recommendation' => $recommendation,
            'reason' => $reason,
            'status' => $status,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function syncTicketRecommendations(
        string $entity,
        string $operation,
        array $execution,
        array $plan,
        string $question,
        string $source = 'database'
    ): array {
        $entity = mb_strtolower(trim($entity));
        $operation = mb_strtolower(trim($operation));

        $rows = $this->extractRows($execution);
        $created = [];

        foreach ($this->extractTicketContexts($entity, $operation, $execution, $plan, $rows) as $context) {
            $ticketId = $context['ticket_id'] ?? null;
            $status = $context['status'] ?? null;
            $recommendation = $context['recommendation'] ?? null;
            $reason = $context['reason'] ?? null;

            if ($ticketId === null || $recommendation === null || $reason === null) {
                continue;
            }

            $id = $this->recordRecommendation($ticketId, $recommendation, $reason, $context['status_value'] ?? 'pending');

            if ($id !== null) {
                $created[] = [
                    'id' => $id,
                    'ticket_id' => $ticketId,
                    'status' => $status,
                    'recommendation' => $recommendation,
                ];
            }
        }

        return $created;
    }

    protected function extractRows(array $execution): array
    {
        $rows = data_get($execution, 'rows', []);

        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_map(function ($row) {
            if (is_object($row)) {
                return (array) $row;
            }

            if (is_array($row)) {
                return $row;
            }

            return [];
        }, $rows));
    }

    protected function extractTicketContexts(
        string $entity,
        string $operation,
        array $execution,
        array $plan,
        array $rows
    ): array {
        $contexts = [];

        if ($entity === 'tickets') {
            if ($operation === 'read') {
                foreach ($rows as $row) {
                    $ticketId = (int) (data_get($row, 'id') ?? 0);
                    if ($ticketId <= 0) {
                        continue;
                    }

                    $status = mb_strtolower((string) (data_get($row, 'status') ?? ''));
                    $context = $this->ticketStatusRecommendation($ticketId, $status, (array) $row, $operation);
                    if ($context !== null) {
                        $contexts[] = $context;
                    }
                }
            } else {
                $ticketId = $this->extractTicketIdFromExecution($execution, $plan);
                if ($ticketId !== null) {
                    $contexts[] = $this->ticketCrudRecommendation($ticketId, $operation, $execution, $plan);
                }
            }

            return $contexts;
        }

        $ticketIds = $this->collectTicketIdsFromRows($rows);
        if (empty($ticketIds)) {
            $ticketIds = $this->collectTicketIdsFromPlan($plan);
        }

        if (empty($ticketIds)) {
            return [];
        }

        foreach ($ticketIds as $ticketId) {
            $contexts[] = $this->linkedTicketRecommendation(
                $ticketId,
                $entity,
                $operation,
                $execution,
                $plan
            );
        }

        return $contexts;
    }

    protected function ticketStatusRecommendation(int $ticketId, string $status, array $row, string $operation): ?array
    {
        $ticketNumber = (string) (data_get($row, 'ticket_number') ?? '#'.$ticketId);

        $map = [
            'waiting' => [
                'recommendation' => "Ticket {$ticketNumber} masih menunggu. Lanjutkan penjadwalan atau diagnosis.",
                'reason' => 'status waiting pada hasil baca ticket',
            ],
            'diagnosis' => [
                'recommendation' => "Ticket {$ticketNumber} sedang diagnosis. Siapkan data teknis sebelum lanjut.",
                'reason' => 'status diagnosis pada hasil baca ticket',
            ],
            'processing' => [
                'recommendation' => "Ticket {$ticketNumber} sedang diproses. Pantau progres pekerjaan.",
                'reason' => 'status processing pada hasil baca ticket',
            ],
            'testing' => [
                'recommendation' => "Ticket {$ticketNumber} sedang testing. Validasi hasil sebelum closing.",
                'reason' => 'status testing pada hasil baca ticket',
            ],
            'failed' => [
                'recommendation' => "Ticket {$ticketNumber} gagal. Cek penyebab utama dan ulangi analisis.",
                'reason' => 'status failed pada hasil baca ticket',
            ],
            'cancelled' => [
                'recommendation' => "Ticket {$ticketNumber} dibatalkan. Konfirmasi alasan pembatalan bila dibutuhkan.",
                'reason' => 'status cancelled pada hasil baca ticket',
            ],
            'completed' => [
                'recommendation' => "Ticket {$ticketNumber} sudah selesai. Arsipkan atau lanjutkan penutupan administrasi.",
                'reason' => 'status completed pada hasil baca ticket',
            ],
        ];

        if (! isset($map[$status])) {
            return null;
        }

        return [
            'ticket_id' => $ticketId,
            'status' => 'pending',
            'status_value' => 'pending',
            'recommendation' => $map[$status]['recommendation'],
            'reason' => $map[$status]['reason'],
        ];
    }

    protected function ticketCrudRecommendation(int $ticketId, string $operation, array $execution, array $plan): array
    {
        $ticketNumber = data_get($execution, 'message') ?: ('#' . $ticketId);

        $map = [
            'create' => [
                'recommendation' => "Ticket {$ticketNumber} berhasil dibuat. Pastikan detail user, jenis, dan jadwal sudah benar.",
                'reason' => 'aksi create ticket berhasil',
            ],
            'update' => [
                'recommendation' => "Ticket {$ticketNumber} berhasil diperbarui. Verifikasi ulang status terbaru.",
                'reason' => 'aksi update ticket berhasil',
            ],
            'delete' => [
                'recommendation' => "Ticket {$ticketNumber} dihapus. Jika ini soft delete, data masih bisa dipulihkan.",
                'reason' => 'aksi delete ticket berhasil',
            ],
            'restore' => [
                'recommendation' => "Ticket {$ticketNumber} dipulihkan. Pastikan tiket sudah aktif kembali.",
                'reason' => 'aksi restore ticket berhasil',
            ],
        ];

        $context = $map[$operation] ?? null;
        if ($context === null) {
            return [
                'ticket_id' => $ticketId,
                'status' => 'pending',
                'status_value' => 'pending',
                'recommendation' => null,
                'reason' => null,
            ];
        }

        return [
            'ticket_id' => $ticketId,
            'status' => 'pending',
            'status_value' => 'pending',
            'recommendation' => $context['recommendation'],
            'reason' => $context['reason'],
        ];
    }

    protected function linkedTicketRecommendation(
        int $ticketId,
        string $entity,
        string $operation,
        array $execution,
        array $plan
    ): array {
        $label = ucfirst(str_replace('_', ' ', $entity));

        return [
            'ticket_id' => $ticketId,
            'status' => 'pending',
            'status_value' => 'pending',
            'recommendation' => "Ada perubahan pada {$label} yang terhubung ke ticket #{$ticketId}. Cek ticket terkait bila perlu.",
            'reason' => "aksi {$operation} pada entitas {$entity} yang memiliki relasi ticket",
        ];
    }

    protected function extractTicketIdFromExecution(array $execution, array $plan): ?int
    {
        foreach ([
            data_get($execution, 'id'),
            data_get($execution, 'ticket_id'),
            data_get($plan, 'target.ticket_id'),
            data_get($plan, 'data.ticket_id'),
            data_get($plan, 'target.id'),
            data_get($plan, 'data.id'),
        ] as $value) {
            if (is_numeric($value) && (int) $value > 0) {
                return (int) $value;
            }
        }

        return null;
    }

    protected function collectTicketIdsFromRows(array $rows): array
    {
        $ids = [];

        foreach ($rows as $row) {
            $ticketId = data_get($row, 'ticket_id');
            if (is_numeric($ticketId) && (int) $ticketId > 0) {
                $ids[] = (int) $ticketId;
            }
        }

        return array_values(array_unique($ids));
    }

    protected function collectTicketIdsFromPlan(array $plan): array
    {
        $ids = [];

        foreach ([
            data_get($plan, 'target.ticket_id'),
            data_get($plan, 'data.ticket_id'),
            data_get($plan, 'target.id'),
            data_get($plan, 'data.id'),
        ] as $value) {
            if (is_numeric($value) && (int) $value > 0) {
                $ids[] = (int) $value;
            }
        }

        return array_values(array_unique($ids));
    }

    protected function resolveRoleName($user): string
    {
        $role = data_get($user, 'role.name')
            ?? data_get($user, 'role.status')
            ?? data_get($user, 'role')
            ?? 'admin';

        return mb_strtolower(trim((string) $role));
    }
}
