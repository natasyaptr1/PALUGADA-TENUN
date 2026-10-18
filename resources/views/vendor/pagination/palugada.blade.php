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

    @if ($paginator->hasPages())
        <div class="pg-pages">

            {{-- Tombol sebelumnya --}}
            @if ($paginator->onFirstPage())
                <span class="pg-link pg-disabled">« Sebelumnya</span>
            @else
                <a class="pg-link" href="{{ $paginator->previousPageUrl() }}">
                    « Sebelumnya
                </a>
            @endif

            {{-- Nomor halaman --}}
            @foreach ($elements as $element)

                @if (is_string($element))
                    <span class="pg-dots">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="pg-link pg-active" aria-current="page">
                                {{ $page }}
                            </span>
                        @else
                            <a class="pg-link" href="{{ $url }}">
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif

            @endforeach

            {{-- Tombol berikutnya --}}
            @if ($paginator->hasMorePages())
                <a class="pg-link" href="{{ $paginator->nextPageUrl() }}">
                    Berikutnya »
                </a>
            @else
                <span class="pg-link pg-disabled">Berikutnya »</span>
            @endif

        </div>
    @endif
</nav>
@endif
