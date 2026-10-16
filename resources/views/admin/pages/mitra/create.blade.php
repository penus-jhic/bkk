@extends('admin.master')

@section('title', 'Tambah Mitra Baru - Admin BKK')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb & Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="dash-eyebrow">
                <a href="{{ route('bkk.admin.index') }}" class="hover:text-brand-ink">Dashboard</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <a href="{{ route('bkk.admin.mitra.index') }}" class="hover:text-brand-ink">Mitra IDUKA</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-brand-ink font-semibold">Tambah Mitra Baru</span>
            </div>
            <h1 class="dash-title font-display text-brand-ink">Tambah <x-sketch.underline>Mitra Industri</x-sketch.underline> (IDUKA) Baru</h1>
            <p class="text-xs text-muted mt-0.5">
                Daftarkan profil perusahaan rekanan dan buatkan kredensial akun login portal IDUKA.
            </p>
        </div>

        <a href="{{ route('bkk.admin.mitra.index') }}" class="px-4 py-2 rounded-full border border-line hover:bg-canvas text-brand-ink text-xs font-semibold transition-colors flex items-center gap-1.5 shrink-0">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            <span>Kembali ke Daftar Mitra</span>
        </a>
    </div>

    <!-- Error Validation Alert -->
    @if($errors->any())
        <div class="p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs space-y-1">
            <div class="font-bold flex items-center gap-2">
                <i data-lucide="alert-triangle" class="w-4 h-4 text-rose-600"></i>
                <span>Terdapat kesalahan pengisian formulir:</span>
            </div>
            <ul class="list-disc pl-6 space-y-0.5 mt-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form Container -->
    <div class="bg-white rounded-3xl border border-line p-6 sm:p-8 shadow-xs">
        <form action="{{ route('bkk.admin.mitra.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Section 1: Kredensial Login & Identitas Perusahaan -->
            <div class="space-y-4">
                <div class="pb-2 border-b border-line flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-maroon"></span>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-brand-ink">1. Kredensial Login & Identitas Resmi</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Nama Perusahaan (Sanitize) -->
                    <div class="md:col-span-2">
                        <label for="nama_perusahaan" class="block text-xs font-semibold text-brand-ink uppercase tracking-wider mb-1">
                            Nama Resmi Perusahaan <span class="text-maroon">*</span>
                        </label>
                        <input
                            type="text"
                            id="nama_perusahaan"
                            name="nama_perusahaan"
                            value="{{ old('nama_perusahaan') }}"
                            required
                            placeholder="Contoh: PT Solusi Teknologi Nusantara"
                            class="w-full bg-canvas text-brand-ink text-sm rounded-xl px-4 py-2.5 border border-line focus:bg-white focus:border-navy focus:outline-none focus:ring-1 focus:ring-navy"
                        />
                        <p class="text-[11px] text-muted mt-1">
                            Nama perusahaan ini digunakan sebagai <strong>username login</strong> mitra di <code class="text-brand-ink font-mono">/bkk/dashboard/login</code> (otomatis disanitasi).
                        </p>
                    </div>

                    <!-- Password Awal Akun -->
                    <div>
                        <label for="password" class="block text-xs font-semibold text-brand-ink uppercase tracking-wider mb-1">
                            Kata Sandi Login Akun <span class="text-maroon">*</span>
                        </label>
                        <input
                            type="text"
                            id="password"
                            name="password"
                            value="{{ old('password', 'Password123!') }}"
                            required
                            minlength="8"
                            placeholder="Minimal 8 karakter"
                            class="w-full bg-canvas text-brand-ink text-sm rounded-xl px-4 py-2.5 border border-line focus:bg-white focus:border-navy focus:outline-none focus:ring-1 focus:ring-navy font-mono"
                        />
                        <p class="text-[11px] text-muted mt-1">Kata sandi default: <code class="font-mono text-brand-ink font-bold">Password123!</code></p>
                    </div>

                    <!-- NPWP -->
                    <div>
                        <label for="npwp" class="block text-xs font-semibold text-brand-ink uppercase tracking-wider mb-1">
                            Nomor NPWP Perusahaan <span class="text-maroon">*</span>
                        </label>
                        <input
                            type="text"
                            id="npwp"
                            name="npwp"
                            value="{{ old('npwp') }}"
                            required
                            placeholder="Contoh: 01.234.567.8-091.000"
                            class="w-full bg-canvas text-brand-ink text-sm rounded-xl px-4 py-2.5 border border-line focus:bg-white focus:border-navy focus:outline-none focus:ring-1 focus:ring-navy font-mono"
                        />
                    </div>

                    <!-- Singkatan / Alias -->
                    <div>
                        <label for="singkatan" class="block text-xs font-semibold text-brand-ink uppercase tracking-wider mb-1">
                            Singkatan / Alias Merk
                        </label>
                        <input
                            type="text"
                            id="singkatan"
                            name="singkatan"
                            value="{{ old('singkatan') }}"
                            placeholder="Contoh: STN atau Telkom Akses"
                            class="w-full bg-canvas text-brand-ink text-sm rounded-xl px-4 py-2.5 border border-line focus:bg-white focus:border-navy focus:outline-none focus:ring-1 focus:ring-navy"
                        />
                    </div>

                    <!-- Status Verifikasi Awal -->
                    <div class="flex items-center pt-6">
                        <label class="flex items-center gap-2.5 cursor-pointer select-none">
                            <input
                                type="checkbox"
                                name="is_verified"
                                value="1"
                                class="w-4 h-4 rounded border-line text-brand-ink focus:ring-brand-ink/30 cursor-pointer"
                                {{ old('is_verified', '1') == '1' ? 'checked' : '' }}
                            />
                            <div>
                                <span class="text-xs text-brand-ink font-semibold block">Langsung Verifikasi Kemitraan (Aktif)</span>
                                <span class="text-[11px] text-muted">Mitra dapat langsung login dan mempublikasikan lowongan.</span>
                            </div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Section 2: Sektor Usaha & Lokasi Kantor -->
            <div class="space-y-4 pt-4 border-t border-line">
                <div class="pb-2 border-b border-line flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-navy"></span>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-brand-ink">2. Bidang Usaha & Lokasi Fisik</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="sektor_industri" class="block text-xs font-semibold text-brand-ink uppercase tracking-wider mb-1">
                            Sektor Industri / Bidang <span class="text-maroon">*</span>
                        </label>
                        <input
                            type="text"
                            id="sektor_industri"
                            name="sektor_industri"
                            value="{{ old('sektor_industri') }}"
                            required
                            placeholder="Contoh: Teknologi Informasi, Software Engineering & Jaringan"
                            class="w-full bg-canvas text-brand-ink text-sm rounded-xl px-4 py-2.5 border border-line focus:bg-white focus:border-navy focus:outline-none focus:ring-1 focus:ring-navy"
                        />
                    </div>

                    <div>
                        <label for="kota" class="block text-xs font-semibold text-brand-ink uppercase tracking-wider mb-1">
                            Kota Domisili Kantor <span class="text-maroon">*</span>
                        </label>
                        <input
                            type="text"
                            id="kota"
                            name="kota"
                            value="{{ old('kota') }}"
                            required
                            placeholder="Contoh: Kota Bogor atau Semarang"
                            class="w-full bg-canvas text-brand-ink text-sm rounded-xl px-4 py-2.5 border border-line focus:bg-white focus:border-navy focus:outline-none focus:ring-1 focus:ring-navy"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <label for="alamat_kantor" class="block text-xs font-semibold text-brand-ink uppercase tracking-wider mb-1">
                            Alamat Lengkap Kantor <span class="text-maroon">*</span>
                        </label>
                        <textarea
                            id="alamat_kantor"
                            name="alamat_kantor"
                            rows="2"
                            required
                            placeholder="Contoh: Jl. Pajajaran No. 88, Baranangsiang, Kota Bogor, Jawa Barat 16143"
                            class="w-full bg-canvas text-brand-ink text-sm rounded-xl p-3 border border-line focus:bg-white focus:border-navy focus:outline-none focus:ring-1 focus:ring-navy"
                        >{{ old('alamat_kantor') }}</textarea>
                    </div>

                    <div class="md:col-span-2">
                        <label for="website" class="block text-xs font-semibold text-brand-ink uppercase tracking-wider mb-1">
                            Website Resmi Perusahaan
                        </label>
                        <input
                            type="url"
                            id="website"
                            name="website"
                            value="{{ old('website') }}"
                            placeholder="https://perusahaan.co.id"
                            class="w-full bg-canvas text-brand-ink text-sm rounded-xl px-4 py-2.5 border border-line focus:bg-white focus:border-navy focus:outline-none focus:ring-1 focus:ring-navy"
                        />
                    </div>
                </div>
            </div>

            <!-- Section 3: Kontak Resmi Kantor & PIC HRD -->
            <div class="space-y-4 pt-4 border-t border-line">
                <div class="pb-2 border-b border-line flex items-center gap-2">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
                    <h2 class="text-sm font-bold uppercase tracking-wider text-brand-ink">3. Kontak Perusahaan & PIC (Person-in-Charge)</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="email_perusahaan" class="block text-xs font-semibold text-brand-ink uppercase tracking-wider mb-1">
                            Email Resmi Perusahaan <span class="text-maroon">*</span>
                        </label>
                        <input
                            type="email"
                            id="email_perusahaan"
                            name="email_perusahaan"
                            value="{{ old('email_perusahaan') }}"
                            required
                            placeholder="hrd@perusahaan.co.id"
                            class="w-full bg-canvas text-brand-ink text-sm rounded-xl px-4 py-2.5 border border-line focus:bg-white focus:border-navy focus:outline-none focus:ring-1 focus:ring-navy"
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
                            value="{{ old('no_telp_perusahaan') }}"
                            required
                            placeholder="0251-8321900"
                            class="w-full bg-canvas text-brand-ink text-sm rounded-xl px-4 py-2.5 border border-line focus:bg-white focus:border-navy focus:outline-none focus:ring-1 focus:ring-navy"
                        />
                    </div>

                    <div>
                        <label for="pic_name" class="block text-xs font-semibold text-brand-ink uppercase tracking-wider mb-1">
                            Nama PIC HRD / Hubin <span class="text-maroon">*</span>
                        </label>
                        <input
                            type="text"
                            id="pic_name"
                            name="pic_name"
                            value="{{ old('pic_name') }}"
                            required
                            placeholder="Contoh: Cindy Claudia, S.Kom."
                            class="w-full bg-canvas text-brand-ink text-sm rounded-xl px-4 py-2.5 border border-line focus:bg-white focus:border-navy focus:outline-none focus:ring-1 focus:ring-navy"
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
                            value="{{ old('pic_role') }}"
                            required
                            placeholder="Contoh: Talent Acquisition Specialist"
                            class="w-full bg-canvas text-brand-ink text-sm rounded-xl px-4 py-2.5 border border-line focus:bg-white focus:border-navy focus:outline-none focus:ring-1 focus:ring-navy"
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
                            value="{{ old('pic_email') }}"
                            required
                            placeholder="cindy.claudia@perusahaan.co.id"
                            class="w-full bg-canvas text-brand-ink text-sm rounded-xl px-4 py-2.5 border border-line focus:bg-white focus:border-navy focus:outline-none focus:ring-1 focus:ring-navy"
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
                            value="{{ old('pic_phone') }}"
                            required
                            placeholder="Contoh: 0812-9988-7766"
                            class="w-full bg-canvas text-brand-ink text-sm rounded-xl px-4 py-2.5 border border-line focus:bg-white focus:border-navy focus:outline-none focus:ring-1 focus:ring-navy"
                        />
                    </div>
                </div>
            </div>

            <!-- Submit Action Bar -->
            <div class="pt-6 border-t border-line flex flex-col sm:flex-row items-center justify-between gap-3">
                <a href="{{ route('bkk.admin.mitra.index') }}" class="px-5 py-2.5 rounded-full border border-line text-xs font-semibold text-brand-ink hover:bg-canvas transition-colors">
                    Batal
                </a>
                <button type="submit" class="w-full sm:w-auto px-6 py-2.5 rounded-full bg-navy hover:bg-maroon text-white font-semibold text-xs shadow-sm hover:shadow transition-all flex items-center justify-center gap-2 cursor-pointer">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Simpan & Daftarkan Mitra Baru</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
