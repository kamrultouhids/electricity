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
        {{-- Unpaid bills --}}
        <div class="col-md-7">
            <div class="card h-100">
                <div class="card-header bg-white d-flex justify-content-between">
                    <h6 class="mb-0">Unpaid Bills — {{ $customer->name }}</h6>
                    <span class="text-muted small">Meter: {{ $customer->meter_number ?? '—' }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Month</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Paid</th>
                                <th class="text-end">Due</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($unpaidBills as $bill)
                                <tr>
                                    <td>{{ $bill->billing_month->format('M Y') }}</td>
                                    <td class="text-end">{{ number_format($bill->total_amount, 2) }}</td>
                                    <td class="text-end">{{ number_format($bill->paid_amount, 2) }}</td>
                                    <td class="text-end">{{ number_format($bill->due_amount, 2) }}</td>
                                    <td>
                                        @if ($bill->isPartial())
                                            <span class="badge bg-info text-dark">Partial</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Unpaid</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold table-light">
                                <td colspan="3" class="text-end">Total Due</td>
                                <td class="text-end">{{ number_format($totalDue, 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                <div class="card-footer text-muted small">
                    Payment is applied to the oldest bill first, then the next, until it runs out.
                </div>
            </div>
        </div>

        {{-- Payment form --}}
        <div class="col-md-5">
            <div class="card h-100">
                <div class="card-header bg-white"><h6 class="mb-0">Payment</h6></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('payments.store', $customer) }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Amount <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" max="{{ $totalDue }}"
                                       name="amount" class="form-control" required
                                       value="{{ old('amount', $totalDue) }}">
                                <small class="text-muted">Max (total due): {{ number_format($totalDue, 2) }}. Partial allowed.</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" class="form-control" required
                                       value="{{ old('payment_date', now()->format('Y-m-d')) }}">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Method <span class="text-danger">*</span></label>
                                <select name="method" class="form-select" required>
                                    @foreach ($methods as $value => $label)
                                        <option value="{{ $value }}" @selected(old('method', 'cash') === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
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
