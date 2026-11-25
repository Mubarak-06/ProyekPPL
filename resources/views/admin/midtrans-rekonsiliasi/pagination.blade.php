@if ($paginator->hasPages())
    <nav class="rekon-pad rekon-links" aria-label="{{ $label }}">

        <span>
            Halaman {{ $paginator->currentPage() }} dari {{ $paginator->lastPage() }}
        </span>

        <div class="rekon-pagination">

            @if ($paginator->previousPageUrl())

                <a href="{{ $paginator->previousPageUrl() }}" class="rekon-action">
                    <i class="bi bi-chevron-left"></i>
                    Sebelumnya
                </a>

            @endif

            @if ($paginator->nextPageUrl())

                <a href="{{ $paginator->nextPageUrl() }}" class="rekon-action">
                    Berikutnya
                    <i class="bi bi-chevron-right"></i>
                </a>

            @endif

        </div>

    </nav>
@endif