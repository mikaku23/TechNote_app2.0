<?php

namespace App\Services;

use App\Models\Perbaikan;
use App\Models\Rekap;
use App\Models\ticket_status_log;
use App\Models\ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class RepairService
{
    private function syncRekapForDate(Carbon $date): void
    {
        $rekapDate = $date->toDateString();

        Rekap::updateOrCreate(
            ['rekap_date' => $rekapDate],
            [
                'total_installations' => ticket::whereDate('booking_date', $rekapDate)
                    ->where('type', 'installation')
                    ->whereNull('deleted_at')
                    ->count(),

                'total_repairs' => ticket::whereDate('booking_date', $rekapDate)
                    ->where('type', 'repair')
                    ->whereNull('deleted_at')
                    ->count(),

                'completed_tickets' => ticket::whereDate('booking_date', $rekapDate)
                    ->where('status', 'completed')
                    ->whereNull('deleted_at')
                    ->count(),

                'failed_tickets' => ticket::whereDate('booking_date', $rekapDate)
                    ->where('status', 'failed')
                    ->whereNull('deleted_at')
                    ->count(),

                'pending_tickets' => ticket::whereDate('booking_date', $rekapDate)
                    ->whereIn('status', ['waiting', 'diagnosis', 'processing', 'testing'])
                    ->whereNull('deleted_at')
                    ->count(),
            ]
        );
    }

    private function generateTicketNumber(string $prefix): string
    {
        $date = now()->format('Ymd');
        $lastId = ticket::withTrashed()->max('id') ?? 0;
        $sequence = str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$date}-{$sequence}";
    }

    private function normalizeResult(string $status, ?string $repairResult): ?string
    {
        if ($repairResult !== null) {
            return $repairResult;
        }

        return match ($status) {
            'completed' => 'success',
            'failed'    => 'failed',
            default     => null,
        };
    }

    private function getRepairStart(ticket $ticket): Carbon
    {
        return $ticket->created_at
            ? Carbon::parse($ticket->created_at)
            : now();
    }

    private function getRepairEnd(ticket $ticket): Carbon
    {
        if ($ticket->estimated_finish) {
            return Carbon::parse($ticket->estimated_finish);
        }

        return $this->getRepairStart($ticket)->copy()->addMinutes(60);
    }

    private function getRepairDurationMinutes(ticket $ticket): int
    {
        $start = $this->getRepairStart($ticket);
        $end   = $this->getRepairEnd($ticket);

        return max(15, $start->diffInMinutes($end));
    }

    public function rebuildTimelineFrom(Carbon $date, Carbon $cursorStart, int $startingQueueNumber = 1): void
    {
        $tickets = ticket::with(['perbaikan'])
            ->where('type', 'repair')
            ->whereDate('booking_date', $date->toDateString())
            ->whereIn('status', ['waiting', 'processing'])
            ->whereNull('deleted_at')
            ->where('created_at', '>=', $cursorStart->toDateTimeString())
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $cursor = $cursorStart->copy();
        $queue  = $startingQueueNumber;

        foreach ($tickets as $ticket) {
            $duration = $this->getRepairDurationMinutes($ticket);

            $start = $cursor->copy();
            $end   = $start->copy()->addMinutes($duration);

            $ticket->update([
                'queue_number'     => $queue,
                'estimated_finish' => $end,
            ]);

            $cursor = $end->copy()->addMinutes(5);
            $queue++;
        }
    }

    public function syncRepairState(?Carbon $date = null): int
    {
        $completedCount = 0;
        $now = now();

        $query = ticket::with(['perbaikan'])
            ->where('type', 'repair')
            ->whereIn('status', ['waiting', 'processing'])
            ->whereNull('deleted_at');

        if ($date) {
            $query->whereDate('booking_date', $date->toDateString());
        }

        $tickets = $query
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($tickets as $ticket) {
            $start = $this->getRepairStart($ticket);
            $end   = $this->getRepairEnd($ticket);

            $ticketDate = Carbon::parse($ticket->booking_date ?? $start)->toDateString();

            if ($now->greaterThanOrEqualTo($end)) {
                $oldStatus = $ticket->status;

                $ticket->update([
                    'status'       => 'completed',
                    'completed_at' => $now,
                ]);

                if ($ticket->perbaikan) {
                    $ticket->perbaikan->update([
                        'repair_result' => 'success',
                    ]);
                }

                ticket_status_log::create([
                    'ticket_id'  => $ticket->id,
                    'old_status' => $oldStatus,
                    'new_status' => 'completed',
                    'note'       => 'Repair completed automatically because estimated finish has passed.',
                    'changed_by' => null,
                ]);

                $this->rebuildTimelineFrom(
                    Carbon::parse($ticketDate),
                    $end->copy()->addMinutes(5),
                    (int) ($ticket->queue_number ?? 1)
                );

                $this->syncRekapForDate(Carbon::parse($ticketDate));
                $completedCount++;
                continue;
            }

            if ($now->greaterThanOrEqualTo($start) && $now->lessThan($end)) {
                if ($ticket->status !== 'processing') {
                    $oldStatus = $ticket->status;

                    $ticket->update([
                        'status' => 'processing',
                    ]);

                    ticket_status_log::create([
                        'ticket_id'  => $ticket->id,
                        'old_status' => $oldStatus,
                        'new_status' => 'processing',
                        'note'       => 'Repair is currently being processed.',
                        'changed_by' => null,
                    ]);

                    $this->syncRekapForDate(Carbon::parse($ticketDate));
                }

                continue;
            }

            if ($ticket->status !== 'waiting') {
                $oldStatus = $ticket->status;

                $ticket->update([
                    'status' => 'waiting',
                ]);

                ticket_status_log::create([
                    'ticket_id'  => $ticket->id,
                    'old_status' => $oldStatus,
                    'new_status' => 'waiting',
                    'note'       => 'Repair is waiting for its turn.',
                    'changed_by' => null,
                ]);

                $this->syncRekapForDate(Carbon::parse($ticketDate));
            }
        }

        return $completedCount;
    }

    public function autoCompleteOverdueTickets(): int
    {
        return $this->syncRepairState();
    }

    public function createRepair(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $date = now();

            $estimatedFinish = ! empty($data['estimated_finish'])
                ? Carbon::parse($data['estimated_finish'])
                : $date->copy()->addMinutes(60);

            if ($estimatedFinish->lessThanOrEqualTo($date)) {
                $estimatedFinish = $date->copy()->addMinutes(60);
            }

            $ticket = ticket::create([
                'ticket_number'    => $this->generateTicketNumber('REP'),
                'qr_token'         => (string) Str::uuid(),
                'type'             => 'repair',
                'user_id'          => $data['user_id'],
                'status'           => 'waiting',
                'priority'         => $data['priority'] ?? 'normal',
                'estimated_finish' => $estimatedFinish,
                'completed_at'     => null,
                'is_public'        => (bool) ($data['is_public'] ?? true),
                'booking_date'     => $date->toDateString(),
                'session'          => null,
                'queue_number'     => null,
                'scheduled_start'  => null,
                'scheduled_end'    => null,
            ]);

            $qrPath = $this->generateTicketQr($ticket);

            $ticket->update([
                'qr_code' => $qrPath,
            ]);

            $perbaikan = Perbaikan::create([
                'ticket_id'           => $ticket->id,
                'user_id'             => $data['user_id'],
                'item_name'           => $data['item_name'],
                'item_location'       => $data['item_location'] ?? null,
                'damage_description'  => $data['damage_description'],
                'repair_action'       => null,
                'repair_result'       => null,
                'note'                => $data['note'] ?? null,
            ]);

            ticket_status_log::create([
                'ticket_id'  => $ticket->id,
                'old_status' => null,
                'new_status' => 'waiting',
                'note'       => 'Repair ticket created by admin.',
                'changed_by' => $data['changed_by'] ?? null,
            ]);

            $this->syncRepairState($date);
            $this->syncRekapForDate($date);

            return [
                'ticket'    => $ticket,
                'perbaikan' => $perbaikan,
            ];
        });
    }

    public function updateRepair(Perbaikan $perbaikan, array $data): Perbaikan
    {
        return DB::transaction(function () use ($perbaikan, $data) {
            $ticket = $perbaikan->ticket;

            if (! $ticket) {
                throw new RuntimeException('Ticket tidak ditemukan untuk data perbaikan ini.');
            }

            $oldStatus = $ticket->status;
            $date = Carbon::parse($ticket->booking_date ?? now()->toDateString());

            $oldEnd = $ticket->estimated_finish ? Carbon::parse($ticket->estimated_finish) : null;
            $newEnd = ! empty($data['estimated_finish'])
                ? Carbon::parse($data['estimated_finish'])
                : $ticket->estimated_finish;

            if ($newEnd instanceof Carbon && $newEnd->lessThanOrEqualTo($this->getRepairStart($ticket))) {
                throw new RuntimeException('Estimasi selesai harus lebih besar dari waktu mulai repair.');
            }

            $ticket->update([
                'user_id'          => $data['user_id'],
                'priority'         => $data['priority'] ?? 'normal',
                'status'           => $data['status'],
                'estimated_finish' => $newEnd,
                'completed_at'     => in_array($data['status'], ['completed', 'failed'], true)
                    ? now()
                    : $ticket->completed_at,
                'is_public'        => (bool) ($data['is_public'] ?? true),
            ]);

            $repairResult = $this->normalizeResult(
                $data['status'],
                $data['repair_result'] ?? null
            );

            $perbaikan->update([
                'user_id'            => $data['user_id'],
                'item_name'          => $data['item_name'],
                'item_location'      => $data['item_location'] ?? null,
                'damage_description' => $data['damage_description'],
                'repair_action'      => $data['repair_action'] ?? null,
                'repair_result'      => $repairResult,
                'note'               => $data['note'] ?? null,
            ]);

            ticket_status_log::create([
                'ticket_id'  => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => $data['status'],
                'note'       => 'Repair ticket updated by admin.',
                'changed_by' => $data['changed_by'] ?? null,
            ]);

            if ($oldEnd && $newEnd && ! $oldEnd->equalTo($newEnd) && ! in_array($ticket->status, ['completed', 'failed', 'cancelled'], true)) {
                $this->rebuildTimelineFrom(
                    $date,
                    $oldEnd->copy()->addMinutes(5),
                    (int) ($ticket->queue_number ?? 1)
                );
            }

            $this->syncRepairState($date);
            $this->syncRekapForDate($date);

            return $perbaikan->refresh();
        });
    }

    public function finishTicket(ticket $ticket, string $result, ?int $changedBy = null): ticket
    {
        if (! in_array($result, ['completed', 'failed'], true)) {
            throw new RuntimeException('Invalid finish result.');
        }

        return DB::transaction(function () use ($ticket, $result, $changedBy) {
            $oldStatus = $ticket->status;
            $date      = Carbon::parse($ticket->booking_date ?? now()->toDateString());

            $ticket->update([
                'status'       => $result,
                'completed_at' => now(),
            ]);

            if ($ticket->perbaikan) {
                $ticket->perbaikan->update([
                    'repair_result' => $result === 'completed' ? 'success' : 'failed',
                ]);
            }

            ticket_status_log::create([
                'ticket_id'  => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => $result,
                'note'       => $result === 'completed'
                    ? 'Repair completed manually by admin.'
                    : 'Repair failed manually by admin.',
                'changed_by' => $changedBy,
            ]);

            $this->syncRekapForDate($date);

            return $ticket;
        });
    }

    public function deleteRepair(Perbaikan $perbaikan, ?int $changedBy = null): void
    {
        DB::transaction(function () use ($perbaikan, $changedBy) {
            $ticket = $perbaikan->ticket;

            if (! $ticket) {
                $perbaikan->delete();
                return;
            }

            $date = Carbon::parse($ticket->booking_date ?? now()->toDateString());
            $start = $this->getRepairStart($ticket);
            $end   = $this->getRepairEnd($ticket);

            $oldStatus = $ticket->status;

            if (in_array($oldStatus, ['waiting', 'processing'], true) && now()->lessThan($end)) {
                $ticket->update([
                    'status'       => 'failed',
                    'completed_at' => now(),
                ]);

                if ($ticket->perbaikan) {
                    $ticket->perbaikan->update([
                        'repair_result' => 'failed',
                    ]);
                }

                ticket_status_log::create([
                    'ticket_id'  => $ticket->id,
                    'old_status' => $oldStatus,
                    'new_status' => 'failed',
                    'note'       => 'Repair failed because ticket was moved to recycle bin while still in progress.',
                    'changed_by' => $changedBy,
                ]);
            } else {
                ticket_status_log::create([
                    'ticket_id'  => $ticket->id,
                    'old_status' => $oldStatus,
                    'new_status' => $oldStatus,
                    'note'       => 'Repair moved to recycle bin by admin.',
                    'changed_by' => $changedBy,
                ]);
            }

            $ticket->delete();
            $perbaikan->delete();

            $this->syncRekapForDate($date);
        });
    }

    public function restoreRepair(Perbaikan $perbaikan, ?int $changedBy = null): void
    {
        DB::transaction(function () use ($perbaikan, $changedBy) {
            $ticket = ticket::withTrashed()->findOrFail($perbaikan->ticket_id);

            $date = Carbon::parse($ticket->booking_date ?? now()->toDateString());
            $oldStatus = $ticket->status ?? 'cancelled';

            $ticket->restore();
            $perbaikan->restore();

            if (in_array($oldStatus, ['completed', 'failed'], true)) {
                $ticket->update([
                    'status'       => $oldStatus,
                    'completed_at' => $ticket->completed_at ?? now(),
                ]);
            } else {
                $ticket->update([
                    'status'       => 'waiting',
                    'completed_at' => null,
                ]);
            }

            ticket_status_log::create([
                'ticket_id'  => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => $ticket->status,
                'note'       => in_array($oldStatus, ['completed', 'failed'], true)
                    ? 'Repair restored from recycle bin without restarting because it was already finished.'
                    : 'Repair restored from recycle bin by admin.',
                'changed_by' => $changedBy,
            ]);

            if (! in_array($ticket->status, ['completed', 'failed'], true)) {
                $this->syncRepairState($date);
            }

            $this->syncRekapForDate($date);
        });
    }
    private function generateTicketQr(ticket $ticket): string
    {
        $path = "qrcodes/tickets/{$ticket->ticket_number}.svg";

        if (!Storage::disk('public')->exists($path)) {
            $qr = QrCode::format('svg')
                ->size(500)
                ->margin(2)
                ->generate(json_encode([
                    'ticket_number' => $ticket->ticket_number,
                    'type'          => $ticket->type,
                    'id'            => $ticket->id,
                    'token'         => $ticket->qr_token,
                ]));

            Storage::disk('public')->put($path, $qr);
        }

        return $path;
    }
}
