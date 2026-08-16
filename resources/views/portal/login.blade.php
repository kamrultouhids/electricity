@extends('portal.layout')

@section('title', 'Customer Login')

@section('content')
<div class="container" style="max-width: 460px;">
    <div class="text-center mb-4 mt-4">
        <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-2"
             style="width:64px;height:64px;background:#1E3A5F;color:#fff;font-size:1.6rem;">
            <i class="bi bi-lightning-charge-fill"></i>
        </div>
        <h4 class="fw-semibold mb-0">Customer Portal</h4>
        <div class="text-muted small">Log in with your mobile number</div>
    </div>

    <div class="card rounded-4">
        <div class="card-body p-4">
            @if (session('status'))
                <div class="alert alert-success">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('portal.login') }}">
                @csrf
                <label class="form-label">Mobile Number</label>
                <div class="input-group mb-1">
                    <span class="input-group-text"><i class="bi bi-phone"></i></span>
                    <input type="text" name="mobile_number" value="{{ old('mobile_number') }}"
                           class="form-control @error('mobile_number') is-invalid @enderror"
                           placeholder="Enter your mobile number" autofocus>
                </div>
                @error('mobile_number')
                    <div class="text-danger small mb-2">{{ $message }}</div>
                @enderror

                <button type="submit" class="btn btn-primary text-white w-100 mt-3">
                    <i class="bi bi-box-arrow-in-right me-1"></i>Login
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
