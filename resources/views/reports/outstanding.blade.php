@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            Outstanding Balance Report
            <span class="badge bg-primary px-1 py-0 small">{{ $bills->total() }}</span>
        </h5>
        @include('reports._actions')
    </div>

    {{-- Filters --}}
    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.outstanding') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Serial / Name / Mobile / Meter">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">Sheet</label>
                    <select name="sheet_id" class="form-select">
                        <option value="">All</option>
                        @foreach ($sheets as $sheet)
                            <option value="{{ $sheet->id }}" @selected(request('sheet_id') == $sheet->id)>{{ $sheet->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary text-white "><i class="bi bi-funnel me-1"></i>Filter</button>
                    <a href="{{ route('reports.outstanding') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="alert alert-warning py-2">
        Total Outstanding (filtered): <strong>{{ number_format($total, 2) }}</strong>
    </div>

    <div class="card list-card rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="list-head">
                    <tr>
                        <th>Serial</th>
                        <th>Name</th>
                        <th>Sheet</th>
                        <th>Mobile</th>
                        <th>Meter</th>
                        <th>Last Billing Month</th>
                        <th class="text-end">Outstanding</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bills as $bill)
                        <tr>
                            <td>{{ $bill->customer->serial_no ?? '—' }}</td>
                            <td>{{ $bill->customer->name ?? '—' }}</td>
                            <td>{{ $bill->customer->sheet->name ?? '—' }}</td>
                            <td>{{ $bill->customer->mobile_number ?? '—' }}</td>
                            <td>{{ $bill->customer->meter_number ?? '—' }}</td>
                            <td>{{ optional($bill->billing_month)->format('M Y') ?? '—' }}</td>
                            <td class="text-end text-danger fw-bold">{{ number_format($bill->due_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No outstanding balances found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-center no-print">
        {{ $bills->links() }}
    </div>
</div>
@endsection
