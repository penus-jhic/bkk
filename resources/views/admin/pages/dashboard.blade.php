@extends('admin.master')

@section('title', 'Dashboard Admin BKK - SMK Plus Pelita Nusantara')

@php
    $name = $authUser['nama_lengkap'] ?? $authUser['username'] ?? 'Administrator';
    $firstName = \Illuminate\Support\Str::of($name)->explode(' ')->first();

    $hour = (int) now()->format('H');
    $greet = match (true) {
        $hour < 11 => 'Selamat pagi',
        $hour < 15 => 'Selamat siang',
        $hour < 18 => 'Selamat sore',
        default => 'Selamat malam',
    };

    $s = fn (string $key) => (int) ($stats[$key] ?? 0);

    // Antrean yang perlu ditangani admin hari ini
    $todo = array_values(array_filter([
        ['count' => $s('total_permohonan_pending'), 'label' => 'permohonan kerja sama', 'href' => route('bkk.admin.mitra.permohonan.index')],
        ['count' => $s('total_jurnal_menunggu'), 'label' => 'jurnal PKL belum divalidasi', 'href' => route('bkk.admin.pkl.monitoring')],
        ['count' => $s('total_laporan_ditinjau'), 'label' => 'bab laporan PKL perlu ditinjau', 'href' => route('bkk.admin.pkl.monitoring')],
        ['count' => $s('total_draft'), 'label' => 'draf berita belum terbit', 'href' => route('bkk.admin.berita.index', ['status' => 'DRAFT'])],
    ], fn ($item) => $item['count'] > 0));

    $funnelMax = max(1, collect($lamaranFunnel)->max('total'));
    $funnelTone = [
        'Terkirim' => 'bg-brand-ink/25',
        'Sedang Ditinjau' => 'bg-brand-rose',
        'Dipanggil Interview' => 'bg-brand-warmred',
        'Diterima' => 'bg-brand-darkred',
        'Ditolak' => 'bg-brand-ink/10',
    ];
    $statusBadge = [
        'Terkirim' => 'bg-brand-softmist text-brand-ink',
        'Sedang Ditinjau' => 'bg-amber-50 text-amber-800 border border-amber-200',
        'Dipanggil Interview' => 'bg-blue-50 text-blue-800 border border-blue-200',
        'Diterima' => 'bg-emerald-50 text-emerald-800 border border-emerald-200',
        'Ditolak' => 'bg-brand-darkred/5 text-brand-darkred border border-brand-darkred/20',
    ];

    $tracerTotal = max(1, $s('total_tracer_respon'));
    $tracerRate = $s('total_tracer_respon') > 0 ? (int) round($s('tracer_terserap') / $s('total_tracer_respon') * 100) : 0;
    $tracerTone = [
        'Bekerja' => 'bg-brand-darkred',
        'Melanjutkan Pendidikan' => 'bg-brand-warmred',
        'Wirausaha' => 'bg-brand-rose',
        'Mencari Kerja' => 'bg-brand-ink/20',
    ];

    $kpis = [
        [
            'label' => 'Lowongan Aktif',
            'value' => $s('total_lowongan_aktif'),
            'icon' => 'briefcase',
            'note' => $s('total_lowongan_pkl') . ' PKL · ' . $s('total_lowongan_kerja') . ' Kerja',
            'href' => route('bkk.admin.lowongan.index'),
        ],
        [
            'label' => 'Total Pelamar',
            'value' => $s('total_pelamar'),
            'icon' => 'file-user',
            'note' => (collect($lamaranFunnel)->firstWhere('status', 'Diterima')['total'] ?? 0) . ' diterima mitra',
            'href' => route('bkk.admin.lowongan.index'),
        ],
        [
            'label' => 'Siswa PKL Berjalan',
            'value' => $s('total_siswa_pkl'),
            'icon' => 'activity',
            'note' => $s('total_pkl_selesai') . ' selesai · ' . $s('total_jurnal_menunggu') . ' jurnal menunggu',
            'href' => route('bkk.admin.pkl.monitoring'),
        ],
        [
            'label' => 'Mitra IDUKA',
            'value' => $s('total_mitra'),
            'icon' => 'building-2',
            'note' => $s('total_mitra_verified') . ' terverifikasi · ' . $s('total_permohonan_pending') . ' permohonan',
            'href' => route('bkk.admin.mitra.index'),
        ],
    ];

    $card = 'bg-white rounded-card border border-brand-ink/10 shadow-card';
@endphp

@section('content')
<div class="space-y-8 fade-up">

    {{-- 1. HERO --}}
    <section class="{{ $card }} relative p-6 sm:p-8 overflow-hidden">
        <div class="absolute -right-20 -top-24 w-72 h-72 rounded-full bg-brand-darkred/[0.04] pointer-events-none"></div>
        <div class="relative grid lg:grid-cols-[1fr_340px] gap-8 items-center">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-brand-darkred">Pusat Kendali Admin · BKK SMK Plus Pelita Nusantara</p>
                <h1 class="mt-3 font-display text-3xl sm:text-4xl lg:text-[44px] font-bold uppercase tracking-wide text-brand-ink leading-[1.1]">
                    {{ $greet }}, <x-sketch.underline size="md">{{ $firstName }}</x-sketch.underline>
                </h1>
                <p class="mt-5 text-sm sm:text-[15px] text-brand-ink/70 leading-relaxed max-w-2xl">
                    Hari ini ada <b class="text-brand-ink">{{ $s('total_lowongan_aktif') }} lowongan aktif</b> dari
                    <b class="text-brand-ink">{{ $s('total_mitra') }} mitra IDUKA</b>,
                    <b class="text-brand-ink">{{ $s('total_pelamar') }} lamaran</b> yang tercatat, dan
                    <b class="text-brand-ink">{{ $s('total_siswa_pkl') }} siswa</b> sedang menjalani PKL.
                </p>
                <div class="mt-6 flex flex-wrap gap-2.5">
                    <a href="{{ route('bkk.admin.lowongan.index') }}" class="inline-flex items-center gap-2 h-10 px-5 rounded-full bg-gradient-to-r from-brand-signal to-brand-darkred text-white text-sm font-semibold shadow-[0_4px_14px_0_rgba(122,16,24,0.25)] hover:-translate-y-px transition-transform">
                        Kelola Lowongan
                        <x-sketch.arrow class="w-6 h-3" />
                    </a>
                    <a href="{{ route('bkk.admin.mitra.create') }}" class="inline-flex items-center gap-2 h-10 px-5 rounded-full border border-brand-ink/15 bg-white text-brand-ink text-sm font-semibold hover:bg-brand-softmist transition-colors">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        Tambah Mitra
                    </a>
                </div>
            </div>

            {{-- Antrean tindakan --}}
            <div class="relative rounded-card bg-brand-paper p-5">
                <x-sketch.corner class="-left-2 -top-2 w-20 h-8" />
                <x-sketch.corner :delay="350" class="-right-2 -bottom-2 rotate-180 w-20 h-8" />
                <h2 class="font-display text-lg uppercase tracking-wide text-brand-ink">Perlu Tindakan</h2>
                <ul class="mt-3 space-y-1.5">
                    @forelse($todo as $item)
                        <li>
                            <a href="{{ $item['href'] }}" class="group flex items-center gap-3 rounded-xl px-2 py-1.5 -mx-2 hover:bg-white transition-colors">
                                <span class="min-w-8 h-8 px-2 rounded-full bg-brand-darkred text-white font-display text-sm grid place-items-center">{{ $item['count'] }}</span>
                                <span class="text-sm text-brand-ink flex-1">{{ $item['label'] }}</span>
                                <i data-lucide="chevron-right" class="w-4 h-4 text-muted group-hover:text-brand-darkred group-hover:translate-x-0.5 transition-transform"></i>
                            </a>
                        </li>
                    @empty
                        <li class="flex items-center gap-2 text-sm text-brand-ink/70 py-2">
                            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600"></i>
                            Tidak ada antrean, semua sudah ditangani.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </section>

    {{-- 2. KPI --}}
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4" aria-label="Ringkasan angka BKK">
        @foreach($kpis as $kpi)
            <a href="{{ $kpi['href'] }}" class="{{ $card }} group p-5 hover:border-brand-darkred/25 hover:shadow-softpill transition-all">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-brand-ink/60">{{ $kpi['label'] }}</span>
                    <span class="w-9 h-9 rounded-full bg-brand-darkred/[0.07] text-brand-darkred grid place-items-center group-hover:bg-brand-darkred group-hover:text-white transition-colors">
                        <i data-lucide="{{ $kpi['icon'] }}" class="w-4 h-4"></i>
                    </span>
                </div>
                <div class="mt-3 font-display text-5xl font-bold text-brand-ink leading-none">{{ number_format($kpi['value']) }}</div>
                <div class="mt-3 pt-3 border-t border-brand-ink/10 text-xs text-brand-ink/60">{{ $kpi['note'] }}</div>
            </a>
        @endforeach
    </section>

    {{-- 3. CORONG LAMARAN + KETERSERAPAN ALUMNI --}}
    <section class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        <div class="{{ $card }} xl:col-span-7 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="font-display text-xl uppercase tracking-wide text-brand-ink"><x-sketch.underline size="sm">Corong Lamaran</x-sketch.underline></h2>
                    <p class="text-xs text-brand-ink/60 mt-3">Posisi seluruh lamaran siswa & alumni di tahap seleksi mitra.</p>
                </div>
                <span class="font-display text-2xl text-brand-ink">{{ $s('total_pelamar') }}</span>
            </div>
            <div class="mt-6 space-y-3.5">
                @foreach($lamaranFunnel as $row)
                    <div class="grid grid-cols-[140px_1fr_32px] items-center gap-3 text-sm">
                        <span class="text-brand-ink/80 truncate">{{ $row['status'] }}</span>
                        <div class="h-3 rounded-full bg-brand-paper overflow-hidden">
                            <div class="h-full rounded-full {{ $funnelTone[$row['status']] }}" style="width: {{ $row['total'] > 0 ? max(4, $row['total'] / $funnelMax * 100) : 0 }}%"></div>
                        </div>
                        <span class="font-display text-base text-brand-ink text-right">{{ $row['total'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="{{ $card }} xl:col-span-5 p-6 flex flex-col">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="font-display text-xl uppercase tracking-wide text-brand-ink"><x-sketch.underline size="sm">Keterserapan Alumni</x-sketch.underline></h2>
                    <p class="text-xs text-brand-ink/60 mt-3">Dari {{ $s('total_tracer_respon') }} respon tracer study.</p>
                </div>
                <a href="{{ route('bkk.admin.tracer.index') }}" class="text-xs font-semibold text-brand-darkred hover:underline shrink-0">Tracer Study</a>
            </div>
            <div class="mt-5 flex items-end gap-3">
                <span class="font-display text-5xl font-bold text-brand-darkred leading-none">{{ $tracerRate }}%</span>
                <span class="text-xs text-brand-ink/60 pb-1">alumni terserap (bekerja, kuliah, atau wirausaha)</span>
            </div>
            <div class="mt-5 flex h-3 rounded-full overflow-hidden bg-brand-paper" role="img" aria-label="Komposisi keterserapan alumni">
                @foreach($tracerBreakdown as $row)
                    @if($row['total'] > 0)
                        <div class="{{ $tracerTone[$row['status']] }} h-full border-r-2 border-white last:border-r-0" style="width: {{ $row['total'] / $tracerTotal * 100 }}%"></div>
                    @endif
                @endforeach
            </div>
            <ul class="mt-4 grid grid-cols-2 gap-x-4 gap-y-2 text-xs">
                @foreach($tracerBreakdown as $row)
                    <li class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full {{ $tracerTone[$row['status']] }}"></span>
                        <span class="text-brand-ink/70 flex-1">{{ $row['status'] }}</span>
                        <span class="font-bold text-brand-ink">{{ $row['total'] }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- 4. LAMARAN TERBARU + LOWONGAN TERBARU --}}
    <section class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        <div class="{{ $card }} xl:col-span-7 overflow-hidden flex flex-col">
            <div class="px-6 pt-6 pb-4 flex items-end justify-between gap-4">
                <h2 class="font-display text-xl uppercase tracking-wide text-brand-ink"><x-sketch.underline size="sm">Lamaran Terbaru</x-sketch.underline></h2>
                <span class="text-xs text-brand-ink/60">{{ $s('total_pelamar') }} total</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm table-fixed">
                    <colgroup><col class="w-[34%]"><col><col class="w-[150px]"></colgroup>
                    <thead>
                        <tr class="text-left text-[11px] font-bold uppercase tracking-wider text-brand-ink/50 border-y border-brand-ink/10 bg-brand-paper/60">
                            <th class="px-6 py-2.5">Pelamar</th>
                            <th class="px-3 py-2.5">Lowongan</th>
                            <th class="px-6 py-2.5 text-right">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-brand-ink/5">
                        @forelse($recentLamarans as $lamaran)
                            <tr class="hover:bg-brand-paper/50">
                                <td class="px-6 py-3">
                                    <div class="font-semibold text-brand-ink truncate">NIS {{ $lamaran->siswa?->nis ?? '-' }}</div>
                                    <div class="text-xs text-brand-ink/60 truncate">{{ $lamaran->siswa?->jurusan ?? 'Siswa / Alumni' }}</div>
                                </td>
                                <td class="px-3 py-3">
                                    @if($lamaran->lowongan)
                                        <a href="{{ route('bkk.admin.lowongan.siswa', $lamaran->lowongan->id) }}" class="block font-medium text-brand-ink hover:text-brand-darkred truncate">{{ $lamaran->lowongan->judul }}</a>
                                        <div class="text-xs text-brand-ink/60 truncate">{{ $lamaran->lowongan->mitra?->nama_perusahaan ?? '-' }}</div>
                                    @else
                                        <span class="text-brand-ink/50">Lowongan dihapus</span>
                                    @endif
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <span class="inline-block rounded-full px-2.5 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $statusBadge[$lamaran->status] ?? 'bg-brand-softmist text-brand-ink' }}">{{ $lamaran->status }}</span>
                                    <div class="text-[11px] text-brand-ink/50 mt-1 whitespace-nowrap">{{ $lamaran->tanggal_melamar?->translatedFormat('d M Y') ?? '-' }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-6 py-10 text-center text-sm text-brand-ink/50">Belum ada lamaran yang masuk.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="{{ $card }} xl:col-span-5 p-6">
            <div class="flex items-end justify-between gap-4">
                <h2 class="font-display text-xl uppercase tracking-wide text-brand-ink"><x-sketch.underline size="sm">Lowongan Terbaru</x-sketch.underline></h2>
                <a href="{{ route('bkk.admin.lowongan.index') }}" class="group inline-flex items-center gap-1.5 text-xs font-semibold text-brand-darkred">
                    Semua <x-sketch.arrow class="w-5 h-2.5 group-hover:translate-x-0.5 transition-transform" />
                </a>
            </div>
            <ul class="mt-5 space-y-2.5">
                @forelse($recentLowongans as $low)
                    <li>
                        <a href="{{ route('bkk.admin.lowongan.edit', $low->id) }}" class="flex items-center gap-3 p-3 rounded-xl border border-brand-ink/10 hover:border-brand-darkred/30 hover:bg-brand-paper/50 transition-colors">
                            <span class="w-11 h-11 rounded-xl grid place-items-center font-display text-xs uppercase shrink-0 {{ $low->tipe === 'PKL' ? 'bg-brand-darkred text-white' : 'bg-brand-ink text-white' }}">{{ $low->tipe }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-brand-ink truncate">{{ $low->judul }}</span>
                                <span class="block text-xs text-brand-ink/60 truncate">{{ $low->mitra?->nama_perusahaan ?? 'Mitra' }} · {{ $low->deadline ? 'tutup ' . $low->deadline->translatedFormat('d M') : 'tanpa batas' }}</span>
                            </span>
                            <span class="text-right shrink-0">
                                <span class="block font-display text-lg text-brand-ink leading-none">{{ $low->lamarans_count }}</span>
                                <span class="block text-[10px] uppercase tracking-wider text-brand-ink/50">pelamar</span>
                            </span>
                        </a>
                    </li>
                @empty
                    <li class="text-sm text-brand-ink/50 text-center py-8">Belum ada lowongan.</li>
                @endforelse
            </ul>
        </div>
    </section>

    {{-- 5. PKL BERJALAN + MITRA & PERMOHONAN --}}
    <section class="grid grid-cols-1 xl:grid-cols-2 gap-6">
        <div class="{{ $card }} p-6">
            <div class="flex items-end justify-between gap-4">
                <h2 class="font-display text-xl uppercase tracking-wide text-brand-ink"><x-sketch.underline size="sm">PKL Berjalan</x-sketch.underline></h2>
                <a href="{{ route('bkk.admin.pkl.monitoring') }}" class="group inline-flex items-center gap-1.5 text-xs font-semibold text-brand-darkred">
                    Monitoring <x-sketch.arrow class="w-5 h-2.5 group-hover:translate-x-0.5 transition-transform" />
                </a>
            </div>
            <ul class="mt-5 space-y-4">
                @forelse($recentPkl as $pkl)
                    @php $progress = $pkl->target_jam > 0 ? min(100, (int) round($pkl->total_jam_tercapai / $pkl->target_jam * 100)) : 0; @endphp
                    <li>
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-brand-ink">NIS {{ $pkl->siswa?->nis ?? '-' }} <span class="font-normal text-brand-ink/60">· {{ $pkl->siswa?->kelas ?? '-' }}</span></div>
                                <div class="text-xs text-brand-ink/60 truncate">{{ $pkl->mitra?->nama_perusahaan ?? 'Lokasi PKL' }}{{ $pkl->unit_kerja_divisi ? ' · ' . $pkl->unit_kerja_divisi : '' }}</div>
                            </div>
                            <span class="text-xs font-bold text-brand-ink whitespace-nowrap">{{ $pkl->total_jam_tercapai }}/{{ $pkl->target_jam }} jam</span>
                        </div>
                        <div class="mt-2 h-2 rounded-full bg-brand-paper overflow-hidden">
                            <div class="h-full rounded-full bg-brand-darkred" style="width: {{ $progress }}%"></div>
                        </div>
                        <div class="mt-1 flex justify-between text-[11px] text-brand-ink/50">
                            <span>{{ $progress }}% target jam</span>
                            <span>s.d. {{ $pkl->tanggal_selesai?->translatedFormat('d M Y') ?? '-' }}</span>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-brand-ink/50 text-center py-8">Belum ada siswa yang sedang PKL.</li>
                @endforelse
            </ul>
        </div>

        <div class="{{ $card }} p-6">
            <div class="flex items-end justify-between gap-4">
                <h2 class="font-display text-xl uppercase tracking-wide text-brand-ink"><x-sketch.underline size="sm">Mitra IDUKA</x-sketch.underline></h2>
                <a href="{{ route('bkk.admin.mitra.index') }}" class="group inline-flex items-center gap-1.5 text-xs font-semibold text-brand-darkred">
                    Semua mitra <x-sketch.arrow class="w-5 h-2.5 group-hover:translate-x-0.5 transition-transform" />
                </a>
            </div>

            @if($pendingPermohonans->isNotEmpty())
                <div class="mt-5 rounded-xl border border-brand-darkred/20 bg-brand-darkred/[0.03] p-3">
                    <div class="flex items-center justify-between text-xs font-bold uppercase tracking-wider text-brand-darkred">
                        <span>Permohonan menunggu review</span>
                        <a href="{{ route('bkk.admin.mitra.permohonan.index') }}" class="hover:underline normal-case tracking-normal">Tinjau</a>
                    </div>
                    <ul class="mt-2 space-y-1.5">
                        @foreach($pendingPermohonans as $permohonan)
                            <li class="flex items-center justify-between gap-3 text-sm">
                                <span class="font-semibold text-brand-ink truncate">{{ $permohonan->nama_perusahaan }}</span>
                                <span class="text-xs text-brand-ink/60 shrink-0">{{ $permohonan->created_at?->diffForHumans() }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <ul class="mt-4 divide-y divide-brand-ink/5">
                @forelse($recentMitras as $mitra)
                    <li class="flex items-center gap-3 py-3">
                        @if($mitra->logo_url)
                            <img src="{{ $mitra->logo_url }}" alt="" class="w-10 h-10 rounded-xl object-cover border border-brand-ink/10 shrink-0" loading="lazy">
                        @else
                            <span class="w-10 h-10 rounded-xl bg-brand-softmist text-brand-ink font-display text-sm grid place-items-center shrink-0">{{ mb_strtoupper(mb_substr($mitra->nama_perusahaan, 0, 2)) }}</span>
                        @endif
                        <span class="min-w-0 flex-1">
                            <a href="{{ route('bkk.admin.mitra.edit', $mitra->id) }}" class="block text-sm font-semibold text-brand-ink hover:text-brand-darkred truncate">{{ $mitra->nama_perusahaan }}</a>
                            <span class="block text-xs text-brand-ink/60 truncate">{{ $mitra->sektor_industri ?? 'Industri' }}</span>
                        </span>
                        <span class="text-right shrink-0">
                            @if($mitra->is_verified)
                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700"><i data-lucide="badge-check" class="w-3.5 h-3.5"></i>Terverifikasi</span>
                            @else
                                <span class="text-[11px] font-semibold text-amber-700">Belum verifikasi</span>
                            @endif
                            <span class="block text-[11px] text-brand-ink/50">{{ $mitra->lowongans_count }} lowongan</span>
                        </span>
                    </li>
                @empty
                    <li class="text-sm text-brand-ink/50 text-center py-8">Belum ada mitra terdaftar.</li>
                @endforelse
            </ul>
        </div>
    </section>

    {{-- 6. BERITA --}}
    <section class="{{ $card }} p-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h2 class="font-display text-xl uppercase tracking-wide text-brand-ink"><x-sketch.underline size="sm">Publikasi Berita BKK</x-sketch.underline></h2>
                <p class="text-xs text-brand-ink/60 mt-3">
                    {{ $s('total_published') }} terbit · {{ $s('total_draft') }} draf · {{ number_format($s('total_views')) }} kali dibaca · {{ $s('total_kategori') }} kategori
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('bkk.admin.berita.index') }}" class="h-9 px-4 rounded-full border border-brand-ink/15 text-xs font-semibold text-brand-ink hover:bg-brand-softmist inline-flex items-center transition-colors">Kelola Berita</a>
                <a href="{{ route('bkk.admin.berita.create') }}" class="h-9 px-4 rounded-full bg-brand-ink text-white text-xs font-semibold hover:bg-brand-darkred inline-flex items-center gap-1.5 transition-colors"><i data-lucide="plus" class="w-3.5 h-3.5"></i>Tulis</a>
            </div>
        </div>
        <div class="mt-5 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            @forelse($recentBeritas as $berita)
                <a href="{{ route('bkk.admin.berita.edit', $berita->id) }}" class="group rounded-xl border border-brand-ink/10 overflow-hidden hover:border-brand-darkred/30 hover:shadow-card transition-all">
                    <div class="aspect-[16/9] bg-brand-softmist overflow-hidden">
                        @if($berita->gambar_sampul)
                            <img src="{{ $berita->gambar_sampul }}" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" loading="lazy">
                        @endif
                    </div>
                    <div class="p-3.5">
                        <div class="flex items-center justify-between gap-2 text-[11px]">
                            <span class="font-semibold text-brand-darkred truncate">{{ $berita->kategori?->nama ?? 'Umum' }}</span>
                            <span class="shrink-0 rounded-full px-2 py-0.5 font-semibold {{ $berita->status === 'PUBLISHED' ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800' }}">{{ $berita->status === 'PUBLISHED' ? 'Terbit' : 'Draf' }}</span>
                        </div>
                        <h3 class="mt-1.5 text-sm font-semibold text-brand-ink leading-snug line-clamp-2 group-hover:text-brand-darkred transition-colors">{{ $berita->judul }}</h3>
                        <div class="mt-2 flex items-center justify-between text-[11px] text-brand-ink/50">
                            <span>{{ $berita->formatted_date }}</span>
                            <span class="inline-flex items-center gap-1"><i data-lucide="eye" class="w-3 h-3"></i>{{ number_format($berita->views_count) }}</span>
                        </div>
                    </div>
                </a>
            @empty
                <p class="col-span-full text-sm text-brand-ink/50 text-center py-8">Belum ada berita. <a href="{{ route('bkk.admin.berita.create') }}" class="text-brand-darkred font-semibold hover:underline">Tulis berita pertama</a>.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
