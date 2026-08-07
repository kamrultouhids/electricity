@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Income &amp; Expense Report</h5>
        @include('reports._actions')
    </div>

    {{-- Filters --}}
    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.income-expense') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">From Date</label>
                    <input type="date" name="from_date" value="{{ $from }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">To Date</label>
                    <input type="date" name="to_date" value="{{ $to }}" class="form-control">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary text-white"><i class="bi bi-funnel me-1"></i>Filter</button>
                    <a href="{{ route('reports.income-expense') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card text-center h-100"><div class="card-body">
                <div class="text-muted small">Total Income</div>
                <div class="h5 mb-0 text-success">{{ number_format($totalIncome, 2) }}</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card text-center h-100"><div class="card-body">
                <div class="text-muted small">Total Expense</div>
                <div class="h5 mb-0 text-danger">{{ number_format($totalExpense, 2) }}</div>
            </div></div>
        </div>
        <div class="col-md-4">
            <div class="card text-center h-100"><div class="card-body">
                <div class="text-muted small">Net {{ $net >= 0 ? 'Profit' : 'Loss' }}</div>
                <div class="h5 mb-0 {{ $net >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($net, 2) }}</div>
            </div></div>
        </div>
    </div>

    <div class="card">
        <div class="card-body pb-0">
            <div class="text-center mb-3">
                <h6 class="mb-0">Income &amp; Expense</h6>
                <small class="text-muted">
                    {{ \Illuminate\Support\Carbon::parse($from)->format('d M Y') }}
                    &mdash; {{ \Illuminate\Support\Carbon::parse($to)->format('d M Y') }}
                </small>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Month</th>
                        <th class="text-end">Income</th>
                        <th class="text-end">Expense</th>
                        <th class="text-end">Net</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ \Illuminate\Support\Carbon::parse($row['ym'] . '-01')->format('F Y') }}</td>
                            <td class="text-end">{{ number_format($row['income'], 2) }}</td>
                            <td class="text-end">{{ number_format($row['expense'], 2) }}</td>
                            <td class="text-end {{ $row['net'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($row['net'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center text-muted py-4">No data in this period.</td></tr>
                    @endforelse
                </tbody>
                @if ($rows->count())
                    <tfoot>
                        <tr class="fw-bold table-light">
                            <td class="text-end">Total</td>
                            <td class="text-end">{{ number_format($totalIncome, 2) }}</td>
                            <td class="text-end">{{ number_format($totalExpense, 2) }}</td>
                            <td class="text-end {{ $net >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($net, 2) }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
@endsection
