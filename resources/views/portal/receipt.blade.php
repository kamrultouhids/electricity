@extends('portal.layout')

@section('title', 'My Receipt')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h4 class="mb-0">Payment Receipt</h4>
        <div class="d-flex gap-2">
            <button type="button" onclick="window.print()" class="btn btn-primary text-white"><i class="bi bi-download me-1"></i>Download / Print</button>
            <a href="{{ route('portal.dashboard') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>
    </div>

    <div class="receipt-copy">
        <div class="text-center">
            <div class="receipt-title">পেমেন্ট রসিদ / Payment Receipt</div>
            <div class="small text-muted">Receipt No: {{ $payment->receiptNo() }}</div>
        </div>

        <div class="row g-0 receipt-block">
            <div class="col-6 p-2 border-end">
                <div class="kv"><span>Customer</span><b>{{ $payment->customer->name ?? '—' }}</b></div>
                <div class="kv"><span>Meter No</span><b>{{ $payment->customer->meter_number ?? '—' }}</b></div>
                <div class="kv"><span>Mobile</span><b>{{ $payment->customer->mobile_number ?? '—' }}</b></div>
                <div class="kv"><span>Sheet</span><b>{{ $payment->customer->sheet->name ?? '—' }}</b></div>
            </div>
            <div class="col-6 p-2">
                <div class="kv"><span>Payment Date</span><b>{{ $payment->payment_date->format('d M Y') }}</b></div>
                <div class="kv"><span>Method</span><b>{{ $payment->methodLabel() }}</b></div>
                <div class="kv"><span>Collector</span><b>{{ $payment->collector->name ?? '—' }}</b></div>
            </div>
        </div>

        {{-- Allocation breakdown --}}
        <table class="table table-bordered receipt-table mb-0">
            <thead>
                <tr class="text-center">
                    <th>Bill Month</th>
                    <th class="text-end">Applied</th>
                    <th class="text-end">Remaining Due</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($payment->allocations as $alloc)
                    <tr>
                        <td class="text-center">{{ $alloc->bill ? $alloc->bill->billing_month->format('M Y') : '—' }}</td>
                        <td class="text-end">{{ number_format($alloc->amount, 2) }}</td>
                        <td class="text-end">{{ $alloc->bill ? number_format($alloc->bill->due_amount, 2) : '—' }}</td>
                    </tr>
                @endforeach
                @if ($payment->discount > 0)
                    <tr>
                        <td class="text-end">Discount</td>
                        <td class="text-end">{{ number_format($payment->discount, 2) }}</td>
                        <td></td>
                    </tr>
                @endif
                <tr class="fw-bold table-light">
                    <td class="text-end">Total Paid</td>
                    <td class="text-end">৳ {{ number_format($payment->amount, 2) }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        @if ($payment->note)
            <div class="p-2 small"><b>Note:</b> {{ $payment->note }}</div>
        @endif

        <div class="p-2 text-end">
            <small class="text-muted">Authorized Signature</small>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .receipt-copy { max-width: 600px; margin: 0 auto; border: 1px solid #000; background: #fff; }
    .receipt-title { font-size: 18px; font-weight: 700; padding: 10px 0 2px; }
    .receipt-block { border-top: 1px solid #000; }
    .receipt-copy .kv { display: flex; font-size: 13px; padding: 1px 0; }
    .receipt-copy .kv > span { min-width: 110px; }
    .receipt-copy .kv > span::after { content: ' :'; }
    .receipt-table th, .receipt-table td { padding: 4px 8px; font-size: 13px; border-color: #000 !important; }
    @media print {
        /* Zero page margin so the browser drops its own header/footer
           (date, title, page URL). The paper margin moves onto the body. */
        @page { size: A4 portrait; margin: 0; }
        body { padding: 12mm !important; }
        .no-print { display: none !important; }
        .navbar, nav { display: none !important; }
    }
</style>
@endpush
