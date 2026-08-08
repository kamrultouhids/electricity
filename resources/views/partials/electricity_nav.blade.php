@php
    $emLinks = [
        ['route' => 'tariffs.index',            'label' => 'Rate Settings',      'icon' => 'bi-sliders',            'active' => Route::is('tariffs.*')],
        ['route' => 'customers.index',          'label' => 'Customer List',      'icon' => 'bi-people',             'active' => Route::is('customers.*')],
        ['route' => 'meter-readings.index',     'label' => 'Meter Readings',     'icon' => 'bi-speedometer2',       'active' => Route::is('meter-readings.*')],
        ['route' => 'bills.pending',            'label' => 'Pending Readings',   'icon' => 'bi-hourglass-split',    'active' => Route::is('bills.pending')],
        ['route' => 'bills.index',              'label' => 'Bills',              'icon' => 'bi-receipt',            'active' => Route::is('bills.*') && ! Route::is('bills.pending')],
        ['route' => 'payments.due',             'label' => 'Due List',           'icon' => 'bi-cash-stack',         'active' => Route::is('payments.due')],
        ['route' => 'payments.index',           'label' => 'Payments',           'icon' => 'bi-cash-coin',          'active' => Route::is('payments.index') || Route::is('payments.receipt')],
        ['route' => 'expense-categories.index', 'label' => 'Expense Categories', 'icon' => 'bi-tags',               'active' => Route::is('expense-categories.*')],
        ['route' => 'expenses.index',           'label' => 'Expenses',           'icon' => 'bi-wallet2',            'active' => Route::is('expenses.index') || Route::is('expenses.create') || Route::is('expenses.edit')],
        ['route' => 'expenses.profit-loss',     'label' => 'Profit & Loss',      'icon' => 'bi-graph-up-arrow',     'active' => Route::is('expenses.profit-loss')],
    ];
@endphp

<div class="em-nav no-print mb-3">
    <div class="d-flex flex-wrap gap-2">
        @foreach ($emLinks as $link)
            <a href="{{ route($link['route']) }}"
               class="btn btn-sm em-nav-btn {{ $link['active'] ? 'active' : '' }}">
                <i class="bi {{ $link['icon'] }} me-1"></i>{{ $link['label'] }}
            </a>
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
