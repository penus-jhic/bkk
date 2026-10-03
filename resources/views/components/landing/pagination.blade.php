{{-- Navigasi halaman gaya landing page sekolah: tombol panah merah di kedua ujung, nomor halaman di tengah --}}
@props(['paginator', 'label' => 'data'])
@if ($paginator->hasPages())
    @php
        $current = $paginator->currentPage();
        $last = $paginator->lastPage();
        // Tampilkan paling banyak 5 nomor di sekitar halaman sekarang
        $start = max(1, min($current - 2, $last - 4));
        $end = min($last, $start + 4);
        $arrowClass = 'shrink-0 w-12 h-12 rounded-lg flex items-center justify-center text-white transition-colors';
    @endphp
    <nav aria-label="Navigasi halaman" {{ $attributes->class('mt-10') }}>
        <div class="flex items-center justify-between gap-4">
            @if ($paginator->onFirstPage())
                <span aria-hidden="true" class="{{ $arrowClass }} bg-brand-darkred/40 cursor-not-allowed">
                    <x-sketch.arrow class="w-7 h-3.5 -scale-x-100" />
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya" class="{{ $arrowClass }} bg-brand-darkred shadow-lg shadow-brand-darkred/25 hover:bg-brand-deepred">
                    <x-sketch.arrow class="w-7 h-3.5 -scale-x-100" />
                </a>
            @endif

            <ul class="flex flex-wrap items-center justify-center gap-2">
                @for ($page = $start; $page <= $end; $page++)
                    <li>
                        <a
                            href="{{ $paginator->url($page) }}"
                            aria-label="Halaman {{ $page }}"
                            @if ($page === $current) aria-current="page" @endif
                            class="min-w-9 h-9 px-2.5 rounded-full flex items-center justify-center text-sm font-semibold transition-colors {{ $page === $current ? 'bg-brand-darkred text-white' : 'text-brand-ink/60 hover:bg-brand-ink/5 hover:text-brand-ink' }}"
                        >{{ $page }}</a>
                    </li>
                @endfor
            </ul>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya" class="{{ $arrowClass }} bg-brand-darkred shadow-lg shadow-brand-darkred/25 hover:bg-brand-deepred">
                    <x-sketch.arrow class="w-7 h-3.5" />
                </a>
            @else
                <span aria-hidden="true" class="{{ $arrowClass }} bg-brand-darkred/40 cursor-not-allowed">
                    <x-sketch.arrow class="w-7 h-3.5" />
                </span>
            @endif
        </div>

        <p class="mt-4 text-center text-sm text-brand-ink/50">
            Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ $paginator->total() }} {{ $label }}
        </p>
    </nav>
@endif
