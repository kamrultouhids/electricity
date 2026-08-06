@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Add Meter Reading</h4>
        <a href="{{ route('meter-readings.index') }}" class="btn btn-outline-secondary">Back to Readings</a>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('meter-readings.store') }}" enctype="multipart/form-data">
                @include('meter_readings._form')
            </form>
        </div>
    </div>
</div>
@endsection
