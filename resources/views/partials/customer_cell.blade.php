@php
    $c = $customer ?? null;
    $showAddress = $showAddress ?? false;
@endphp
<div class="d-flex align-items-center gap-2">
    @if ($c && $c->photo)
        <a href="{{ asset('storage/' . $c->photo) }}" target="_blank" rel="noopener" title="View full photo">
            <img src="{{ asset('storage/' . $c->photo) }}" alt="photo" class="cust-avatar cust-avatar-img">
        </a>
    @else
        <span class="cust-avatar">{{ strtoupper(mb_substr($c->name ?? '?', 0, 1)) }}</span>
    @endif
    <div class="min-w-0">
        <div class="fw-semibold">{{ $c->name ?? '—' }}</div>
        <small class="text-muted d-block">Serial No: {{ $c->serial_no ?? '—' }}</small>
        <small class="text-muted d-block">Meter No: {{ $c->meter_number ?? '—' }}</small>
        <small class="text-muted d-block">Mobile: {{ $c->mobile_number ?? '—' }}</small>
        @if ($showAddress)
            <small class="text-muted d-block">Address: {{ $c->address ?? '—' }}</small>
        @endif
    </div>
</div>

@once
@push('styles')
<style>
    .cust-avatar {
        width: 40px; height: 40px; flex: 0 0 40px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 50%;
        background: color-mix(in srgb, #3585BC 12%, #fff);
        color: #3585BC;
        font-weight: 700; font-size: .95rem;
        overflow: hidden;
    }
    .cust-avatar-img { object-fit: cover; cursor: pointer; }
    .min-w-0 { min-width: 0; }
</style>
@endpush
@endonce
