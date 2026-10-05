@extends('me.master')

@section('title', 'Riwayat Lamaran - BKK SMK Plus Pelita Nusantara')

@section('content')
<div class="space-y-5 fade-up">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-navy tracking-tight">Riwayat Lamaran</h1>
            <p class="text-xs sm:text-sm text-muted">Pantau seluruh progres dan riwayat berkas lamaran kerja & PKL Anda.</p>
        </div>

        <div class="flex items-center gap-2">
            <!-- Search bar -->
            <label class="flex items-center gap-2 h-10 rounded-full bg-white border border-line px-4 w-full sm:w-72 focus-within:shadow-md transition">
                <i data-lucide="search" class="w-4 h-4 text-muted"></i>
                <input
                    type="text"
                    id="lamaranSearch"
                    placeholder="Cari posisi / perusahaan…"
                    oninput="filterApplications()"
                    class="flex-1 outline-none text-xs sm:text-sm bg-transparent text-navy"
                />
            </label>

            <!-- Table / Grid Toggle -->
            <div class="hidden md:flex rounded-full border border-line bg-white p-0.5 shadow-sm">
                <button id="btnViewTable" onclick="switchView('table')" class="w-9 h-9 rounded-full grid place-items-center bg-navy text-white cursor-pointer transition-colors" title="Tampilan Tabel">
                    <i data-lucide="list" class="w-4 h-4"></i>
                </button>
                <button id="btnViewGrid" onclick="switchView('grid')" class="w-9 h-9 rounded-full grid place-items-center text-muted hover:text-navy cursor-pointer transition-colors" title="Tampilan Grid">
                    <i data-lucide="layout-grid" class="w-4 h-4"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Filter Status Chips -->
    @php
        $filters = ['Semua', 'Terkirim', 'Sedang Ditinjau', 'Dipanggil Interview', 'Diterima', 'Ditolak'];
        $counts = [
            'Semua' => count($applications),
            'Terkirim' => count(array_filter($applications, fn($a) => $a['status'] === 'Terkirim')),
            'Sedang Ditinjau' => count(array_filter($applications, fn($a) => $a['status'] === 'Sedang Ditinjau')),
            'Dipanggil Interview' => count(array_filter($applications, fn($a) => $a['status'] === 'Dipanggil Interview')),
            'Diterima' => count(array_filter($applications, fn($a) => $a['status'] === 'Diterima')),
            'Ditolak' => count(array_filter($applications, fn($a) => $a['status'] === 'Ditolak')),
        ];
    @endphp

    <div class="flex gap-2 overflow-x-auto pb-1 -mx-1 px-1">
        @foreach($filters as $f)
            <button
                onclick="setFilter('{{ $f }}')"
                data-filter="{{ $f }}"
                class="filter-chip inline-flex items-center gap-1.5 rounded-full px-4 h-8 text-xs font-semibold border transition-all whitespace-nowrap cursor-pointer {{ $loop->first ? 'bg-navy text-white border-navy active' : 'bg-white text-navy border-line hover:bg-canvas' }}"
            >
                <span>{{ $f }}</span>
                <span class="count-pill text-[10px] rounded-full px-1.5 min-w-5 {{ $loop->first ? 'bg-white/20 text-white' : 'bg-[#f1f3f4] text-muted' }}">
                    {{ $counts[$f] ?? 0 }}
                </span>
            </button>
        @endforeach
    </div>

    <!-- Empty State -->
    <div id="emptyState" class="{{ empty($applications) ? '' : 'hidden' }} p-12 bg-white border border-line rounded-3xl text-center shadow-sm">
        <div class="w-14 h-14 rounded-full bg-line/40 grid place-items-center mx-auto text-muted mb-3">
            <i data-lucide="inbox" class="w-7 h-7"></i>
        </div>
        <div class="font-bold text-navy text-base">Tidak ada lamaran ditemukan</div>
        <div class="text-xs text-muted mt-1">Anda belum memiliki riwayat lamaran atau belum ada lamaran yang cocok dengan filter saat ini.</div>
        <button onclick="resetFilters()" class="mt-4 h-9 px-5 rounded-full border border-line bg-white text-navy text-xs font-semibold hover:bg-canvas cursor-pointer shadow-sm">
            Reset Filter
        </button>
    </div>

    <!-- Table View Container -->
    <div id="tableViewContainer" class="{{ empty($applications) ? 'hidden' : '' }} bg-white border border-line rounded-3xl overflow-hidden shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs sm:text-sm">
                <thead class="bg-[#f8f9fa] border-b border-line text-muted text-[11px] uppercase tracking-wider font-semibold">
                    <tr>
                        <th class="px-5 py-3.5">Posisi & Perusahaan</th>
                        <th class="px-5 py-3.5 hidden sm:table-cell">Kategori Mitra</th>
                        <th class="px-5 py-3.5">Tanggal Lamar</th>
                        <th class="px-5 py-3.5">Status Seleksi</th>
                        <th class="px-5 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line/60" id="applicationTableBody">
                    @foreach($applications as $app)
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
                        <tr class="app-row hover:bg-canvas/80 transition-colors"
                            data-search="{{ strtolower($app['position'] . ' ' . $app['company'] . ' ' . $app['mitra'] . ' ' . $app['location']) }}"
                            data-status="{{ $app['status'] }}">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl grid place-items-center text-white font-bold text-xs shrink-0 shadow-sm" style="background: {{ $app['color'] }}">
                                        {{ strtoupper(substr($app['company'], 0, 2)) }}
                                    </div>
                                    <div>
                                        <div class="font-semibold text-navy text-sm">{{ $app['position'] }}</div>
                                        <div class="text-xs text-muted mt-0.5">{{ $app['company'] }} · {{ $app['location'] }} · <span class="font-medium text-navy/70">{{ $app['type'] }}</span></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-muted hidden sm:table-cell">{{ $app['mitra'] }}</td>
                            <td class="px-5 py-4 text-muted whitespace-nowrap">{{ $app['date'] }}</td>
                            <td class="px-5 py-4 whitespace-nowrap">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusBadgeClass }}">
                                    {{ $app['status'] }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right whitespace-nowrap">
                                <button onclick='openDetailModal(@json($app))' class="h-8 px-4 rounded-full border border-line bg-white hover:bg-canvas text-navy text-xs font-semibold transition-colors shadow-sm cursor-pointer">
                                    Detail Seleksi
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Grid View Container -->
    <div id="gridViewContainer" class="hidden grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($applications as $app)
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
            <div class="app-card p-5 bg-white border border-line rounded-2xl hover:shadow-md transition-shadow flex flex-col justify-between"
                 data-search="{{ strtolower($app['position'] . ' ' . $app['company'] . ' ' . $app['mitra'] . ' ' . $app['location']) }}"
                 data-status="{{ $app['status'] }}">
                <div>
                    <div class="flex items-start justify-between gap-3">
                        <div class="w-11 h-11 rounded-xl grid place-items-center text-white font-bold text-sm shrink-0 shadow-sm" style="background: {{ $app['color'] }}">
                            {{ strtoupper(substr($app['company'], 0, 2)) }}
                        </div>
                        <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $statusBadgeClass }}">
                            {{ $app['status'] }}
                        </span>
                    </div>

                    <div class="mt-3">
                        <h3 class="font-bold text-sm text-navy leading-snug">{{ $app['position'] }}</h3>
                        <div class="text-xs text-muted mt-1">{{ $app['company'] }} · {{ $app['location'] }}</div>
                        <div class="text-[11px] text-muted/80 mt-0.5">{{ $app['mitra'] }}</div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-line flex items-center justify-between text-xs">
                    <span class="text-muted">{{ $app['date'] }}</span>
                    <button onclick='openDetailModal(@json($app))' class="font-semibold text-maroon hover:underline cursor-pointer">
                        Lihat Progres &rarr;
                    </button>
                </div>
            </div>
        @endforeach
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
    let activeFilter = 'Semua';

    function switchView(mode) {
        const tableView = document.getElementById('tableViewContainer');
        const gridView = document.getElementById('gridViewContainer');
        const btnTable = document.getElementById('btnViewTable');
        const btnGrid = document.getElementById('btnViewGrid');

        if (mode === 'table') {
            tableView.classList.remove('hidden');
            gridView.classList.add('hidden');
            btnTable.className = "w-9 h-9 rounded-full grid place-items-center bg-navy text-white cursor-pointer transition-colors";
            btnGrid.className = "w-9 h-9 rounded-full grid place-items-center text-muted hover:text-navy cursor-pointer transition-colors";
        } else {
            tableView.classList.add('hidden');
            gridView.classList.remove('hidden');
            btnGrid.className = "w-9 h-9 rounded-full grid place-items-center bg-navy text-white cursor-pointer transition-colors";
            btnTable.className = "w-9 h-9 rounded-full grid place-items-center text-muted hover:text-navy cursor-pointer transition-colors";
        }
    }

    function setFilter(status) {
        activeFilter = status;
        document.querySelectorAll('.filter-chip').forEach(btn => {
            const isTarget = btn.getAttribute('data-filter') === status;
            if (isTarget) {
                btn.className = "filter-chip inline-flex items-center gap-1.5 rounded-full px-4 h-8 text-xs font-semibold border transition-all whitespace-nowrap cursor-pointer bg-navy text-white border-navy active";
                btn.querySelector('.count-pill').className = "count-pill text-[10px] rounded-full px-1.5 min-w-5 bg-white/20 text-white";
            } else {
                btn.className = "filter-chip inline-flex items-center gap-1.5 rounded-full px-4 h-8 text-xs font-semibold border transition-all whitespace-nowrap cursor-pointer bg-white text-navy border-line hover:bg-canvas";
                btn.querySelector('.count-pill').className = "count-pill text-[10px] rounded-full px-1.5 min-w-5 bg-[#f1f3f4] text-muted";
            }
        });
        filterApplications();
    }

    function filterApplications() {
        const query = (document.getElementById('lamaranSearch')?.value || '').toLowerCase().trim();
        const rows = document.querySelectorAll('.app-row');
        const cards = document.querySelectorAll('.app-card');
        const emptyState = document.getElementById('emptyState');
        let visibleCount = 0;

        rows.forEach(r => {
            const matchesFilter = (activeFilter === 'Semua' || r.getAttribute('data-status') === activeFilter);
            const matchesSearch = !query || r.getAttribute('data-search').includes(query);
            if (matchesFilter && matchesSearch) {
                r.style.display = '';
                visibleCount++;
            } else {
                r.style.display = 'none';
            }
        });

        cards.forEach(c => {
            const matchesFilter = (activeFilter === 'Semua' || c.getAttribute('data-status') === activeFilter);
            const matchesSearch = !query || c.getAttribute('data-search').includes(query);
            c.style.display = (matchesFilter && matchesSearch) ? '' : 'none';
        });

        if (visibleCount === 0) {
            emptyState.classList.remove('hidden');
            document.getElementById('tableViewContainer').classList.add('hidden');
            document.getElementById('gridViewContainer').classList.add('hidden');
        } else {
            emptyState.classList.add('hidden');
            const isTableActive = !document.getElementById('btnViewTable').classList.contains('text-muted');
            if (isTableActive) {
                document.getElementById('tableViewContainer').classList.remove('hidden');
            } else {
                document.getElementById('gridViewContainer').classList.remove('hidden');
            }
        }
    }

    function resetFilters() {
        const searchInput = document.getElementById('lamaranSearch');
        if (searchInput) searchInput.value = '';
        setFilter('Semua');
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
