{{--
    Detail berita BKK. Gaya mengikuti landing page sekolah (coretan tangan, merah marun),
    data dari PublicController@beritaDetail: $berita, $relatedBerita.
--}}
@php
    $navSection = 'berita';
    $shareUrl = url()->current();
    $authorInitials = strtoupper(substr($berita->penulis_nama, 0, 2));

    // Coretan hanya di kata terakhir judul: kalau seluruh judul, garisnya ikut melebar saat judul terlipat beberapa baris
    $titleWords = explode(' ', trim($berita->judul));
    $titleLastWord = array_pop($titleWords);
    $titleFirstWords = implode(' ', $titleWords);

    $steps = [
        ['title' => 'Lengkapi Profil Siswa', 'desc' => 'Upload nilai & portofolio praktikum.'],
        ['title' => 'Generate ATS CV', 'desc' => 'Unduh format PDF terstandar resmi.'],
        ['title' => 'Apply Lowongan Sekali Klik', 'desc' => 'Tersambung ke 45+ IDUKA mitra Penus.'],
    ];
@endphp

@extends('index.layouts.landing')

@section('title', $berita->judul . ' - BKK SMK Plus Pelita Nusantara')
@section('description', Str::limit($berita->ringkasan ?: $berita->judul, 160))

{{-- Isi artikel berasal dari Markdown, jadi gayanya ditulis sebagai CSS biasa. Coretan (garis bawah subjudul,
     garis kutipan, panah daftar) memakai SVG goresan yang sama dengan mesin coretan, tanpa animasi. --}}
@push('styles')
<style>
    .berita-prose { font-size: 1rem; line-height: 1.85; color: rgb(36 16 18 / 0.82); }
    .berita-prose > * + * { margin-top: 1.35rem; }
    .berita-prose h1, .berita-prose h2, .berita-prose h3, .berita-prose h4 {
        font-family: "Oswald", ui-sans-serif, system-ui, sans-serif;
        font-weight: 700; text-transform: uppercase; letter-spacing: 0.025em; line-height: 1.25; color: #241012; text-align: left;
    }
    .berita-prose h1 { font-size: 1.85rem; margin-top: 2.5rem; color: #7A1018; }
    .berita-prose h2 {
        font-size: 1.5rem; margin-top: 2.75rem; padding-bottom: 0.9rem;
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 300 20' fill='none' stroke='%23B72A32' stroke-linecap='round'%3E%3Cpath d='M6 10C70 6 150 9 220 6S285 5 296 4' stroke-width='3.5'/%3E%3Cpath d='M298 8C280 9 220 10 150 11S40 13 2 13' stroke-width='2'/%3E%3C/svg%3E") no-repeat left bottom / 7.5rem 0.6rem;
    }
    .berita-prose h3 { font-size: 1.2rem; margin-top: 2rem; color: #7A1018; }
    .berita-prose h4 { font-size: 1.05rem; margin-top: 1.75rem; }
    .berita-prose a { color: #7A1018; font-weight: 600; text-decoration: underline; text-decoration-color: rgb(122 16 24 / 0.35); text-underline-offset: 3px; }
    .berita-prose a:hover { text-decoration-color: #7A1018; }
    .berita-prose strong { color: #241012; font-weight: 700; }
    .berita-prose em { color: #5C0B12; }
    .berita-prose blockquote {
        margin: 2.25rem 0; padding: 0.25rem 0 0.25rem 2rem; font-size: 1.125rem; font-weight: 600; line-height: 1.55; color: #241012;
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 300' preserveAspectRatio='none' fill='none' stroke='%237A1018' stroke-linecap='round'%3E%3Cpath d='M5 2C3 70 7 160 4 298' stroke-width='3' vector-effect='non-scaling-stroke'/%3E%3Cpath d='M9 12C7 90 10 190 7 284' stroke-width='1.5' vector-effect='non-scaling-stroke'/%3E%3C/svg%3E") no-repeat left top / 0.75rem 100%;
    }
    .berita-prose blockquote p + p { margin-top: 0.75rem; }
    .berita-prose ul { list-style: none; padding: 0; }
    .berita-prose ul > li {
        padding-left: 2.25rem;
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 48 24' fill='none' stroke='%237A1018' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M3 13C12 11 22 13 31 11S41 11 45 12' stroke-width='2.75'/%3E%3Cpath d='M34 4C38 7 42 10 46 12' stroke-width='2.75'/%3E%3Cpath d='M46 11C42 15 38 18 33 21' stroke-width='2.75'/%3E%3C/svg%3E") no-repeat 0 0.5em / 1.5rem 0.75rem;
    }
    .berita-prose ol { list-style: decimal; padding-left: 1.75rem; }
    .berita-prose ol > li { padding-left: 0.4rem; }
    .berita-prose ol > li::marker { color: #7A1018; font-family: "Oswald", ui-sans-serif, sans-serif; font-weight: 700; }
    .berita-prose li + li { margin-top: 0.6rem; }
    .berita-prose li > ul, .berita-prose li > ol { margin-top: 0.6rem; }
    .berita-prose hr {
        height: 0.75rem; border: 0; margin: 2.75rem 0;
        background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 300 12' preserveAspectRatio='none' fill='none' stroke='%237A1018' stroke-opacity='.35' stroke-linecap='round'%3E%3Cpath d='M2 5C70 3 160 7 298 4' stroke-width='3' vector-effect='non-scaling-stroke'/%3E%3Cpath d='M12 9C90 7 190 10 284 7' stroke-width='1.5' vector-effect='non-scaling-stroke'/%3E%3C/svg%3E") no-repeat center / 100% 100%;
    }
    .berita-prose img { width: 100%; border-radius: 1rem; margin: 2rem 0; box-shadow: 0 8px 30px -8px rgb(36 16 18 / 0.18); }
    .berita-prose table { width: 100%; border-collapse: separate; border-spacing: 0; margin: 2rem 0; font-size: 0.925rem; overflow: hidden; border-radius: 1rem; box-shadow: 0 0 0 1px rgb(36 16 18 / 0.1); }
    .berita-prose th, .berita-prose td { padding: 0.75rem 1rem; text-align: left; border-bottom: 1px solid rgb(36 16 18 / 0.08); }
    .berita-prose tr:last-child td { border-bottom: 0; }
    .berita-prose th { background: #7A1018; color: #fff; font-weight: 600; }
    .berita-prose tbody tr:nth-child(even) td { background: rgb(232 232 232 / 0.45); }
    .berita-prose code { background: rgb(122 16 24 / 0.08); color: #7A1018; padding: 0.15rem 0.4rem; border-radius: 0.35rem; font-size: 0.9em; }
    .berita-prose pre { background: #241012; color: #fff; padding: 1.25rem; border-radius: 1rem; overflow-x: auto; margin: 2rem 0; }
    .berita-prose pre code { background: transparent; color: inherit; padding: 0; }
</style>
@endpush

@section('content')
{{-- ============================================================
     1. KEPALA ARTIKEL: breadcrumb, kategori, judul, penulis & tombol bagikan
     ============================================================ --}}
<section class="relative bg-white px-6 pt-32 md:pt-40">
    {{-- Dekorasi titik-titik --}}
    <div aria-hidden="true" class="pointer-events-none absolute right-6 top-28 hidden md:block w-40 h-28 bg-[radial-gradient(circle,var(--color-brand-mist)_2px,transparent_2.5px)] bg-size-[22px_22px]"></div>

    <div class="relative max-w-6xl mx-auto animate-fade-up">
        <nav aria-label="Breadcrumb">
            {{-- Tanpa flex-wrap: judul berita yang menyusut & terpotong, supaya breadcrumb tetap satu baris di HP --}}
            <ol class="flex items-center gap-2 text-sm text-brand-ink/50">
                <li class="shrink-0">
                    <a href="{{ route('bkk.index') }}" class="flex items-center gap-1.5 transition-colors hover:text-brand-darkred">
                        <x-landing.icon name="home" class="w-4 h-4" />
                        Beranda
                    </a>
                </li>
                <li class="flex shrink-0 items-center gap-2">
                    <x-landing.icon name="chevronRight" class="w-3.5 h-3.5" />
                    <a href="{{ route('bkk.berita') }}" class="transition-colors hover:text-brand-darkred">Berita &amp; Agenda</a>
                </li>
                <li class="hidden shrink-0 items-center gap-2 sm:flex">
                    <x-landing.icon name="chevronRight" class="w-3.5 h-3.5" />
                    <span>{{ $berita->kategori->nama ?? 'Warta' }}</span>
                </li>
                <li class="flex min-w-0 items-center gap-2">
                    <x-landing.icon name="chevronRight" class="w-3.5 h-3.5 shrink-0" />
                    <span aria-current="page" class="truncate font-medium text-brand-ink sm:max-w-sm">{{ $berita->judul }}</span>
                </li>
            </ol>
        </nav>

        <div class="mt-10 md:mt-12 flex flex-wrap items-center gap-x-10 gap-y-3">
            <p class="text-xs sm:text-sm font-semibold uppercase tracking-[0.25em] text-brand-darkred">
                <x-sketch.sparks>{{ $berita->kategori->nama ?? 'Warta & Agenda BKK' }}</x-sketch.sparks>
            </p>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-softmist px-3 py-1 text-xs font-semibold text-brand-ink/70">
                    <x-landing.icon name="calendar" class="w-3.5 h-3.5 text-brand-darkred" />
                    {{ $berita->formatted_date }}
                </span>
                @if ($berita->views_count > 0)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-brand-softmist px-3 py-1 text-xs font-semibold text-brand-ink/70">
                        <x-landing.icon name="eye" class="w-3.5 h-3.5 text-brand-darkred" />
                        {{ number_format($berita->views_count) }} Kali Dibaca
                    </span>
                @endif
            </div>
        </div>

        {{-- text-left: judul panjang yang rata kiri-kanan jadi renggang antar katanya. delay: coretan setelah teks muncul --}}
        <h1 class="mt-5 max-w-4xl text-left font-display text-3xl md:text-4xl lg:text-5xl font-bold uppercase tracking-wide leading-tight">
            @if ($titleFirstWords !== '')
                {{ $titleFirstWords }}
            @endif
            <x-sketch.underline size="lg" tone="text-brand-signal" :delay="500">{{ $titleLastWord }}</x-sketch.underline>
        </h1>

        {{-- mt-12: ruang untuk garis coretan yang menggantung di bawah judul --}}
        <div class="relative mt-12 md:mt-14 flex flex-col gap-6 py-7 sm:flex-row sm:items-center sm:justify-between">
            <x-sketch.rule class="text-brand-ink/20 left-0 right-0 top-0 h-3" />
            <x-sketch.rule :delay="200" class="text-brand-ink/20 left-0 right-0 bottom-0 h-3 rotate-180" />

            <div class="flex items-center gap-4">
                @if ($berita->penulis_avatar)
                    <img src="{{ $berita->penulis_avatar }}" alt="{{ $berita->penulis_nama }}" class="w-14 h-14 shrink-0 rounded-full object-cover ring-2 ring-brand-darkred/15">
                @else
                    <span class="flex w-14 h-14 shrink-0 items-center justify-center rounded-full bg-brand-darkred/10 font-display text-lg font-bold text-brand-darkred ring-2 ring-brand-darkred/15">{{ $authorInitials }}</span>
                @endif
                <div class="flex min-w-0 flex-col">
                    <span class="flex items-center gap-1.5 font-semibold">
                        {{ $berita->penulis_nama }}
                        <span title="Penulis Terverifikasi" class="text-brand-darkred">
                            <x-landing.icon name="badgeCheck" class="w-4.5 h-4.5" />
                            <span class="sr-only">Penulis Terverifikasi</span>
                        </span>
                    </span>
                    <span class="text-sm text-brand-ink/60">{{ $berita->penulis_jabatan ?? 'Kontributor Resmi BKK SMK Plus Pelita Nusantara' }}</span>
                    <span class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-brand-ink/55">
                        <span class="flex items-center gap-1">
                            <x-landing.icon name="calendar" class="w-3.5 h-3.5 text-brand-darkred" />
                            {{ $berita->formatted_date }}
                        </span>
                        <span aria-hidden="true">&bull;</span>
                        <span class="flex items-center gap-1">
                            <x-landing.icon name="clock" class="w-3.5 h-3.5 text-brand-darkred" />
                            {{ $berita->estimasi_baca ?? '5 Menit Baca' }}
                        </span>
                    </span>
                </div>
            </div>

            @php $shareButton = 'inline-flex w-11 h-11 items-center justify-center rounded-full bg-brand-darkred/10 text-brand-darkred transition-colors hover:bg-brand-darkred hover:text-white'; @endphp
            <div class="flex flex-wrap items-center gap-2.5">
                <button type="button" x-data="{ saved: false }" @click="saved = !saved" aria-label="Simpan Artikel" :aria-pressed="saved.toString()"
                    class="{{ $shareButton }}" :class="{ 'bg-brand-darkred! text-white!': saved }">
                    <x-landing.icon name="bookmark" class="w-5 h-5" x-bind:class="{ 'fill-current': saved }" />
                </button>
                <a href="https://api.whatsapp.com/send?text={{ urlencode($berita->judul . ' ' . $shareUrl) }}" target="_blank" rel="noopener noreferrer" aria-label="Bagikan ke WhatsApp" title="Bagikan ke WhatsApp" class="{{ $shareButton }}">
                    <x-landing.icon name="whatsapp" class="w-5 h-5" />
                </a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode($shareUrl) }}" target="_blank" rel="noopener noreferrer" aria-label="Bagikan ke LinkedIn" title="Bagikan ke LinkedIn" class="{{ $shareButton }}">
                    <x-landing.icon name="linkedin" class="w-5 h-5" />
                </a>
                <button type="button" x-data="copyLink(@js($shareUrl))" @click="copy()" aria-label="Salin Tautan"
                    class="inline-flex h-11 items-center gap-2 rounded-full bg-linear-to-r from-brand-signal to-brand-darkred px-5 text-sm font-semibold text-white shadow-lg shadow-brand-darkred/25 transition-transform hover:-translate-y-0.5">
                    <x-landing.icon name="copy" class="w-4 h-4" x-show="!copied" />
                    <x-landing.icon name="check" class="w-4 h-4" x-show="copied" x-cloak />
                    <span x-text="copied ? 'Tersalin!' : 'Salin Link'">Salin Link</span>
                    <span role="status" class="sr-only" x-text="copied ? 'Tautan berita berhasil disalin' : ''"></span>
                </button>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================
     2. SAMPUL, ISI ARTIKEL & SIDEBAR
     ============================================================ --}}
<section class="relative bg-white px-6 pt-14 pb-20 md:pt-16 md:pb-28">
    <div class="max-w-6xl mx-auto">
        @if ($berita->gambar_sampul)
            <figure class="relative mb-16 md:mb-20">
                <div class="relative overflow-hidden rounded-card bg-brand-softmist shadow-2xl shadow-brand-ink/15">
                    <img src="{{ $berita->gambar_sampul }}" alt="{{ $berita->judul }}" class="w-full aspect-video max-h-136 object-cover">
                    @if ($berita->caption_gambar)
                        <figcaption class="absolute inset-x-0 bottom-0 bg-linear-to-t from-brand-ink/80 to-transparent px-5 pt-16 pb-5 md:px-8 md:pb-7 text-sm text-white/90">
                            {{ $berita->caption_gambar }}
                        </figcaption>
                    @endif
                </div>
                {{-- Siku coretan di pojok kiri atas & kanan bawah sampul --}}
                <x-sketch.corner class="-left-4 -top-4 w-24 h-10 md:-left-6 md:-top-6 md:w-36 md:h-14" />
                <x-sketch.corner :delay="350" class="-right-4 -bottom-4 rotate-180 w-24 h-10 md:-right-6 md:-bottom-6 md:w-36 md:h-14" />
            </figure>
        @endif

        <div class="grid gap-16 lg:grid-cols-[1fr_20rem] lg:items-start xl:gap-20">
            <article class="min-w-0">
                @if ($berita->ringkasan)
                    {{-- Paragraf pembuka dengan garis tegak coretan, pengganti border-l --}}
                    <div class="relative mb-10 py-1 pl-8 md:pl-10">
                        <x-sketch.rule bold vertical class="text-brand-darkred -top-1 -bottom-1 left-0 w-3" />
                        <p class="text-lg md:text-xl font-medium italic leading-relaxed text-brand-ink">{{ $berita->ringkasan }}</p>
                    </div>
                @endif

                <div class="berita-prose">
                    {!! $berita->rendered_konten !!}
                </div>

                {{-- Kotak penulis --}}
                <div class="relative mt-16 rounded-card bg-brand-softmist p-6 md:p-8">
                    <x-sketch.corner class="-left-3 -top-3 w-20 h-9" />
                    <div class="flex flex-col items-center gap-6 text-center sm:flex-row sm:items-start sm:text-left">
                        @if ($berita->penulis_avatar)
                            <img src="{{ $berita->penulis_avatar }}" alt="{{ $berita->penulis_nama }}" class="w-20 h-20 shrink-0 rounded-full object-cover ring-4 ring-white">
                        @else
                            <span class="flex w-20 h-20 shrink-0 items-center justify-center rounded-full bg-brand-darkred font-display text-2xl font-bold text-white ring-4 ring-white">{{ $authorInitials }}</span>
                        @endif
                        <div class="flex flex-col gap-2">
                            <div class="flex flex-col items-center gap-2 sm:flex-row">
                                <span class="font-display text-xl font-bold uppercase tracking-wide">{{ $berita->penulis_nama }}</span>
                                <span class="w-fit rounded-full bg-brand-darkred/10 px-2.5 py-0.5 text-xs font-semibold text-brand-darkred">
                                    {{ $berita->penulis_jabatan ?? 'Penulis & Kontributor' }}
                                </span>
                            </div>
                            <p class="text-sm leading-relaxed text-brand-ink/70">
                                {{ $berita->penulis_bio ?? 'Tim Humas & Pusat Karier Bursa Kerja Khusus SMK Plus Pelita Nusantara Cibinong.' }}
                            </p>
                            <div class="flex flex-wrap items-center justify-center gap-3 pt-2 text-sm sm:justify-start">
                                <a href="{{ url('/bkk/tentang') }}" class="inline-flex items-center gap-1.5 font-semibold text-brand-darkred hover:underline">
                                    <x-landing.icon name="mail" class="w-4 h-4" />
                                    Konsultasi Bimbingan Karier
                                </a>
                                <span aria-hidden="true" class="text-brand-ink/30">&bull;</span>
                                <span class="text-brand-ink/60">Ruang BKK Gedung A Lt. 2</span>
                            </div>
                        </div>
                    </div>
                </div>
            </article>

            {{-- Sidebar: bingkai coretan penuh, ikut menempel saat discroll di desktop (top-32 = di bawah navbar mengambang) --}}
            <aside aria-labelledby="alur-cepat" class="relative p-6 lg:sticky lg:top-32">
                <x-sketch.box />
                <h2 id="alur-cepat" class="flex items-center gap-2 font-display text-lg font-bold uppercase tracking-wide">
                    <x-landing.icon name="flag" class="w-5 h-5 text-brand-darkred" />
                    Alur Cepat BKK Penus
                </h2>

                <ol class="mt-5">
                    @foreach ($steps as $i => $step)
                        <li class="relative flex items-start gap-3.5 py-3.5 first:pt-0">
                            @if ($i > 0)
                                <x-sketch.rule :delay="$i * 100" class="text-brand-darkred/30 -left-1 -right-1 -top-1.5 h-3" />
                            @endif
                            <span class="flex w-8 h-8 shrink-0 items-center justify-center rounded-full bg-brand-darkred font-display text-sm font-bold text-white">{{ $i + 1 }}</span>
                            <span class="flex flex-col">
                                <span class="text-sm font-semibold">{{ $step['title'] }}</span>
                                <span class="text-xs leading-relaxed text-brand-ink/60">{{ $step['desc'] }}</span>
                            </span>
                        </li>
                    @endforeach
                </ol>

                <div class="mt-5 rounded-card bg-brand-softmist p-4">
                    <p class="text-[11px] font-bold uppercase tracking-[0.25em] text-brand-darkred">Agenda Terdekat</p>
                    <p class="mt-2 font-display text-base font-bold uppercase tracking-wide">Walk-in Interview Batch 1</p>
                    <p class="mt-1 text-xs text-brand-ink/60">Kamis, 6 Maret 2025 di Aula Utama</p>
                    <a href="{{ route('bkk.berita') }}" class="group mt-3 inline-flex items-center gap-2 text-sm font-semibold text-brand-darkred">
                        Lihat Syarat &amp; Dokumen
                        <x-sketch.arrow class="w-6 h-3 transition-transform group-hover:translate-x-1" />
                    </a>
                </div>

                <div class="mt-4 rounded-card bg-brand-darkred/5 p-4">
                    <p class="text-[11px] font-bold uppercase tracking-[0.25em] text-brand-darkred">Unduhan Berkas</p>
                    <div class="mt-3 flex items-center justify-between gap-2 rounded-lg bg-white p-2.5">
                        <span class="flex min-w-0 items-center gap-2">
                            <x-landing.icon name="fileText" class="w-5 h-5 shrink-0 text-brand-darkred" />
                            <span class="truncate text-xs font-medium">Template_CV_ATS_Penus.docx</span>
                        </span>
                        <button type="button" aria-label="Unduh Dokumen" class="shrink-0 rounded-full p-1 text-brand-ink/50 transition-colors hover:text-brand-darkred">
                            <x-landing.icon name="download" class="w-5 h-5" />
                        </button>
                    </div>
                </div>
            </aside>
        </div>
    </div>
</section>

{{-- ============================================================
     3. REKOMENDASI BACAAN
     ============================================================ --}}
@if ($relatedBerita->count() > 0)
    <section class="relative bg-brand-softmist px-6 py-20 md:py-28">
        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-brand-darkred">Rekomendasi Bacaan</p>
                    {{-- Margin negatif = padding bingkai, supaya teks tetap sejajar dan garisnya menjorok ke kiri --}}
                    <h2 class="mt-3 text-left font-display text-3xl md:text-4xl font-bold uppercase tracking-wide leading-tight">
                        <x-sketch.frame class="-ml-4 md:-ml-5">Artikel &amp; Pembekalan Terkait</x-sketch.frame>
                    </h2>
                </div>
                <a href="{{ route('bkk.berita') }}" class="group inline-flex shrink-0 items-center gap-2 text-sm font-semibold text-brand-darkred">
                    Lihat Semua Artikel
                    <x-sketch.arrow class="w-7 h-3.5 transition-transform group-hover:translate-x-1" />
                </a>
            </div>

            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($relatedBerita as $i => $rel)
                    {{-- Di tablet hanya 2 kolom, jadi kartu ketiga disembunyikan supaya tidak tersisa sendirian --}}
                    <x-landing.berita-item :item="$rel" read-fallback="4 Min Baca" cta="Baca Selengkapnya" :author="false" :class="$i === 2 ? 'sm:max-lg:hidden' : ''" />
                @endforeach
            </div>
        </div>
    </section>
@endif
@endsection
