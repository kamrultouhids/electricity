@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Edit Customer</h4>
        <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Customer List</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('customers.update', $customer) }}" enctype="multipart/form-data">
                @method('PUT')
                @include('customers._form')
            </form>
        </div>
    </div>
</div>
@endsection
