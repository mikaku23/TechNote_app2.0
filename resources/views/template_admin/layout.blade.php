<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/card.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/popupconfirmation.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/table.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/ai-widget.css') }}">

    @yield('css')

    <style>
        .table-actions {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .btn-icon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: .25s ease;
            backdrop-filter: blur(10px);
        }

        .btn-icon svg {
            width: 18px;
            height: 18px;
        }

        .btn-icon.secondary {
            background: rgba(255, 255, 255, .08);
            color: #fff;
        }

        .btn-icon.success {
            background: rgba(34, 197, 94, .18);
            color: #22c55e;
        }

        .btn-icon.danger {
            background: rgba(239, 68, 68, .18);
            color: #ef4444;
        }

        .btn-icon:hover:not(.disabled-action) {
            transform: translateY(-2px);
        }

        .disabled-action {
            opacity: .35;
            filter: grayscale(1);
            cursor: default !important;
            pointer-events: none;
        }
    </style>
</head>

<body class="admin-panel">

    <div class="bg-gradient"></div>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    @include('template_admin.sidebar')

    <main class="main">
        @include('template_admin.nav')

        <section class="dashboard">
            @yield('content')
        </section>

        @include('template_admin.footer')
    </main>

    <div id="modalContainer"></div>

    <div class="tn-confirm-overlay" id="tnConfirmOverlay" aria-hidden="true">
        <div class="glass card tn-confirm-card" role="dialog" aria-modal="true" aria-labelledby="tnConfirmTitle">
            <div class="tn-confirm-header">
                <div class="tn-confirm-icon-wrap" id="tnConfirmIconWrap">
                    <i data-lucide="alert-triangle" id="tnConfirmIcon"></i>
                </div>

                <button type="button" class="icon-btn tn-confirm-close" id="tnConfirmClose">
                    <i data-lucide="x"></i>
                </button>
            </div>

            <div class="tn-confirm-body">
                <h2 class="tn-confirm-title" id="tnConfirmTitle">Confirmation</h2>
                <p class="tn-confirm-message" id="tnConfirmMessage">
                    Are you sure you want to continue?
                </p>
            </div>

            <div class="tn-confirm-actions">
                <button type="button" class="btn-secondary" id="tnConfirmCancel">
                    <i data-lucide="x"></i>
                    Cancel
                </button>

                <button type="button" class="btn-primary" id="tnConfirmProceed">
                    <i data-lucide="check"></i>
                    Continue
                </button>
            </div>
        </div>
    </div>

    @php
    $isAiIndex = request()->routeIs('admin.ai.index');
    @endphp

    @if(Auth::check() && Auth::user()->role?->name === 'Admin' && ! $isAiIndex)
    @include('template_admin.partials.ai-floating')
    @endif

    <script src="{{ asset('assets/js/script.js') }}"></script>
    <script src="{{ asset('assets/js/ai-widget.js') }}"></script>

    @yield('js')
</body>

</html>