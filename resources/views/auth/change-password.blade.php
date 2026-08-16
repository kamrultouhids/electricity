@extends('layouts.app')

@section('title', 'Change Password')
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">

                @if (session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif

                <div class="card">
                    <div class="card-header">Change Password</div>
                    <div class="card-body">

                        <form method="POST" action="{{ route('update.password') }}">
                            @csrf

                            <div class="mb-3">
                                <label class="mb-2">Current Password<span class="text-danger">*</span></label>
                                <input type="password" name="current_password" class="form-control" required placeholder="Enter Your Current Password">
                                @error('current_password')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="mb-2">New Password<span class="text-danger">*</span></label>
                                <input type="password" name="password" class="form-control" required placeholder="Enter Your New Password">
                                @error('password')
                                <small class="text-danger">{{ $message }}</small>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label class="mb-2">Confirm New Password<span class="text-danger">*</span></label>
                                <input type="password" name="password_confirmation" class="form-control" required placeholder="Enter Your Confirm New Password">
                            </div>

                            <button class="btn btn-primary ">Update Password</button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
