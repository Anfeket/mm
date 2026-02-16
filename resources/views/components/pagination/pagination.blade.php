@if ($paginator->hasPages())
    <nav class="pagination">
        @if ($paginator->onFirstPage())
            <span class="pagination-next disabled">&laquo;</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="pagination-next" rel="next">&laquo;</a>
        @endif

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pagination-ellipsis">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pagination-current">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="pagination-page">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="prev" class="pagination-prev">&raquo;</a>
        @else
            <span class="pagination-prev disabled">&raquo;</span>
        @endif
    </nav>
@endif
