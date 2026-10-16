@extends('mitra.master')

@section('title', 'Edit Lowongan: ' . $vacancy['title'] . ' - Mitra IDUKA')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="dash-eyebrow">
                <a href="{{ route('bkk.mitra.dashboard') }}" class="hover:text-brand-ink">Dashboard</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <a href="{{ route('bkk.mitra.lowongan.index') }}" class="hover:text-brand-ink">Lowongan</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-brand-ink font-semibold">Edit Lowongan</span>
            </div>
            <h1 class="dash-title font-display text-brand-ink"><x-sketch.underline>Edit</x-sketch.underline>: {{ $vacancy['title'] }}</h1>
            <p class="text-xs text-muted mt-0.5">
                ID Lowongan: <code class="font-mono text-brand-ink font-semibold">{{ $vacancy['id'] }}</code> • Dipublikasikan: {{ $vacancy['created_at'] }}
            </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('bkk.mitra.pelamar.index', $vacancy['id']) }}" class="px-4 py-2 rounded-full bg-navy hover:bg-navy-light text-white text-xs font-semibold shadow-sm transition-colors flex items-center gap-1.5">
                <i data-lucide="users" class="w-4 h-4"></i>
                <span>Lihat Pelamar ({{ $vacancy['pelamar_count'] }})</span>
            </a>
        </div>
    </div>

    <!-- Edit Form Card -->
    <div class="google-card p-6 sm:p-8">
        <form action="{{ route('bkk.mitra.lowongan.update', $vacancy['id']) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Section 1: Informasi Utama & Status -->
            <div class="space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-line">
                    <h3 class="text-sm font-headline font-bold text-brand-ink uppercase tracking-wider flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full bg-maroon"></span> 1. Status & Judul Lowongan
                    </h3>

                    <!-- Status Indicator Toggle -->
                    <div class="flex items-center gap-2">
                        <label for="status" class="text-xs font-semibold text-brand-ink">Status Publikasi:</label>
                        <select name="status" id="status" class="px-3 py-1 rounded-full border border-line text-xs font-semibold {{ $vacancy['status'] === 'Aktif' ? 'bg-emerald-50 text-emerald-800' : 'bg-gray-100 text-gray-700' }} focus:outline-none focus:ring-1 focus:ring-navy">
                            <option value="Aktif" {{ $vacancy['status'] === 'Aktif' ? 'selected' : '' }}>Aktif (Menerima Lamaran)</option>
                            <option value="Ditutup" {{ $vacancy['status'] === 'Ditutup' ? 'selected' : '' }}>Ditutup (Nonaktif)</option>
                        </select>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label for="title" class="text-xs font-semibold text-brand-ink">Judul Posisi Lowongan <span class="text-red-500">*</span></label>
                    <input
                        type="text"
                        name="title"
                        id="title"
                        required
                        value="{{ $vacancy['title'] }}"
                        class="w-full px-4 py-2.5 rounded-xl border border-line bg-canvas text-brand-ink text-sm focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                    />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <label for="tipe" class="text-xs font-semibold text-brand-ink">Tipe Program <span class="text-red-500">*</span></label>
                        <select name="tipe" id="tipe" required class="w-full px-4 py-2.5 rounded-xl border border-line bg-canvas text-brand-ink text-sm focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy">
                            <option value="PKL" {{ $vacancy['tipe'] === 'PKL' ? 'selected' : '' }}>Praktik Kerja Lapangan (PKL Siswa)</option>
                            <option value="Kerja" {{ $vacancy['tipe'] === 'Kerja' ? 'selected' : '' }}>Pekerjaan Penuh Waktu (Lulusan & Alumni)</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label for="jurusan" class="text-xs font-semibold text-brand-ink">Target Jurusan SMK Penus <span class="text-red-500">*</span></label>
                        <select name="jurusan" id="jurusan" required class="w-full px-4 py-2.5 rounded-xl border border-line bg-canvas text-brand-ink text-sm focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy">
                            <option value="Rekayasa Perangkat Lunak (RPL)" {{ str_contains($vacancy['jurusan'], 'RPL') ? 'selected' : '' }}>Rekayasa Perangkat Lunak (RPL)</option>
                            <option value="Teknik Komputer & Jaringan (TKJ)" {{ str_contains($vacancy['jurusan'], 'TKJ') ? 'selected' : '' }}>Teknik Komputer & Jaringan (TKJ)</option>
                            <option value="Desain Komunikasi Visual (DKV) / Multimedia" {{ str_contains($vacancy['jurusan'], 'Multimedia') || str_contains($vacancy['jurusan'], 'DKV') ? 'selected' : '' }}>Desain Komunikasi Visual (DKV) / Multimedia</option>
                            <option value="Semua Jurusan SMK Pelita Nusantara" {{ str_contains($vacancy['jurusan'], 'Semua') ? 'selected' : '' }}>Semua Jurusan Terbuka</option>
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
                            value="{{ $vacancy['lokasi'] }}"
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
                            value="{{ $vacancy['kuota'] ?? 2 }}"
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
                            value="{{ $vacancy['deadline'] }}"
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
                        value="{{ $vacancy['gaji'] }}"
                        class="w-full px-4 py-2.5 rounded-xl border border-line bg-canvas text-brand-ink text-sm focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                    />
                </div>
            </div>

            <!-- Section 2: Deskripsi & Persyaratan -->
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
                        class="w-full px-4 py-3 rounded-xl border border-line bg-canvas text-brand-ink text-sm focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                    >{{ $vacancy['deskripsi'] }}</textarea>
                </div>

                <div class="space-y-1.5">
                    <label for="persyaratan" class="text-xs font-semibold text-brand-ink">Persyaratan & Kualifikasi (1 baris per poin) <span class="text-red-500">*</span></label>
                    <textarea
                        name="persyaratan"
                        id="persyaratan"
                        rows="4"
                        required
                        class="w-full px-4 py-3 rounded-xl border border-line bg-canvas text-brand-ink text-sm focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy font-mono text-xs"
                    >{{ implode("\n", $vacancy['persyaratan']) }}</textarea>
                </div>

                <div class="space-y-1.5">
                    <label for="benefit" class="text-xs font-semibold text-brand-ink">Fasilitas & Benefit yang Diberikan (1 baris per poin)</label>
                    <textarea
                        name="benefit"
                        id="benefit"
                        rows="3"
                        class="w-full px-4 py-3 rounded-xl border border-line bg-canvas text-brand-ink text-sm focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy font-mono text-xs"
                    >{{ implode("\n", $vacancy['benefit']) }}</textarea>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="pt-6 border-t border-line flex flex-col sm:flex-row items-center justify-between gap-3">
                <!-- Delete Button Trigger -->
                <button type="button" onclick="document.getElementById('delete-form').submit()" class="w-full sm:w-auto px-5 py-2.5 rounded-full border border-red-200 text-red-600 hover:bg-red-50 text-xs font-semibold transition-colors flex items-center justify-center gap-1.5">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    <span>Hapus Lowongan Ini</span>
                </button>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <a href="{{ route('bkk.mitra.lowongan.index') }}" class="w-full sm:w-auto px-6 py-2.5 rounded-full border border-line text-brand-ink hover:bg-canvas text-xs font-semibold transition-colors text-center">
                        Batal
                    </a>
                    <button type="submit" class="w-full sm:w-auto px-7 py-2.5 rounded-full bg-navy hover:bg-navy-light text-white text-xs font-semibold shadow-sm transition-all flex items-center justify-center gap-2">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </div>
        </form>

        <!-- Hidden Delete Form -->
        <form id="delete-form" action="{{ route('bkk.mitra.lowongan.destroy', $vacancy['id']) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus / mengarsipkan lowongan ini?')" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>
@endsection
