{{-- Print action (PDF = browser print). --}}
<div class="d-flex gap-2 no-print">
    <button type="button" onclick="window.print()" class="btn btn-outline-secondary">
        <i class="bi bi-printer me-1"></i>Print
        @isset($printCount)
            <span class="badge bg-light text-dark ms-1">{{ $printCount }}</span>
        @endisset
    </button>
</div>

@once
@push('styles')
<style>
    @media print {
        /* Zero page margin so the browser drops its own header/footer
           (date, title, page URL). The paper margin moves onto the body. */
        @page { size: A4 portrait; margin: 0; }
        body {
            -webkit-print-color-adjust: exact; print-color-adjust: exact;
            padding: 12mm !important;
        }
        .navbar, nav, .no-print { display: none !important; }
        main.py-4 { padding: 0 !important; }
        .container, .container-fluid { max-width: 100% !important; padding: 0 !important; margin: 0 !important; }

        /* Flatten cards so only the data prints */
        .card { border: none !important; box-shadow: none !important; }
        .card-body { padding: 0 !important; }
        .table-responsive { overflow: visible !important; }

        /* Report headings */
        h4, h5, h6 { color: #000 !important; }

        /* Tables: full width, bordered, repeat header on each page */
        table.table { width: 100% !important; border-collapse: collapse !important; font-size: 12px; }
        table.table th, table.table td {
            border: 1px solid #444 !important;
            padding: 4px 6px !important;
        }
        table.table thead { display: table-header-group; }
        table.table tfoot { display: table-footer-group; }
        table.table tr, table.table td, table.table th { page-break-inside: avoid; }
        .table-light, thead.table-light th { background: #eee !important; }
    }
</style>
@endpush
@endonce
