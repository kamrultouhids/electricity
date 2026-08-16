@extends('layouts.app')

@section('title', 'Add Expense Category')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Add Expense Category</h4>
        <a href="{{ route('expense-categories.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Categories</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('expense-categories.store') }}">
                @include('expense_categories._form')
            </form>
        </div>
    </div>
</div>
@endsection
