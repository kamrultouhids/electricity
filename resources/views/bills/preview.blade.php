@extends('layouts.app')

@section('title', 'Bill Preview')

@php
    $customer = $meterReading->customer;
    $billMonth = \Illuminate\Support\Carbon::parse($data['billing_month']);
    $prepDate = now();
    // Suggested deadline; the operator confirms or changes it below before
    // the bill is saved.
    $lastDate = \App\Models\Bill::defaultLastDate($billMonth);
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
        'lineCharge'         => $data['line_charge'],
        'serviceCharge'      => $data['service_charge'],
        'demandCharge'       => $data['demand_charge'],
        'electricityDutyRate' => $data['electricity_duty_rate'],
        'electricityDuty'    => $data['electricity_duty'],
        'previousOutstanding' => $data['previous_outstanding'],
        'lateFee'            => $data['late_fee'],
        'fixedCharge'        => $data['fixed_charge'],
        'totalAmount'        => $data['total_amount'],
        'discount'           => $data['discount'] ?? 0,
        'previousBills'      => $previousBills,
    ])

    {{-- Confirm --}}
    <div class="mt-4 text-end no-print">
        <form method="POST" action="{{ route('bills.store', $meterReading) }}"
              class="d-inline-flex align-items-end gap-2">
            @csrf
            {{-- Printed on the bill as পরিশোধের শেষ তারিখ and saved with it. --}}
            <div class="text-start">
                <label for="bill_last_date" class="form-label mb-1 small">
                    Last payment date <span class="text-danger">*</span>
                </label>
                <input type="date" id="bill_last_date" name="bill_last_date" required
                       class="form-control @error('bill_last_date') is-invalid @enderror"
                       value="{{ old('bill_last_date', $lastDate->toDateString()) }}">
                @error('bill_last_date')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
            <a href="{{ route('bills.pending') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary text-white"
                    onclick="return confirm('Generate this bill?');">
                Generate Bill
            </button>
        </form>
    </div>
</div>
@endsection
