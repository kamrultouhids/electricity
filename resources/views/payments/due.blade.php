@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3 no-print">
        <h5 class="mb-0">
            Customers with Due
            <span class="badge bg-primary px-1 py-0 small">{{ $bills->total() }}</span>
        </h5>
        <div class="d-flex gap-2 no-print">
            <button type="button" onclick="window.print()" class="btn btn-outline-secondary">
                <i class="bi bi-printer me-1"></i>Print
            </button>
            <a href="{{ route('payments.index') }}" class="btn btn-outline-secondary"><i class="bi bi-clock-history me-1"></i>Payment History</a>
        </div>
    </div>

    {{-- Print-only heading --}}
    <div class="text-center mb-3 d-none d-print-block">
        <h5 class="mb-0">Due List</h5>
        <small>Generated {{ now()->format('d M Y, h:i A') }}</small>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Filters --}}
    <div class="card mb-3 no-print">
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
                    <button type="submit" class="btn btn-primary text-white "><i class="bi bi-funnel me-1"></i>Filter</button>
                    <a href="{{ route('payments.due') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    {{-- List --}}
    <div class="card list-card rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="list-head">
                    <tr>
                        <th>#</th>
                        <th>Customer</th>
                        <th>Sheet</th>
                        <th>Latest Bill</th>
                        <th class="text-end">Total Due</th>
                        <th class="text-end no-print" width="120">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bills as $bill)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                @include('partials.customer_cell', ['customer' => $bill->customer, 'showAddress' => true])
                            </td>
                            <td>{{ $bill->customer->sheet->name ?? '—' }}</td>
                            <td>{{ $bill->billing_month->format('M Y') }}</td>
                            <td class="text-end fw-bold">{{ number_format($bill->due_amount, 2) }}</td>
                            <td class="text-end no-print">
                                <a href="{{ route('payments.create', $bill->customer) }}" class="btn btn-sm btn-success text-white">
                                    <i class="bi bi-cash-coin me-1"></i>Pay
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No customers with due.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($bills->count())
                    <tfoot>
                        <tr class="fw-bold table-light">
                            <td colspan="4" class="text-end">Total Due (all filtered)</td>
                            <td class="text-end">{{ number_format($totalDue, 2) }}</td>
                            <td class="no-print"></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-center no-print">
        {{ $bills->links() }}
    </div>
</div>
@endsection

@push('styles')
<style>
    @media print {
        @page { size: A4 portrait; margin: 12mm; }
        body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .navbar, nav, .no-print { display: none !important; }
        main.py-4 { padding: 0 !important; }
        .container { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }
        .card { border: none !important; box-shadow: none !important; }
        table.table { width: 100% !important; border-collapse: collapse !important; font-size: 12px; }
        table.table th, table.table td { border: 1px solid #444 !important; padding: 4px 6px !important; }
        table.table thead { display: table-header-group; }
        table.table tfoot { display: table-footer-group; }
        table.table tr, table.table td, table.table th { page-break-inside: avoid; }
        .table-light, thead.table-light th { background: #eee !important; }
    }
</style>
@endpush
