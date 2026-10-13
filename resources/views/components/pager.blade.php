@props(['paginator'])

{{--
    Paginator Laravel yang dirender memakai kelas pager dari design system LONTAR
    (.pager / .pager__btn), bukan markup default Tailwind.

    `LengthAwarePaginator::elements()` protected di Laravel 13, jadi jendela
    nomor halaman disusun dari API publik (currentPage / lastPage / url).
--}}
@if ($paginator instanceof \Illuminate\Contracts\Pagination\Paginator && $paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $previous = $paginator->previousPageUrl();
        $next = $paginator->nextPageUrl();

        if ($paginator instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            $last = $paginator->lastPage();
            $window = 2;
            $from = max(1, $current - $window);
            $to = min($last, $current + $window);
            $pages = range($from, $to);
            $showLeadingGap = $from > 1;
            $showTrailingGap = $to < $last;
        } else {
            $last = null;
            $pages = [];
            $showLeadingGap = $showTrailingGap = false;
        }
    @endphp
    <nav class="pager" aria-label="Navigasi halaman">
        @if ($previous)
            <a class="pager__btn" href="{{ $previous }}" rel="prev" aria-label="Sebelumnya"><svg data-icon="chevron-left"></svg></a>
        @else
            <span class="pager__btn is-disabled" aria-hidden="true"><svg data-icon="chevron-left"></svg></span>
        @endif

        @if ($showLeadingGap)
            <a class="pager__btn" href="{{ $paginator->url(1) }}" aria-label="Halaman 1">1</a>
            @if ($from > 2)
                <span class="pager__btn is-disabled">&hellip;</span>
            @endif
        @endif

        @foreach ($pages as $page)
            @if ($page == $current)
                <span class="pager__btn is-active" aria-current="page">{{ $page }}</span>
            @else
                <a class="pager__btn" href="{{ $paginator->url($page) }}">{{ $page }}</a>
            @endif
        @endforeach

        @if ($showTrailingGap)
            @if ($to < $last - 1)
                <span class="pager__btn is-disabled">&hellip;</span>
            @endif
            <a class="pager__btn" href="{{ $paginator->url($last) }}" aria-label="Halaman {{ $last }}">{{ $last }}</a>
        @endif

        @if ($next)
            <a class="pager__btn" href="{{ $next }}" rel="next" aria-label="Berikutnya"><svg data-icon="chevron-right"></svg></a>
        @else
            <span class="pager__btn is-disabled" aria-hidden="true"><svg data-icon="chevron-right"></svg></span>
        @endif
    </nav>
@endif
