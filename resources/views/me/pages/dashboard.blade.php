@extends('me.master')

@section('title', 'Dashboard Siswa & Alumni - BKK SMK Plus Pelita Nusantara')

@php
    $role = strtoupper($authUser['role'] ?? 'SISWA');
    $isSiswa = ($role === 'SISWA');
    $activeCount = count($activeApplications ?? []);
    $interviewCount = count($interviews ?? []);
    $totalApplications = count($applications ?? []);
    $acceptedCount = collect($applications ?? [])->where('status', 'Diterima')->count();
    $completion = (int) ($profile['completion'] ?? 0);
    $score = (int) ($cvScore['total'] ?? 0);
    $firstName = \Illuminate\Support\Str::of($profile['name'] ?? 'Siswa')->explode(' ')->first();
    $hour = (int) now()->format('H');
    $greet = match (true) {
        $hour < 11 => 'Selamat pagi',
        $hour < 15 => 'Selamat siang',
        $hour < 18 => 'Selamat sore',
        default => 'Selamat malam',
    };

    // Hal yang sebaiknya dikerjakan siswa/alumni sekarang
    $todo = array_values(array_filter([
        $interviewCount > 0 ? ['count' => $interviewCount, 'label' => 'undangan interview menunggu konfirmasi', 'href' => route('bkk.me.lamaran')] : null,
        $completion < 100 ? ['count' => $completion . '%', 'label' => 'kelengkapan profil, lengkapi agar dilirik mitra', 'href' => route('bkk.me.cv.edit')] : null,
        $score > 0 && $score < 70 ? ['count' => $score, 'label' => 'skor CV, perbaiki dengan saran AI', 'href' => route('bkk.me.cv')] : null,
    ]));

    $nearestInterview = $interviews[0] ?? null;
    $kpis = [
        ['label' => 'Lamaran Aktif', 'value' => $activeCount, 'icon' => 'briefcase', 'note' => $totalApplications . ' lamaran terkirim seluruhnya', 'href' => route('bkk.me.lamaran')],
        ['label' => 'Panggilan Interview', 'value' => $interviewCount, 'icon' => 'calendar-check', 'note' => $nearestInterview ? 'Terdekat: ' . $nearestInterview['company'] : 'Belum ada jadwal wawancara', 'href' => route('bkk.me.lamaran'), 'alert' => $interviewCount > 0],
        ['label' => 'Skor CV', 'value' => $score, 'suffix' => '/100', 'icon' => 'sparkles', 'note' => $score >= 80 ? 'CV sudah kuat, pertahankan' : 'Optimalkan dengan saran AI', 'href' => route('bkk.me.cv')],
        ['label' => 'Diterima Mitra', 'value' => $acceptedCount, 'icon' => 'badge-check', 'note' => 'dari ' . $totalApplications . ' lamaran', 'href' => route('bkk.me.lamaran')],
    ];

    $card = 'bg-white rounded-card border border-brand-ink/10 shadow-card';
@endphp

@section('content')
<div class="space-y-8 fade-up">
    {{-- HERO --}}
    <section class="{{ $card }} relative p-6 sm:p-8 overflow-hidden">
        <div class="absolute -right-20 -top-24 w-72 h-72 rounded-full bg-brand-darkred/[0.04] pointer-events-none"></div>
        <div class="relative grid lg:grid-cols-[1fr_340px] gap-8 items-center">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.14em] text-brand-darkred">
                    Portal {{ $isSiswa ? 'Siswa PKL' : 'Alumni' }} · {{ $profile['jurusan'] ?? 'SMK Plus Pelita Nusantara' }}
                </p>
                <h1 class="mt-3 font-display text-3xl sm:text-4xl lg:text-[44px] font-bold uppercase tracking-wide text-brand-ink leading-[1.1]">
                    {{ $greet }}, <x-sketch.underline size="md">{{ $firstName }}</x-sketch.underline>
                </h1>
                <p class="mt-5 text-sm sm:text-[15px] text-brand-ink/70 leading-relaxed max-w-2xl">
                    @if($interviewCount > 0)
                        Anda punya <b class="text-brand-ink">{{ $activeCount }} lamaran aktif</b> dan
                        <b class="text-brand-darkred">{{ $interviewCount }} undangan interview</b> yang menunggu konfirmasi kehadiran.
                    @else
                        Anda punya <b class="text-brand-ink">{{ $activeCount }} lamaran aktif</b>. Terus pantau status lamaran
                        dan rekomendasi lowongan mitra industri terbaru.
                    @endif
                </p>
                <div class="mt-6 flex flex-wrap gap-2.5">
                    <a href="{{ route('bkk.lowongan') }}" class="inline-flex items-center gap-2 h-10 px-5 rounded-full bg-gradient-to-r from-brand-signal to-brand-darkred text-white text-sm font-semibold shadow-[0_4px_14px_0_rgba(122,16,24,0.25)] hover:-translate-y-px transition-transform">
                        Cari Lowongan
                        <x-sketch.arrow class="w-6 h-3" />
                    </a>
                    <a href="{{ route('bkk.me.cv.edit') }}" class="inline-flex items-center gap-2 h-10 px-5 rounded-full border border-brand-ink/15 bg-white text-brand-ink text-sm font-semibold hover:bg-brand-softmist transition-colors">
                        <i data-lucide="sparkles" class="w-4 h-4 text-brand-darkred"></i>
                        Optimalkan CV
                    </a>
                </div>
            </div>

            {{-- Perlu tindakan --}}
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
                            Profil & CV sudah siap, tinggal melamar.
                        </li>
                    @endforelse
                </ul>
                <div class="mt-4 pt-3 border-t border-brand-ink/10">
                    <div class="flex justify-between text-xs font-semibold text-brand-ink">
                        <span>Kelengkapan profil</span>
                        <span class="tabular-nums">{{ $completion }}%</span>
                    </div>
                    <div class="mt-1.5 h-2 rounded-full bg-white overflow-hidden">
                        <div class="h-full rounded-full bg-brand-darkred" style="width: {{ $completion }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- KPI --}}
    <section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4" aria-label="Ringkasan lamaran">
        @foreach($kpis as $kpi)
            <a href="{{ $kpi['href'] }}" class="{{ $card }} group p-5 hover:border-brand-darkred/25 hover:shadow-softpill transition-all {{ !empty($kpi['alert']) ? 'ring-1 ring-brand-darkred/20' : '' }}">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider {{ !empty($kpi['alert']) ? 'text-brand-darkred' : 'text-brand-ink/60' }}">{{ $kpi['label'] }}</span>
                    <span class="w-9 h-9 rounded-full grid place-items-center transition-colors {{ !empty($kpi['alert']) ? 'bg-brand-darkred text-white' : 'bg-brand-darkred/[0.07] text-brand-darkred group-hover:bg-brand-darkred group-hover:text-white' }}">
                        <i data-lucide="{{ $kpi['icon'] }}" class="w-4 h-4"></i>
                    </span>
                </div>
                <div class="mt-3 font-display text-5xl font-bold text-brand-ink leading-none">{{ number_format($kpi['value']) }}@if(!empty($kpi['suffix']))<span class="text-lg text-brand-ink/40">{{ $kpi['suffix'] }}</span>@endif</div>
                <div class="mt-3 pt-3 border-t border-brand-ink/10 text-xs text-brand-ink/60 truncate">{{ $kpi['note'] }}</div>
            </a>
        @endforeach
    </section>

    <!-- Main Section: Tracker Lamaran + Rekomendasi Lowongan -->
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">
        <!-- Left: Tracker Lamaran (2 cols) -->
        <div class="xl:col-span-2 space-y-4">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-brand-ink">Tracker Lamaran</h2>
                    <p class="text-xs text-muted">3 lamaran terbaru yang Anda kirimkan</p>
                </div>
                <a href="{{ route('bkk.me.lamaran') }}" class="text-xs font-semibold text-maroon inline-flex items-center gap-1 hover:underline">
                    <span>Lihat semua</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>

            <div class="space-y-3">
                @forelse(array_slice($applications, 0, 3) as $app)
                    @php
                        $statusBadgeClass = match($app['status']) {
                            'Terkirim' => 'border border-navy text-brand-ink bg-white',
                            'Sedang Ditinjau' => 'bg-amber-100 text-amber-800 border border-amber-200',
                            'Dipanggil Interview' => 'bg-maroon text-white border border-maroon',
                            'Diterima' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
                            'Ditolak' => 'bg-gray-100 text-muted border border-gray-200',
                            default => 'bg-gray-100 text-brand-ink'
                        };
                    @endphp
                    <div class="p-4 bg-white border border-line rounded-2xl hover:shadow-md transition-shadow cursor-pointer" onclick='openDetailModal(@json($app))'>
                        <div class="flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl grid place-items-center text-white font-bold text-xs shrink-0 shadow-sm" style="background: {{ $app['color'] }}">
                                {{ strtoupper(substr($app['company'], 0, 2)) }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="font-semibold text-sm text-brand-ink truncate">{{ $app['position'] }}</div>
                                        <div class="text-xs text-muted mt-0.5">{{ $app['company'] }} · {{ $app['date'] }}</div>
                                    </div>
                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $statusBadgeClass }}">
                                        {{ $app['status'] }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Step Progress Bar -->
                        <div class="mt-4 pt-3 border-t border-line/60">
                            @php
                                $steps = ['Terkirim', 'Ditinjau', 'Interview', 'Hasil'];
                                $currentStep = match($app['status']) {
                                    'Diterima' => 4,
                                    'Dipanggil Interview' => 3,
                                    'Sedang Ditinjau' => 2,
                                    'Ditolak' => 3,
                                    default => 1
                                };
                            @endphp
                            <div class="flex items-center w-full">
                                @foreach($steps as $idx => $st)
                                    @php
                                        $isDone = ($idx < $currentStep);
                                        $isLast = ($idx === count($steps) - 1);
                                        $isFailed = ($app['status'] === 'Ditolak' && $idx === 3);
                                    @endphp
                                    <div class="flex items-center {{ !$isLast ? 'flex-1' : '' }}">
                                        <div class="flex flex-col items-center gap-1">
                                            <div class="w-5 h-5 rounded-full grid place-items-center text-[9px] font-bold border {{ $isDone ? 'bg-navy border-navy text-white' : 'bg-white border-line text-muted' }}">
                                                @if($isFailed)
                                                    <i data-lucide="x" class="w-3 h-3"></i>
                                                @elseif($isDone)
                                                    <i data-lucide="check" class="w-3 h-3"></i>
                                                @else
                                                    {{ $idx + 1 }}
                                                @endif
                                            </div>
                                            <span class="text-[10px] {{ $isDone ? 'text-brand-ink font-semibold' : 'text-muted' }}">{{ $st }}</span>
                                        </div>
                                        @if(!$isLast)
                                            <div class="flex-1 h-0.5 mx-1 -mt-4 {{ $idx < $currentStep - 1 ? 'bg-navy' : 'bg-line' }}"></div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="p-8 bg-white border border-line rounded-2xl text-center space-y-2">
                        <div class="w-10 h-10 rounded-full bg-canvas border border-line flex items-center justify-center mx-auto text-muted">
                            <i data-lucide="inbox" class="w-5 h-5"></i>
                        </div>
                        <div class="font-bold text-sm text-brand-ink">Belum Ada Lamaran Aktif</div>
                        <p class="text-xs text-muted max-w-xs mx-auto">Anda belum mengajukan lamaran atau pendaftaran PKL. Silakan jelajahi lowongan yang tersedia untuk mulai mendaftar.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Right: Rekomendasi Lowongan (3 cols) -->
        <div class="xl:col-span-3 space-y-4">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-brand-ink">
                        {{ $isSiswa ? 'Rekomendasi PKL & Lowongan' : 'Lowongan Direkomendasikan' }}
                    </h2>
                    <p class="text-xs text-muted">Dikurasi AI berdasarkan jurusan & kata kunci CV Anda</p>
                </div>
                <span class="rounded-full px-3 py-1 text-xs font-semibold bg-maroon/10 text-maroon inline-flex items-center gap-1">
                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                    <span>AI Match</span>
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @forelse($vacancies as $v)
                    <div class="p-5 bg-white border border-line rounded-2xl hover:shadow-md transition-shadow flex flex-col justify-between">
                        <div>
                            <div class="flex items-start gap-3">
                                <div class="w-11 h-11 rounded-xl text-white font-bold text-sm grid place-items-center shrink-0 shadow-sm" style="background: {{ $v['color'] }}">
                                    {{ strtoupper(substr($v['company'], 0, 2)) }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-bold text-sm text-brand-ink leading-snug truncate">{{ $v['title'] }}</div>
                                    <div class="text-xs text-muted mt-0.5">{{ $v['company'] }}</div>
                                </div>
                                <span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $v['match'] >= 90 ? 'bg-maroon text-white' : 'bg-brand-ink/10 text-brand-ink' }}">
                                    {{ $v['match'] }}%
                                </span>
                            </div>

                            <div class="flex flex-wrap gap-x-4 gap-y-1.5 mt-3 text-xs text-muted">
                                <span class="flex items-center gap-1">
                                    <i data-lucide="map-pin" class="w-3.5 h-3.5"></i> {{ $v['location'] }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <i data-lucide="wallet" class="w-3.5 h-3.5"></i> {{ $v['salary'] }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <i data-lucide="clock" class="w-3.5 h-3.5"></i> s.d. {{ $v['deadline'] }}
                                </span>
                            </div>

                            <div class="flex flex-wrap gap-1.5 mt-3">
                                <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold bg-navy text-white">{{ $v['type'] }}</span>
                                @foreach($v['tags'] as $tg)
                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-medium bg-brand-softmist text-muted">{{ $tg }}</span>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-5 pt-3 border-t border-line flex items-center justify-between gap-2">
                            <button onclick="showToast('Lowongan disimpan ke bookmark')" class="text-xs text-muted hover:text-brand-ink font-medium cursor-pointer transition-colors">
                                Simpan
                            </button>
                            <button id="btn-apply-{{ $v['id'] }}" onclick="applyVacancy(@js($v['id']), @js($v['title']))" class="h-8 px-4 rounded-full bg-maroon text-white text-xs font-semibold hover:bg-maroon-dark transition-colors shadow-sm cursor-pointer">
                                Lamar Sekarang
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full p-8 bg-white border border-line rounded-2xl text-center space-y-2">
                        <div class="w-10 h-10 rounded-full bg-canvas border border-line flex items-center justify-center mx-auto text-muted">
                            <i data-lucide="briefcase" class="w-5 h-5"></i>
                        </div>
                        <div class="font-bold text-sm text-brand-ink">Belum Ada Lowongan Aktif</div>
                        <p class="text-xs text-muted max-w-xs mx-auto">Saat ini belum ada lowongan atau posisi PKL aktif yang tersedia.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Slide-in Detail Drawer for Application Status -->
<div id="detailDrawerBackdrop" class="fixed inset-0 bg-brand-ink/40 backdrop-blur-[1px] z-50 hidden transition-opacity duration-300" onclick="closeDetailModal()"></div>

<aside id="detailDrawer" class="fixed right-0 top-0 bottom-0 w-full sm:w-[460px] bg-white shadow-2xl flex flex-col z-50 transform translate-x-full transition-transform duration-300 ease-in-out hidden">
    <div class="flex items-center justify-between px-5 h-16 border-b border-line shrink-0">
        <span class="font-bold text-brand-ink text-base">Detail Seleksi & Timeline</span>
        <button onclick="closeDetailModal()" class="w-9 h-9 rounded-full grid place-items-center hover:bg-canvas cursor-pointer text-muted" aria-label="Tutup">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto p-5 space-y-6" id="detailDrawerBody">
        <!-- Injected via JavaScript -->
    </div>

    <div class="p-4 border-t border-line flex gap-2 justify-end bg-canvas">
        <button onclick="window.print()" class="h-9 px-4 rounded-full border border-line text-brand-ink bg-white text-xs font-semibold hover:bg-canvas inline-flex items-center gap-1.5 cursor-pointer">
            <i data-lucide="download" class="w-3.5 h-3.5"></i>
            <span>Unduh Bukti</span>
        </button>
        <button onclick="closeDetailModal()" class="h-9 px-5 rounded-full bg-navy text-white text-xs font-semibold hover:bg-navy-dark cursor-pointer">
            Tutup
        </button>
    </div>
</aside>
@endsection

@push('scripts')
<script>
    async function applyVacancy(id, title) {
        const btn = document.getElementById('btn-apply-' + id);
        if (!btn) return;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        btn.disabled = true;
        btn.innerHTML = `<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin inline"></i> Mengirim…`;
        lucide.createIcons();

        try {
            const res = await fetch(`/bkk/me/daftar/${id}`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": csrfToken
                }
            });

            const data = await res.json();

            if (!res.ok) {
                btn.disabled = false;
                btn.innerHTML = `Lamar Sekarang`;
                showToast(data.message || 'Gagal mengajukan lamaran.');
                return;
            }

            btn.innerHTML = `<i data-lucide="check" class="w-3.5 h-3.5 inline"></i> Terkirim`;
            btn.className = "h-8 px-4 rounded-full bg-emerald-100 text-emerald-800 border border-emerald-300 text-xs font-semibold cursor-not-allowed";
            lucide.createIcons();
            showToast(data.message || `Lamaran untuk "${title}" berhasil dikirimkan.`);
        } catch (err) {
            btn.disabled = false;
            btn.innerHTML = `Lamar Sekarang`;
            showToast('Terjadi kesalahan jaringan saat mengajukan lamaran.');
        }
    }

    function openDetailModal(app) {
        const drawer = document.getElementById('detailDrawer');
        const backdrop = document.getElementById('detailDrawerBackdrop');
        const body = document.getElementById('detailDrawerBody');
        if (!drawer || !backdrop || !body) return;

        let interviewHtml = '';
        if (app.interview) {
            interviewHtml = `
                <div class="rounded-2xl bg-maroon text-white p-5 relative overflow-hidden shadow-sm">
                    <div class="absolute -right-8 -bottom-8 w-32 h-32 rounded-full bg-white/10 pointer-events-none"></div>
                    <div class="text-[11px] uppercase tracking-wider text-white/80 font-bold">Jadwal Interview & Wawancara</div>
                    <div class="mt-3 space-y-2 text-xs relative">
                        <div class="flex items-center gap-2.5"><i data-lucide="calendar" class="w-4 h-4 text-white/70"></i><span>${escapeHtml(app.interview.date)}</span></div>
                        <div class="flex items-center gap-2.5"><i data-lucide="clock" class="w-4 h-4 text-white/70"></i><span>${escapeHtml(app.interview.time)}</span></div>
                        <div class="flex items-center gap-2.5"><i data-lucide="map-pin" class="w-4 h-4 text-white/70"></i><span>${escapeHtml(app.interview.mode)} — ${escapeHtml(app.interview.place)}</span></div>
                        <div class="flex items-center gap-2.5"><i data-lucide="user" class="w-4 h-4 text-white/70"></i><span>PIC: ${escapeHtml(app.interview.pic)}</span></div>
                    </div>
                    <div class="flex gap-2 mt-4 relative">
                        <button onclick="showToast('Kehadiran interview berhasil dikonfirmasi')" class="h-8 px-4 rounded-full bg-white text-maroon text-xs font-bold hover:bg-canvas cursor-pointer shadow-sm">Konfirmasi Hadir</button>
                    </div>
                </div>
            `;
        }

        let timelineItemsHtml = '';
        if (app.timeline && app.timeline.length) {
            timelineItemsHtml = app.timeline.map((t, idx) => `
                <li class="pl-6 relative">
                    <span class="absolute -left-[9px] top-0.5 w-4 h-4 rounded-full bg-white grid place-items-center">
                        <i data-lucide="${t.done ? 'check-circle-2' : 'circle'}" class="w-4 h-4 ${t.done ? 'text-brand-ink' : 'text-line'}"></i>
                    </span>
                    <div class="text-xs font-semibold ${t.done ? 'text-brand-ink' : 'text-muted'}">${escapeHtml(t.title)}</div>
                    <div class="text-[11px] text-muted leading-relaxed">${escapeHtml(t.desc)}</div>
                    ${t.date ? `<div class="text-[10px] text-muted/70 mt-0.5">${escapeHtml(t.date)}</div>` : ''}
                </li>
            `).join('');
        }

        const validColor = /^#[0-9a-fA-F]{6}$/.test(app.color || '') ? app.color : '#241012';
        const companyShort = escapeHtml(String(app.company || '').substring(0, 2).toUpperCase());

        body.innerHTML = `
            <div class="flex gap-4 items-center">
                <div class="w-13 h-13 rounded-2xl grid place-items-center text-white font-bold text-base shrink-0 shadow-sm" style="width: 52px; height: 52px; background: ${validColor}">
                    ${companyShort}
                </div>
                <div class="min-w-0">
                    <h3 class="font-bold text-base text-brand-ink leading-snug">${escapeHtml(app.position)}</h3>
                    <div class="text-xs text-muted mt-0.5">${escapeHtml(app.company)} · ${escapeHtml(app.location)}</div>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold bg-navy text-white">${escapeHtml(app.status)}</span>
                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-medium bg-brand-softmist text-muted">${escapeHtml(app.type)}</span>
                    </div>
                </div>
            </div>

            ${interviewHtml}

            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-muted mb-3">Log Tahapan Seleksi</div>
                <ol class="relative ml-2 border-l-2 border-line space-y-4">
                    ${timelineItemsHtml}
                </ol>
            </div>

            <div class="rounded-2xl bg-brand-paper border border-line p-4 text-xs">
                <div class="font-semibold text-brand-ink mb-1 flex items-center gap-1.5">
                    <i data-lucide="message-square" class="w-3.5 h-3.5 text-muted"></i>
                    <span>Catatan BKK Penus</span>
                </div>
                <p class="text-muted leading-relaxed">
                    Pastikan Anda mempersiapkan CV tercetak, kelengkapan berkas identitas, dan portofolio kejuruan. Hubungi admin BKK jika ada pertanyaan lebih lanjut.
                </p>
            </div>
        `;

        lucide.createIcons({ root: body });

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

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDetailModal();
        }
    });
</script>
@endpush
