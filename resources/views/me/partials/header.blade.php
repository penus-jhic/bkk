@php
    $role = strtoupper($authUser['role'] ?? 'SISWA');
    $isSiswa = ($role === 'SISWA');
    $initials = $profile['initials'] ?? 'RP';
    $name = $profile['name'] ?? ($authUser['nama_lengkap'] ?? $authUser['username'] ?? 'Pengguna');
    $firstName = explode(' ', trim($name))[0];
@endphp

<header class="sticky top-0 z-30 h-16 bg-[#f8f9fa]/95 backdrop-blur border-b border-line flex items-center gap-2 px-3 sm:px-5">
    <!-- Mobile Hamburger / Desktop Toggle Button -->
    <button onclick="toggleMeSidebar()" class="w-10 h-10 rounded-full grid place-items-center hover:bg-navy/5 cursor-pointer text-muted transition-colors" aria-label="Toggle Menu">
        <i data-lucide="menu" class="w-5 h-5"></i>
    </button>

    <!-- Brandmark Logo / Image Placeholder -->
    <a href="{{ route('bkk.me.index') }}" class="flex items-center hover:opacity-90 transition-opacity" title="Portal Siswa & Alumni">
        <img src="{{ asset('images/logo-penus.png') }}" alt="SMK Plus Pelita Nusantara" class="h-9 sm:h-10 w-auto max-w-[190px] object-contain" />
    </a>

    <!-- Center Search Bar -->
    <div class="flex-1 max-w-xl mx-auto hidden md:block">
        <form action="{{ route('bkk.me.lamaran') }}" method="GET" class="relative">
            <label class="flex items-center gap-3 h-11 rounded-full bg-[#eef0f2] focus-within:bg-white focus-within:shadow-md focus-within:ring-1 focus-within:ring-line px-4 transition-all">
                <i data-lucide="search" class="w-4 h-4 text-muted"></i>
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="Cari lowongan, perusahaan, atau lamaran…"
                    class="flex-1 bg-transparent outline-none text-sm placeholder:text-muted text-navy"
                />
            </label>
        </form>
    </div>

    <!-- Right Actions -->
    <div class="ml-auto flex items-center gap-2 sm:gap-3">
        <!-- Role Indicator Badge (Ketat 100% dari Auth Service) -->
        <div class="h-8 px-3 rounded-full text-xs font-semibold flex items-center gap-1.5 shadow-sm {{ $isSiswa ? 'bg-navy text-white' : 'bg-maroon text-white' }}">
            <span class="w-2 h-2 rounded-full {{ $isSiswa ? 'bg-emerald-400' : 'bg-amber-300' }}"></span>
            <span>[{{ $isSiswa ? 'Siswa PKL' : 'Alumni' }}]</span>
        </div>

        <!-- Notifications Dropdown -->
        <div class="relative" id="notifDropdownContainer">
            <button onclick="toggleNotifDropdown()" class="relative w-10 h-10 rounded-full grid place-items-center hover:bg-navy/5 cursor-pointer text-muted transition-colors" aria-label="Notifikasi">
                <i data-lucide="bell" class="w-5 h-5"></i>
                <span class="absolute top-2.5 right-2.5 w-2 h-2 rounded-full bg-maroon ring-2 ring-[#f8f9fa]"></span>
            </button>

            <!-- Dropdown Content -->
            <div id="notifDropdown" class="hidden absolute right-0 mt-2 w-[min(22rem,calc(100vw-1.5rem))] bg-white rounded-2xl border border-line shadow-xl overflow-hidden fade-up z-50">
                <div class="px-4 py-3 border-b border-line flex justify-between items-center bg-[#f8f9fa]">
                    <span class="font-semibold text-sm text-navy">Notifikasi</span>
                    <span class="text-xs text-maroon font-semibold bg-maroon/10 px-2 py-0.5 rounded-full">2 baru</span>
                </div>
                <div class="divide-y divide-line/60 max-h-80 overflow-y-auto">
                    @forelse($notifications ?? [] as $n)
                        <a href="{{ route('bkk.me.lamaran') }}" class="block px-4 py-3 hover:bg-[#f8f9fa] transition-colors">
                            <div class="flex gap-3">
                                <span class="mt-1.5 w-2 h-2 rounded-full shrink-0 {{ ($n['unread'] ?? false) ? (($n['accent'] ?? false) ? 'bg-maroon' : 'bg-navy') : 'bg-transparent' }}"></span>
                                <div>
                                    <div class="text-sm font-medium text-navy">{{ $n['title'] }}</div>
                                    <div class="text-xs text-muted mt-0.5 leading-relaxed">{{ $n['desc'] }}</div>
                                    <div class="text-[11px] text-muted mt-1">{{ $n['time'] }}</div>
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="px-4 py-6 text-center text-xs text-muted">Tidak ada notifikasi baru.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- User Profile Avatar & Dropdown -->
        <div class="relative" id="profileDropdownContainer">
            <button onclick="toggleProfileDropdown()" class="w-10 h-10 rounded-full grid place-items-center hover:bg-navy/5 cursor-pointer transition-colors" aria-label="Menu Profil">
                <span class="w-8 h-8 rounded-full grid place-items-center text-white text-xs font-bold shadow-sm {{ $isSiswa ? 'bg-navy' : 'bg-maroon' }}">
                    {{ $initials }}
                </span>
            </button>

            <!-- Dropdown Content -->
            <div id="profileDropdown" class="hidden absolute right-0 mt-2 w-72 bg-white rounded-3xl border border-line shadow-2xl p-4 fade-up z-50">
                <div class="text-center pb-3 border-b border-line/60">
                    <div class="w-16 h-16 mx-auto rounded-full grid place-items-center text-white text-xl font-bold shadow-md {{ $isSiswa ? 'bg-navy' : 'bg-maroon' }}">
                        {{ $initials }}
                    </div>
                    <div class="mt-2 font-semibold text-navy text-sm">{{ $name }}</div>
                    <div class="text-xs text-muted">{{ $profile['email'] ?? ($authUser['email'] ?? '-') }}</div>
                    <div class="text-xs text-muted mt-0.5 font-medium">{{ $profile['jurusan'] ?? '-' }}</div>
                    <div class="mt-2 inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold {{ $isSiswa ? 'bg-navy/10 text-navy' : 'bg-maroon/10 text-maroon' }}">
                        NIS: {{ $profile['nis'] ?? ($authUser['nomor_induk'] ?? '-') }}
                    </div>
                </div>

                <div class="mt-3 bg-[#f8f9fa] rounded-2xl p-1 space-y-0.5">
                    <a href="{{ route('bkk.me.index') }}" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-medium text-navy hover:bg-white transition-colors">
                        <i data-lucide="user" class="w-4 h-4 text-muted"></i>
                        <span>Profil Saya</span>
                    </a>
                    <a href="{{ url('/bkk') }}" target="_blank" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-medium text-navy hover:bg-white transition-colors">
                        <i data-lucide="external-link" class="w-4 h-4 text-muted"></i>
                        <span>Web Publik BKK</span>
                    </a>
                    <a href="{{ url('/bkk') }}" class="w-full flex items-center gap-3 px-3 py-2 rounded-xl text-xs font-semibold text-maroon hover:bg-white transition-colors">
                        <i data-lucide="log-out" class="w-4 h-4 text-maroon"></i>
                        <span>Keluar Portal</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    function toggleNotifDropdown() {
        const notif = document.getElementById('notifDropdown');
        const prof = document.getElementById('profileDropdown');
        if (prof) prof.classList.add('hidden');
        if (notif) notif.classList.toggle('hidden');
    }

    function toggleProfileDropdown() {
        const prof = document.getElementById('profileDropdown');
        const notif = document.getElementById('notifDropdown');
        if (notif) notif.classList.add('hidden');
        if (prof) prof.classList.toggle('hidden');
    }

    // Close on outside click
    document.addEventListener('click', function(e) {
        const notifContainer = document.getElementById('notifDropdownContainer');
        const profContainer = document.getElementById('profileDropdownContainer');
        if (notifContainer && !notifContainer.contains(e.target)) {
            document.getElementById('notifDropdown')?.classList.add('hidden');
        }
        if (profContainer && !profContainer.contains(e.target)) {
            document.getElementById('profileDropdown')?.classList.add('hidden');
        }
    });
</script>
