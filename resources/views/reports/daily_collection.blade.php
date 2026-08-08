@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Daily Collection Report</h5>
        @include('reports._actions')
    </div>

    {{-- Filters --}}
    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.daily-collection') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">From Date</label>
                    <input type="date" name="from_date" value="{{ $from }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">To Date</label>
                    <input type="date" name="to_date" value="{{ $to }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Method</label>
                    <select name="method" class="form-select">
                        <option value="">All</option>
                        @foreach ($methods as $key => $label)
                            <option value="{{ $key }}" @selected(request('method') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Collector</label>
                    <select name="collector_id" class="form-select">
                        <option value="">All</option>
                        @foreach ($collectors as $collector)
                            <option value="{{ $collector->id }}" @selected(request('collector_id') == $collector->id)>{{ $collector->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary text-white"><i class="bi bi-funnel me-1"></i>Filter</button>
                    <a href="{{ route('reports.daily-collection') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card list-card rounded-4">
        <div class="card-body pb-0">
            <div class="text-center mb-3">
                <h6 class="mb-0">Daily Collection</h6>
                <small class="text-muted">
                    {{ \Illuminate\Support\Carbon::parse($from)->format('d M Y') }}
                    &mdash; {{ \Illuminate\Support\Carbon::parse($to)->format('d M Y') }}
                </small>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="list-head">
                    <tr>
                        <th>Date</th>
                        <th class="text-end">Payments</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">Discount</th>
                        <th class="text-end">Total Settled</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($row->day)->format('d M Y') }}</td>
                            <td class="text-end">{{ $row->cnt }}</td>
                            <td class="text-end">{{ number_format($row->total_amount, 2) }}</td>
                            <td class="text-end">{{ number_format($row->total_discount, 2) }}</td>
                            <td class="text-end">{{ number_format($row->total_amount + $row->total_discount, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">No collections in this period.</td></tr>
                    @endforelse
                </tbody>
                @if ($rows->count())
                    <tfoot>
                        <tr class="fw-bold table-light">
                            <td class="text-end">Total</td>
                            <td class="text-end">{{ $rows->sum('cnt') }}</td>
                            <td class="text-end">{{ number_format($rows->sum('total_amount'), 2) }}</td>
                            <td class="text-end">{{ number_format($rows->sum('total_discount'), 2) }}</td>
                            <td class="text-end">{{ number_format($rows->sum('total_amount') + $rows->sum('total_discount'), 2) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
