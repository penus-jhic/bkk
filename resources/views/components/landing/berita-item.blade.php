{{-- Kartu berita berlatar putih: sampul, kategori, tanggal, judul & ringkasan. Dipakai di halaman daftar berita
     dan rekomendasi di detail berita (x-landing.berita-card dipakai di beranda).
     readFallback = teks estimasi baca kalau kosong, cta = label tautan, author = tampilkan nama penulis. --}}
@props(['item', 'readFallback' => '3 Menit', 'cta' => 'Selengkapnya', 'author' => true])
@php $url = route('bkk.berita.detail', $item->slug ?: $item->id); @endphp
<article {{ $attributes->class('group relative flex flex-col overflow-hidden rounded-card bg-white ring-1 ring-brand-ink/10 shadow-sm shadow-brand-ink/5 transition-all duration-300 hover:-translate-y-1 hover:shadow-softpill hover:ring-brand-darkred/20') }}>
    <a href="{{ $url }}" tabindex="-1" aria-hidden="true" class="relative block aspect-16/10 overflow-hidden bg-linear-to-br from-brand-signal to-brand-deepred">
        @if ($item->gambar_sampul)
            <img src="{{ $item->gambar_sampul }}" alt="{{ $item->judul }}" class="absolute inset-0 size-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
        @else
            <span class="absolute inset-0 flex items-center justify-center text-white/20 transition-transform duration-500 group-hover:scale-110">
                <x-landing.icon name="newspaper" class="w-14 h-14" />
            </span>
        @endif
        <span class="absolute left-3 top-3 rounded-full bg-white/95 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.15em] text-brand-darkred shadow-sm">
            {{ $item->kategori->nama ?? 'Umum' }}
        </span>
    </a>

    <div class="flex flex-1 flex-col p-5">
        <p class="flex flex-wrap items-center gap-x-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-brand-ink/50">
            <time>{{ $item->formatted_date }}</time>
            <span aria-hidden="true" class="text-brand-darkred">&bull;</span>
            <span>{{ $item->estimasi_baca ?? $readFallback }}</span>
        </p>
        {{-- text-left: judul yang terbungkus jadi renggang antar katanya kalau ikut justify dari body --}}
        <h3 class="mt-2.5 text-left text-base font-semibold leading-snug line-clamp-2 transition-colors group-hover:text-brand-darkred">
            <a href="{{ $url }}">{{ $item->judul }}</a>
        </h3>
        <p class="mt-2 text-sm leading-relaxed text-brand-ink/65 line-clamp-2">{{ $item->ringkasan }}</p>

        <div class="mt-auto flex items-center justify-between gap-4 pt-5">
            @if ($author)
                <span class="truncate text-xs font-medium text-brand-ink/60">{{ $item->penulis_nama }}</span>
            @endif
            <a href="{{ $url }}" class="inline-flex shrink-0 items-center gap-2 text-sm font-semibold text-brand-darkred {{ $author ? '' : 'mr-auto' }}">
                {{ $cta }}
                <x-sketch.arrow class="w-6 h-3 transition-transform group-hover:translate-x-1" />
            </a>
        </div>
    </div>
</article>
