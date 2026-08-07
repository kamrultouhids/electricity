@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Edit Meter Reading</h4>
        <a href="{{ route('meter-readings.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Readings</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('meter-readings.update', $meterReading) }}" enctype="multipart/form-data">
                @method('PUT')
                @include('meter_readings._form')
            </form>
        </div>
    </div>
</div>
@endsection
