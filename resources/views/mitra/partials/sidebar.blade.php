@php
    $mitraAlerts ??= ['by_key' => []];
    $activeMitra = $currentMitra ?? $mitra ?? null;

    // active = pola nama route yang menandai menu aktif, except = pola yang dikecualikan
    $navGroups = [
        'Rekrutmen' => [
            ['label' => 'Dashboard', 'icon' => 'layout-dashboard', 'href' => route('bkk.mitra.dashboard'), 'active' => ['bkk.mitra.dashboard']],
            ['label' => 'Lowongan & Pelamar', 'icon' => 'briefcase', 'href' => route('bkk.mitra.lowongan.index'), 'active' => ['bkk.mitra.lowongan.*', 'bkk.mitra.pelamar.*'], 'except' => ['bkk.mitra.lowongan.create'], 'badge' => $mitraAlerts['by_key']['pelamar'] ?? 0],
            ['label' => 'Pasang Lowongan', 'icon' => 'plus-circle', 'href' => route('bkk.mitra.lowongan.create'), 'active' => ['bkk.mitra.lowongan.create']],
        ],
        'Akun Perusahaan' => [
            ['label' => 'Pengaturan Akun', 'icon' => 'settings', 'href' => route('bkk.mitra.pengaturan'), 'active' => ['bkk.mitra.pengaturan*']],
        ],
    ];

    $externalLinks = [
        ['label' => 'Panduan Kerja Sama', 'icon' => 'handshake', 'href' => route('bkk.kerjasama')],
        ['label' => 'Katalog Lowongan Publik', 'icon' => 'globe', 'href' => route('bkk.lowongan')],
    ];

    $mouSelesai = $activeMitra?->tanggal_mou_selesai ? \Illuminate\Support\Carbon::parse($activeMitra->tanggal_mou_selesai) : null;
@endphp

<!-- Backdrop sidebar mobile -->
<div id="mitraSidebarBackdrop" class="fixed inset-0 bg-brand-ink/40 z-40 hidden lg:hidden" onclick="toggleMitraSidebar()"></div>

<aside id="mitraSidebar" class="w-[264px] shrink-0 fixed lg:sticky top-16 h-[calc(100vh-4rem)] left-0 z-40 bg-white border-r border-brand-ink/10 flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform duration-200" aria-label="Navigasi mitra">
    <div class="flex-1 overflow-y-auto p-3 space-y-5">
        <!-- Aksi utama -->
        <a href="{{ route('bkk.mitra.lowongan.create') }}" class="flex items-center gap-3 h-12 rounded-full bg-gradient-to-r from-brand-signal to-brand-darkred text-white px-4 shadow-[0_4px_14px_0_rgba(122,16,24,0.25)] hover:shadow-[0_6px_20px_0_rgba(122,16,24,0.35)] hover:-translate-y-px transition-all">
            <i data-lucide="plus" class="w-4 h-4"></i>
            <span class="text-sm font-semibold">Pasang Lowongan Baru</span>
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
                    <a href="{{ $item['href'] }}"
                       @if($isActive) aria-current="page" @endif
                       class="group flex items-center justify-between gap-2 h-10 px-3 rounded-xl text-[13px] transition-colors {{ $isActive ? 'bg-brand-paper text-brand-darkred font-bold' : 'text-brand-ink font-medium hover:bg-brand-paper' }}">
                        <span class="flex items-center gap-3 min-w-0">
                            <i data-lucide="{{ $item['icon'] }}" class="w-4 h-4 shrink-0 {{ $isActive ? 'text-brand-darkred' : 'text-muted group-hover:text-brand-ink' }}"></i>
                            @if($isActive)
                                <x-sketch.underline size="sm" class="truncate">{{ $item['label'] }}</x-sketch.underline>
                            @else
                                <span class="truncate">{{ $item['label'] }}</span>
                            @endif
                        </span>
                        @if(!empty($item['badge']))
                            <span class="min-w-5 h-5 px-1.5 rounded-full bg-brand-darkred text-white text-[10px] font-bold grid place-items-center shrink-0" title="Lamaran baru belum ditinjau">{{ $item['badge'] }}</span>
                        @endif
                    </a>
                @endforeach
            </nav>
        @endforeach

        <nav class="space-y-0.5">
            <div class="px-3 pb-1.5 flex items-center gap-2">
                <span class="font-display text-[11px] font-semibold uppercase tracking-[0.12em] text-brand-darkred">Layanan BKK</span>
                <span class="h-px flex-1 bg-brand-ink/10"></span>
            </div>
            @foreach($externalLinks as $item)
                <a href="{{ $item['href'] }}" target="_blank" rel="noopener" class="group flex items-center justify-between h-10 px-3 rounded-xl text-[13px] font-medium text-brand-ink hover:bg-brand-paper transition-colors">
                    <span class="flex items-center gap-3"><i data-lucide="{{ $item['icon'] }}" class="w-4 h-4 text-muted group-hover:text-brand-ink"></i>{{ $item['label'] }}</span>
                    <i data-lucide="arrow-up-right" class="w-3.5 h-3.5 text-muted group-hover:text-brand-darkred"></i>
                </a>
            @endforeach
        </nav>
    </div>

    <!-- Kaki sidebar: status kemitraan -->
    <div class="p-3 border-t border-brand-ink/10">
        <div class="rounded-card bg-brand-ink text-white p-4">
            <span class="flex items-center gap-1.5 text-[10px] font-semibold uppercase tracking-wider text-[#F5C2C7]">
                <span class="w-1.5 h-1.5 rounded-full {{ $activeMitra?->is_verified ? 'bg-emerald-400' : 'bg-amber-400' }}"></span>
                {{ $activeMitra?->status_kemitraan ?? 'Status Kemitraan' }}
            </span>
            <span class="mt-1 block font-display text-base uppercase tracking-wide truncate" title="{{ $activeMitra?->nama_perusahaan }}">{{ $activeMitra?->nama_perusahaan ?? 'Mitra IDUKA' }}</span>
            <span class="mt-2 pt-2 border-t border-white/15 flex items-center justify-between gap-2 text-[11px] text-white/70">
                <span class="truncate">PIC: {{ $activeMitra?->pic_name ?? '-' }}</span>
                @if($mouSelesai)
                    <span class="shrink-0">MoU s.d. {{ $mouSelesai->translatedFormat('M Y') }}</span>
                @endif
            </span>
        </div>
    </div>
</aside>

<script>
    function toggleMitraSidebar() {
        const sidebar = document.getElementById('mitraSidebar');
        const backdrop = document.getElementById('mitraSidebarBackdrop');
        if (!sidebar || !backdrop) return;

        const willOpen = sidebar.classList.contains('-translate-x-full');
        sidebar.classList.toggle('-translate-x-full', !willOpen);
        backdrop.classList.toggle('hidden', !willOpen);
        document.body.classList.toggle('overflow-hidden', willOpen);
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !document.getElementById('mitraSidebar')?.classList.contains('-translate-x-full')) {
            toggleMitraSidebar();
        }
    });
</script>
