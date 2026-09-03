@extends('layouts.app')

@section('title', 'Bills')

@section('content')
<div class="container">
    @include('partials.electricity_nav')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0">
            Bills
            <span class="badge bg-primary px-1 py-0 small">{{ $bills->total() }}</span>
        </h5>
        <div class="d-flex gap-2">
            {{-- Opening balances are skipped by the print run (they have no
                 units or rate to print), so they are left out of the count. --}}
            @php($printableCount = $bills->getCollection()->where('is_opening', false)->count())
            @if ($printableCount)
                {{-- Prints exactly the bills shown below — same filters, same
                     per-page — into a hidden frame, so we stay on this page. --}}
                <button type="button" id="printAllBtn" class="btn btn-outline-secondary"
                        data-url="{{ route('bills.print-all', request()->only('search', 'sheet_id', 'status', 'month', 'per_page', 'page')) }}"
                        title="Print the bills shown on this page">
                    <i class="bi bi-printer me-1"></i>Print
                    <span class="badge bg-light text-dark ms-1">{{ $printableCount }}</span>
                </button>
            @endif
            
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    {{-- Filters --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('bills.index') }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1">Search</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control"
                           placeholder="Serial No, Name, Mobile or Meter No">
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
                    <label class="form-label mb-1">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected(request('status') !== null && request('status') !== '' && (int) request('status') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1">Month</label>
                    <input type="month" name="month" value="{{ request('month') }}" class="form-control">
                </div>
                <div class="col-md-1">
                    <label class="form-label mb-1">Per Page</label>
                    {{-- Applies straight away; page resets so the range stays valid. --}}
                    <select name="per_page" class="form-select" onchange="this.form.page.value = 1; this.form.submit();">
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <input type="hidden" name="page" value="1">
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary text-white "><i class="bi bi-funnel me-1"></i>Filter</button>
                    <a href="{{ route('bills.index') }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise"></i></a>
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
                        <th>Customer</th>
                        <th>Sheet</th>
                        <th>Month</th>
                        <th class="text-end">Units</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Paid</th>
                        <th class="text-end">Due</th>
                        <th>Status</th>
                        <th class="text-end" width="130">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bills as $bill)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>
                                @include('partials.customer_cell', ['customer' => $bill->customer])
                            </td>
                            <td>{{ $bill->customer->sheet->name ?? '—' }}</td>
                            <td>
                                {{ $bill->billing_month->format('M Y') }}
                                @if ($bill->is_opening)
                                    <span class="badge bg-secondary ms-1" title="Balance carried over from the previous system">Opening</span>
                                @endif
                            </td>
                            <td class="text-end">{{ $bill->is_opening ? '—' : number_format($bill->units, 2) }}</td>
                            <td class="text-end">{{ number_format($bill->total_amount, 2) }}</td>
                            <td class="text-end">{{ number_format($bill->paid_amount, 2) }}</td>
                            <td class="text-end">{{ number_format($bill->due_amount, 2) }}</td>
                            <td>
                                @if ($bill->isPaid())
                                    <span class="badge bg-success">Paid</span>
                                @elseif ($bill->isPartial())
                                    <span class="badge bg-info text-dark">Partial</span>
                                @else
                                    <span class="badge bg-warning text-dark">Unpaid</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('bills.show', $bill) }}" class="btn btn-outline-info"><i class="bi bi-eye me-1"></i>View</a>
                                    @unless ($bill->isPaid())
                                        @can('collect-payments')
                                            <a href="{{ route('payments.create', $bill->customer) }}" class="btn btn-outline-success"><i class="bi bi-cash-coin me-1"></i>Pay</a>
                                        @endcan
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted py-4">No bills found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3 d-flex justify-content-center">
        {{ $bills->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Print All loads the bill documents into an off-screen iframe and prints
    // that, so the browser's print dialog opens without leaving this page.
    (function () {
        const btn = document.getElementById('printAllBtn');
        if (! btn) return;

        const label = btn.innerHTML;
        let frame = null;

        function cleanup() {
            btn.disabled = false;
            btn.innerHTML = label;
            if (frame) {
                frame.remove();
                frame = null;
            }
        }

        btn.addEventListener('click', function () {
            if (btn.disabled) return;

            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Preparing…';

            frame = document.createElement('iframe');
            frame.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;visibility:hidden;';
            frame.src = btn.dataset.url;

            frame.addEventListener('load', function () {
                const win = frame.contentWindow;
                // Fires after the dialog closes, whether printed or cancelled.
                win.addEventListener('afterprint', cleanup);
                win.focus();
                win.print();
                // Safety net for browsers that never fire afterprint.
                setTimeout(cleanup, 60000);
            });

            document.body.appendChild(frame);
        });
    })();
</script>
@endpush
