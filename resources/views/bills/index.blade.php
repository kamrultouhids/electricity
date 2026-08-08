@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            Bills
            <span class="badge bg-primary px-1 py-0 small">{{ $bills->total() }}</span>
        </h5>
        <a href="{{ route('bills.pending') }}" class="btn btn-primary text-white">
            <i class="bi bi-hourglass-split me-1"></i>Pending Readings for Billing
            <span class="badge bg-light text-dark ms-1">{{ $pendingCount }}</span>
        </a>
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
            <form method="GET" action="{{ route('bills.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                           placeholder="Serial No, Name, Mobile or Meter No">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Sheet</label>
                    <select name="sheet_id" class="form-select">
                        <option value="">All</option>
                        @foreach ($sheets as $sheet)
                            <option value="{{ $sheet->id }}" @selected(request('sheet_id') == $sheet->id)>
                                {{ $sheet->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') !== null && request('status') !== '' && (int) request('status') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Month</label>
                    <input type="month" name="month" value="{{ request('month') }}" class="form-control">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary text-white "><i class="bi bi-funnel me-1"></i>Filter</button>
                    <a href="{{ route('bills.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    {{-- List --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Sheet</th>
                        <th>Month</th>
                        <th class="text-end">Units</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Due</th>
                        <th>Status</th>
                        <th class="text-end" width="130">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bills as $bill)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                @include('partials.customer_cell', ['customer' => $bill->customer])
                            </td>
                            <td>{{ $bill->customer->sheet->name ?? '—' }}</td>
                            <td>{{ $bill->billing_month->format('M Y') }}</td>
                            <td class="text-end">{{ number_format($bill->units, 2) }}</td>
                            <td class="text-end">{{ number_format($bill->total_amount, 2) }}</td>
                            <td class="text-end">{{ number_format($bill->paid_amount, 2) }}</td>
                            <td class="text-end">{{ number_format($bill->due_amount, 2) }}</td>
                            <td>
                                @if ($bill->isPaid())
                                    <span class="badge bg-success">Paid</span>
                                @elseif ($bill->isPartial())
                                    <span class="badge bg-info text-dark">Partial</span>
                                @else
                                    <span class="badge bg-warning text-dark">Unpaid</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('bills.show', $bill) }}" class="btn btn-outline-info"><i class="bi bi-eye me-1"></i>View</a>
                                    @unless ($bill->isPaid())
                                        <a href="{{ route('payments.create', $bill->customer) }}" class="btn btn-outline-success"><i class="bi bi-cash-coin me-1"></i>Pay</a>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No bills found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-center">
        {{ $bills->links() }}
    </div>
</div>
@endsection
