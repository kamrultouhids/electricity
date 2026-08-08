<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('logo.png')}}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Scripts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --app-body-bg: #F0F4F8;
            --app-nav-bg: #1E3A5F;
            --app-primary: #2563EB;
            --app-primary-light: #3B82F6;
            --app-primary-dark: #1D4ED8;
            --bs-link-color: var(--app-primary);
            --bs-link-hover-color: var(--app-primary-dark);
        }

        body { background-color: var(--app-body-bg); }

        /* ===== Navbar ===== */
        .navbar {
            background-color: var(--app-nav-bg) !important;
        }
        .navbar .navbar-brand,
        .navbar .navbar-nav .nav-link {
            color: rgba(255,255,255,.82) !important;
        }
        .navbar .navbar-brand { font-weight: 700; color: #fff !important; }
        .navbar .navbar-nav .nav-link:hover,
        .navbar .navbar-nav .nav-link:focus {
            color: #fff !important;
        }
        .navbar-nav .nav-link.active {
            color: #fff !important;
            font-weight: 600;
        }
        .navbar .navbar-toggler {
            border-color: rgba(255,255,255,.35);
        }
        .navbar .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255,255,255,0.85)' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        /* ===== Primary button ===== */
        .btn-primary {
            background-color: #3061B3 !important;
            border-color: #3061B3 !important;
            box-shadow: 0 2px 6px rgba(48,97,179,.28);
        }
        .btn-primary:hover,
        .btn-primary:focus,
        .btn-primary:active {
            background-color: #274f93 !important;
            border-color: #274f93 !important;
            box-shadow: 0 4px 10px rgba(48,97,179,.35);
        }

        /* ===== Outline / link accents ===== */
        .btn-outline-primary {
            color: var(--app-primary);
            border-color: var(--app-primary);
        }
        .btn-outline-primary:hover,
        .btn-outline-primary:active {
            background-color: var(--app-primary);
            border-color: var(--app-primary);
            color: #fff;
        }
        a { color: var(--app-primary); }
        a:hover { color: var(--app-primary-dark); }
        .text-primary { color: var(--app-primary) !important; }

        /* ===== Dropdown active items ===== */
        .dropdown-item.active,
        .dropdown-item:active {
            background-color: var(--app-primary);
            color: #fff;
        }

        /* ===== Pagination ===== */
        .page-link { color: var(--app-primary); }
        .page-item.active .page-link {
            background-color: var(--app-primary);
            border-color: var(--app-primary);
        }

        /* ===== Primary badge ===== */
        .badge.bg-primary {
            background-color: #3061B3 !important;
            color: #fff !important;
            border-radius: 50rem !important;
            padding: .35em .7em !important;
        }

        /* ===== List tables (all list pages, not the dashboard) ===== */
        .list-card {
            border: 1px solid #e3e7ee;
            box-shadow: 0 1px 3px rgba(16,24,40,.05);
            overflow: hidden;            /* clip the table to the card's rounded corners */
        }
        .list-card .list-head th {
            background-color: #EEF5FF;
            border-bottom: 1px solid #dbe6f5;
            color: #1f2937;
            font-weight: 600;
            font-size: 14px;
        }
        .list-card tbody td { font-size: 14px; }

        @media print {
            body { background: #fff !important; }
            .no-print { display: none !important; }
            .navbar, nav { display: none !important; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-dark shadow-sm">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    {{ config('app.name', 'Laravel') }}
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">

                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">{{ __('Login') }}</a>
                                </li>
                            @endif
                        @else
                             <li class="nav-item">
                                <a class="nav-link {{ Route::is('home') ? 'active' : '' }}" href="{{ route('home') }}"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
                            </li>
                             <li class="nav-item">
                                <a class="nav-link  {{ Route::is('users.index') ? 'active' : '' }}" href="{{ route('users.index') }}"><i class="bi bi-people me-1"></i>User List</a>
                            </li>
                            <li class="nav-item dropdown">
                                <a id="electricityDropdown" class="nav-link dropdown-toggle {{ Route::is('customers.*') || Route::is('meter-readings.*') || Route::is('bills.*') || Route::is('payments.*') || Route::is('tariffs.*') || Route::is('expenses.*') || Route::is('expense-categories.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                   <i class="bi bi-lightning-charge me-1"></i>Electricity management
                                </a>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="electricityDropdown">
                                    <a class="dropdown-item {{ Route::is('tariffs.index') ? 'active' : '' }}" href="{{ route('tariffs.index') }}">Rate Settings</a>
                                    <a class="dropdown-item {{ Route::is('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}">Customer List</a>
                                    <a class="dropdown-item {{ Route::is('meter-readings.*') ? 'active' : '' }}" href="{{ route('meter-readings.index') }}">Meter Readings</a>
                                    <a class="dropdown-item {{ Route::is('bills.pending') ? 'active' : '' }}" href="{{ route('bills.pending') }}">Pending Billing Readings</a>
                                    <a class="dropdown-item {{ (Route::is('bills.*') && ! Route::is('bills.pending')) ? 'active' : '' }}" href="{{ route('bills.index') }}">Bills</a>
                                    <a class="dropdown-item {{ Route::is('payments.due') ? 'active' : '' }}" href="{{ route('payments.due') }}">Due List</a>
                                    <a class="dropdown-item {{ Route::is('payments.index') || Route::is('payments.receipt') ? 'active' : '' }}" href="{{ route('payments.index') }}">Payments</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item {{ Route::is('expense-categories.*') ? 'active' : '' }}" href="{{ route('expense-categories.index') }}">Expense Categories</a>
                                    <a class="dropdown-item {{ Route::is('expenses.index') || Route::is('expenses.create') || Route::is('expenses.edit') ? 'active' : '' }}" href="{{ route('expenses.index') }}">Expenses</a>
                                    <a class="dropdown-item {{ Route::is('expenses.profit-loss') ? 'active' : '' }}" href="{{ route('expenses.profit-loss') }}">Profit &amp; Loss</a>
                                </div>
                            </li>
                            <li class="nav-item dropdown">
                                <a id="reportDropdown" class="nav-link dropdown-toggle {{ Route::is('reports.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                   <i class="bi bi-bar-chart-line me-1"></i>Report management
                                </a>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="reportDropdown">
                                    <a class="dropdown-item {{ Route::is('reports.daily-collection') ? 'active' : '' }}" href="{{ route('reports.daily-collection') }}">Daily Collection</a>
                                    <a class="dropdown-item {{ Route::is('reports.monthly-collection') ? 'active' : '' }}" href="{{ route('reports.monthly-collection') }}">Monthly Collection</a>
                                    <a class="dropdown-item {{ Route::is('reports.customers') ? 'active' : '' }}" href="{{ route('reports.customers') }}">Customer Report</a>
                                    <a class="dropdown-item {{ Route::is('reports.unit-consumption') ? 'active' : '' }}" href="{{ route('reports.unit-consumption') }}">Unit Consumption</a>
                                    <a class="dropdown-item {{ Route::is('reports.meter-not-read') ? 'active' : '' }}" href="{{ route('reports.meter-not-read') }}">Meter Not Read</a>
                                    <a class="dropdown-item {{ Route::is('reports.outstanding') ? 'active' : '' }}" href="{{ route('reports.outstanding') }}">Outstanding Balance</a>
                                    <a class="dropdown-item {{ Route::is('reports.income-expense') ? 'active' : '' }}" href="{{ route('reports.income-expense') }}">Income &amp; Expense</a>
                                </div>
                            </li>
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle {{ Route::is('password.change') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    <i class="bi bi-person-circle me-1"></i>{{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <a href="{{ route('password.change') }}" class="dropdown-item {{ Route::is('password.change') ? 'active' : '' }}">Change Password</a>

                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        {{ __('Logout') }}
                                    </a>

                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            @yield('content')
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    @stack('scripts')
</body>
</html>
