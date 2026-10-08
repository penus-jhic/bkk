@php
    $inputClass = 'w-full rounded-xl border-2 bg-white py-3 text-sm text-brand-ink placeholder:text-brand-ink/40 transition focus:border-brand-darkred focus:outline-none focus:ring-4 focus:ring-brand-darkred/10';
    $fieldClass = fn (string $field) => $inputClass . ' ' . ($errors->has($field) ? 'border-brand-signal' : 'border-brand-ink/10 hover:border-brand-ink/20');

    $benefits = [
        ['icon' => 'megaphone', 'title' => 'Pasang lowongan PKL & kerja', 'desc' => 'Langsung tampil di katalog lowongan BKK untuk siswa dan alumni.'],
        ['icon' => 'file-search', 'title' => 'Tinjau CV pelamar', 'desc' => 'Baca berkas, ubah status seleksi, dan panggil interview dari satu tempat.'],
        ['icon' => 'activity', 'title' => 'Pantau siswa PKL', 'desc' => 'Lihat penempatan siswa yang sedang praktik kerja di perusahaan Anda.'],
    ];
    $mitraCount = isset($registeredMitras) ? $registeredMitras->count() : 0;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    @include('mitra.partials.head')
    <title>Masuk Portal Mitra IDUKA - BKK SMK Plus Pelita Nusantara</title>
</head>
<body class="bg-brand-paper text-brand-ink font-sans antialiased min-h-screen flex flex-col">

    <!-- Bar atas -->
    <header class="sticky top-0 z-30 h-16 bg-white/95 backdrop-blur border-b border-brand-ink/10 flex items-center gap-2 px-4 sm:px-6">
        <a href="{{ route('bkk.index') }}" class="flex items-center gap-2.5 group shrink-0" title="Beranda BKK">
            <img src="{{ asset('images/logosmkpenus.png') }}" alt="Logo SMK Plus Pelita Nusantara" class="w-9 h-9 object-contain group-hover:scale-105 transition-transform" />
            <span class="flex flex-col">
                <span class="font-display text-[15px] font-bold uppercase tracking-wide text-brand-ink leading-tight group-hover:text-brand-darkred transition-colors">BKK Pelita Nusantara</span>
                <span class="text-[10px] uppercase font-semibold tracking-wider text-brand-darkred mt-0.5">Portal Mitra IDUKA</span>
            </span>
        </a>
        <a href="{{ route('bkk.index') }}" class="ml-auto inline-flex items-center gap-1.5 h-9 px-3.5 rounded-full text-xs font-semibold text-brand-ink bg-white border border-brand-ink/15 hover:bg-brand-softmist transition-colors">
            <i data-lucide="arrow-left" class="w-3.5 h-3.5 text-brand-darkred"></i>
            <span class="hidden sm:inline">Kembali ke Beranda</span>
            <span class="sm:hidden">Beranda</span>
        </a>
    </header>

    <main class="relative flex-1 overflow-hidden bg-white">
        <!-- Latar: kisi tipis & cahaya merah seperti hero landing page -->
        <div aria-hidden="true" class="pointer-events-none absolute inset-0">
            <div class="absolute inset-0 bg-[linear-gradient(to_right,rgb(36_16_18/0.04)_1px,transparent_1px),linear-gradient(to_bottom,rgb(36_16_18/0.04)_1px,transparent_1px)] bg-[length:44px_44px] [mask-image:radial-gradient(ellipse_70%_60%_at_30%_30%,#000_60%,transparent_100%)]"></div>
            <div class="absolute -top-40 -right-32 w-[35rem] h-[35rem] rounded-full bg-brand-signal/10 blur-[120px]"></div>
        </div>

        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 py-12 md:py-20 grid gap-14 lg:grid-cols-[1.1fr_1fr] lg:gap-16 lg:items-center">

            <!-- Kiri: pengantar portal -->
            <section class="fade-up">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-brand-darkred">
                    <x-sketch.sparks>Portal Hubungan Industri</x-sketch.sparks>
                </p>
                <h2 class="mt-6 font-display text-4xl sm:text-5xl font-bold uppercase tracking-wide leading-[1.05]">
                    Rekrut Talenta Vokasi
                    <span class="block mt-2 text-brand-darkred"><x-sketch.underline size="lg" tone="text-brand-signal" :delay="400">Siap Kerja</x-sketch.underline></span>
                </h2>
                <p class="mt-10 max-w-xl text-base leading-relaxed text-brand-ink/70">
                    Dashboard khusus perusahaan mitra (IDUKA) SMK Plus Pelita Nusantara untuk mengelola lowongan, meninjau berkas siswa & alumni, dan memantau program PKL.
                </p>

                <ul class="mt-9 max-w-xl">
                    @foreach($benefits as $i => $benefit)
                        <li class="relative flex items-start gap-4 py-4">
                            <x-sketch.rule :delay="$i * 120" class="text-brand-darkred/25 left-0 right-0 -top-1.5 h-3" />
                            <span class="flex w-10 h-10 shrink-0 items-center justify-center rounded-full bg-brand-darkred/[0.07] text-brand-darkred">
                                <i data-lucide="{{ $benefit['icon'] }}" class="w-[18px] h-[18px]"></i>
                            </span>
                            <span>
                                <span class="block font-display text-base uppercase tracking-wide">{{ $benefit['title'] }}</span>
                                <span class="block text-sm text-brand-ink/60 mt-0.5">{{ $benefit['desc'] }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>

                @if($mitraCount > 0)
                    <p class="mt-6 inline-flex items-center gap-3 rounded-card bg-white px-4 py-3 shadow-softpill ring-1 ring-brand-ink/5 text-sm">
                        <span class="flex w-9 h-9 shrink-0 items-center justify-center rounded-full bg-brand-darkred text-white"><i data-lucide="badge-check" class="w-5 h-5"></i></span>
                        <span><b class="font-display text-xl text-brand-darkred">{{ $mitraCount }}</b> <span class="text-brand-ink/70">mitra industri terverifikasi sudah bergabung</span></span>
                    </p>
                @endif
            </section>

            <!-- Kanan: formulir masuk -->
            <section class="fade-up">
                <div class="relative bg-white p-6 sm:p-9 shadow-card">
                    <x-sketch.box />

                    <div class="flex items-start gap-4">
                        <span class="flex w-12 h-12 shrink-0 items-center justify-center rounded-full bg-brand-darkred text-white">
                            <i data-lucide="building-2" class="w-6 h-6"></i>
                        </span>
                        <div>
                            <h1 class="font-display text-2xl md:text-3xl font-bold uppercase tracking-wide leading-tight">Masuk Portal IDUKA</h1>
                            <p class="mt-1 text-sm text-brand-ink/60">Gunakan nama perusahaan & kata sandi dari BKK.</p>
                        </div>
                    </div>

                    @if(session('success'))
                        <div role="status" class="relative mt-7 rounded-xl bg-emerald-50 py-3 pr-4 pl-9 text-sm text-emerald-900">
                            <x-sketch.rule bold vertical class="text-emerald-600 top-1 bottom-1 left-2 w-3" />
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div role="alert" class="relative mt-7 rounded-xl bg-brand-darkred/5 py-3 pr-4 pl-9 text-sm text-brand-darkred">
                            <x-sketch.rule bold vertical class="text-brand-darkred top-1 bottom-1 left-2 w-3" />
                            {{ session('error') }}
                        </div>
                    @endif

                    @if($errors->any())
                        <div role="alert" class="mt-7 rounded-xl border-2 border-brand-signal/30 bg-brand-signal/5 p-4 text-sm">
                            <p class="font-semibold text-brand-darkred">Belum bisa masuk. Periksa kembali isian berikut:</p>
                            <ul class="mt-2 list-disc space-y-1 pl-5 text-brand-ink/75">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('bkk.mitra.login.post') }}" method="POST" class="mt-7 flex flex-col gap-5">
                        @csrf

                        <div>
                            <label for="nama_perusahaan" class="text-sm font-semibold">Nama Perusahaan <span class="text-brand-darkred">*</span></label>
                            <div class="relative mt-1.5">
                                <i data-lucide="briefcase" class="w-4 h-4 text-brand-ink/40 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input
                                    type="text"
                                    id="nama_perusahaan"
                                    name="nama_perusahaan"
                                    value="{{ old('nama_perusahaan') }}"
                                    required
                                    autocomplete="organization"
                                    placeholder="PT Solusi Teknologi Nusantara"
                                    class="{{ $fieldClass('nama_perusahaan') }} pl-11 pr-4"
                                />
                            </div>
                            <p class="text-xs text-brand-ink/50 mt-1.5">Tidak membedakan huruf besar/kecil, spasi berlebih dibersihkan otomatis.</p>
                        </div>

                        <div>
                            <div class="flex items-center justify-between gap-3">
                                <label for="password" class="text-sm font-semibold">Kata Sandi <span class="text-brand-darkred">*</span></label>
                                <span class="text-xs text-brand-ink/50">Lupa? Hubungi Hubin BKK</span>
                            </div>
                            <div class="relative mt-1.5">
                                <i data-lucide="lock" class="w-4 h-4 text-brand-ink/40 absolute left-4 top-1/2 -translate-y-1/2 pointer-events-none"></i>
                                <input
                                    type="password"
                                    id="password"
                                    name="password"
                                    required
                                    autocomplete="current-password"
                                    placeholder="Masukkan kata sandi akun"
                                    class="{{ $fieldClass('password') }} pl-11 pr-12"
                                />
                                <button
                                    type="button"
                                    id="togglePasswordBtn"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full grid place-items-center text-brand-ink/50 hover:text-brand-darkred hover:bg-brand-paper transition-colors cursor-pointer"
                                    aria-label="Tampilkan kata sandi"
                                    aria-pressed="false"
                                >
                                    <i data-lucide="eye" class="w-4 h-4" data-eye="show"></i>
                                    <i data-lucide="eye-off" class="w-4 h-4 hidden" data-eye="hide"></i>
                                </button>
                            </div>
                        </div>

                        <label class="flex items-center gap-2.5 cursor-pointer select-none text-sm text-brand-ink/80">
                            <input type="checkbox" name="remember" value="1" class="w-4 h-4 shrink-0 accent-brand-darkred cursor-pointer" @checked(old('remember')) />
                            Ingat sesi di perangkat ini
                        </label>

                        <button type="submit" class="group mt-1 inline-flex w-full items-center justify-center gap-3 rounded-full bg-gradient-to-r from-brand-signal to-brand-darkred px-7 py-3.5 text-sm font-semibold text-white shadow-xl shadow-brand-darkred/25 transition-transform hover:-translate-y-0.5 cursor-pointer">
                            Masuk ke Dashboard Mitra
                            <x-sketch.arrow :delay="500" class="w-7 h-3.5 transition-transform group-hover:translate-x-1" />
                        </button>
                    </form>

                    @if(isset($registeredMitras) && $registeredMitras->isNotEmpty())
                        <div class="mt-7 rounded-xl border-2 border-dashed border-brand-ink/10 bg-brand-paper/60 p-4">
                            <p class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-brand-ink/60">
                                <i data-lucide="sparkles" class="w-3.5 h-3.5 text-brand-darkred"></i>
                                Uji coba cepat · akun mitra terdaftar
                            </p>
                            <div class="mt-2.5 flex flex-wrap gap-1.5">
                                @foreach($registeredMitras as $demo)
                                    <button
                                        type="button"
                                        data-company="{{ $demo->nama_perusahaan }}"
                                        class="js-fill-login text-xs font-medium px-3 py-1 rounded-full bg-white border border-brand-ink/10 hover:border-brand-darkred hover:text-brand-darkred text-brand-ink transition-colors flex items-center gap-1.5 cursor-pointer"
                                        title="Isi otomatis: {{ $demo->nama_perusahaan }}"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        {{ $demo->singkatan ?: $demo->nama_perusahaan }}
                                    </button>
                                @endforeach
                            </div>
                            <p class="text-[11px] text-brand-ink/50 mt-2.5">
                                Kata sandi bawaan seeder: <code class="bg-white border border-brand-ink/10 px-1.5 py-0.5 rounded font-mono font-bold text-brand-ink">Password123!</code>
                            </p>
                        </div>
                    @endif
                </div>

                <p class="mt-10 text-center text-sm text-brand-ink/60">
                    Perusahaan Anda belum terdaftar sebagai mitra BKK?
                    <a href="{{ route('bkk.kerjasama') }}" class="group mt-1 flex items-center justify-center gap-2 font-semibold text-brand-darkred">
                        Ajukan Permohonan Kemitraan
                        <x-sketch.arrow class="w-5 h-2.5 transition-transform group-hover:translate-x-1" />
                    </a>
                </p>
            </section>
        </div>
    </main>

    <footer class="border-t border-brand-ink/10 bg-brand-paper py-4 px-4 text-center text-xs text-brand-ink/50">
        &copy; {{ date('Y') }} BKK SMK Plus Pelita Nusantara · Bogor, Jawa Barat
    </footer>

    <script>
        lucide.createIcons();

        // Tampilkan / sembunyikan kata sandi
        const toggleBtn = document.getElementById('togglePasswordBtn');
        const passwordInput = document.getElementById('password');
        toggleBtn?.addEventListener('click', () => {
            const show = passwordInput.type === 'password';
            passwordInput.type = show ? 'text' : 'password';
            toggleBtn.setAttribute('aria-pressed', String(show));
            toggleBtn.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            toggleBtn.querySelector('[data-eye="show"]')?.classList.toggle('hidden', show);
            toggleBtn.querySelector('[data-eye="hide"]')?.classList.toggle('hidden', !show);
        });

        // Isi formulir dari chip akun uji coba
        document.querySelectorAll('.js-fill-login').forEach((chip) => {
            chip.addEventListener('click', () => {
                document.getElementById('nama_perusahaan').value = chip.dataset.company;
                passwordInput.value = 'Password123!';
                passwordInput.focus();
            });
        });
    </script>
    @include('partials.sketch-engine')
</body>
</html>
