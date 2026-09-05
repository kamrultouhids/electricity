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
        <label class="form-label">Mobile Number</label>
        <input type="text" name="mobile_number" class="form-control" placeholder="Enter Mobile Number"
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
        {{-- Quick picks for the সমাজ the address belongs to. Clicking one fills it
             into the box; the rest of the address is still typed by hand. --}}
        <div class="d-flex flex-wrap gap-1 mt-2" id="somajTags">
            @foreach (['১ নং সমাজ', '২ নং সমাজ', '৩ নং সমাজ', '৪ নং সমাজ', '৫ নং সমাজ', '৬ নং সমাজ', '৭ নং সমাজ', '৮ নং সমাজ', '৯ নং সমাজ', '১০ নং সমাজ'] as $somaj)
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill py-0 px-2"
                        data-tag="{{ $somaj }}">{{ $somaj }}</button>
            @endforeach
        </div>
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
        <label class="form-label">Meter Number</label>
        <input type="text" name="meter_number" class="form-control" placeholder="Enter Meter Number"
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

{{-- Opening balance — only for a customer carried over from the old system. --}}
@php
    $openingBlocked = $openingBlocked ?? null;
    $hasOpening = old('opening_as_of', ($customer && $customer->opening_as_of) ? $customer->opening_as_of->format('Y-m-d') : '') !== '';
@endphp

<hr class="my-4">

<div class="form-check mb-3">
    <input type="checkbox" class="form-check-input" id="hasOpeningBalance"
           @checked($hasOpening) @disabled($openingBlocked)>
    <label class="form-check-label fw-semibold" for="hasOpeningBalance">
        Existing customer — carry over their meter reading and outstanding due
    </label>
    <div class="form-text">
        Use this for a customer who was already being billed before this system. Leave it
        unticked for a brand new connection.
    </div>
</div>

@if ($openingBlocked)
    <div class="alert alert-warning py-2">
        <strong>Opening balance locked.</strong> {{ $openingBlocked }}
        These figures can no longer be edited here.
    </div>
@endif

<div class="row g-3 {{ $hasOpening ? '' : 'd-none' }}" id="openingBalanceFields">
    <div class="col-md-4">
        <label class="form-label">Last Meter Reading <span class="text-danger">*</span></label>
        <input type="number" step="0.01" min="0" name="opening_reading" class="form-control"
               placeholder="Reading on the meter at handover"
               @disabled($openingBlocked)
               value="{{ old('opening_reading', $customer->opening_reading ?? '') }}">
        <div class="form-text">The first bill charges the units above this — not the whole meter.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label">Outstanding Due <span class="text-danger">*</span></label>
        <input type="number" step="0.01" min="0" name="opening_due" class="form-control"
               placeholder="0.00"
               @disabled($openingBlocked)
               value="{{ old('opening_due', $customer->opening_due ?? '') }}">
        <div class="form-text">Enter 0 if they are paid up. No late fee is charged on this amount.</div>
    </div>
    <div class="col-md-4">
        <label class="form-label">As Of <span class="text-danger">*</span></label>
        <input type="date" name="opening_as_of" class="form-control"
               @disabled($openingBlocked)
               value="{{ old('opening_as_of', ($customer && $customer->opening_as_of) ? $customer->opening_as_of->format('Y-m-d') : '') }}">
        <div class="form-text">The last month the old system billed. Billing here starts after it.</div>
    </div>
</div>

<div class="mt-4 text-end">
    <button type="submit" class="btn btn-primary text-white">{{ $customer ? 'Update' : 'Save' }}</button>
    <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>

<script>
    // Ticking the box reveals the opening fields; unticking clears them, so an
    // unticked box always submits blanks and the balance is genuinely removed.
    (function () {
        const toggle = document.getElementById('hasOpeningBalance');
        const fields = document.getElementById('openingBalanceFields');
        if (!toggle || !fields) return;

        toggle.addEventListener('change', function () {
            fields.classList.toggle('d-none', !toggle.checked);
            if (!toggle.checked) {
                fields.querySelectorAll('input').forEach(input => input.value = '');
            }
        });
    })();

    // Address সমাজ chips: a click drops the chosen সমাজ into the address box.
    // If one is already there it is swapped, so picking twice never stacks up,
    // and anything else the user typed is left untouched.
    (function () {
        const address = document.querySelector('textarea[name="address"]');
        const tags = document.getElementById('somajTags');
        if (!address || !tags) return;

        const pattern = /[\u09E6-\u09EF]+\s*নং\s*সমাজ/;

        function markActive() {
            const current = address.value.match(pattern);
            tags.querySelectorAll('button[data-tag]').forEach(btn => {
                const on = current && btn.dataset.tag === current[0];
                btn.classList.toggle('btn-primary', !!on);
                btn.classList.toggle('text-white', !!on);
                btn.classList.toggle('btn-outline-secondary', !on);
            });
        }

        tags.addEventListener('click', function (event) {
            const btn = event.target.closest('button[data-tag]');
            if (!btn) return;

            const tag = btn.dataset.tag;
            const value = address.value.trim();
            address.value = pattern.test(value)
                ? value.replace(pattern, tag)
                : (value ? tag + ', ' + value : tag);

            markActive();
            address.focus();
            address.setSelectionRange(address.value.length, address.value.length);
        });

        address.addEventListener('input', markActive);
        markActive();
    })();

    function previewPhoto(event) {
        const input = event.target;
        const preview = document.getElementById('photoPreview');
        if (input.files && input.files[0]) {
            preview.src = URL.createObjectURL(input.files[0]);
            preview.classList.remove('d-none');
        }
    }
</script>
