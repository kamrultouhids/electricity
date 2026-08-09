@php
    $reportLinks = [];

    // ['reports.daily-collection',   'Daily Collection',    'bi-calendar-day'],
    // ['reports.monthly-collection', 'Monthly Collection',  'bi-calendar-month'],
    // ['reports.customers',          'Customer Report',     'bi-people'],
    // ['reports.unit-consumption',   'Unit Consumption',    'bi-lightning-charge'],
    // ['reports.meter-not-read',     'Meter Not Read',      'bi-clipboard-x'],
    // ['reports.outstanding',        'Outstanding Balance', 'bi-exclamation-circle'],
    // ['reports.income-expense',     'Income & Expense',    'bi-cash-coin'],
@endphp

<div class="report-nav no-print mb-3">
    <div class="d-flex flex-wrap gap-2">
        @foreach ($reportLinks as [$route, $label, $icon])
            <a href="{{ route($route) }}"
               class="btn btn-sm report-nav-btn {{ Route::is($route) ? 'active' : '' }}">
                <i class="bi {{ $icon }} me-1"></i>{{ $label }}
            </a>
        @endforeach
    </div>
</div>

@once
@push('styles')
<style>
    .report-nav-btn {
        background: #fff;
        border: 1px solid #e3e7ee;
        color: #475467;
        font-weight: 500;
    }
    .report-nav-btn:hover {
        border-color: #3061B3;
        color: #3061B3;
    }
    .report-nav-btn.active {
        background: #3061B3;
        border-color: #3061B3;
        color: #fff;
    }
</style>
@endpush
@endonce
