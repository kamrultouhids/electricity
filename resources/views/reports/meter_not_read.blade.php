@extends('layouts.app')

@section('content')
<div class="container">
    @include('reports._nav')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            Meter Not Read
            <span class="badge bg-primary px-1 py-0 small">{{ $customers->total() }}</span>
        </h5>
        @include('reports._actions')
    </div>

    {{-- Print-only heading --}}
    <div class="text-center mb-3 d-none d-print-block">
        <h5 class="mb-0">Meter Not Read — {{ \Illuminate\Support\Carbon::create($year, $month, 1)->format('F Y') }}</h5>
        <small>Generated {{ now()->format('d M Y, h:i A') }}</small>
    </div>

    {{-- Filters --}}
    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.meter-not-read') }}" class="row g-2 align-items-end">
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
                            <option value="{{ $sheet->id }}" @selected(request('sheet_id') == $sheet->id)>{{ $sheet->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Month</label>
                    <input type="month" name="month" value="{{ $monthInput }}" class="form-control">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary text-white"><i class="bi bi-funnel me-1"></i>Filter</button>
                    <a href="{{ route('reports.meter-not-read') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card list-card rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="list-head">
                    <tr>
                        <th>#</th>
                        <th>Serial No</th>
                        <th>Name</th>
                        <th>Sheet</th>
                        <th>Meter No</th>
                        <th>Mobile</th>
                        <th>Connection Date</th>
                        <th class="text-end no-print" width="120">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $customer->serial_no ?? '—' }}</td>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->sheet->name ?? '—' }}</td>
                            <td>{{ $customer->meter_number ?? '—' }}</td>
                            <td>{{ $customer->mobile_number ?? '—' }}</td>
                            <td>{{ $customer->connection_date ? $customer->connection_date->format('d M Y') : '—' }}</td>
                            <td class="text-end no-print">
                                <a href="{{ route('meter-readings.create') }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-plus-lg me-1"></i>Add Reading
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">All connected customers have a reading for this month.</td></tr>
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
