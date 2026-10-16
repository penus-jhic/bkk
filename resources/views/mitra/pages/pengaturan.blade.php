@extends('mitra.master')

@section('title', 'Pengaturan Akun & Perusahaan - Portal Mitra IDUKA BKK')

@section('content')
<div class="space-y-6 max-w-5xl mx-auto">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-4 border-b border-line">
        <div>
            <p class="dash-eyebrow">Portal Mitra IDUKA · Akun</p>
            <div class="flex items-center gap-2">
                <h1 class="dash-title font-display text-brand-ink">Pengaturan <x-sketch.underline>Akun Mitra</x-sketch.underline></h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $mitra->is_verified ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                    {{ $mitra->is_verified ? 'Terverifikasi' : 'Pending' }}
                </span>
            </div>
            <p class="text-xs sm:text-sm text-muted mt-1">
                Kelola keamanan kata sandi, logo resmi perusahaan, kontak PIC, dan kontrol sesi login.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('bkk.mitra.dashboard') }}" class="px-3.5 py-2 rounded-full border border-line hover:bg-canvas text-xs font-semibold text-brand-ink transition-all flex items-center gap-1.5">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Kembali ke Dashboard</span>
            </a>
        </div>
    </div>

    <!-- Alert Notifikasi Global -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-3">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0"></i>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left Column: Ubah Password & Ubah Logo -->
        <div class="space-y-6 lg:col-span-1">

            <!-- Card 1: Ubah Logo Perusahaan -->
            <div class="bg-white rounded-3xl border border-line p-5 shadow-xs">
                <div class="flex items-center gap-2.5 pb-3 border-b border-line mb-4">
                    <div class="w-8 h-8 rounded-xl bg-brand-ink/5 text-brand-ink flex items-center justify-center">
                        <i data-lucide="image" class="w-4 h-4 text-maroon"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-brand-ink">Logo Perusahaan</h2>
                        <p class="text-[11px] text-muted">Format: PNG, JPG, ICO (Maks. 5 MB)</p>
                    </div>
                </div>

                @if(session('logo_success'))
                    <div class="mb-3 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs">
                        {{ session('logo_success') }}
                    </div>
                @endif

                @if($errors->has('logo'))
                    <div class="mb-3 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                        {{ $errors->first('logo') }}
                    </div>
                @endif

                <div class="flex flex-col items-center text-center p-3 rounded-2xl bg-canvas border border-dashed border-line mb-4">
                    @if($mitra->logo_url)
                        <img src="{{ $mitra->logo_url }}" alt="{{ $mitra->nama_perusahaan }}" class="w-20 h-20 rounded-2xl object-cover border border-line shadow-xs mb-2"/>
                    @else
                        <div class="w-20 h-20 rounded-2xl bg-navy text-white flex items-center justify-center text-xl font-bold shadow-inner mb-2">
                            {{ $mitra->singkatan ?: substr($mitra->nama_perusahaan, 0, 3) }}
                        </div>
                    @endif
                    <div class="text-xs font-semibold text-brand-ink">{{ $mitra->nama_perusahaan }}</div>
                    <div class="text-[10px] text-muted mt-0.5">NPWP: {{ $mitra->npwp }}</div>
                </div>

                <form action="{{ route('bkk.mitra.pengaturan.logo') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div>
                        <label for="logo" class="block text-xs font-medium text-brand-ink mb-1">Pilih File Logo Baru</label>
                        <input
                            type="file"
                            id="logo"
                            name="logo"
                            accept=".png,.jpg,.jpeg,.ico"
                            required
                            class="block w-full text-xs text-muted file:mr-3 file:py-2 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-navy file:text-white hover:file:bg-maroon cursor-pointer border border-line rounded-2xl p-1 bg-canvas"
                        />
                    </div>
                    <button type="submit" class="w-full py-2 px-4 rounded-full bg-navy hover:bg-maroon text-white text-xs font-semibold shadow-xs transition-colors flex items-center justify-center gap-1.5 cursor-pointer">
                        <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                        <span>Unggah Logo</span>
                    </button>
                </form>
            </div>

            <!-- Card 2: Ubah Kata Sandi -->
            <div class="bg-white rounded-3xl border border-line p-5 shadow-xs">
                <div class="flex items-center gap-2.5 pb-3 border-b border-line mb-4">
                    <div class="w-8 h-8 rounded-xl bg-brand-ink/5 text-brand-ink flex items-center justify-center">
                        <i data-lucide="key-round" class="w-4 h-4 text-maroon"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-brand-ink">Keamanan Kata Sandi</h2>
                        <p class="text-[11px] text-muted">Perbarui kata sandi akun mitra</p>
                    </div>
                </div>

                @if(session('password_success'))
                    <div class="mb-3 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs">
                        {{ session('password_success') }}
                    </div>
                @endif

                @if(session('password_error'))
                    <div class="mb-3 p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-xs">
                        {{ session('password_error') }}
                    </div>
                @endif

                <form action="{{ route('bkk.mitra.pengaturan.password') }}" method="POST" class="space-y-3">
                    @csrf
                    <div>
                        <label for="current_password" class="block text-xs font-medium text-brand-ink mb-1">Kata Sandi Saat Ini</label>
                        <input
                            type="password"
                            id="current_password"
                            name="current_password"
                            required
                            placeholder="Password lama Anda"
                            class="w-full bg-canvas text-brand-ink text-xs rounded-xl px-3 py-2 border border-line focus:bg-white focus:border-navy focus:outline-none"
                        />
                        @error('current_password')
                            <p class="text-[11px] text-rose-600 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-medium text-brand-ink mb-1">Kata Sandi Baru (Min. 8 Karakter)</label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            placeholder="Password baru"
                            class="w-full bg-canvas text-brand-ink text-xs rounded-xl px-3 py-2 border border-line focus:bg-white focus:border-navy focus:outline-none"
                        />
                        @error('password')
                            <p class="text-[11px] text-rose-600 mt-0.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-xs font-medium text-brand-ink mb-1">Ulangi Kata Sandi Baru</label>
                        <input
                            type="password"
                            id="password_confirmation"
                            name="password_confirmation"
                            required
                            placeholder="Konfirmasi password baru"
                            class="w-full bg-canvas text-brand-ink text-xs rounded-xl px-3 py-2 border border-line focus:bg-white focus:border-navy focus:outline-none"
                        />
                    </div>

                    <button type="submit" class="w-full py-2 px-4 rounded-full bg-navy hover:bg-maroon text-white text-xs font-semibold shadow-xs transition-colors flex items-center justify-center gap-1.5 cursor-pointer">
                        <i data-lucide="shield-check" class="w-3.5 h-3.5"></i>
                        <span>Simpan Kata Sandi</span>
                    </button>
                </form>
            </div>

            <!-- Card 3: Keluar / Logout Sesi (Hapus Cookie) -->
            <div class="bg-white rounded-3xl border border-line p-5 shadow-xs">
                <div class="flex items-center gap-2.5 pb-3 border-b border-line mb-3">
                    <div class="w-8 h-8 rounded-xl bg-rose-50 text-rose-700 flex items-center justify-center">
                        <i data-lucide="log-out" class="w-4 h-4 text-maroon"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-brand-ink">Sesi & Keluar Akun</h2>
                        <p class="text-[11px] text-muted">Hapus cookie autentikasi perangkat</p>
                    </div>
                </div>
                <p class="text-xs text-muted leading-relaxed mb-4">
                    Setelah keluar, cookie dan sesi browser akan dihapus. Anda harus memasukkan nama perusahaan dan kata sandi kembali untuk masuk.
                </p>
                <form action="{{ route('bkk.mitra.logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full py-2.5 px-4 rounded-full bg-rose-50 hover:bg-rose-100 text-maroon border border-rose-200 text-xs font-bold transition-colors flex items-center justify-center gap-2 cursor-pointer">
                        <i data-lucide="log-out" class="w-4 h-4"></i>
                        <span>Keluar dari Akun (Logout)</span>
                    </button>
                </form>
            </div>

        </div>

        <!-- Right Column: Profil Perusahaan & PIC -->
        <div class="lg:col-span-2 space-y-6">

            <div class="bg-white rounded-3xl border border-line p-6 shadow-xs">
                <div class="flex items-center justify-between pb-4 border-b border-line mb-5">
                    <div>
                        <h2 class="text-base font-bold text-brand-ink">Informasi Perusahaan & PIC</h2>
                        <p class="text-xs text-muted">Data ini ditampilkan pada katalog mitra dan pengajuan lowongan.</p>
                    </div>
                    <span class="text-[11px] text-muted font-mono bg-canvas px-2.5 py-1 rounded-full border border-line">
                        ID: #{{ $mitra->id }}
                    </span>
                </div>

                @if(session('profil_success'))
                    <div class="mb-4 p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                        <span>{{ session('profil_success') }}</span>
                    </div>
                @endif

                <form action="{{ route('bkk.mitra.pengaturan.profil') }}" method="POST" class="space-y-4">
                    @csrf

                    <!-- Row 1: Nama Perusahaan & Singkatan -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-brand-ink uppercase tracking-wider mb-1">
                                Nama Perusahaan (Kredensial Login)
                            </label>
                            <input
                                type="text"
                                value="{{ $mitra->nama_perusahaan }}"
                                disabled
                                class="w-full bg-brand-softmist text-muted text-xs rounded-xl px-3.5 py-2.5 border border-line cursor-not-allowed"
                                title="Nama perusahaan dikelola oleh Admin BKK"
                            />
                            <p class="text-[10px] text-muted mt-0.5">Untuk pengubahan nama resmi entitas perusahaan, hubungi Admin BKK Penus.</p>
                        </div>
                        <div>
                            <label for="singkatan" class="block text-xs font-semibold text-brand-ink uppercase tracking-wider mb-1">
                                Singkatan / Alias
                            </label>
                            <input
                                type="text"
                                id="singkatan"
                                name="singkatan"
                                value="{{ old('singkatan', $mitra->singkatan) }}"
                                placeholder="Misal: STN"
                                class="w-full bg-canvas text-brand-ink text-xs rounded-xl px-3.5 py-2.5 border border-line focus:bg-white focus:border-navy focus:outline-none"
                            />
                        </div>
                    </div>

                    <!-- Row 2: NPWP & Sektor Industri -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-brand-ink uppercase tracking-wider mb-1">
                                Nomor NPWP Perusahaan
                            </label>
                            <input
                                type="text"
                                value="{{ $mitra->npwp }}"
                                disabled
                                class="w-full bg-brand-softmist text-muted text-xs rounded-xl px-3.5 py-2.5 border border-line font-mono cursor-not-allowed"
                            />
                        </div>
                        <div>
                            <label for="sektor_industri" class="block text-xs font-semibold text-brand-ink uppercase tracking-wider mb-1">
                                Sektor Industri <span class="text-maroon">*</span>
                            </label>
                            <input
                                type="text"
                                id="sektor_industri"
                                name="sektor_industri"
                                value="{{ old('sektor_industri', $mitra->sektor_industri) }}"
                                required
                                placeholder="Contoh: Teknologi Informasi & Jaringan"
                                class="w-full bg-canvas text-brand-ink text-xs rounded-xl px-3.5 py-2.5 border border-line focus:bg-white focus:border-navy focus:outline-none"
                            />
                        </div>
                    </div>

                    <!-- Row 3: Kota & Website -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="kota" class="block text-xs font-semibold text-brand-ink uppercase tracking-wider mb-1">
                                Kota Domisili Kantor <span class="text-maroon">*</span>
                            </label>
                            <input
                                type="text"
                                id="kota"
                                name="kota"
                                value="{{ old('kota', $mitra->kota) }}"
                                required
                                placeholder="Contoh: Kota Bogor"
                                class="w-full bg-canvas text-brand-ink text-xs rounded-xl px-3.5 py-2.5 border border-line focus:bg-white focus:border-navy focus:outline-none"
                            />
                        </div>
                        <div>
                            <label for="website" class="block text-xs font-semibold text-brand-ink uppercase tracking-wider mb-1">
                                Website Perusahaan
                            </label>
                            <input
                                type="url"
                                id="website"
                                name="website"
                                value="{{ old('website', $mitra->website) }}"
                                placeholder="https://perusahaan.co.id"
                                class="w-full bg-canvas text-brand-ink text-xs rounded-xl px-3.5 py-2.5 border border-line focus:bg-white focus:border-navy focus:outline-none"
                            />
                        </div>
                    </div>

                    <!-- Row 4: Alamat Kantor -->
                    <div>
                        <label for="alamat_kantor" class="block text-xs font-semibold text-brand-ink uppercase tracking-wider mb-1">
                            Alamat Kantor Lengkap <span class="text-maroon">*</span>
                        </label>
                        <textarea
                            id="alamat_kantor"
                            name="alamat_kantor"
                            rows="2"
                            required
                            class="w-full bg-canvas text-brand-ink text-xs rounded-xl p-3 border border-line focus:bg-white focus:border-navy focus:outline-none"
                        >{{ old('alamat_kantor', $mitra->alamat_kantor) }}</textarea>
                    </div>

                    <!-- Section Divider: PIC Contacts -->
                    <div class="pt-4 pb-2 border-t border-line">
                        <div class="text-xs font-bold text-brand-ink uppercase tracking-wider">
                            Person-in-Charge (PIC HRD / Hubin)
                        </div>
                        <p class="text-[11px] text-muted">Kontak ini dihubungi sekolah terkait penempatan PKL dan administrasi lamaran.</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="pic_name" class="block text-xs font-semibold text-brand-ink uppercase tracking-wider mb-1">
                                Nama PIC <span class="text-maroon">*</span>
                            </label>
                            <input
                                type="text"
                                id="pic_name"
                                name="pic_name"
                                value="{{ old('pic_name', $mitra->pic_name) }}"
                                required
                                class="w-full bg-canvas text-brand-ink text-xs rounded-xl px-3.5 py-2.5 border border-line focus:bg-white focus:border-navy focus:outline-none"
                            />
                        </div>
                        <div>
                            <label for="pic_role" class="block text-xs font-semibold text-brand-ink uppercase tracking-wider mb-1">
                                Jabatan PIC <span class="text-maroon">*</span>
                            </label>
                            <input
                                type="text"
                                id="pic_role"
                                name="pic_role"
                                value="{{ old('pic_role', $mitra->pic_role) }}"
                                required
                                class="w-full bg-canvas text-brand-ink text-xs rounded-xl px-3.5 py-2.5 border border-line focus:bg-white focus:border-navy focus:outline-none"
                            />
                        </div>
                        <div>
                            <label for="pic_email" class="block text-xs font-semibold text-brand-ink uppercase tracking-wider mb-1">
                                Email PIC <span class="text-maroon">*</span>
                            </label>
                            <input
                                type="email"
                                id="pic_email"
                                name="pic_email"
                                value="{{ old('pic_email', $mitra->pic_email) }}"
                                required
                                class="w-full bg-canvas text-brand-ink text-xs rounded-xl px-3.5 py-2.5 border border-line focus:bg-white focus:border-navy focus:outline-none"
                            />
                        </div>
                        <div>
                            <label for="pic_phone" class="block text-xs font-semibold text-brand-ink uppercase tracking-wider mb-1">
                                No. WhatsApp / Telepon PIC <span class="text-maroon">*</span>
                            </label>
                            <input
                                type="text"
                                id="pic_phone"
                                name="pic_phone"
                                value="{{ old('pic_phone', $mitra->pic_phone) }}"
                                required
                                class="w-full bg-canvas text-brand-ink text-xs rounded-xl px-3.5 py-2.5 border border-line focus:bg-white focus:border-navy focus:outline-none"
                            />
                        </div>
                    </div>

                    <!-- Row 5: Kontak Kantor -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="email_perusahaan" class="block text-xs font-semibold text-brand-ink uppercase tracking-wider mb-1">
                                Email Resmi Kantor <span class="text-maroon">*</span>
                            </label>
                            <input
                                type="email"
                                id="email_perusahaan"
                                name="email_perusahaan"
                                value="{{ old('email_perusahaan', $mitra->email_perusahaan) }}"
                                required
                                class="w-full bg-canvas text-brand-ink text-xs rounded-xl px-3.5 py-2.5 border border-line focus:bg-white focus:border-navy focus:outline-none"
                            />
                        </div>
                        <div>
                            <label for="no_telp_perusahaan" class="block text-xs font-semibold text-brand-ink uppercase tracking-wider mb-1">
                                No. Telepon Kantor <span class="text-maroon">*</span>
                            </label>
                            <input
                                type="text"
                                id="no_telp_perusahaan"
                                name="no_telp_perusahaan"
                                value="{{ old('no_telp_perusahaan', $mitra->no_telp_perusahaan) }}"
                                required
                                class="w-full bg-canvas text-brand-ink text-xs rounded-xl px-3.5 py-2.5 border border-line focus:bg-white focus:border-navy focus:outline-none"
                            />
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-4 border-t border-line flex justify-end">
                        <button type="submit" class="py-2.5 px-6 rounded-full bg-navy hover:bg-maroon text-white font-semibold text-xs shadow-xs hover:shadow transition-all flex items-center gap-2 cursor-pointer">
                            <i data-lucide="save" class="w-4 h-4"></i>
                            <span>Simpan Perubahan Profil</span>
                        </button>
                    </div>
                </form>
            </div>

        </div>

    </div>
</div>
@endsection
