<?php

namespace App\Services;

use App\Models\Penginstalan;
use App\Models\ticket;
use App\Models\ticket_status_log;
use Illuminate\Support\Facades\DB;

class TicketFlowService
{
    public function createInstallation(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $ticket = ticket::create([
                'ticket_number'    => $this->generateTicketNumber('INS'),
                'type'             => 'installation',
                'user_id'          => $data['user_id'],
                'status'           => 'waiting',
                'priority'         => $data['priority'] ?? 'normal',
                'estimated_finish' => $data['estimated_finish'] ?? null,
                'is_public'        => $data['is_public'] ?? true,
            ]);

            $penginstalan = Penginstalan::create([
                'ticket_id'           => $ticket->id,
                'user_id'             => $data['user_id'],
                'software_id'         => $data['software_id'],
                'installation_result' => $data['installation_result'] ?? null,
                'note'                => $data['note'] ?? null,
            ]);

            ticket_status_log::create([
                'ticket_id'  => $ticket->id,
                'old_status' => null,
                'new_status' => 'waiting',
                'note'       => 'Installation ticket created.',
                'changed_by' => $data['changed_by'] ?? null,
            ]);

            return [
                'ticket'       => $ticket,
                'penginstalan' => $penginstalan,
            ];
        });
    }

    public function updateInstallation(Penginstalan $penginstalan, array $data): Penginstalan
    {
        return DB::transaction(function () use ($penginstalan, $data) {
            $ticket = $penginstalan->ticket;

            if ($ticket) {
                $ticket->update([
                    'user_id'          => $data['user_id'],
                    'priority'         => $data['priority'] ?? $ticket->priority,
                    'estimated_finish' => $data['estimated_finish'] ?? null,
                    'is_public'        => $data['is_public'] ?? true,
                ]);
            }

            $penginstalan->update([
                'user_id'             => $data['user_id'],
                'software_id'         => $data['software_id'],
                'installation_result' => $data['installation_result'] ?? null,
                'note'                => $data['note'] ?? null,
            ]);

            return $penginstalan;
        });
    }

    public function changeStatus(ticket $ticket, string $newStatus, ?string $note = null, ?int $changedBy = null): ticket
    {
        return DB::transaction(function () use ($ticket, $newStatus, $note, $changedBy) {
            $oldStatus = $ticket->status;

            $ticket->update([
                'status' => $newStatus,
                'completed_at' => in_array($newStatus, ['completed', 'failed'])
                    ? now()
                    : $ticket->completed_at,
            ]);

            ticket_status_log::create([
                'ticket_id'  => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'note'       => $note,
                'changed_by' => $changedBy,
            ]);

            return $ticket;
        });
    }

    public function autoCompleteOverdueTickets(): int
    {
        return DB::transaction(function () {
            $tickets = ticket::whereNotNull('estimated_finish')
                ->where('estimated_finish', '<=', now())
                ->whereNotIn('status', ['completed', 'failed'])
                ->get();

            foreach ($tickets as $ticket) {
                $oldStatus = $ticket->status;

                $ticket->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

                ticket_status_log::create([
                    'ticket_id'  => $ticket->id,
                    'old_status' => $oldStatus,
                    'new_status' => 'completed',
                    'note'       => 'Auto completed by system because estimated finish has passed.',
                    'changed_by' => null,
                ]);
            }

            return $tickets->count();
        });
    }

    private function generateTicketNumber(string $prefix): string
    {
        $date = now()->format('Ymd');
        $lastId = ticket::withTrashed()->max('id') ?? 0;
        $sequence = str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$date}-{$sequence}";
    }
}
