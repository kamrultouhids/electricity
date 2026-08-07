@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Collect Payment</h4>
        <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-secondary">Back to Customer</a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3">
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
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Amount <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0" name="amount" id="amount"
                                       class="form-control" required
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
                                <small class="text-muted">Remaining due after: <span id="remaining"></span></small>
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
</div>
@endsection

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
