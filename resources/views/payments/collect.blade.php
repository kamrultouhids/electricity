@extends('layouts.app')

@section('content')
<div class="container">
    @include('partials.electricity_nav')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Collect Payment</h5>
        <div class="d-flex gap-2">
            <a href="{{ route('payments.due') }}" class="btn btn-outline-secondary"><i class="bi bi-cash-stack me-1"></i>Due List</a>
            <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary"><i class="bi bi-clock-history me-1"></i>Payment History</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success d-flex justify-content-between align-items-center">
            <span>{{ session('success') }}</span>
            @if (session('receipt_payment_id'))
                {{-- New tab, so printing the receipt never leaves this page. --}}
                <a href="{{ route('payments.receipt', session('receipt_payment_id')) }}" target="_blank" rel="noopener"
                   class="btn btn-sm btn-outline-success">
                    <i class="bi bi-printer me-1"></i>Print Receipt
                </a>
            @endif
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Find the customer --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('payments.collect') }}" class="row g-2 align-items-end">
                <div class="col-md-9">
                    <label class="form-label mb-1">Search Customer</label>
                    <input type="text" name="search" value="{{ $search }}" class="form-control" @unless ($customer) autofocus @endunless
                           placeholder="Serial No, Name, Mobile or Meter No">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary text-white"><i class="bi bi-search me-1"></i>Search</button>
                    <a href="{{ route('payments.collect') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    {{-- Selected customer: bill summary + payment form --}}
    @if ($customer)
        @if (! $bill)
            <div class="alert alert-warning">
                <strong>{{ $customer->name }}</strong> has no due to collect.
            </div>
        @else
            <div class="row g-3 mb-3">
                {{-- Latest bill summary --}}
                <div class="col-md-5">
                    <div class="card h-100">
                        <div class="card-header bg-white"><h6 class="mb-0">Latest Bill — {{ $customer->name }}</h6></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-6">
                                    <div class="text-muted small">Meter No</div>
                                    <div>{{ $customer->meter_number ?? '—' }}</div>
                                </div>
                                <div class="col-6">
                                    <div class="text-muted small">Billing Month</div>
                                    <div>{{ $bill->billing_month->format('M Y') }}</div>
                                </div>
                                <div class="col-4">
                                    <div class="text-muted small">Total</div>
                                    <div>{{ number_format($bill->total_amount, 2) }}</div>
                                </div>
                                <div class="col-4">
                                    <div class="text-muted small">Paid</div>
                                    <div>{{ number_format($bill->paid_amount, 2) }}</div>
                                </div>
                                <div class="col-4">
                                    <div class="text-muted small">Due</div>
                                    <div class="fw-bold">{{ number_format($bill->due_amount, 2) }}</div>
                                </div>
                            </div>
                            <div class="text-muted small mt-2">
                                This due already carries forward all previous months.
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Payment form --}}
                <div class="col-md-7">
                    <div class="card h-100">
                        <div class="card-header bg-white"><h6 class="mb-0">Payment</h6></div>
                        <div class="card-body">
                            <form method="POST" action="{{ route('payments.store', $customer) }}">
                                @csrf
                                {{-- Carried through the redirect so the search survives the post. --}}
                                <input type="hidden" name="search" value="{{ $search }}">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" min="0" name="amount" id="amount"
                                               class="form-control" required autofocus
                                               value="{{ old('amount', $bill->due_amount) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Discount</label>
                                        <input type="number" step="0.01" min="0" name="discount" id="discount"
                                               class="form-control" value="{{ old('discount', 0) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Settling (Amount + Discount)</label>
                                        <input type="number" id="settling" class="form-control" readonly>
                                        <h6 class="text-bold">Remaining due after: <span id="remaining"></span></h6>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                                        <input type="date" name="payment_date" class="form-control" required
                                               value="{{ old('payment_date', now()->format('Y-m-d')) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Method <span class="text-danger">*</span></label>
                                        <select name="method" class="form-select" required>
                                            @foreach ($methods as $value => $label)
                                                <option value="{{ $value }}" @selected(old('method', 'cash') === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Note</label>
                                        <input type="text" name="note" class="form-control" placeholder="Optional"
                                               value="{{ old('note') }}">
                                    </div>
                                </div>

                                <div class="mt-4 text-end">
                                    <button type="submit" class="btn btn-primary text-white">Record Payment</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif

    {{-- Search results — skipped when the search landed on a single customer,
         whose payment form is already open above. --}}
    @if ($search === '')
        @unless ($customer)
            <div class="card list-card rounded-4">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-search fs-3 d-block mb-2"></i>
                    Search by Serial No, Name, Mobile or Meter No to collect a payment.
                </div>
            </div>
        @endunless
    @elseif ($customers->total() === 0)
        <div class="card list-card rounded-4">
            <div class="card-body text-center text-muted py-5">
                No customer found for &ldquo;{{ $search }}&rdquo;.
            </div>
        </div>
    @elseif ($customers->total() > 1)
        <div class="card list-card rounded-4">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="list-head">
                        <tr>
                            <th>#</th>
                            <th>Customer</th>
                            <th>Sheet</th>
                            <th>Latest Due Bill</th>
                            <th class="text-end">Due</th>
                            <th class="text-end" width="150">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customers as $row)
                            @php $dueBill = $dueBills->get($row->id); @endphp
                            <tr @class(['table-active' => $customer && $customer->id === $row->id])>
                                <td>{{ $loop->iteration }}</td>
                                <td>
                                    @include('partials.customer_cell', ['customer' => $row, 'showAddress' => true])
                                </td>
                                <td>{{ $row->sheet->name ?? '—' }}</td>
                                <td>{{ $dueBill ? $dueBill->billing_month->format('M Y') : '—' }}</td>
                                <td class="text-end fw-bold">
                                    {{ $dueBill ? number_format($dueBill->due_amount, 2) : '0.00' }}
                                </td>
                                <td class="text-end">
                                    @if (! $dueBill)
                                        <span class="badge bg-light text-muted border">No due</span>
                                    @elseif ($customer && $customer->id === $row->id)
                                        <span class="badge bg-success">Selected</span>
                                    @else
                                        <a href="{{ route('payments.collect', array_merge(request()->only('search', 'page'), ['customer' => $row->id])) }}"
                                           class="btn btn-sm btn-success text-white">
                                            <i class="bi bi-cash-coin me-1"></i>Collect
                                        </a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No customer found for &ldquo;{{ $search }}&rdquo;.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-3 d-flex justify-content-center">
            {{ $customers->appends(request()->only('search', 'customer'))->links() }}
        </div>
    @endif
</div>
@endsection

@if ($customer && $bill)
@push('scripts')
<script>
    const due = {{ (float) $bill->due_amount }};
    const amount = document.getElementById('amount');
    const discount = document.getElementById('discount');
    const settling = document.getElementById('settling');
    const remaining = document.getElementById('remaining');

    function recalc() {
        const s = (parseFloat(amount.value) || 0) + (parseFloat(discount.value) || 0);
        settling.value = s.toFixed(2);
        remaining.textContent = (due - s).toFixed(2);
    }

    amount.addEventListener('input', recalc);
    discount.addEventListener('input', recalc);
    recalc();
</script>
@endpush
@endif
