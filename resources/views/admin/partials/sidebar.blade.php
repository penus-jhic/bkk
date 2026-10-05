@php
    $role = strtoupper($authUser['role'] ?? 'ADMIN');
    $nip = $authUser['nomor_induk'] ?? 'ADM-2026-001';
@endphp

<!-- Mobile Sidebar Backdrop -->
<div id="adminSidebarBackdrop" class="fixed inset-0 bg-black/40 z-40 hidden lg:hidden" onclick="toggleAdminSidebar()"></div>

<!-- Main Aside Navigation: Fixed on mobile, Sticky on desktop locked to viewport height -->
<aside id="adminSidebar" class="w-[272px] shrink-0 fixed lg:sticky top-16 h-[calc(100vh-4rem)] left-0 z-40 bg-[#f8f9fa] border-r border-line flex flex-col justify-between transform -translate-x-full lg:translate-x-0 transition-transform duration-200">
    <!-- Top Scrollable Nav Area -->
    <div class="flex-1 overflow-y-auto p-3 space-y-4">
        <!-- Top Action Card: Tulis Berita Baru -->
        <div class="px-1">
            <a href="{{ route('bkk.admin.berita.create') }}" class="flex items-center gap-3 h-14 rounded-2xl bg-white border border-line shadow-sm hover:shadow-md transition-all px-4 group">
                <div class="w-8 h-8 rounded-xl bg-maroon/10 grid place-items-center text-maroon group-hover:bg-maroon group-hover:text-white transition-colors">
                    <i data-lucide="pen-tool" class="w-4 h-4"></i>
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-semibold text-navy leading-tight">Tulis Berita Baru</span>
                    <span class="text-[11px] text-muted">Publikasi warta & agenda</span>
                </div>
            </a>
        </div>

        <!-- Main Navigation Group -->
        <nav class="space-y-1">
            <div class="px-4 pt-2 pb-1 text-[11px] font-bold uppercase tracking-wider text-muted/80">
                Menu Utama
            </div>

            <a href="{{ route('bkk.admin.index') }}"
               class="group flex items-center gap-3.5 h-11 px-4 rounded-full text-sm font-medium transition-colors {{ (request()->routeIs('bkk.admin.index') || request()->routeIs('bkk.dashboard.index')) ? 'bg-navy text-white shadow-sm' : 'text-navy hover:bg-navy/5' }}">
                <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0 {{ (request()->routeIs('bkk.admin.index') || request()->routeIs('bkk.dashboard.index')) ? 'text-white' : 'text-muted group-hover:text-navy' }}"></i>
                <span class="truncate">Dashboard</span>
            </a>

            <a href="{{ route('bkk.admin.berita.index') }}"
               class="group flex items-center gap-3.5 h-11 px-4 rounded-full text-sm font-medium transition-colors {{ request()->routeIs('*berita.index*') ? 'bg-navy text-white shadow-sm' : 'text-navy hover:bg-navy/5' }}">
                <i data-lucide="newspaper" class="w-4 h-4 shrink-0 {{ request()->routeIs('*berita.index*') ? 'text-white' : 'text-muted group-hover:text-navy' }}"></i>
                <span class="truncate">Kelola Berita</span>
            </a>

            <a href="{{ route('bkk.admin.berita.create') }}"
               class="group flex items-center gap-3.5 h-11 px-4 rounded-full text-sm font-medium transition-colors {{ request()->routeIs('*berita.create*') ? 'bg-navy text-white shadow-sm' : 'text-navy hover:bg-navy/5' }}">
                <i data-lucide="plus-circle" class="w-4 h-4 shrink-0 {{ request()->routeIs('*berita.create*') ? 'text-white' : 'text-muted group-hover:text-navy' }}"></i>
                <span class="truncate">Tulis Berita</span>
            </a>
        </nav>

        <!-- Modul Manajemen BKK Sesuai SITEMAP.md -->
        <nav class="space-y-1 pt-2">
            <div class="px-4 pb-1 text-[11px] font-bold uppercase tracking-wider text-muted/80 flex items-center gap-2">
                <span>Manajemen BKK</span>
                <span class="h-px flex-1 bg-line"></span>
            </div>

            <a href="{{ route('bkk.admin.tracer.index') }}"
               class="group flex items-center justify-between h-10 px-4 rounded-full text-xs font-medium transition-colors {{ request()->routeIs('*tracer*') ? 'bg-navy text-white shadow-sm' : 'text-navy hover:bg-navy/5' }}">
                <div class="flex items-center gap-3 truncate">
                    <i data-lucide="graduation-cap" class="w-4 h-4 shrink-0 {{ request()->routeIs('*tracer*') ? 'text-white' : 'text-muted group-hover:text-navy' }}"></i>
                    <span class="truncate">Tracer Study</span>
                </div>
            </a>

            <a href="{{ route('bkk.admin.mitra.index') }}"
               class="group flex items-center justify-between h-10 px-4 rounded-full text-xs font-medium transition-colors {{ request()->routeIs('*admin.mitra*') ? 'bg-navy text-white shadow-sm' : 'text-navy hover:bg-navy/5' }}">
                <div class="flex items-center gap-3 truncate">
                    <i data-lucide="building-2" class="w-4 h-4 shrink-0 {{ request()->routeIs('*admin.mitra*') ? 'text-white' : 'text-muted group-hover:text-navy' }}"></i>
                    <span class="truncate">Mitra IDUKA</span>
                </div>
            </a>

            <a href="{{ route('bkk.admin.lowongan.index') }}"
               class="group flex items-center justify-between h-10 px-4 rounded-full text-xs font-medium transition-colors {{ request()->routeIs('*admin.lowongan*') ? 'bg-navy text-white shadow-sm' : 'text-navy hover:bg-navy/5' }}">
                <div class="flex items-center gap-3 truncate">
                    <i data-lucide="briefcase" class="w-4 h-4 shrink-0 {{ request()->routeIs('*admin.lowongan*') ? 'text-white' : 'text-muted group-hover:text-navy' }}"></i>
                    <span class="truncate">Lowongan Kerja</span>
                </div>
            </a>

            <a href="{{ route('bkk.admin.pkl.monitoring') }}"
               class="group flex items-center justify-between h-10 px-4 rounded-full text-xs font-medium transition-colors {{ request()->routeIs('*admin.pkl*') ? 'bg-navy text-white shadow-sm' : 'text-navy hover:bg-navy/5' }}">
                <div class="flex items-center gap-3 truncate">
                    <i data-lucide="activity" class="w-4 h-4 shrink-0 {{ request()->routeIs('*admin.pkl*') ? 'text-white' : 'text-muted group-hover:text-navy' }}"></i>
                    <span class="truncate">Monitoring PKL</span>
                </div>
            </a>
        </nav>

        <!-- Pintas Dashboard Admin -->
        <nav class="space-y-1 pt-2">
            <div class="px-4 pb-1 text-[11px] font-bold uppercase tracking-wider text-muted/80 flex items-center gap-2">
                <span>Pintas Dashboard</span>
                <span class="h-px flex-1 bg-line"></span>
            </div>

            <!-- 1. CMS Portal Utama -->
            <a href="/admin"
               class="group flex items-center justify-between h-10 px-4 rounded-full text-xs font-medium text-navy hover:bg-navy/5 transition-colors">
                <div class="flex items-center gap-3 truncate">
                    <i data-lucide="globe" class="w-4 h-4 shrink-0 text-muted group-hover:text-navy"></i>
                    <span class="truncate">CMS Portal Utama</span>
                </div>
                <i data-lucide="arrow-up-right" class="w-3.5 h-3.5 text-muted group-hover:text-navy shrink-0"></i>
            </a>

            <!-- 2. Admin BKK (Saat Ini Aktif) -->
            <div class="flex items-center justify-between h-10 px-4 rounded-full text-xs font-semibold bg-navy text-white shadow-xs">
                <div class="flex items-center gap-3 truncate">
                    <i data-lucide="briefcase" class="w-4 h-4 shrink-0 text-white"></i>
                    <span class="truncate">Admin BKK</span>
                </div>
                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">Aktif</span>
            </div>

            <!-- 3. Admin PPDB -->
            <a href="/ppdb/dashboard"
               class="group flex items-center justify-between h-10 px-4 rounded-full text-xs font-medium text-navy hover:bg-navy/5 transition-colors">
                <div class="flex items-center gap-3 truncate">
                    <i data-lucide="graduation-cap" class="w-4 h-4 shrink-0 text-muted group-hover:text-navy"></i>
                    <span class="truncate">Admin PPDB</span>
                </div>
                <i data-lucide="arrow-up-right" class="w-3.5 h-3.5 text-muted group-hover:text-navy shrink-0"></i>
            </a>
        </nav>

        <!-- Situs Publik -->
        <nav class="space-y-1 pt-2">
            <div class="px-4 pb-1 text-[11px] font-bold uppercase tracking-wider text-muted/80 flex items-center gap-2">
                <span>Situs Publik</span>
                <span class="h-px flex-1 bg-line"></span>
            </div>

            <a href="{{ url('/bkk') }}" target="_blank"
               class="group flex items-center justify-between h-10 px-4 rounded-full text-xs font-medium text-navy hover:bg-navy/5 transition-colors">
                <div class="flex items-center gap-3 truncate">
                    <i data-lucide="external-link" class="w-4 h-4 shrink-0 text-muted group-hover:text-navy"></i>
                    <span class="truncate">Web Publik BKK</span>
                </div>
                <i data-lucide="arrow-up-right" class="w-3.5 h-3.5 text-muted group-hover:text-navy shrink-0"></i>
            </a>
        </nav>
    </div>

</aside>

<script>
    function toggleAdminSidebar() {
        const sidebar = document.getElementById('adminSidebar');
        const backdrop = document.getElementById('adminSidebarBackdrop');
        if (!sidebar || !backdrop) return;

        const isHidden = sidebar.classList.contains('-translate-x-full');
        if (isHidden) {
            sidebar.classList.remove('-translate-x-full');
            backdrop.classList.remove('hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
            backdrop.classList.add('hidden');
        }
    }
</script>
