@extends('layouts.app')

@section('title', 'Print Bills')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h5 class="mb-0">
            Print Bills
            <span class="badge bg-primary px-1 py-0 small">{{ $bills->count() }}</span>
        </h5>
        <div class="d-flex gap-2">
            <button type="button" onclick="window.print()" class="btn btn-primary text-white">
                <i class="bi bi-printer me-1"></i>Print
            </button>
            <a href="{{ url()->previous() }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i>Back to Bills
            </a>
        </div>
    </div>

    <div class="alert alert-info no-print">
        {{ $bills->count() }} bill(s) ready — each prints on its own page.
        @if ($bills->hasPages())
            Showing {{ $bills->firstItem() }}–{{ $bills->lastItem() }} of {{ $bills->total() }};
            change Per Page or the page on the bill list to print the rest.
        @endif
    </div>

    @foreach ($bills as $bill)
        @php
            $mr = $bill->meterReading;
            $billMonth = $bill->billing_month;
            // The reading before this bill's own, for the "পূর্ববতী" label.
            $previousReading = $mr
                ? ($readingsByCustomer[$bill->customer_id] ?? collect())
                    ->last(fn ($r) => $r->reading_date < $mr->reading_date)
                : null;
        @endphp

        <div class="bill-page">
            @include('bills._document', [
                'customer'            => $bill->customer,
                'billMonth'           => $billMonth,
                'prepDate'            => $bill->created_at,
                'lastDate'            => $bill->paymentLastDate(),
                'serialNo'            => $mr->id ?? $bill->id,
                'accountNo'           => $bill->customer_id,
                'preparerName'        => $bill->createdBy->name ?? '',
                'currentReading'      => $mr->current_reading ?? 0,
                'previousReading'     => $mr->previous_reading ?? 0,
                'currentReadingDate'  => $mr->reading_date ?? $billMonth,
                'previousReadingDate' => $previousReading?->reading_date,
                'units'               => $bill->units,
                'energyCharge'        => $bill->energy_charge,
                'lineCharge'          => $bill->line_charge,
                'serviceCharge'       => $bill->service_charge,
                'demandCharge'        => $bill->demand_charge,
                'electricityDutyRate' => $bill->electricity_duty_rate,
                'electricityDuty'     => $bill->electricity_duty,
                'previousOutstanding' => $bill->previous_outstanding,
                'lateFee'             => $bill->late_fee,
                'fixedCharge'         => $bill->fixed_charge,
                'totalAmount'         => $bill->total_amount,
                'discount'            => $bill->discount,
                'previousBills'       => $bill->historyRows(),
                'verifyUrl'           => route('portal.bills.show', $bill),
            ])
        </div>
    @endforeach
</div>
@endsection

@push('styles')
<style>
    .bill-page + .bill-page { margin-top: 24px; }
    @media print {
        /* One bill per sheet of paper. */
        .bill-page { page-break-after: always; break-after: page; margin-top: 0; }
        .bill-page:last-child { page-break-after: auto; break-after: auto; }
    }
</style>
@endpush
