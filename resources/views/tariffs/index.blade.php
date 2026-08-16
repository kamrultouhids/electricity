@extends('layouts.app')

@section('content')
<div class="container">
    @include('partials.electricity_nav')
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
                                <th width="200">Per Unit Rate</th>
                                <th width="200">Line Charge</th>
                                <th width="200">Service Charge</th>
                                <th width="200">Demand Charge</th>
                                <th width="180">Electricity Duty</th>
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
                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-text">৳</span>
                                            <input type="number" step="0.01" min="0"
                                                   name="line_charges[{{ $tariff->connection_type }}]"
                                                   class="form-control"
                                                   value="{{ old('line_charges.'.$tariff->connection_type, $tariff->line_charge) }}">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-text">৳</span>
                                            <input type="number" step="0.01" min="0"
                                                   name="service_charges[{{ $tariff->connection_type }}]"
                                                   class="form-control"
                                                   value="{{ old('service_charges.'.$tariff->connection_type, $tariff->service_charge) }}">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <span class="input-group-text">৳</span>
                                            <input type="number" step="0.01" min="0"
                                                   name="demand_charges[{{ $tariff->connection_type }}]"
                                                   class="form-control"
                                                   value="{{ old('demand_charges.'.$tariff->connection_type, $tariff->demand_charge) }}">
                                        </div>
                                    </td>
                                    <td>
                                        <div class="input-group">
                                            <input type="number" step="0.01" min="0" max="100"
                                                   name="duties[{{ $tariff->connection_type }}]"
                                                   class="form-control"
                                                   value="{{ old('duties.'.$tariff->connection_type, $tariff->electricity_duty) }}">
                                            <span class="input-group-text">%</span>
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
                        <th>Per Unit Rate</th>
                        <th>Line Charge</th>
                        <th>Service Charge</th>
                        <th>Demand Charge</th>
                        <th>Electricity Duty</th>
                        <th>Changed By</th>
                        <th>Changed At</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        @php
                            // [old, new, isPercent] — the duty is a rate, the rest are amounts.
                            $changes = [
                                [$log->old_rate, $log->new_rate, false],
                                [$log->old_line_charge, $log->new_line_charge, false],
                                [$log->old_service_charge, $log->new_service_charge, false],
                                [$log->old_demand_charge, $log->new_demand_charge, false],
                                [$log->old_electricity_duty, $log->new_electricity_duty, true],
                            ];
                            $money = fn ($v, $percent) => $percent
                                ? number_format($v, 2).'%'
                                : '৳ '.number_format($v, 2);
                        @endphp
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ ucfirst($log->connection_type) }}</td>
                            @foreach ($changes as [$oldValue, $newValue, $isPercent])
                                <td class="text-nowrap">
                                    @if ((float) $oldValue === (float) $newValue)
                                        <span class="text-muted">{{ $money($newValue, $isPercent) }}</span>
                                    @else
                                        <span class="text-muted">{{ $money($oldValue, $isPercent) }}</span>
                                        <i class="bi bi-arrow-right mx-1"></i>
                                        <span class="fw-semibold">{{ $money($newValue, $isPercent) }}</span>
                                    @endif
                                </td>
                            @endforeach
                            <td>{{ $log->changedBy->name ?? '—' }}</td>
                            <td>{{ $log->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">No rate changes found.</td>
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
