@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            Customers with Due
            <span class="badge bg-primary px-1 py-0 small">{{ $customers->total() }}</span>
        </h5>
        <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary">Payment History</a>
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
            <form method="GET" action="{{ route('payments.due') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                           placeholder="Serial No, Name, Mobile or Meter No">
                </div>
                <div class="col-md-3">
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
                <div class="col-md-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary text-white w-100">Filter</button>
                    <a href="{{ route('payments.due') }}" class="btn btn-outline-secondary">Reset</a>
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
                        <th class="text-end">Due Months</th>
                        <th class="text-end">Total Due</th>
                        <th class="text-end" width="120">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div>{{ $customer->name }}</div>
                                <small class="text-muted d-block">Meter No: {{ $customer->meter_number ?? '—' }}</small>
                                <small class="text-muted d-block">Mobile: {{ $customer->mobile_number ?? '—' }}</small>
                            </td>
                            <td>{{ $customer->sheet->name ?? '—' }}</td>
                            <td class="text-end">{{ $customer->due_bills_count }}</td>
                            <td class="text-end fw-bold">{{ number_format($customer->total_due, 2) }}</td>
                            <td class="text-end">
                                <a href="{{ route('payments.create', $customer) }}" class="btn btn-sm btn-success text-white">
                                    Pay
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No customers with due.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-center">
        {{ $customers->links() }}
    </div>
</div>
@endsection
