@extends('mitra.master')

@php
    $mNama = $mitra->nama_perusahaan ?? 'Mitra Industri';

    $hour = (int) now()->format('H');
    $greet = match (true) {
        $hour < 11 => 'Selamat pagi',
        $hour < 15 => 'Selamat siang',
        $hour < 18 => 'Selamat sore',
        default => 'Selamat malam',
    };

    $m = fn (string $key) => (int) ($metrics[$key] ?? 0);
    $funnel = collect($lamaranFunnel)->pluck('total', 'status');

    // Antrean seleksi yang perlu ditangani mitra
    $todo = array_values(array_filter([
        ['count' => (int) ($funnel['Terkirim'] ?? 0), 'label' => 'lamaran baru belum ditinjau', 'href' => route('bkk.mitra.lowongan.index')],
        ['count' => (int) ($funnel['Sedang Ditinjau'] ?? 0), 'label' => 'lamaran sedang ditinjau', 'href' => route('bkk.mitra.lowongan.index')],
        ['count' => $m('pelamar_interview'), 'label' => 'kandidat menunggu hasil interview', 'href' => route('bkk.mitra.lowongan.index')],
    ], fn ($item) => $item['count'] > 0));

    $funnelMax = max(1, $funnel->max());
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
    $lowonganStatusBadge = [
        'Aktif' => 'bg-emerald-50 text-emerald-800',
        'Ditutup' => 'bg-brand-softmist text-brand-ink/70',
        'Draft' => 'bg-amber-50 text-amber-800',
    ];

    $acceptRate = $m('total_pelamar') > 0 ? (int) round($m('pelamar_diterima') / $m('total_pelamar') * 100) : 0;

    $kpis = [
        [
            'label' => 'Lowongan Aktif',
            'value' => $m('lowongan_aktif'),
            'icon' => 'briefcase',
            'note' => 'dari ' . $m('total_lowongan') . ' lowongan yang pernah dipasang',
            'href' => route('bkk.mitra.lowongan.index', ['status' => 'Aktif']),
        ],
        [
            'label' => 'Total Pelamar',
            'value' => $m('total_pelamar'),
            'icon' => 'file-user',
            'note' => ($funnel['Terkirim'] ?? 0) . ' lamaran baru belum ditinjau',
            'href' => route('bkk.mitra.lowongan.index'),
        ],
        [
            'label' => 'Tahap Interview',
            'value' => $m('pelamar_interview'),
            'icon' => 'calendar-check',
            'note' => 'kandidat dipanggil wawancara',
            'href' => route('bkk.mitra.lowongan.index'),
        ],
        [
            'label' => 'Kandidat Diterima',
            'value' => $m('pelamar_diterima'),
            'icon' => 'award',
            'note' => $m('siswa_aktif_pkl') . ' siswa sedang PKL di perusahaan Anda',
            'href' => route('bkk.mitra.lowongan.index'),
        ],
    ];

    $card = 'bg-white rounded-card border border-brand-ink/10 shadow-card';
@endphp

@section('title', 'Dashboard Mitra IDUKA - ' . $mNama)

@section('content')
<div class="space-y-8 fade-up">

    {{-- 1. HERO --}}
    <section class="{{ $card }} relative p-6 sm:p-8 overflow-hidden">
        <div class="absolute -right-20 -top-24 w-72 h-72 rounded-full bg-brand-darkred/[0.04] pointer-events-none"></div>
        <div class="relative grid lg:grid-cols-[1fr_340px] gap-8 items-center">
            <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-brand-darkred">Portal Resmi Rekrutmen Mitra IDUKA · BKK SMK Plus Pelita Nusantara</p>
                <h1 class="mt-3 font-display text-3xl sm:text-4xl lg:text-[44px] font-bold uppercase tracking-wide text-brand-ink leading-[1.1]">
                    {{ $greet }},
                    <span class="block mt-1"><x-sketch.underline size="md">{{ $mNama }}</x-sketch.underline></span>
                </h1>
                <p class="mt-6 text-sm sm:text-[15px] text-brand-ink/70 leading-relaxed max-w-2xl">
                    Saat ini ada <b class="text-brand-ink">{{ $m('lowongan_aktif') }} lowongan aktif</b> milik perusahaan Anda,
                    <b class="text-brand-ink">{{ $m('total_pelamar') }} lamaran</b> dari siswa & alumni yang tercatat, dan
                    <b class="text-brand-ink">{{ $m('siswa_aktif_pkl') }} siswa</b> sedang menjalani PKL bersama Anda.
                </p>
                <div class="mt-6 flex flex-wrap gap-2.5">
                    <a href="{{ route('bkk.mitra.lowongan.create') }}" class="inline-flex items-center gap-2 h-10 px-5 rounded-full bg-gradient-to-r from-brand-signal to-brand-darkred text-white text-sm font-semibold shadow-[0_4px_14px_0_rgba(122,16,24,0.25)] hover:-translate-y-px transition-transform">
                        Pasang Lowongan
                        <x-sketch.arrow class="w-6 h-3" />
                    </a>
                    <a href="{{ route('bkk.mitra.lowongan.index') }}" class="inline-flex items-center gap-2 h-10 px-5 rounded-full border border-brand-ink/15 bg-white text-brand-ink text-sm font-semibold hover:bg-brand-softmist transition-colors">
                        <i data-lucide="briefcase" class="w-4 h-4"></i>
                        Daftar Lowongan
                    </a>
                </div>
                <div class="mt-6 flex flex-wrap items-center gap-x-4 gap-y-1.5 text-xs text-brand-ink/60">
                    <span class="inline-flex items-center gap-1.5"><i data-lucide="file-badge" class="w-3.5 h-3.5 text-brand-darkred"></i>NPWP {{ $mitra->npwp ?? '-' }}</span>
                    <span class="inline-flex items-center gap-1.5"><i data-lucide="user-check" class="w-3.5 h-3.5 text-brand-darkred"></i>PIC {{ $mitra->pic_name ?? '-' }}</span>
                    <span class="inline-flex items-center gap-1.5"><i data-lucide="shield-check" class="w-3.5 h-3.5 {{ $mitra->is_verified ? 'text-emerald-600' : 'text-amber-600' }}"></i>{{ $mitra->is_verified ? ($mitra->status_kemitraan ?? 'Mitra Terverifikasi') : 'Menunggu verifikasi BKK' }}</span>
                </div>
            </div>

            {{-- Antrean seleksi --}}
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
                            Tidak ada lamaran yang menunggu, semua sudah ditangani.
                        </li>
                    @endforelse
                </ul>
            </div>
        </div>
    </section>

    {{-- 2. KPI --}}
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4" aria-label="Ringkasan angka rekrutmen">
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

    {{-- 3. CORONG LAMARAN + PROFIL KEMITRAAN --}}
    <section class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        <div class="{{ $card }} xl:col-span-7 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h2 class="font-display text-xl uppercase tracking-wide text-brand-ink"><x-sketch.underline size="sm">Corong Lamaran</x-sketch.underline></h2>
                    <p class="text-xs text-brand-ink/60 mt-3">Posisi seluruh lamaran ke lowongan Anda di tiap tahap seleksi.</p>
                </div>
                <span class="text-right shrink-0">
                    <span class="block font-display text-2xl text-brand-ink leading-none">{{ $acceptRate }}%</span>
                    <span class="block text-[10px] uppercase tracking-wider text-brand-ink/50 mt-1">diterima</span>
                </span>
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
                <h2 class="font-display text-xl uppercase tracking-wide text-brand-ink"><x-sketch.underline size="sm">Profil Kemitraan</x-sketch.underline></h2>
                <a href="{{ route('bkk.mitra.pengaturan') }}" class="text-xs font-semibold text-brand-darkred hover:underline shrink-0">Ubah profil</a>
            </div>
            <div class="mt-5 flex items-center gap-3">
                @if($mitra->logo_url)
                    <img src="{{ $mitra->logo_url }}" alt="" class="w-12 h-12 rounded-xl object-cover border border-brand-ink/10 bg-white shrink-0">
                @else
                    <span class="w-12 h-12 rounded-xl bg-brand-softmist text-brand-ink font-display text-sm grid place-items-center shrink-0">{{ mb_strtoupper(mb_substr($mitra->singkatan ?: $mNama, 0, 2)) }}</span>
                @endif
                <span class="min-w-0">
                    <span class="block text-sm font-semibold text-brand-ink truncate">{{ $mNama }}</span>
                    <span class="block text-xs text-brand-ink/60 truncate">{{ $mitra->sektor_industri ?? 'Industri' }} · {{ $mitra->kota ?? '-' }}</span>
                </span>
            </div>
            <dl class="mt-5 grid grid-cols-2 gap-px overflow-hidden rounded-xl bg-brand-ink/10 ring-1 ring-brand-ink/10 text-xs">
                <div class="bg-white p-3">
                    <dt class="text-brand-ink/50">Masa MoU</dt>
                    <dd class="mt-0.5 font-semibold text-brand-ink">
                        @if($mitra->tanggal_mou_mulai || $mitra->tanggal_mou_selesai)
                            {{ $mitra->tanggal_mou_mulai?->translatedFormat('M Y') ?? '…' }} – {{ $mitra->tanggal_mou_selesai?->translatedFormat('M Y') ?? '…' }}
                        @else
                            Belum tercatat
                        @endif
                    </dd>
                </div>
                <div class="bg-white p-3">
                    <dt class="text-brand-ink/50">Penanggung Jawab</dt>
                    <dd class="mt-0.5 font-semibold text-brand-ink truncate">{{ $mitra->pic_name ?? '-' }}</dd>
                </div>
                <div class="bg-white p-3">
                    <dt class="text-brand-ink/50">Email PIC</dt>
                    <dd class="mt-0.5 font-semibold text-brand-ink truncate">{{ $mitra->pic_email ?? '-' }}</dd>
                </div>
                <div class="bg-white p-3">
                    <dt class="text-brand-ink/50">Siswa PKL Aktif</dt>
                    <dd class="mt-0.5 font-semibold text-brand-ink">{{ $m('siswa_aktif_pkl') }} siswa</dd>
                </div>
            </dl>
            <div class="relative mt-auto pt-5">
                <div class="relative rounded-xl bg-brand-darkred/5 py-3 pr-3 pl-8 text-xs leading-relaxed text-brand-ink/70">
                    <x-sketch.rule bold vertical class="text-brand-darkred top-1 bottom-1 left-1.5 w-3" />
                    Butuh penyelarasan kualifikasi atau jadwal walk-in interview di sekolah? Hubungi Hubin & BKK di
                    <a href="mailto:kemitraan@smkpenus.sch.id" class="font-semibold text-brand-darkred hover:underline">kemitraan@smkpenus.sch.id</a>.
                </div>
            </div>
        </div>
    </section>

    {{-- 4. LOWONGAN DITAWARKAN + PELAMAR TERBARU --}}
    <section class="grid grid-cols-1 xl:grid-cols-12 gap-6">
        <div class="{{ $card }} xl:col-span-7 p-6">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <h2 class="font-display text-xl uppercase tracking-wide text-brand-ink"><x-sketch.underline size="sm">Lowongan Ditawarkan</x-sketch.underline></h2>
                    <p class="text-xs text-brand-ink/60 mt-3">Posisi PKL & kerja terbaru yang Anda pasang.</p>
                </div>
                <a href="{{ route('bkk.mitra.lowongan.index') }}" class="group inline-flex items-center gap-1.5 text-xs font-semibold text-brand-darkred shrink-0">
                    Semua <x-sketch.arrow class="w-5 h-2.5 group-hover:translate-x-0.5 transition-transform" />
                </a>
            </div>
            <ul class="mt-5 space-y-2.5">
                @forelse($vacancies as $job)
                    <li class="flex items-center gap-3 p-3 rounded-xl border border-brand-ink/10 hover:border-brand-darkred/30 hover:bg-brand-paper/50 transition-colors">
                        <span class="w-11 h-11 rounded-xl grid place-items-center font-display text-xs uppercase shrink-0 {{ $job->tipe === 'PKL' ? 'bg-brand-darkred text-white' : 'bg-brand-ink text-white' }}">{{ $job->tipe }}</span>
                        <span class="min-w-0 flex-1">
                            <a href="{{ route('bkk.mitra.pelamar.index', $job->id) }}" class="block text-sm font-semibold text-brand-ink hover:text-brand-darkred truncate">{{ $job->judul }}</a>
                            <span class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs text-brand-ink/60">
                                <span class="rounded-full px-2 py-px text-[10px] font-semibold {{ $lowonganStatusBadge[$job->status] ?? 'bg-brand-softmist text-brand-ink' }}">{{ $job->status }}</span>
                                <span class="truncate">{{ $job->target_jurusan ?: 'Semua jurusan' }}</span>
                                <span>· {{ $job->deadline ? 'tutup ' . $job->deadline->translatedFormat('d M Y') : 'tanpa batas' }}</span>
                            </span>
                        </span>
                        <span class="text-right shrink-0 hidden sm:block">
                            <span class="block font-display text-lg text-brand-ink leading-none">{{ $job->lamaran_count ?? 0 }}</span>
                            <span class="block text-[10px] uppercase tracking-wider text-brand-ink/50">pelamar</span>
                        </span>
                        <span class="flex items-center gap-1 shrink-0">
                            <a href="{{ route('bkk.mitra.pelamar.index', $job->id) }}" class="h-8 px-3 rounded-full bg-brand-ink text-white text-xs font-semibold hover:bg-brand-darkred inline-flex items-center transition-colors">Review CV</a>
                            <a href="{{ route('bkk.mitra.lowongan.edit', $job->id) }}" class="w-8 h-8 rounded-full grid place-items-center text-muted hover:text-brand-ink hover:bg-brand-softmist transition-colors" title="Edit lowongan" aria-label="Edit lowongan {{ $job->judul }}">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </a>
                        </span>
                    </li>
                @empty
                    <li class="text-center py-10">
                        <span class="w-12 h-12 rounded-full bg-brand-paper text-brand-darkred grid place-items-center mx-auto"><i data-lucide="briefcase" class="w-5 h-5"></i></span>
                        <p class="mt-3 font-display uppercase tracking-wide text-brand-ink">Belum Ada Lowongan</p>
                        <p class="text-xs text-brand-ink/60 mt-1 max-w-sm mx-auto">Pasang lowongan PKL atau kerja untuk siswa dan alumni SMK Plus Pelita Nusantara.</p>
                        <a href="{{ route('bkk.mitra.lowongan.create') }}" class="mt-4 inline-flex items-center gap-1.5 h-9 px-4 rounded-full bg-brand-ink text-white text-xs font-semibold hover:bg-brand-darkred transition-colors">
                            <i data-lucide="plus" class="w-3.5 h-3.5"></i>Buat Lowongan Pertama
                        </a>
                    </li>
                @endforelse
            </ul>
        </div>

        <div class="{{ $card }} xl:col-span-5 p-6">
            <div class="flex items-end justify-between gap-4">
                <h2 class="font-display text-xl uppercase tracking-wide text-brand-ink"><x-sketch.underline size="sm">Pelamar Terbaru</x-sketch.underline></h2>
                <span class="text-xs text-brand-ink/60">{{ $m('total_pelamar') }} total</span>
            </div>
            <ul class="mt-4 divide-y divide-brand-ink/5">
                @forelse($recentApplicants as $applicant)
                    <li>
                        <a href="{{ route('bkk.mitra.pelamar.show', [$applicant->lowongan_id, $applicant->kode_lamaran ?: $applicant->id]) }}" class="group flex items-start gap-3 py-3 -mx-2 px-2 rounded-xl hover:bg-brand-paper/60 transition-colors">
                            <span class="w-10 h-10 rounded-full bg-brand-softmist text-brand-ink font-display text-xs grid place-items-center shrink-0">{{ mb_strtoupper(mb_substr($applicant->jurusan, 0, 3)) }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-brand-ink group-hover:text-brand-darkred truncate">NIS {{ $applicant->siswa?->nis ?? '-' }}</span>
                                <span class="block text-xs text-brand-ink/60 truncate">{{ $applicant->status_pendidikan }} · {{ $applicant->jurusan }}</span>
                                <span class="block text-xs text-brand-ink/80 truncate mt-0.5">{{ $applicant->lowongan_title }}</span>
                            </span>
                            <span class="text-right shrink-0">
                                <span class="inline-block rounded-full px-2.5 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $statusBadge[$applicant->status] ?? 'bg-brand-softmist text-brand-ink' }}">{{ $applicant->status }}</span>
                                <span class="block text-[11px] text-brand-ink/50 mt-1 whitespace-nowrap">
                                    @if($applicant->skor_match_ai)
                                        Skor {{ $applicant->skor_match_ai }}% ·
                                    @endif
                                    {{ $applicant->tanggal_melamar?->translatedFormat('d M') ?? '-' }}
                                </span>
                            </span>
                        </a>
                    </li>
                @empty
                    <li class="py-10 text-center">
                        <i data-lucide="inbox" class="w-8 h-8 mx-auto text-brand-ink/30"></i>
                        <p class="mt-2 text-sm text-brand-ink/50">Belum ada lamaran yang masuk.</p>
                    </li>
                @endforelse
            </ul>
        </div>
    </section>
</div>
@endsection
