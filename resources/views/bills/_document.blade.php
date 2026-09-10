@php
    $lineCharge = $lineCharge ?? 0;
    $serviceCharge = $serviceCharge ?? 0;
    $demandCharge = $demandCharge ?? 0;
    $electricityDutyRate = $electricityDutyRate ?? 0;
    $electricityDuty = $electricityDuty ?? 0;
    $previousReadingDate = $previousReadingDate;

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
        <div class="org-addr">পরিচালনায়ঃ জঙ্গল সলিমপুর বিদ্যুৎ গ্রাহক ফোরাম <br>ডাকঘরঃ জাফরাবাদ, থানাঃ সীতাকুণ্ড, চট্টগ্রাম </div>
    </div>

    {{-- Header --}}
    <div class="text-center position-relative">
        <div class="bill-title">বিদ্যুৎ বিল</div>
        <div class="bill-copy-tag">( গ্রাহক কপি )</div>
    </div>

    {{-- Top: customer info + bill dates --}}
    <div class="row g-0 bill-block">
        <div class="col-6 p-2 border-black-right">
            <div class="kv"><span>বিলের মাস</span> <b> {{ $bnMonthYear($billMonth) }}</b></div>
            <div class="kv"><span>বিল প্রস্তুতের তারিখ</span><b>{{ $bnDate($prepDate) }}</b></div>
            <div class="kv"><span>পরিশোধের শেষ তারিখ</span><b>{{ $bnDate($lastDate) }}</b></div>
            <div class="kv"><span>সংযোগের ধরন</span><b>{{ $customer->connection_type_bangla }}</b></div>
        </div>
        <div class="col-6 p-2 ">
            <div class="kv boxed-right"><span>এরিয়া কোড</span><b>{{ $customer->sheet->name ?? '—' }}</b></div>
            <div class="kv boxed boxed-right"><span>হিসাব নং</span><b>{{ $customer->serial_no ?? '—' }}</b></div>
            <div class="kv boxed boxed-right"><span>গ্রাহকের নাম</span><b>{{ $customer->name }}</b></div>
            @if (filled($customer->father_or_husband_name))
                <div class="kv boxed boxed-right"><span>পিতা/স্বামীর নাম</span><b>{{ $customer->father_or_husband_name ?? '—' }}</b></div>
            @endif
            <div class="kv boxed boxed-right"><span>ঠিকানা</span><b>{{ $customer->address ?? '—' }}</b></div>
            @if (filled($customer->mobile_number))
                <div class="kv boxed boxed-right"><span>মোবাইল নং</span><b>{{ $customer->mobile_number }}</b></div>
            @endif
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
                    <td>{{ $bn(number_format($pb->units, 0)) }}</td>
                    <td>{{ $bn(number_format($pb->total_amount, 0)) }}</td>
                    <td>{{ $bn(number_format($pb->paid_amount, 0)) }}</td>
                    <td>{{ $bn(number_format($pb->discount, 0)) }}</td>
                    <td>{{ $bn(number_format($pb->due_amount, 0)) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted">কোন আগের বিল নেই</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Meter reading + charge breakdown --}}
    <div class="row g-0 bill-mid">
        {{-- Meter reading --}}
        <div class="col-5 ">
            <table class="table table-bordered bill-table mb-0 h-100">
                <thead>
                    <tr class="text-center"><th colspan="2">মিটার রিডিং</th></tr>
                    <tr class="text-center"><th>তারিখ</th><th>ইউনিট</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td>বর্তমান<br><small>{{ $bnMonthYear($currentReadingDate) }}</small></td>
                        <td class="text-center align-middle">{{ $bn(number_format($currentReading, 0)) }}</td>
                    </tr>
                    <tr>
                        <td>পূর্ববতী<br><small>{{ $bnMonthYear($previousReadingDate) }}</small></td>
                        <td class="text-center align-middle">{{ $bn(number_format($previousReading, 0)) }}</td>
                    </tr>
                    <tr>
                        <td>ব্যবহৃত ইউনিট</td>
                        <td class="text-center align-middle">{{ $bn(number_format($units, 0)) }}</td>
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
                    <tr><td>ব্যবহৃত ইউনিট মূল্য</td><td class="text-end">{{ $bn(number_format($energyCharge, 0)) }}</td></tr>
                    <tr><td>লাইন চার্জ</td><td class="text-end">{{ $bn(number_format($lineCharge, 0)) }}</td></tr>
                    <tr><td>সার্ভিস চার্জ</td><td class="text-end">{{ $bn(number_format($serviceCharge, 0)) }}</td></tr>
                    <tr><td>ডিমান্ড চার্জ</td><td class="text-end">{{ $bn(number_format($demandCharge, 0)) }}</td></tr>
                    <tr><td>বকেয়া বিল</td><td class="text-end">{{ $bn(number_format($previousOutstanding, 0)) }}</td></tr>
                    <tr><td>বকেয়া বিলের জরিমানা</td><td class="text-end">{{ $bn(number_format($lateFee, 0)) }}</td></tr>
                    <tr><td>অতিরিক্ত চার্জ</td><td class="text-end">{{ $bn(number_format($fixedCharge, 0)) }}</td></tr>
                    <tr>
                        <td>বিদ্যুৎ শুল্ক{{ $electricityDutyRate > 0 ? ' ('.$bn(rtrim(rtrim(number_format($electricityDutyRate, 2), '0'), '.')).'%)' : '(%)' }}</td>
                        <td class="text-end">{{ $bn(number_format($electricityDuty, 0)) }}</td>
                    </tr>
                    <!-- @php $discount = $discount ?? 0; @endphp -->
                    @php $discount =  0; @endphp

                    <tr class="fw-bold"><td>মোট বিল</td><td class="text-end">{{ $bn(number_format($totalAmount, 0)) }}</td></tr>
                    <tr><td>ছাড়(-)</td><td class="text-end">{{ $bn(number_format($discount, 0)) }}</td></tr>
                    <tr class="fw-bold "><td>বিল</td><td class="text-end">{{ $bn(number_format($totalAmount - $discount, 0)) }}৳</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- Preparer — no border-top: the tables above already draw that line --}}
    <div class="row g-0 bill-block border-top-0">
        <div class="col-6 p-2 ">
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
        <ul class="mb-1 small fw-bold">
            <li>উপরোক্ত বিলের টাকা নির্দিষ্ট তারিখের মধ্যে কর্তৃপক্ষের বরাবরে পরিশোধ করিতে হইবে।</li>
            <li>সাইড লাইন ব্যবহার করা যাবে না। ৯৯৯ টাকার ঊর্ধ্বে বকেয়া বিলের উপর ১০% জরিমানা যোগ করা হবে।</li>
            <li>নির্ধারিত তারিখের মধ্যে বিল পরিশোধ না করিলে সংযোগ বিচ্ছিন্ন করা হবে।</li>
        </ul>
        {{-- Where to call, and where to check the bill online. The portal URL
             is printed in full, scheme included, and follows APP_URL. --}}
        <div class="small bill-contact fw-bold">
            হটলাইন: <b>{{ $bn('01633380033') }}</b>
            <span class="bill-contact-sep">|</span>
            অনলাইনে বিল দেখুন:
            <a href="{{ route('portal.login') }}" target="_blank" rel="noopener">{{ route('portal.login') }}</a>
        </div>
        <div class="small fw-bold" style="margin-top: -1px;">
            ১) বিদ্যুৎ সাশ্রয়ের মাধ্যমে বিদ্যুৎ বিল কমান | <br>২) বাতি/ফ্যান ব্যবহারে সচেতন হোন |<br>
            ৩) বিদ্যুৎ সাশ্রয়ী বাতি (CFL/T5 টিউব লাইট) ব্যবহার করুন |
        </div>
    </div>

    {{-- Cut separator between customer copy and office copy --}}
    <div class="bill-cut"><span>&#9986;</span></div>

    {{-- Office copy --}}
    {{-- Sides/bottom come from the outer .bill-copy frame; only the top line is ours --}}
    <div class="bill-office-copy border-top border-dark">
        <div class="p-2 text-center fw-bold border-bottom border-dark">অফিস কপি</div>
        <div class="row g-0">
            <div class="col-7 p-2 border-end border-dark">
                <div class="d-flex mb-1"><span class="me-1 min-space">গ্রাহকের নাম</span>:<b>{{ $customer->name }}</b></div>
                @if (filled($customer->father_or_husband_name))
                    <div class="d-flex mb-1"><span class="me-1 min-space">পিতা/স্বামীর নাম</span>:<b>{{ $customer->father_or_husband_name ?? '—' }}</b></div>
                @endif
                <div class="d-flex"><span class="me-1 min-space">ঠিকানা</span>:<b>{{ $customer->address ?? '—' }}</b></div>
                @if (filled($customer->mobile_number))
                    <div class="d-flex mb-1"><span class="me-1 min-space">মোবাইল নং</span>:<b>{{ $customer->mobile_number }}</b></div>
                @endif
                <div class="d-flex"><span class="me-1 min-space">বিলের মাস</span>:<b>{{ $bnMonthYear($billMonth) }}</b></div>
                <div class="d-flex"><span class="me-1 min-space">সংযোগের ধরন</span>:<b>{{ $customer->connection_type_bangla }}</b></div>
            </div>

            <div class="col-5 p-2 font-office-copy">
                <div class="d-flex mb-1"><span class="me-1 min-space-right">হিসাব নং</span>: <b>{{ $customer->serial_no ?? '—' }}</b></div>
                <div class="d-flex"><span class="me-1 min-space-right">মোট বিল</span>: <b>{{ $bn(number_format($totalAmount, 0)) }}৳</b></div>
            </div>
        </div>

        {{-- Signature section --}}
        <div class="row g-0 px-2 pt-2 pb-4 border-top border-dark bill-signature">
            <div class="col-6">
                গ্রাহকের স্বাক্ষর
            </div>
            <div class="col-6 text-end">
                আদায়কারীর স্বাক্ষর ও তারিখ
            </div>
        </div>
    </div>
</div>

@once
@push('styles')
<style>
    .bill-copy {
        max-width: 182mm;
        width: 182mm;
        margin: 0 auto;
        border: 1px solid #000;
        background: #fff;
        position: relative;
        box-sizing: border-box;
    }
    .bill-org { line-height: 1.35; }
    .bill-org .org-bismillah { font-size: 12px; }
    .bill-org .org-slogan { font-size: 12px; font-weight: 700; }
    .bill-org .org-name { font-size: 12px; }
    .bill-org .org-addr { font-size: 12px; font-weight: 700; }
    .bill-title { font-size: 20px; font-weight: 700; padding: 3px 3px; }
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
    .bill-disclaimer-vertical {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        border-right: 1px solid #000;
        padding: 8px 0;
    }
    .bill-disclaimer-text {
        writing-mode: vertical-rl;
        text-orientation: mixed;
        font-size: 11px;
        font-weight: 600;
        white-space: nowrap;
    }
    .border-black-right { border-right: var(--bs-border-width) var(--bs-border-style) #000 !important;}
    .bill-signature { font-size: 11px; }
    .bill-office-copy { font-size: 14px; }
    .bill-contact { margin: 2px 0 4px; }
    /* Clickable on screen, plain black text on paper. */
    .bill-contact a { color: inherit; text-decoration: underline; }
    .boxed-right > span {
        min-width: 90px !important;
    }
    @media print {
        .bill-contact a { color: #000; text-decoration: none; }
    }
    .bill-contact-sep { padding: 0 4px; color: #555; }
    .bill-copy .kv { display: flex; font-size: 13px; padding: 1px 0; }
    .bill-copy .kv > span { min-width: 130px;    margin-right: 2px; }
    .bill-copy .kv > span::after { content: ' :'; float: right; }
    /* Labels never wrap; date values (left column) stay on one line too.
       Long values on the right (name/address) may still wrap normally. */
    .bill-copy .kv > span { white-space: nowrap; flex-shrink: 0; }
    .border-black-right .kv > b { white-space: nowrap; }
    .bill-table th, .bill-table td { padding: 3px 6px; font-size: 13px; }
    .bill-table { border-color: #000 !important; }
    .bill-table th, .bill-table td { border-color: #000 !important; }
    /* Single-weight rules everywhere: tables drop their outermost vertical
       borders (the .bill-copy frame already draws them)... */
    .bill-table > :not(caption) > * > :first-child { border-left: 0 !important; }
    .bill-table > :not(caption) > * > :last-child { border-right: 0 !important; }
    /* ...the divider between the meter and charges tables is drawn once,
       by the column wrapper (spans the full row height too)... */
    .bill-mid > .col-5 { border-right: 1px solid #000; }
    /* ...and the line under the previous-bills table belongs to that table
       alone, so the tables below start with no top border. */
    .bill-mid .bill-table thead tr:first-child,
    .bill-mid .bill-table thead tr:first-child > th { border-top: 0 !important; }
    .min-space {
        min-width: 80px;
    }
    .min-space-right {
            min-width: 110px;
    }
    .font-office-copy{
        font-size: 15px !important;
        font-weight: 700;
    }
    @media print {
        /* Zero page margin so the browser drops its own header/footer. */
        @page { size: 182mm 257mm; margin: 0; }
        html, body { height: auto; }
        body { box-sizing: border-box !important; width: auto !important; padding: 2mm 4mm !important; }
        .no-print { display: none !important; }
        .navbar, nav { display: none !important; }
        /* Strip app chrome/spacing so only the bill prints */
        #app > main.py-4, main.py-4 { padding: 0 !important; }
        .container, .container-fluid { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
        /* Fit the whole bill (both copies) on a single B5 page.
           Flex column pinned to the full page height: leftover space
           (previous-bills table can have 0–3 rows) collects above the
           cut line, so the office copy always ends at the page bottom. */
        .bill-copy {
            border: 1px solid #000;
            max-width: 100%;
            width: 100%;
            margin: 0 auto;
            page-break-inside: avoid;
            break-inside: avoid;
            padding: 0;
            height: 252mm; /* 257mm page − 2×2mm body padding, minus a hair for rounding */
            display: flex;
            flex-direction: column;
        }
        .bill-copy > * { flex-shrink: 0; }
        /* Leftover page space is absorbed by the customer copy's meter
           reading + charges tables (their rows stretch evenly), so there is
           no visible void: the cut line stays snug after the instructions
           and a compact office copy ends at the page bottom. */
        .bill-copy > .row.g-0:not(.bill-block) { flex-grow: 1; }
        .bill-copy > .row.g-0:not(.bill-block) .bill-table { height: 100%; }

        .bill-copy > .bill-org {
            padding-left: 17mm;
            padding-right: 3mm;
            padding-top: 2mm;
        }
        .bill-copy > .text-center.position-relative {
            padding-left: 3mm;
            padding-right: 3mm;
            padding-bottom: 0;
            padding-top: 0;
        }
        .bill-copy tr, .bill-copy .bill-block, .bill-copy table { page-break-inside: avoid; break-inside: avoid; }
        /* Optimized spacing for B5 paper - compact */
        .bill-org { line-height: 1.25; }
        .bill-org .org-bismillah { font-size: 10px; }
        .bill-org .org-slogan { font-size: 10px; }
        .bill-org .org-name { font-size: 11px; line-height: 1.2; }
        .bill-org .org-addr { font-size: 10px; line-height: 1.3; }
        .bill-title { font-size: 20px; font-weight: 700; padding: 3px 3px; }
        .bill-copy-tag { font-size: 10px; }
        /* Slightly smaller than screen: real printers render a hair narrower
           than the preview, which used to wrap the labels and dates. */
        .bill-copy .kv { font-size: 12px; padding: 0.3px 0; line-height: 1.45; }
        .bill-copy .kv > span { min-width: 120px; margin-right: 2px;}
        .bill-table th, .bill-table td { padding: 1.5px 3.5px; font-size: 10px; line-height: 1.3; }
        .bill-block.p-2 { padding: 1.2mm !important; }
        .row.g-0.bill-block .col-5.p-2, .row.g-0.bill-block .col-6.p-2, .row.g-0.bill-block .col-7.p-2 { padding: 1.2mm !important; }
        .bill-disclaimer-vertical { width: 32px; padding: 6px 0; border-right-width: 0.5px; }
        .bill-disclaimer-text { font-size: 9px; }
        .bill-signature { font-size: 9px; padding-bottom: 6mm !important; }
        .bill-office-copy { font-size: 14px; }
        .bill-cut { height: 13px; margin: 3.5px 0; border-top-width: 1px; }
        .bill-cut span { font-size: 12px; line-height: 13px; top: -6.5px; }
        .bill-qr { width: 62px; left: 6.5px; top: 6.5px; }
        .bill-qr img { width: 56px; height: 56px; }
        .bill-qr-cap { font-size: 7.5px; }
        ul.small { font-size: 9.5px; margin-bottom: 0.7mm !important; padding-left: 15px; }
        ul.small li { margin-bottom: 0.2mm; line-height: 1.35; }
        .small.bill-contact { font-size: 9.5px; margin: 1.2mm 0 2mm; }
        .bill-contact + .small { font-size: 9.5px; line-height: 1.35; }
       .border-black-right {
            border-top: 0 !important;
            border-bottom: 0 !important;
            border-left: 0 !important;
            border-right: 1px solid #000 !important;
        }
    .min-space {
            min-width: 80px;
        }
        .min-space-right {
                min-width: 110px;
        }
        .font-office-copy{
            font-size: 15px;
           font-weight: 700;
        }
    .boxed-right > span {
            min-width: 90px !important;
        }

    }
</style>
@endpush
@endonce
