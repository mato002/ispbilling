@if ($invoices->hasPages())
    {{ $invoices->withQueryString()->links() }}
@endif
