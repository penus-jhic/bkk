{{-- Kartu berita bergambar penuh. featured = kartu besar untuk berita terbaru di halaman pertama --}}
@props(['item', 'featured' => false])
<a
    href="{{ route('bkk.berita.detail', $item->slug ?: $item->id) }}"
    {{ $attributes->class([
        'group relative block overflow-hidden rounded-card bg-linear-to-br from-brand-signal to-brand-deepred shadow-lg shadow-brand-ink/20 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-darkred',
        'aspect-4/3 sm:aspect-5/2 lg:aspect-7/2' => $featured,
        'aspect-video' => !$featured,
    ]) }}
>
    @if ($item->gambar_sampul)
        <img
            src="{{ $item->gambar_sampul }}"
            alt=""
            class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
            loading="lazy"
        >
    @else
        <div class="absolute inset-0 flex items-center justify-center text-white/15 transition-transform duration-500 group-hover:scale-110">
            <x-landing.icon name="fileText" class="{{ $featured ? 'w-28 h-28 md:w-36 md:h-36' : 'w-16 h-16' }}" />
        </div>
    @endif

    <div class="absolute inset-0 bg-linear-to-t from-brand-ink/90 via-brand-ink/40 to-transparent"></div>

    <div class="absolute inset-x-0 bottom-0 {{ $featured ? 'pl-5 pr-14 py-5 md:pl-7 md:pr-16 md:py-7' : 'pl-4 pr-12 py-4' }}">
        @if ($featured)
            <p class="font-display text-4xl md:text-5xl font-bold uppercase tracking-wide leading-none text-white">
                <x-sketch.underline tone="text-brand-warmred">Terbaru</x-sketch.underline>
            </p>
        @endif
        <p class="{{ $featured ? 'mt-6 md:mt-7' : '' }} text-left text-[11px] font-semibold uppercase tracking-[0.2em] text-white/70">
            {{ $item->kategori->nama ?? 'Berita BKK' }} &middot; {{ $item->formatted_date }}
        </p>
        {{-- text-left: judul yang terbungkus jadi renggang antar katanya kalau ikut justify dari body --}}
        <h3 class="mt-1.5 text-left font-semibold leading-snug text-white {{ $featured ? 'max-w-xl text-lg line-clamp-3 md:line-clamp-2' : 'text-base line-clamp-2' }}">
            {{ $item->judul }}
        </h3>
    </div>

    <span class="absolute text-white transition-transform group-hover:translate-x-1 {{ $featured ? 'bottom-6 right-5 md:bottom-8 md:right-7' : 'bottom-5 right-4' }}">
        <x-sketch.arrow class="w-7 h-3.5" />
    </span>
</a>
