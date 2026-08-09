@extends('layouts.app')

@section('content')
<div class="container">
    @include('partials.electricity_nav')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            Meter Readings
            <span class="badge bg-primary px-1 py-0 small">{{ $readings->total() }}</span>
        </h5>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importReadingsModal">
                <i class="bi bi-upload me-1"></i>Import CSV
            </button>
            <a href="{{ route('meter-readings.create') }}" class="btn btn-primary text-white"><i class="bi bi-plus-lg me-1"></i>Add Reading</a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (session('import_errors'))
        <div class="alert alert-warning">
            <div class="fw-semibold mb-1">Some rows were skipped:</div>
            <ul class="mb-0 small">
                @foreach (session('import_errors') as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('meter-readings.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                           placeholder="Serial No, Name, Mobile or Meter No">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Sheet</label>
                    <select name="sheet_id" class="form-select">
                        <option value="">All</option>
                        @foreach ($sheets as $sheet)
                            <option value="{{ $sheet->id }}" @selected(request('sheet_id') == $sheet->id)>
                                {{ $sheet->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') !== null && request('status') !== '' && (int) request('status') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Month</label>
                    <input type="month" name="month" value="{{ request('month') }}" class="form-control">
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary text-white "><i class="bi bi-funnel me-1"></i>Filter</button>
                    <a href="{{ route('meter-readings.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
    </div>

    {{-- List --}}
    <div class="card list-card rounded-4">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="list-head">
                    <tr>
                        <th>#</th>
                        <th>Meter Photo</th>
                        <th>Customer</th>
                        <th>Sheet</th>
                        <th>Previous Unit</th>
                        <th>Current Unit</th>
                        <th>Consumed Unit</th>
                        <th>Reading Date</th>
                        <th>Reader Name</th>
                        <th>Status</th>
                        <th>Source</th>
                        <th>Flag</th>
                        <th class="text-end" width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($readings as $reading)
                        <tr class="{{ $reading->is_flagged ? 'table-danger' : '' }}">
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                @if ($reading->photo)
                                    <a href="{{ asset('storage/' . $reading->photo) }}" target="_blank" rel="noopener" title="View full photo">
                                        <img src="{{ asset('storage/' . $reading->photo) }}" alt="photo"
                                             class="rounded" style="width:42px;height:42px;object-fit:cover;cursor:pointer;">
                                    </a>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @include('partials.customer_cell', ['customer' => $reading->customer])
                            </td>
                            <td>{{ $reading->customer->sheet->name ?? '—' }}</td>
                            <td>{{ number_format($reading->previous_reading, 2) }}</td>
                            <td>{{ number_format($reading->current_reading, 2) }}</td>
                            <td>{{ number_format($reading->consumed_units, 2) }}</td>
                            <td>{{ $reading->reading_date->format('d M Y') }}</td>
                            <td>
                                <div>{{ $reading->createdBy->name ?? '—' }}</div>
                                <small class="text-muted d-block">{{ $reading->created_at->format('d M Y, h:i A') }}</small>
                            </td>
                            <td>
                                @if ($reading->isCompleted())
                                    <span class="badge bg-success">Completed</span>
                                @else
                                    <span class="badge bg-warning text-dark">Pending</span>
                                @endif
                            </td>
                            <td>
                                @if ($reading->isImported())
                                    <span class="badge bg-info text-dark"><i class="bi bi-filetype-csv me-1"></i>CSV Import</span>
                                @else
                                    <span class="badge bg-light text-dark border"><i class="bi bi-pencil me-1"></i>Manual</span>
                                @endif
                            </td>
                            <td>
                                @if ($reading->is_flagged)
                                    <span class="badge bg-danger" title="This reading's previous ({{ number_format($reading->previous_reading, 2) }})@if (! is_null($reading->flag_expected)) does not match the last reading's current ({{ number_format($reading->flag_expected, 2) }}) on {{ optional($reading->flag_prev_date)->format('d M Y') }}@else is greater than current ({{ number_format($reading->current_reading, 2) }})@endif">
                                        <i class="bi bi-exclamation-triangle-fill me-1"></i>Discrepancy
                                    </span>
                                    @if (! is_null($reading->flag_expected))
                                        <div class="small text-danger mt-1">
                                            Previous: {{ number_format($reading->previous_reading, 2) }}<br>
                                            Expected: {{ number_format($reading->flag_expected, 2) }}
                                        </div>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('meter-readings.show', $reading) }}" class="btn btn-outline-info"><i class="bi bi-eye me-1"></i>View</a>
                                    @if ($reading->isPending())
                                        <a href="{{ route('meter-readings.edit', $reading) }}" class="btn btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Edit</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="13" class="text-center text-muted py-4">No meter readings found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-center">
        {{ $readings->links() }}
    </div>
</div>

{{-- Import CSV modal --}}
<div class="modal fade" id="importReadingsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <form method="POST" action="{{ route('meter-readings.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-upload me-1"></i>Import Meter Readings from CSV</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-2">
                        Upload a CSV with a header row: <code>meter_number</code>, <code>previous_units</code>,
                        <code>current_units</code>, <code>reading_date</code> (use <code>YYYY-MM-DD</code>).
                        The customer is matched by meter number.
                        Rows are skipped when the meter is unknown, the current units are below the previous, or the
                        customer already has a reading for that month.
                    </p>
                    <a href="{{ route('meter-readings.import.template') }}" class="btn btn-sm btn-outline-secondary mb-3">
                        <i class="bi bi-download me-1"></i>Download template
                    </a>
                    <div>
                        <label class="form-label">CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="file" accept=".csv,text/csv" class="form-control @error('file') is-invalid @enderror" required>
                        @error('file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary text-white"><i class="bi bi-upload me-1"></i>Import</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@if ($errors->has('file'))
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        new bootstrap.Modal(document.getElementById('importReadingsModal')).show();
    });
</script>
@endpush
@endif

