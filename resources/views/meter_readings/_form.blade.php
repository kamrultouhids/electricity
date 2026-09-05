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
                   value="{{ $meterReading->customer->serial_no ? $meterReading->customer->name.' ('.$meterReading->customer->serial_no.')' : $meterReading->customer->name }}" readonly>
            <input type="hidden" name="customer_id" value="{{ $meterReading->customer_id }}">
        @else
            <select name="customer_id" id="customerSelect" class="form-select" required>
                <option value="">Search customer…</option>
                @if ($selectedCustomer)
                    <option value="{{ $selectedCustomer->id }}"
                            data-last="{{ $selectedCustomer->latestMeterReading->current_reading ?? 0 }}" selected>
                        {{ $selectedCustomer->serial_no ? $selectedCustomer->name.' ('.$selectedCustomer->serial_no.')' : $selectedCustomer->name }}
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
        {{-- Camera shoots in place through the webcam on a desktop, and hands off
             to the phone's camera app on a touch device. The picker keeps gallery
             shots and plain file uploads working everywhere. --}}
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-secondary flex-fill" id="cameraBtn">
                <i class="bi bi-camera me-1"></i>Camera
            </button>
            <label class="btn btn-outline-secondary flex-fill mb-0">
                <i class="bi bi-image me-1"></i>Choose File
                <input type="file" name="photo" accept="image/*"
                       class="d-none" id="photoInput" onchange="previewPhoto(event)">
            </label>
        </div>
        {{-- Carries the captured shot, or the phone camera's own file. --}}
        <input type="file" name="photo_camera" accept="image/*" capture="environment"
               class="d-none" id="photoCameraInput" onchange="previewPhoto(event)">
        <div id="photoName" class="small text-muted mt-1"></div>
        <img id="photoPreview"
             src="{{ ($meterReading && $meterReading->photo) ? asset('storage/' . $meterReading->photo) : '' }}"
             alt="preview"
             class="rounded mt-2 {{ ($meterReading && $meterReading->photo) ? '' : 'd-none' }}"
             style="width:120px;height:120px;object-fit:cover;">
    </div>
</div>

{{-- Webcam capture --}}
<div class="modal fade" id="cameraModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-camera me-1"></i>Take Meter Photo</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div id="cameraError" class="alert alert-warning d-none mb-3"></div>
                <video id="cameraVideo" autoplay playsinline muted
                       class="rounded bg-dark w-100" style="max-height:60vh;object-fit:contain;"></video>
                <canvas id="cameraCanvas" class="d-none"></canvas>
                <img id="cameraShot" alt="captured" class="rounded d-none w-100" style="max-height:60vh;object-fit:contain;">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-outline-secondary d-none" id="cameraRetake">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Retake
                </button>
                <button type="button" class="btn btn-primary text-white" id="cameraCapture">
                    <i class="bi bi-camera-fill me-1"></i>Capture
                </button>
                <button type="button" class="btn btn-success text-white d-none" id="cameraUse">
                    <i class="bi bi-check-lg me-1"></i>Use Photo
                </button>
            </div>
        </div>
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
                placeholder: 'Search by name or serial no',
                // The label carries both searchable fields ("Name (serial no)"),
                // so Tom Select's own filtering hides options left over from
                // earlier queries instead of listing them alongside the matches.
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

            if (! input.files || ! input.files[0]) return;

            // Only one of the two inputs may carry a file, or the server would
            // keep using whichever it prefers instead of the latest pick.
            const other = input.id === 'photoCameraInput'
                ? document.getElementById('photoInput')
                : document.getElementById('photoCameraInput');
            if (other) other.value = '';

            const file = input.files[0];
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('d-none');

            const name = document.getElementById('photoName');
            if (name) {
                name.textContent = file.name + ' (' + (file.size / 1048576).toFixed(1) + ' MB)';
            }
        }

        // Camera button: shoot in place via the webcam where that is possible,
        // otherwise hand off to the device's own camera app.
        (function () {
            const btn = document.getElementById('cameraBtn');
            const cameraInput = document.getElementById('photoCameraInput');
            if (! btn) return;

            // A file input can attach a captured blob only through DataTransfer.
            const canAttach = typeof DataTransfer !== 'undefined' && 'items' in new DataTransfer();
            // getUserMedia needs a secure context (https or localhost).
            const hasWebcam = !! (navigator.mediaDevices && navigator.mediaDevices.getUserMedia);
            // On a phone the native camera app beats an in-page preview.
            const isTouch = window.matchMedia('(pointer: coarse)').matches;

            if (! hasWebcam || ! canAttach || isTouch) {
                btn.addEventListener('click', () => cameraInput.click());
                return;
            }

            const modalEl = document.getElementById('cameraModal');
            const modal = new bootstrap.Modal(modalEl);
            const video = document.getElementById('cameraVideo');
            const canvas = document.getElementById('cameraCanvas');
            const shot = document.getElementById('cameraShot');
            const error = document.getElementById('cameraError');
            const captureBtn = document.getElementById('cameraCapture');
            const retakeBtn = document.getElementById('cameraRetake');
            const useBtn = document.getElementById('cameraUse');
            let stream = null;

            function showLive() {
                video.classList.remove('d-none');
                shot.classList.add('d-none');
                captureBtn.classList.remove('d-none');
                retakeBtn.classList.add('d-none');
                useBtn.classList.add('d-none');
            }

            function stopStream() {
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                    stream = null;
                }
            }

            btn.addEventListener('click', async function () {
                error.classList.add('d-none');
                showLive();
                modal.show();

                try {
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: { facingMode: 'environment', width: { ideal: 1600 } },
                        audio: false,
                    });
                    video.srcObject = stream;
                } catch (e) {
                    stopStream();
                    error.textContent = 'Could not open the camera (' + (e.name || 'error') + '). Use Choose File instead.';
                    error.classList.remove('d-none');
                    captureBtn.classList.add('d-none');
                }
            });

            captureBtn.addEventListener('click', function () {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

                shot.src = canvas.toDataURL('image/jpeg', 0.9);
                video.classList.add('d-none');
                shot.classList.remove('d-none');
                captureBtn.classList.add('d-none');
                retakeBtn.classList.remove('d-none');
                useBtn.classList.remove('d-none');
            });

            retakeBtn.addEventListener('click', showLive);

            useBtn.addEventListener('click', function () {
                canvas.toBlob(function (blob) {
                    const stamp = new Date().toISOString().slice(0, 19).replace(/[:T]/g, '-');
                    const file = new File([blob], 'meter-' + stamp + '.jpg', { type: 'image/jpeg' });

                    const transfer = new DataTransfer();
                    transfer.items.add(file);
                    cameraInput.files = transfer.files;
                    cameraInput.dispatchEvent(new Event('change'));

                    modal.hide();
                }, 'image/jpeg', 0.9);
            });

            // Never leave the camera light on.
            modalEl.addEventListener('hidden.bs.modal', stopStream);
        })();
    </script>
@endpush
