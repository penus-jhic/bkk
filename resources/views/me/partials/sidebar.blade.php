@php
    $role = strtoupper($authUser['role'] ?? 'SISWA');
    $isSiswa = ($role === 'SISWA');
@endphp

<!-- Mobile Sidebar Backdrop -->
<div id="meSidebarBackdrop" class="fixed inset-0 bg-black/40 z-40 hidden lg:hidden" onclick="toggleMeSidebar()"></div>

<!-- Main Aside Navigation: Fixed on mobile, Sticky on desktop locked to viewport height -->
<aside id="meSidebar" class="w-[272px] shrink-0 fixed lg:sticky top-16 h-[calc(100vh-4rem)] left-0 z-40 bg-[#f8f9fa] border-r border-line flex flex-col justify-between transform -translate-x-full lg:translate-x-0 transition-transform duration-200">
    <!-- Top Scrollable Nav Area -->
    <div class="flex-1 overflow-y-auto p-3 space-y-4">
        <!-- Top Action Card: Optimalkan CV -->
        <div class="px-1">
            <a href="{{ route('bkk.me.cv.edit') }}" class="flex items-center gap-3 h-14 rounded-2xl bg-white border border-line shadow-sm hover:shadow-md transition-all px-4 group">
                <div class="w-8 h-8 rounded-xl bg-maroon/10 grid place-items-center text-maroon group-hover:bg-maroon group-hover:text-white transition-colors">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                </div>
                <div class="flex flex-col">
                    <span class="text-sm font-semibold text-navy leading-tight">Optimalkan CV</span>
                    <span class="text-[11px] text-muted">Didukung kecerdasan AI</span>
                </div>
            </a>
        </div>

        <!-- Main Navigation Group -->
        <nav class="space-y-1">
            <div class="px-4 pt-2 pb-1 text-[11px] font-bold uppercase tracking-wider text-muted/80">
                Menu Utama
            </div>

            <a href="{{ route('bkk.me.index') }}"
               class="group flex items-center gap-3.5 h-11 px-4 rounded-full text-sm font-medium transition-colors {{ request()->routeIs('bkk.me.index') ? 'bg-navy text-white shadow-sm' : 'text-navy hover:bg-navy/5' }}">
                <i data-lucide="layout-dashboard" class="w-4 h-4 shrink-0 {{ request()->routeIs('bkk.me.index') ? 'text-white' : 'text-muted group-hover:text-navy' }}"></i>
                <span class="truncate">Dashboard</span>
            </a>

            <a href="{{ route('bkk.me.lamaran') }}"
               class="group flex items-center gap-3.5 h-11 px-4 rounded-full text-sm font-medium transition-colors {{ request()->routeIs('bkk.me.lamaran') ? 'bg-navy text-white shadow-sm' : 'text-navy hover:bg-navy/5' }}">
                <i data-lucide="briefcase" class="w-4 h-4 shrink-0 {{ request()->routeIs('bkk.me.lamaran') ? 'text-white' : 'text-muted group-hover:text-navy' }}"></i>
                <span class="truncate">Riwayat Lamaran</span>
            </a>

            <a href="{{ route('bkk.me.cv') }}"
               class="group flex items-center gap-3.5 h-11 px-4 rounded-full text-sm font-medium transition-colors {{ request()->routeIs('bkk.me.cv') ? 'bg-navy text-white shadow-sm' : 'text-navy hover:bg-navy/5' }}">
                <i data-lucide="file-text" class="w-4 h-4 shrink-0 {{ request()->routeIs('bkk.me.cv') ? 'text-white' : 'text-muted group-hover:text-navy' }}"></i>
                <span class="truncate">CV & AI Scoring</span>
            </a>

            <a href="{{ route('bkk.me.cv.edit') }}"
               class="group flex items-center gap-3.5 h-11 px-4 rounded-full text-sm font-medium transition-colors {{ request()->routeIs('bkk.me.cv.edit') ? 'bg-navy text-white shadow-sm' : 'text-navy hover:bg-navy/5' }}">
                <i data-lucide="file-pen-line" class="w-4 h-4 shrink-0 {{ request()->routeIs('bkk.me.cv.edit') ? 'text-white' : 'text-muted group-hover:text-navy' }}"></i>
                <span class="truncate">Editor CV</span>
                <span class="ml-auto text-[10px] font-bold rounded-full px-2 py-0.5 bg-maroon text-white flex items-center gap-0.5">
                    <i data-lucide="sparkles" class="w-2.5 h-2.5"></i> AI
                </span>
            </a>
        </nav>

        <!-- Khusus Siswa PKL Navigation (Conditional Role SISWA) -->
        @if($isSiswa)
            <nav class="space-y-1 pt-2">
                <div class="px-4 pb-1 text-[11px] font-bold uppercase tracking-wider text-muted/80 flex items-center gap-2">
                    <span>Khusus Siswa PKL</span>
                    <span class="h-px flex-1 bg-line"></span>
                </div>

                <a href="{{ route('bkk.me.jurnal') }}"
                   class="group flex items-center gap-3.5 h-11 px-4 rounded-full text-sm font-medium transition-colors {{ request()->routeIs('bkk.me.jurnal') ? 'bg-navy text-white shadow-sm' : 'text-navy hover:bg-navy/5' }}">
                    <i data-lucide="notebook-pen" class="w-4 h-4 shrink-0 {{ request()->routeIs('bkk.me.jurnal') ? 'text-white' : 'text-muted group-hover:text-navy' }}"></i>
                    <span class="truncate">Jurnal PKL</span>
                </a>

                <a href="{{ route('bkk.me.laporan') }}"
                   class="group flex items-center gap-3.5 h-11 px-4 rounded-full text-sm font-medium transition-colors {{ request()->routeIs('bkk.me.laporan') ? 'bg-navy text-white shadow-sm' : 'text-navy hover:bg-navy/5' }}">
                    <i data-lucide="book-open" class="w-4 h-4 shrink-0 {{ request()->routeIs('bkk.me.laporan') ? 'text-white' : 'text-muted group-hover:text-navy' }}"></i>
                    <span class="truncate">Laporan Akhir</span>
                </a>
            </nav>
        @endif
    </div>

    <!-- Pinned Bottom Status Card: Selalu berada di bawah pada elemen Sidebar -->
    <div class="p-3 pt-0 shrink-0">
        <div class="p-4 rounded-2xl bg-navy text-white relative overflow-hidden shadow-sm">
            <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full bg-maroon/60 blur-sm pointer-events-none"></div>
            <div class="relative">
                <div class="flex items-center justify-between text-xs text-white/70 font-medium">
                    <span>{{ $isSiswa ? 'Status PKL' : 'Status Karier' }}</span>
                    @if($isSiswa)
                        @php
                            $code = $profile['pkl_status']['code'] ?? 'BELUM_PKL';
                        @endphp
                        <span class="w-2 h-2 rounded-full {{ $code === 'SEDANG_PKL' ? 'bg-emerald-400 animate-pulse' : ($code === 'SUDAH_PKL' ? 'bg-emerald-300' : 'bg-amber-400') }}"></span>
                    @endif
                </div>
                <div class="text-sm font-semibold mt-0.5 leading-snug">
                    {{ $profile['status'] ?? ($isSiswa ? 'Belum PKL' : 'Pencari Kerja') }}
                </div>
                @if($isSiswa && !empty($profile['pkl_status']['mitra_nama']))
                    <div class="text-[11px] text-white/80 mt-1 truncate">
                        {{ $profile['pkl_status']['mitra_nama'] }}
                    </div>
                @endif
                <div class="text-[11px] text-white/60 mt-2 font-mono">
                    {{ $profile['kelas'] ?? ($isSiswa ? 'XII' : 'Alumni') }} · NIS {{ $profile['nis'] ?? '-' }}
                </div>
            </div>
        </div>
    </div>
</aside>

<script>
    function toggleMeSidebar() {
        const sidebar = document.getElementById('meSidebar');
        const backdrop = document.getElementById('meSidebarBackdrop');
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
