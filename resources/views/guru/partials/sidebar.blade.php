@php
    $navGroups = [
        'Menu Utama' => [
            ['label' => 'Dashboard', 'icon' => 'layout-dashboard', 'route' => 'bkk.guru.dashboard', 'active' => ['bkk.guru.dashboard']],
            ['label' => 'Direktori Siswa & CV', 'icon' => 'file-text', 'route' => 'bkk.guru.siswa.index', 'active' => ['bkk.guru.siswa.*']],
        ],
        'Bimbingan & Persetujuan' => [
            ['label' => 'Persetujuan PKL (Accept)', 'icon' => 'check-check', 'route' => 'bkk.guru.pengajuan.index', 'active' => ['bkk.guru.pengajuan.*']],
            ['label' => 'Validasi Jurnal PKL', 'icon' => 'notebook-pen', 'route' => 'bkk.guru.jurnal.index', 'active' => ['bkk.guru.jurnal.*']],
            ['label' => 'Review Laporan Akhir', 'icon' => 'book-open', 'route' => 'bkk.guru.laporan.index', 'active' => ['bkk.guru.laporan.*']],
        ],
        'Referensi & Portal' => [
            ['label' => 'Monitoring Admin BKK', 'icon' => 'activity', 'route' => 'bkk.admin.pkl.monitoring', 'active' => ['bkk.admin.pkl.*']],
            ['label' => 'Katalog Lowongan PKL', 'icon' => 'briefcase', 'route' => 'bkk.lowongan', 'active' => ['bkk.lowongan*'], 'external' => true],
        ],
    ];
@endphp

<!-- Backdrop sidebar mobile -->
<div id="guruSidebarBackdrop" class="fixed inset-0 bg-brand-ink/40 z-40 hidden lg:hidden" onclick="toggleGuruSidebar()"></div>

<aside id="guruSidebar" class="w-[264px] shrink-0 fixed lg:sticky top-16 h-[calc(100vh-4rem)] left-0 z-40 bg-white border-r border-brand-ink/10 flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform duration-200" aria-label="Navigasi portal guru">
    <div class="flex-1 overflow-y-auto p-3 space-y-5">
        <!-- Banner Singkat Guru -->
        <div class="p-3.5 rounded-2xl bg-gradient-to-br from-emerald-800 to-teal-900 text-white shadow-xs">
            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-emerald-200">
                <i data-lucide="shield-check" class="w-4 h-4"></i>
                <span>Guru Pembimbing</span>
            </div>
            <div class="text-sm font-bold mt-1 text-white">SMK Plus Pelita Nusantara</div>
            <p class="text-[11px] text-emerald-100/80 mt-1 leading-snug">Meninjau CV, validasi berkas PKL, dan pemantauan jurnal harian siswa.</p>
        </div>

        @foreach($navGroups as $groupLabel => $items)
            <nav class="space-y-0.5">
                <div class="px-3 pb-1.5 flex items-center gap-2">
                    <span class="font-display text-[11px] font-semibold uppercase tracking-[0.12em] text-emerald-800">{{ $groupLabel }}</span>
                    <span class="h-px flex-1 bg-brand-ink/10"></span>
                </div>
                @foreach($items as $item)
                    @php
                        $isActive = request()->routeIs(...($item['active'] ?? []));
                        $isExternal = $item['external'] ?? false;
                    @endphp
                    <a href="{{ $isExternal ? route($item['route']) : route($item['route']) }}"
                       @if($isExternal) target="_blank" rel="noopener" @endif
                       @if($isActive) aria-current="page" @endif
                       class="group flex items-center justify-between gap-2 h-10 px-3 rounded-xl text-[13px] transition-colors {{ $isActive ? 'bg-emerald-50 text-emerald-900 font-bold border border-emerald-200/60' : 'text-brand-ink font-medium hover:bg-brand-paper' }}">
                        <span class="flex items-center gap-3 min-w-0">
                            <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4 shrink-0 {{ $isActive ? 'text-emerald-700' : 'text-muted group-hover:text-brand-ink' }}"></i>
                            <span class="truncate">{{ $item['label'] }}</span>
                        </span>
                        @if($isExternal)
                            <i data-lucide="external-link" class="w-3 h-3 text-muted"></i>
                        @endif
                    </a>
                @endforeach
            </nav>
        @endforeach
    </div>

    <!-- Info Akun di Kaki Sidebar -->
    <div class="p-3 border-t border-brand-ink/10">
        <div class="rounded-xl bg-brand-paper p-3 border border-brand-ink/10 flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-emerald-700 text-white grid place-items-center font-bold text-xs shrink-0">
                <i data-lucide="user-check" class="w-4 h-4"></i>
            </div>
            <div class="min-w-0 flex-1">
                <div class="text-xs font-bold text-navy truncate">{{ $authUser['nama_lengkap'] ?? 'Bpk/Ibu Guru' }}</div>
                <div class="text-[10px] text-muted truncate">Peran: {{ $authUser['role'] ?? 'GURU' }}</div>
            </div>
        </div>
    </div>
</aside>

<script>
    function toggleGuruSidebar() {
        const sidebar = document.getElementById('guruSidebar');
        const backdrop = document.getElementById('guruSidebarBackdrop');
        if (sidebar) sidebar.classList.toggle('-translate-x-full');
        if (backdrop) backdrop.classList.toggle('hidden');
    }
</script>
