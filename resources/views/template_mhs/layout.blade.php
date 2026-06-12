<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>

    <!-- Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Lucide -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/card.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/popupconfirmation.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/table.css') }}">
    @yield('css')
</head>

<body class="admin-panel">

    <!-- Floating Background -->
    <div class="bg-gradient"></div>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <!-- Sidebar -->
    @include('template_mhs.sidebar')


    <!-- Main -->
    <main class="main">

        <!-- Navbar -->
        @include('template_mhs.nav')

        <!-- Dashboard -->
        <section class="dashboard">
            @yield('content')

        </section>

        <!-- Footer -->
        @include('template_mhs.footer')


    </main>

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

    @yield('js')
    <script src="{{ asset('assets/js/script.js') }}"></script>

</body>

</html>