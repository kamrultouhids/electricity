{{-- Export / print actions. Excel = CSV download of the current filtered view; PDF = browser print. --}}
<div class="d-flex gap-2 no-print">
    <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="btn btn-outline-success">
        <i class="bi bi-file-earmark-excel me-1"></i>Excel
    </a>
    <button type="button" onclick="window.print()" class="btn btn-outline-secondary">
        <i class="bi bi-printer me-1"></i>PDF / Print
    </button>
</div>
