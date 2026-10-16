@extends('mitra.master')

@section('title', 'Bikin Lowongan Baru - Mitra IDUKA')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb & Title -->
    <div>
        <div class="dash-eyebrow">
            <a href="{{ route('bkk.mitra.dashboard') }}" class="hover:text-brand-ink">Dashboard</a>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            <a href="{{ route('bkk.mitra.lowongan.index') }}" class="hover:text-brand-ink">Lowongan</a>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            <span class="text-brand-ink font-semibold">Buat Lowongan Baru</span>
        </div>
        <h1 class="dash-title font-display text-brand-ink">Bikin Lowongan <x-sketch.underline>Baru</x-sketch.underline></h1>
        <p class="text-xs text-muted mt-0.5">
            Publikasikan kesempatan Praktik Kerja Lapangan (PKL) atau penyerapan kerja alumni SMK Plus Pelita Nusantara.
        </p>
    </div>

    <!-- Form Card -->
    <div class="google-card p-6 sm:p-8">
        <form action="{{ route('bkk.mitra.lowongan.store') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Section 1: Informasi Dasar -->
            <div class="space-y-4">
                <h3 class="text-sm font-headline font-bold text-brand-ink uppercase tracking-wider pb-2 border-b border-line flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-maroon"></span> 1. Informasi Utama Lowongan
                </h3>

                <div class="space-y-1.5">
                    <label for="title" class="text-xs font-semibold text-brand-ink">Judul Posisi Lowongan <span class="text-red-500">*</span></label>
                    <input
                        type="text"
                        name="title"
                        id="title"
                        required
                        placeholder="Contoh: Junior Web Developer (Laravel) atau Internship IT Support (PKL)"
                        class="w-full px-4 py-2.5 rounded-xl border border-line bg-canvas text-brand-ink text-sm focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label for="tipe" class="text-xs font-semibold text-brand-ink">Tipe Program <span class="text-red-500">*</span></label>
                        <select name="tipe" id="tipe" required class="w-full px-4 py-2.5 rounded-xl border border-line bg-canvas text-brand-ink text-sm focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy">
                            <option value="PKL">Praktik Kerja Lapangan (PKL Siswa)</option>
                            <option value="Kerja">Pekerjaan Penuh Waktu (Lulusan & Alumni)</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label for="jurusan" class="text-xs font-semibold text-brand-ink">Target Jurusan SMK Penus <span class="text-red-500">*</span></label>
                        <select name="jurusan" id="jurusan" required class="w-full px-4 py-2.5 rounded-xl border border-line bg-canvas text-brand-ink text-sm focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy">
                            <option value="Rekayasa Perangkat Lunak (RPL)">Rekayasa Perangkat Lunak (RPL)</option>
                            <option value="Teknik Komputer & Jaringan (TKJ)">Teknik Komputer & Jaringan (TKJ)</option>
                            <option value="Desain Komunikasi Visual (DKV) / Multimedia">Desain Komunikasi Visual (DKV) / Multimedia</option>
                            <option value="Semua Jurusan SMK Pelita Nusantara">Semua Jurusan Terbuka</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="space-y-1.5">
                        <label for="lokasi" class="text-xs font-semibold text-brand-ink">Lokasi Penempatan <span class="text-red-500">*</span></label>
                        <input
                            type="text"
                            name="lokasi"
                            id="lokasi"
                            required
                            placeholder="Contoh: Bogor (On-site Penus Technopark) / Hybrid"
                            class="w-full px-4 py-2.5 rounded-xl border border-line bg-canvas text-brand-ink text-sm focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <label for="kuota" class="text-xs font-semibold text-brand-ink">Kuota Penerimaan (Orang) <span class="text-red-500">*</span></label>
                        <input
                            type="number"
                            name="kuota"
                            id="kuota"
                            min="1"
                            value="2"
                            required
                            class="w-full px-4 py-2.5 rounded-xl border border-line bg-canvas text-brand-ink text-sm focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <label for="deadline" class="text-xs font-semibold text-brand-ink">Batas Akhir Pendaftaran <span class="text-red-500">*</span></label>
                        <input
                            type="date"
                            name="deadline"
                            id="deadline"
                            required
                            value="{{ date('Y-m-d', strtotime('+30 days')) }}"
                            class="w-full px-4 py-2.5 rounded-xl border border-line bg-canvas text-brand-ink text-sm focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                        />
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label for="gaji" class="text-xs font-semibold text-brand-ink">Kompensasi / Uang Saku / Gaji</label>
                    <input
                        type="text"
                        name="gaji"
                        id="gaji"
                        placeholder="Contoh: Rp 4.500.000 - Rp 6.000.000 atau Uang Saku & Uang Makan Harian (PKL)"
                        class="w-full px-4 py-2.5 rounded-xl border border-line bg-canvas text-brand-ink text-sm focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                    />
                </div>
            </div>

            <!-- Section 2: Deskripsi & Kualifikasi -->
            <div class="space-y-4 pt-4 border-t border-line">
                <h3 class="text-sm font-headline font-bold text-brand-ink uppercase tracking-wider pb-2 border-b border-line flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-navy"></span> 2. Rincian Pekerjaan & Kualifikasi
                </h3>

                <div class="space-y-1.5">
                    <label for="deskripsi" class="text-xs font-semibold text-brand-ink">Deskripsi & Tanggung Jawab <span class="text-red-500">*</span></label>
                    <textarea
                        name="deskripsi"
                        id="deskripsi"
                        rows="4"
                        required
                        placeholder="Jelaskan gambaran umum tugas yang akan dikerjakan siswa/alumni selama bekerja atau magang..."
                        class="w-full px-4 py-3 rounded-xl border border-line bg-canvas text-brand-ink text-sm focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                    ></textarea>
                </div>

                <div class="space-y-1.5">
                    <label for="persyaratan" class="text-xs font-semibold text-brand-ink">Persyaratan & Kualifikasi Teknis (1 baris per poin) <span class="text-red-500">*</span></label>
                    <textarea
                        name="persyaratan"
                        id="persyaratan"
                        rows="4"
                        required
                        placeholder="Siswa aktif kelas XI / XII RPL&#10;Menguasai dasar HTML, CSS, JavaScript&#10;Memiliki sertifikat kompetensi dasar&#10;Disiplin dan bertanggung jawab"
                        class="w-full px-4 py-3 rounded-xl border border-line bg-canvas text-brand-ink text-sm focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy font-mono text-xs"
                    ></textarea>
                    <p class="text-[11px] text-muted">Tekan Enter untuk membuat poin persyaratan baru.</p>
                </div>

                <div class="space-y-1.5">
                    <label for="benefit" class="text-xs font-semibold text-brand-ink">Fasilitas & Benefit yang Diberikan (1 baris per poin)</label>
                    <textarea
                        name="benefit"
                        id="benefit"
                        rows="3"
                        placeholder="Sertifikat resmi IDUKA terverifikasi&#10;Mentorship langsung oleh engineer profesional&#10;Peralatan kerja disediakan"
                        class="w-full px-4 py-3 rounded-xl border border-line bg-canvas text-brand-ink text-sm focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy font-mono text-xs"
                    ></textarea>
                </div>
            </div>

            <!-- Submit & Cancel Buttons -->
            <div class="pt-6 border-t border-line flex flex-col sm:flex-row items-center justify-end gap-3">
                <a href="{{ route('bkk.mitra.lowongan.index') }}" class="w-full sm:w-auto px-6 py-2.5 rounded-full border border-line text-brand-ink hover:bg-canvas text-xs font-semibold transition-colors text-center">
                    Batal
                </a>
                <button type="submit" class="w-full sm:w-auto px-7 py-2.5 rounded-full bg-maroon hover:bg-maroon-dark text-white text-xs font-semibold shadow-sm transition-all flex items-center justify-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span>Tayangkan Lowongan Sekarang</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
