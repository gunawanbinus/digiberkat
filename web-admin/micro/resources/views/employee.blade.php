<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title')</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Custom Admin CSS -->
    <link href="{{ asset('admin/css/styles.css') }}" rel="stylesheet" />

    <!-- Icon Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.3.0/css/all.min.css" />

    {{-- Optional: Add any page-specific styles here if needed --}}
    @yield('styles')
</head>
<body class="sb-nav-fixed">
    {{-- Include Navbar partial --}}
    @include('layouts.partials.navbar')

    <div id="layoutSidenav">
        {{-- Include Sidebar for Employee partial --}}
        @include('layouts.partials.sidebaremployee')

        <div id="layoutSidenav_content">
            <main>
                <div class="container-fluid px-4">
                    {{-- Content from specific views will be injected here --}}
                    @yield('content')
                </div>
            </main>

            {{-- Include Footer partial --}}
            @include('layouts.partials.footer')
        </div>
    </div>

    <!-- JavaScript Libraries -->
    <!-- Load jQuery first as it's often a dependency for other scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <!-- Bootstrap Bundle with Popper for Bootstrap JS functionalities -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>

    <!-- html5-qrcode library for QR scanning functionality -->
    {{-- IMPORTANT: Moved this script here to ensure it's loaded before any custom scripts that might use it --}}
    {{-- <script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode/minified/html5-qrcode.min.js"></script> --}}
   <script src="{{ asset('js/html5-qrcode.min.js') }}"></script>

    <!-- Admin Custom JS files -->
    <script src="{{ asset('admin/js/scripts.js') }}"></script>
    <script src="{{ asset('admin/js/datatables-simple-demo.js') }}"></script>

    <!-- Section for page-specific scripts -->
    {{-- Scripts defined in child views (e.g., employee.dashboard) will be injected here --}}
    @yield('scripts')
</body>
</html>
