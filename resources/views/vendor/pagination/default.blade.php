{{-- نمای صفحه‌بندی پروژه. لاراول ۱۳ نمای pagination::default را حذف کرد؛ این فایل روی ۱۲ و ۱۳ یکسان کار می‌کند. --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <ul class="pagination">
            @if ($paginator->onFirstPage())
                <li class="disabled" aria-disabled="true"><span>&rsaquo;</span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}" rel="prev">&rsaquo;</a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="disabled" aria-disabled="true"><span>{{ $element }}</span></li>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="active" aria-current="page"><span>{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}" rel="next">&lsaquo;</a></li>
            @else
                <li class="disabled" aria-disabled="true"><span>&lsaquo;</span></li>
            @endif
        </ul>
    </nav>
@endif
