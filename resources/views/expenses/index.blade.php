@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            Expenses
            <span class="badge bg-primary px-1 py-0 small">{{ $expenses->total() }}</span>
        </h5>
        <a href="{{ route('expenses.create') }}" class="btn btn-primary text-white"><i class="bi bi-plus-lg me-1"></i>Add Expense</a>
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
            <form method="GET" action="{{ route('expenses.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">Search Note</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Note">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1">Category</label>
                    <select name="expense_category_id" class="form-select">
                        <option value="">All</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected(request('expense_category_id') == $category->id)>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">From Date</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">To Date</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary text-white "><i class="bi bi-funnel me-1"></i>Filter</button>
                    <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
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
                        <th>Date</th>
                        <th>Category</th>
                        <th>Note</th>
                        <th>Added By</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end" width="200">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($expenses as $expense)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $expense->expense_date->format('d M Y') }}</td>
                            <td>{{ $expense->category->name ?? '—' }}</td>
                            <td>{{ $expense->note ?? '—' }}</td>
                            <td>{{ $expense->createdBy->name ?? '—' }}</td>
                            <td class="text-end">{{ number_format($expense->amount, 2) }}</td>
                            <td class="text-end" width="200">
                               <a href="{{ route('expenses.edit', $expense) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Edit</a>
                                    <a href="{{ route('expenses.delete', $expense) }}" class="btn btn-sm btn-outline-danger"
                                       onclick="return confirm('Delete this expense?');"><i class="bi bi-trash me-1"></i>Delete</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No expenses found.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($expenses->count())
                    <tfoot>
                        <tr class="fw-bold table-light">
                            <td colspan="5" class="text-end">Total (filtered)</td>
                            <td class="text-end">{{ number_format($total, 2) }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-center">
        {{ $expenses->links() }}
    </div>
</div>
@endsection
