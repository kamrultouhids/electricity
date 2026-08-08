@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            Unit Consumption Report
            <span class="badge bg-primary px-1 py-0 small">{{ $rows->total() }}</span>
        </h5>
        @include('reports._actions')
    </div>

    {{-- Filters --}}
    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.unit-consumption') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Serial / Name / Mobile / Meter">
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
                    <label class="form-label mb-1">From Date</label>
                    <input type="date" name="from_date" value="{{ $from }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">To Date</label>
                    <input type="date" name="to_date" value="{{ $to }}" class="form-control">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary text-white "><i class="bi bi-funnel me-1"></i>Filter</button>
                    <a href="{{ route('reports.unit-consumption') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card list-card rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="list-head">
                    <tr>
                        <th>Serial</th>
                        <th>Name</th>
                        <th>Sheet</th>
                        <th>Meter</th>
                        <th class="text-end">Readings</th>
                        <th class="text-end">Total Units</th>
                        <th>Last Reading</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row->customer->serial_no ?? '—' }}</td>
                            <td>{{ $row->customer->name ?? '—' }}</td>
                            <td>{{ $row->customer->sheet->name ?? '—' }}</td>
                            <td>{{ $row->customer->meter_number ?? '—' }}</td>
                            <td class="text-end">{{ $row->readings_count }}</td>
                            <td class="text-end">{{ number_format((float) $row->total_units, 2) }}</td>
                            <td>{{ $row->last_reading ? \Illuminate\Support\Carbon::parse($row->last_reading)->format('d M Y') : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-4">No readings found.</td></tr>
                    @endforelse
                </tbody>
                @if ($rows->count())
                    <tfoot>
                        <tr class="fw-bold table-light">
                            <td colspan="5" class="text-end">Total Units (this page)</td>
                            <td class="text-end">{{ number_format($rows->sum('total_units'), 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-center no-print">
        {{ $rows->links() }}
    </div>
</div>
@endsection
