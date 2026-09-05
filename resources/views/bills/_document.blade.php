{{--
    Shared electricity-bill document.
    Expects: $customer, $billMonth, $prepDate, $lastDate (Carbon),
             $preparerName,
             $currentReading, $previousReading, $currentReadingDate, $previousReadingDate,
             $units, $energyCharge, $lineCharge, $serviceCharge, $demandCharge,
             $electricityDutyRate, $electricityDuty,
             $previousOutstanding, $lateFee, $fixedCharge, $totalAmount,
             $previousBills (Collection of Bill)
--}}
@php
    $lineCharge = $lineCharge ?? 0;
    $serviceCharge = $serviceCharge ?? 0;
    $demandCharge = $demandCharge ?? 0;
    $electricityDutyRate = $electricityDutyRate ?? 0;
    $electricityDuty = $electricityDuty ?? 0;

    // Bengali numerals and dates — shared with the dashboard, see App\Support\Bn.
    $bn = fn ($v) => \App\Support\Bn::digits($v);
    $bnMonths = \App\Support\Bn::MONTHS;
    $bnMonthYear = fn ($d) => \App\Support\Bn::monthYear($d);
    $bnDate = fn ($d) => \App\Support\Bn::date($d);
@endphp
<div class="bill-copy">
    @isset($verifyUrl)
        <div class="bill-qr">
            <img src="{{ \App\Support\Qr::dataUri($verifyUrl) }}" alt="Verify QR">
            <div class="bill-qr-cap">যাচাই করুন</div>
        </div>
    @endisset

    {{-- Organisation masthead --}}
    <div class="bill-org text-center px-2 pt-2">
        <div class="org-bismillah">বিসমিল্লাহির রাহমানির রাহিম</div>
        <div class="org-slogan">&ldquo;বিদ্যুৎ জাতীয় সম্পদ অপচয় রোধে এগিয়ে আসুন, অবৈধ সংযোগ থেকে বিরত থাকুন&rdquo;</div>
        <div class="org-name">চট্টগ্রাম মহানগর ছিন্নমূল বস্তিবাসি সমন্বয় সংগ্রাম পরিষদ</div>
        <div class="org-addr">পরিচালনায়ঃ জঙ্গল সলিমপুর বিদ্যুৎ গ্রাহক ফোরাম, ডাকঘরঃ জাফরাবাদ, থানাঃ সিতাকুণ্ড, চট্টগ্রাম।</div>
    </div>

    {{-- Header --}}
    <div class="text-center position-relative">
        <div class="bill-title">বিদ্যুৎ বিল</div>
        <div class="bill-copy-tag">( গ্রাহক কপি )</div>
    </div>

    {{-- Top: customer info + bill dates --}}
    <div class="row g-0 bill-block">
        <div class="col-7 p-2 border-end">
            <div class="kv"><span>এরিয়া কোড/শাখা</span><b>{{ $customer->sheet->name ?? '—' }}</b></div>
            <div class="kv boxed"><span>হিসাব নং/গ্রাহক নং</span><b>{{ $customer->serial_no ?? '—' }}</b></div>
            <div class="kv boxed"><span>গ্রাহকের নাম</span><b>{{ $customer->name }}</b></div>
            <div class="kv boxed"><span>পিতা/স্বামীর নাম</span><b>{{ $customer->father_or_husband_name ?? '—' }}</b></div>
            <div class="kv boxed"><span>ঠিকানা</span><b>{{ $customer->address ?? '—' }}</b></div>
            @if (filled($customer->mobile_number))
                <div class="kv boxed"><span>মোবাইল নং</span><b>{{ $customer->mobile_number }}</b></div>
            @endif
        </div>
        <div class="col-5 p-2">
            <div class="kv"><span>বিলের মাস</span><b>{{ $bnMonthYear($billMonth) }}</b></div>
            <div class="kv"><span>বিল প্রস্তুতের তারিখ</span><b>{{ $bnDate($prepDate) }}</b></div>
            <div class="kv"><span>পরিশোধের শেষ তারিখ</span><b>{{ $bnDate($lastDate) }}</b></div>
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
                    <td class="text-center">{{ $bnMonthYear($pb->billing_month) }}</td>
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
                        <td>বর্তমান<br><small>{{ $bnMonthYear($currentReadingDate) }}</small></td>
                        <td class="text-end align-middle">{{ $bn(number_format($currentReading, 0)) }}</td>
                    </tr>
                    <tr>
                        <td>পূর্ববতী<br><small>{{ $bnMonthYear($previousReadingDate) }}</small></td>
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
                    <tr><td>লাইন চার্জ</td><td class="text-end">{{ $bn(number_format($lineCharge, 2)) }}</td></tr>
                    <tr><td>সার্ভিস চার্জ</td><td class="text-end">{{ $bn(number_format($serviceCharge, 2)) }}</td></tr>
                    <tr><td>ডিমান্ড চার্জ</td><td class="text-end">{{ $bn(number_format($demandCharge, 2)) }}</td></tr>
                    <tr><td>বকেয়া বিল</td><td class="text-end">{{ $bn(number_format($previousOutstanding, 2)) }}</td></tr>
                    <tr><td>বকেয়া বিলের জরিমানা</td><td class="text-end">{{ $bn(number_format($lateFee, 2)) }}</td></tr>
                    <tr><td>অতিরিক্ত চার্জ</td><td class="text-end">{{ $bn(number_format($fixedCharge, 2)) }}</td></tr>
                    <tr>
                        <td>বিদ্যুৎ শুল্ক{{ $electricityDutyRate > 0 ? ' ('.$bn(rtrim(rtrim(number_format($electricityDutyRate, 2), '0'), '.')).'%)' : '(%)' }}</td>
                        <td class="text-end">{{ $bn(number_format($electricityDuty, 2)) }}</td>
                    </tr>
                    <!-- @php $discount = $discount ?? 0; @endphp -->
                    @php $discount =  0; @endphp

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
        {{-- Where to call, and where to check the bill online. The portal URL
             is printed in full, scheme included, and follows APP_URL. --}}
        <div class="small bill-contact">
            <b>হটলাইন:</b> {{ $bn('01633380033') }}
            <span class="bill-contact-sep">|</span>
            <b>অনলাইনে বিল দেখুন:</b>
            <a href="{{ route('portal.login') }}" target="_blank" rel="noopener">{{ route('portal.login') }}</a>
        </div>
        <div class="small">
            ১) বিদ্যুৎ সাশ্রয়ের মাধ্যমে বিদ্যুৎ বিল কমান | ২) বাতি/ফ্যান ব্যবহারে সচেতন হোন |<br>
            ৩) বিদ্যুৎ সাশ্রয়ী বাতি (CFL/T5 টিউব লাইট) ব্যবহার করুন |
        </div>
    </div>

    {{-- Cut separator between customer copy and office copy --}}
    <div class="bill-cut"><span>&#9986;</span></div>

    {{-- Office copy --}}
    <div class="p-2 text-center fw-bold bill-block">অফিস কপি</div>
    <div class="row g-0 bill-block">
        <div class="col-6 p-2 border-end">
            <div class="kv"><span>গ্রাহকের নাম</span><b>{{ $customer->name }}</b></div>
            <div class="kv"><span>পিতা/স্বামীর নাম</span><b>{{ $customer->father_or_husband_name ?? '—' }}</b></div>
            <div class="kv"><span>ঠিকানা</span><b>{{ $customer->address ?? '—' }}</b></div>
        </div>
        <div class="col-6 p-2">
            @if (filled($customer->mobile_number))
                <div class="kv"><span>মোবাইল নং</span><b>{{ $customer->mobile_number }}</b></div>
            @endif
            <div class="kv"><span>হিসাব নং/গ্রাহক নং</span><b>{{ $customer->serial_no ?? '—' }}</b></div>
            <div class="kv"><span>বিলের মাস</span><b>{{ $bnMonthYear($billMonth) }}</b></div>
            <div class="kv"><span>মোট বিল</span><b>৳ {{ $bn(number_format($totalAmount, 2)) }}</b></div>
        </div>
    </div>
</div>

@once
@push('styles')
<style>
    .bill-copy {
        max-width: 800px;
        margin: 0 auto;
        border: 1px solid #000;
        background: #fff;
        position: relative;
    }
    .bill-org { line-height: 1.35; }
    .bill-org .org-bismillah { font-size: 12px; }
    .bill-org .org-slogan { font-size: 13px; font-weight: 700; }
    .bill-org .org-name { font-size: 17px; font-weight: 700; }
    .bill-org .org-addr { font-size: 12px; }
    .bill-title { font-size: 20px; font-weight: 700; padding: 0 0 2px; }
    .bill-cut {
        position: relative;
        height: 24px;
        margin: 10px 0;
        border-top: 2px dashed #000;
    }
    .bill-cut span {
        position: absolute;
        top: -12px;
        left: 12px;
        background: #fff;
        padding: 0 6px;
        font-size: 16px;
        line-height: 24px;
    }
    .bill-copy-tag { position: absolute; right: 8px; top: 2px; font-size: 12px; }
    .bill-qr { position: absolute; left: 8px; top: 8px; width: 74px; text-align: center; z-index: 2; }
    .bill-qr img { width: 66px; height: 66px; display: block; margin: 0 auto; }
    .bill-qr-cap { font-size: 8px; line-height: 1.1; margin-top: 1px; }
    .bill-block { border-top: 1px solid #000; }
    .bill-contact { margin: 2px 0 4px; }
    /* Clickable on screen, plain black text on paper. */
    .bill-contact a { color: inherit; text-decoration: underline; }
    @media print {
        .bill-contact a { color: #000; text-decoration: none; }
    }
    .bill-contact-sep { padding: 0 4px; color: #555; }
    .bill-copy .kv { display: flex; font-size: 13px; padding: 1px 0; }
    .bill-copy .kv > span { min-width: 130px; }
    .bill-copy .kv > span::after { content: ' :'; }
    .bill-table th, .bill-table td { padding: 3px 6px; font-size: 13px; }
    .bill-table { border-color: #000 !important; }
    .bill-table th, .bill-table td { border-color: #000 !important; }
    @media print {
        @page { size: A4 portrait; margin: 8mm; }
        html, body { height: auto; }
        .no-print { display: none !important; }
        .navbar, nav { display: none !important; }
        /* Strip app chrome/spacing so only the bill prints */
        #app > main.py-4, main.py-4 { padding: 0 !important; }
        .container, .container-fluid { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
        /* Fit the whole bill (both copies) on a single A4 page */
        .bill-copy {
            border: 1px solid #000;
            max-width: 100%;
            width: 100%;
            margin: 0 auto;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .bill-copy tr, .bill-copy .bill-block, .bill-copy table { page-break-inside: avoid; break-inside: avoid; }
        /* Slightly tighter type/padding so it never spills to a 2nd page */
        .bill-org .org-name { font-size: 15px; }
        .bill-title { font-size: 18px; padding-top: 0; }
        .bill-copy .kv { font-size: 12px; }
        .bill-table th, .bill-table td { padding: 2px 5px; font-size: 12px; }
    }
</style>
@endpush
@endonce
