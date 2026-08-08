@extends('layouts.app')

@section('content')
<div class="container">
    @include('reports._nav')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            Customer Report
            <span class="badge bg-primary px-1 py-0 small">{{ $customers->total() }}</span>
        </h5>
        @include('reports._actions')
    </div>

    {{-- Filters --}}
    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.customers') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
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
                <div class="col-md-2">
                    <label class="form-label mb-1">Status</label>
                    <select name="status" class="form-select">
                        <option value="all" @selected(request('status') === 'all' || ! request()->filled('status'))>All</option>
                        <option value="1" @selected(request('status') === '1')>Active</option>
                        <option value="0" @selected(request('status') === '0')>Inactive</option>
                    </select>
                </div>
                
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary text-white "><i class="bi bi-funnel me-1"></i>Filter</button>
                    <a href="{{ route('reports.customers') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card list-card rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="list-head">
                    <tr>
                        <th>Serial No</th>
                        <th>Name</th>
                        <th>Sheet</th>
                        <th>Meter No</th>
                        <th>Mobile</th>
                        <th>Connection </th>
                        <th class="text-end">Consumption</th>
                        <th class="text-end">Collected</th>
                        <th class="text-end">Discount</th>
                        <th class="text-end">Outstanding</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td>{{ $customer->serial_no }}</td>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->sheet->name ?? '—' }}</td>
                            <td>{{ $customer->meter_number ?? '—' }}</td>
                            <td>{{ $customer->mobile_number ?? '—' }}</td>
                            <td>
                                @if ($customer->isActive())
                                    <span class="badge rounded-pill bg-success-subtle text-success">Active</span>
                                @else
                                    <span class="badge rounded-pill bg-danger-subtle text-danger">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">{{ number_format((float) $customer->consumption_total, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $customer->paid_total, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $customer->discount_total, 2) }}</td>
                            <td class="text-end {{ (float) $customer->outstanding > 0 ? 'text-danger fw-bold' : '' }}">
                                {{ number_format((float) $customer->outstanding, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="text-center text-muted py-4">No customers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-center no-print">
        {{ $customers->links() }}
    </div>
</div>
@endsection
