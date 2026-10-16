{{-- Pintas Dashboard: perpindahan antar panel admin sekolah (CMS landing page, BKK, PPDB).
     Isi & tampilan berkas ini sama persis di bkk/ dan ppdb/ (partials/dashboard-shortcuts.blade.php), dan versi React-nya
     ada di lpfe/src/admin/components/DashboardShortcuts.tsx. Ubah ketiganya bersamaan.
     Parameter: $current = 'cms' | 'bkk' | 'ppdb', $currentHref = URL beranda panel yang sedang dibuka. --}}
@php
    $dashboardShortcuts = [
        'cms' => ['label' => 'CMS Portal Utama', 'desc' => 'Web & konten', 'icon' => 'globe'],
        'bkk' => ['label' => 'Admin BKK', 'desc' => 'Bursa kerja & karier', 'icon' => 'briefcase'],
        'ppdb' => ['label' => 'Admin PPDB', 'desc' => 'Penerimaan siswa', 'icon' => 'graduation-cap'],
    ];
@endphp
<nav class="space-y-1" aria-label="Pintas dashboard">
    <div class="px-3 pb-1.5 flex items-center gap-2">
        <span class="font-display text-[11px] font-semibold uppercase tracking-[0.12em] text-brand-darkred">Pintas Dashboard</span>
        <span class="h-px flex-1 bg-brand-ink/10"></span>
    </div>
    @foreach($dashboardShortcuts as $key => $item)
        @if($key === $current)
            <a href="{{ $currentHref }}" aria-current="page" class="flex items-center gap-3 px-2.5 py-2 rounded-xl bg-brand-ink text-white shadow-sm">
                <span class="w-8 h-8 rounded-lg bg-white/10 grid place-items-center shrink-0"><i data-lucide="{{ $item['icon'] }}" class="w-4 h-4"></i></span>
                <span class="min-w-0 flex-1 leading-tight">
                    <span class="block text-[13px] font-semibold truncate">{{ $item['label'] }}</span>
                    <span class="block text-[11px] text-white/60 truncate">{{ $item['desc'] }}</span>
                </span>
                <span class="shrink-0 inline-flex items-center gap-1 text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded-full bg-emerald-400/15 text-emerald-300">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>Aktif
                </span>
            </a>
        @else
            <a href="{{ config("app.{$key}_admin_url") }}" class="group flex items-center gap-3 px-2.5 py-2 rounded-xl text-brand-ink hover:bg-brand-paper transition-colors">
                <span class="w-8 h-8 rounded-lg bg-brand-paper border border-brand-ink/10 grid place-items-center shrink-0 text-brand-ink/60 group-hover:text-brand-darkred group-hover:border-brand-darkred/20 transition-colors"><i data-lucide="{{ $item['icon'] }}" class="w-4 h-4"></i></span>
                <span class="min-w-0 flex-1 leading-tight">
                    <span class="block text-[13px] font-semibold truncate">{{ $item['label'] }}</span>
                    <span class="block text-[11px] text-brand-ink/50 truncate">{{ $item['desc'] }}</span>
                </span>
                <i data-lucide="arrow-up-right" class="w-3.5 h-3.5 shrink-0 text-brand-ink/40 group-hover:text-brand-darkred transition-colors"></i>
            </a>
        @endif
    @endforeach
</nav>
