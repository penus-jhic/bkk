@php
    $role = strtoupper($authUser['role'] ?? 'SISWA');
    $isSiswa = ($role === 'SISWA');
    $name = $profile['name'] ?? ($authUser['nama_lengkap'] ?? $authUser['username'] ?? 'Pengguna');
    $initials = $profile['initials'] ?? (collect(preg_split('/\s+/', trim($name)))->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: 'PS');
    $notifItems = $notifications ?? [];
    $unreadCount = collect($notifItems)->where('unread', true)->count();
@endphp

<header class="sticky top-0 z-30 h-16 bg-white/95 backdrop-blur border-b border-brand-ink/10 flex items-center gap-2 px-3 sm:px-5">
    <!-- Tombol menu (hanya mobile; di desktop sidebar selalu tampil) -->
    <button type="button" onclick="toggleMeSidebar()" class="lg:hidden w-10 h-10 rounded-full grid place-items-center hover:bg-brand-softmist cursor-pointer text-brand-ink transition-colors" aria-label="Buka menu">
        <i data-lucide="menu" class="w-5 h-5"></i>
    </button>

    <!-- Brand -->
    <a href="{{ route('bkk.me.index') }}" class="flex items-center gap-2.5 group shrink-0" title="Dashboard Portal Siswa & Alumni">
        <img src="{{ asset('images/logosmkpenus.png') }}" alt="Logo SMK Plus Pelita Nusantara" class="w-9 h-9 object-contain group-hover:scale-105 transition-transform" />
        <span class="hidden sm:flex flex-col">
            <span class="font-display text-[15px] font-bold uppercase tracking-wide text-brand-ink leading-tight group-hover:text-brand-darkred transition-colors">BKK Pelita Nusantara</span>
            <span class="text-[10px] uppercase font-semibold tracking-wider text-brand-darkred mt-0.5">Portal {{ $isSiswa ? 'Siswa' : 'Alumni' }}</span>
        </span>
    </a>

    <!-- Pencarian lamaran -->
    <div class="flex-1 max-w-xl mx-auto hidden md:block">
        <form action="{{ route('bkk.me.lamaran') }}" method="GET" role="search">
            <label class="flex items-center gap-3 h-10 rounded-full bg-brand-paper border border-brand-ink/10 focus-within:bg-white focus-within:border-brand-darkred focus-within:ring-4 focus-within:ring-brand-darkred/10 px-4 transition-all">
                <i data-lucide="search" class="w-4 h-4 text-muted"></i>
                <input
                    type="search"
                    name="q"
                    value="{{ request()->routeIs('bkk.me.lamaran') ? request('q') : '' }}"
                    placeholder="Cari lowongan, perusahaan, atau lamaran…"
                    aria-label="Cari lamaran"
                    class="flex-1 bg-transparent outline-none text-sm placeholder:text-muted text-brand-ink"
                />
            </label>
        </form>
    </div>

    <!-- Aksi kanan -->
    <div class="ml-auto flex items-center gap-1.5 sm:gap-2.5">
        <a href="{{ route('bkk.lowongan') }}" target="_blank" rel="noopener" class="hidden sm:inline-flex items-center gap-1.5 h-9 px-3.5 rounded-full text-xs font-semibold text-brand-ink bg-white border border-brand-ink/15 hover:bg-brand-softmist transition-colors">
            <i data-lucide="external-link" class="w-3.5 h-3.5 text-brand-darkred"></i>
            <span>Cari Lowongan</span>
        </a>

        <!-- Notifikasi -->
        <div class="relative" id="notifDropdownContainer">
            <button type="button" onclick="toggleNotifDropdown()" class="relative w-10 h-10 rounded-full grid place-items-center hover:bg-brand-softmist cursor-pointer text-brand-ink transition-colors" aria-label="Notifikasi ({{ $unreadCount }} belum dibaca)" aria-haspopup="true">
                <i data-lucide="bell" class="w-5 h-5"></i>
                @if($unreadCount > 0)
                    <span class="absolute top-1 right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-brand-darkred text-white text-[10px] font-bold grid place-items-center ring-2 ring-white">{{ $unreadCount > 99 ? '99+' : $unreadCount }}</span>
                @endif
            </button>

            <div id="notifDropdown" class="hidden absolute right-0 mt-2 w-[min(22rem,calc(100vw-1.5rem))] bg-white rounded-card border border-brand-ink/10 shadow-softpill overflow-hidden fade-up z-50">
                <div class="px-4 py-3 border-b border-brand-ink/10 flex justify-between items-center bg-brand-paper">
                    <span class="font-display uppercase tracking-wide text-sm text-brand-ink">Notifikasi</span>
                    <span class="text-[11px] font-bold px-2 py-0.5 rounded-full {{ $unreadCount > 0 ? 'text-brand-darkred bg-brand-darkred/10' : 'text-emerald-700 bg-emerald-50' }}">{{ $unreadCount }} baru</span>
                </div>
                <div class="divide-y divide-brand-ink/5 max-h-80 overflow-y-auto">
                    @forelse($notifItems as $n)
                        <a href="{{ ($n['url'] ?? '#') !== '#' ? $n['url'] : route('bkk.me.lamaran') }}" class="flex gap-3 px-4 py-3 hover:bg-brand-paper transition-colors">
                            <span class="mt-1.5 w-2 h-2 rounded-full shrink-0 {{ ($n['unread'] ?? false) ? 'bg-brand-darkred' : 'bg-brand-ink/15' }}"></span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-brand-ink">{{ $n['title'] }}</span>
                                <span class="block text-xs text-muted mt-0.5 leading-relaxed">{{ $n['desc'] ?? '' }}</span>
                                <span class="block text-[11px] text-brand-ink/50 mt-1">{{ $n['time'] ?? '' }}</span>
                            </span>
                        </a>
                    @empty
                        <div class="px-4 py-8 text-center">
                            <i data-lucide="check-circle-2" class="w-8 h-8 text-emerald-600 mx-auto mb-2"></i>
                            <p class="text-sm font-semibold text-brand-ink">Belum ada notifikasi</p>
                            <p class="text-xs text-muted mt-0.5">Kabar lamaran & PKL akan muncul di sini.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Profil -->
        <div class="relative" id="profileDropdownContainer">
            <button type="button" onclick="toggleProfileDropdown()" class="flex items-center gap-2 h-10 pl-1 pr-1 sm:pr-3 rounded-full hover:bg-brand-softmist cursor-pointer transition-colors" aria-label="Menu profil" aria-haspopup="true">
                <span class="w-8 h-8 rounded-full grid place-items-center text-white text-xs font-bold bg-brand-darkred">{{ $initials }}</span>
                <span class="hidden sm:block text-left leading-tight">
                    <span class="block text-xs font-bold text-brand-ink max-w-[140px] truncate">{{ $name }}</span>
                    <span class="block text-[10px] font-semibold uppercase tracking-wider text-muted">{{ $isSiswa ? 'Siswa PKL' : 'Alumni' }}</span>
                </span>
            </button>

            <div id="profileDropdown" class="hidden absolute right-0 mt-2 w-72 bg-white rounded-card border border-brand-ink/10 shadow-softpill p-4 fade-up z-50">
                <div class="text-center pb-3 border-b border-brand-ink/10">
                    <div class="w-14 h-14 mx-auto rounded-full grid place-items-center text-white text-lg font-bold bg-brand-darkred">{{ $initials }}</div>
                    <div class="mt-2 font-bold text-brand-ink text-sm">{{ $name }}</div>
                    <div class="text-xs text-muted">{{ $profile['email'] ?? ($authUser['email'] ?? '-') }}</div>
                    @if(!empty($profile['jurusan']))
                        <div class="text-xs text-muted mt-0.5">{{ $profile['jurusan'] }}</div>
                    @endif
                    <div class="mt-2 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-brand-softmist text-brand-ink">
                        {{ $isSiswa ? 'SISWA' : 'ALUMNI' }} · NIS {{ $profile['nis'] ?? ($authUser['nomor_induk'] ?? '-') }}
                    </div>
                </div>

                <div class="mt-3 space-y-0.5">
                    <a href="{{ route('bkk.me.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-medium text-brand-ink hover:bg-brand-paper transition-colors">
                        <i data-lucide="layout-dashboard" class="w-4 h-4 text-muted"></i>
                        <span>Dashboard Saya</span>
                    </a>
                    <a href="{{ route('bkk.me.cv') }}" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-medium text-brand-ink hover:bg-brand-paper transition-colors">
                        <i data-lucide="file-text" class="w-4 h-4 text-muted"></i>
                        <span>CV Saya</span>
                    </a>
                    <a href="{{ route('bkk.index') }}" target="_blank" rel="noopener" class="flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-medium text-brand-ink hover:bg-brand-paper transition-colors">
                        <i data-lucide="external-link" class="w-4 h-4 text-muted"></i>
                        <span>Web Publik BKK</span>
                    </a>
                    <form action="{{ route('bkk.logout') }}" method="POST" class="pt-1 mt-1 border-t border-brand-ink/10">
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
    function toggleNotifDropdown() {
        document.getElementById('profileDropdown')?.classList.add('hidden');
        document.getElementById('notifDropdown')?.classList.toggle('hidden');
    }

    function toggleProfileDropdown() {
        document.getElementById('notifDropdown')?.classList.add('hidden');
        document.getElementById('profileDropdown')?.classList.toggle('hidden');
    }

    document.addEventListener('click', function (e) {
        const notifContainer = document.getElementById('notifDropdownContainer');
        const profContainer = document.getElementById('profileDropdownContainer');
        if (notifContainer && !notifContainer.contains(e.target)) {
            document.getElementById('notifDropdown')?.classList.add('hidden');
        }
        if (profContainer && !profContainer.contains(e.target)) {
            document.getElementById('profileDropdown')?.classList.add('hidden');
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        document.getElementById('notifDropdown')?.classList.add('hidden');
        document.getElementById('profileDropdown')?.classList.add('hidden');
    });
</script>
