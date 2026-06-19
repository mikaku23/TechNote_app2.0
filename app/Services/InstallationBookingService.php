<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Penginstalan;
use App\Models\Rekap;
use App\Models\Software;
use App\Models\ticket;
use App\Models\ticket_comment;
use App\Models\ticket_status_log;
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

class InstallationBookingService
{
    public function getSessionConfig(string $session): array
    {
        return match ($session) {
            'morning' => [
                'start'        => '08:00',
                'end'          => '11:00',
                'accept_until' => '10:00',
            ],
            'afternoon' => [
                'start'        => '14:00',
                'end'          => '21:00',
                'accept_until' => '20:00',
            ],
            default => throw new RuntimeException('Invalid session.'),
        };
    }

    private function makeDateTime(Carbon $date, string $time): Carbon
    {
        return Carbon::parse($date->format('Y-m-d') . ' ' . $time);
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

    private function activeTicketsQuery(Carbon $date, string $session, ?int $ignoreTicketId = null)
    {
        $query = ticket::with(['penginstalan.software'])
            ->where('type', 'installation')
            ->whereDate('booking_date', $date->toDateString())
            ->where('session', $session)
            ->whereIn('status', ['waiting', 'processing'])
            ->whereNull('deleted_at');

        if ($ignoreTicketId) {
            $query->where('id', '!=', $ignoreTicketId);
        }

        return $query;
    }

    private function countActiveTickets(Carbon $date, string $session, ?int $ignoreTicketId = null): int
    {
        $query = ticket::where('type', 'installation')
            ->whereDate('booking_date', $date->toDateString())
            ->where('session', $session)
            ->whereIn('status', ['waiting', 'processing'])
            ->whereNull('deleted_at');

        if ($ignoreTicketId) {
            $query->where('id', '!=', $ignoreTicketId);
        }

        return $query->count();
    }

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
                    ->whereIn('status', ['waiting', 'processing', 'diagnosis', 'testing'])
                    ->whereNull('deleted_at')
                    ->count(),
            ]
        );
    }

    public function checkAvailability(
        Carbon $date,
        string $session,
        int $softwareId,
        ?int $ignoreTicketId = null
    ): array {
        $software = Software::findOrFail($softwareId);
        $sessionConfig = $this->getSessionConfig($session);

        $sessionStart = $this->makeDateTime($date, $sessionConfig['start']);
        $sessionEnd   = $this->makeDateTime($date, $sessionConfig['end']);
        $acceptUntil  = $this->makeDateTime($date, $sessionConfig['accept_until']);

        if ($date->isToday() && now()->gte($acceptUntil)) {
            return [
                'available'        => false,
                'session_start'    => $sessionStart,
                'session_end'      => $sessionEnd,
                'accept_until'     => $acceptUntil,
                'next_start'       => $acceptUntil,
                'next_end'         => $acceptUntil,
                'queue_number'     => $this->countActiveTickets($date, $session, $ignoreTicketId) + 1,
                'software_name'    => $software->name,
                'software_minutes' => (int) $software->estimated_minutes,
                'reason'           => 'Booking sudah melewati batas waktu penerimaan sesi.',
            ];
        }

        $tickets = $this->activeTicketsQuery($date, $session, $ignoreTicketId)
            ->orderBy('queue_number')
            ->orderBy('scheduled_start')
            ->orderBy('id')
            ->get();

        if ($tickets->isEmpty()) {
            $cursor = $date->isToday()
                ? max(now(), $sessionStart)
                : $sessionStart->copy();
        } else {
            $last = $tickets->last();

            $cursor = Carbon::parse(
                $last->estimated_finish
                    ?? $last->scheduled_end
                    ?? $last->scheduled_start
            );
        }

        if ($cursor->lessThan($sessionStart)) {
            $cursor = $sessionStart->copy();
        }

        if ($date->isToday() && $cursor->lessThan(now())) {
            $cursor = now()->copy();
        }

        $newDuration = (int) $software->estimated_minutes;
        $nextStart = $cursor->copy();
        $nextEnd   = $nextStart->copy()->addMinutes($newDuration + 5);

        return [
            'available'        => $nextEnd->lessThanOrEqualTo($sessionEnd),
            'session_start'    => $sessionStart,
            'session_end'      => $sessionEnd,
            'accept_until'     => $acceptUntil,
            'next_start'       => $nextStart,
            'next_end'         => $nextEnd,
            'queue_number'     => $tickets->count() + 1,
            'software_name'    => $software->name,
            'software_minutes' => $newDuration,
            'reason'           => $nextEnd->greaterThan($sessionEnd)
                ? 'Estimasi tidak cukup untuk masuk sesi ini.'
                : null,
        ];
    }

    public function createBooking(array $data): array
    {
        $result = DB::transaction(function () use ($data) {
            $date = Carbon::parse($data['booking_date']);

            $availability = $this->checkAvailability(
                $date,
                $data['session'],
                (int) $data['software_id']
            );

            if (! $availability['available']) {
                throw new RuntimeException($availability['reason'] ?? 'Booking penuh atau slot waktunya sudah lewat.');
            }

            $ticket = ticket::create([
                'ticket_number'    => $this->generateTicketNumber('INS'),
                'qr_token'         => (string) Str::uuid(),
                'type'             => 'installation',
                'user_id'          => $data['user_id'],
                'status'           => 'waiting',
                'priority'         => 'normal',
                'booking_date'     => $date->toDateString(),
                'session'          => $data['session'],
                'queue_number'     => $availability['queue_number'],
                'scheduled_start'  => $availability['next_start'],
                'scheduled_end'    => $availability['next_start']->copy()->addMinutes($availability['software_minutes']),
                'estimated_finish' => $availability['next_end'],
                'is_public'        => true,
            ]);

            $penginstalan = Penginstalan::create([
                'ticket_id'           => $ticket->id,
                'user_id'             => $data['user_id'],
                'software_id'         => $data['software_id'],
                'installation_result' => null,
                'note'                => null,
            ]);

            if (! empty($data['note'])) {
                ticket_comment::create([
                    'ticket_id'   => $ticket->id,
                    'user_id'     => $data['user_id'],
                    'comment'     => $data['note'],
                    'is_internal' => false,
                ]);
            }

            ticket_status_log::create([
                'ticket_id'  => $ticket->id,
                'old_status' => null,
                'new_status' => 'waiting',
                'note'       => 'Installation booking created by student.',
                'changed_by' => $data['changed_by'] ?? null,
            ]);

            $this->createSystemNotification(
                $ticket,
                'New Installation Booking',
                'Booking penginstalan berhasil dibuat untuk ticket ' . $ticket->ticket_number . '. Status awal: waiting.'
            );

            if (! empty($data['note'])) {
                $this->createSystemNotification(
                    $ticket,
                    'Ticket Comment Added',
                    'Catatan baru ditambahkan pada ticket ' . $ticket->ticket_number . ': ' . Str::limit($data['note'], 120)
                );
            }

            $this->syncSessionState(Carbon::parse($ticket->booking_date), $ticket->session);
            $this->syncRekapForDate(Carbon::parse($ticket->booking_date));

            return [
                'ticket'       => $ticket->refresh(),
                'penginstalan' => $penginstalan,
                'availability' => $availability,
            ];
        });

        try {
            $qrPath = $this->generateTicketQr($result['ticket']);

            $result['ticket']->forceFill([
                'qr_code' => $qrPath,
            ])->save();

            $result['ticket']->refresh();
        } catch (\Throwable $e) {
            report($e);
        }

        return $result;
    }

    public function updateBooking(ticket $ticket, array $data): ticket
    {
        return DB::transaction(function () use ($ticket, $data) {
            $oldStatus  = $ticket->status;
            $oldDate    = Carbon::parse($ticket->booking_date);
            $oldSession = (string) $ticket->session;
            $oldStart   = $ticket->scheduled_start ? Carbon::parse($ticket->scheduled_start) : null;
            $oldQueue   = (int) ($ticket->queue_number ?? 1);

            $date = Carbon::parse($data['booking_date']);

            $availability = $this->checkAvailability(
                $date,
                $data['session'],
                (int) $data['software_id'],
                $ticket->id
            );

            if (! $availability['available']) {
                throw new RuntimeException($availability['reason'] ?? 'Booking penuh atau slot waktunya sudah lewat.');
            }

            $ticket->update([
                'booking_date'    => $date->toDateString(),
                'session'         => $data['session'],
                'queue_number'    => $availability['queue_number'],
                'scheduled_start'  => $availability['next_start'],
                'scheduled_end'    => $availability['next_start']->copy()->addMinutes($availability['software_minutes']),
                'estimated_finish' => $availability['next_end'],
            ]);

            if ($ticket->penginstalan) {
                $ticket->penginstalan->update([
                    'software_id' => (int) $data['software_id'],
                ]);
            }

            if (! empty($data['note'])) {
                ticket_comment::create([
                    'ticket_id'   => $ticket->id,
                    'user_id'     => $data['changed_by'] ?? $ticket->user_id,
                    'comment'     => $data['note'],
                    'is_internal' => false,
                ]);
            }

            ticket_status_log::create([
                'ticket_id'  => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => $ticket->status,
                'note'       => 'Booking updated by student.',
                'changed_by' => $data['changed_by'] ?? null,
            ]);

            $this->createSystemNotification(
                $ticket,
                'Installation Booking Updated',
                'Booking penginstalan ' . $ticket->ticket_number . ' telah diperbarui.'
            );

            if (! empty($data['note'])) {
                $this->createSystemNotification(
                    $ticket,
                    'Ticket Comment Added',
                    'Catatan baru ditambahkan pada ticket ' . $ticket->ticket_number . ': ' . Str::limit($data['note'], 120)
                );
            }

            if ($oldDate->toDateString() !== $date->toDateString() || $oldSession !== $data['session']) {
                if ($oldStart) {
                    $this->rebuildQueueFrom($oldDate, $oldSession, $oldStart, $oldQueue);
                }
            }

            $this->rebuildQueueFrom(
                $date,
                $data['session'],
                $ticket->scheduled_end->copy()->addMinutes(5),
                $ticket->queue_number + 1
            );

            $this->syncSessionState($oldDate, $oldSession);
            $this->syncSessionState($date, $data['session']);

            $this->syncRekapForDate($oldDate);
            $this->syncRekapForDate($date);

            return $ticket;
        });
    }

    public function cancelBooking(ticket $ticket, ?int $changedBy = null): ticket
    {
        return DB::transaction(function () use ($ticket, $changedBy) {
            $oldStatus = $ticket->status;
            $date      = Carbon::parse($ticket->booking_date);
            $session   = (string) $ticket->session;
            $start     = $ticket->scheduled_start ? Carbon::parse($ticket->scheduled_start) : null;
            $queue     = (int) ($ticket->queue_number ?? 1);

            ticket_status_log::create([
                'ticket_id'  => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => 'cancelled',
                'note'       => 'Booking cancelled by student.',
                'changed_by' => $changedBy,
            ]);

            if ($ticket->penginstalan) {
                $ticket->penginstalan->delete();
            }

            $ticket->delete();

            if ($start) {
                $this->rebuildQueueFrom($date, $session, $start, $queue);
            }

            $this->syncSessionState($date, $session);
            $this->syncRekapForDate($date);

            $this->createSystemNotification(
                $ticket,
                'Ticket Cancelled',
                'Booking penginstalan ' . $ticket->ticket_number . ' telah dibatalkan.'
            );

            return $ticket;
        });
    }

    public function finishTicket(ticket $ticket, string $result, ?int $changedBy = null): ticket
    {
        if (! in_array($result, ['completed', 'failed'], true)) {
            throw new RuntimeException('Invalid finish result.');
        }

        return DB::transaction(function () use ($ticket, $result, $changedBy) {
            $oldStatus = $ticket->status;
            $date      = Carbon::parse($ticket->booking_date);
            $session   = (string) $ticket->session;
            $queue     = (int) ($ticket->queue_number ?? 1);

            $ticket->update([
                'status'       => $result,
                'completed_at' => now(),
            ]);

            if ($ticket->penginstalan) {
                $ticket->penginstalan->update([
                    'installation_result' => $result === 'completed' ? 'success' : 'failed',
                ]);
            }

            ticket_status_log::create([
                'ticket_id'  => $ticket->id,
                'old_status' => $oldStatus,
                'new_status' => $result,
                'note'       => $result === 'completed'
                    ? 'Installation completed manually.'
                    : 'Installation failed manually.',
                'changed_by' => $changedBy,
            ]);

            $this->rebuildQueueFrom(
                $date,
                $session,
                $ticket->completed_at->copy()->addMinutes(5),
                $queue
            );

            $this->createSystemNotification(
                $ticket,
                $result === 'completed' ? 'Ticket Completed' : 'Ticket Failed',
                $result === 'completed'
                    ? 'Ticket penginstalan ' . $ticket->ticket_number . ' telah selesai.'
                    : 'Ticket penginstalan ' . $ticket->ticket_number . ' dinyatakan gagal.'
            );

            $ticket->refresh();
            $this->sendCompletionWhatsApp($ticket, $result);
            $this->sendCompletionEmail($ticket, $result);

            $this->syncSessionState($date, $session);
            $this->syncRekapForDate($date);

            return $ticket;
        });
    }

    public function rebuildQueueFrom(
        Carbon $date,
        string $session,
        Carbon $cursorStart,
        int $startingQueueNumber = 1
    ): void {
        $tickets = ticket::with('penginstalan.software')
            ->whereDate('booking_date', $date->toDateString())
            ->where('session', $session)
            ->whereIn('status', ['waiting', 'processing'])
            ->whereNull('deleted_at')
            ->where('scheduled_start', '>=', $cursorStart->toDateTimeString())
            ->orderBy('scheduled_start')
            ->orderBy('id')
            ->get();

        $cursor = $cursorStart->copy();
        $queue  = $startingQueueNumber;

        foreach ($tickets as $ticket) {
            $duration = (int) ($ticket->penginstalan?->software?->estimated_minutes ?? 30);

            $start  = $cursor->copy();
            $end    = $start->copy()->addMinutes($duration);
            $finish = $end->copy()->addMinutes(5);

            $ticket->update([
                'queue_number'     => $queue,
                'scheduled_start'  => $start,
                'scheduled_end'    => $end,
                'estimated_finish' => $finish,
            ]);

            $cursor = $finish->copy();
            $queue++;
        }
    }

    public function syncSessionState(Carbon $date, ?string $session = null): int
    {
        $completedCount = 0;
        $sessions = $session ? [$session] : ['morning', 'afternoon'];
        $now = now();

        foreach ($sessions as $sessionName) {
            $guard = 0;

            while ($guard < 50) {
                $guard++;

                $tickets = ticket::with(['penginstalan.software'])
                    ->whereDate('booking_date', $date->toDateString())
                    ->where('session', $sessionName)
                    ->whereIn('status', ['waiting', 'processing'])
                    ->whereNull('deleted_at')
                    ->orderBy('scheduled_start')
                    ->orderBy('id')
                    ->get();

                if ($tickets->isEmpty()) {
                    break;
                }

                $current = $tickets->first();

                if (! $current || ! $current->scheduled_start || ! $current->scheduled_end) {
                    break;
                }

                $start = Carbon::parse($current->scheduled_start);
                $end   = Carbon::parse($current->scheduled_end);

                if ($now->greaterThanOrEqualTo($end)) {
                    $oldStatus = $current->status;

                    $current->update([
                        'status'       => 'completed',
                        'completed_at' => $now,
                    ]);

                    $this->createSystemNotification(
                        $current,
                        'Ticket Completed',
                        'Ticket penginstalan ' . $current->ticket_number . ' selesai otomatis oleh sistem.'
                    );

                    ticket_status_log::create([
                        'ticket_id'  => $current->id,
                        'old_status' => $oldStatus,
                        'new_status' => 'completed',
                        'note'       => 'Auto completed by system because scheduled end has passed.',
                        'changed_by' => null,
                    ]);

                    if ($current->penginstalan) {
                        $current->penginstalan->update([
                            'installation_result' => 'success',
                        ]);
                    }

                    $current->refresh();
                    $this->sendCompletionWhatsApp($current, 'completed');
                    $this->sendCompletionEmail($current, 'completed');

                    $this->rebuildQueueFrom(
                        $date,
                        $sessionName,
                        $current->completed_at->copy()->addMinutes(5),
                        (int) ($current->queue_number ?? 1)
                    );

                    $completedCount++;
                    continue;
                }

                if ($now->greaterThanOrEqualTo($start) && $now->lessThan($end)) {
                    if ($current->status !== 'processing') {
                        $oldStatus = $current->status;

                        $current->update([
                            'status' => 'processing',
                        ]);

                        $this->createSystemNotification(
                            $current,
                            'Ticket Processing',
                            'Ticket penginstalan ' . $current->ticket_number . ' sedang diproses.'
                        );

                        ticket_status_log::create([
                            'ticket_id'  => $current->id,
                            'old_status' => $oldStatus,
                            'new_status' => 'processing',
                            'note'       => 'Queue is currently being processed.',
                            'changed_by' => null,
                        ]);
                    }

                    break;
                }

                if ($current->status !== 'waiting') {
                    $oldStatus = $current->status;

                    $current->update([
                        'status' => 'waiting',
                    ]);

                    ticket_status_log::create([
                        'ticket_id'  => $current->id,
                        'old_status' => $oldStatus,
                        'new_status' => 'waiting',
                        'note'       => 'Queue is waiting for its turn.',
                        'changed_by' => null,
                    ]);
                }

                break;
            }
        }

        $this->syncRekapForDate($date);

        return $completedCount;
    }

    public function autoCompleteOverdueTickets(): int
    {
        return $this->syncSessionState(Carbon::today());
    }

    private function generateTicketNumber(string $prefix): string
    {
        $date = now()->format('Ymd');
        $lastId = ticket::withTrashed()->max('id') ?? 0;
        $sequence = str_pad((string) ($lastId + 1), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$date}-{$sequence}";
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
        $softwareName = $ticket->penginstalan?->software?->name
            ?? $ticket->penginstalan?->software?->nama
            ?? '-';

        $softwareVersion = $ticket->penginstalan?->software?->version
            ?? $ticket->penginstalan?->software?->versi
            ?? '-';

        $bookingDateText = $ticket->booking_date
            ? Carbon::parse($ticket->booking_date)->translatedFormat('d F Y')
            : '-';

        $completedAtText = $ticket->completed_at
            ? Carbon::parse($ticket->completed_at)->translatedFormat('d F Y H:i')
            : Carbon::now()->translatedFormat('d F Y H:i');

        $sessionText = match ($ticket->session) {
            'morning'   => 'Pagi',
            'afternoon' => 'Siang',
            default     => '-',
        };

        $durationMinutes = (int) ($ticket->penginstalan?->software?->estimated_minutes ?? 0);
        $durationText = $this->formatDurationText($durationMinutes);

        if ($result === 'completed') {
            $opening = 'penginstalan Anda telah selesai dikerjakan';
            $statusText = 'berhasil';
            $closing = 'Silakan datang ke ruang teknisi untuk mengambil perangkat.';
        } else {
            $opening = 'penginstalan Anda mengalami kendala dan dinyatakan gagal';
            $statusText = 'gagal (mengalami kendala)';
            $closing = 'Silakan datang ke ruang teknisi untuk informasi lebih lanjut.';
        }

        return "Halo {$userName}, {$opening}.\n\n"
            . "Berikut data penginstalan Anda:\n"
            . "Nama software: {$softwareName}\n"
            . "Versi: {$softwareVersion}\n"
            . "Ticket Number: {$ticket->ticket_number}\n"
            . "Tanggal Booking: {$bookingDateText}\n"
            . "Sesi: {$sessionText}\n"
            . "Status penginstalan: {$statusText}\n"
            . "Durasi Pengerjaan: {$durationText}\n"
            . "Queue: " . ($ticket->queue_number ?? '-') . "\n\n"
            . "{$closing}\n\n"
            . "QR code tiket terlampir pada pesan ini.\n\n"
            . "untuk informasi lebih lanjut silahkan cek email->folder spam.\n\n"
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

        $ticket->loadMissing(['user', 'penginstalan.software']);

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

        $ticket->loadMissing(['user', 'penginstalan.software']);

        $email = trim((string) ($ticket->user?->email ?? ''));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $subject = $result === 'completed'
            ? 'TechNoteAPP - Penginstalan selesai: ' . $ticket->ticket_number
            : 'TechNoteAPP - Penginstalan gagal: ' . $ticket->ticket_number;

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
