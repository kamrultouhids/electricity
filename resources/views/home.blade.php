@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="container ">

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @php
        // Rendered server-side: the greeting must follow the app's Asia/Dhaka
        // clock, not whatever timezone the viewer's device happens to be set to.
        $now = \Illuminate\Support\Carbon::now();
    @endphp

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <div class="greet-line">{{ \App\Support\Bn::greeting($now) }}</div>
            <h4 class="mb-0 fw-semibold">Dashboard</h4>
        </div>
        <div class="clock-chip" role="status" aria-live="off">
            <i class="bi bi-clock"></i>
            <div>
                <div class="clock-time" id="dashClock"
                     data-epoch="{{ $now->getTimestampMs() }}"
                     data-offset="{{ $now->utcOffset() }}">{{ \App\Support\Bn::time($now) }}</div>
                <div class="clock-date">{{ \App\Support\Bn::fullDate($now) }}</div>
            </div>
        </div>
    </div>

    {{-- ===== Summary Cards ===== --}}
    <div class="row row-cols-2 row-cols-md-3 row-cols-xl-5 g-3 mb-4">
        @php
            // [label, value, subtitle, icon, tone]
            $cards = [
                ['Total Users',         number_format($totalUsers),                'Users',     'bi-person-badge-fill',      'stat-indigo'],
                ['Active / Inactive',   $activeUsers.' / '.$inactiveUsers,         'Users',     'bi-person-check-fill',      'stat-teal'],
                ['Total Customers',     number_format($totalCustomers),            'Customers', 'bi-people-fill',            'stat-blue'],
                ['Active / Inactive',   $activeCustomers.' / '.$inactiveCustomers, 'Customers', 'bi-plug-fill',             'stat-teal'],
                ["Today's Collection",  '৳ '.number_format($todayCollection, 2),   'Today',     'bi-cash-coin',             'stat-green'],
                ['Monthly Collection',  '৳ '.number_format($monthCollection, 2),   'This month','bi-calendar-check',        'stat-indigo'],
                ['Discount This Month', '৳ '.number_format($monthDiscount, 2),     'This month','bi-tags-fill',             'stat-amber'],
                ['Due Balance',         '৳ '.number_format($totalOutstanding, 2),  'Outstanding','bi-exclamation-circle',   'stat-red'],
                ['Total Consumption',   number_format($unitsThisMonth, 2).' units', 'This month','bi-lightning-charge-fill','stat-amber'],
                ['Pending Bill Generation', number_format($pendingBills),         'Readings',  'bi-hourglass-split',       'stat-red'],
                ['Meter Not Read',      number_format($meterNotRead),             'This month','bi-clipboard-x',           'stat-amber'],
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
    <div class="card panel-card rounded-4 mb-4">
        <div class="card-body">
            <div class="panel-head mb-3">
                <span class="panel-icon stat-amber"><i class="bi bi-lightning-charge-fill"></i></span>
                <h6 class="mb-0 fw-semibold">Quick Actions</h6>
            </div>
            <div class="d-flex flex-wrap gap-2">
                @can('manage-customers')
                    <a href="{{ route('customers.create') }}" class="btn btn-primary text-white rounded-3 px-3"><i class="bi bi-person-plus me-1"></i>Add Customer</a>
                @endcan
                @can('access-meter-readings')
                    <a href="{{ route('meter-readings.create') }}" class="btn btn-outline-primary rounded-3 px-3"><i class="bi bi-speedometer2 me-1"></i>Add Meter Reading</a>
                @endcan
                @can('generate-bills')
                    <a href="{{ route('bills.pending') }}" class="btn btn-outline-primary rounded-3 px-3"><i class="bi bi-receipt me-1"></i>Generate Bills</a>
                @endcan
                @can('view-due-list')
                    <a href="{{ route('payments.due') }}" class="btn btn-outline-primary rounded-3 px-3"><i class="bi bi-cash-stack me-1"></i>Record Payment</a>
                @endcan
            </div>
        </div>
    </div>

    {{-- ===== Charts ===== --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card panel-card rounded-4 h-100">
                <div class="card-body">
                    <div class="panel-head mb-3">
                        <span class="panel-icon stat-blue"><i class="bi bi-graph-up-arrow"></i></span>
                        <h6 class="mb-0 fw-semibold">Monthly Collection <span class="text-muted fw-normal small">({{ $year }})</span></h6>
                    </div>
                    <div class="chart-box"><canvas id="collectionChart"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card panel-card rounded-4 h-100">
                <div class="card-body">
                    <div class="panel-head mb-3">
                        <span class="panel-icon stat-teal"><i class="bi bi-bar-chart-line-fill"></i></span>
                        <h6 class="mb-0 fw-semibold">Monthly Electricity Consumption <span class="text-muted fw-normal small">({{ $year }})</span></h6>
                    </div>
                    <div class="chart-box"><canvas id="consumptionChart"></canvas></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Recent Payments ===== --}}
    <div class="card panel-card rounded-4 mb-4">
        <div class="card-body">
        <div class="mb-3 d-flex justify-content-between align-items-center">
            <div class="panel-head">
                <span class="panel-icon stat-blue"><i class="bi bi-credit-card-2-front"></i></span>
                <div>
                    <h6 class="mb-0 fw-semibold">Recent Payments</h6>
                    <small class="text-muted">Latest transactions</small>
                </div>
            </div>
            <a href="{{ route('payments.index') }}" class="btn btn-sm rp-viewall rounded-pill">View all <i class="bi bi-arrow-right ms-1"></i></a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover rp-table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Date</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Discount</th>
                        <th>Method</th>
                        <th>Collected By</th>
                        <th class="text-end">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentPayments as $payment)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    @if ($payment->customer && $payment->customer->photo)
                                        <img src="{{ asset('storage/' . $payment->customer->photo) }}" alt="photo" class="rp-avatar rp-avatar-img">
                                    @else
                                        <span class="rp-avatar">{{ strtoupper(mb_substr($payment->customer->name ?? '?', 0, 1)) }}</span>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="fw-semibold text-truncate">{{ $payment->customer->name ?? '—' }}</div>
                                        <small class="text-muted d-block">Serial: {{ $payment->customer->serial_no ?? '—' }} · Meter: {{ $payment->customer->meter_number ?? '—' }}</small>
                                        <small class="text-muted d-block">Mobile: {{ $payment->customer->mobile_number ?? '—' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td class="text-nowrap">{{ optional($payment->payment_date)->format('d M Y') ?? '—' }}</td>
                            <td class="text-end fw-bold text-success text-nowrap">৳ {{ number_format($payment->amount, 2) }}</td>
                            <td class="text-end text-nowrap">
                                @if ((float) $payment->discount > 0)
                                    <span class="text-danger fw-semibold">৳ {{ number_format($payment->discount, 2) }}</span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td><span class="badge rounded-pill rp-method">{{ $payment->methodLabel() }}</span></td>
                            <td class="text-truncate">{{ $payment->createdBy->name ?? '—' }}</td>
                            <td class="text-end">
                                @if ($payment->status === \App\Models\Payment::STATUS_COMPLETED)
                                    <span class="badge rounded-pill bg-success-subtle text-success"><i class="bi bi-check-circle-fill me-1"></i>Completed</span>
                                @else
                                    <span class="badge rounded-pill bg-danger-subtle text-danger"><i class="bi bi-x-circle-fill me-1"></i>Cancelled</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-5">
                            <i class="bi bi-inbox d-block mb-2" style="font-size:1.6rem;opacity:.5;"></i>
                            No payments recorded yet.
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
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

    /* ===== Panels (quick actions, charts, recent payments) ===== */
    .panel-card {
        background: #fff;
        border: 1px solid #eef0f4;
        box-shadow: 0 1px 3px rgba(16,24,40,.05) !important;
        transition: box-shadow .18s ease, transform .18s ease, border-color .18s ease;
    }
    .panel-card:hover {
        box-shadow: 0 .6rem 1.2rem rgba(16,24,40,.10) !important;
        border-color: #e3e7ee;
        transform: translateY(-2px);
    }
    .panel-head { display: flex; align-items: center; gap: .6rem; }
    .panel-head h6 { color: #1f2937; }
    .panel-icon {
        --tone: #3585BC;
        width: 34px; height: 34px; flex: 0 0 34px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem;
        color: var(--tone);
        background: color-mix(in srgb, var(--tone) 14%, #fff);
        border-radius: 9px;
    }

    /* ===== Recent Payments table ===== */
    .rp-table thead th {
        border: 0;
        border-bottom: 1px solid #eef0f4;
        background: transparent;
        color: #9aa1ad;
        font-size: .72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .04em;
        padding: .6rem .75rem;
    }
    .rp-table tbody td {
        border: 0;
        border-bottom: 1px solid #f4f5f8;
        padding: .7rem .75rem;
        vertical-align: middle;
    }
    .rp-table tbody tr:last-child td { border-bottom: 0; }
    .rp-avatar {
        width: 38px; height: 38px; flex: 0 0 38px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 50%;
        background: color-mix(in srgb, #6610f2 12%, #fff);
        color: #6610f2;
        font-weight: 700; font-size: .95rem;
        overflow: hidden;
    }
    .rp-avatar-img { object-fit: cover; }
    .rp-method {
        background: #f1f3f7;
        color: #4b5563;
        font-weight: 600;
        border: 1px solid #e7eaf0;
    }
    .rp-viewall {
        background: #eaf1ff;
        color: #2563EB;
        font-weight: 600;
        border: 1px solid #dbe6ff;
        padding: .35rem .9rem;
    }
    .rp-viewall:hover {
        background: #2563EB;
        color: #fff;
        border-color: #2563EB;
    }

    .min-w-0 { min-width: 0; }
    .chart-box { position: relative; height: 300px; }

    /* ===== Greeting + clock ===== */
    .greet-line {
        font-size: .95rem;
        font-weight: 600;
        color: #3061B3;
        margin-bottom: .1rem;
    }
    .clock-chip {
        display: flex;
        align-items: center;
        gap: .6rem;
        background: #fff;
        border: 1px solid #eef0f4;
        box-shadow: 0 1px 3px rgba(16,24,40,.05);
        border-radius: .85rem;
        padding: .5rem .9rem;
    }
    .clock-chip > i {
        font-size: 1.05rem;
        color: #3061B3;
    }
    .clock-time {
        font-weight: 700;
        line-height: 1.2;
        /* Digits keep their column as the seconds tick, so nothing jitters. */
        font-variant-numeric: tabular-nums;
    }
    .clock-date {
        font-size: .78rem;
        color: #6b7280;
        line-height: 1.2;
    }
    @media (max-width: 575.98px) {
        .clock-chip { width: 100%; }
    }
</style>
@endpush

@push('scripts')
<script>
    // The server rendered the correct time; this only advances it so the chip
    // doesn't go stale on a page left open. The starting hour comes with it, so
    // the day-part label stays right even if the device's clock is wrong.
    (function () {
        const el = document.getElementById('dashClock');
        if (! el) return;

        const digits = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
        const bn = (v) => String(v).replace(/[0-9]/g, (d) => digits[d]);

        // Mirrors App\Support\Bn::DAY_PARTS — the night band wraps past midnight.
        const parts = [
            [4, 5, 'ভোর'], [6, 11, 'সকাল'], [12, 15, 'দুপুর'],
            [16, 17, 'বিকাল'], [18, 19, 'সন্ধ্যা'], [20, 3, 'রাত'],
        ];
        const partFor = (hour) => {
            for (const [from, to, label] of parts) {
                const inBand = from <= to ? (hour >= from && hour <= to) : (hour >= from || hour <= to);
                if (inBand) return label;
            }
            return 'দিন';
        };

        // Tick the server's clock, not the device's: start from the epoch the
        // server rendered, add the elapsed time since load, then shift by the
        // app timezone's offset and read the result in UTC. A device set to the
        // wrong timezone — or the wrong time — never affects what is shown.
        const startedAt = Date.now();
        const serverEpoch = parseInt(el.dataset.epoch, 10);
        const offsetMs = parseInt(el.dataset.offset, 10) * 60000;

        const pad = (n) => String(n).padStart(2, '0');

        setInterval(function () {
            const at = new Date(serverEpoch + (Date.now() - startedAt) + offsetMs);
            const h24 = at.getUTCHours();
            const h12 = h24 % 12 === 0 ? 12 : h24 % 12;
            el.textContent = partFor(h24) + ' '
                + bn(pad(h12) + ':' + pad(at.getUTCMinutes()) + ':' + pad(at.getUTCSeconds()));
        }, 1000);
    })();
</script>
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
