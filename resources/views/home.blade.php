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
            // [label, value, subtitle, icon, tone]
            $cards = [
                ['Total Customers',     number_format($totalCustomers),            'Customers', 'bi-people-fill',            'stat-blue'],
                ['Active / Inactive',   $activeCustomers.' / '.$inactiveCustomers, 'Customers', 'bi-plug-fill',             'stat-teal'],
                ["Today's Collection",  '৳ '.number_format($todayCollection, 2),   'Today',     'bi-cash-coin',             'stat-green'],
                ['Monthly Collection',  '৳ '.number_format($monthCollection, 2),   'This month','bi-calendar-check',        'stat-indigo'],
                ['Discount This Month', '৳ '.number_format($monthDiscount, 2),     'This month','bi-tags-fill',             'stat-amber'],
                ['Due Balance',         '৳ '.number_format($totalOutstanding, 2),  'Outstanding','bi-exclamation-circle',   'stat-red'],
                ['Units This Month',    number_format($unitsThisMonth, 2),         'This month','bi-lightning-charge-fill', 'stat-amber'],
                ['Total Income',        '৳ '.number_format($totalIncome, 2),       'This month','bi-arrow-down-circle-fill','stat-green'],
                ['Total Expense',       '৳ '.number_format($totalExpense, 2),      'This month','bi-arrow-up-circle-fill',  'stat-red'],
                ['Net Profit',          '৳ '.number_format($netProfit, 2),         'This month','bi-graph-up-arrow', $netProfit >= 0 ? 'stat-teal' : 'stat-red'],
            ];
        @endphp

        @foreach ($cards as [$label, $value, $subtitle, $icon, $tone])
            <div class="col">
                <div class="card stat-card {{ $tone }} h-100 rounded-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                            <div class="stat-icon"><i class="bi {{ $icon }}"></i></div>
                            <span class="stat-value">{{ $value }}</span>
                        </div>
                        <div class="stat-label fw-semibold">{{ $label }}</div>
                        <div class="stat-subtitle small">{{ $subtitle }}</div>
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
    <div class="">
        <div class=" mb-2 pb-0 d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="bi bi-clock-history text-primary me-1"></i>Recent Payments</h6>
            <a href="{{ route('payments.index') }}" class="btn btn-sm btn-link text-decoration-none">View all</a>
        </div>
        <div class="table-responsive">
            <table class="table  table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Customer</th>
                        <th>Date</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Discount</th>
                        <th>Method</th>
                        <th>Collected By</th>
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
                            <td class="text-end">৳ {{ number_format($payment->discount, 2) }}</td>
                            <td><span class="badge rounded-pill bg-light text-dark border">{{ $payment->methodLabel() }}</span></td>
                            <td>{{ $payment->createdBy->name ?? '—' }}</td>
                            <td>
                                @if ($payment->status === \App\Models\Payment::STATUS_COMPLETED)
                                    <span class="badge rounded-pill bg-success-subtle text-success">Completed</span>
                                @else
                                    <span class="badge rounded-pill bg-danger-subtle text-danger">Cancelled</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No payments recorded yet.</td></tr>
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

    /* ===== Stat cards ===== */
    .stat-card {
        --tone: #3585BC;
        background: #fff;
        border: 1px solid #eef0f4;
        box-shadow: 0 1px 3px rgba(16,24,40,.05) !important;
        transition: background .18s ease, transform .18s ease, box-shadow .18s ease, border-color .18s ease;
    }
    .stat-card .card-body { padding: 1rem 1.1rem; }

    .stat-card .stat-icon {
        width: 34px; height: 34px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem;
        color: var(--tone);
        background: color-mix(in srgb, var(--tone) 14%, #fff);
        border-radius: 9px;
        transition: background .18s ease, color .18s ease;
    }
    .stat-card .stat-label    { color: #1f2937; letter-spacing: .01em; }
    .stat-card .stat-subtitle { color: #9aa1ad; }

    /* Plain value on the right */
    .stat-card .stat-value {
        font-size: 1.25rem; font-weight: 700; line-height: 1.1;
        color: var(--tone);
        text-align: right;
        transition: color .18s ease;
    }

    /* Hover = active: fill the card with the tone colour */
    .stat-card:hover {
        background: var(--tone);
        border-color: var(--tone);
        transform: translateY(-3px);
        box-shadow: 0 .6rem 1.2rem rgba(16,24,40,.16) !important;
    }
    .stat-card:hover .stat-label,
    .stat-card:hover .stat-subtitle { color: #fff; }
    .stat-card:hover .stat-subtitle { opacity: .85; }
    .stat-card:hover .stat-icon  { background: rgba(255,255,255,.22); color: #fff; }
    .stat-card:hover .stat-value { color: #fff; }

    /* Tone palette */
    .stat-blue   { --tone: #3585BC; }
    .stat-teal   { --tone: #17a2b8; }
    .stat-green  { --tone: #28a745; }
    .stat-indigo { --tone: #6610f2; }
    .stat-red    { --tone: #dc3545; }
    .stat-amber  { --tone: #e0932f; }

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
