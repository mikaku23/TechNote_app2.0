<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Throwable;

class AuditLoggerService
{
    public function logAi($user, string $question, string $answer, ?string $action, string $source, array $meta = []): int
    {
        try {
            $candidate = [
                'user_id' => data_get($user, 'id'),
                'role' => data_get($user, 'role.name') ?? data_get($user, 'role') ?? 'admin',
                'question' => $question,
                'answer' => $answer,
                'action' => $action,
                'source' => $source,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            foreach ($meta as $key => $value) {
                $candidate[$key] = is_scalar($value) || $value === null
                    ? $value
                    : json_encode($value, JSON_UNESCAPED_UNICODE);
            }

            $record = [];

            foreach ($candidate as $key => $value) {
                if (Schema::hasColumn('ai_logs', (string) $key)) {
                    $record[$key] = $value;
                }
            }

            if (empty($record)) {
                return 0;
            }

            return (int) DB::table('ai_logs')->insertGetId($record);
        } catch (Throwable $e) {
            logger()->warning('AI log insert failed', [
                'message' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    public function logAction(int $aiLogId, string $actionType, array $payload, string $result = 'success'): void
    {
        try {
            $actionRecord = [
                'ai_log_id' => $aiLogId > 0 ? $aiLogId : null,
                'action_type' => $actionType,
                'action_data' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'result' => $result,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (Schema::hasTable('ai_action_logs')) {
                $data = [];
                foreach ($actionRecord as $key => $value) {
                    if (Schema::hasColumn('ai_action_logs', (string) $key)) {
                        $data[$key] = $value;
                    }
                }
                if (! empty($data)) {
                    DB::table('ai_action_logs')->insert($data);
                }
            }

            if (Schema::hasTable('user_activities')) {
                $legacy = [
                    'user_id' => data_get($payload, 'user_id'),
                    'module' => 'ai',
                    'activity' => $actionType,
                    'description' => json_encode([
                        'ai_log_id' => $aiLogId,
                        'result' => $result,
                        'payload' => $payload,
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $data = [];
                foreach ($legacy as $key => $value) {
                    if (Schema::hasColumn('user_activities', (string) $key)) {
                        $data[$key] = $value;
                    }
                }

                if (! empty($data)) {
                    DB::table('user_activities')->insert($data);
                }
            }
        } catch (Throwable $e) {
            logger()->warning('AI action log failed', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function pushHistory($user, string $input, string $output): void
    {
        $historyKey = 'chat_history_' . data_get($user, 'id');
        $history = Session::get($historyKey, []);

        $history[] = [
            'in' => $input,
            'out' => $output,
            'at' => now()->toDateTimeString(),
        ];

        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }

        Session::put($historyKey, $history);
    }
}
