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
            <a href="{{ route('bills.pending') }}" class="btn btn-outline-secondary">Back to Pending</a>
        </div>
    </div>

    <div class="alert alert-info no-print">
        Review the bill below, then click <strong>Generate Bill</strong> to confirm.
    </div>

    <div class="bill-copy">
        {{-- Header --}}
        <div class="text-center position-relative">
            <div class="bill-title">বিদ্যুৎ বিল</div>
            <div class="bill-copy-tag">( গ্রাহক কপি )</div>
        </div>

        {{-- Top: customer info + bill dates --}}
        <div class="row g-0 bill-block">
            <div class="col-7 p-2 border-end">
                <div class="kv"><span>ক্রমিক নং</span><b>{{ $meterReading->id ?? '—' }}</b></div>
                <div class="kv"><span>এরিয়া কোড/শাখা</span><b>{{ $customer->sheet->name ?? '—' }}</b></div>
                <div class="kv boxed"><span>হিসাব নং/গ্রাহক নং</span><b>{{ $customer->id }}</b></div>
                <div class="kv boxed"><span>গ্রাহকের নাম</span><b>{{ $customer->name }}</b></div>
                <div class="kv boxed"><span>পিতা/স্বামীর নাম</span><b>{{ $customer->father_or_husband_name ?? '—' }}</b></div>
                <div class="kv boxed"><span>ঠিকানা</span><b>{{ $customer->address ?? '—' }}</b></div>
                <div class="kv boxed"><span>মিটার নং</span><b>{{ $customer->meter_number ?? '—' }}</b></div>
            </div>
            <div class="col-5 p-2">
                <div class="kv"><span>বিলের মাস</span><b>{{ $billMonth->format('F - Y') }}</b></div>
                <div class="kv"><span>বিল প্রস্তুতের তারিখ</span><b>{{ $prepDate->format('d-M-Y') }}</b></div>
                <div class="kv"><span>পরিশোধের শেষ তারিখ</span><b>{{ $lastDate->format('d-M-Y') }}</b></div>
            </div>
        </div>

        {{-- Previous bills history --}}
        <table class="table table-bordered bill-table mb-0">
            <thead>
                <tr class="text-center">
                    <th>আগের বিলের মাস</th>
                    <th>ব্যবহৃত ইউনিট</th>
                    <th>বিল</th>
                    <th>পরিশোধিত বিল</th>
                    <th>ছাড়</th>
                    <th>বকেয়া</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($previousBills as $pb)
                    <tr class="text-end">
                        <td class="text-center">{{ $pb->billing_month->format('F-Y') }}</td>
                        <td>{{ number_format($pb->units, 1) }}</td>
                        <td>{{ number_format($pb->total_amount, 1) }}</td>
                        <td>{{ number_format($pb->paid_amount, 1) }}</td>
                        <td>0</td>
                        <td>{{ number_format($pb->due_amount, 1) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted">কোন আগের বিল নেই</td></tr>
                @endforelse
            </tbody>
        </table>

        {{-- Meter reading + charge breakdown --}}
        <div class="row g-0 bill-block">
            {{-- Meter reading --}}
            <div class="col-5 border-end">
                <table class="table table-bordered bill-table mb-0 h-100">
                    <thead>
                        <tr class="text-center"><th colspan="2">মিটার রিডিং</th></tr>
                        <tr class="text-center"><th>তারিখ</th><th>ইউনিট</th></tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>বর্তমান<br><small>{{ $meterReading->reading_date->format('F - Y') }}</small></td>
                            <td class="text-end align-middle">{{ number_format($meterReading->current_reading, 0) }}</td>
                        </tr>
                        <tr>
                            <td>পূর্ববতী<br><small>{{ $previousReading ? $previousReading->reading_date->format('F - Y') : '—' }}</small></td>
                            <td class="text-end align-middle">{{ number_format($meterReading->previous_reading, 0) }}</td>
                        </tr>
                        <tr>
                            <td>ব্যবহৃত ইউনিট</td>
                            <td class="text-end align-middle">{{ number_format($data['units'], 0) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            {{-- Charges --}}
            <div class="col-7">
                <table class="table table-bordered bill-table mb-0">
                    <thead>
                        <tr><th>বিবরণ</th><th class="text-end" width="120">টাকা</th></tr>
                    </thead>
                    <tbody>
                        <tr><td>ব্যবহৃত ইউনিট মূল্য</td><td class="text-end">{{ number_format($data['energy_charge'], 2) }}</td></tr>
                        <tr><td>লাইন চার্জ</td><td class="text-end">0</td></tr>
                        <tr><td>সার্ভিস চার্জ</td><td class="text-end">0</td></tr>
                        <tr><td>ডিমান্ড চার্জ</td><td class="text-end">{{ number_format($data['meter_rent'], 2) }}</td></tr>
                        <tr><td>বকেয়া বিল</td><td class="text-end">{{ number_format($data['previous_outstanding'], 2) }}</td></tr>
                        <tr><td>বকেয়া বিলের জরিমানা</td><td class="text-end">{{ number_format($data['late_fee'], 2) }}</td></tr>
                        <tr><td>অতিরিক্ত চার্জ</td><td class="text-end">{{ number_format($data['fixed_charge'], 2) }}</td></tr>
                        <tr><td>বিদ্যুৎ শুল্ক(%)</td><td class="text-end">0</td></tr>
                        <tr class="fw-bold"><td>মোট বিল</td><td class="text-end">{{ number_format($data['total_amount'], 2) }}</td></tr>
                        <tr><td>ছাড়(-)</td><td class="text-end">0</td></tr>
                        <tr class="fw-bold table-light"><td>বিল</td><td class="text-end">৳ {{ number_format($data['total_amount'], 2) }}</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Preparer --}}
        <div class="row g-0 bill-block">
            <div class="col-6 p-2 border-end">
                <div>{{ auth()->user()->name ?? '' }}</div>
                <small class="text-muted">বিল প্রস্তুতকারী</small>
            </div>
            <div class="col-6 p-2 text-end">
                <small class="text-muted">বিল ইস্যুকারী</small>
            </div>
        </div>

        {{-- Instructions --}}
        <div class="p-2 bill-block">
            <div class="fw-bold">নির্দেশনা</div>
            <ul class="mb-1 small">
                <li>উপরোক্ত বিলের টাকা নির্দিষ্ট তারিখের মধ্যে কর্তৃপক্ষের বরাবরে পরিশোধ করিতে হইবে।</li>
                <li>সাইড লাইন ব্যবহার করা যাবে না। ৯৯৯ টাকার ঊর্ধ্বে বকেয়া বিলের উপর ১০% জরিমানা যোগ করা হবে।</li>
                <li>নির্ধারিত তারিখের মধ্যে বিল পরিশোধ না করিলে সংযোগ বিচ্ছিন্ন করা হবে।</li>
            </ul>
            <div class="small">
                ১) বিদ্যুৎ সাশ্রয়ের মাধ্যমে বিদ্যুৎ বিল কমান | ২) বাতি/ফ্যান ব্যবহারে সচেতন হোন |<br>
                ৩) বিদ্যুৎ সাশ্রয়ী বাতি (CFL/T5 টিউব লাইট) ব্যবহার করুন |
            </div>
        </div>

        {{-- Office copy --}}
        <div class="p-2 text-center fw-bold bill-block">অফিস কপি</div>
        <div class="row g-0 bill-block">
            <div class="col-6 p-2 border-end">
                <div class="kv"><span>ক্রমিক নং</span><b>{{ $meterReading->id ?? '—' }}</b></div>
                <div class="kv"><span>গ্রাহকের নাম</span><b>{{ $customer->name }}</b></div>
                <div class="kv"><span>পিতা/স্বামীর নাম</span><b>{{ $customer->father_or_husband_name ?? '—' }}</b></div>
                <div class="kv"><span>ঠিকানা</span><b>{{ $customer->address ?? '—' }}</b></div>
            </div>
            <div class="col-6 p-2">
                <div class="kv"><span>মিটার নং</span><b>{{ $customer->meter_number ?? '—' }}</b></div>
                <div class="kv"><span>হিসাব নং/গ্রাহক নং</span><b>{{ $customer->id }}</b></div>
                <div class="kv"><span>বিলের মাস</span><b>{{ $billMonth->format('F - Y') }}</b></div>
                <div class="kv"><span>মোট বিল</span><b>৳ {{ number_format($data['total_amount'], 2) }}</b></div>
            </div>
        </div>
    </div>

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

@push('styles')
<style>
    .bill-copy {
        max-width: 800px;
        margin: 0 auto;
        border: 1px solid #000;
        background: #fff;
    }
    .bill-title { font-size: 20px; font-weight: 700; padding: 8px 0 0; }
    .bill-copy-tag { position: absolute; right: 8px; top: 10px; font-size: 12px; }
    .bill-block { border-top: 1px solid #000; }
    .bill-copy .kv { display: flex; font-size: 13px; padding: 1px 0; }
    .bill-copy .kv > span { min-width: 130px; }
    .bill-copy .kv > span::after { content: ' :'; }
    .bill-table th, .bill-table td { padding: 3px 6px; font-size: 13px; }
    .bill-table { border-color: #000 !important; }
    .bill-table th, .bill-table td { border-color: #000 !important; }
    @media print {
        .no-print { display: none !important; }
        .navbar, nav { display: none !important; }
        .bill-copy { border: 1px solid #000; }
    }
</style>
@endpush
