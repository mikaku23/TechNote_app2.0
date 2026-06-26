<?php

namespace App\Services;

use Illuminate\Support\Facades\Schema;

class CrudPlanService
{
    public function __construct(protected AdminActionService $actionService)
    {
    }

    public function build(string $question, ?string $entity = null, ?string $operation = null, array $context = []): array
    {
        $entity = $entity ? mb_strtolower(trim($entity)) : null;
        $operation = $operation ? mb_strtolower(trim($operation)) : null;

        $data = [];
        $target = [];
        $filters = [];
        $notes = [];
        $items = [];
        $mode = 'single';

        $pairs = $this->extractKeyValuePairs($question);
        if (! empty($pairs)) {
            foreach ($pairs as $key => $value) {
                if ($this->isTargetKey($key, $entity, $operation)) {
                    $target[$key] = $value;
                } else {
                    $data[$key] = $value;
                }
            }
        }

        if (in_array($operation, ['create', 'update', 'delete', 'restore', 'read'], true)) {
            $items = $this->extractBulkItems($question, $entity, $operation);

            if (! empty($items)) {
                $mode = 'bulk';
                $notes[] = 'bulk_payload_detected';

                $first = $items[0] ?? [];

                if ($operation === 'create') {
                    $data = array_merge($data, is_array($first) ? ($first['data'] ?? $first) : []);
                } elseif ($operation === 'update') {
                    $data = array_merge($data, is_array($first) ? ($first['data'] ?? $first) : []);
                    $target = array_merge($target, is_array($first) ? ($first['target'] ?? []) : []);
                } elseif (in_array($operation, ['delete', 'restore', 'read'], true)) {
                    $target = array_merge($target, is_array($first) ? ($first['target'] ?? []) : []);
                }
            }
        }

        if ($operation !== 'create') {
            $target = array_merge($target, $this->extractTargetHints($question, $entity, $operation));
        }
        $data = array_merge($data, $this->extractNaturalDataHints($question, $entity, $operation, $target, $notes));

        if (preg_match('/\b(hari ini|today)\b/u', mb_strtolower($question))) {
            $filters['today'] = true;
        }

        if (preg_match('/\b(semua|seluruh|all|list|daftar)\b/u', mb_strtolower($question))) {
            $filters['list_all'] = true;
        }

        if ($operation === 'read' && preg_match('/\b(soft\s*delete|soft-delete|recycle|recycle\s+bin|trash|trash\s+bin|sampah|deleted\s+data|data\s+terhapus|data\s+yang\s+dihapus)\b/u', mb_strtolower($question))) {
            $filters['only_deleted'] = true;
            $filters['include_deleted'] = true;
            $filters['list_all'] = true;
            $notes[] = 'soft_deleted_scope';
        }

        $idList = $this->extractIdListTargets($question);
        if (! empty($idList)) {
            if (count($idList) > 1 && in_array($operation, ['delete', 'restore', 'read'], true)) {
                $items = array_map(fn ($id) => ['target' => ['id' => $id]], $idList);
                $mode = 'bulk';
                $notes[] = 'id_list_detected';
                $target = [];
            } elseif (count($idList) === 1 && ! isset($target['id'])) {
                $target['id'] = $idList[0];
            }
        } elseif (preg_match('/\b(id|nomor)\s*[:=]?\s*(\d+)\b/u', mb_strtolower($question), $m)) {
            $target['id'] = (int) $m[2];
        }

        $data = $this->normalizeData($data);
        $target = $this->normalizeTarget($target);
        $filters = $this->normalizeFilters($filters);

        if (($operation ?? '') === 'create') {
            unset($target['id'], $data['id']);
        }

        if (($operation ?? '') === 'update' && empty($data)) {
            $notes[] = 'update_masih_kurang_field';
        }

        if (($operation ?? '') === 'delete' && empty($target)) {
            $notes[] = 'delete_target_missing';
        }

        if (($operation ?? '') === 'read' && empty($target) && empty($filters)) {
            $filters['limit'] = 10;
        }

        return [
            'entity' => $entity,
            'operation' => $operation,
            'mode' => $mode,
            'items' => $items,
            'data' => $data,
            'target' => $target,
            'filters' => $filters,
            'notes' => array_values(array_unique($notes)),
            'confidence' => $this->confidenceScore($question, $entity, $operation, $data, $target, $filters),
        ];
    }


    public function isMassDelete(array $plan, string $question): bool
    {
        if (($plan['operation'] ?? '') !== 'delete') {
            return false;
        }

        $m = mb_strtolower($question);

        return (bool) preg_match('/\b(semua|seluruh|all|mass|massal|hapus\s+semua|delete\s+all)\b/u', $m)
            && empty($plan['target']);
    }

    protected function extractKeyValuePairs(string $question): array
    {
        $text = trim($question);
        $out = [];
        $allowed = array_flip(array_merge(
            ['id', 'name', 'username', 'email', 'nim', 'nip', 'no_hp', 'password', 'role_id', 'ticket_id', 'user_id', 'software_id', 'task_name', 'instruction', 'status', 'type', 'priority', 'developer', 'version', 'description', 'estimated_minutes', 'message', 'title', 'comment', 'url', 'key', 'value', 'sync_status', 'old_status', 'new_status', 'note', 'repair_result', 'installation_result', 'is_read', 'is_active', 'is_public', 'is_internal'],
            array_keys((new AdminActionService())->entityAliases())
        ));

        if (preg_match_all('/(?:\'(?<key>[a-zA-Z_][a-zA-Z0-9_]*)\'\s*(?:=>|:|=)\s*)\'(?<value>[^\']*)\'/u', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $key = mb_strtolower(trim($match['key']));
                if (! isset($allowed[$key])) {
                    continue;
                }
                $out[$key] = trim((string) $match['value']);
            }
        }

        if (preg_match_all('/\b(?<key>[a-zA-Z_][a-zA-Z0-9_]*)\s*[:=]\s*(?<value>[^\n,;]+)/u', $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $key = mb_strtolower(trim($match['key']));
                if (! isset($allowed[$key])) {
                    continue;
                }
                $value = trim((string) $match['value'], " \t\n\r\0\x0B\"'");
                if ($value !== '') {
                    $out[$key] = $value;
                }
            }
        }

        return $out;
    }

    protected function extractIdListTargets(string $question): array
    {
        if (! preg_match_all('/\b(?:id|nomor)\b\s*[:=]?\s*(\d+)/iu', $question, $matches)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $matches[1])));
    }


    protected function extractBulkItems(string $question, ?string $entity = null, ?string $operation = null): array
    {
        $text = trim($question);

        if ($operation === 'create') {
            $blocks = $this->extractCreateBlocks($text);
            if (! empty($blocks)) {
                $items = [];
                foreach ($blocks as $block) {
                    foreach ($this->parseCreateBlockItems($block, $entity, $operation) as $item) {
                        if (! empty($item)) {
                            $items[] = $item;
                        }
                    }
                }

                return $items;
            }
        }

        $start = strpos($text, '[');

        if ($start === false) {
            return [];
        }

        $outer = $this->extractBracketedSection($text, $start);
        if ($outer === null) {
            return [];
        }

        return $this->parseBracketPayloadItems($outer, $entity, $operation);
    }

    protected function extractCreateBlocks(string $text): array
    {
        $blocks = [];
        $offset = 0;

        while (preg_match('/(?:\b[A-Za-z_][A-Za-z0-9_\\\\]*\s*(?:::|->)\s*)?create\s*\(/i', $text, $match, PREG_OFFSET_CAPTURE, $offset)) {
            $matchStart = $match[0][1];
            $matchLength = strlen($match[0][0]);
            $searchStart = $matchStart + $matchLength;

            $open = strpos($text, '[', $searchStart);
            if ($open === false) {
                $offset = $searchStart;
                continue;
            }

            $body = $this->extractBracketedSection($text, $open);
            if ($body !== null) {
                $blocks[] = $body;
            }

            $offset = $open + 1;
        }

        return $blocks;
    }

    protected function parseBracketPayloadItems(string $body, ?string $entity = null, ?string $operation = null): array
    {
        $segments = $this->splitTopLevelArraySegments($body);
        $items = [];

        if ($operation === 'create' && $this->looksLikeSingleAssocArray($body, $segments)) {
            $assoc = $this->extractPhpArrayPairs($body);
            if (! empty($assoc)) {
                $normalized = $this->normalizeBulkItemForOperation($assoc, $entity, $operation);
                if (! empty($normalized)) {
                    $items[] = $normalized;
                }
            }

            return $items;
        }

        foreach ($segments as $segment) {
            $parsed = $this->parseArrayItem($segment);

            if ($parsed === null) {
                continue;
            }

            if (is_array($parsed)) {
                $normalized = $this->normalizeBulkItemForOperation($parsed, $entity, $operation);
                if (! empty($normalized)) {
                    $items[] = $normalized;
                }

                continue;
            }

            if (in_array($operation, ['delete', 'restore', 'read'], true)) {
                $target = $this->normalizeScalarBulkTarget($parsed, $entity);
                if (! empty($target)) {
                    $items[] = ['target' => $target];
                }
            }
        }

        return $items;
    }

    protected function parseCreateBlockItems(string $body, ?string $entity = null, ?string $operation = null): array
    {
        $body = trim($body);

        if ($body === '') {
            return [];
        }

        return $this->parseBracketPayloadItems($body, $entity, $operation);
    }

    protected function looksLikeSingleAssocArray(string $body, array $segments): bool
    {
        if (! str_contains($body, '=>')) {
            return false;
        }

        foreach ($segments as $segment) {
            if (preg_match('/^\s*\[/', trim($segment))) {
                return false;
            }
        }

        return true;
    }

    protected function extractBracketedSection(string $text, int $startPos): ?string
    {
        $depth = 0;
        $inString = false;
        $quote = null;
        $len = strlen($text);

        for ($i = $startPos; $i < $len; $i++) {
            $ch = $text[$i];
            $prev = $i > 0 ? $text[$i - 1] : '';

            if ($inString) {
                if ($ch === $quote && $prev !== '\\') {
                    $inString = false;
                    $quote = null;
                }
                continue;
            }

            if ($ch === "'" || $ch === '"') {
                $inString = true;
                $quote = $ch;
                continue;
            }

            if ($ch === '[') {
                $depth++;
                continue;
            }

            if ($ch === ']') {
                $depth--;
                if ($depth === 0) {
                    return substr($text, $startPos + 1, $i - $startPos - 1);
                }
            }
        }

        return null;
    }

    protected function splitTopLevelArraySegments(string $body): array
    {
        $segments = [];
        $depth = 0;
        $inString = false;
        $quote = null;
        $start = 0;
        $len = strlen($body);

        for ($i = 0; $i < $len; $i++) {
            $ch = $body[$i];
            $prev = $i > 0 ? $body[$i - 1] : '';

            if ($inString) {
                if ($ch === $quote && $prev !== '\\') {
                    $inString = false;
                    $quote = null;
                }
                continue;
            }

            if ($ch === "'" || $ch === '"') {
                $inString = true;
                $quote = $ch;
                continue;
            }

            if ($ch === '[') {
                $depth++;
                continue;
            }

            if ($ch === ']') {
                $depth--;
                continue;
            }

            if ($ch === ',' && $depth === 0) {
                $segment = trim(substr($body, $start, $i - $start));
                if ($segment !== '') {
                    $segments[] = $segment;
                }
                $start = $i + 1;
            }
        }

        $last = trim(substr($body, $start));
        if ($last !== '') {
            $segments[] = $last;
        }

        return $segments;
    }

    protected function parseArrayItem(string $segment): mixed
    {
        $segment = trim($segment);

        if ($segment === '') {
            return null;
        }

        // Terima item array dalam bentuk:
        // [ 'name' => '...', ... ]
        // maupun potongan teks array yang sudah dipisah di level teratas.
        if ($segment[0] === '[' && str_ends_with($segment, ']')) {
            $segment = trim(substr($segment, 1, -1));
        }

        $assoc = $this->extractPhpArrayPairs($segment);
        if (! empty($assoc)) {
            return $assoc;
        }

        return $this->parseScalarLiteral($segment);
    }

    protected function parseScalarLiteral(string $value): mixed
    {
        $value = trim($value, " \t\n\r\0\x0B");

        if ($value === '') {
            return null;
        }

        if ((str_starts_with($value, "'") && str_ends_with($value, "'")) || (str_starts_with($value, '"') && str_ends_with($value, '"'))) {
            $value = substr($value, 1, -1);
        }

        $low = mb_strtolower($value);

        if ($low === 'null') {
            return null;
        }

        if ($low === 'true') {
            return true;
        }

        if ($low === 'false') {
            return false;
        }

        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }

        return $value;
    }

    protected function normalizeBulkItemForOperation(array $item, ?string $entity = null, ?string $operation = null): array
    {
        if ($operation === 'create') {
            $data = $this->normalizeData($item);
            return $data;
        }

        if ($operation === 'update') {
            return $item;
        }

        if (in_array($operation, ['delete', 'restore', 'read'], true)) {
            if (isset($item['target']) && is_array($item['target'])) {
                $target = $this->normalizeTarget($item['target']);
            } else {
                $target = $this->normalizeTarget($item);

                if (empty($target) && isset($item['id']) && is_numeric($item['id'])) {
                    $target = ['id' => (int) $item['id']];
                }
            }

            return empty($target) ? [] : ['target' => $target];
        }

        return $item;
    }

    protected function normalizeScalarBulkTarget(mixed $value, ?string $entity = null): array
    {
        if (is_array($value)) {
            return $this->normalizeTarget($value);
        }

        if (is_numeric($value)) {
            return ['id' => (int) $value];
        }

        if (! is_string($value)) {
            return [];
        }

        $value = trim($value, " \t\n\r\0\x0B\"'");
        if ($value === '') {
            return [];
        }

        $fields = array_values(array_unique(array_merge(
            ['name', 'username', 'email', 'nim', 'nip', 'ticket_number', 'url', 'key', 'title', 'task_name', 'item_name', 'rekap_date'],
            $this->likelyTargetFields($entity)
        )));

        foreach ($fields as $field) {
            if ($field === 'id') {
                continue;
            }

            return [$field => $value];
        }

        return ['name' => $value];
    }

    protected function extractPhpArrayPairs(string $text): array
    {
        $out = [];

        if (preg_match_all("/[\"']?(?<key>[a-zA-Z_][a-zA-Z0-9_]*)[\"']?\s*=>\s*(?<value>'[^']*'|\"[^\"]*\"|\d+(?:\.\d+)?|true|false|null)/u", $text, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $key = mb_strtolower(trim($match['key']));
                $value = trim($match['value'], " \t\n\r\0\x0B\"'");

                if ($value === 'null') {
                    $out[$key] = null;
                } elseif (in_array(mb_strtolower($value), ['true', 'false'], true)) {
                    $out[$key] = mb_strtolower($value) === 'true';
                } elseif (is_numeric($value)) {
                    $out[$key] = str_contains($value, '.') ? (float) $value : (int) $value;
                } else {
                    $out[$key] = $value;
                }
            }
        }

        return $out;
    }

    protected function extractTargetHints(string $question, ?string $entity = null, ?string $operation = null): array
    {
        if ($operation === 'create') {
            return [];
        }

        $m = mb_strtolower($question);
        $out = [];

        foreach ([
            'id' => '/\b(?:id|data\s+ke|record\s+ke)\s*[:=]?\s*(\d+)\b/u',
            'username' => '/\busername\s*[:=]?\s*([^\n,;]+)/u',
            'email' => '/\bemail\s*[:=]?\s*([^\n,;]+)/u',
            'nim' => '/\bnim\s*[:=]?\s*([^\n,;]+)/u',
            'nip' => '/\bnip\s*[:=]?\s*([^\n,;]+)/u',
            'ticket_number' => '/\bticket\s*number\s*[:=]?\s*([^\n,;]+)/u',
            'name' => '/\b(?:nama|name)\s*[:=]?\s*([^\n,;]+?)(?=\s+(?:menjadi|jadi|ubah|ganti|update|hapus|delete|perbarui|set|to)\b|$|,|;)/u',
            'url' => '/\burl\s*[:=]?\s*([^\n,;]+)/u',
            'key' => '/\bkey\s*[:=]?\s*([^\n,;]+)/u',
        ] as $field => $pattern) {
            if (preg_match($pattern, $m, $match)) {
                $value = trim($match[1], " \t\n\r\0\x0B\"'");
                if ($value !== '') {
                    $out[$field] = is_numeric($value) ? (int) $value : $value;
                }
            }
        }

        if ($entity === 'users' && isset($out['name']) && ! isset($out['username'])) {
            if (preg_match('/\busername\s*([^\n,;]+)\b/u', $m, $match)) {
                $out['username'] = trim($match[1], " \t\n\r\0\x0B\"'");
            }
        }

        return $out;
    }

    protected function extractNaturalDataHints(string $question, ?string $entity, ?string $operation, array $target, array &$notes): array
    {
        $m = mb_strtolower($question);
        $data = [];

        if ($operation === 'update') {
            $bool = $this->detectBooleanFromMessage($m);
            $field = $this->detectUpdateField($m, $entity);

            if ($field && $bool !== null) {
                $data[$field] = $bool;
            }

            if ($entity === 'users' && array_key_exists('is_active', $data) === false) {
                if (preg_match('/\b(status|aktif|active|nonactive|nonaktif|deactivate|disable|enable)\b/u', $m)) {
                    $data['__special_user_role_status__'] = $this->detectBooleanFromMessage($m);
                }
            }

            if ($entity === 'tickets' && preg_match('/\b(status)\b/u', $m) && preg_match('/\b(waiting|diagnosis|processing|testing|completed|failed|cancelled)\b/u', $m, $match)) {
                $data['status'] = mb_strtolower($match[1]);
            }

            if ($entity === 'roles' && preg_match('/\b(status|aktif|active|nonactive|nonaktif|deactivate|disable|enable)\b/u', $m)) {
                $data['is_active'] = $this->detectBooleanFromMessage($m);
            }

            if ($entity === 'notifications' && preg_match('/\b(baca|read|sudah dibaca|belum dibaca)\b/u', $m)) {
                $data['is_read'] = ! preg_match('/\b(belum)\b/u', $m);
            }
        }

        if ($operation === 'create') {
            foreach ($this->extractShorthandFields($m) as $k => $v) {
                if (! isset($data[$k])) {
                    $data[$k] = $v;
                }
            }
        }

        if ($operation === 'delete' && empty($target)) {
            if (preg_match('/\b(id|nama|name|username|email|ticket number|ticket_number|url|key)\s*[:=]?\s*([^\n,;]+)/u', $m, $match)) {
                $data[$this->normalizeFieldName($match[1])] = trim($match[2], " \t\n\r\0\x0B\"'");
            }
        }

        if ($operation === 'read') {
            if (preg_match('/\b(hari ini|today)\b/u', $m)) {
                $notes[] = 'today_filter';
            }
        }

        return $data;
    }

    protected function extractShorthandFields(string $message): array
    {
        $out = [];

        if (preg_match('/\bname\s*[:=]\s*([^\n,;]+)/u', $message, $m)) {
            $out['name'] = trim($m[1], " \t\n\r\0\x0B\"'");
        }

        if (preg_match('/\bdeveloper\s*[:=]\s*([^\n,;]+)/u', $message, $m)) {
            $out['developer'] = trim($m[1], " \t\n\r\0\x0B\"'");
        }

        if (preg_match('/\bversion\s*[:=]\s*([^\n,;]+)/u', $message, $m)) {
            $out['version'] = trim($m[1], " \t\n\r\0\x0B\"'");
        }

        if (preg_match('/\bestimated_minutes\s*[:=]\s*(\d+)/u', $message, $m)) {
            $out['estimated_minutes'] = (int) $m[1];
        }

        if (preg_match('/\bdescription\s*[:=]\s*([^\n,;]+)/u', $message, $m)) {
            $out['description'] = trim($m[1], " \t\n\r\0\x0B\"'");
        }

        return $out;
    }

    protected function detectUpdateField(string $message, ?string $entity): ?string
    {
        if (preg_match('/\bstatus\b/u', $message) || preg_match('/\b(aktif|active|nonactive|nonaktif|deactivate|disable|enable)\b/u', $message)) {
            if (in_array($entity, ['roles', 'maintenances'], true)) {
                return 'is_active';
            }

            if ($entity === 'notifications') {
                return 'is_read';
            }

            if ($entity === 'tickets') {
                return 'status';
            }

            if ($entity === 'login_logs') {
                return 'status';
            }

            if ($entity === 'vercel_sync_logs') {
                return 'sync_status';
            }

            if ($entity === 'ai_tasks') {
                return 'status';
            }

            if ($entity === 'ai_recommendations') {
                return 'status';
            }
        }

        if (preg_match('/\b(read|baca)\b/u', $message) && $entity === 'notifications') {
            return 'is_read';
        }

        return null;
    }

    protected function detectBooleanFromMessage(string $message): ?bool
    {
        if (preg_match('/\b(nonactive|nonaktif|inactive|deactivate|disable|mati|off|false|0)\b/u', $message)) {
            return false;
        }

        if (preg_match('/\b(active|aktif|enable|enabled|on|hidup|true|1)\b/u', $message)) {
            return true;
        }

        return null;
    }

    protected function normalizeData(array $data): array
    {
        $aliases = $this->fieldAliases();
        $out = [];

        foreach ($data as $key => $value) {
            $key = $this->normalizeFieldName((string) $key);
            if (isset($aliases[$key])) {
                $key = $aliases[$key];
            }

            $value = is_string($value) ? trim($value) : $value;

            if ($value === '') {
                continue;
            }

            if (is_string($value) && is_numeric($value)) {
                $value = str_contains($value, '.') ? (float) $value : (int) $value;
            }

            if (is_string($value)) {
                $low = mb_strtolower($value);
                if (in_array($low, ['nonactive', 'nonaktif', 'inactive', 'deactivate', 'disable', 'mati', 'off'], true)) {
                    $value = false;
                } elseif (in_array($low, ['active', 'aktif', 'enable', 'enabled', 'on', 'hidup'], true)) {
                    $value = true;
                }
            }

            $out[$key] = $value;
        }

        return $out;
    }

    protected function normalizeTarget(array $target): array
    {
        $out = [];
        foreach ($target as $key => $value) {
            $key = $this->normalizeFieldName((string) $key);
            $value = is_string($value) ? trim($value) : $value;

            if ($value === '') {
                continue;
            }

            if (is_string($value) && is_numeric($value)) {
                $value = (int) $value;
            }

            $out[$key] = $value;
        }

        return $out;
    }

    protected function normalizeFilters(array $filters): array
    {
        $out = [];
        foreach ($filters as $key => $value) {
            $out[$this->normalizeFieldName((string) $key)] = $value;
        }

        return $out;
    }

    protected function isTargetKey(string $key, ?string $entity, ?string $operation): bool
    {
        $key = $this->normalizeFieldName($key);

        if ($operation === 'create') {
            return false;
        }

        return in_array($key, ['id', 'username', 'email', 'nim', 'nip', 'name', 'ticket_number', 'url', 'key'], true)
            || ($operation === 'delete' && in_array($key, $this->likelyTargetFields($entity), true));
    }

    protected function likelyTargetFields(?string $entity): array
    {
        return match (mb_strtolower(trim((string) $entity))) {
            'software' => ['id', 'name', 'developer', 'version'],
            'users' => ['id', 'username', 'email', 'nim', 'nip', 'name'],
            'tickets' => ['id', 'ticket_number'],
            'roles' => ['id', 'name'],
            'trusted_websites' => ['id', 'name', 'url'],
            'perbaikans' => ['id', 'item_name'],
            'penginstalans' => ['id'],
            'login_logs' => ['id'],
            'ai_tasks' => ['id', 'task_name'],
            'ai_recommendations' => ['id'],
            'notifications' => ['id', 'title'],
            'maintenances' => ['id'],
            'system_settings' => ['id', 'key'],
            'rekaps' => ['id', 'rekap_date'],
            'vercel_sync_logs' => ['id'],
            'ticket_status_logs' => ['id', 'ticket_id'],
            'ticket_comments' => ['id', 'ticket_id'],
            default => ['id', 'name', 'title', 'username', 'ticket_number', 'url', 'task_name'],
        };
    }

    protected function normalizeFieldName(string $field): string
    {
        $field = mb_strtolower(trim($field));
        return str_replace([' ', '-', '.'], '_', $field);
    }

    protected function fieldAliases(): array
    {
        return [
            'nama' => 'name',
            'deskripsi' => 'description',
            'pengembang' => 'developer',
            'versi' => 'version',
            'estimasi_menit' => 'estimated_minutes',
            'menit_estimasi' => 'estimated_minutes',
            'judul' => 'title',
            'pesan' => 'message',
            'komentar' => 'comment',
            'aktif' => 'is_active',
            'status_baca' => 'is_read',
            'nonactive' => 'is_active',
            'nonaktif' => 'is_active',
            'inactive' => 'is_active',
            'deactivate' => 'is_active',
            'disable' => 'is_active',
        ];
    }

    protected function confidenceScore(string $question, ?string $entity, ?string $operation, array $data, array $target, array $filters): float
    {
        $score = 0.4;

        if ($operation) {
            $score += 0.15;
        }

        if ($entity) {
            $score += 0.15;
        }

        if (! empty($data)) {
            $score += 0.15;
        }

        if (! empty($target)) {
            $score += 0.15;
        }

        if (! empty($filters)) {
            $score += 0.05;
        }

        if (preg_match('/\b(id|username|email|name|ticket_number|url|key)\b/u', mb_strtolower($question))) {
            $score += 0.05;
        }

        return min(0.99, $score);
    }
}
