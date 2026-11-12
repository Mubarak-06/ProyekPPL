@if($paginator->hasPages())
    <nav aria-label="Navigasi halaman">
        @if($paginator->onFirstPage())
            <span>Sebelumnya</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">Sebelumnya</a>
        @endif
        <span>Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>
        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">Berikutnya</a>
        @endif
    </nav>
@endif
