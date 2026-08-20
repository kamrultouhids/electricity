@extends('layouts.app')

@section('title', 'Bill Details')

@php
    $customer = $bill->customer;
    $billMonth = $bill->billing_month;
    $prepDate = $bill->created_at;
    // Payable by the 20th of the month after the billing month.
    $lastDate = $billMonth->copy()->addMonth()->day(20);
    $mr = $bill->meterReading;
@endphp

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h4 class="mb-0">Bill Details</h4>
        <div class="d-flex gap-2">
            @unless ($bill->isPaid())
                <a href="{{ route('payments.create', $bill->customer) }}" class="btn btn-success text-white">Collect Payment</a>
            @endunless
            @can('revise-bills')
                @if ($bill->isRevisable())
                    <a href="{{ route('bills.revise', $bill) }}" class="btn btn-outline-warning">
                        <i class="bi bi-pencil-square me-1"></i>Revise Reading
                    </a>
                @endif
            @endcan
            <button type="button" onclick="window.print()" class="btn btn-outline-secondary">Print</button>
            <a href="{{ route('bills.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Bills</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success no-print">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger no-print">{{ session('error') }}</div>
    @endif

    @include('bills._document', [
        'customer'            => $customer,
        'billMonth'          => $billMonth,
        'prepDate'           => $prepDate,
        'lastDate'           => $lastDate,
        'serialNo'           => $mr->id ?? $bill->id,
        'accountNo'          => $customer->id,
        'preparerName'       => $bill->createdBy->name ?? '',
        'currentReading'     => $mr->current_reading ?? 0,
        'previousReading'    => $mr->previous_reading ?? 0,
        'currentReadingDate' => $mr->reading_date ?? $billMonth,
        'previousReadingDate' => $previousReading?->reading_date,
        'units'              => $bill->units,
        'energyCharge'       => $bill->energy_charge,
        'lineCharge'         => $bill->line_charge,
        'serviceCharge'      => $bill->service_charge,
        'demandCharge'       => $bill->demand_charge,
        'electricityDutyRate' => $bill->electricity_duty_rate,
        'electricityDuty'    => $bill->electricity_duty,
        'previousOutstanding' => $bill->previous_outstanding,
        'lateFee'            => $bill->late_fee,
        'fixedCharge'        => $bill->fixed_charge,
        'totalAmount'        => $bill->total_amount,
        'discount'           => $bill->discount,
        'previousBills'      => $previousBills,
        'verifyUrl'          => route('portal.bills.show', $bill),
    ])

    {{-- Revision history — screen only, never on the printed bill. --}}
    @if ($bill->revisions->count())
        <div class="card mt-4 list-card rounded-4 no-print">
            <div class="card-header bg-white"><h6 class="mb-0">Revision History</h6></div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="list-head">
                        <tr>
                            <th>#</th>
                            <th>Reading</th>
                            <th>Units</th>
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
                                <td class="text-nowrap">{{ number_format($revision->old_current_reading, 2) }} <i class="bi bi-arrow-right mx-1"></i> {{ number_format($revision->new_current_reading, 2) }}</td>
                                <td class="text-nowrap">{{ number_format($revision->old_units, 2) }} <i class="bi bi-arrow-right mx-1"></i> {{ number_format($revision->new_units, 2) }}</td>
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
