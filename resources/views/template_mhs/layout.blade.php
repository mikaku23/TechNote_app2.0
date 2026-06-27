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
    <link rel="stylesheet" href="{{ asset('assets/css/mhs-ai-widget.css') }}">
    @yield('css')
</head>

<body class="student-panel">
    <div class="bg-gradient"></div>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

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
                <p class="tn-confirm-message" id="tnConfirmMessage">Are you sure you want to continue?</p>
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

    @include('template_mhs.partials.ai-floating')

    @yield('js')
    <script src="{{ asset('assets/js/script.js') }}"></script>
    <script src="{{ asset('assets/js/mhs-ai-widget.js') }}"></script>
</body>

</html>