@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Add Expense</h4>
        <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Expenses</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('expenses.store') }}">
                @include('expenses._form')
            </form>
        </div>
    </div>
</div>
@endsection
