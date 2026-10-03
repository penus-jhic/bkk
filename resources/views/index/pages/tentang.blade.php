{{--
    Profil BKK SMK Plus Pelita Nusantara. Gaya mengikuti landing page sekolah (coretan tangan, merah marun).
--}}
@php
    $navSection = ''; // tidak ada section beranda yang ditandai aktif

    $missions = [
        'Menjembatani komunikasi intensif antara siswa/alumni dengan institusi dunia usaha dan dunia industri (DUDI).',
        'Menyelenggarakan pembekalan etika kerja, pembuatan CV standar ATS, dan uji kompetensi berkala.',
        'Melakukan penelusuran tamatan (tracer study) terpadu untuk evaluasi kurikulum yang relevan dengan kebutuhan industri.',
    ];

    $services = [
        ['icon' => 'briefcase', 'title' => 'Penyaluran PKL Terpadu', 'desc' => 'Penempatan siswa kelas XI dan XII di ratusan perusahaan rekanan resmi dengan sistem administrasi jurnal digital dan monitoring teratur.'],
        ['icon' => 'users', 'title' => 'Walk-in Rekrutmen Kampus', 'desc' => 'Penyelenggaraan seleksi wawancara dan tes psikotes langsung di lingkungan sekolah bersama HRD perusahaan mitra.'],
        ['icon' => 'school', 'title' => 'Konseling Karier & ATS', 'desc' => 'Bimbingan karier 1-on-1 bersama guru BK dan konselor BKK untuk mematangkan kesiapan wawancara dan portofolio profesional siswa.'],
    ];

    // photo = foto pengurus; selama belum ada, ditampilkan ikon perannya
    $team = [
        ['name' => 'Dra. Hj. Sri Wahyuni, M.Pd.', 'role' => 'Ketua Bursa Kerja Khusus', 'icon' => 'award', 'lead' => true, 'photo' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuB63mLO-BnMgFFgqx1mTuwPFGCzTmwLyy8_J4aLTzOGznPDmlW9ogUP_BfzTLkJ0xlAceEAOeRFi6oS_ZIKOEISZaexn-6scwvS0zs0dbPRAtJzIBlUYU2AMsnkYW-A-8Jh1zw-wQ4iRtEhhvoE1Ukg7i6-1XSC3KrQ8MOWP1Mp2FIYqYepTfVt-76KoTaeWJF2gXKoGd38EgQEhJhlThO8F6cMObBxynYkKpZ54dFAgJrgWayRbAbo'],
        ['name' => 'Ahmad Fauzi, S.Kom.', 'role' => 'Koordinator Hubungan Industri (Hubin)', 'icon' => 'handshake', 'lead' => false, 'photo' => null],
        ['name' => 'Rina Marlina, S.Psi.', 'role' => 'Konselor Karier & Psikotes', 'icon' => 'bulb', 'lead' => false, 'photo' => null],
        ['name' => 'Budi Santoso, S.T.', 'role' => 'Admin Tracer Study & Data PKL', 'icon' => 'chart', 'lead' => false, 'photo' => null],
    ];
@endphp

@extends('index.layouts.landing')

@section('title', 'Tentang BKK - SMK Plus Pelita Nusantara')

@section('content')
{{-- ============================================================
     1. KEPALA HALAMAN
     ============================================================ --}}
<section class="relative overflow-hidden bg-white px-6 pt-32 pb-16 md:pt-40 md:pb-24">
    <div aria-hidden="true" class="pointer-events-none absolute inset-0">
        <div class="absolute inset-0 bg-[linear-gradient(to_right,rgb(36_16_18/0.04)_1px,transparent_1px),linear-gradient(to_bottom,rgb(36_16_18/0.04)_1px,transparent_1px)] bg-size-[44px_44px] mask-[radial-gradient(ellipse_60%_70%_at_20%_20%,#000_50%,transparent_100%)]"></div>
        <div class="absolute -top-40 -right-32 w-140 h-140 rounded-full bg-brand-signal/10 blur-[120px]"></div>
    </div>

    <div class="relative max-w-6xl mx-auto grid gap-14 lg:grid-cols-[1.2fr_1fr] lg:items-center">
        <div class="animate-fade-up">
            <p class="flex flex-wrap items-center gap-x-10 gap-y-2 text-xs font-semibold uppercase tracking-[0.25em]">
                <span class="text-brand-darkred"><x-sketch.sparks>Profil Lembaga</x-sketch.sparks></span>
                <span class="text-brand-ink/40">/ Bursa Kerja Khusus</span>
            </p>
            <h1 class="mt-6 text-left font-display text-4xl sm:text-5xl lg:text-6xl font-bold uppercase tracking-wide leading-[1.05]">
                Mengenal BKK
                <span class="block mt-2 text-brand-darkred">SMK Plus Pelita <x-sketch.underline size="lg" tone="text-brand-signal" :delay="500">Nusantara</x-sketch.underline></span>
            </h1>
            <p class="mt-10 max-w-xl text-base md:text-lg leading-relaxed text-brand-ink/70">
                Lembaga resmi di bawah naungan SMK Plus Pelita Nusantara yang berdedikasi mengoptimalkan penyerapan lulusan ke dunia kerja, fasilitasi praktik kerja lapangan (PKL), dan kemitraan strategis dengan IDUKA nasional dan multinasional.
            </p>
        </div>

        <div class="relative">
            <div class="relative overflow-hidden rounded-card shadow-softpill ring-1 ring-brand-ink/5">
                <img src="{{ asset('images/fotogedung.jpg') }}" alt="Gedung SMK Plus Pelita Nusantara di Cibinong, Bogor" class="w-full aspect-4/3 object-cover">
                <div class="absolute inset-0 bg-linear-to-t from-brand-ink/80 via-brand-ink/10 to-transparent"></div>
                <p class="absolute inset-x-5 bottom-5 font-display text-2xl font-bold uppercase tracking-wide text-white">We Are Different</p>
            </div>
            <x-sketch.corner class="-left-4 -top-4 w-24 h-10 md:w-32 md:h-12" />
            <x-sketch.corner :delay="350" class="-right-4 -bottom-4 rotate-180 w-24 h-10 md:w-32 md:h-12" />
        </div>
    </div>
</section>

{{-- ============================================================
     2. VISI & MISI
     ============================================================ --}}
<section class="relative bg-brand-softmist px-6 py-20 md:py-28">
    <div class="max-w-6xl mx-auto grid gap-10 md:grid-cols-2 md:gap-8 items-stretch">
        {{-- Visi: kartu merah, kutipan besar --}}
        <div class="relative flex flex-col justify-between overflow-hidden rounded-card bg-linear-135 from-brand-darkred to-brand-deepred p-8 md:p-10 text-white shadow-softpill">
            <div aria-hidden="true" class="pointer-events-none absolute -right-10 -bottom-10 w-44 h-44 rounded-full bg-white/10 blur-2xl"></div>
            <div class="relative">
                <p class="text-xs font-bold uppercase tracking-[0.25em] text-[#F5C2C7]">
                    <x-sketch.sparks tone="text-[#F5C2C7]">Visi BKK</x-sketch.sparks>
                </p>
                <p class="mt-6 text-left font-display text-2xl md:text-3xl font-bold uppercase tracking-wide leading-snug">
                    Menjadi unit Bursa Kerja Khusus vokasi yang terpercaya, adaptif terhadap perkembangan revolusi industri 4.0, dan unggul dalam mencetak tenaga kerja muda yang profesional, berkarakter, serta berdaya saing global.
                </p>
            </div>
            <p class="relative mt-10 flex items-center gap-2 border-t border-white/15 pt-5 text-sm font-semibold text-white/85">
                <x-landing.icon name="badgeCheck" class="w-5 h-5 text-[#F5C2C7]" />
                Terakreditasi dan Tersinkronisasi Disnaker
            </p>
        </div>

        {{-- Misi: bingkai coretan penuh, poin bertanda panah coretan --}}
        <div class="relative bg-white p-8 md:p-10">
            <x-sketch.box />
            <h2 class="flex items-center gap-3 font-display text-2xl md:text-3xl font-bold uppercase tracking-wide">
                <x-landing.icon name="flag" class="w-7 h-7 text-brand-darkred" />
                Misi BKK
            </h2>
            <ul class="mt-8 space-y-5">
                @foreach ($missions as $i => $mission)
                    <li class="flex gap-4">
                        <span class="mt-1.5 shrink-0 text-brand-darkred">
                            <x-sketch.arrow :delay="$i * 200" class="w-8 h-4" />
                        </span>
                        <span class="leading-relaxed text-brand-ink/80">{{ $mission }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</section>

{{-- ============================================================
     3. LAYANAN PRIORITAS
     ============================================================ --}}
<section class="relative bg-white px-6 py-20 md:py-28">
    <div class="max-w-6xl mx-auto">
        <div class="max-w-2xl">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-brand-darkred">Layanan &amp; Program</p>
            <h2 class="mt-4 text-left font-display text-3xl md:text-4xl font-bold uppercase tracking-wide leading-tight">
                Layanan Prioritas <x-sketch.underline>BKK Penus</x-sketch.underline>
            </h2>
            <p class="mt-6 text-base md:text-lg leading-relaxed text-brand-ink/70">
                Fasilitas terpadu untuk menunjang transisi siswa dari bangku sekolah menuju dunia kerja profesional.
            </p>
        </div>

        <div class="mt-14 grid gap-6 md:grid-cols-3">
            @foreach ($services as $i => $service)
                <div class="group relative flex flex-col rounded-card bg-brand-softmist p-7 transition-all duration-300 hover:-translate-y-1 hover:bg-white hover:shadow-softpill hover:ring-1 hover:ring-brand-darkred/15">
                    <div class="flex items-start justify-between">
                        <span class="flex w-14 h-14 items-center justify-center rounded-2xl bg-white text-brand-darkred shadow-sm transition-colors group-hover:bg-brand-darkred group-hover:text-white">
                            <x-landing.icon :name="$service['icon']" class="w-7 h-7" />
                        </span>
                        <span class="font-display text-4xl font-bold text-brand-ink/10">{{ sprintf('%02d', $i + 1) }}</span>
                    </div>
                    <h3 class="mt-6 text-left font-display text-xl font-bold uppercase tracking-wide">{{ $service['title'] }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-brand-ink/70">{{ $service['desc'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- ============================================================
     4. PENGURUS BKK
     ============================================================ --}}
<section class="relative bg-brand-softmist px-6 py-20 md:py-28">
    <div class="max-w-6xl mx-auto">
        <div class="mx-auto max-w-xl text-center">
            <p class="text-xs font-semibold uppercase tracking-[0.25em] text-brand-darkred">Struktur Lembaga</p>
            <h2 class="mt-4 font-display text-3xl md:text-4xl font-bold uppercase tracking-wide leading-tight">
                <x-sketch.frame>Pengurus BKK SMK Penus</x-sketch.frame>
            </h2>
            <p class="mt-6 text-base md:text-lg leading-relaxed text-brand-ink/70">
                Dedikasi para pendidik dan praktisi hubungan industri demi masa depan gemilang lulusan vokasi.
            </p>
        </div>

        <ul class="mt-14 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($team as $member)
                <li class="relative flex flex-col items-center rounded-card bg-white px-6 pt-8 pb-7 text-center shadow-sm ring-1 ring-brand-ink/5">
                    <div class="relative">
                        @if ($member['photo'])
                            <img src="{{ $member['photo'] }}" alt="{{ $member['name'] }}" class="w-24 h-24 rounded-full object-cover ring-4 ring-brand-darkred/10">
                        @else
                            <span class="flex w-24 h-24 items-center justify-center rounded-full bg-linear-to-br from-brand-signal to-brand-deepred text-white ring-4 ring-brand-darkred/10">
                                <x-landing.icon :name="$member['icon']" class="w-10 h-10" />
                            </span>
                        @endif
                        @if ($member['lead'])
                            {{-- Ketua ditandai lingkaran coretan --}}
                            <span data-sketch="circle" data-duration="900" aria-hidden="true" class="pointer-events-none absolute -inset-3 text-brand-darkred"></span>
                        @endif
                    </div>
                    <p class="mt-6 font-semibold leading-snug">{{ $member['name'] }}</p>
                    <p class="mt-1 text-xs font-semibold uppercase tracking-[0.15em] {{ $member['lead'] ? 'text-brand-darkred' : 'text-brand-ink/50' }}">{{ $member['role'] }}</p>
                </li>
            @endforeach
        </ul>
    </div>
</section>
@endsection
