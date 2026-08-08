@csrf

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@php $customer = $customer ?? null; @endphp

<div class="row g-3">
    {{-- Photo --}}
    <div class="col-md-4 ">
        <label class="form-label ">Photo</label>
        <input type="file" name="photo" accept="image/*" class="form-control" id="photoInput"
               onchange="previewPhoto(event)">
        <img id="photoPreview"
             src="{{ ($customer && $customer->photo) ? asset('storage/' . $customer->photo) : '' }}"
             alt="preview"
             class="rounded mt-2 {{ ($customer && $customer->photo) ? '' : 'd-none' }}"
             style="width:120px;height:120px;object-fit:cover;">
    </div>

    <div class="col-md-4">
        <label class="form-label">Sheet <span class="text-danger">*</span></label>
        <select name="sheet_id" class="form-select" required>
            <option value="">Select Sheet</option>
            @foreach ($sheets as $sheet)
                <option value="{{ $sheet->id }}" @selected(old('sheet_id', $customer->sheet_id ?? '') == $sheet->id)>
                    {{ $sheet->name }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Serial No <span class="text-danger">*</span></label>
        <input type="text" name="serial_no" class="form-control" required placeholder="Enter Serial No"
               value="{{ old('serial_no', $customer->serial_no ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" required placeholder="Enter Name"
               value="{{ old('name', $customer->name ?? '') }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">Father / Husband Name</label>
        <input type="text" name="father_or_husband_name" class="form-control" placeholder="Enter Father / Husband Name"
               value="{{ old('father_or_husband_name', $customer->father_or_husband_name ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Mother Name</label>
        <input type="text" name="mother_name" class="form-control" placeholder="Enter Mother Name"
               value="{{ old('mother_name', $customer->mother_name ?? '') }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">Mobile Number <span class="text-danger">*</span></label>
        <input type="text" name="mobile_number" class="form-control" required placeholder="Enter Mobile Number"
               value="{{ old('mobile_number', $customer->mobile_number ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">National / Voter ID</label>
        <input type="text" name="national_id" class="form-control" placeholder="Enter National / Voter ID"
               value="{{ old('national_id', $customer->national_id ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Age</label>
        <input type="number" name="age" min="0" max="150" class="form-control" placeholder="Enter Age"
               value="{{ old('age', $customer->age ?? '') }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">Address <span class="text-danger">*</span></label>
        <textarea name="address" rows="2" class="form-control" required placeholder="Enter Address">{{ old('address', $customer->address ?? '') }}</textarea>
    </div>

    <div class="col-md-4">
        <label class="form-label">Occupation</label>
        <input type="text" name="occupation" class="form-control" placeholder="Enter Occupation"
               value="{{ old('occupation', $customer->occupation ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Religion</label>
        <input type="text" name="religion" class="form-control" placeholder="Enter Religion"
               value="{{ old('religion', $customer->religion ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Educational Qualification</label>
        <input type="text" name="educational_qualification" class="form-control" placeholder="Enter Educational Qualification"
               value="{{ old('educational_qualification', $customer->educational_qualification ?? '') }}">
    </div>

    {{-- Guardian --}}
    <div class="col-md-4">
        <label class="form-label">Guardian Name</label>
        <input type="text" name="guardian_name" class="form-control" placeholder="Enter Guardian Name"
               value="{{ old('guardian_name', $customer->guardian_name ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Guardian Relationship</label>
        <input type="text" name="guardian_relationship" class="form-control" placeholder="Enter Guardian Relationship"
               value="{{ old('guardian_relationship', $customer->guardian_relationship ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Guardian Address</label>
        <input type="text" name="guardian_address" class="form-control" placeholder="Enter Guardian Address"
               value="{{ old('guardian_address', $customer->guardian_address ?? '') }}">
    </div>

    {{-- Connection --}}
    <div class="col-md-4">
        <label class="form-label">Meter Number <span class="text-danger">*</span></label>
        <input type="text" name="meter_number" class="form-control" required placeholder="Enter Meter Number"
               value="{{ old('meter_number', $customer->meter_number ?? '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Connection Type <span class="text-danger">*</span></label>
        <select name="connection_type" class="form-select" required>
            <option value="">Select</option>
            @foreach ($connectionTypes as $type)
                <option value="{{ $type }}" @selected(old('connection_type', $customer->connection_type ?? '') === $type)>
                    {{ ucfirst($type) }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Connection Date <span class="text-danger">*</span></label>
        <input type="date" name="connection_date" class="form-control" required
               value="{{ old('connection_date', isset($customer) && $customer->connection_date ? $customer->connection_date->format('Y-m-d') : '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Connection Status <span class="text-danger">*</span></label>
        <select name="status" class="form-select" required>
            <option value="1" @selected(old('status', $customer->status ?? 1) == 1)>Active</option>
            <option value="0" @selected(old('status', $customer->status ?? 1) == 0)>Inactive</option>
        </select>
    </div>
</div>

<div class="mt-4 text-end">
    <button type="submit" class="btn btn-primary text-white">{{ $customer ? 'Update' : 'Save' }}</button>
    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>

<script>
    function previewPhoto(event) {
        const input = event.target;
        const preview = document.getElementById('photoPreview');
        if (input.files && input.files[0]) {
            preview.src = URL.createObjectURL(input.files[0]);
            preview.classList.remove('d-none');
        }
    }
</script>
