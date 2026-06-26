<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function admin()
    {
        $admin = Auth::user();
        abort_unless($admin, 403);

        $now = now();
        $today = $now->copy()->startOfDay();

        $roleCount = $this->safeCount('roles');

        $softwareCount = $this->safeCount('softwares');
        if ($softwareCount === 0) {
            $softwareCount = $this->safeCount('software');
        }

        $onlineCount = $this->countUsersByLoginStatus('online');
        $offlineCount = $this->countUsersByLoginStatus('offline');

        $ticketTotal = $this->safeCount('tickets');
        $ticketWaiting = $this->safeCountWhere('tickets', 'status', 'waiting');
        $ticketProcessing = $this->safeCountWhere('tickets', 'status', 'processing');
        $ticketCompleted = $this->safeCountWhere('tickets', 'status', 'completed');
        $ticketFailed = $this->safeCountWhere('tickets', 'status', 'failed');

        $maintenanceActive = $this->tableExists('maintenances')
            ? DB::table('maintenances')->where('is_active', 1)->exists()
            : false;

        $antiAiMode = $this->getSystemSettingValue('ai_admin_anti_mode', '0') === '1';

        // Ambil data 1 bulan penuh, lalu di JS ditampilkan mingguan/bulanan
        $ticketChart = $this->buildTicketTrendChart($now);

        $sessionCards = [
            $this->buildSessionCard($now->copy(), 'morning', '08:00', '11:00', 12),
            $this->buildSessionCard($now->copy(), 'afternoon', '14:00', '17:00', 12),
        ];

        $recentUserActivities = $this->recentUserActivities();
        $recentLoginLogs = $this->recentLoginLogs();
        $recentAiLogs = $this->recentAiLogs();

        $todayTicketCount = $this->tableExists('tickets')
            ? DB::table('tickets')->whereDate('created_at', $today->toDateString())->count()
            : 0;

        $todayUserActivityCount = $this->tableExists('user_activities')
            ? DB::table('user_activities')->whereDate('created_at', $today->toDateString())->count()
            : 0;

        $todayAiLogCount = $this->tableExists('ai_logs')
            ? DB::table('ai_logs')->whereDate('created_at', $today->toDateString())->count()
            : 0;

        return view('admin.index', [
            'menu' => 'dashboardAdmin',
            'title' => 'Admin Dashboard',

            'adminName' => $admin->name ?? 'Administrator',
            'adminUsername' => $admin->username ?? '-',
            'adminEmail' => $admin->email ?? '-',
            'adminRole' => $admin->role->name ?? 'Admin',
            'adminInitial' => strtoupper(mb_substr((string) ($admin->name ?? 'A'), 0, 1)),
            'serverTime' => $now->format('H:i'),
            'todayLabel' => $now->translatedFormat('l, d F Y'),

            'roleCount' => $roleCount,
            'softwareCount' => $softwareCount,
            'onlineCount' => $onlineCount,
            'offlineCount' => $offlineCount,

            'ticketTotal' => $ticketTotal,
            'ticketWaiting' => $ticketWaiting,
            'ticketProcessing' => $ticketProcessing,
            'ticketCompleted' => $ticketCompleted,
            'ticketFailed' => $ticketFailed,

            'maintenanceActive' => $maintenanceActive,
            'antiAiMode' => $antiAiMode,

            'ticketChartLabels' => $ticketChart['labels'],
            'ticketInstallationChart' => $ticketChart['installation'],
            'ticketRepairChart' => $ticketChart['repair'],

            'sessionCards' => $sessionCards,

            'recentUserActivities' => $recentUserActivities,
            'recentLoginLogs' => $recentLoginLogs,
            'recentAiLogs' => $recentAiLogs,

            'todayTicketCount' => $todayTicketCount,
            'todayUserActivityCount' => $todayUserActivityCount,
            'todayAiLogCount' => $todayAiLogCount,
        ]);
    }

    private function buildTicketTrendChart(Carbon $reference): array
    {
        $year = $reference->year;
        $month = $reference->month;
        $daysInMonth = $reference->daysInMonth;

        $lookup = [];

        if ($this->tableExists('tickets')) {
            $raw = DB::table('tickets')
                ->selectRaw('DATE(created_at) as day, type, COUNT(*) as total')
                ->whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->groupByRaw('DATE(created_at), type')
                ->get();

            foreach ($raw as $row) {
                $dayKey = (string) $row->day;
                $type = (string) $row->type;

                if (!isset($lookup[$dayKey])) {
                    $lookup[$dayKey] = [];
                }

                $lookup[$dayKey][$type] = (int) $row->total;
            }
        }

        $labels = [];
        $installation = [];
        $repair = [];

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $dateKey = Carbon::create($year, $month, $day)->toDateString();

            $labels[] = (string) $day;
            $installation[] = (int) data_get($lookup, "{$dateKey}.installation", 0);
            $repair[] = (int) data_get($lookup, "{$dateKey}.repair", 0);
        }

        return [
            'labels' => $labels,
            'installation' => $installation,
            'repair' => $repair,
        ];
    }

    private function buildSessionCard(Carbon $now, string $key, string $startTime, string $endTime, int $capacity): array
    {
        $start = $now->copy()->setTimeFromTimeString($startTime);
        $end = $now->copy()->setTimeFromTimeString($endTime);

        $status = 'upcoming';
        if ($now->betweenIncluded($start, $end)) {
            $status = 'active';
        } elseif ($now->greaterThan($end)) {
            $status = 'ended';
        }

        $totalMinutes = max(1, $start->diffInMinutes($end));
        $elapsedMinutes = 0;
        $remainingMinutes = 0;

        if ($status === 'active') {
            $elapsedMinutes = max(0, $start->diffInMinutes($now));
            $remainingMinutes = max(0, $now->diffInMinutes($end));
        } elseif ($status === 'upcoming') {
            $remainingMinutes = max(0, $now->diffInMinutes($start));
        } else {
            $elapsedMinutes = $totalMinutes;
        }

        $timeProgress = $status === 'active'
            ? min(100, (int) round(($elapsedMinutes / $totalMinutes) * 100))
            : ($status === 'ended' ? 100 : 0);

        $ticketCount = $this->tableExists('tickets')
            ? DB::table('tickets')
            ->whereBetween('created_at', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->count()
            : 0;

        $bookingProgress = min(100, (int) round(($ticketCount / max(1, $capacity)) * 100));
        $capacityRemaining = max(0, $capacity - $ticketCount);

        $alertLevel = 'neutral';
        if ($status === 'active') {
            if ($bookingProgress >= 90) {
                $alertLevel = 'danger';
            } elseif ($bookingProgress >= 70) {
                $alertLevel = 'warning';
            } else {
                $alertLevel = 'capacity';
            }
        }

        return [
            'badge' => $key === 'morning' ? 'Session 1' : 'Session 2',
            'label' => $key === 'morning' ? 'Pagi' : 'Sore',
            'range' => "{$startTime} - {$endTime}",
            'status' => $status,
            'time_label' => $status === 'active'
                ? 'Waktu berjalan'
                : ($status === 'upcoming' ? 'Menuju sesi' : 'Sesi selesai'),
            'time_remaining_label' => $status === 'active'
                ? $this->humanizeMinutes($remainingMinutes) . ' tersisa'
                : ($status === 'upcoming' ? $this->humanizeMinutes($remainingMinutes) . ' menuju sesi' : 'Beku'),
            'capacity_label' => "Ticket terpakai: {$ticketCount}",
            'capacity_remaining_label' => "{$capacityRemaining} slot tersisa",
            'time_progress_percent' => $timeProgress,
            'booking_progress_percent' => $bookingProgress,
            'ticket_count' => $ticketCount,
            'accept_until' => $end->copy()->subMinutes(5)->format('H:i'),
            'alert_level' => $alertLevel,
        ];
    }

    private function recentUserActivities(int $limit = 3)
    {
        if (! $this->tableExists('user_activities')) {
            return collect();
        }

        return DB::table('user_activities')
            ->leftJoin('users', 'users.id', '=', 'user_activities.user_id')
            ->select('user_activities.*', 'users.name as user_name', 'users.username as user_username')
            ->orderByDesc('user_activities.created_at')
            ->orderByDesc('user_activities.id')
            ->limit($limit)
            ->get();
    }

    private function recentLoginLogs(int $limit = 3)
    {
        if (! $this->tableExists('login_logs')) {
            return collect();
        }

        return DB::table('login_logs')
            ->leftJoin('users', 'users.id', '=', 'login_logs.user_id')
            ->select('login_logs.*', 'users.name as user_name', 'users.username as user_username')
            ->orderByDesc('login_logs.created_at')
            ->orderByDesc('login_logs.id')
            ->limit($limit)
            ->get();
    }

    private function recentAiLogs(int $limit = 3)
    {
        if (! $this->tableExists('ai_logs')) {
            return collect();
        }

        return DB::table('ai_logs')
            ->leftJoin('users', 'users.id', '=', 'ai_logs.user_id')
            ->select('ai_logs.*', 'users.name as user_name', 'users.username as user_username')
            ->orderByDesc('ai_logs.created_at')
            ->orderByDesc('ai_logs.id')
            ->limit($limit)
            ->get();
    }

    private function countUsersByLoginStatus(string $status): int
    {
        if (! $this->tableExists('login_logs')) {
            return 0;
        }

        return (int) DB::table('login_logs')
            ->where('status', $status)
            ->distinct('user_id')
            ->count('user_id');
    }

    private function safeCount(string $table): int
    {
        return $this->tableExists($table)
            ? (int) DB::table($table)->count()
            : 0;
    }

    private function safeCountWhere(string $table, string $column, $value): int
    {
        return $this->tableExists($table)
            ? (int) DB::table($table)->where($column, $value)->count()
            : 0;
    }

    private function tableExists(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function getSystemSettingValue(string $key, string $default = ''): string
    {
        if (! $this->tableExists('system_settings')) {
            return $default;
        }

        $value = DB::table('system_settings')->where('key', $key)->value('value');

        return $value !== null ? (string) $value : $default;
    }

    private function humanizeMinutes(int $minutes): string
    {
        $minutes = max(0, $minutes);
        $hours = intdiv($minutes, 60);
        $remain = $minutes % 60;

        if ($hours > 0 && $remain > 0) {
            return $hours . 'j ' . $remain . 'm';
        }

        if ($hours > 0) {
            return $hours . 'j';
        }

        return $remain . 'm';
    }

    public function mahasiswa()
    {
        return view('mhs.index', [
            'menu' => 'dashboardMhs',
            'title' => 'Mahasiswa Dashboard',
        ]);
    }
}
