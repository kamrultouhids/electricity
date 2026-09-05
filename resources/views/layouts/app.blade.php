<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('logo.png')}}">

    <title>{{ config('app.name', 'Laravel') }}@hasSection('title') : @yield('title')@endif</title>

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

        /* ===== Mobile menu (slide-in panel below md) ===== */
        @media (max-width: 767.98px) {
            .navbar .offcanvas {
                background-color: var(--app-nav-bg);
                width: min(86vw, 340px);
                border-left: 0;
            }
            .navbar .offcanvas-header {
                border-bottom: 1px solid rgba(255,255,255,.14);
                padding: 1rem 1.25rem;
            }
            .navbar .offcanvas-title { color: #fff; font-weight: 700; }
            .navbar .offcanvas-body { padding: .75rem 1rem 1.5rem; }

            /* Roomier tap targets, and a clear press state on a dark panel. */
            .navbar .offcanvas .nav-link {
                padding: .7rem .75rem;
                border-radius: .6rem;
                font-size: 1rem;
            }
            .navbar .offcanvas .nav-link:hover,
            .navbar .offcanvas .nav-link:focus,
            .navbar .offcanvas .nav-link[aria-expanded="true"] {
                background-color: rgba(255,255,255,.08);
            }
            .navbar .offcanvas .nav-link.active {
                background-color: rgba(255,255,255,.16);
            }
            /* The caret sits at the far edge so the whole row reads as one control. */
            .navbar .offcanvas .nav-link.dropdown-toggle {
                display: flex;
                align-items: center;
            }
            .navbar .offcanvas .nav-link.dropdown-toggle::after {
                margin-left: auto;
                transition: transform .18s ease;
            }
            .navbar .offcanvas .nav-link.dropdown-toggle[aria-expanded="true"]::after {
                transform: rotate(180deg);
            }

            /* Submenu reads as a nested group, not a floating card. */
            .navbar .offcanvas .dropdown-menu {
                background-color: transparent;
                border: 0;
                box-shadow: none;
                padding: .15rem 0 .35rem;
                margin: 0 0 .25rem;
                border-left: 2px solid rgba(255,255,255,.16);
                margin-left: 1.15rem;
            }
            .navbar .offcanvas .dropdown-item {
                color: rgba(255,255,255,.75);
                padding: .6rem .85rem;
                margin: .1rem .35rem;
                border-radius: .5rem;
                white-space: normal;
            }
            .navbar .offcanvas .dropdown-item:hover,
            .navbar .offcanvas .dropdown-item:focus {
                background-color: rgba(255,255,255,.08);
                color: #fff;
            }
            .navbar .offcanvas .dropdown-item.active,
            .navbar .offcanvas .dropdown-item:active {
                background-color: rgba(255,255,255,.18);
                color: #fff;
            }
            .navbar .offcanvas .dropdown-divider {
                border-top-color: rgba(255,255,255,.16);
                margin: .5rem .85rem;
            }
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
            /* Zero page margin so the browser drops its own header/footer
               (date, title, page URL). The paper margin moves onto the body. */
            @page { size: A4 portrait; margin: 0; }
            body {
                background: #fff !important;
                box-sizing: border-box !important;
                width: auto !important;
                padding: 12mm !important;
            }
            .no-print { display: none !important; }
            .navbar, nav { display: none !important; }

            /* Flatten list cards for clean printing */
            .list-card {
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
                overflow: visible !important;
            }
            /* No head background colour in print */
            .list-card .list-head th {
                background-color: transparent !important;
                border-bottom: 1px solid #444 !important;
                color: #000 !important;
            }
            /* Bordered, readable table on paper */
            .list-card table.table th,
            .list-card table.table td {
                border: 1px solid #444 !important;
                padding: 4px 6px !important;
            }
            .list-card table.table thead { display: table-header-group; }
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
                {{-- Below md the menu is a slide-in panel; from md up it is the ordinary inline navbar. --}}
                <button class="navbar-toggler" type="button" data-bs-toggle="offcanvas" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="offcanvas offcanvas-end offcanvas-md" tabindex="-1" id="navbarSupportedContent" aria-labelledby="navbarSupportedContentLabel">
                    <div class="offcanvas-header d-md-none">
                        <h5 class="offcanvas-title" id="navbarSupportedContentLabel">{{ config('app.name', 'Laravel') }}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#navbarSupportedContent" aria-label="{{ __('Close') }}"></button>
                    </div>
                    <div class="offcanvas-body">
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
                            @can('manage-users')
                             <li class="nav-item">
                                <a class="nav-link  {{ Route::is('users.index') ? 'active' : '' }}" href="{{ route('users.index') }}"><i class="bi bi-people me-1"></i>User List</a>
                            </li>
                            @endcan
                            <li class="nav-item dropdown">
                                <a id="electricityDropdown" class="nav-link dropdown-toggle {{ Route::is('customers.*') || Route::is('meter-readings.*') || Route::is('bills.*') || Route::is('payments.*') || Route::is('tariffs.*') || Route::is('expenses.*') || Route::is('expense-categories.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                   <i class="bi bi-lightning-charge me-1"></i>Electricity management
                                </a>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="electricityDropdown">
                                    @can('rate-settings')
                                    <a class="dropdown-item {{ Route::is('tariffs.index') ? 'active' : '' }}" href="{{ route('tariffs.index') }}"><i class="bi bi-sliders me-2"></i>Rate Settings</a>
                                    @endcan
                                    <a class="dropdown-item {{ Route::is('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}"><i class="bi bi-people me-2"></i>Customer List</a>
                                    @can('access-meter-readings')
                                    <a class="dropdown-item {{ Route::is('meter-readings.*') ? 'active' : '' }}" href="{{ route('meter-readings.index') }}"><i class="bi bi-speedometer2 me-2"></i>Meter Readings</a>
                                    @endcan
                                    @can('generate-bills')
                                    <a class="dropdown-item {{ Route::is('bills.pending') ? 'active' : '' }}" href="{{ route('bills.pending') }}"><i class="bi bi-hourglass-split me-2"></i>Pending Billing Readings</a>
                                    @endcan
                                    @can('view-bills')
                                    <a class="dropdown-item {{ (Route::is('bills.*') && ! Route::is('bills.pending')) ? 'active' : '' }}" href="{{ route('bills.index') }}"><i class="bi bi-receipt me-2"></i>Bills</a>
                                    @endcan
                                    @can('collect-payments')
                                    <a class="dropdown-item {{ Route::is('payments.collect') || Route::is('payments.create') ? 'active' : '' }}" href="{{ route('payments.collect') }}"><i class="bi bi-wallet me-2"></i>Collect Payment</a>
                                    @endcan
                                    @can('view-due-list')
                                    <a class="dropdown-item {{ Route::is('payments.due') ? 'active' : '' }}" href="{{ route('payments.due') }}"><i class="bi bi-cash-stack me-2"></i>Due List</a>
                                    @endcan
                                    @can('view-payments')
                                    <a class="dropdown-item {{ Route::is('payments.index') || Route::is('payments.receipt') ? 'active' : '' }}" href="{{ route('payments.index') }}"><i class="bi bi-cash-coin me-2"></i>Payments</a>
                                    @endcan
                                    @can('manage-expenses')
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item {{ Route::is('expense-categories.*') ? 'active' : '' }}" href="{{ route('expense-categories.index') }}"><i class="bi bi-tags me-2"></i>Expense Categories</a>
                                    <a class="dropdown-item {{ Route::is('expenses.index') || Route::is('expenses.create') || Route::is('expenses.edit') ? 'active' : '' }}" href="{{ route('expenses.index') }}"><i class="bi bi-wallet2 me-2"></i>Expenses</a>
                                    <a class="dropdown-item {{ Route::is('expenses.profit-loss') ? 'active' : '' }}" href="{{ route('expenses.profit-loss') }}"><i class="bi bi-graph-up-arrow me-2"></i>Profit &amp; Loss</a>
                                    @endcan
                                </div>
                            </li>
                            @can('view-reports')
                            <li class="nav-item dropdown">
                                <a id="reportDropdown" class="nav-link dropdown-toggle {{ Route::is('reports.*') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                   <i class="bi bi-bar-chart-line me-1"></i>Report management
                                </a>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="reportDropdown">
                                    <a class="dropdown-item {{ Route::is('reports.daily-collection') ? 'active' : '' }}" href="{{ route('reports.daily-collection') }}"><i class="bi bi-calendar-day me-2"></i>Daily Collection</a>
                                    <a class="dropdown-item {{ Route::is('reports.monthly-collection') ? 'active' : '' }}" href="{{ route('reports.monthly-collection') }}"><i class="bi bi-calendar-month me-2"></i>Monthly Collection</a>
                                    <a class="dropdown-item {{ Route::is('reports.customers') ? 'active' : '' }}" href="{{ route('reports.customers') }}"><i class="bi bi-people me-2"></i>Customer Report</a>
                                    <a class="dropdown-item {{ Route::is('reports.unit-consumption') ? 'active' : '' }}" href="{{ route('reports.unit-consumption') }}"><i class="bi bi-lightning-charge me-2"></i>Unit Consumption</a>
                                    <a class="dropdown-item {{ Route::is('reports.meter-not-read') ? 'active' : '' }}" href="{{ route('reports.meter-not-read') }}"><i class="bi bi-clipboard-x me-2"></i>Meter Not Read</a>
                                    <a class="dropdown-item {{ Route::is('reports.outstanding') ? 'active' : '' }}" href="{{ route('reports.outstanding') }}"><i class="bi bi-exclamation-circle me-2"></i>Outstanding Balance</a>
                                    <a class="dropdown-item {{ Route::is('reports.income-expense') ? 'active' : '' }}" href="{{ route('reports.income-expense') }}"><i class="bi bi-cash-coin me-2"></i>Income &amp; Expense</a>
                                </div>
                            </li>
                            @endcan
                            <li class="nav-item dropdown">
                                <a id="navbarDropdown" class="nav-link dropdown-toggle {{ Route::is('password.change') ? 'active' : '' }}" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                    <i class="bi bi-person-circle me-1"></i>{{ Auth::user()->name }}
                                </a>

                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                    <a href="{{ route('password.change') }}" class="dropdown-item {{ Route::is('password.change') ? 'active' : '' }}"><i class="bi bi-key me-2"></i>Change Password</a>

                                    <a class="dropdown-item" href="{{ route('logout') }}"
                                       onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                        <i class="bi bi-box-arrow-right me-2"></i>{{ __('Logout') }}
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
            </div>
        </nav>

        <main class="py-4">
            @yield('content')
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script>
        // Adds a show/hide eye button to every password field on the page.
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('input[type="password"]').forEach(function (input) {
                var group = document.createElement('div');
                group.className = 'input-group has-validation';
                input.parentNode.insertBefore(group, input);
                group.appendChild(input);

                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-outline-secondary';
                btn.setAttribute('aria-label', 'Show password');
                btn.innerHTML = '<i class="bi bi-eye"></i>';
                group.appendChild(btn);

                // Bootstrap only shows feedback as a sibling of the input, so pull it inside the group.
                var next = group.nextElementSibling;
                while (next && (next.classList.contains('invalid-feedback') || next.classList.contains('valid-feedback'))) {
                    var feedback = next;
                    next = next.nextElementSibling;
                    group.appendChild(feedback);
                }

                btn.addEventListener('click', function () {
                    var show = input.type === 'password';
                    input.type = show ? 'text' : 'password';
                    btn.innerHTML = show ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
                    btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                });
            });
        });

        // Mobile menu: open the panel already showing the section the current
        // page lives in, so the user isn't hunting for it behind a tap.
        document.addEventListener('DOMContentLoaded', function () {
            var panel = document.getElementById('navbarSupportedContent');
            if (! panel) return;

            panel.addEventListener('show.bs.offcanvas', function () {
                var current = panel.querySelector('.dropdown-item.active');
                if (! current) return;

                var menu = current.closest('.dropdown-menu');
                var toggle = menu && menu.parentElement.querySelector('.dropdown-toggle');
                if (! menu || ! toggle) return;

                menu.classList.add('show');
                toggle.classList.add('show');
                toggle.setAttribute('aria-expanded', 'true');
            });

            // Leave the desktop navbar as it was — those menus float on hover/click.
            panel.addEventListener('hidden.bs.offcanvas', function () {
                panel.querySelectorAll('.dropdown-menu.show, .dropdown-toggle.show').forEach(function (el) {
                    el.classList.remove('show');
                    if (el.classList.contains('dropdown-toggle')) el.setAttribute('aria-expanded', 'false');
                });
            });
        });
    </script>
    @include('partials.submit_guard')
    @stack('scripts')
</body>
</html>
