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
    <style>
        /* =================================
   CRUD GLOBAL COMPONENTS
================================= */

        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 30px;

            background: rgba(0, 0, 0, .25);

            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);

            animation: fadeIn .25s ease;
        }

        .modal-card {
            width: 100%;
            max-width: 750px;

            max-height: 90vh;
            overflow-y: auto;

            border-radius: 32px;
            padding: 28px;

            animation: modalIn .3s ease;
        }

        .modal-card::-webkit-scrollbar {
            width: 6px;
        }

        .modal-card::-webkit-scrollbar-thumb {
            background: rgba(127, 127, 127, .25);
            border-radius: 999px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 600;
        }

        .form-control {
            width: 100%;

            border: none;
            outline: none;

            padding: 14px 18px;

            border-radius: 16px;

            background: rgba(255, 255, 255, .20);

            color: var(--text);

            transition: var(--transition);

            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }

        .form-control:focus {
            box-shadow:
                0 0 0 2px rgba(91, 92, 235, .25);
        }

        textarea.form-control {
            resize: none;
            min-height: 120px;
        }

        .dark .form-control {
            background: rgba(255, 255, 255, .05);
        }

        .table-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }

        .table-actions {
            display: flex;
            gap: 8px;
        }

        .pagination-wrapper {
            margin-top: 25px;

            display: flex;
            justify-content: center;
        }

        .pagination-wrapper nav {
            display: flex;
            gap: 10px;
        }

        .pagination-wrapper svg {
            width: 18px;
            height: 18px;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes modalIn {
            from {
                opacity: 0;
                transform:
                    translateY(20px) scale(.96);
            }

            to {
                opacity: 1;
                transform:
                    translateY(0) scale(1);
            }
        }

        @media(max-width:768px) {

            .form-grid {
                grid-template-columns: 1fr;
            }

            .modal-card {
                padding: 22px;
            }

        }
    </style>
    @yield('css')
</head>

<body class="admin-panel">

    <!-- Floating Background -->
    <div class="bg-gradient"></div>
    <div class="blob blob-1"></div>
    <div class="blob blob-2"></div>
    <div class="blob blob-3"></div>

    <!-- Sidebar -->
    @include('template_admin.sidebar')


    <!-- Main -->
    <main class="main">

        <!-- Navbar -->
        @include('template_admin.nav')

        <!-- Dashboard -->
        <section class="dashboard">
            @yield('content')

        </section>

        <!-- Footer -->
        @include('template_admin.footer')


    </main>

    @yield('js')
    <script src="{{ asset('assets/js/script.js') }}"></script>

</body>

</html>