@php
$navNotifications = $navNotifications ?? collect();
$unreadCount = $unreadCount ?? 0;
$user = Auth::user();

$foto = $user && $user->avatar && file_exists(public_path('storage/' . $user->avatar))
? asset('storage/' . $user->avatar)
: asset('assets/images/default.png');
@endphp

<header class="navbar glass">
    <div class="navbar-left">
        <button id="sidebarToggle" class="icon-btn" type="button">
            <i data-lucide="menu"></i>
        </button>

        <div class="search-box">
            <i data-lucide="search"></i>
            <input type="text" placeholder="Search...">
        </div>
    </div>

    <div class="navbar-right">
        <div class="notification-wrapper">
            <button id="notificationButton" class="icon-btn notification-trigger" type="button">
                <i data-lucide="bell"></i>

                @if($unreadCount > 0)
                <span class="notification-dot">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                @endif
            </button>

            <div class="notification-dropdown glass" id="notificationDropdown">
                <div class="notification-header">
                    <div>
                        <h4>Notifications</h4>
                        <p>{{ $unreadCount }} unread item(s)</p>
                    </div>

                    <div style="display:flex;gap:8px;align-items:center;">
                        <a href="{{ route('notifications.index') }}" class="notification-view-all">
                            View all
                        </a>

                        <button type="button" class="icon-btn close-modal" id="closeNotificationPanel" aria-label="Close">
                            <i data-lucide="x"></i>
                        </button>
                    </div>
                </div>

                <div class="notification-list">
                    @forelse($navNotifications as $notif)
                    @php
                    $ticket = $notif->ticket;

                    $ticketNumber = $ticket->ticket_number ?? '-';
                    $ticketType = $ticket?->type === 'installation' ? 'Installation' : 'Repair';
                    $userName = $ticket?->user?->name ?? '-';

                    $status = $ticket?->status ?? '-';
                    $statusLabel = match ($status) {
                    'waiting' => 'Waiting',
                    'diagnosis' => 'Diagnosis',
                    'processing' => 'Processing',
                    'testing' => 'Testing',
                    'completed' => 'Completed',
                    'failed' => 'Failed',
                    'cancelled' => 'Cancelled',
                    default => ucfirst($status),
                    };

                    $icon = match ($status) {
                    'waiting' => 'clock-3',
                    'diagnosis' => 'search',
                    'processing' => 'cog',
                    'testing' => 'flask-conical',
                    'completed' => 'check-circle',
                    'failed' => 'x-circle',
                    'cancelled' => 'ban',
                    default => 'bell',
                    };

                    $softwareName = data_get($ticket, 'penginstalan.software.name')
                    ?? data_get($ticket, 'perbaikan.item_name')
                    ?? '-';
                    @endphp

                    <a href="{{ route('notifications.show', $notif->id) }}"
                        class="notification-item {{ $notif->is_read ? '' : 'is-unread' }}">
                        <div class="notification-icon">
                            <i data-lucide="{{ $icon }}"></i>
                        </div>

                        <div class="notification-content">
                            <div class="notification-top">
                                <strong>{{ $notif->title }}</strong>
                                <span class="tn-badge tn-badge-secondary">{{ $statusLabel }}</span>
                            </div>

                            <p>{{ $notif->message }}</p>

                            <div class="notification-meta">
                                <span>Ticket: {{ $ticketNumber }}</span>
                                <span>•</span>
                                <span>{{ $ticketType }}</span>
                                <span>•</span>
                                <span>User: {{ $userName }}</span>
                            </div>

                            <div class="notification-meta notification-meta-bottom">
                                <span>Software: {{ $softwareName }}</span>
                                <span>•</span>
                                <span>{{ $notif->created_at?->diffForHumans() ?? '-' }}</span>
                            </div>
                        </div>
                    </a>
                    @empty
                    <div class="notification-empty">
                        <div class="notification-empty-icon">
                            <i data-lucide="bell-off"></i>
                        </div>

                        <strong>No notifications available</strong>
                        <p>System notifications will appear here when a ticket is created or updated.</p>
                    </div>
                    @endforelse
                </div>

                <div class="notification-footer">
                    <a href="{{ route('notifications.index') }}">View all notifications</a>
                </div>
            </div>
        </div>

        <button id="themeToggle" class="icon-btn" type="button">
            <i data-lucide="moon"></i>
        </button>

        @if(Auth::check())
        <div class="profile-wrapper">
            <div class="profile" id="profileMenuButton">
                <img src="{{ $foto }}" class="avatar rounded-circle me-2" alt="profile">

                <div>
                    <h6 class="mb-0 caption-title">{{ $user->name }}</h6>
                    <p class="mb-0 caption-sub-title text-muted">
                        {{ $user->role->name ?? '-' }}
                    </p>
                </div>
            </div>

            <div class="profile-dropdown glass" id="profileDropdown">
                <button type="button" class="profile-dropdown-link open-modal" data-url="{{ route('profile.show') }}">
                    <i data-lucide="user"></i>
                    <span>View Profile</span>
                </button>

                <button type="button" class="profile-dropdown-link open-modal" data-url="{{ route('settings.show') }}">
                    <i data-lucide="settings"></i>
                    <span>Settings</span>
                </button>

                <hr>

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="profile-dropdown-link logout">
                        <i data-lucide="log-out"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
        @endif
    </div>
</header>