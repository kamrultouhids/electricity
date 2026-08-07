@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            Pending Readings for Billing
            <span class="badge bg-primary px-1 py-0 small">{{ $readings->total() }}</span>
        </h5>
        <a href="{{ route('bills.index') }}" class="btn btn-outline-secondary"><i class="bi bi-receipt me-1"></i>Generated Bills</a>
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
            <form method="GET" action="{{ route('bills.pending') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
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
                <div class="col-md-3">
                    <label class="form-label mb-1">Month</label>
                    <input type="month" name="month" value="{{ request('month') }}" class="form-control">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary text-white w-100"><i class="bi bi-funnel me-1"></i>Filter</button>
                    <a href="{{ route('bills.pending') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
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
                        <th>Reading Month</th>
                        <th class="text-end">Previous</th>
                        <th class="text-end">Current</th>
                        <th class="text-end">Consumed</th>
                        <th class="text-end" width="140">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($readings as $reading)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                <div>{{ $reading->customer->name ?? '—' }}</div>
                                <small class="text-muted d-block">Meter No: {{ $reading->customer->meter_number ?? '—' }}</small>
                                <small class="text-muted d-block">Mobile: {{ $reading->customer->mobile_number ?? '—' }}</small>
                            </td>
                            <td>{{ $reading->customer->sheet->name ?? '—' }}</td>
                            <td>{{ $reading->reading_date->format('M Y') }}</td>
                            <td class="text-end">{{ number_format($reading->previous_reading, 2) }}</td>
                            <td class="text-end">{{ number_format($reading->current_reading, 2) }}</td>
                            <td class="text-end">{{ number_format($reading->consumed_units, 2) }}</td>
                            <td class="text-end">
                                <a href="{{ route('bills.preview', $reading) }}" class="btn btn-sm btn-primary text-white">
                                    <i class="bi bi-receipt me-1"></i>Generate Bill
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No pending readings.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-center">
        {{ $readings->links() }}
    </div>
</div>
@endsection
