@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            Payments
            <span class="badge bg-primary px-1 py-0 small">{{ $payments->total() }}</span>
        </h5>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('payments.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                           placeholder="Serial No, Name, Mobile or Meter No">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Method</label>
                    <select name="method" class="form-select">
                        <option value="">All</option>
                        @foreach ($methods as $value => $label)
                            <option value="{{ $value }}" @selected(request('method') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">From Date</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">To Date</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary text-white "><i class="bi bi-funnel me-1"></i>Filter</button>
                    <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    {{-- List --}}
    <div class="card list-card rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="list-head">
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Discount</th>
                        <th>Method</th>
                        <th>Collector</th>
                        <th>Date</th>
                        <th class="text-end" width="150">Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                @include('partials.customer_cell', ['customer' => $payment->customer])
                            </td>
                            <td class="text-end">{{ number_format($payment->amount, 2) }}</td>
                            <td class="text-end">{{ number_format($payment->discount, 2) }}</td>
                            <td>{{ $payment->methodLabel() }}</td>
                            <td>{{ $payment->collector->name ?? '—' }}</td>
                            <td>{{ $payment->payment_date->format('d M Y') }}</td>
                            <td class="text-end">
                                <a href="{{ route('payments.receipt', $payment) }}" class="btn btn-outline-primary"><i class="bi bi-receipt me-1"></i>Receipt</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No payments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-center">
        {{ $payments->links() }}
    </div>
</div>
@endsection
