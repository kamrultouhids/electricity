@extends('layouts.app')

@section('title', 'User Details')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">User Details</h4>
        <div>
            <a href="{{ route('users.edit', $user) }}" class="btn btn-primary text-white"><i class="bi bi-pencil-square me-1"></i>Edit</a>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to User List</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
            <table class="table table-borderless mb-0">
                <tbody>
                    <tr>
                        <th style="width:30%">Name</th>
                        <td>{{ $user->name }}</td>
                    </tr>
                    <tr>
                        <th>Email</th>
                        <td>{{ $user->email }}</td>
                    </tr>
                    <tr>
                        <th>User Type</th>
                        <td>{{ $user->user_type ? $user->user_type_label : '—' }}</td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            @if ($user->isActive())
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Created At</th>
                        <td>{{ $user->created_at?->format('d M Y, h:i A') }}</td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>
    </div>
</div>
@endsection
