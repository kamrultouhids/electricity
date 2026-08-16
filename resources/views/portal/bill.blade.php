@extends('portal.layout')

@php
    $customer = $bill->customer;
    $billMonth = $bill->billing_month;
    $prepDate = $bill->created_at;
    $lastDate = $bill->created_at->copy()->day(20);
    $mr = $bill->meterReading;
@endphp

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h4 class="mb-0">Bill — {{ $billMonth->format('M Y') }}</h4>
        <div class="d-flex gap-2">
            <button type="button" onclick="window.print()" class="btn btn-primary text-white"><i class="bi bi-download me-1"></i>Download / Print</button>
            @auth('customer')
                <a href="{{ route('portal.dashboard') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
            @endauth
        </div>
    </div>

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
</div>
@endsection
