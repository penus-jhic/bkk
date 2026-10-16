@extends('admin.master')

@section('title', 'Edit Lowongan - Admin BKK')

@section('content')
<div class="flex flex-col gap-6 max-w-4xl mx-auto">
    <!-- Breadcrumb & Header -->
    <div>
        <div class="dash-eyebrow">
            <a href="{{ route('bkk.admin.index') }}" class="hover:text-brand-ink">Dashboard</a>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            <a href="{{ route('bkk.admin.lowongan.index') }}" class="hover:text-brand-ink">Lowongan</a>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            <span class="text-brand-ink font-semibold">Edit Lowongan</span>
        </div>
        <h1 class="dash-title font-display text-brand-ink">Edit Informasi <x-sketch.underline>Lowongan</x-sketch.underline></h1>
        <p class="text-xs text-muted mt-1">
            Perbarui data kebutuhan kompetensi, kuota pelamar, batas waktu, dan status moderasi lowongan dari Mitra {{ $lowongan->mitra->nama_perusahaan ?? '' }}.
        </p>
    </div>

    @if($errors->any())
        <div class="p-4 rounded-2xl bg-red-50 border border-red-200 text-red-800 text-xs">
            <div class="font-semibold mb-1">Terjadi kesalahan validasi:</div>
            <ul class="list-disc list-inside space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form Container -->
    <div class="bg-white rounded-3xl border border-line p-6 shadow-xs">
        <form method="POST" action="{{ route('bkk.admin.lowongan.update', $lowongan->id) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Informasi Utama -->
            <div>
                <h3 class="text-sm font-bold text-brand-ink uppercase tracking-wider mb-4 pb-2 border-b border-line flex items-center gap-2">
                    <i data-lucide="briefcase" class="w-4 h-4 text-maroon"></i>
                    <span>Informasi Pekerjaan & Mitra</span>
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-brand-ink mb-1.5">
                            Mitra Perusahaan (IDUKA)
                        </label>
                        <div class="p-3 bg-canvas rounded-2xl border border-line text-xs font-semibold text-brand-ink flex items-center gap-2">
                            <i data-lucide="building-2" class="w-4 h-4 text-muted"></i>
                            <span>{{ $lowongan->mitra->nama_perusahaan ?? 'Perusahaan Mitra' }}</span>
                            <span class="text-muted font-normal">(NPWP: {{ $lowongan->mitra->npwp ?? '-' }})</span>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <label for="judul" class="block text-xs font-semibold text-brand-ink mb-1.5">
                            Judul / Posisi Lowongan <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="judul"
                            name="judul"
                            value="{{ old('judul', $lowongan->judul) }}"
                            required
                            class="w-full px-4 py-2.5 bg-canvas text-brand-ink text-xs rounded-2xl border border-line focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                        />
                    </div>

                    <div>
                        <label for="tipe" class="block text-xs font-semibold text-brand-ink mb-1.5">
                            Tipe Kesempatan <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="tipe"
                            name="tipe"
                            required
                            class="w-full px-4 py-2.5 bg-canvas text-brand-ink text-xs rounded-2xl border border-line focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                        >
                            <option value="PKL" {{ old('tipe', $lowongan->tipe) === 'PKL' ? 'selected' : '' }}>Magang / PKL Siswa</option>
                            <option value="Kerja" {{ old('tipe', $lowongan->tipe) === 'Kerja' ? 'selected' : '' }}>Rekrutmen Kerja Lulusan</option>
                        </select>
                    </div>

                    <div>
                        <label for="status" class="block text-xs font-semibold text-brand-ink mb-1.5">
                            Status Publikasi <span class="text-red-500">*</span>
                        </label>
                        <select
                            id="status"
                            name="status"
                            required
                            class="w-full px-4 py-2.5 bg-canvas text-brand-ink text-xs rounded-2xl border border-line focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                        >
                            <option value="Aktif" {{ old('status', $lowongan->status) === 'Aktif' ? 'selected' : '' }}>Aktif (Tayang)</option>
                            <option value="Ditutup" {{ old('status', $lowongan->status) === 'Ditutup' ? 'selected' : '' }}>Ditutup</option>
                            <option value="Draft" {{ old('status', $lowongan->status) === 'Draft' ? 'selected' : '' }}>Draft (Review)</option>
                        </select>
                    </div>

                    <div>
                        <label for="target_jurusan" class="block text-xs font-semibold text-brand-ink mb-1.5">
                            Target Jurusan Sasaran <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="target_jurusan"
                            name="target_jurusan"
                            value="{{ old('target_jurusan', $lowongan->target_jurusan) }}"
                            placeholder="Contoh: Rekayasa Perangkat Lunak, TKJ, DKV"
                            required
                            class="w-full px-4 py-2.5 bg-canvas text-brand-ink text-xs rounded-2xl border border-line focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                        />
                    </div>

                    <div>
                        <label for="lokasi" class="block text-xs font-semibold text-brand-ink mb-1.5">
                            Lokasi Penempatan <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            id="lokasi"
                            name="lokasi"
                            value="{{ old('lokasi', $lowongan->lokasi) }}"
                            placeholder="Contoh: Bogor / Jakarta (Hybrid)"
                            required
                            class="w-full px-4 py-2.5 bg-canvas text-brand-ink text-xs rounded-2xl border border-line focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                        />
                    </div>

                    <div>
                        <label for="kuota" class="block text-xs font-semibold text-brand-ink mb-1.5">
                            Kuota Penerimaan <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="number"
                            id="kuota"
                            name="kuota"
                            min="1"
                            value="{{ old('kuota', $lowongan->kuota) }}"
                            required
                            class="w-full px-4 py-2.5 bg-canvas text-brand-ink text-xs rounded-2xl border border-line focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                        />
                    </div>

                    <div>
                        <label for="deadline" class="block text-xs font-semibold text-brand-ink mb-1.5">
                            Batas Waktu Pendaftaran (Deadline) <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="date"
                            id="deadline"
                            name="deadline"
                            value="{{ old('deadline', $lowongan->deadline ? $lowongan->deadline->format('Y-m-d') : '') }}"
                            required
                            class="w-full px-4 py-2.5 bg-canvas text-brand-ink text-xs rounded-2xl border border-line focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                        />
                    </div>

                    <div class="md:col-span-2">
                        <label for="gaji_kompensasi" class="block text-xs font-semibold text-brand-ink mb-1.5">
                            Gaji / Uang Saku Kompensasi
                        </label>
                        <input
                            type="text"
                            id="gaji_kompensasi"
                            name="gaji_kompensasi"
                            value="{{ old('gaji_kompensasi', $lowongan->gaji_kompensasi) }}"
                            placeholder="Contoh: Rp 4.500.000 - Rp 6.000.000 atau Uang Saku Bulanan"
                            class="w-full px-4 py-2.5 bg-canvas text-brand-ink text-xs rounded-2xl border border-line focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                        />
                    </div>
                </div>
            </div>

            <!-- Rincian Deskripsi & Syarat -->
            <div>
                <h3 class="text-sm font-bold text-brand-ink uppercase tracking-wider mb-4 pb-2 border-b border-line flex items-center gap-2">
                    <i data-lucide="file-text" class="w-4 h-4 text-maroon"></i>
                    <span>Deskripsi, Persyaratan & Benefit</span>
                </h3>

                <div class="space-y-4">
                    <div>
                        <label for="deskripsi" class="block text-xs font-semibold text-brand-ink mb-1.5">
                            Deskripsi Tanggung Jawab Pekerjaan <span class="text-red-500">*</span>
                        </label>
                        <textarea
                            id="deskripsi"
                            name="deskripsi"
                            rows="5"
                            required
                            class="w-full p-4 bg-canvas text-brand-ink text-xs rounded-2xl border border-line focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                        >{{ old('deskripsi', $lowongan->deskripsi) }}</textarea>
                    </div>

                    <div>
                        <label for="persyaratan" class="block text-xs font-semibold text-brand-ink mb-1.5">
                            Daftar Kualifikasi & Persyaratan (Satu per baris)
                        </label>
                        @php
                            $persyaratanText = is_array($lowongan->persyaratan_json) ? implode("\n", $lowongan->persyaratan_json) : ($lowongan->persyaratan_json ?? '');
                        @endphp
                        <textarea
                            id="persyaratan"
                            name="persyaratan"
                            rows="4"
                            placeholder="Masukkan satu syarat per baris..."
                            class="w-full p-4 bg-canvas text-brand-ink text-xs rounded-2xl border border-line focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                        >{{ old('persyaratan', $persyaratanText) }}</textarea>
                    </div>

                    <div>
                        <label for="benefit" class="block text-xs font-semibold text-brand-ink mb-1.5">
                            Fasilitas & Benefit yang Ditawarkan (Satu per baris)
                        </label>
                        @php
                            $benefitText = is_array($lowongan->benefit_json) ? implode("\n", $lowongan->benefit_json) : ($lowongan->benefit_json ?? '');
                        @endphp
                        <textarea
                            id="benefit"
                            name="benefit"
                            rows="4"
                            placeholder="Masukkan satu benefit per baris..."
                            class="w-full p-4 bg-canvas text-brand-ink text-xs rounded-2xl border border-line focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                        >{{ old('benefit', $benefitText) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Tombol Aksi -->
            <div class="pt-4 border-t border-line flex items-center justify-between">
                <a href="{{ route('bkk.admin.lowongan.index') }}" class="px-5 py-2.5 rounded-full text-xs font-semibold text-muted hover:text-brand-ink transition-colors">
                    Batal
                </a>
                <button
                    type="submit"
                    class="px-6 py-2.5 rounded-full bg-navy hover:bg-maroon text-white font-semibold text-xs transition-colors shadow-sm cursor-pointer"
                >
                    Simpan Perubahan Lowongan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
