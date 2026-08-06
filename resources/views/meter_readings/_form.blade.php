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

@php $meterReading = $meterReading ?? null; @endphp

@php
    $selectedCustomer = null;
    $selectedId = old('customer_id', $meterReading->customer_id ?? null);
    if (! $meterReading && $selectedId) {
        $selectedCustomer = \App\Models\Customer::find($selectedId);
    }
@endphp

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Customer <span class="text-danger">*</span></label>
        @if ($meterReading)
            {{-- Locked on edit: the previous/current chain must stay tied to one customer --}}
            <input type="text" class="form-control"
                   value="{{ $meterReading->customer->name }} ({{ $meterReading->customer->meter_number }})" readonly>
            <input type="hidden" name="customer_id" value="{{ $meterReading->customer_id }}">
        @else
            <select name="customer_id" id="customerSelect" class="form-select" required>
                <option value="">Search customer…</option>
                @if ($selectedCustomer)
                    <option value="{{ $selectedCustomer->id }}"
                            data-last="{{ $selectedCustomer->latestMeterReading->current_reading ?? 0 }}" selected>
                        {{ $selectedCustomer->name }} ({{ $selectedCustomer->meter_number }})
                    </option>
                @endif
            </select>
        @endif
    </div>

    <div class="col-md-4">
        <label class="form-label">Previous Units <span class="text-danger">*</span></label>
        <input type="number" step="0.01" min="0" name="previous_reading" id="previousReading"
               class="form-control" required
               value="{{ old('previous_reading', $meterReading->previous_reading ?? 0) }}">
        <small class="text-muted">Auto-filled from the last units — editable.</small>
    </div>

    <div class="col-md-4">
        <label class="form-label">Current Units <span class="text-danger">*</span></label>
        <input type="number" step="0.01" min="0" name="current_reading" id="currentReading"
               class="form-control" required placeholder="Enter Current Units"
               value="{{ old('current_reading', $meterReading->current_reading ?? '') }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">Consumed Units</label>
        <input type="number" id="consumedUnits" class="form-control" readonly
               value="{{ $meterReading->consumed_units ?? 0 }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">Reading Date <span class="text-danger">*</span></label>
        <input type="date" name="reading_date" class="form-control" required
               value="{{ old('reading_date', isset($meterReading) ? $meterReading->reading_date->format('Y-m-d') : now()->format('Y-m-d')) }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">Meter Photo</label>
        <input type="file" name="photo" accept="image/*" class="form-control" id="photoInput"
               onchange="previewPhoto(event)">
        <img id="photoPreview"
             src="{{ ($meterReading && $meterReading->photo) ? asset('storage/' . $meterReading->photo) : '' }}"
             alt="preview"
             class="rounded mt-2 {{ ($meterReading && $meterReading->photo) ? '' : 'd-none' }}"
             style="width:120px;height:120px;object-fit:cover;">
    </div>
</div>

<div class="mt-4 text-end">
    <button type="submit" class="btn btn-primary text-white">{{ $meterReading ? 'Update' : 'Save' }}</button>
    <a href="{{ route('meter-readings.index') }}" class="btn btn-outline-secondary">Cancel</a>
</div>

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css" rel="stylesheet">
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        const previousReading = document.getElementById('previousReading');
        const currentReading = document.getElementById('currentReading');
        const consumedUnits = document.getElementById('consumedUnits');

        function calcConsumed() {
            const prev = parseFloat(previousReading.value) || 0;
            const curr = parseFloat(currentReading.value) || 0;
            consumedUnits.value = (curr - prev).toFixed(2);
        }

        if (currentReading) currentReading.addEventListener('input', calcConsumed);
        if (previousReading) previousReading.addEventListener('input', calcConsumed);

        // Cache of customer_id => last current_reading, for the previous-reading pull.
        const lastReadingCache = {};
        @if (isset($selectedCustomer) && $selectedCustomer)
            lastReadingCache[{{ $selectedCustomer->id }}] = {{ $selectedCustomer->latestMeterReading->current_reading ?? 0 }};
        @endif

        const customerEl = document.getElementById('customerSelect');
        if (customerEl) {
            const ts = new TomSelect(customerEl, {
                valueField: 'id',
                labelField: 'text',
                searchField: 'text',
                placeholder: 'Search by name, mobile or meter no…',
                // Results are already filtered server-side (incl. mobile/meter which
                // aren't in the label) — keep every loaded option, don't re-filter.
                score: function () { return function () { return 1; }; },
                load: function (query, callback) {
                    fetch('{{ route('customers.search') }}?q=' + encodeURIComponent(query))
                        .then(r => r.json())
                        .then(json => {
                            // Cache last reading per customer for the previous-reading pull.
                            json.forEach(c => { lastReadingCache[c.id] = c.last; });
                            callback(json);
                        })
                        .catch(() => callback());
                },
                onChange: function (value) {
                    previousReading.value = lastReadingCache[value] ?? 0;
                    calcConsumed();
                },
            });
        }

        function previewPhoto(event) {
            const input = event.target;
            const preview = document.getElementById('photoPreview');
            if (input.files && input.files[0]) {
                preview.src = URL.createObjectURL(input.files[0]);
                preview.classList.remove('d-none');
            }
        }
    </script>
@endpush
