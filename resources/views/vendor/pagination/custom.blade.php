@if ($paginator->hasPages() || $paginator->total() > 0)
    <nav class="pagination-container" role="navigation" aria-label="Navigasi Halaman">
        <div class="pagination-info">
            Menampilkan <strong>{{ $paginator->firstItem() ?? 0 }}</strong> sampai <strong>{{ $paginator->lastItem() ?? 0 }}</strong> dari <strong>{{ $paginator->total() }}</strong> produk
        </div>

        @if ($paginator->hasPages())
            <ul class="pagination-nav">
                {{-- Tombol Sebelumnya --}}
                @if ($paginator->onFirstPage())
                    <li class="page-item disabled" aria-disabled="true" aria-label="Halaman sebelumnya">
                        <span class="page-link" aria-hidden="true">&laquo; Sebelumnya</span>
                    </li>
                @else
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya">&laquo; Sebelumnya</a>
                    </li>
                @endif

                {{-- Nomor Halaman --}}
                @foreach ($elements as $element)
                    {{-- Separator titik tiga --}}
                    @if (is_string($element))
                        <li class="page-item disabled" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                    @endif

                    {{-- Link halaman --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                            @else
                                <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                {{-- Tombol Berikutnya --}}
                @if ($paginator->hasMorePages())
                    <li class="page-item">
                        <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya">Berikutnya &raquo;</a>
                    </li>
                @else
                    <li class="page-item disabled" aria-disabled="true" aria-label="Halaman berikutnya">
                        <span class="page-link" aria-hidden="true">Berikutnya &raquo;</span>
                    </li>
                @endif
            </ul>
        @endif
    </nav>
@endif
