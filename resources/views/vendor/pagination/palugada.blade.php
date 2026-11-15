@if ($paginator->total() > 0)
<nav class="pg-wrap" role="navigation" aria-label="Navigasi halaman">

    <p class="pg-info">
        Menampilkan
        <strong>{{ $paginator->firstItem() }}</strong>
        sampai
        <strong>{{ $paginator->lastItem() }}</strong>
        dari
        <strong>{{ $paginator->total() }}</strong>
        data
    </p>

    <div class="pg-pages">

        {{-- TOMBOL SEBELUMNYA --}}
        @if ($paginator->onFirstPage())
            <span class="pg-link pg-disabled" aria-disabled="true">
                &laquo; Sebelumnya
            </span>
        @else
            <a class="pg-link"
               href="{{ $paginator->previousPageUrl() }}">
                &laquo; Sebelumnya
            </a>
        @endif

        {{-- NOMOR HALAMAN --}}
        @if ($paginator->hasPages())

            @foreach ($elements as $element)

                @if (is_string($element))
                    <span class="pg-dots">
                        {{ $element }}
                    </span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)

                        @if ($page == $paginator->currentPage())
                            <span class="pg-link pg-active"
                                  aria-current="page">
                                {{ $page }}
                            </span>
                        @else
                            <a class="pg-link"
                               href="{{ $url }}">
                                {{ $page }}
                            </a>
                        @endif

                    @endforeach
                @endif

            @endforeach

        @else

            {{-- TETAP TAMPILKAN HALAMAN 1 --}}
            <span class="pg-link pg-active"
                  aria-current="page">
                1
            </span>

        @endif

        {{-- TOMBOL BERIKUTNYA --}}
        @if ($paginator->hasMorePages())
            <a class="pg-link"
               href="{{ $paginator->nextPageUrl() }}">
                Berikutnya &raquo;
            </a>
        @else
            <span class="pg-link pg-disabled"
                  aria-disabled="true">
                Berikutnya &raquo;
            </span>
        @endif

    </div>

</nav>
@endif