<?php

namespace App\Providers;

use App\Models\Notification;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::defaultView('vendor.pagination.back-next');

        View::composer('template_admin.*', function ($view) {
            if (!Auth::check()) {
                $view->with([
                    'navNotifications' => collect(),
                    'unreadCount' => 0,
                ]);

                return;
            }

            $user = Auth::user();
            $isAdmin = (($user->role->name ?? null) === 'Admin');

            $baseQuery = Notification::with([
                'ticket.user.role',
                'ticket.penginstalan.software',
                'ticket.perbaikan',
            ])
                ->where('type', 'system')
                ->where('is_read', false);

            /*
            Admin:
            melihat semua notifikasi system yang belum dibaca

            Mahasiswa/Dosen:
            hanya notifikasi miliknya / ticket miliknya yang belum dibaca
            */
            if (!$isAdmin) {
                $baseQuery->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                        ->orWhereHas('ticket', function ($ticket) use ($user) {
                            $ticket->where('user_id', $user->id);
                        });
                });
            }

            $navNotifications = (clone $baseQuery)
                ->latest()
                ->take(8)
                ->get();

            $unreadCount = (clone $baseQuery)->count();

            $view->with([
                'navNotifications' => $navNotifications,
                'unreadCount' => $unreadCount,
            ]);
        });
    }
}
