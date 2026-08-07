{{--
    Shared electricity-bill document.
    Expects: $customer, $billMonth, $prepDate, $lastDate (Carbon),
             $serialNo, $accountNo, $preparerName,
             $currentReading, $previousReading, $currentReadingDate, $previousReadingDate,
             $units, $energyCharge, $meterRent, $previousOutstanding, $lateFee, $fixedCharge, $totalAmount,
             $previousBills (Collection of Bill)
--}}
@php
    // Convert western digits to Bengali numerals for bill figures.
    $bn = fn ($v) => strtr((string) $v, ['0' => '০', '1' => '১', '2' => '২', '3' => '৩', '4' => '৪', '5' => '৫', '6' => '৬', '7' => '৭', '8' => '৮', '9' => '৯']);
@endphp
<div class="bill-copy">
    {{-- Header --}}
    <div class="text-center position-relative">
        <div class="bill-title">বিদ্যুৎ বিল</div>
        <div class="bill-copy-tag">( গ্রাহক কপি )</div>
    </div>

    {{-- Top: customer info + bill dates --}}
    <div class="row g-0 bill-block">
        <div class="col-7 p-2 border-end">
            <div class="kv"><span>ক্রমিক নং</span><b>{{ $serialNo ?? '—' }}</b></div>
            <div class="kv"><span>এরিয়া কোড/শাখা</span><b>{{ $customer->sheet->name ?? '—' }}</b></div>
            <div class="kv boxed"><span>হিসাব নং/গ্রাহক নং</span><b>{{ $accountNo }}</b></div>
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
                    <td>{{ $bn(number_format($pb->units, 1)) }}</td>
                    <td>{{ $bn(number_format($pb->total_amount, 1)) }}</td>
                    <td>{{ $bn(number_format($pb->paid_amount, 1)) }}</td>
                    <td>{{ $bn(number_format($pb->discount, 1)) }}</td>
                    <td>{{ $bn(number_format($pb->due_amount, 1)) }}</td>
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
                        <td>বর্তমান<br><small>{{ $currentReadingDate ? $currentReadingDate->format('F - Y') : '—' }}</small></td>
                        <td class="text-end align-middle">{{ $bn(number_format($currentReading, 0)) }}</td>
                    </tr>
                    <tr>
                        <td>পূর্ববতী<br><small>{{ $previousReadingDate ? $previousReadingDate->format('F - Y') : '—' }}</small></td>
                        <td class="text-end align-middle">{{ $bn(number_format($previousReading, 0)) }}</td>
                    </tr>
                    <tr>
                        <td>ব্যবহৃত ইউনিট</td>
                        <td class="text-end align-middle">{{ $bn(number_format($units, 0)) }}</td>
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
                    <tr><td>ব্যবহৃত ইউনিট মূল্য</td><td class="text-end">{{ $bn(number_format($energyCharge, 2)) }}</td></tr>
                    <tr><td>লাইন চার্জ</td><td class="text-end">{{ $bn(0) }}</td></tr>
                    <tr><td>সার্ভিস চার্জ</td><td class="text-end">{{ $bn(0) }}</td></tr>
                    <tr><td>ডিমান্ড চার্জ</td><td class="text-end">{{ $bn(number_format($meterRent, 2)) }}</td></tr>
                    <tr><td>বকেয়া বিল</td><td class="text-end">{{ $bn(number_format($previousOutstanding, 2)) }}</td></tr>
                    <tr><td>বকেয়া বিলের জরিমানা</td><td class="text-end">{{ $bn(number_format($lateFee, 2)) }}</td></tr>
                    <tr><td>অতিরিক্ত চার্জ</td><td class="text-end">{{ $bn(number_format($fixedCharge, 2)) }}</td></tr>
                    <tr><td>বিদ্যুৎ শুল্ক(%)</td><td class="text-end">{{ $bn(0) }}</td></tr>
                    @php $discount = $discount ?? 0; @endphp
                    <tr class="fw-bold"><td>মোট বিল</td><td class="text-end">{{ $bn(number_format($totalAmount, 2)) }}</td></tr>
                    <tr><td>ছাড়(-)</td><td class="text-end">{{ $bn(number_format($discount, 2)) }}</td></tr>
                    <tr class="fw-bold table-light"><td>বিল</td><td class="text-end">৳ {{ $bn(number_format($totalAmount - $discount, 2)) }}</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Preparer --}}
    <div class="row g-0 bill-block">
        <div class="col-6 p-2 border-end">
            <div>{{ $preparerName }}</div>
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
            <div class="kv"><span>ক্রমিক নং</span><b>{{ $serialNo ?? '—' }}</b></div>
            <div class="kv"><span>গ্রাহকের নাম</span><b>{{ $customer->name }}</b></div>
            <div class="kv"><span>পিতা/স্বামীর নাম</span><b>{{ $customer->father_or_husband_name ?? '—' }}</b></div>
            <div class="kv"><span>ঠিকানা</span><b>{{ $customer->sheet->name ?? '—' }}</b></div>
        </div>
        <div class="col-6 p-2">
            <div class="kv"><span>মিটার নং</span><b>{{ $customer->meter_number ?? '—' }}</b></div>
            <div class="kv"><span>হিসাব নং/গ্রাহক নং</span><b>{{ $accountNo }}</b></div>
            <div class="kv"><span>বিলের মাস</span><b>{{ $billMonth->format('F - Y') }}</b></div>
            <div class="kv"><span>মোট বিল</span><b>৳ {{ $bn(number_format($totalAmount, 2)) }}</b></div>
        </div>
    </div>
</div>

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
