@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Customers</h4>
        <a href="{{ route('customers.create') }}" class="btn btn-primary text-white">+ Add Customer</a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    {{-- Search & Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('customers.index') }}" class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                           placeholder="Serial No, Name or Meter Number">
                </div>
                <div class="col-md-3">
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
                    <button type="submit" class="btn btn-primary text-white w-100">Filter</button>
                    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- List --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Photo</th>
                        <th>Serial No</th>
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Meter No</th>
                        <th>Connection Type</th>
                        <th>Connection Status</th>
                        <th class="text-end" width="150">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($customers as $customer)
                        <tr>
                            <td>
                                @if ($customer->photo)
                                    <img src="{{ asset('storage/' . $customer->photo) }}" alt="photo"
                                         class="rounded" style="width:42px;height:42px;object-fit:cover;">
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ $customer->serial_no ?? '—' }}</td>
                            <td>{{ $customer->name }}</td>
                            <td>{{ $customer->mobile_number }}</td>
                            <td>{{ $customer->meter_number ?? '—' }}</td>
                            <td>{{ $customer->connection_type ? ucfirst($customer->connection_type) : '—' }}</td>
                            <td>
                                @if ($customer->isActive())
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('customers.show', $customer) }}" class="btn btn-outline-info">View</a>
                                    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-primary">Edit</a>
                                    <a href="{{ route('customers.delete', $customer) }}" class="btn btn-outline-danger"
                                       onclick="return confirm('Delete this customer?');">Delete</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No customers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $customers->links() }}
    </div>
</div>
@endsection
