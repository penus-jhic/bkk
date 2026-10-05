@extends('admin.master')

@section('title', 'Dashboard Admin BKK - SMK Plus Pelita Nusantara')

@php
    $role = strtoupper($authUser['role'] ?? 'ADMIN');
    $name = $authUser['nama_lengkap'] ?? $authUser['username'] ?? 'Administrator';
    $username = $authUser['username'] ?? 'admin';
    $email = $authUser['email'] ?? 'admin@smkpenus.sch.id';
    $nip = $authUser['nomor_induk'] ?? 'ADM-2026-001';
    $initials = strtoupper(substr($name, 0, 2));

    $totalBerita = $stats['total_berita'] ?? 0;
    $totalPublished = $stats['total_published'] ?? 0;
    $totalDraft = $stats['total_draft'] ?? 0;
    $totalViews = $stats['total_views'] ?? 0;
    $totalKategori = $stats['total_kategori'] ?? 0;

    $publishRate = $totalBerita > 0 ? (int) round(($totalPublished / $totalBerita) * 100) : 100;

    $hour = (int) date('H');
    $greet = ($hour < 11) ? 'Selamat pagi' : (($hour < 15) ? 'Selamat siang' : (($hour < 18) ? 'Selamat sore' : 'Selamat malam'));

    $recentBeritas = $recentBeritas ?? collect();
    $kategoriList = $kategoriList ?? collect();
    $recentLowongans = $recentLowongans ?? collect();
    $recentMitras = $recentMitras ?? collect();
    $recentPkl = $recentPkl ?? collect();
    $recentTracer = $recentTracer ?? collect();
@endphp

@section('content')
<div class="space-y-6 fade-up">
    <!-- Greeting Banner (Mirroring resources/views/me) -->
    <div class="p-6 sm:p-8 bg-white border border-line rounded-3xl relative overflow-hidden shadow-sm">
        <div class="absolute right-0 top-0 h-full w-1/2 pointer-events-none hidden md:block">
            <div class="absolute right-10 top-6 w-40 h-40 rounded-full bg-navy/5"></div>
            <div class="absolute right-40 bottom-[-40px] w-32 h-32 rounded-full bg-maroon/10"></div>
            <div class="absolute right-16 bottom-8 w-14 h-14 rounded-2xl bg-line/40 rotate-12"></div>
        </div>

        <div class="relative flex flex-col md:flex-row md:items-center gap-6">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                    <span class="rounded-full px-3 py-1 text-xs font-semibold bg-maroon text-white">
                        Pusat Kendali Admin
                    </span>
                    <span class="text-xs text-muted font-medium">BKK SMK Plus Pelita Nusantara</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold text-navy tracking-tight">
                    Halo, {{ $name }} 👋
                </h1>
                <p class="text-muted mt-1.5 text-sm sm:text-base leading-relaxed">
                    {{ $greet }}!
                    @if($totalDraft > 0)
                        Anda memiliki <b class="text-maroon">{{ $totalDraft }} draf berita</b> yang menunggu peninjauan dan publikasi ke web publik.
                    @else
                        Seluruh publikasi berita, agenda rekrutmen, dan artikel karier siswa berjalan lancar.
                    @endif
                </p>
            </div>

            <!-- Right Widget: Rasio Publikasi -->
            <div class="md:w-80 bg-[#f8f9fa] rounded-2xl border border-line p-4">
                <div class="flex justify-between text-sm mb-2 font-medium">
                    <span class="text-navy">Rasio Publikasi</span>
                    <span class="font-bold text-navy">{{ $publishRate }}% ({{ $totalPublished }}/{{ $totalBerita }})</span>
                </div>
                <div class="h-2 rounded-full bg-line/60 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-700 {{ $publishRate >= 80 ? 'bg-navy' : 'bg-maroon' }}" style="width: {{ $publishRate }}%"></div>
                </div>
                <a href="{{ route('bkk.admin.berita.create') }}" class="text-xs text-maroon font-semibold mt-3 inline-flex items-center gap-1 hover:underline">
                    <span>Tulis berita baru sekarang</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- 4 Metric Cards (Matching resources/views/me) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <!-- Metric 1: Total Artikel -->
        <div class="p-5 bg-white border border-line rounded-2xl hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-sm text-muted font-medium">Total Artikel</span>
                <div class="w-9 h-9 rounded-full bg-navy/8 grid place-items-center text-navy">
                    <i data-lucide="newspaper" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-3xl font-bold text-navy mt-3">{{ number_format($totalBerita) }}</div>
            <div class="flex items-center gap-1 text-xs mt-1 text-emerald-700 font-medium">
                <i data-lucide="trending-up" class="w-3.5 h-3.5"></i>
                <span>+{{ $recentBeritas->count() }} artikel terbaru</span>
            </div>
            <div class="flex items-end gap-1 h-8 mt-3">
                @foreach([30, 45, 20, 60, 50, 75, 95] as $bar)
                    <div class="flex-1 rounded-sm bg-navy" style="height: {{ $bar }}%; opacity: {{ 0.3 + ($loop->index * 0.1) }};"></div>
                @endforeach
            </div>
        </div>

        <!-- Metric 2: Berita Terbit -->
        <div class="p-5 bg-gradient-to-br from-white to-emerald-50/50 border border-emerald-200/80 rounded-2xl hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-sm text-emerald-800 font-semibold">Berita Terbit</span>
                <div class="w-9 h-9 rounded-full bg-emerald-600 grid place-items-center text-white">
                    <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-3xl font-bold text-emerald-900 mt-3">{{ number_format($totalPublished) }}</div>
            <div class="text-xs mt-1 text-muted">
                Tayang di web publik BKK
            </div>
            <div class="mt-4 flex items-center gap-2">
                <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold bg-emerald-100 text-emerald-800">
                    {{ $publishRate }}% dari total
                </span>
                <span class="text-[11px] text-muted">{{ $totalKategori }} kategori aktif</span>
            </div>
        </div>

        <!-- Metric 3: Total Pembaca (Views) -->
        <div class="p-5 bg-white gemini-border rounded-2xl hover:shadow-md transition-shadow relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-sm text-muted font-medium">Total Pembaca</span>
                <i data-lucide="sparkles" class="w-5 h-5 text-maroon"></i>
            </div>
            <div class="text-3xl font-bold text-navy mt-3">
                {{ number_format($totalViews) }}<span class="text-base text-muted font-normal"> views</span>
            </div>
            <div class="h-2 rounded-full bg-line/60 overflow-hidden mt-2">
                <div class="h-full rounded-full transition-all duration-700" style="width: {{ min(100, max(15, (int)($totalViews > 0 ? log($totalViews) * 12 : 15))) }}%; background: linear-gradient(90deg, #741918, #1b283b)"></div>
            </div>
            <a href="{{ url('/bkk/berita') }}" target="_blank" class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-maroon hover:underline">
                <i data-lucide="external-link" class="w-3.5 h-3.5"></i>
                <span>Lihat di katalog publik</span>
            </a>
        </div>

        <!-- Metric 4: Draf Menunggu -->
        <div class="p-5 bg-gradient-to-br from-white to-amber-50/50 border border-amber-200/80 rounded-2xl hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-sm text-amber-800 font-semibold">Draf / Menunggu</span>
                <div class="w-9 h-9 rounded-full bg-amber-600 grid place-items-center text-white">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-3xl font-bold text-amber-900 mt-3">{{ number_format($totalDraft) }}</div>
            <div class="flex items-center gap-1 text-xs mt-1 text-amber-800 font-medium">
                <i data-lucide="file-edit" class="w-3.5 h-3.5"></i>
                <span>Belum dipublikasikan</span>
            </div>
            <div class="flex items-end gap-1 h-8 mt-3">
                @foreach([25, 40, 55, 35, 60, 70, 85] as $bar)
                    <div class="flex-1 rounded-sm bg-amber-600" style="height: {{ $bar }}%; opacity: {{ 0.3 + ($loop->index * 0.1) }};"></div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Main Section: Publikasi Terkini + Sidebar Profil Admin (Grid 5 Cols) -->
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">
        <!-- Left: Publikasi Berita Terkini (3 cols) -->
        <div class="xl:col-span-3 space-y-4">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-navy">Publikasi Berita Terkini</h2>
                    <p class="text-xs text-muted">Daftar artikel warta dan agenda terbaru dalam sistem BKK</p>
                </div>
                <a href="{{ route('bkk.admin.berita.index') }}" class="text-xs font-semibold text-maroon inline-flex items-center gap-1 hover:underline">
                    <span>Lihat semua</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>

            <div class="space-y-3">
                @forelse($recentBeritas as $b)
                    @php
                        $isPublished = ($b->status === 'PUBLISHED');
                        $statusBadgeClass = $isPublished 
                            ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' 
                            : 'bg-amber-100 text-amber-800 border border-amber-200';
                        $catName = $b->kategori->nama ?? 'Umum';
                        $initial = strtoupper(substr($b->judul, 0, 2));
                    @endphp
                    <div class="p-4 bg-white border border-line rounded-2xl hover:shadow-md transition-shadow cursor-pointer" onclick='openDetailModal(@json($b))'>
                        <div class="flex items-start gap-3.5">
                            @if($b->gambar_sampul)
                                <img src="{{ $b->gambar_sampul }}" alt="{{ $b->judul }}" class="w-12 h-12 rounded-xl object-cover shrink-0 shadow-sm border border-line/60"/>
                            @else
                                <div class="w-12 h-12 rounded-xl bg-navy text-white font-bold text-xs grid place-items-center shrink-0 shadow-sm">
                                    {{ $initial }}
                                </div>
                            @endif

                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <h3 class="font-semibold text-sm text-navy truncate hover:text-maroon transition-colors">
                                            {{ $b->judul }}
                                        </h3>
                                        <div class="text-xs text-muted mt-0.5">
                                            {{ $b->penulis_nama }} · {{ $b->formatted_date }}
                                        </div>
                                    </div>
                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $statusBadgeClass }}">
                                        {{ $isPublished ? 'Terbit' : 'Draf' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Step Pipeline Progress Bar (Styled like resources/views/me) -->
                        <div class="mt-4 pt-3 border-t border-line/60">
                            @php
                                $steps = ['Draf Dibuat', 'Peninjauan Konten', 'Publikasi Live'];
                                $currentStep = $isPublished ? 3 : 2;
                            @endphp
                            <div class="flex items-center w-full">
                                @foreach($steps as $idx => $st)
                                    @php
                                        $isDone = ($idx < $currentStep);
                                        $isLast = ($idx === count($steps) - 1);
                                    @endphp
                                    <div class="flex items-center {{ !$isLast ? 'flex-1' : '' }}">
                                        <div class="flex flex-col items-center gap-1">
                                            <div class="w-5 h-5 rounded-full grid place-items-center text-[9px] font-bold border {{ $isDone ? 'bg-navy border-navy text-white' : 'bg-white border-line text-muted' }}">
                                                @if($isDone)
                                                    <i data-lucide="check" class="w-3 h-3"></i>
                                                @else
                                                    {{ $idx + 1 }}
                                                @endif
                                            </div>
                                            <span class="text-[10px] {{ $isDone ? 'text-navy font-semibold' : 'text-muted' }}">{{ $st }}</span>
                                        </div>
                                        @if(!$isLast)
                                            <div class="flex-1 h-0.5 mx-1 -mt-4 {{ $idx < $currentStep - 1 ? 'bg-navy' : 'bg-line' }}"></div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Meta Pill Badges & Quick Action -->
                        <div class="mt-3 pt-2.5 border-t border-line/40 flex flex-wrap items-center justify-between gap-2 text-xs">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded-full px-2.5 py-0.5 text-[11px] font-medium bg-[#f1f3f4] text-navy">
                                    {{ $catName }}
                                </span>
                                <span class="text-muted text-[11px] inline-flex items-center gap-1">
                                    <i data-lucide="eye" class="w-3 h-3 text-muted"></i>
                                    <span>{{ number_format($b->views_count) }} views</span>
                                </span>
                                <span class="text-muted text-[11px] inline-flex items-center gap-1">
                                    <i data-lucide="clock" class="w-3 h-3 text-muted"></i>
                                    <span>{{ $b->estimasi_baca ?? '2 Menit' }}</span>
                                </span>
                            </div>

                            <div class="flex items-center gap-2" onclick="event.stopPropagation()">
                                <a href="{{ route('bkk.berita.detail', $b->slug) }}" target="_blank" class="h-7 px-2.5 rounded-full bg-canvas text-navy text-xs font-semibold hover:bg-line/60 inline-flex items-center gap-1 transition-colors border border-line" title="Lihat di Web">
                                    <i data-lucide="external-link" class="w-3 h-3"></i>
                                    <span>Lihat</span>
                                </a>
                                <a href="{{ route('bkk.admin.berita.edit', $b->id) }}" class="h-7 px-3 rounded-full bg-navy text-white text-xs font-semibold hover:bg-navy-dark inline-flex items-center gap-1 transition-colors shadow-sm">
                                    <i data-lucide="edit-3" class="w-3 h-3"></i>
                                    <span>Edit</span>
                                </a>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center bg-white border border-line rounded-2xl">
                        <i data-lucide="newspaper" class="w-10 h-10 text-muted mx-auto mb-2 opacity-50"></i>
                        <p class="text-sm font-semibold text-navy">Belum ada artikel berita</p>
                        <p class="text-xs text-muted mt-1">Mulai tulis warta bursa kerja pertama Anda sekarang.</p>
                        <a href="{{ route('bkk.admin.berita.create') }}" class="mt-4 inline-flex items-center gap-2 h-9 px-4 rounded-full bg-maroon text-white text-xs font-semibold hover:bg-maroon-dark transition-colors shadow-sm">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                            <span>Tulis Berita Baru</span>
                        </a>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Right: Profil Administrator & Quick Actions (2 cols) -->
        <div class="xl:col-span-2 space-y-4">
            <!-- Profil Otentikasi (Matching me.pages profile card) -->
            <div>
                <h2 class="text-lg font-bold text-navy">Profil Administrator</h2>
                <p class="text-xs text-muted">Sesi aktif dari VerifyAuthToken RBAC</p>
            </div>

            <div class="p-5 bg-white border border-line rounded-2xl hover:shadow-md transition-shadow">
                <div class="flex flex-col items-center p-4 rounded-2xl bg-[#f8f9fa] border border-line/60 text-center">
                    <div class="w-16 h-16 rounded-full bg-maroon text-white flex items-center justify-center font-bold text-xl mb-3 shadow-md">
                        {{ $initials }}
                    </div>
                    <h3 class="font-bold text-base text-navy">
                        {{ $name }}
                    </h3>
                    <div class="mt-1 flex items-center gap-1.5">
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold bg-navy text-white">
                            {{ $role }}
                        </span>
                        <span class="text-xs text-muted font-mono">
                            {{ $nip }}
                        </span>
                    </div>
                </div>

                <div class="mt-4 divide-y divide-line/60 text-xs">
                    <div class="flex justify-between py-2">
                        <span class="text-muted">Username</span>
                        <span class="font-semibold text-navy">{{ $username }}</span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="text-muted">Email</span>
                        <span class="font-medium text-navy">{{ $email }}</span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="text-muted">Status Akun</span>
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-800">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                            <span>Aktif</span>
                        </span>
                    </div>
                    <div class="flex justify-between py-2">
                        <span class="text-muted">Auth Layer</span>
                        <span class="font-mono text-navy font-semibold text-[11px]">VerifyAuthToken</span>
                    </div>
                </div>
            </div>

            <!-- Pintasan Aksi Cepat -->
            <div class="p-5 bg-white border border-line rounded-2xl hover:shadow-md transition-shadow">
                <div class="flex items-center gap-2 mb-3">
                    <i data-lucide="zap" class="w-4 h-4 text-maroon"></i>
                    <h3 class="text-sm font-bold text-navy">Pintasan Cepat</h3>
                </div>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <a href="{{ route('bkk.admin.berita.create') }}" class="p-3 rounded-xl bg-canvas border border-line hover:border-maroon/40 hover:bg-white flex flex-col gap-1.5 transition-all group">
                        <div class="w-7 h-7 rounded-lg bg-maroon/10 text-maroon grid place-items-center group-hover:bg-maroon group-hover:text-white transition-colors">
                            <i data-lucide="pen-tool" class="w-3.5 h-3.5"></i>
                        </div>
                        <span class="font-semibold text-navy">Tulis Berita</span>
                        <span class="text-[10px] text-muted">Publikasi warta baru</span>
                    </a>

                    <a href="{{ route('bkk.admin.berita.index') }}" class="p-3 rounded-xl bg-canvas border border-line hover:border-navy/40 hover:bg-white flex flex-col gap-1.5 transition-all group">
                        <div class="w-7 h-7 rounded-lg bg-navy/10 text-navy grid place-items-center group-hover:bg-navy group-hover:text-white transition-colors">
                            <i data-lucide="newspaper" class="w-3.5 h-3.5"></i>
                        </div>
                        <span class="font-semibold text-navy">Kelola Berita</span>
                        <span class="text-[10px] text-muted">Daftar semua artikel</span>
                    </a>

                    <a href="{{ route('bkk.me.index') }}" class="p-3 rounded-xl bg-canvas border border-line hover:border-navy/40 hover:bg-white flex flex-col gap-1.5 transition-all group">
                        <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-700 grid place-items-center group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                            <i data-lucide="user-check" class="w-3.5 h-3.5"></i>
                        </div>
                        <span class="font-semibold text-navy">Portal Siswa</span>
                        <span class="text-[10px] text-muted">Cek dashboard siswa</span>
                    </a>

                    <a href="{{ url('/bkk') }}" target="_blank" class="p-3 rounded-xl bg-canvas border border-line hover:border-navy/40 hover:bg-white flex flex-col gap-1.5 transition-all group">
                        <div class="w-7 h-7 rounded-lg bg-blue-50 text-blue-700 grid place-items-center group-hover:bg-blue-600 group-hover:text-white transition-colors">
                            <i data-lucide="globe" class="w-3.5 h-3.5"></i>
                        </div>
                        <span class="font-semibold text-navy">Web Publik</span>
                        <span class="text-[10px] text-muted">Lihat portal utama</span>
                    </a>
                </div>
            </div>

            <!-- Status Kesiapan Modul BKK Penus (Sitemap) -->
            <div class="p-5 bg-white border border-line rounded-2xl hover:shadow-md transition-shadow">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <i data-lucide="layers" class="w-4 h-4 text-navy"></i>
                        <h3 class="text-sm font-bold text-navy">Kesiapan Modul BKK</h3>
                    </div>
                    <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full bg-navy/10 text-navy">SITEMAP.md</span>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex items-center justify-between py-1.5 border-b border-line/40">
                        <span class="font-medium text-navy flex items-center gap-2">
                            <i data-lucide="newspaper" class="w-3.5 h-3.5 text-emerald-600"></i>
                            <span>Modul Berita & Agenda</span>
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                            Aktif
                        </span>
                    </div>

                    <a href="{{ route('bkk.admin.tracer.index') }}" class="flex items-center justify-between py-1.5 border-b border-line/40 hover:bg-canvas px-1 rounded transition-colors">
                        <span class="font-medium text-navy flex items-center gap-2">
                            <i data-lucide="graduation-cap" class="w-3.5 h-3.5 text-emerald-600"></i>
                            <span>Tracer Study Alumni</span>
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                            Aktif
                        </span>
                    </a>

                    <a href="{{ route('bkk.admin.mitra.index') }}" class="flex items-center justify-between py-1.5 border-b border-line/40 hover:bg-canvas px-1 rounded transition-colors">
                        <span class="font-medium text-navy flex items-center gap-2">
                            <i data-lucide="building-2" class="w-3.5 h-3.5 text-emerald-600"></i>
                            <span>Mitra IDUKA</span>
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                            Aktif
                        </span>
                    </a>

                    <a href="{{ route('bkk.admin.lowongan.index') }}" class="flex items-center justify-between py-1.5 border-b border-line/40 hover:bg-canvas px-1 rounded transition-colors">
                        <span class="font-medium text-navy flex items-center gap-2">
                            <i data-lucide="briefcase" class="w-3.5 h-3.5 text-emerald-600"></i>
                            <span>Lowongan Kerja BKK</span>
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                            Aktif
                        </span>
                    </a>

                    <a href="{{ route('bkk.admin.pkl.monitoring') }}" class="flex items-center justify-between py-1.5 hover:bg-canvas px-1 rounded transition-colors">
                        <span class="font-medium text-navy flex items-center gap-2">
                            <i data-lucide="activity" class="w-3.5 h-3.5 text-emerald-600"></i>
                            <span>Monitoring PKL Siswa</span>
                        </span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                            Aktif
                        </span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Ringkasan Ekosistem BKK (Lowongan, Mitra, PKL, Tracer Study) -->
    <div class="space-y-4 pt-2">
        <div>
            <h2 class="text-lg font-bold text-navy">Aktivitas Terkini Ekosistem BKK</h2>
            <p class="text-xs text-muted">Pantau data terbaru lowongan aktif, mitra industri, siswa PKL, dan kuesioner tracer study.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <!-- 1. Lowongan Terbaru -->
            <div class="p-5 bg-white border border-line rounded-2xl shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <i data-lucide="briefcase" class="w-4 h-4 text-navy"></i>
                            <h3 class="text-sm font-bold text-navy">Lowongan Baru</h3>
                        </div>
                        <a href="{{ route('bkk.admin.lowongan.index') }}" class="text-[11px] font-semibold text-maroon hover:underline">Semua &rarr;</a>
                    </div>
                    <div class="space-y-2.5">
                        @forelse($recentLowongans as $low)
                            <div class="p-2.5 rounded-xl bg-canvas border border-line/60 text-xs">
                                <div class="font-semibold text-navy truncate">{{ $low->judul }}</div>
                                <div class="text-[11px] text-muted mt-0.5 truncate">{{ $low->mitra?->nama_perusahaan ?? 'Mitra Industri' }} · <span class="text-navy font-medium">{{ $low->tipe }}</span></div>
                            </div>
                        @empty
                            <p class="text-xs text-muted text-center py-4">Belum ada data lowongan</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- 2. Mitra Terbaru -->
            <div class="p-5 bg-white border border-line rounded-2xl shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <i data-lucide="building-2" class="w-4 h-4 text-navy"></i>
                            <h3 class="text-sm font-bold text-navy">Mitra Industri</h3>
                        </div>
                        <a href="{{ route('bkk.admin.mitra.index') }}" class="text-[11px] font-semibold text-maroon hover:underline">Semua &rarr;</a>
                    </div>
                    <div class="space-y-2.5">
                        @forelse($recentMitras as $mit)
                            <div class="p-2.5 rounded-xl bg-canvas border border-line/60 text-xs">
                                <div class="font-semibold text-navy truncate">{{ $mit->nama_perusahaan }}</div>
                                <div class="text-[11px] text-muted mt-0.5 truncate">{{ $mit->sektor_industri ?? 'Industri Mitra' }}</div>
                            </div>
                        @empty
                            <p class="text-xs text-muted text-center py-4">Belum ada data mitra</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- 3. Monitoring PKL -->
            <div class="p-5 bg-white border border-line rounded-2xl shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <i data-lucide="activity" class="w-4 h-4 text-navy"></i>
                            <h3 class="text-sm font-bold text-navy">Monitoring PKL</h3>
                        </div>
                        <a href="{{ route('bkk.admin.pkl.monitoring') }}" class="text-[11px] font-semibold text-maroon hover:underline">Semua &rarr;</a>
                    </div>
                    <div class="space-y-2.5">
                        @forelse($recentPkl as $pkl)
                            <div class="p-2.5 rounded-xl bg-canvas border border-line/60 text-xs">
                                <div class="font-semibold text-navy truncate">{{ $pkl->siswa?->user_id ?? 'Siswa PKL' }}</div>
                                <div class="text-[11px] text-muted mt-0.5 truncate">{{ $pkl->mitra?->nama_perusahaan ?? 'Lokasi PKL' }} · <span class="text-emerald-700 font-medium">{{ $pkl->status }}</span></div>
                            </div>
                        @empty
                            <p class="text-xs text-muted text-center py-4">Belum ada siswa PKL aktif</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- 4. Tracer Study -->
            <div class="p-5 bg-white border border-line rounded-2xl shadow-sm flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <i data-lucide="graduation-cap" class="w-4 h-4 text-navy"></i>
                            <h3 class="text-sm font-bold text-navy">Tracer Alumni</h3>
                        </div>
                        <a href="{{ route('bkk.admin.tracer.index') }}" class="text-[11px] font-semibold text-maroon hover:underline">Semua &rarr;</a>
                    </div>
                    <div class="space-y-2.5">
                        @forelse($recentTracer as $tr)
                            <div class="p-2.5 rounded-xl bg-canvas border border-line/60 text-xs">
                                <div class="font-semibold text-navy truncate">{{ $tr->profilSiswa?->nis ?? $tr->alumni_id }}</div>
                                <div class="text-[11px] text-muted mt-0.5 truncate">Status: <span class="text-navy font-medium">{{ $tr->status_setelah_lulus }}</span></div>
                            </div>
                        @empty
                            <p class="text-xs text-muted text-center py-4">Belum ada respon tracer</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Slide-in Detail Drawer for Article Inspection (Mirroring resources/views/me) -->
<div id="detailDrawerBackdrop" class="fixed inset-0 bg-navy/40 backdrop-blur-[1px] z-50 hidden transition-opacity duration-300" onclick="closeDetailModal()"></div>

<aside id="detailDrawer" class="fixed right-0 top-0 bottom-0 w-full sm:w-[460px] bg-white shadow-2xl flex flex-col z-50 transform translate-x-full transition-transform duration-300 ease-in-out hidden">
    <div class="flex items-center justify-between px-5 h-16 border-b border-line shrink-0">
        <span class="font-bold text-navy text-base">Detail Publikasi Berita</span>
        <button onclick="closeDetailModal()" class="w-9 h-9 rounded-full grid place-items-center hover:bg-canvas cursor-pointer text-muted" aria-label="Tutup">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto p-5 space-y-6" id="detailDrawerBody">
        <!-- Injected via JavaScript -->
    </div>

    <div class="p-4 border-t border-line flex gap-2 justify-end bg-canvas">
        <button onclick="copyArticleUrl()" class="h-9 px-4 rounded-full border border-line text-navy bg-white text-xs font-semibold hover:bg-canvas inline-flex items-center gap-1.5 cursor-pointer">
            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
            <span>Salin URL</span>
        </button>
        <button onclick="closeDetailModal()" class="h-9 px-5 rounded-full bg-navy text-white text-xs font-semibold hover:bg-navy-dark cursor-pointer">
            Tutup
        </button>
    </div>
</aside>
@endsection

@push('scripts')
<script>
    let activeArticleData = null;

    function openDetailModal(berita) {
        activeArticleData = berita;
        const drawer = document.getElementById('detailDrawer');
        const backdrop = document.getElementById('detailDrawerBackdrop');
        const body = document.getElementById('detailDrawerBody');
        if (!drawer || !backdrop || !body) return;

        const isPublished = (berita.status === 'PUBLISHED');
        const statusBadge = isPublished
            ? '<span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">Terbit</span>'
            : '<span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold bg-amber-100 text-amber-800 border border-amber-200">Draf</span>';

        const categoryName = berita.kategori ? berita.kategori.nama : 'Umum';
        const coverHtml = berita.gambar_sampul
            ? `<div class="rounded-2xl overflow-hidden border border-line shadow-sm"><img src="${berita.gambar_sampul}" alt="${berita.judul}" class="w-full h-48 object-cover"/></div>`
            : '';

        const editUrl = `{{ url('/bkk/admin/berita') }}/${berita.id}`;
        const webUrl = `{{ url('/bkk/berita') }}/${berita.slug || berita.id}`;

        body.innerHTML = `
            <div class="space-y-4">
                ${coverHtml}

                <div>
                    <div class="flex items-center gap-2 mb-2">
                        ${statusBadge}
                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-medium bg-[#f1f3f4] text-navy">
                            ${categoryName}
                        </span>
                    </div>
                    <h3 class="font-bold text-lg text-navy leading-snug">${berita.judul}</h3>
                    <div class="text-xs text-muted mt-1.5 flex items-center gap-2">
                        <span>Penulis: <b class="text-navy">${berita.penulis_nama || 'Tim Humas'}</b></span>
                        <span>•</span>
                        <span>${berita.formatted_date || '-'}</span>
                    </div>
                </div>

                <div class="rounded-2xl bg-[#f8f9fa] border border-line p-4 text-xs space-y-2">
                    <div class="font-semibold text-navy flex items-center gap-1.5">
                        <i data-lucide="file-text" class="w-3.5 h-3.5 text-muted"></i>
                        <span>Ringkasan Artikel</span>
                    </div>
                    <p class="text-muted leading-relaxed">
                        ${berita.ringkasan || 'Tidak ada ringkasan teks untuk artikel ini.'}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div class="p-3 rounded-xl bg-canvas border border-line">
                        <div class="text-muted text-[11px]">Total Tayangan</div>
                        <div class="text-base font-bold text-navy mt-0.5">${berita.views_count || 0} views</div>
                    </div>
                    <div class="p-3 rounded-xl bg-canvas border border-line">
                        <div class="text-muted text-[11px]">Estimasi Baca</div>
                        <div class="text-base font-bold text-navy mt-0.5">${berita.estimasi_baca || '2 Menit'}</div>
                    </div>
                </div>

                <div class="pt-2 flex flex-col gap-2">
                    <a href="${webUrl}" target="_blank" class="w-full h-10 rounded-full bg-canvas text-navy border border-line text-xs font-semibold hover:bg-line/40 inline-flex items-center justify-center gap-2 transition-colors">
                        <i data-lucide="external-link" class="w-4 h-4"></i>
                        <span>Buka Halaman Web Publik</span>
                    </a>
                    <a href="${editUrl}" class="w-full h-10 rounded-full bg-navy text-white text-xs font-semibold hover:bg-navy-dark inline-flex items-center justify-center gap-2 transition-colors shadow-sm">
                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                        <span>Edit Artikel di Formulir</span>
                    </a>
                </div>
            </div>
        `;

        if (window.lucide) {
            lucide.createIcons({ root: body });
        }

        backdrop.classList.remove('hidden');
        drawer.classList.remove('hidden');
        void drawer.offsetWidth;
        drawer.classList.remove('translate-x-full');
        document.body.classList.add('overflow-hidden');
    }

    function closeDetailModal() {
        const drawer = document.getElementById('detailDrawer');
        const backdrop = document.getElementById('detailDrawerBackdrop');
        if (!drawer || !backdrop) return;

        drawer.classList.add('translate-x-full');
        document.body.classList.remove('overflow-hidden');
        setTimeout(() => {
            if (drawer.classList.contains('translate-x-full')) {
                drawer.classList.add('hidden');
                backdrop.classList.add('hidden');
            }
        }, 300);
    }

    function copyArticleUrl() {
        if (!activeArticleData) return;
        const slug = activeArticleData.slug || activeArticleData.id;
        const url = `${window.location.origin}/bkk/berita/${slug}`;
        navigator.clipboard.writeText(url).then(() => {
            showToast('Tautan publik artikel berhasil disalin!');
        }).catch(() => {
            showToast(`URL: ${url}`);
        });
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDetailModal();
        }
    });
</script>
@endpush
