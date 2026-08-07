@extends('layouts.app')

@php
    $customer = $meterReading->customer;
    $billMonth = \Illuminate\Support\Carbon::parse($data['billing_month']);
    $prepDate = now();
    $lastDate = now()->day(20);
@endphp

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h4 class="mb-0">Bill Preview</h4>
        <div class="d-flex gap-2">
            <button type="button" onclick="window.print()" class="btn btn-outline-secondary">Print</button>
            <a href="{{ route('bills.pending') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Pending</a>
        </div>
    </div>

    <div class="alert alert-info no-print">
        Review the bill below, then click <strong>Generate Bill</strong> to confirm.
    </div>

    @include('bills._document', [
        'customer'            => $customer,
        'billMonth'          => $billMonth,
        'prepDate'           => $prepDate,
        'lastDate'           => $lastDate,
        'serialNo'           => $meterReading->id,
        'accountNo'          => $customer->id,
        'preparerName'       => auth()->user()->name ?? '',
        'currentReading'     => $meterReading->current_reading,
        'previousReading'    => $meterReading->previous_reading,
        'currentReadingDate' => $meterReading->reading_date,
        'previousReadingDate' => $previousReading?->reading_date,
        'units'              => $data['units'],
        'energyCharge'       => $data['energy_charge'],
        'meterRent'          => $data['meter_rent'],
        'previousOutstanding' => $data['previous_outstanding'],
        'lateFee'            => $data['late_fee'],
        'fixedCharge'        => $data['fixed_charge'],
        'totalAmount'        => $data['total_amount'],
        'previousBills'      => $previousBills,
    ])

    {{-- Confirm --}}
    <div class="mt-4 text-end no-print">
        <form method="POST" action="{{ route('bills.store', $meterReading) }}" class="d-inline">
            @csrf
            <a href="{{ route('bills.pending') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary text-white"
                    onclick="return confirm('Generate this bill?');">
                Generate Bill
            </button>
        </form>
    </div>
</div>
@endsection
