@extends('layouts.app')

@section('content')
<div class="container ">

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0 fw-semibold">Dashboard</h4>
        </div>
    </div>

    {{-- ===== Summary Cards ===== --}}
    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
        @php
            $cards = [
                ['Total Customers',        number_format($totalCustomers),        'bi-people-fill',        'stat-blue'],
                ['Active / Inactive',      $activeCustomers.' / '.$inactiveCustomers, 'bi-plug-fill',      'stat-teal'],
                ["Today's Collection",     '৳ '.number_format($todayCollection, 2), 'bi-cash-coin',        'stat-green'],
                ['Monthly Collection',     '৳ '.number_format($monthCollection, 2), 'bi-calendar-check',   'stat-indigo'],
                ['Due Balance',    '৳ '.number_format($totalOutstanding, 2),'bi-exclamation-circle','stat-red'],
                ['Units This Month',       number_format($unitsThisMonth, 2),        'bi-lightning-charge-fill','stat-amber'],
                ['Total Income',           '৳ '.number_format($totalIncome, 2),      'bi-arrow-down-circle-fill','stat-green'],
                ['Total Expense',          '৳ '.number_format($totalExpense, 2),     'bi-arrow-up-circle-fill','stat-red'],
                ['Net Profit',             '৳ '.number_format($netProfit, 2),        'bi-graph-up-arrow', $netProfit >= 0 ? 'stat-teal' : 'stat-red'],
            ];
        @endphp

        @foreach ($cards as [$label, $value, $icon, $tone])
            <div class="col">
                <div class="card stat-card {{ $tone }} h-100 border-0 shadow-sm rounded-4">
                    <div class="card-body d-flex align-items-center gap-3">
                        <div class="stat-icon"><i class="bi {{ $icon }}"></i></div>
                        <div class="min-w-0">
                            <div class="stat-label text-muted small text-truncate">{{ $label }}</div>
                            <div class="stat-value fw-bold">{{ $value }}</div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ===== Quick Actions ===== --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body">
            <h6 class="text-muted mb-3"><i class="bi bi-lightning-charge me-1"></i>Quick Actions</h6>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('customers.create') }}" class="btn btn-primary text-white rounded-3"><i class="bi bi-person-plus me-1"></i>Add Customer</a>
                <a href="{{ route('meter-readings.create') }}" class="btn btn-outline-primary rounded-3"><i class="bi bi-speedometer2 me-1"></i>Add Meter Reading</a>
                <a href="{{ route('bills.pending') }}" class="btn btn-outline-primary rounded-3"><i class="bi bi-receipt me-1"></i>Generate Bills</a>
                <a href="{{ route('payments.due') }}" class="btn btn-outline-primary rounded-3"><i class="bi bi-cash-stack me-1"></i>Record Payment</a>
            </div>
        </div>
    </div>

    {{-- ===== Charts ===== --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <h6 class="mb-3"><i class="bi bi-graph-up-arrow text-primary me-1"></i>Monthly Collection <span class="text-muted small">({{ $year }})</span></h6>
                    <div class="chart-box"><canvas id="collectionChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <h6 class="mb-3"><i class="bi bi-bar-chart-line text-primary me-1"></i>Monthly Electricity Consumption <span class="text-muted small">({{ $year }})</span></h6>
                    <div class="chart-box"><canvas id="consumptionChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Recent Payments ===== --}}
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body pb-0 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-clock-history text-primary me-1"></i>Recent Payments</h6>
            <a href="{{ route('payments.index') }}" class="btn btn-sm btn-link text-decoration-none">View all</a>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Customer</th>
                        <th>Date</th>
                        <th class="text-end">Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentPayments as $payment)
                        <tr>
                            <td>
                                <div>{{ $payment->customer->name ?? '—' }}</div>
                                <small class="text-muted d-block">Serial No: {{ $payment->customer->serial_no ?? '—' }}</small>
                                <small class="text-muted d-block">Meter No: {{ $payment->customer->meter_number ?? '—' }}</small>
                                <small class="text-muted d-block">Mobile: {{ $payment->customer->mobile_number ?? '—' }}</small>
                            </td>
                            <td>{{ optional($payment->payment_date)->format('d M Y') ?? '—' }}</td>
                            <td class="text-end fw-semibold">৳ {{ number_format($payment->amount, 2) }}</td>
                            <td><span class="badge rounded-pill bg-light text-dark border">{{ $payment->methodLabel() }}</span></td>
                            <td>
                                @if ($payment->status === \App\Models\Payment::STATUS_COMPLETED)
                                    <span class="badge rounded-pill bg-success-subtle text-success">Completed</span>
                                @else
                                    <span class="badge rounded-pill bg-danger-subtle text-danger">Cancelled</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No payments recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .dashboard { max-width: 1400px; margin: 0 auto; }
    .dashboard .card {
        box-shadow: 0 1px 3px rgba(16,24,40,.06), 0 4px 12px rgba(16,24,40,.08) !important;
    }
    .stat-card .stat-icon {
        width: 46px; height: 46px; flex: 0 0 46px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem; color: #fff;
    }
    .stat-card .stat-value { font-size: 1.15rem; line-height: 1.2; }
    .stat-card .stat-label { letter-spacing: .02em; }
    .stat-card { transition: transform .15s ease, box-shadow .15s ease; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.1) !important; }
    .stat-blue   .stat-icon { background: linear-gradient(135deg,#3585BC,#2b6f9e); }
    .stat-teal   .stat-icon { background: linear-gradient(135deg,#17a2b8,#128293); }
    .stat-green  .stat-icon { background: linear-gradient(135deg,#28a745,#1e8637); }
    .stat-indigo .stat-icon { background: linear-gradient(135deg,#6610f2,#520bc4); }
    .stat-red    .stat-icon { background: linear-gradient(135deg,#dc3545,#b52a38); }
    .stat-amber  .stat-icon { background: linear-gradient(135deg,#f0ad4e,#d18e2c); }
    .min-w-0 { min-width: 0; }
    .chart-box { position: relative; height: 300px; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    (function () {
        const labels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        const collection = @json($collectionSeries);
        const consumption = @json($consumptionSeries);
        const currentMonthIdx = {{ $currentMonth }} - 1;

        const highlight = (base, active) => labels.map((_, i) => i === currentMonthIdx ? active : base);

        const baseOpts = {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 800, easing: 'easeOutQuart' },
            plugins: {
                legend: { display: false },
                tooltip: { mode: 'index', intersect: false, padding: 10, cornerRadius: 8 },
            },
            scales: {
                y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.05)' } },
                x: { grid: { display: false } },
            },
        };

        const collectionCanvas = document.getElementById('collectionChart');
        if (collectionCanvas) {
            new Chart(collectionCanvas, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Collection',
                        data: collection,
                        backgroundColor: highlight('rgba(53,133,188,.55)', 'rgba(53,133,188,1)'),
                        borderColor: highlight('rgba(53,133,188,.8)', 'rgba(53,133,188,1)'),
                        borderWidth: 1,
                        borderRadius: 6,
                    }],
                },
                options: baseOpts,
            });
        }

        const consumptionCanvas = document.getElementById('consumptionChart');
        if (consumptionCanvas) {
            new Chart(consumptionCanvas, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Units',
                        data: consumption,
                        fill: true,
                        tension: 0.35,
                        borderColor: '#3585BC',
                        backgroundColor: 'rgba(53,133,188,.12)',
                        pointBackgroundColor: highlight('#3585BC', '#dc3545'),
                        pointRadius: highlight(3, 6),
                        pointHoverRadius: 7,
                    }],
                },
                options: baseOpts,
            });
        }
    })();
</script>
@endpush
