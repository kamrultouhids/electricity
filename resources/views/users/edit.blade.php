@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Edit User</h4>
        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to User List</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('users.update', $user) }}">
                @method('PUT')
                @include('users._form')
            </form>
        </div>
    </div>
</div>
@endsection
