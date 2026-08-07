@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h4 class="mb-0">Profit &amp; Loss</h4>
        <button type="button" onclick="window.print()" class="btn btn-outline-secondary"><i class="bi bi-printer me-1"></i>Print</button>
    </div>

    {{-- Period filter --}}
    <div class="card mb-3 no-print">
        <div class="card-body">
            <form method="GET" action="{{ route('expenses.profit-loss') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">From Date</label>
                    <input type="date" name="from_date" value="{{ $from }}" class="form-control">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">To Date</label>
                    <input type="date" name="to_date" value="{{ $to }}" class="form-control">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary text-white"><i class="bi bi-funnel me-1"></i>Fliter</button>
                    <a href="{{ route('expenses.profit-loss') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="text-center mb-3">
                <h5 class="mb-0">Profit &amp; Loss Statement</h5>
                <small class="text-muted">
                    {{ \Illuminate\Support\Carbon::parse($from)->format('d M Y') }}
                    &mdash; {{ \Illuminate\Support\Carbon::parse($to)->format('d M Y') }}
                </small>
            </div>

            <div class="table-responsive">
            <table class="table table-bordered mb-0">
                <tbody>
                    <tr class="table-light fw-bold">
                        <td colspan="2">Collections (Payments)</td>
                    </tr>
                    <tr>
                        <td>Total Collected</td>
                        <td class="text-end">{{ number_format($collections, 2) }}</td>
                    </tr>

                    <tr class="table-light fw-bold">
                        <td colspan="2">Expenses</td>
                    </tr>
                    @forelse ($expenseByCategory as $row)
                        <tr>
                            <td>{{ $row->category->name ?? '—' }}</td>
                            <td class="text-end">{{ number_format($row->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr><td class="text-muted">No expenses in this period</td><td class="text-end">0.00</td></tr>
                    @endforelse
                    <tr class="fw-bold">
                        <td class="text-end">Total Expense</td>
                        <td class="text-end">{{ number_format($totalExpense, 2) }}</td>
                    </tr>

                    <tr class="fw-bold {{ $net >= 0 ? 'table-success' : 'table-danger' }}">
                        <td>Net {{ $net >= 0 ? 'Profit' : 'Loss' }}</td>
                        <td class="text-end">{{ number_format($net, 2) }}</td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    @media print {
        .no-print { display: none !important; }
        .navbar, nav { display: none !important; }
    }
</style>
@endpush
