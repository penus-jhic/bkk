{{--
    Daftar berita & agenda BKK. Gaya mengikuti landing page sekolah (coretan tangan, merah marun),
    data dari PublicController@berita: $categories, $totalPublished, $heroBerita, $beritas, $kategoriSlug, $searchQuery.
--}}
@php
    $navSection = 'berita';

    $activeCategory = $kategoriSlug ? ($categories->firstWhere('slug', $kategoriSlug)->nama ?? $kategoriSlug) : null;

    // tone = warna kotak tanggal, berurutan dari agenda paling dekat
    $agendas = [
        ['month' => 'Mei', 'day' => '24', 'title' => 'Penus Career Fair 2025', 'place' => 'Aula Serbaguna Kampus • 08:00 WIB', 'note' => '35 Perusahaan Partisipan', 'highlight' => true, 'tone' => 'bg-brand-darkred text-white'],
        ['month' => 'Mei', 'day' => '28', 'title' => 'Pelepasan Magang Gel. II', 'place' => 'Lapangan Utama • Siswa Kelas XI', 'note' => 'Wajib Seragam Wearpack', 'highlight' => false, 'tone' => 'bg-brand-signal/15 text-brand-darkred'],
        ['month' => 'Jun', 'day' => '05', 'title' => 'Walk-in PT Denso Indonesia', 'place' => 'Lab Mesin & Otomotif • 09:00 WIB', 'note' => 'Khusus Alumni 2024 & 2025', 'highlight' => true, 'tone' => 'bg-brand-softmist text-brand-ink'],
    ];

    $downloads = [
        ['title' => 'Pedoman Laporan PKL 2025', 'meta' => 'PDF • 2.4 MB • Versi Revisi', 'icon' => 'fileText'],
        ['title' => 'Format CV ATS Vokasi SMK', 'meta' => 'DOCX • 680 KB • Terstandarisasi', 'icon' => 'user'],
        ['title' => 'Form Penilaian Mitra IDUKA', 'meta' => 'PDF • 450 KB • Lampiran Penilaian', 'icon' => 'check'],
    ];
@endphp

@extends('index.layouts.landing')

@section('title', 'Berita & Agenda - BKK SMK Plus Pelita Nusantara')

@section('content')
{{-- ============================================================
     1. KEPALA HALAMAN: judul, pencarian & kategori
     ============================================================ --}}
<section class="relative overflow-hidden bg-white px-6 pt-32 pb-10 md:pt-40 md:pb-12">
    {{-- Latar: grid halus + titik-titik di kanan atas --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0">
        <div class="absolute inset-0 bg-[linear-gradient(to_right,rgb(36_16_18/0.04)_1px,transparent_1px),linear-gradient(to_bottom,rgb(36_16_18/0.04)_1px,transparent_1px)] bg-size-[44px_44px] mask-[radial-gradient(ellipse_60%_70%_at_20%_20%,#000_50%,transparent_100%)]"></div>
        <div class="absolute right-6 top-28 hidden md:block w-40 h-28 bg-[radial-gradient(circle,var(--color-brand-mist)_2px,transparent_2.5px)] bg-size-[22px_22px]"></div>
    </div>

    <div class="relative max-w-6xl mx-auto">
        <div class="animate-fade-up">
            <p class="flex flex-wrap items-center gap-x-10 gap-y-2 text-xs font-semibold uppercase tracking-[0.25em]">
                <span class="text-brand-darkred"><x-sketch.sparks>Warta &amp; Agenda Terkini</x-sketch.sparks></span>
                <span class="text-brand-ink/40">/ Portal Informasi Terpadu</span>
            </p>

            <div class="mt-6 flex flex-col gap-10 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    {{-- delay: coretan mulai setelah teks selesai muncul --}}
                    <h1 class="text-left font-display text-4xl sm:text-5xl lg:text-6xl font-bold uppercase tracking-wide leading-[1.05]">
                        Kabar &amp; Agenda Bursa Kerja
                        <x-sketch.underline size="lg" tone="text-brand-signal" :delay="500" class="text-brand-darkred">Khusus</x-sketch.underline>
                    </h1>
                    {{-- mt-10: ruang untuk coretan yang menggantung di bawah judul --}}
                    <p class="mt-10 text-base md:text-lg leading-relaxed text-brand-ink/70">
                        Informasi terkini mengenai bursa kerja, agenda walk-in interview, kunjungan industri, pembekalan magang (PKL), serta tips karier persiapan dunia kerja SMK Plus Pelita Nusantara.
                    </p>
                </div>

                <form method="GET" action="{{ route('bkk.berita') }}" role="search" class="relative w-full shrink-0 sm:w-80">
                    @if ($kategoriSlug)
                        <input type="hidden" name="kategori" value="{{ $kategoriSlug }}">
                    @endif
                    <label for="cari-berita" class="sr-only">Cari berita atau agenda</label>
                    <x-landing.icon name="search" class="pointer-events-none absolute left-4 top-1/2 w-5 h-5 -translate-y-1/2 text-brand-darkred" />
                    <input id="cari-berita" name="q" type="search" value="{{ $searchQuery }}" placeholder="Cari berita atau agenda..."
                        class="w-full rounded-full border-2 border-brand-ink/10 bg-white py-3 pl-12 pr-5 text-sm text-brand-ink shadow-softpill placeholder:text-brand-ink/40 transition focus:border-brand-darkred focus:outline-none focus:ring-4 focus:ring-brand-darkred/10">
                </form>
            </div>
        </div>

        {{-- Kategori: kategori aktif ditandai coretan bawah. py-3 memberi ruang coretannya, karena daftar ini
             overflow-x-auto (yang keluar dari kotaknya ikut terpotong) --}}
        <nav aria-label="Kategori berita" class="relative mt-12 md:mt-14">
            <ul class="-mx-6 flex gap-2 overflow-x-auto px-6 pb-3 [scrollbar-width:none] sm:mx-0 sm:px-0">
                @php
                    $tabs = collect([['label' => 'Semua Artikel', 'count' => $totalPublished, 'href' => route('bkk.berita', request()->only('q')), 'active' => empty($kategoriSlug)]])
                        ->concat($categories->map(fn ($cat) => [
                            'label' => $cat->nama,
                            'count' => $cat->beritas_count,
                            'href' => route('bkk.berita', array_merge(request()->only('q'), ['kategori' => $cat->slug])),
                            'active' => $kategoriSlug === $cat->slug,
                        ]));
                @endphp
                @foreach ($tabs as $tab)
                    <li class="shrink-0">
                        <a href="{{ $tab['href'] }}" @if ($tab['active']) aria-current="page" @endif
                            class="inline-flex items-center gap-2 rounded-full px-4 py-3 text-sm font-semibold transition-colors {{ $tab['active'] ? 'text-brand-darkred' : 'text-brand-ink/65 hover:bg-brand-ink/5 hover:text-brand-ink' }}">
                            @if ($tab['active'])
                                <x-sketch.underline size="sm">{{ $tab['label'] }}</x-sketch.underline>
                            @else
                                <span>{{ $tab['label'] }}</span>
                            @endif
                            <span class="min-w-6 rounded-full px-1.5 py-0.5 text-center text-[11px] font-bold {{ $tab['active'] ? 'bg-brand-darkred text-white' : 'bg-brand-ink/5 text-brand-ink/50' }}">{{ $tab['count'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <x-sketch.line class="left-0 right-0 -bottom-1.5 h-3 text-brand-ink/15" />
        </nav>
    </div>
</section>

{{-- ============================================================
     2. BERITA UTAMA, DAFTAR BERITA & SIDEBAR
     ============================================================ --}}
<section class="relative bg-brand-softmist px-6 py-16 md:py-24">
    <div class="max-w-6xl mx-auto">
        @if ($heroBerita)
            @php $heroUrl = route('bkk.berita.detail', $heroBerita->slug); @endphp
            {{-- Siku coretan di luar kartu, sama seperti foto di hero beranda --}}
            <article class="group relative mb-16 md:mb-20">
                <div class="relative grid overflow-hidden rounded-card bg-white shadow-softpill ring-1 ring-brand-ink/5 lg:grid-cols-[7fr_5fr]">
                    <a href="{{ $heroUrl }}" tabindex="-1" aria-hidden="true" class="relative block min-h-72 aspect-video overflow-hidden bg-linear-to-br from-brand-signal to-brand-deepred lg:aspect-auto">
                        <img src="{{ $heroBerita->gambar_sampul }}" alt="{{ $heroBerita->judul }}" class="absolute inset-0 size-full object-cover transition-transform duration-700 group-hover:scale-105">
                        <div class="absolute inset-0 bg-linear-to-t from-brand-ink/50 via-transparent to-transparent"></div>
                        <span class="absolute left-4 top-4 rounded-full bg-white/95 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.15em] text-brand-darkred shadow-sm">
                            {{ $heroBerita->kategori->nama ?? 'Bursa Kerja' }}
                        </span>
                    </a>

                    <div class="flex flex-col justify-between gap-8 p-7 md:p-10">
                        <div>
                            <p class="font-display text-3xl md:text-4xl font-bold uppercase tracking-wide leading-none text-brand-darkred">
                                <x-sketch.underline tone="text-brand-signal">Agenda Utama</x-sketch.underline>
                            </p>
                            <ul class="mt-9 flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-brand-ink/60">
                                <li class="flex items-center gap-2">
                                    <x-landing.icon name="calendar" class="w-4 h-4 text-brand-darkred" />
                                    {{ $heroBerita->formatted_date }}
                                </li>
                                <li class="flex items-center gap-2">
                                    <x-landing.icon name="clock" class="w-4 h-4 text-brand-darkred" />
                                    {{ $heroBerita->estimasi_baca ?? '4 Menit Baca' }}
                                </li>
                            </ul>
                            <h2 class="mt-4 text-left font-display text-2xl md:text-3xl font-bold uppercase tracking-wide leading-tight transition-colors group-hover:text-brand-darkred">
                                <a href="{{ $heroUrl }}">{{ $heroBerita->judul }}</a>
                            </h2>
                            <p class="mt-4 text-base leading-relaxed text-brand-ink/70 line-clamp-3">{{ $heroBerita->ringkasan }}</p>
                        </div>

                        <div class="flex flex-wrap items-center justify-between gap-5">
                            <div class="flex items-center gap-3">
                                <span class="flex w-11 h-11 shrink-0 items-center justify-center rounded-full bg-brand-darkred/10 font-display text-sm font-bold text-brand-darkred">
                                    {{ strtoupper(substr($heroBerita->penulis_nama, 0, 2)) }}
                                </span>
                                <span class="flex flex-col">
                                    <span class="text-sm font-semibold">{{ $heroBerita->penulis_nama }}</span>
                                    <span class="text-xs text-brand-ink/60">{{ $heroBerita->penulis_jabatan ?? 'Sekretariat Penus Cibinong' }}</span>
                                </span>
                            </div>
                            <a href="{{ $heroUrl }}" class="inline-flex items-center gap-3 rounded-full bg-linear-to-r from-brand-signal to-brand-darkred px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-darkred/25 transition-transform hover:-translate-y-0.5">
                                Baca Selengkapnya
                                <x-sketch.arrow :delay="400" class="w-7 h-3.5 transition-transform group-hover:translate-x-1" />
                            </a>
                        </div>
                    </div>
                </div>
                <x-sketch.corner class="-left-4 -top-4 w-24 h-10 md:w-32 md:h-12" />
                <x-sketch.corner :delay="350" class="-right-4 -bottom-4 rotate-180 w-24 h-10 md:w-32 md:h-12" />
            </article>
        @endif

        <div class="grid items-start gap-14 lg:grid-cols-[1fr_20rem] xl:gap-16">
            {{-- Daftar berita --}}
            <div class="min-w-0">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <h2 class="text-left font-display text-2xl md:text-3xl font-bold uppercase tracking-wide leading-tight">
                        @if ($kategoriSlug)
                            Kategori: <x-sketch.underline>{{ $activeCategory }}</x-sketch.underline>
                        @elseif ($searchQuery)
                            Hasil Pencarian: <x-sketch.underline>"{{ $searchQuery }}"</x-sketch.underline>
                        @else
                            Semua Publikasi <x-sketch.underline>Terkini</x-sketch.underline>
                        @endif
                    </h2>
                    <p class="shrink-0 text-xs font-semibold uppercase tracking-[0.2em] text-brand-ink/50">
                        Menampilkan {{ $beritas->firstItem() ?? 0 }} - {{ $beritas->lastItem() ?? 0 }} dari {{ $beritas->total() }} Kabar
                    </p>
                </div>

                <div class="mt-10 grid gap-6 sm:grid-cols-2">
                    @forelse ($beritas as $item)
                        <x-landing.berita-item :item="$item" />
                    @empty
                        <div class="relative flex flex-col items-center gap-3 rounded-card border-2 border-dashed border-brand-ink/15 bg-white px-8 py-14 text-center sm:col-span-2">
                            <span class="flex w-16 h-16 items-center justify-center rounded-full bg-brand-darkred/10 text-brand-darkred">
                                <x-landing.icon name="newspaper" class="w-8 h-8" />
                            </span>
                            <h3 class="mt-2 font-display text-xl font-bold uppercase tracking-wide">Belum Ada Artikel</h3>
                            <p class="max-w-md text-sm leading-relaxed text-brand-ink/65">
                                Belum ditemukan publikasi berita yang sesuai dengan kategori atau kata kunci pencarian yang dipilih.
                            </p>
                            <a href="{{ route('bkk.berita') }}" class="group mt-3 inline-flex items-center gap-3 rounded-full bg-linear-to-r from-brand-signal to-brand-darkred px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-darkred/25 transition-transform hover:-translate-y-0.5">
                                Lihat Semua Artikel
                                <x-sketch.arrow class="w-7 h-3.5 transition-transform group-hover:translate-x-1" />
                            </a>
                        </div>
                    @endforelse
                </div>

                @if ($beritas->hasPages())
                    @php $arrowClass = 'shrink-0 w-11 h-11 rounded-lg flex items-center justify-center text-white transition-colors'; @endphp
                    <nav aria-label="Navigasi halaman berita" class="mt-12 flex flex-col-reverse items-center gap-5 sm:flex-row sm:justify-between">
                        <p class="text-sm text-brand-ink/60">
                            Halaman <strong class="text-brand-ink">{{ $beritas->currentPage() }}</strong> dari <strong class="text-brand-ink">{{ $beritas->lastPage() }}</strong> (Total {{ $beritas->total() }} Catatan Berita)
                        </p>
                        <div class="flex items-center gap-2">
                            @if ($beritas->onFirstPage())
                                <span aria-hidden="true" class="{{ $arrowClass }} bg-brand-darkred/40 cursor-not-allowed">
                                    <x-sketch.arrow class="w-6 h-3 -scale-x-100" />
                                </span>
                            @else
                                <a href="{{ $beritas->previousPageUrl() }}" rel="prev" aria-label="Halaman sebelumnya" class="{{ $arrowClass }} bg-brand-darkred shadow-lg shadow-brand-darkred/25 hover:bg-brand-deepred">
                                    <x-sketch.arrow class="w-6 h-3 -scale-x-100" />
                                </a>
                            @endif

                            <ul class="flex flex-wrap items-center justify-center gap-1">
                                @foreach ($beritas->getUrlRange(1, $beritas->lastPage()) as $page => $url)
                                    <li>
                                        <a href="{{ $url }}" aria-label="Halaman {{ $page }}" @if ($page == $beritas->currentPage()) aria-current="page" @endif
                                            class="flex min-w-9 h-9 items-center justify-center rounded-full px-2.5 text-sm font-semibold transition-colors {{ $page == $beritas->currentPage() ? 'bg-brand-darkred text-white' : 'text-brand-ink/60 hover:bg-white hover:text-brand-ink' }}">{{ $page }}</a>
                                    </li>
                                @endforeach
                            </ul>

                            @if ($beritas->hasMorePages())
                                <a href="{{ $beritas->nextPageUrl() }}" rel="next" aria-label="Halaman berikutnya" class="{{ $arrowClass }} bg-brand-darkred shadow-lg shadow-brand-darkred/25 hover:bg-brand-deepred">
                                    <x-sketch.arrow class="w-6 h-3" />
                                </a>
                            @else
                                <span aria-hidden="true" class="{{ $arrowClass }} bg-brand-darkred/40 cursor-not-allowed">
                                    <x-sketch.arrow class="w-6 h-3" />
                                </span>
                            @endif
                        </div>
                    </nav>
                @endif
            </div>

            {{-- Sidebar --}}
            <aside class="flex flex-col gap-10">
                {{-- Kalender agenda: bingkai coretan penuh, pemisah antar agenda juga coretan --}}
                <div class="relative bg-white p-6">
                    <x-sketch.box />
                    <div class="flex items-start justify-between gap-3">
                        <h3 class="flex items-center gap-2 font-display text-lg font-bold uppercase tracking-wide">
                            <x-landing.icon name="calendar" class="w-5 h-5 text-brand-darkred" />
                            Kalender Agenda BKK
                        </h3>
                        <span class="shrink-0 rounded-full bg-brand-softmist px-2.5 py-1 text-[11px] font-semibold text-brand-ink/60">Mei - Juni 2025</span>
                    </div>

                    <ul class="mt-5">
                        @foreach ($agendas as $i => $agenda)
                            <li class="relative flex items-start gap-3.5 py-4 first:pt-0 last:pb-0">
                                @if ($i > 0)
                                    <x-sketch.rule :delay="$i * 100" class="text-brand-darkred/30 -left-1 -right-1 -top-1.5 h-3" />
                                @endif
                                <span class="flex w-12 h-12 shrink-0 flex-col items-center justify-center rounded-lg {{ $agenda['tone'] }}">
                                    <span class="text-[10px] font-semibold uppercase tracking-wide opacity-80">{{ $agenda['month'] }}</span>
                                    <span class="font-display text-lg font-bold leading-none">{{ $agenda['day'] }}</span>
                                </span>
                                <span class="flex min-w-0 flex-col">
                                    <span class="truncate text-sm font-semibold">{{ $agenda['title'] }}</span>
                                    <span class="text-xs text-brand-ink/60">{{ $agenda['place'] }}</span>
                                    <span class="mt-0.5 text-xs font-semibold {{ $agenda['highlight'] ? 'text-brand-darkred' : 'text-brand-ink/50' }}">{{ $agenda['note'] }}</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>

                    <a href="#" class="group mt-6 flex items-center justify-center gap-2 rounded-full border-2 border-brand-darkred/20 px-4 py-2.5 text-center text-sm font-semibold text-brand-darkred transition-colors hover:border-brand-darkred hover:bg-brand-darkred/5">
                        Lihat Jadwal Lengkap Semester Ini
                    </a>
                </div>

                {{-- Unduhan berkas --}}
                <div class="rounded-card bg-white p-6 ring-1 ring-brand-ink/10">
                    <h3 class="flex items-center gap-2 font-display text-lg font-bold uppercase tracking-wide">
                        <x-landing.icon name="download" class="w-5 h-5 text-brand-darkred" />
                        Pusat Unduhan Siswa
                    </h3>
                    <p class="mt-2 text-sm leading-relaxed text-brand-ink/65">
                        Unduh format resmi berkas administratif prakerin magang dan template portofolio standar industri.
                    </p>
                    <ul class="mt-4 flex flex-col gap-2">
                        @foreach ($downloads as $download)
                            <li>
                                <a href="#" class="group flex items-center justify-between gap-3 rounded-xl bg-brand-softmist/60 p-3 transition-colors hover:bg-brand-darkred/5">
                                    <span class="flex min-w-0 items-center gap-3">
                                        <span class="flex w-9 h-9 shrink-0 items-center justify-center rounded-lg bg-white text-brand-darkred shadow-sm">
                                            <x-landing.icon :name="$download['icon']" class="w-4.5 h-4.5" />
                                        </span>
                                        <span class="flex min-w-0 flex-col">
                                            <span class="truncate text-sm font-semibold transition-colors group-hover:text-brand-darkred">{{ $download['title'] }}</span>
                                            <span class="text-xs text-brand-ink/55">{{ $download['meta'] }}</span>
                                        </span>
                                    </span>
                                    <x-landing.icon name="download" class="w-5 h-5 shrink-0 text-brand-ink/40 transition-colors group-hover:text-brand-darkred" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                {{-- Langganan info karier, warna & coretan sama dengan banner ajakan di footer --}}
                <div class="relative overflow-hidden rounded-card bg-linear-135 from-brand-darkred to-brand-deepred p-6 text-white shadow-softpill">
                    <div aria-hidden="true" class="pointer-events-none absolute -right-10 -bottom-10 w-36 h-36 rounded-full bg-white/10 blur-2xl"></div>
                    <p class="relative flex items-center gap-2 text-[11px] font-bold uppercase tracking-[0.25em] text-[#F5C2C7]">
                        <x-landing.icon name="megaphone" class="w-4 h-4" />
                        <x-sketch.sparks tone="text-[#F5C2C7]">Langganan Info Karir</x-sketch.sparks>
                    </p>
                    <h3 class="relative mt-4 text-left font-display text-xl font-bold uppercase tracking-wide leading-snug">
                        Dapatkan Info Lowongan &amp; Walk-in Wawancara Langsung di Ponselmu
                    </h3>
                    <p class="relative mt-3 text-sm leading-relaxed text-white/85">
                        Bergabung dengan 1.200+ siswa dan alumni dalam siaran kabar eksklusif BKK Penus setiap Jumat pagi.
                    </p>
                    <form class="relative mt-5 flex flex-col gap-2.5" onsubmit="event.preventDefault(); alert('Terima kasih! Anda telah terdaftar dalam sistem broadcast BKK Penus.');">
                        <label class="sr-only" for="langganan-nama">Nama Lengkap Siswa / Alumni</label>
                        <input id="langganan-nama" type="text" required placeholder="Nama Lengkap Siswa / Alumni" class="w-full rounded-full bg-white px-4 py-2.5 text-sm text-brand-ink placeholder:text-brand-ink/45 focus:outline-none focus:ring-4 focus:ring-white/30">
                        <label class="sr-only" for="langganan-wa">No. WhatsApp Aktif</label>
                        <input id="langganan-wa" type="tel" required placeholder="No. WhatsApp Aktif (08xx)" class="w-full rounded-full bg-white px-4 py-2.5 text-sm text-brand-ink placeholder:text-brand-ink/45 focus:outline-none focus:ring-4 focus:ring-white/30">
                        <button type="submit" class="mt-1 inline-flex w-full items-center justify-center gap-2 rounded-full border-2 border-white/30 py-2.5 text-sm font-semibold text-white transition-all hover:border-white hover:bg-white/10 active:scale-95">
                            <x-landing.icon name="send" class="w-4 h-4" />
                            Daftar Notifikasi WhatsApp
                        </button>
                    </form>
                    <p class="relative mt-4 flex items-center justify-center gap-1.5 text-[11px] text-white/70">
                        <x-landing.icon name="lock" class="w-3.5 h-3.5" />
                        Data privat terproteksi &amp; bebas spam iklan luar.
                    </p>
                </div>
            </aside>
        </div>
    </div>
</section>
@endsection
