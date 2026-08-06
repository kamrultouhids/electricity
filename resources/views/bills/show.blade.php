@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Bill Details</h4>
        <a href="{{ route('bills.index') }}" class="btn btn-outline-secondary">Back to Bills</a>
    </div>

    <div class="row g-3">
        {{-- Customer / meta --}}
        <div class="col-md-5">
            <div class="card h-100">
                <div class="card-header bg-white"><h6 class="mb-0">Customer</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="text-muted small">Name</div>
                            <div>{{ $bill->customer->name ?? '—' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Sheet</div>
                            <div>{{ $bill->customer->sheet->name ?? '—' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Serial No</div>
                            <div>{{ $bill->customer->serial_no ?? '—' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Meter No</div>
                            <div>{{ $bill->customer->meter_number ?? '—' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Mobile</div>
                            <div>{{ $bill->customer->mobile_number ?? '—' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Billing Month</div>
                            <div>{{ $bill->billing_month->format('M Y') }}</div>
                        </div>
                        <div class="col-6">
                            <div class="text-muted small">Status</div>
                            <div>
                                @if ($bill->isPaid())
                                    <span class="badge bg-success">Paid</span>
                                @elseif ($bill->isPartial())
                                    <span class="badge bg-info text-dark">Partial</span>
                                @else
                                    <span class="badge bg-warning text-dark">Unpaid</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charge breakdown --}}
        <div class="col-md-7">
            <div class="card h-100">
                <div class="card-header bg-white"><h6 class="mb-0">Charge Breakdown</h6></div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <tr>
                                <td>Units Consumed</td>
                                <td class="text-end">{{ number_format($bill->units, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Per Unit Rate</td>
                                <td class="text-end">{{ number_format($bill->per_unit_rate, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Energy Charge</td>
                                <td class="text-end">{{ number_format($bill->energy_charge, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Fixed Charge</td>
                                <td class="text-end">{{ number_format($bill->fixed_charge, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Meter Rent</td>
                                <td class="text-end">{{ number_format($bill->meter_rent, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Previous Outstanding</td>
                                <td class="text-end">{{ number_format($bill->previous_outstanding, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Late Fee</td>
                                <td class="text-end">{{ number_format($bill->late_fee, 2) }}</td>
                            </tr>
                            <tr class="table-light fw-bold">
                                <td>Total Amount</td>
                                <td class="text-end">{{ number_format($bill->total_amount, 2) }}</td>
                            </tr>
                            <tr>
                                <td>Paid Amount</td>
                                <td class="text-end">{{ number_format($bill->paid_amount, 2) }}</td>
                            </tr>
                            <tr class="fw-bold">
                                <td>Due Amount</td>
                                <td class="text-end">{{ number_format($bill->due_amount, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
