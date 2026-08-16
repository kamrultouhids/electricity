@extends('layouts.app')

@section('title', 'Reports')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="bi bi-bar-chart-line me-1"></i>Reports</h5>
    </div>

    <div class="row g-3">
        @php
            $cards = [
                ['reports.daily-collection',   'Daily Collection',    'Collections grouped by day.',            'bi-calendar-day'],
                ['reports.monthly-collection', 'Monthly Collection',  'Collections grouped by month.',          'bi-calendar-month'],
                ['reports.customers',          'Customer Report',     'Per-customer collection & outstanding.', 'bi-people'],
                ['reports.unit-consumption',   'Unit Consumption',    'Consumed units per customer.',           'bi-lightning-charge'],
                ['reports.meter-not-read',     'Meter Not Read',      'Connected customers with no reading this month.', 'bi-clipboard-x'],
                ['reports.outstanding',        'Outstanding Balance', 'Customers with pending dues.',           'bi-exclamation-circle'],
                ['reports.income-expense',     'Income & Expense',    'Collections vs expenses by month.',      'bi-cash-coin'],
            ];
        @endphp

        @foreach ($cards as [$route, $title, $desc, $icon])
            <div class="col-md-4">
                <a href="{{ route($route) }}" class="text-decoration-none">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title text-dark"><i class="bi {{ $icon }} me-2 text-primary"></i>{{ $title }}</h6>
                            <p class="card-text text-muted small mb-0">{{ $desc }}</p>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
</div>
@endsection
