@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Meter Reading Details</h4>
        <div class="d-flex gap-2">
            @if ($meterReading->isPending())
                <a href="{{ route('meter-readings.edit', $meterReading) }}" class="btn btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Edit</a>
            @endif
            <a href="{{ route('meter-readings.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Readings</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="text-muted small">Customer</div>
                    <div>{{ $meterReading->customer->name ?? '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Serial No</div>
                    <div>{{ $meterReading->customer->serial_no ?? '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Meter No</div>
                    <div>{{ $meterReading->customer->meter_number ?? '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Mobile</div>
                    <div>{{ $meterReading->customer->mobile_number ?? '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Sheet</div>
                    <div>{{ $meterReading->customer->sheet->name ?? '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Reading Date</div>
                    <div>{{ $meterReading->reading_date->format('d M Y') }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Previous Reading</div>
                    <div>{{ number_format($meterReading->previous_reading, 2) }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Current Reading</div>
                    <div>{{ number_format($meterReading->current_reading, 2) }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Consumed Units</div>
                    <div><strong>{{ number_format($meterReading->consumed_units, 2) }}</strong></div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Reader Name (Created By)</div>
                    <div>{{ $meterReading->createdBy->name ?? '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Updated By</div>
                    <div>{{ $meterReading->updatedBy->name ?? '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="text-muted small">Status</div>
                    <div>
                        @if ($meterReading->isCompleted())
                            <span class="badge bg-success">Completed</span>
                        @else
                            <span class="badge bg-warning text-dark">Pending</span>
                        @endif
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="text-muted small">Meter Photo</div>
                    @if ($meterReading->photo)
                        <img src="{{ asset('storage/' . $meterReading->photo) }}" alt="meter photo"
                             class="rounded mt-1" style="max-width:220px;object-fit:cover;">
                    @else
                        <div>—</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
