<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('logo.png') }}">
    <title>Customer Portal — {{ config('app.name', 'Electricity') }}</title>

    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root { --app-nav-bg: #1E3A5F; --app-primary: #3061B3; }
        body { background-color: #F0F4F8; }
        .navbar { background-color: var(--app-nav-bg) !important; }
        .navbar .navbar-brand, .navbar .nav-link { color: #fff !important; }
        .btn-primary { background-color: var(--app-primary) !important; border-color: var(--app-primary) !important; }
        .card { border: 1px solid #e3e7ee; box-shadow: 0 1px 3px rgba(16,24,40,.05); }
        .rounded-4 { border-radius: 1rem !important; }
        .chart-box { position: relative; height: 300px; }
        .info-avatar { width: 90px; height: 90px; border-radius: 50%; object-fit: cover; background: #eef2f7; }
    </style>
    @stack('styles')
</head>
<body>
    @auth('customer')
        <nav class="navbar navbar-expand-md shadow-sm">
            <div class="container">
                <a class="navbar-brand fw-bold" href="{{ route('portal.dashboard') }}">
                    <i class="bi bi-lightning-charge-fill me-1"></i>Customer Portal
                </a>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-white small d-none d-sm-inline">
                        <i class="bi bi-person-circle me-1"></i>{{ Auth::guard('customer')->user()->name }}
                    </span>
                    <form method="POST" action="{{ route('portal.logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-light"><i class="bi bi-box-arrow-right me-1"></i>Logout</button>
                    </form>
                </div>
            </div>
        </nav>
    @endauth

    <main class="py-4">
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
