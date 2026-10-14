<header class="fixed top-0 left-0 right-0 z-50 bg-surface-container-lowest/90 backdrop-blur-xl shadow-[0_1px_8px_rgba(0,0,0,0.04)]">
    <div class="h-20 max-w-7xl mx-auto px-6 lg:px-12 flex items-center justify-between gap-6">
        <!-- Logo & School Brand -->
        <a href="{{ url('/bkk') }}" class="flex items-center gap-3 shrink-0 hover:opacity-90 transition-opacity">
            <img src="{{ asset('images/logo-penus.png') }}" alt="SMK Plus Pelita Nusantara" class="h-10 sm:h-11 w-auto max-w-[200px] object-contain" />
            <span class="hidden sm:inline-flex items-center text-[11px] font-bold uppercase tracking-wider text-primary bg-primary/10 px-2.5 py-1 rounded-full">Bursa Kerja Khusus</span>
        </a>

        <!-- Main Navigation Bar -->
        @include('index.partials.navbar')

        <!-- Right Action Items -->
        <div class="flex items-center gap-3 shrink-0">
            <button aria-label="Pemberitahuan" class="w-10 h-10 rounded-full flex items-center justify-center text-on-surface-variant hover:bg-surface-container hover:text-on-surface transition-colors relative" type="button">
                <span class="material-symbols-outlined text-[20px]">notifications</span>
                <span class="absolute top-2 right-2 w-2 h-2 rounded-full bg-primary-container ring-2 ring-surface-container-lowest"></span>
            </button>
            <a class="hidden sm:inline-flex items-center justify-center px-5 py-2.5 rounded-full bg-primary-container text-on-primary-container font-label-md text-label-md font-semibold hover:bg-primary transition-colors" data-path="login-siswa" href="#">
                <span class="material-symbols-outlined text-[18px] mr-2">login</span>Masuk
            </a>

            <!-- Hamburger Button for Mobile / Tablet (< 1280px) -->
            <button aria-label="Menu Navigasi" class="xl:hidden w-10 h-10 rounded-full flex items-center justify-center text-on-surface-variant hover:bg-surface-container hover:text-on-surface transition-colors" onclick="toggleMobileNav()" type="button">
                <span class="material-symbols-outlined text-[24px]" id="mobile-menu-icon">menu</span>
            </button>
        </div>
    </div>
</header>

<script>
    function toggleMobileNav() {
        const drawer = document.getElementById('mobile-nav-drawer');
        const backdrop = document.getElementById('mobile-nav-backdrop');
        const icon = document.getElementById('mobile-menu-icon');
        if (!drawer || !backdrop) return;

        const isOpen = !drawer.classList.contains('translate-x-full');
        if (isOpen) {
            drawer.classList.add('translate-x-full');
            backdrop.classList.add('hidden');
            if (icon) icon.textContent = 'menu';
            document.body.style.overflow = '';
        } else {
            drawer.classList.remove('translate-x-full');
            backdrop.classList.remove('hidden');
            if (icon) icon.textContent = 'close';
            document.body.style.overflow = 'hidden';
        }
    }
</script>
