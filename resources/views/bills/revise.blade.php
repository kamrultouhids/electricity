@extends('layouts.app')

@section('title', 'Revise Bill')

@php
    $reading = $bill->meterReading;
    $previous = (float) $reading->previous_reading;
@endphp

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Revise Bill — {{ $bill->billing_month->format('M Y') }}</h5>
        <a href="{{ route('bills.show', $bill) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back to Bill
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="alert alert-warning">
        Correcting the readings or the carried balance rewrites this bill's units, late fee and charges.
        The tariff rates and previous-months history stay exactly as issued.
    </div>

    <form method="POST" action="{{ route('bills.revise.store', $bill) }}">
        @csrf
        @method('PUT')

        <div class="row g-3">
            {{-- Reading --}}
            <div class="col-md-5">
                <div class="card h-100">
                    <div class="card-header bg-white"><h6 class="mb-0">Meter Reading — {{ $bill->customer->name }}</h6></div>
                    <div class="card-body">
                        @if ($reading->photo)
                            <a href="{{ asset('storage/' . $reading->photo) }}" target="_blank" rel="noopener">
                                <img src="{{ asset('storage/' . $reading->photo) }}" alt="meter photo"
                                     class="rounded mb-3" style="max-width:100%;max-height:220px;object-fit:cover;">
                            </a>
                        @endif

                        <div class="row g-3">
                            <div class="col-6">
                                <div class="text-muted small">Meter No</div>
                                <div>{{ $bill->customer->meter_number ?? '—' }}</div>
                            </div>
                            <div class="col-6">
                                <div class="text-muted small">Reading Date</div>
                                <div>{{ $reading->reading_date->format('d M Y') }}</div>
                            </div>
                            <div class="col-6">
                                <label class="form-label mb-1">Previous Reading <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="previous_reading" id="previous_reading"
                                       class="form-control" required
                                       value="{{ old('previous_reading', $previous) }}">
                                <small class="text-muted">Carried from the last reading — correct it if it was wrong.</small>
                            </div>
                            <div class="col-6">
                                <label class="form-label mb-1">Current Reading <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="current_reading" id="current_reading"
                                       class="form-control" required autofocus
                                       value="{{ old('current_reading', $reading->current_reading) }}">
                            </div>
                            <div class="col-6">
                                <label class="form-label mb-1">Previous Outstanding <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="previous_outstanding" id="previous_outstanding"
                                       class="form-control" required
                                       value="{{ old('previous_outstanding', $bill->previous_outstanding) }}">
                                <small class="text-muted">The late fee is recalculated from this.</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label mb-1">Reason <span class="text-danger">*</span></label>
                                <input type="text" name="reason" class="form-control" maxlength="255" required
                                       placeholder="e.g. misread digit — photo shows 1450, not 1050"
                                       value="{{ old('reason') }}">
                                <small class="text-muted">Recorded against the bill's revision history.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Before / after --}}
            <div class="col-md-7">
                <div class="card h-100">
                    <div class="card-header bg-white"><h6 class="mb-0">Charges</h6></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="list-head">
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-end">On the bill</th>
                                        <th class="text-end">After revision</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Units</td>
                                        <td class="text-end text-muted">{{ number_format($bill->units, 2) }}</td>
                                        <td class="text-end fw-semibold" id="new_units">—</td>
                                    </tr>
                                    <tr>
                                        <td>Energy Charge <small class="text-muted">@ ৳{{ number_format($bill->per_unit_rate, 2) }}/unit</small></td>
                                        <td class="text-end text-muted">{{ number_format($bill->energy_charge, 2) }}</td>
                                        <td class="text-end fw-semibold" id="new_energy">—</td>
                                    </tr>
                                    <tr>
                                        <td>Line + Service + Demand</td>
                                        <td class="text-end text-muted" colspan="2">
                                            {{ number_format((float) $bill->line_charge + (float) $bill->service_charge + (float) $bill->demand_charge, 2) }}
                                            <span class="badge bg-light text-muted border ms-1">unchanged</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Electricity Duty <small class="text-muted">{{ rtrim(rtrim(number_format($bill->electricity_duty_rate, 2), '0'), '.') }}%</small></td>
                                        <td class="text-end text-muted">{{ number_format($bill->electricity_duty, 2) }}</td>
                                        <td class="text-end fw-semibold" id="new_duty">—</td>
                                    </tr>
                                    <tr>
                                        <td>Previous Outstanding</td>
                                        <td class="text-end text-muted">{{ number_format($bill->previous_outstanding, 2) }}</td>
                                        <td class="text-end fw-semibold" id="new_outstanding">—</td>
                                    </tr>
                                    <tr>
                                        <td>Late Fee <small class="text-muted">on the outstanding</small></td>
                                        <td class="text-end text-muted">{{ number_format($bill->late_fee, 2) }}</td>
                                        <td class="text-end fw-semibold" id="new_late_fee">—</td>
                                    </tr>
                                    <tr class="fw-bold table-light">
                                        <td>Total</td>
                                        <td class="text-end">{{ number_format($bill->total_amount, 2) }}</td>
                                        <td class="text-end" id="new_total">—</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-4 d-flex justify-content-end gap-2">
                            <a href="{{ route('bills.show', $bill) }}" class="btn btn-outline-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary text-white"
                                    onclick="return confirm('Revise this bill from the corrected reading?');">
                                Save Revision
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    {{-- Revision history --}}
    @if ($bill->revisions->count())
        <div class="card mt-4 list-card rounded-4">
            <div class="card-header bg-white"><h6 class="mb-0">Revision History</h6></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="list-head">
                        <tr>
                            <th>#</th>
                            <th>Prev. Reading</th>
                            <th>Reading</th>
                            <th>Units</th>
                            <th>Outstanding</th>
                            <th>Late Fee</th>
                            <th>Total</th>
                            <th>Reason</th>
                            <th>Revised By</th>
                            <th>When</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($bill->revisions as $revision)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td class="text-nowrap">{{ number_format($revision->old_previous_reading, 2) }} <i class="bi bi-arrow-right mx-1"></i> {{ number_format($revision->new_previous_reading, 2) }}</td>
                                <td class="text-nowrap">{{ number_format($revision->old_current_reading, 2) }} <i class="bi bi-arrow-right mx-1"></i> {{ number_format($revision->new_current_reading, 2) }}</td>
                                <td class="text-nowrap">{{ number_format($revision->old_units, 2) }} <i class="bi bi-arrow-right mx-1"></i> {{ number_format($revision->new_units, 2) }}</td>
                                <td class="text-nowrap">{{ number_format($revision->old_previous_outstanding, 2) }} <i class="bi bi-arrow-right mx-1"></i> {{ number_format($revision->new_previous_outstanding, 2) }}</td>
                                <td class="text-nowrap">{{ number_format($revision->old_late_fee, 2) }} <i class="bi bi-arrow-right mx-1"></i> {{ number_format($revision->new_late_fee, 2) }}</td>
                                <td class="text-nowrap">{{ number_format($revision->old_total_amount, 2) }} <i class="bi bi-arrow-right mx-1"></i> {{ number_format($revision->new_total_amount, 2) }}</td>
                                <td>{{ $revision->reason }}</td>
                                <td>{{ $revision->changedBy->name ?? '—' }}</td>
                                <td>{{ $revision->created_at->format('d M Y, h:i A') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Mirrors BillCalculator: energy charge floors at the connection minimum,
    // duty is a percentage of it, the late fee is charged on the outstanding,
    // and the rest of the bill is untouched.
    const rate       = {{ (float) $bill->per_unit_rate }};
    const minCharge  = {{ (float) $minimumCharge }};
    const dutyRate   = {{ (float) $bill->electricity_duty_rate }};
    const fixedParts = {{ (float) $bill->line_charge + (float) $bill->service_charge + (float) $bill->demand_charge + (float) $bill->fixed_charge + (float) $bill->meter_rent }};
    const flatLimit  = {{ \App\Services\BillCalculator::OUTSTANDING_FLAT_LIMIT }};
    const flatFee    = {{ \App\Services\BillCalculator::OUTSTANDING_FLAT_FEE }};
    const percentFee = {{ \App\Services\BillCalculator::OUTSTANDING_PERCENT }};

    const previousInput    = document.getElementById('previous_reading');
    const currentInput     = document.getElementById('current_reading');
    const outstandingInput = document.getElementById('previous_outstanding');
    const money = (v) => v.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});

    // BillCalculator::lateFee — flat up to the limit, a percentage above it.
    function lateFeeOn(outstanding) {
        if (outstanding <= 0) return 0;
        if (outstanding <= flatLimit) return flatFee;
        return Math.round(outstanding * percentFee * 100) / 100;
    }

    function recalc() {
        const previous    = parseFloat(previousInput.value);
        const current     = parseFloat(currentInput.value);
        const outstanding = parseFloat(outstandingInput.value);
        const targets = ['new_units', 'new_energy', 'new_duty', 'new_outstanding', 'new_late_fee', 'new_total'];

        if (isNaN(previous) || isNaN(current) || isNaN(outstanding) || current < previous || outstanding < 0) {
            targets.forEach(id => document.getElementById(id).textContent = '—');
            return;
        }

        const units   = Math.round((current - previous) * 100) / 100;
        const energy  = Math.round(Math.max(minCharge, Math.max(0, units) * rate) * 100) / 100;
        const duty    = dutyRate > 0 ? Math.round(energy * dutyRate) / 100 : 0;
        const lateFee = lateFeeOn(outstanding);
        const total   = Math.round((energy + duty + fixedParts + outstanding + lateFee) * 100) / 100;

        document.getElementById('new_units').textContent       = money(units);
        document.getElementById('new_energy').textContent      = money(energy);
        document.getElementById('new_duty').textContent        = money(duty);
        document.getElementById('new_outstanding').textContent = money(outstanding);
        document.getElementById('new_late_fee').textContent    = money(lateFee);
        document.getElementById('new_total').textContent       = money(total);
    }

    [previousInput, currentInput, outstandingInput].forEach(el => el.addEventListener('input', recalc));
    recalc();
</script>
@endpush
