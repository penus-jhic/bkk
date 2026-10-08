@php
    $activeMitra = $currentMitra ?? $mitra ?? null;
    $mNama = $activeMitra?->nama_perusahaan ?? 'Mitra Industri';
    $mInitials = mb_strtoupper($activeMitra?->singkatan ?: collect(preg_split('/\s+/', trim($mNama)))->reject(fn ($part) => in_array(mb_strtoupper(rtrim($part, '.')), ['PT', 'CV', 'TBK']))->take(2)->map(fn ($part) => mb_substr($part, 0, 1))->implode('')) ?: 'MI';
    $mNpwp = $activeMitra?->npwp ?? '-';
    $mLogo = $activeMitra?->logo_url;
    $mVerified = (bool) ($activeMitra?->is_verified ?? false);
    $mitraAlerts ??= ['items' => [], 'total' => 0];
@endphp

<header class="sticky top-0 z-30 h-16 bg-white/95 backdrop-blur border-b border-brand-ink/10 flex items-center gap-2 px-3 sm:px-5">
    <!-- Tombol menu (hanya mobile; di desktop sidebar selalu tampil) -->
    <button type="button" onclick="toggleMitraSidebar()" class="lg:hidden w-10 h-10 rounded-full grid place-items-center hover:bg-brand-softmist cursor-pointer text-brand-ink transition-colors" aria-label="Buka menu">
        <i data-lucide="menu" class="w-5 h-5"></i>
    </button>

    <!-- Brand -->
    <a href="{{ route('bkk.mitra.dashboard') }}" class="flex items-center gap-2.5 group shrink-0" title="Dashboard Mitra IDUKA">
        <img src="{{ asset('images/logosmkpenus.png') }}" alt="Logo SMK Plus Pelita Nusantara" class="w-9 h-9 object-contain group-hover:scale-105 transition-transform" />
        <span class="hidden sm:flex flex-col">
            <span class="font-display text-[15px] font-bold uppercase tracking-wide text-brand-ink leading-tight group-hover:text-brand-darkred transition-colors">BKK Pelita Nusantara</span>
            <span class="text-[10px] uppercase font-semibold tracking-wider text-brand-darkred mt-0.5">Portal Mitra IDUKA</span>
        </span>
    </a>

    <!-- Pencarian lowongan milik mitra -->
    <div class="flex-1 max-w-xl mx-auto hidden md:block">
        <form action="{{ route('bkk.mitra.lowongan.index') }}" method="GET" role="search">
            <label class="flex items-center gap-3 h-10 rounded-full bg-brand-paper border border-brand-ink/10 focus-within:bg-white focus-within:border-brand-darkred focus-within:ring-4 focus-within:ring-brand-darkred/10 px-4 transition-all">
                <i data-lucide="search" class="w-4 h-4 text-muted"></i>
                <input
                    type="search"
                    name="q"
                    value="{{ request()->routeIs('bkk.mitra.lowongan.index') ? request('q') : '' }}"
                    placeholder="Cari lowongan berdasarkan posisi, jurusan, atau lokasi…"
                    aria-label="Cari lowongan"
                    class="flex-1 bg-transparent outline-none text-sm placeholder:text-muted text-brand-ink"
                />
            </label>
        </form>
    </div>

    <!-- Aksi kanan -->
    <div class="ml-auto flex items-center gap-1.5 sm:gap-2.5">
        <a href="{{ route('bkk.index') }}" target="_blank" rel="noopener" class="hidden sm:inline-flex items-center gap-1.5 h-9 px-3.5 rounded-full text-xs font-semibold text-brand-ink bg-white border border-brand-ink/15 hover:bg-brand-softmist transition-colors">
            <i data-lucide="external-link" class="w-3.5 h-3.5 text-brand-darkred"></i>
            <span>Web Publik</span>
        </a>

        <!-- Notifikasi: antrean rekrutmen nyata dari database -->
        <div class="relative" id="mitraNotifDropdownContainer">
            <button type="button" onclick="toggleMitraNotifDropdown()" class="relative w-10 h-10 rounded-full grid place-items-center hover:bg-brand-softmist cursor-pointer text-brand-ink transition-colors" aria-label="Notifikasi ({{ $mitraAlerts['total'] }})" aria-haspopup="true">
                <i data-lucide="bell" class="w-5 h-5"></i>
                @if($mitraAlerts['total'] > 0)
                    <span class="absolute top-1 right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-brand-darkred text-white text-[10px] font-bold grid place-items-center ring-2 ring-white">{{ $mitraAlerts['total'] > 99 ? '99+' : $mitraAlerts['total'] }}</span>
                @endif
            </button>

            <div id="mitraNotifDropdown" class="hidden absolute right-0 mt-2 w-[min(22rem,calc(100vw-1.5rem))] bg-white rounded-card border border-brand-ink/10 shadow-softpill overflow-hidden fade-up z-50">
                <div class="px-4 py-3 border-b border-brand-ink/10 flex justify-between items-center bg-brand-paper">
                    <span class="font-display uppercase tracking-wide text-sm text-brand-ink">Perlu Tindakan</span>
                    <span class="text-[11px] text-brand-darkred font-bold bg-brand-darkred/10 px-2 py-0.5 rounded-full">{{ $mitraAlerts['total'] }} antrean</span>
                </div>
                <div class="divide-y divide-brand-ink/5 max-h-80 overflow-y-auto">
                    @forelse($mitraAlerts['items'] as $alert)
                        <a href="{{ $alert['href'] }}" class="flex gap-3 px-4 py-3 hover:bg-brand-paper transition-colors">
                            <span class="mt-0.5 min-w-7 h-7 px-1.5 rounded-full bg-brand-darkred text-white text-xs font-bold grid place-items-center shrink-0">{{ $alert['count'] }}</span>
                            <span>
                                <span class="block text-sm font-semibold text-brand-ink">{{ $alert['title'] }}</span>
                                <span class="block text-xs text-muted mt-0.5">{{ $alert['desc'] }}</span>
                            </span>
                        </a>
                    @empty
                        <div class="px-4 py-8 text-center">
                            <i data-lucide="check-circle-2" class="w-8 h-8 text-emerald-600 mx-auto mb-2"></i>
                            <p class="text-sm font-semibold text-brand-ink">Semua beres</p>
                            <p class="text-xs text-muted mt-0.5">Tidak ada lamaran yang menunggu tinjauan.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Profil perusahaan -->
        <div class="relative" id="mitraProfileDropdownContainer">
            <button type="button" onclick="toggleMitraProfileDropdown()" class="flex items-center gap-2 h-10 pl-1 pr-1 sm:pr-3 rounded-full hover:bg-brand-softmist cursor-pointer transition-colors" aria-label="Menu profil perusahaan" aria-haspopup="true">
                @if($mLogo)
                    <img src="{{ $mLogo }}" alt="" class="w-8 h-8 rounded-full object-cover border border-brand-ink/10 bg-white" />
                @else
                    <span class="w-8 h-8 rounded-full grid place-items-center text-white text-[10px] font-bold bg-brand-darkred">{{ mb_substr($mInitials, 0, 3) }}</span>
                @endif
                <span class="hidden sm:block text-left leading-tight">
                    <span class="block text-xs font-bold text-brand-ink max-w-[160px] truncate">{{ $mNama }}</span>
                    <span class="block text-[10px] font-semibold uppercase tracking-wider text-muted">Mitra IDUKA</span>
                </span>
            </button>

            <div id="mitraProfileDropdown" class="hidden absolute right-0 mt-2 w-72 bg-white rounded-card border border-brand-ink/10 shadow-softpill p-4 fade-up z-50">
                <div class="text-center pb-3 border-b border-brand-ink/10">
                    @if($mLogo)
                        <img src="{{ $mLogo }}" alt="" class="w-14 h-14 mx-auto rounded-full object-cover border border-brand-ink/10 bg-white" />
                    @else
                        <div class="w-14 h-14 mx-auto rounded-full grid place-items-center text-white text-base font-bold bg-brand-darkred">{{ mb_substr($mInitials, 0, 3) }}</div>
                    @endif
                    <div class="mt-2 font-bold text-brand-ink text-sm">{{ $mNama }}</div>
                    <div class="text-xs text-muted font-mono">NPWP {{ $mNpwp }}</div>
                    @if($mVerified)
                        <div class="mt-2 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            <i data-lucide="badge-check" class="w-3.5 h-3.5"></i> Mitra Terverifikasi
                        </div>
                    @else
                        <div class="mt-2 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                            Menunggu verifikasi BKK
                        </div>
                    @endif
                </div>

                <div class="mt-3 space-y-0.5">
                    <a href="{{ route('bkk.mitra.dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-medium text-brand-ink hover:bg-brand-paper transition-colors">
                        <i data-lucide="layout-dashboard" class="w-4 h-4 text-muted"></i>
                        <span>Dashboard Mitra</span>
                    </a>
                    <a href="{{ route('bkk.mitra.pengaturan') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-medium text-brand-ink hover:bg-brand-paper transition-colors">
                        <i data-lucide="settings" class="w-4 h-4 text-muted"></i>
                        <span>Pengaturan Akun & Logo</span>
                    </a>
                    <a href="{{ route('bkk.index') }}" target="_blank" rel="noopener" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-medium text-brand-ink hover:bg-brand-paper transition-colors">
                        <i data-lucide="external-link" class="w-4 h-4 text-muted"></i>
                        <span>Web Publik BKK</span>
                    </a>
                    <form action="{{ route('bkk.mitra.logout') }}" method="POST" class="pt-1 mt-1 border-t border-brand-ink/10">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-bold text-brand-darkred hover:bg-brand-darkred/5 transition-colors cursor-pointer">
                            <i data-lucide="log-out" class="w-4 h-4"></i>
                            <span>Keluar Sesi</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    function toggleMitraNotifDropdown() {
        document.getElementById('mitraProfileDropdown')?.classList.add('hidden');
        document.getElementById('mitraNotifDropdown')?.classList.toggle('hidden');
    }

    function toggleMitraProfileDropdown() {
        document.getElementById('mitraNotifDropdown')?.classList.add('hidden');
        document.getElementById('mitraProfileDropdown')?.classList.toggle('hidden');
    }

    document.addEventListener('click', function (e) {
        const notifContainer = document.getElementById('mitraNotifDropdownContainer');
        const profContainer = document.getElementById('mitraProfileDropdownContainer');
        if (notifContainer && !notifContainer.contains(e.target)) {
            document.getElementById('mitraNotifDropdown')?.classList.add('hidden');
        }
        if (profContainer && !profContainer.contains(e.target)) {
            document.getElementById('mitraProfileDropdown')?.classList.add('hidden');
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        document.getElementById('mitraNotifDropdown')?.classList.add('hidden');
        document.getElementById('mitraProfileDropdown')?.classList.add('hidden');
    });
</script>
