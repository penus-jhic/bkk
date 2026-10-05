@extends('me.master')

@section('title', 'Dashboard Siswa & Alumni - BKK SMK Plus Pelita Nusantara')

@php
    $role = strtoupper($authUser['role'] ?? 'SISWA');
    $isSiswa = ($role === 'SISWA');
    $activeCount = count($activeApplications ?? []);
    $interviewCount = count($interviews ?? []);
    $completion = $profile['completion'] ?? 75;
    $hour = (int) date('H');
    $greet = ($hour < 11) ? 'Selamat pagi' : (($hour < 15) ? 'Selamat siang' : (($hour < 18) ? 'Selamat sore' : 'Selamat malam'));
@endphp

@section('content')
<div class="space-y-6 fade-up">
    <!-- Greeting Banner -->
    <div class="p-6 sm:p-8 bg-white border border-line rounded-3xl relative overflow-hidden shadow-sm">
        <div class="absolute right-0 top-0 h-full w-1/2 pointer-events-none hidden md:block">
            <div class="absolute right-10 top-6 w-40 h-40 rounded-full bg-navy/5"></div>
            <div class="absolute right-40 bottom-[-40px] w-32 h-32 rounded-full bg-maroon/10"></div>
            <div class="absolute right-16 bottom-8 w-14 h-14 rounded-2xl bg-line/40 rotate-12"></div>
        </div>

        <div class="relative flex flex-col md:flex-row md:items-center gap-6">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                    <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $isSiswa ? 'bg-navy text-white' : 'bg-maroon text-white' }}">
                        {{ $isSiswa ? 'Siswa PKL' : 'Alumni' }}
                    </span>
                    <span class="text-xs text-muted font-medium">{{ $profile['jurusan'] ?? 'SMK Plus Pelita Nusantara' }}</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold text-navy tracking-tight">
                    Halo, {{ $profile['name'] }} 👋
                </h1>
                <p class="text-muted mt-1.5 text-sm sm:text-base leading-relaxed">
                    {{ $greet }}!
                    @if($interviewCount > 0)
                        Anda punya <b class="text-maroon">{{ $interviewCount }} undangan interview</b> yang menunggu konfirmasi kehadiran.
                    @else
                        Terus pantau status lamaran kerja/PKL dan rekomendasi lowongan mitra industri terbaru.
                    @endif
                </p>
            </div>

            <div class="md:w-80 bg-[#f8f9fa] rounded-2xl border border-line p-4">
                <div class="flex justify-between text-sm mb-2 font-medium">
                    <span class="text-navy">Kelengkapan Profil</span>
                    <span class="font-bold text-navy">{{ $completion }}%</span>
                </div>
                <div class="h-2 rounded-full bg-line/60 overflow-hidden">
                    <div class="h-full rounded-full transition-all duration-700 {{ $completion >= 85 ? 'bg-navy' : 'bg-maroon' }}" style="width: {{ $completion }}%"></div>
                </div>
                <a href="{{ route('bkk.me.cv.edit') }}" class="text-xs text-maroon font-semibold mt-3 inline-flex items-center gap-1 hover:underline">
                    <span>Lengkapi profil sekarang</span>
                    <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- 4 Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <!-- Metric 1: Lamaran Aktif -->
        <div class="p-5 bg-white border border-line rounded-2xl hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-sm text-muted font-medium">Lamaran Aktif</span>
                <div class="w-9 h-9 rounded-full bg-navy/8 grid place-items-center text-navy">
                    <i data-lucide="briefcase" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-3xl font-bold text-navy mt-3">{{ $activeCount }}</div>
            <div class="flex items-center gap-1 text-xs mt-1 text-emerald-700 font-medium">
                <i data-lucide="trending-up" class="w-3.5 h-3.5"></i>
                <span>+2 dari minggu lalu</span>
            </div>
            <div class="flex items-end gap-1 h-8 mt-3">
                @foreach([30, 45, 20, 60, 50, 75, 95] as $bar)
                    <div class="flex-1 rounded-sm bg-navy" style="height: {{ $bar }}%; opacity: {{ 0.3 + ($loop->index * 0.1) }};"></div>
                @endforeach
            </div>
        </div>

        <!-- Metric 2: Panggilan Interview -->
        <div class="p-5 bg-gradient-to-br from-white to-maroon/5 border border-maroon/30 rounded-2xl hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-sm text-maroon font-semibold">Panggilan Interview</span>
                <div class="w-9 h-9 rounded-full bg-maroon grid place-items-center text-white">
                    <i data-lucide="calendar-check" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-3xl font-bold text-maroon mt-3">{{ $interviewCount }}</div>
            @if(!empty($interviews[0]['interview']))
                <div class="text-xs mt-1 text-muted">
                    Terdekat: <b class="text-navy">{{ $interviews[0]['company'] }}</b> · {{ explode(',', $interviews[0]['interview']['date'])[1] ?? '' }}
                </div>
            @else
                <div class="text-xs mt-1 text-muted">Belum ada jadwal wawancara baru</div>
            @endif
            <div class="mt-4 flex -space-x-2">
                @foreach($interviews as $inv)
                    <div class="w-7 h-7 rounded-lg text-white font-bold text-[10px] grid place-items-center ring-2 ring-white shadow-sm" style="background: {{ $inv['color'] }}">
                        {{ strtoupper(substr($inv['company'], 0, 2)) }}
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Metric 3: CV Health Score -->
        <div class="p-5 bg-white gemini-border rounded-2xl hover:shadow-md transition-shadow relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-sm text-muted font-medium">CV Health Score</span>
                <i data-lucide="sparkles" class="w-5 h-5 text-maroon"></i>
            </div>
            <div class="text-3xl font-bold text-navy mt-3">
                {{ $cvScore['total'] ?? 82 }}<span class="text-base text-muted font-normal">/100</span>
            </div>
            <div class="h-2 rounded-full bg-line/60 overflow-hidden mt-2">
                <div class="h-full rounded-full transition-all duration-700" style="width: {{ $cvScore['total'] ?? 82 }}%; background: linear-gradient(90deg, #741918, #1b283b)"></div>
            </div>
            <a href="{{ route('bkk.me.cv') }}" class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-maroon hover:underline">
                <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                <span>Optimalkan dengan AI</span>
            </a>
        </div>

        <!-- Metric 4: Profil Dilihat Mitra -->
        <div class="p-5 bg-white border border-line rounded-2xl hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between">
                <span class="text-sm text-muted font-medium">Profil Dilihat Mitra</span>
                <div class="w-9 h-9 rounded-full bg-emerald-50 grid place-items-center text-emerald-700">
                    <i data-lucide="check-circle-2" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-3xl font-bold text-navy mt-3">{{ $isSiswa ? 23 : 41 }}</div>
            <div class="flex items-center gap-1 text-xs mt-1 text-emerald-700 font-medium">
                <i data-lucide="trending-up" class="w-3.5 h-3.5"></i>
                <span>+18% dalam 30 hari</span>
            </div>
            <div class="flex items-end gap-1 h-8 mt-3">
                @foreach([20, 35, 55, 45, 65, 80, 90] as $bar)
                    <div class="flex-1 rounded-sm bg-maroon" style="height: {{ $bar }}%; opacity: {{ 0.3 + ($loop->index * 0.1) }};"></div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Main Section: Tracker Lamaran + Rekomendasi Lowongan -->
    <div class="grid grid-cols-1 xl:grid-cols-5 gap-6">
        <!-- Left: Tracker Lamaran (2 cols) -->
        <div class="xl:col-span-2 space-y-4">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-navy">Tracker Lamaran</h2>
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
                            'Terkirim' => 'border border-navy text-navy bg-white',
                            'Sedang Ditinjau' => 'bg-amber-100 text-amber-800 border border-amber-200',
                            'Dipanggil Interview' => 'bg-maroon text-white border border-maroon',
                            'Diterima' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
                            'Ditolak' => 'bg-gray-100 text-muted border border-gray-200',
                            default => 'bg-gray-100 text-navy'
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
                                        <div class="font-semibold text-sm text-navy truncate">{{ $app['position'] }}</div>
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
                                            <span class="text-[10px] {{ $isDone ? 'text-navy font-semibold' : 'text-muted' }}">{{ $st }}</span>
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
                        <div class="font-bold text-sm text-navy">Belum Ada Lamaran Aktif</div>
                        <p class="text-xs text-muted max-w-xs mx-auto">Anda belum mengajukan lamaran atau pendaftaran PKL. Silakan jelajahi lowongan yang tersedia untuk mulai mendaftar.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Right: Rekomendasi Lowongan (3 cols) -->
        <div class="xl:col-span-3 space-y-4">
            <div class="flex items-end justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-navy">
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
                                    <div class="font-bold text-sm text-navy leading-snug truncate">{{ $v['title'] }}</div>
                                    <div class="text-xs text-muted mt-0.5">{{ $v['company'] }}</div>
                                </div>
                                <span class="rounded-full px-2 py-0.5 text-xs font-bold {{ $v['match'] >= 90 ? 'bg-maroon text-white' : 'bg-navy/10 text-navy' }}">
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
                                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-medium bg-[#f1f3f4] text-muted">{{ $tg }}</span>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-5 pt-3 border-t border-line flex items-center justify-between gap-2">
                            <button onclick="showToast('Lowongan disimpan ke bookmark')" class="text-xs text-muted hover:text-navy font-medium cursor-pointer transition-colors">
                                Simpan
                            </button>
                            <button id="btn-apply-{{ $v['id'] }}" onclick="applyVacancy('{{ $v['id'] }}', '{{ $v['title'] }}')" class="h-8 px-4 rounded-full bg-maroon text-white text-xs font-semibold hover:bg-maroon-dark transition-colors shadow-sm cursor-pointer">
                                Lamar Sekarang
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full p-8 bg-white border border-line rounded-2xl text-center space-y-2">
                        <div class="w-10 h-10 rounded-full bg-canvas border border-line flex items-center justify-center mx-auto text-muted">
                            <i data-lucide="briefcase" class="w-5 h-5"></i>
                        </div>
                        <div class="font-bold text-sm text-navy">Belum Ada Lowongan Aktif</div>
                        <p class="text-xs text-muted max-w-xs mx-auto">Saat ini belum ada lowongan atau posisi PKL aktif yang tersedia.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Slide-in Detail Drawer for Application Status -->
<div id="detailDrawerBackdrop" class="fixed inset-0 bg-navy/40 backdrop-blur-[1px] z-50 hidden transition-opacity duration-300" onclick="closeDetailModal()"></div>

<aside id="detailDrawer" class="fixed right-0 top-0 bottom-0 w-full sm:w-[460px] bg-white shadow-2xl flex flex-col z-50 transform translate-x-full transition-transform duration-300 ease-in-out hidden">
    <div class="flex items-center justify-between px-5 h-16 border-b border-line shrink-0">
        <span class="font-bold text-navy text-base">Detail Seleksi & Timeline</span>
        <button onclick="closeDetailModal()" class="w-9 h-9 rounded-full grid place-items-center hover:bg-canvas cursor-pointer text-muted" aria-label="Tutup">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto p-5 space-y-6" id="detailDrawerBody">
        <!-- Injected via JavaScript -->
    </div>

    <div class="p-4 border-t border-line flex gap-2 justify-end bg-canvas">
        <button onclick="window.print()" class="h-9 px-4 rounded-full border border-line text-navy bg-white text-xs font-semibold hover:bg-canvas inline-flex items-center gap-1.5 cursor-pointer">
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
                        <div class="flex items-center gap-2.5"><i data-lucide="calendar" class="w-4 h-4 text-white/70"></i><span>${app.interview.date}</span></div>
                        <div class="flex items-center gap-2.5"><i data-lucide="clock" class="w-4 h-4 text-white/70"></i><span>${app.interview.time}</span></div>
                        <div class="flex items-center gap-2.5"><i data-lucide="map-pin" class="w-4 h-4 text-white/70"></i><span>${app.interview.mode} — ${app.interview.place}</span></div>
                        <div class="flex items-center gap-2.5"><i data-lucide="user" class="w-4 h-4 text-white/70"></i><span>PIC: ${app.interview.pic}</span></div>
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
                        <i data-lucide="${t.done ? 'check-circle-2' : 'circle'}" class="w-4 h-4 ${t.done ? 'text-navy' : 'text-line'}"></i>
                    </span>
                    <div class="text-xs font-semibold ${t.done ? 'text-navy' : 'text-muted'}">${t.title}</div>
                    <div class="text-[11px] text-muted leading-relaxed">${t.desc}</div>
                    ${t.date ? `<div class="text-[10px] text-muted/70 mt-0.5">${t.date}</div>` : ''}
                </li>
            `).join('');
        }

        body.innerHTML = `
            <div class="flex gap-4 items-center">
                <div class="w-13 h-13 rounded-2xl grid place-items-center text-white font-bold text-base shrink-0 shadow-sm" style="width: 52px; height: 52px; background: ${app.color}">
                    ${app.company.substring(0, 2).toUpperCase()}
                </div>
                <div class="min-w-0">
                    <h3 class="font-bold text-base text-navy leading-snug">${app.position}</h3>
                    <div class="text-xs text-muted mt-0.5">${app.company} · ${app.location}</div>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold bg-navy text-white">${app.status}</span>
                        <span class="rounded-full px-2.5 py-0.5 text-[11px] font-medium bg-[#f1f3f4] text-muted">${app.type}</span>
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

            <div class="rounded-2xl bg-[#f8f9fa] border border-line p-4 text-xs">
                <div class="font-semibold text-navy mb-1 flex items-center gap-1.5">
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
