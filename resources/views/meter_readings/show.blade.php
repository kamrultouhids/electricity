@extends('layouts.app')

@section('title', 'Meter Reading Details')

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

    {{-- Previous reading history --}}
    <div class="card mt-3 list-card rounded-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Previous Reading History</h6>
            <span class="badge bg-primary">{{ $history->count() }}</span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="list-head">
                    <tr>
                        <th>#</th>
                        <th>Reading Date</th>
                        <th class="text-end">Previous Reading</th>
                        <th class="text-end">Current Reading</th>
                        <th class="text-end">Consumed Units</th>
                        <th>Reader Name</th>
                        <th>Status</th>
                        <th>Flag</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- History is newest-first; each row's previous should equal
                         the older reading's current. The flag lands on the newer row. --}}
                    @forelse ($history as $row)
                        @php
                            $older      = $history->get($loop->index + 1);   // next item = older reading
                            $selfIssue  = (float) $row->current_reading < (float) $row->previous_reading;
                            $chainBreak = $older && (float) $older->current_reading !== (float) $row->previous_reading;
                            $flagged    = $selfIssue || $chainBreak;
                        @endphp
                        <tr class="{{ $flagged ? 'table-danger' : '' }}">
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row->reading_date->format('d M Y') }}</td>
                            <td class="text-end {{ $chainBreak ? 'text-danger fw-bold' : '' }}">{{ number_format($row->previous_reading, 2) }}</td>
                            <td class="text-end {{ $selfIssue ? 'text-danger fw-bold' : '' }}">{{ number_format($row->current_reading, 2) }}</td>
                            <td class="text-end">{{ number_format($row->consumed_units, 2) }}</td>
                            <td>{{ $row->createdBy->name ?? '—' }}</td>
                            <td>
                                @if ($row->isCompleted())
                                    <span class="badge bg-success">Completed</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if ($chainBreak)
                                    <span class="badge bg-danger" title="This reading's previous ({{ number_format($row->previous_reading, 2) }}) does not match the last reading's current ({{ number_format($older->current_reading, 2) }}) on {{ $older->reading_date->format('d M Y') }}">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Mismatch
                                    </span>
                                @elseif ($selfIssue)
                                    <span class="badge bg-danger" title="Current reading ({{ number_format($row->current_reading, 2) }}) is lower than previous reading ({{ number_format($row->previous_reading, 2) }})">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Discrepancy
                                    </span>
                                @else
                                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle me-1"></i>OK</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No previous readings.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
