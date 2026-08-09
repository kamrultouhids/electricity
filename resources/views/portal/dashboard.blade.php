@extends('portal.layout')

@section('content')
<div class="container">

    {{-- ===== Consumer Information ===== --}}
    <div class="card rounded-4 mb-4">
        <div class="card-body">
            <div class="d-flex flex-wrap align-items-center gap-3 mb-3">
                @if ($customer->photo)
                    <img src="{{ asset('storage/' . $customer->photo) }}" alt="photo" class="info-avatar">
                @else
                    <div class="info-avatar d-flex align-items-center justify-content-center text-secondary fw-bold" style="font-size:2rem;">
                        {{ strtoupper(mb_substr($customer->name ?? '?', 0, 1)) }}
                    </div>
                @endif
                <div>
                    <h5 class="mb-1 fw-semibold">{{ $customer->name }}
                        @if ($customer->status === \App\Models\Customer::STATUS_ACTIVE)
                            <span class="badge bg-success align-middle">Active</span>
                        @else
                            <span class="badge bg-secondary align-middle">Inactive</span>
                        @endif
                    </h5>
                    <div class="text-muted small">
                        <i class="bi bi-hash me-1"></i>Serial: {{ $customer->serial_no ?? '—' }}
                        <span class="mx-2">·</span>
                        <i class="bi bi-speedometer2 me-1"></i>Meter: {{ $customer->meter_number ?? '—' }}
                    </div>
                </div>
            </div>

            @php
                $info = [
                    'Mobile Number'             => $customer->mobile_number,
                    'Father / Husband Name'     => $customer->father_or_husband_name,
                    'Mother Name'               => $customer->mother_name,
                    'National / Voter ID'       => $customer->national_id,
                    'Age'                       => $customer->age,
                    'Occupation'                => $customer->occupation,
                    'Religion'                  => $customer->religion,
                    'Educational Qualification' => $customer->educational_qualification,
                    'Sheet'                     => $customer->sheet->name ?? null,
                    'Connection Type'           => $customer->connection_type ? ucfirst($customer->connection_type) : null,
                    'Connection Date'           => $customer->connection_date ? $customer->connection_date->format('d M Y') : null,
                    'Address'                   => $customer->address,
                    'Guardian Name'             => $customer->guardian_name,
                    'Guardian Relationship'     => $customer->guardian_relationship,
                    'Guardian Address'          => $customer->guardian_address,
                ];
            @endphp

            <div class="row g-0 border-top">
                @foreach ($info as $label => $value)
                    <div class="col-md-6 d-flex border-bottom py-2 px-1">
                        <div class="text-muted" style="min-width: 190px;">{{ $label }}</div>
                        <div class="fw-medium">{{ $value !== null && $value !== '' ? $value : '—' }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ===== Monthly Consumption chart ===== --}}
    <div class="card rounded-4 mb-4">
        <div class="card-body">
            <h6 class="mb-3"><i class="bi bi-bar-chart-line text-primary me-1"></i>Monthly Consumption <span class="text-muted small">({{ $year }})</span></h6>
            <div class="chart-box"><canvas id="consumptionChart"></canvas></div>
        </div>
    </div>

    {{-- ===== Last 12 months bills ===== --}}
    <div class="card rounded-4 mb-4">
        <div class="card-body">
            <h6 class="mb-3"><i class="bi bi-receipt text-primary me-1"></i>Bills — Last 12 Months</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Month</th>
                            <th class="text-end">Units</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Due</th>
                            <th>Status</th>
                            <th class="text-end">Bill</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bills as $bill)
                            <tr>
                                <td>{{ $bill->billing_month->format('M Y') }}</td>
                                <td class="text-end">{{ number_format($bill->units, 2) }}</td>
                                <td class="text-end">৳ {{ number_format($bill->total_amount, 2) }}</td>
                                <td class="text-end">৳ {{ number_format($bill->paid_amount, 2) }}</td>
                                <td class="text-end">৳ {{ number_format($bill->due_amount, 2) }}</td>
                                <td>
                                    @if ($bill->isPaid())
                                        <span class="badge bg-success">Paid</span>
                                    @elseif ($bill->isPartial())
                                        <span class="badge bg-info text-dark">Partial</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Unpaid</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('portal.bills.show', $bill) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-download me-1"></i>Download
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No bills yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ===== Payment History ===== --}}
    <div class="card rounded-4 mb-4">
        <div class="card-body">
            <h6 class="mb-3"><i class="bi bi-cash-coin text-primary me-1"></i>Payment History</h6>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Discount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th class="text-end">Receipt</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($payments as $payment)
                            <tr>
                                <td>{{ optional($payment->payment_date)->format('d M Y') ?? '—' }}</td>
                                <td class="text-end fw-semibold">৳ {{ number_format($payment->amount, 2) }}</td>
                                <td class="text-end">{{ (float) $payment->discount > 0 ? '৳ '.number_format($payment->discount, 2) : '—' }}</td>
                                <td><span class="badge bg-light text-dark border">{{ $payment->methodLabel() }}</span></td>
                                <td>
                                    @if ($payment->status === \App\Models\Payment::STATUS_COMPLETED)
                                        <span class="badge bg-success-subtle text-success">Completed</span>
                                    @else
                                        <span class="badge bg-danger-subtle text-danger">Cancelled</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('portal.payments.receipt', $payment) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-download me-1"></i>Receipt
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No payments yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
    (function () {
        const labels = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        const consumption = @json($consumptionSeries);
        const currentMonthIdx = {{ $currentMonth }} - 1;
        const highlight = (base, active) => labels.map((_, i) => i === currentMonthIdx ? active : base);

        const el = document.getElementById('consumptionChart');
        if (el) {
            new Chart(el, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Units',
                        data: consumption,
                        backgroundColor: highlight('rgba(48,97,179,.55)', 'rgba(48,97,179,1)'),
                        borderColor: 'rgba(48,97,179,.9)',
                        borderWidth: 1,
                        borderRadius: 6,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } },
                },
            });
        }
    })();
</script>
@endpush
