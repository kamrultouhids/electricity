@extends('layouts.app')

@section('title', 'Customer List')

@section('content')
<div class="container">
    @include('partials.electricity_nav')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            Customer Management
            <span class="badge bg-primary px-1 py-0 small">{{ $customers->total() }}</span>
        </h5>
        @can('manage-customers')
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importCsvModal">
                    <i class="bi bi-upload me-1"></i>Import CSV
                </button>
                <a href="{{ route('customers.create') }}" class="btn btn-primary text-white"><i class="bi bi-plus-lg me-1"></i>Add Customer</a>
            </div>
        @endcan
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

    {{-- Search & Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('customers.index') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                           placeholder="Serial No, Name, Mobile or Meter Number">
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
                    <label class="form-label mb-1">Connection Type</label>
                    <select name="connection_type" class="form-select">
                        <option value="">All</option>
                        @foreach ($connectionTypes as $type)
                            <option value="{{ $type }}" @selected(request('connection_type') === $type)>
                                {{ ucfirst($type) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Connection Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="1" @selected(request('status') === '1')>Active</option>
                        <option value="0" @selected(request('status') === '0')>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary text-white "><i class="bi bi-funnel me-1"></i>Filter</button>
                    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
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
                        <th>Photo</th>
                        <th>Sheet</th>
                        <th>Serial No</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Meter No</th>
                        <th>Connection Type</th>
                        <th>Connection Date</th>
                        <th>Connection Status</th>
                        <th>Source</th>
                        <th class="text-end" width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                             <td>{{ $loop->iteration }}</td>
                            <td>
                                @if ($customer->photo)
                                    <img src="{{ asset('storage/' . $customer->photo) }}" alt="photo"
                                         class="rounded" style="width:42px;height:42px;object-fit:cover;">
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $customer->sheet->name ?? '—' }}</td>
                            <td>{{ $customer->serial_no ?? '—' }}</td>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->mobile_number }}</td>
                            <td>{{ $customer->meter_number ?? '' }}</td>
                            <td>{{ $customer->connection_type ? ucfirst($customer->connection_type) : '—' }}</td>
                            <td>{{ $customer->connection_date ? $customer->connection_date->format('d M Y') : '—' }}</td>
                            <td>
                                @if ($customer->isActive())
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                @if ($customer->isImported())
                                    <span class="badge bg-info text-dark"><i class="bi bi-filetype-csv me-1"></i>CSV Import</span>
                                @else
                                    <span class="badge bg-light text-dark border"><i class="bi bi-pencil me-1"></i>Manual</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-info"><i class="bi bi-eye me-1"></i>View</a>
                                    @can('manage-customers')
                                        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-primary"><i class="bi bi-pencil-square me-1"></i>Edit</a>
                                    @endcan
                                    <!-- <a href="{{ route('customers.delete', $customer) }}" class="btn btn-outline-danger"
                                       onclick="return confirm('Delete this customer?');">Delete</a> -->
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted py-4">No customers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-center">
        {{ $customers->links() }}
    </div>
</div>

@can('manage-customers')
{{-- Import CSV modal --}}
<div class="modal fade" id="importCsvModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4">
            <form method="POST" action="{{ route('customers.import') }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title"><i class="bi bi-upload me-1"></i>Import Customers from CSV</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted">
                        Upload a CSV with a header row. Use the template so the columns match.
                        
                        {{ implode(', ', \App\Models\Customer::CONNECTION_TYPES) }}.
                    </p>
                    <a href="{{ route('customers.import.template') }}" class="btn btn-sm btn-outline-secondary mb-3">
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
@endcan
@endsection

@if ($errors->has('file'))
@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        new bootstrap.Modal(document.getElementById('importCsvModal')).show();
    });
</script>
@endpush
@endif
