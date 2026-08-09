@php
    $emLinks = [
        ['route' => 'tariffs.index',            'label' => 'Rate Settings',      'icon' => 'bi-sliders',            'active' => Route::is('tariffs.*'), 'can' => 'rate-settings'],
        ['route' => 'customers.index',          'label' => 'Customer List',      'icon' => 'bi-people',             'active' => Route::is('customers.*'), 'can' => null],
        ['route' => 'meter-readings.index',     'label' => 'Meter Readings',     'icon' => 'bi-speedometer2',       'active' => Route::is('meter-readings.*'), 'can' => 'access-meter-readings'],
        ['route' => 'bills.pending',            'label' => 'Pending Readings',   'icon' => 'bi-hourglass-split',    'active' => Route::is('bills.pending'), 'can' => 'generate-bills'],
        ['route' => 'bills.index',              'label' => 'Bills',              'icon' => 'bi-receipt',            'active' => Route::is('bills.*') && ! Route::is('bills.pending'), 'can' => 'view-bills'],
        ['route' => 'payments.due',             'label' => 'Due List',           'icon' => 'bi-cash-stack',         'active' => Route::is('payments.due'), 'can' => 'view-due-list'],
        ['route' => 'payments.index',           'label' => 'Payments',           'icon' => 'bi-cash-coin',          'active' => Route::is('payments.index') || Route::is('payments.receipt'), 'can' => 'view-payments'],
        ['route' => 'expense-categories.index', 'label' => 'Expense Categories', 'icon' => 'bi-tags',               'active' => Route::is('expense-categories.*'), 'can' => 'manage-expenses'],
        ['route' => 'expenses.index',           'label' => 'Expenses',           'icon' => 'bi-wallet2',            'active' => Route::is('expenses.index') || Route::is('expenses.create') || Route::is('expenses.edit'), 'can' => 'manage-expenses'],
        ['route' => 'expenses.profit-loss',     'label' => 'Profit & Loss',      'icon' => 'bi-graph-up-arrow',     'active' => Route::is('expenses.profit-loss'), 'can' => 'manage-expenses'],
    ];
@endphp

<div class="em-nav no-print mb-3">
    <div class="d-flex flex-wrap gap-2">
        @foreach ($emLinks as $link)
            @if (is_null($link['can']) || auth()->user()?->hasAbility($link['can']))
                <a href="{{ route($link['route']) }}"
                   class="btn btn-sm em-nav-btn {{ $link['active'] ? 'active' : '' }}">
                    <i class="bi {{ $link['icon'] }} me-1"></i>{{ $link['label'] }}
                </a>
            @endif
        @endforeach
    </div>
</div>

@once
@push('styles')
<style>
    .em-nav-btn {
        background: #fff;
        border: 1px solid #e3e7ee;
        color: #475467;
        font-weight: 500;
    }
    .em-nav-btn:hover {
        border-color: #3061B3;
        color: #3061B3;
    }
    .em-nav-btn.active {
        background: #3061B3;
        border-color: #3061B3;
        color: #fff;
    }
</style>
@endpush
@endonce
