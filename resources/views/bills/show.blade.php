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
</div>
@endsection
