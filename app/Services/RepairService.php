<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Perbaikan;
use App\Models\Rekap;
use App\Models\ticket_status_log;
use App\Models\ticket;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRGdImagePNG;

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

    private function createSystemNotification(ticket $ticket, string $title, string $message): void
    {
        Notification::create([
            'user_id'   => $ticket->user_id,
            'ticket_id' => $ticket->id,
            'type'      => 'system',
            'title'     => $title,
            'message'   => $message,
            'is_read'   => false,
        ]);
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

                $ticket->refresh();
                $this->sendCompletionWhatsApp($ticket, 'completed');
                $this->sendCompletionEmail($ticket, 'completed');

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

            $this->createSystemNotification(
                $ticket,
                'New Repair Ticket',
                'Ticket perbaikan ' . $ticket->ticket_number . ' berhasil dibuat.'
            );

            $this->createSystemNotification(
                $ticket,
                'Ticket Status Update',
                'Status ticket ' . $ticket->ticket_number . ' adalah waiting.'
            );

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

            $this->createSystemNotification(
                $ticket,
                'Repair Ticket Updated',
                'Ticket perbaikan ' . $ticket->ticket_number . ' telah diperbarui.'
            );

            if ($ticket->status === 'completed' || $ticket->status === 'failed') {
                $this->createSystemNotification(
                    $ticket,
                    'Ticket Finished',
                    'Status ticket ' . $ticket->ticket_number . ' berubah menjadi ' . $ticket->status . '.'
                );
            }

            if ($oldEnd && $newEnd && ! $oldEnd->equalTo($newEnd) && ! in_array($ticket->status, ['completed', 'failed', 'cancelled'], true)) {
                $this->rebuildTimelineFrom(
                    $date,
                    $oldEnd->copy()->addMinutes(5),
                    (int) ($ticket->queue_number ?? 1)
                );
            }

            $this->syncRepairState($date);
            $this->syncRekapForDate($date);

            $ticket->refresh();

            if (in_array($ticket->status, ['completed', 'failed'], true)) {
                $this->sendCompletionWhatsApp($ticket, $ticket->status);
                $this->sendCompletionEmail($ticket, $ticket->status);
            }

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

            $this->createSystemNotification(
                $ticket,
                $result === 'completed' ? 'Repair Completed' : 'Repair Failed',
                $result === 'completed'
                    ? 'Ticket perbaikan ' . $ticket->ticket_number . ' telah selesai.'
                    : 'Ticket perbaikan ' . $ticket->ticket_number . ' dinyatakan gagal.'
            );

            $ticket->refresh();
            $this->sendCompletionWhatsApp($ticket, $result);
            $this->sendCompletionEmail($ticket, $result);

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
            $oldStatus = $ticket->status;

            if (in_array($oldStatus, ['waiting', 'processing'], true) && now()->lessThan($this->getRepairEnd($ticket))) {
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
        $path = "qrcodes/tickets/{$ticket->ticket_number}.png";

        if (! Storage::disk('public')->exists($path)) {
            $payload = json_encode([
                'ticket_number' => $ticket->ticket_number,
                'type'          => $ticket->type,
                'id'            => $ticket->id,
                'token'         => $ticket->qr_token,
            ], JSON_UNESCAPED_UNICODE);

            $options = new QROptions([
                'outputInterface' => QRGdImagePNG::class,
                'scale'           => 10,
                'outputBase64'    => false,
            ]);

            $png = (new QRCode($options))->render($payload);

            Storage::disk('public')->put($path, $png);
        }

        return $path;
    }

    private function normalizePhoneToChatId(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if (! $digits) {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            $digits = '62' . substr($digits, 1);
        } elseif (! str_starts_with($digits, '62')) {
            $digits = '62' . ltrim($digits, '0');
        }

        return $digits . '@c.us';
    }

    private function formatDurationText(int $minutes): string
    {
        if ($minutes <= 0) {
            return '-';
        }

        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        $parts = [];

        if ($hours > 0) {
            $parts[] = $hours . ' jam';
        }

        if ($mins > 0 || $hours === 0) {
            $parts[] = $mins . ' menit';
        }

        return trim(implode(' ', $parts));
    }

    private function buildCompletionMessage(ticket $ticket, string $result): string
    {
        $userName = $ticket->user?->name ?? $ticket->user?->nama ?? 'Pengguna';

        $itemName = $ticket->perbaikan?->item_name ?? '-';
        $itemLocation = $ticket->perbaikan?->item_location ?? '-';
        $damageDescription = $ticket->perbaikan?->damage_description ?? '-';
        $repairAction = $ticket->perbaikan?->repair_action ?? '-';
        $repairResultRaw = $ticket->perbaikan?->repair_result;

        $bookingDateText = $ticket->booking_date
            ? Carbon::parse($ticket->booking_date)->translatedFormat('d F Y')
            : '-';

        $completedAtText = $ticket->completed_at
            ? Carbon::parse($ticket->completed_at)->translatedFormat('d F Y H:i')
            : Carbon::now()->translatedFormat('d F Y H:i');

        $durationMinutes = $this->getRepairDurationMinutes($ticket);
        $durationText = $this->formatDurationText($durationMinutes);

        $resultText = match ($repairResultRaw) {
            'success'       => 'berhasil',
            'failed'        => 'gagal (mengalami kendala)',
            'unrepairable'  => 'tidak dapat diperbaiki',
            default         => $result === 'completed' ? 'berhasil' : 'gagal (mengalami kendala)',
        };

        if ($result === 'completed') {
            $opening = 'perbaikan Anda telah selesai dikerjakan';
            $closing = 'Silakan datang ke ruang teknisi untuk mengambil perangkat.';
        } else {
            $opening = 'perbaikan Anda mengalami kendala dan dinyatakan gagal';
            $closing = 'Silakan datang ke ruang teknisi untuk informasi lebih lanjut.';
        }

        return "Halo {$userName}, {$opening}.\n\n"
            . "Berikut data perbaikan Anda:\n"
            . "Nama barang: {$itemName}\n"
            . "Lokasi barang: {$itemLocation}\n"
            . "Kerusakan: {$damageDescription}\n"
            . "Tindakan perbaikan: {$repairAction}\n"
            . "Hasil perbaikan: {$resultText}\n"
            . "Ticket Number: {$ticket->ticket_number}\n"
            . "Tanggal Data: {$bookingDateText}\n"
            . "Durasi Pengerjaan: {$durationText}\n"
            . "Queue: " . ($ticket->queue_number ?? '-') . "\n\n"
            . "{$closing}\n\n"
            . "QR code tiket terlampir pada pesan ini.\n\n"
            . "_{$completedAtText}_\n"
            . "_Sent via TechNoteAPP (powered by Green.com)_";
    }

    private function sendWhatsAppFile(string $chatId, string $filePath, string $caption = ''): bool
    {
        $mediaUrl = rtrim(env('GREEN_API_MEDIA_URL'), '/');
        $idInstance = env('GREEN_API_ID_INSTANCE');
        $token = env('GREEN_API_API_TOKEN');

        if (! file_exists($filePath)) {
            return false;
        }

        $response = Http::attach(
            'file',
            fopen($filePath, 'r'),
            basename($filePath)
        )->post(
            "{$mediaUrl}/waInstance{$idInstance}/sendFileByUpload/{$token}",
            [
                'chatId'  => $chatId,
                'caption' => $caption,
            ]
        );

        return $response->successful();
    }

    private function sendCompletionWhatsApp(ticket $ticket, string $result): void
    {
        if ($ticket->wa_notification_sent_at) {
            return;
        }

        $ticket->loadMissing(['user', 'perbaikan']);

        $chatId = $this->normalizePhoneToChatId($ticket->user?->no_hp);

        if (! $chatId) {
            return;
        }

        $message = $this->buildCompletionMessage($ticket, $result);
        $qrRelativePath = $this->generateTicketQr($ticket);
        $qrAbsolutePath = Storage::disk('public')->path($qrRelativePath);

        try {
            $sentFile = $this->sendWhatsAppFile(
                $chatId,
                $qrAbsolutePath,
                $message
            );

            if ($sentFile) {
                $ticket->forceFill([
                    'wa_notification_sent_at' => now(),
                ])->save();
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function sendCompletionEmail(ticket $ticket, string $result): void
    {
        if ($ticket->email_notification_sent_at) {
            return;
        }

        $ticket->loadMissing(['user', 'perbaikan']);

        $email = trim((string) ($ticket->user?->email ?? ''));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $subject = $result === 'completed'
            ? 'TechNoteAPP - Perbaikan selesai: ' . $ticket->ticket_number
            : 'TechNoteAPP - Perbaikan gagal: ' . $ticket->ticket_number;

        $message = $this->buildCompletionMessage($ticket, $result);
        $html = nl2br(e($message));

        $qrRelativePath = $this->generateTicketQr($ticket);
        $qrAbsolutePath = Storage::disk('public')->path($qrRelativePath);

        try {
            Mail::html($html, function ($mail) use ($email, $subject, $qrAbsolutePath, $ticket) {
                $mail->to($email)
                    ->from(config('mail.from.address'), config('mail.from.name'))
                    ->replyTo(config('mail.from.address'), config('mail.from.name'))
                    ->subject($subject)
                    ->priority(3);

                if (method_exists($mail, 'getSymfonyMessage')) {
                    $symfonyMessage = $mail->getSymfonyMessage();
                    $headers = $symfonyMessage->getHeaders();

                    if (method_exists($headers, 'addTextHeader')) {
                        $headers->addTextHeader('X-Mailer', 'TechNoteAPP');
                        $headers->addTextHeader('X-Priority', '3');
                        $headers->addTextHeader('Importance', 'Normal');
                    }
                }

                if (is_file($qrAbsolutePath)) {
                    $mail->attach($qrAbsolutePath, [
                        'as'   => 'QR-' . $ticket->ticket_number . '.png',
                        'mime' => 'image/png',
                    ]);
                }
            });

            $ticket->forceFill([
                'email_notification_sent_at' => now(),
            ])->save();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
