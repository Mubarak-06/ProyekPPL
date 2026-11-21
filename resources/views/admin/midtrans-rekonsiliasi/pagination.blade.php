@if ($paginator->hasPages())
    <nav class="rekon-pad rekon-links" aria-label="{{ $label }}">
        @if ($paginator->previousPageUrl())
            <a href="{{ $paginator->previousPageUrl() }}">Sebelumnya</a>
        @endif
        <span>Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}</span>
        @if ($paginator->nextPageUrl())
            <a href="{{ $paginator->nextPageUrl() }}">Berikutnya</a>
        @endif
    </nav>
@endif
