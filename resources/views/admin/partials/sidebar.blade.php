@php
    $adminAlerts ??= ['by_key' => []];
    $badge = fn (string ...$keys) => array_sum(array_map(fn ($key) => $adminAlerts['by_key'][$key] ?? 0, $keys));

    // active = pola nama route yang menandai menu aktif, except = pola yang dikecualikan
    $navGroups = [
        'Menu Utama' => [
            ['label' => 'Dashboard', 'icon' => 'layout-dashboard', 'route' => 'bkk.admin.index', 'active' => ['bkk.admin.index']],
            ['label' => 'Kelola Berita', 'icon' => 'newspaper', 'route' => 'bkk.admin.berita.index', 'active' => ['bkk.admin.berita.*'], 'except' => ['bkk.admin.berita.create']],
            ['label' => 'Tulis Berita', 'icon' => 'pen-line', 'route' => 'bkk.admin.berita.create', 'active' => ['bkk.admin.berita.create']],
        ],
        'Manajemen BKK' => [
            ['label' => 'Lowongan Kerja & PKL', 'icon' => 'briefcase', 'route' => 'bkk.admin.lowongan.index', 'active' => ['bkk.admin.lowongan.*']],
            ['label' => 'Mitra IDUKA', 'icon' => 'building-2', 'route' => 'bkk.admin.mitra.index', 'active' => ['bkk.admin.mitra.*'], 'except' => ['bkk.admin.mitra.permohonan.*']],
            ['label' => 'Permohonan Kerja Sama', 'icon' => 'handshake', 'route' => 'bkk.admin.mitra.permohonan.index', 'active' => ['bkk.admin.mitra.permohonan.*'], 'badge' => $badge('permohonan')],
            ['label' => 'Monitoring PKL', 'icon' => 'activity', 'route' => 'bkk.admin.pkl.monitoring', 'active' => ['bkk.admin.pkl.*'], 'badge' => $badge('jurnal', 'laporan')],
            ['label' => 'Tracer Study', 'icon' => 'graduation-cap', 'route' => 'bkk.admin.tracer.index', 'active' => ['bkk.admin.tracer.*']],
        ],
    ];
@endphp

<!-- Backdrop sidebar mobile -->
<div id="adminSidebarBackdrop" class="fixed inset-0 bg-brand-ink/40 z-40 hidden lg:hidden" onclick="toggleAdminSidebar()"></div>

<aside id="adminSidebar" class="w-[264px] shrink-0 fixed lg:sticky top-16 h-[calc(100vh-4rem)] left-0 z-40 bg-white border-r border-brand-ink/10 flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform duration-200" aria-label="Navigasi admin">
    <div class="flex-1 overflow-y-auto p-3 space-y-5">
        <!-- Aksi utama -->
        <a href="{{ route('bkk.admin.berita.create') }}" class="flex items-center gap-3 h-12 rounded-full bg-gradient-to-r from-brand-signal to-brand-darkred text-white px-4 shadow-[0_4px_14px_0_rgba(122,16,24,0.25)] hover:shadow-[0_6px_20px_0_rgba(122,16,24,0.35)] hover:-translate-y-px transition-all">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span class="text-sm font-semibold">Tulis Berita Baru</span>
        </a>

        @foreach($navGroups as $groupLabel => $items)
            <nav class="space-y-0.5">
                <div class="px-3 pb-1.5 flex items-center gap-2">
                    <span class="font-display text-[11px] font-semibold uppercase tracking-[0.12em] text-brand-darkred">{{ $groupLabel }}</span>
                    <span class="h-px flex-1 bg-brand-ink/10"></span>
                </div>
                @foreach($items as $item)
                    @php
                        $isActive = request()->routeIs(...$item['active']) && ! (isset($item['except']) && request()->routeIs(...$item['except']));
                    @endphp
                    <a href="{{ route($item['route']) }}"
                       @if($isActive) aria-current="page" @endif
                       class="group flex items-center justify-between gap-2 h-10 px-3 rounded-xl text-[13px] transition-colors {{ $isActive ? 'bg-navy text-white font-semibold shadow-sm' : 'text-brand-ink font-medium hover:bg-brand-paper' }}">
                        <span class="flex items-center gap-3 min-w-0">
                            <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4 shrink-0 {{ $isActive ? 'text-white' : 'text-muted group-hover:text-brand-ink' }}"></i>
                            <span class="truncate">{{ $item['label'] }}</span>
                        </span>
                        @if(!empty($item['badge']))
                            <span class="min-w-5 h-5 px-1.5 rounded-full bg-brand-darkred text-white text-[10px] font-bold grid place-items-center shrink-0">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
        @endforeach

        @include('partials.dashboard-shortcuts', ['current' => 'bkk', 'currentHref' => route('bkk.admin.index')])
    </div>

    <!-- Kaki sidebar -->
    <div class="p-3 border-t border-brand-ink/10">
        <a href="{{ route('bkk.index') }}" target="_blank" rel="noopener" class="group block rounded-card bg-gradient-to-br from-brand-darkred to-brand-deepred text-white shadow-softpill p-4 relative overflow-hidden">
            <span class="text-[10px] font-semibold uppercase tracking-wider text-[#F5C2C7]">Situs Publik</span>
            <span class="mt-1 flex items-center justify-between font-display text-base uppercase tracking-wide">
                Web BKK Penus
                <i data-lucide="arrow-up-right" class="w-4 h-4 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform"></i>
            </span>
        </a>
    </div>
</aside>

<script>
    function toggleAdminSidebar() {
        const sidebar = document.getElementById('adminSidebar');
        const backdrop = document.getElementById('adminSidebarBackdrop');
        if (!sidebar || !backdrop) return;

        const willOpen = sidebar.classList.contains('-translate-x-full');
        sidebar.classList.toggle('-translate-x-full', !willOpen);
        backdrop.classList.toggle('hidden', !willOpen);
        document.body.classList.toggle('overflow-hidden', willOpen);
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !document.getElementById('adminSidebar')?.classList.contains('-translate-x-full')) {
            toggleAdminSidebar();
        }
    });
</script>
