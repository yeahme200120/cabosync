<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'CaboSync') - Sistema de Gestión</title>

    {{-- Bootstrap 5 --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap5.min.css" rel="stylesheet">

    {{-- 🎨 CSS GLOBAL CABOSYNC (Vite) --}}
    @vite(['resources/css/app.css', 'resources/js/app.js','resources/js/geo.js'])

    @stack('styles')
</head>

<body>

    {{-- HEADER --}}
    <nav class="navbar navbar-cabosync d-flex align-items-center">
        <button class="btn-menu" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu">
            <i class="bi bi-list"></i>
        </button>

        <a class="navbar-brand ms-3 d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
            <img src="{{ asset('img/logo-cabosync.png') }}" alt="CaboSync"
                style="height: 45px; width: auto; max-height: 45px;">
            <span>CABOSYNC - DASHBOARD</span>
        </a>

        <div class="ms-auto d-flex align-items-center gap-3">
            <span class="text-white small d-none d-md-inline">
                <i class="bi bi-person-circle"></i>
                {{ auth()->user()->nombre ?? 'Invitado' }}
                @if (auth()->check())
                    <span class="badge bg-secondary ms-1">{{ auth()->user()->rol?->nombre ?? '' }}</span>
                @endif
            </span>
            @auth
                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-light">
                        <i class="bi bi-box-arrow-right"></i> Salir
                    </button>
                </form>
            @endauth
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3 col-lg-2 d-none d-md-block sidebar-cabosync p-0">
                @include('layouts.partials.sidebar')
            </div>

            <main class="col-md-9 col-lg-10 py-4 px-4">
                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show">
                        <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarMenu">
        <div class="offcanvas-header bg-cabosync-primary text-white">
            <h5 class="offcanvas-title">
                <img src="{{ asset('img/logo-cabosync.png') }}" alt="CaboSync" width="28" height="28">
                CaboSync
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            @include('layouts.partials.sidebar')
        </div>
    </div>

    {{-- PRELOADER GLOBAL --}}
    @include('partials.preloader')

    {{-- SCRIPTS --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    @stack('scripts')
</body>

</html>