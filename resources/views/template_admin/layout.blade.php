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
        @yield('css')
</head>

<body>

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
          <h1>p apah</h1>
          
        </section>

        <!-- Footer -->
        @include('template_admin.footer')
        

    </main>

    @yield('js')
    <script src="{{ asset('assets/js/script.js') }}"></script>

</body>

</html>