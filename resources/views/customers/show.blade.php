@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">Customer Details</h4>
        <div>
            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-primary text-white">Edit</a>
            <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Back to Customer List</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 text-center mb-3">
                    @if ($customer->photo)
                        <img src="{{ asset('storage/' . $customer->photo) }}" alt="photo"
                             class="rounded img-fluid" style="max-width:180px;object-fit:cover;">
                    @else
                        <div class="border rounded d-flex align-items-center justify-content-center text-muted"
                             style="width:180px;height:180px;margin:auto;">No Photo</div>
                    @endif
                    <div class="mt-2">
                        @if ($customer->isActive())
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </div>
                </div>

                <div class="col-md-9">
                    <table class="table table-borderless mb-0">
                        <tbody>
                            @php
                                $rows = [
                                    'Serial No'                 => $customer->serial_no,
                                    'Name'                      => $customer->name,
                                    'Father / Husband Name'     => $customer->father_or_husband_name,
                                    'Mother Name'               => $customer->mother_name,
                                    'Mobile Number'             => $customer->mobile_number,
                                    'National / Voter ID'       => $customer->national_id,
                                    'Age'                       => $customer->age,
                                    'Address'                   => $customer->address,
                                    'Occupation'                => $customer->occupation,
                                    'Religion'                  => $customer->religion,
                                    'Educational Qualification' => $customer->educational_qualification,
                                    'Guardian Name'             => $customer->guardian_name,
                                    'Guardian Relationship'     => $customer->guardian_relationship,
                                    'Guardian Address'          => $customer->guardian_address,
                                    'Meter Number'              => $customer->meter_number,
                                    'Connection Type'           => $customer->connection_type ? ucfirst($customer->connection_type) : null,
                                ];
                            @endphp
                            @foreach ($rows as $label => $value)
                                <tr>
                                    <th style="width:35%">{{ $label }}</th>
                                    <td>{{ $value ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
