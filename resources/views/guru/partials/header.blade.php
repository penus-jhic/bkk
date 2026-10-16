@php
    $role = strtoupper($authUser['role'] ?? 'GURU');
    $name = $authUser['nama_lengkap'] ?? $authUser['username'] ?? 'Guru Pembimbing';
    $initials = collect(preg_split('/\s+/', trim($name)))->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: 'GP';
    $username = $authUser['username'] ?? 'guru';
    $email = $authUser['email'] ?? '-';
    $nip = $authUser['nomor_induk'] ?? '-';
@endphp

<header class="sticky top-0 z-30 h-16 bg-white/95 backdrop-blur border-b border-brand-ink/10 flex items-center gap-2 px-3 sm:px-5">
    <!-- Tombol menu mobile -->
    <button type="button" onclick="toggleGuruSidebar()" class="lg:hidden w-10 h-10 rounded-full grid place-items-center hover:bg-brand-softmist cursor-pointer text-brand-ink transition-colors" aria-label="Buka menu">
        <i data-lucide="menu" class="w-5 h-5"></i>
    </button>

    <!-- Brand -->
    <a href="{{ route('bkk.guru.dashboard') }}" class="flex items-center gap-2.5 group shrink-0" title="Portal Guru Pembimbing PKL">
        <img src="{{ asset('images/logosmkpenus.png') }}" alt="Logo SMK Plus Pelita Nusantara" class="w-9 h-9 object-contain group-hover:scale-105 transition-transform" />
        <span class="hidden sm:flex flex-col">
            <span class="font-display text-[15px] font-bold uppercase tracking-wide text-brand-ink leading-tight group-hover:text-brand-darkred transition-colors">BKK Pelita Nusantara</span>
            <span class="text-[10px] uppercase font-semibold tracking-wider text-emerald-700 mt-0.5 flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                <span>Portal Guru Pembimbing PKL</span>
            </span>
        </span>
    </a>

    <!-- Pencarian Cepat Siswa -->
    <div class="flex-1 max-w-xl mx-auto hidden md:block">
        <form action="{{ route('bkk.guru.siswa.index') }}" method="GET" role="search">
            <label class="flex items-center gap-3 h-10 rounded-full bg-brand-paper border border-brand-ink/10 focus-within:bg-white focus-within:border-brand-darkred focus-within:ring-4 focus-within:ring-brand-darkred/10 px-4 transition-all">
                <i data-lucide="search" class="w-4 h-4 text-muted"></i>
                <input
                    type="search"
                    name="q"
                    value="{{ request()->routeIs('bkk.guru.siswa.*') ? request('q') : '' }}"
                    placeholder="Cari nama, NIS, jurusan siswa bimbingan PKL…"
                    aria-label="Cari siswa"
                    class="flex-1 bg-transparent outline-none text-sm placeholder:text-muted text-brand-ink"
                />
            </label>
        </form>
    </div>

    <!-- Aksi kanan -->
    <div class="ml-auto flex items-center gap-1.5 sm:gap-2.5">
        <a href="{{ route('bkk.lowongan') }}" target="_blank" rel="noopener" class="hidden sm:inline-flex items-center gap-1.5 h-9 px-3.5 rounded-full text-xs font-semibold text-brand-ink bg-white border border-brand-ink/15 hover:bg-brand-softmist transition-colors">
            <i data-lucide="briefcase" class="w-3.5 h-3.5 text-brand-darkred"></i>
            <span>Katalog PKL</span>
        </a>

        <!-- Profil & Menu Akun -->
        <div class="relative" id="guruUserDropdownContainer">
            <button type="button" onclick="toggleGuruUserDropdown()" class="flex items-center gap-2.5 pl-1 pr-2.5 py-1 rounded-full hover:bg-brand-softmist cursor-pointer transition-colors border border-brand-ink/10" aria-label="Profil Guru" aria-haspopup="true">
                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-emerald-600 to-teal-800 text-white font-display text-xs font-bold grid place-items-center shadow-xs">
                    {{ $initials }}
                </div>
                <div class="hidden xl:flex flex-col text-left">
                    <span class="text-xs font-bold text-brand-ink leading-tight truncate max-w-[140px]">{{ $name }}</span>
                    <span class="text-[10px] text-muted leading-tight">Guru Pembimbing</span>
                </div>
                <i data-lucide="chevron-down" class="w-3.5 h-3.5 text-muted"></i>
            </button>

            <div id="guruUserDropdown" class="hidden absolute right-0 mt-2 w-64 bg-white rounded-2xl border border-brand-ink/10 shadow-lg overflow-hidden fade-up z-50">
                <div class="p-4 bg-brand-paper border-b border-brand-ink/10">
                    <div class="text-xs font-semibold uppercase tracking-wider text-emerald-800">Akun Guru Pembimbing</div>
                    <div class="text-sm font-bold text-brand-ink mt-1 truncate">{{ $name }}</div>
                    <div class="text-[11px] text-muted mt-0.5 truncate">{{ $email }}</div>
                    <div class="mt-2 text-[10px] text-brand-ink/70 font-mono">NIP: {{ $nip }}</div>
                </div>

                <div class="p-2 space-y-1">
                    <a href="{{ route('bkk.guru.dashboard') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium text-brand-ink hover:bg-brand-paper transition-colors">
                        <i data-lucide="layout-dashboard" class="w-4 h-4 text-muted"></i>
                        <span>Dashboard Guru</span>
                    </a>
                    <a href="{{ route('bkk.guru.siswa.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium text-brand-ink hover:bg-brand-paper transition-colors">
                        <i data-lucide="users" class="w-4 h-4 text-muted"></i>
                        <span>Daftar Siswa & CV</span>
                    </a>
                    <a href="{{ route('bkk.guru.pengajuan.index') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-medium text-brand-ink hover:bg-brand-paper transition-colors">
                        <i data-lucide="check-circle" class="w-4 h-4 text-muted"></i>
                        <span>Persetujuan PKL</span>
                    </a>

                    <div class="h-px bg-brand-ink/10 my-1"></div>

                    <a href="{{ route('bkk.logout') }}" class="flex items-center gap-2.5 px-3 py-2 rounded-xl text-xs font-semibold text-red-600 hover:bg-red-50 transition-colors">
                        <i data-lucide="log-out" class="w-4 h-4 text-red-600"></i>
                        <span>Keluar Sesi</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    function toggleGuruUserDropdown() {
        const dd = document.getElementById('guruUserDropdown');
        if (dd) dd.classList.toggle('hidden');
    }

    document.addEventListener('click', function(e) {
        const container = document.getElementById('guruUserDropdownContainer');
        const dd = document.getElementById('guruUserDropdown');
        if (container && dd && !container.contains(e.target)) {
            dd.classList.add('hidden');
        }
    });
</script>
