@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">Per Unit Rate Settings</h5>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card list-card rounded-4">
        <div class="card-body">
            <form method="POST" action="{{ route('tariffs.update') }}">
                @csrf
                @method('PUT')

                <div class="table-responsive ">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="list-head">
                            <tr>
                                <th>#</th>
                                <th>Connection Type</th>
                                <th width="260">Per Unit Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tariffs as $tariff)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ ucfirst($tariff->connection_type) }}</td>
                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-text">৳</span>
                                            <input type="number" step="0.01" min="0"
                                                   name="rates[{{ $tariff->connection_type }}]"
                                                   class="form-control"
                                                   value="{{ old('rates.'.$tariff->connection_type, $tariff->per_unit_rate) }}"
                                                   required>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4 text-end">
                    <button type="submit" class="btn btn-primary text-white">Save</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Rate change history --}}
    <div class="card mt-4 list-card rounded-4">
        <div class="card-header bg-white">
            <h6 class="mb-0">Rate Change History</h6>
        </div>
        <div class="card-body pb-0">
            <form method="GET" action="{{ route('tariffs.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3 mb-2">
                    <label class="form-label mb-2">Connection Type</label>
                    <select name="connection_type" class="form-select">
                        <option value="">All</option>
                        @foreach ($connectionTypes as $type)
                            <option value="{{ $type }}" @selected(request('connection_type') === $type)>
                                {{ ucfirst($type) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label class="form-label mb-1">From Date</label>
                    <input type="date" name="from_date" value="{{ request('from_date') }}" class="form-control">
                </div>
                <div class="col-md-3 mb-2">
                    <label class="form-label mb-1">To Date</label>
                    <input type="date" name="to_date" value="{{ request('to_date') }}" class="form-control">
                </div>
                <div class="col-md-3 d-flex gap-2 mb-2">
                    <button type="submit" class="btn btn-primary text-white "><i class="bi bi-funnel me-1"></i>Filter</button>
                    <a href="{{ route('tariffs.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
                </div>
            </form>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="list-head">
                    <tr>
                        <th>#</th>
                        <th>Connection Type</th>
                        <th>Old Rate</th>
                        <th>New Rate</th>
                        <th>Changed By</th>
                        <th>Changed At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ ucfirst($log->connection_type) }}</td>
                            <td>৳ {{ number_format($log->old_rate, 2) }}</td>
                            <td>৳ {{ number_format($log->new_rate, 2) }}</td>
                            <td>{{ $log->changedBy->name ?? '—' }}</td>
                            <td>{{ $log->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No rate changes found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 d-flex justify-content-center">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
