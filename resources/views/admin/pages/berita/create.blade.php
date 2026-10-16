@extends('admin.master')

@section('title', 'Tulis Berita Baru - Admin BKK')

@push('styles')
    <!-- EasyMDE Markdown Editor CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css"/>
    <style>
        .EasyMDEContainer .CodeMirror {
            border-radius: 0 0 0.75rem 0.75rem;
            border-color: #EAE4E3;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 13px;
            background: #ffffff;
        }
        .EasyMDEContainer .editor-toolbar {
            border-radius: 0.75rem 0.75rem 0 0;
            border-color: #EAE4E3;
            background: #F6F3F2;
        }
        .editor-preview, .editor-preview-side {
            background: #ffffff;
            color: #241012;
            padding: 1.5rem;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            line-height: 1.6;
        }
        .editor-preview h1, .editor-preview-side h1 { font-size: 1.75rem; font-weight: 700; margin-bottom: 1rem; color: #7A1018; }
        .editor-preview h2, .editor-preview-side h2 { font-size: 1.35rem; font-weight: 700; margin-top: 1.5rem; margin-bottom: 0.75rem; color: #241012; border-bottom: 1px solid #EAE4E3; padding-bottom: 0.25rem; }
        .editor-preview h3, .editor-preview-side h3 { font-size: 1.15rem; font-weight: 600; margin-top: 1rem; margin-bottom: 0.5rem; color: #875300; }
        .editor-preview blockquote, .editor-preview-side blockquote { border-left: 4px solid #7A1018; padding-left: 1rem; color: #5E5253; font-style: italic; margin: 1rem 0; background: #F6F3F2; padding: 0.5rem 1rem; border-radius: 0 0.5rem 0.5rem 0; }
        .editor-preview ul, .editor-preview-side ul { list-style-type: disc; margin-left: 1.5rem; margin-bottom: 1rem; }
        .editor-preview ol, .editor-preview-side ol { list-style-type: decimal; margin-left: 1.5rem; margin-bottom: 1rem; }
        .editor-preview table, .editor-preview-side table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
        .editor-preview th, .editor-preview-side th, .editor-preview td, .editor-preview-side td { border: 1px solid #EAE4E3; padding: 0.5rem; }
        .editor-preview th, .editor-preview-side th { background: #F1ECEB; }
    </style>
@endpush

@section('content')
<div class="flex flex-col gap-6 max-w-5xl mx-auto">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-2 text-xs text-on-surface-variant">
            <a href="{{ route('bkk.admin.berita.index') }}" class="hover:text-primary transition-colors flex items-center gap-1">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                <span>Kembali ke Daftar Berita</span>
            </a>
            <span>/</span>
            <span class="text-on-surface font-semibold">Tulis Baru</span>
        </div>
        <div class="flex items-center gap-2">
            <button type="submit" form="formBerita" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-primary text-on-primary font-bold text-xs hover:bg-primary/90 transition-all shadow-sm">
                <span class="material-symbols-outlined text-[18px]">publish</span>
                <span>Simpan &amp; Terbitkan</span>
            </button>
        </div>
    </div>

    <form id="formBerita" method="POST" action="{{ route('bkk.admin.berita.store') }}" enctype="multipart/form-data" class="flex flex-col gap-6">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Main Form (2 Cols) -->
            <div class="lg:col-span-2 flex flex-col gap-5">
                <!-- Judul Artikel -->
                <div class="bg-surface-container-lowest p-5 rounded-xl border border-surface-variant/30 shadow-sm flex flex-col gap-2">
                    <label for="judul" class="text-xs font-bold text-on-surface uppercase tracking-wider">
                        Judul Berita / Agenda <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="judul" name="judul" value="{{ old('judul') }}" required
                           placeholder="Contoh: Job Fair Akbar SMK Plus Pelita Nusantara 2025" 
                           class="w-full px-3.5 py-2.5 text-sm rounded-lg bg-surface-container border border-surface-variant/50 text-on-surface font-semibold focus:outline-none focus:ring-2 focus:ring-primary/20"/>
                    
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-[11px] text-on-surface-variant">Slug URL:</span>
                        <input type="text" id="slug" name="slug" value="{{ old('slug') }}" placeholder="slug-otomatis" 
                               class="flex-1 px-2 py-1 text-[11px] rounded bg-surface-container/60 border border-surface-variant/40 text-on-surface-variant focus:outline-none"/>
                    </div>
                </div>

                <!-- Ringkasan / Excerpt -->
                <div class="bg-surface-container-lowest p-5 rounded-xl border border-surface-variant/30 shadow-sm flex flex-col gap-2">
                    <label for="ringkasan" class="text-xs font-bold text-on-surface uppercase tracking-wider">
                        Ringkasan Singkat (Lead / Excerpt) <span class="text-red-500">*</span>
                    </label>
                    <textarea id="ringkasan" name="ringkasan" rows="3" required
                              placeholder="Tuliskan 1-2 kalimat ringkasan yang menarik perhatian calon pembaca di halaman katalog..." 
                              class="w-full px-3.5 py-2 text-xs rounded-lg bg-surface-container border border-surface-variant/50 text-on-surface leading-relaxed focus:outline-none focus:ring-2 focus:ring-primary/20">{{ old('ringkasan') }}</textarea>
                </div>

                <!-- Konten Markdown (EasyMDE) -->
                <div class="bg-surface-container-lowest p-5 rounded-xl border border-surface-variant/30 shadow-sm flex flex-col gap-2">
                    <div class="flex items-center justify-between">
                        <label for="markdownEditor" class="text-xs font-bold text-on-surface uppercase tracking-wider flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px] text-primary">markdown</span>
                            <span>Konten Utama (Markdown Editor)</span>
                            <span class="text-red-500">*</span>
                        </label>
                        <span class="text-[11px] text-on-surface-variant">Mendukung Live Preview &amp; Toolbar</span>
                    </div>
                    <textarea id="markdownEditor" name="konten">{{ old('konten') }}</textarea>
                </div>
            </div>

            <!-- Right Sidebar Meta (1 Col) -->
            <div class="flex flex-col gap-5">
                <!-- Status & Kategori Card -->
                <div class="bg-surface-container-lowest p-5 rounded-xl border border-surface-variant/30 shadow-sm flex flex-col gap-4">
                    <h3 class="font-bold text-xs text-on-surface uppercase tracking-wider border-b border-surface-variant/40 pb-2">
                        Publikasi &amp; Taksonomi
                    </h3>

                    <div class="flex flex-col gap-1.5">
                        <label for="status" class="text-xs font-semibold text-on-surface-variant">Status</label>
                        <select id="status" name="status" class="w-full py-2 px-3 text-xs rounded-lg bg-surface-container border border-surface-variant/50 text-on-surface font-semibold focus:outline-none">
                            <option value="PUBLISHED" {{ old('status', 'PUBLISHED') === 'PUBLISHED' ? 'selected' : '' }}>Terbitkan Langsung (PUBLISHED)</option>
                            <option value="DRAFT" {{ old('status') === 'DRAFT' ? 'selected' : '' }}>Simpan sebagai Draf (DRAFT)</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="kategori_id" class="text-xs font-semibold text-on-surface-variant">Kategori <span class="text-red-500">*</span></label>
                        <select id="kategori_id" name="kategori_id" required class="w-full py-2 px-3 text-xs rounded-lg bg-surface-container border border-surface-variant/50 text-on-surface focus:outline-none">
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('kategori_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-2 pt-2 border-t border-surface-variant/30">
                        <input type="checkbox" id="is_featured" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}
                               class="w-4 h-4 rounded text-primary focus:ring-primary border-surface-variant"/>
                        <label for="is_featured" class="text-xs font-semibold text-on-surface cursor-pointer">
                            Jadikan Artikel Hero / Headline Utama
                        </label>
                    </div>

                    <div class="flex flex-col gap-1.5 pt-2 border-t border-surface-variant/30">
                        <label for="estimasi_baca" class="text-xs font-semibold text-on-surface-variant">Estimasi Waktu Baca</label>
                        <input type="text" id="estimasi_baca" name="estimasi_baca" value="{{ old('estimasi_baca') }}" placeholder="Otomatis dihitung jika kosong (misal: 4 Menit Baca)"
                               class="w-full px-3 py-1.5 text-xs rounded-lg bg-surface-container border border-surface-variant/50 text-on-surface focus:outline-none"/>
                    </div>
                </div>

                <!-- Gambar Sampul Card -->
                <div class="bg-surface-container-lowest p-5 rounded-xl border border-surface-variant/30 shadow-sm flex flex-col gap-3">
                    <h3 class="font-bold text-xs text-on-surface uppercase tracking-wider border-b border-surface-variant/40 pb-2">
                        Gambar Sampul (Cover)
                    </h3>

                    <div class="flex flex-col gap-1.5">
                        <label for="gambar_sampul" class="text-xs font-semibold text-on-surface-variant">URL Gambar Web</label>
                        <input type="url" id="gambar_sampul" name="gambar_sampul" value="{{ old('gambar_sampul') }}" placeholder="https://..."
                               class="w-full px-3 py-1.5 text-xs rounded-lg bg-surface-container border border-surface-variant/50 text-on-surface focus:outline-none"/>
                    </div>

                    <div class="text-center text-[11px] text-on-surface-variant/60 font-semibold">— ATAU UPLOAD —</div>

                    <div class="flex flex-col gap-1.5">
                        <label for="gambar_sampul_file" class="text-xs font-semibold text-on-surface-variant">Upload Berkas Gambar</label>
                        <input type="file" id="gambar_sampul_file" name="gambar_sampul_file" accept="image/*"
                               class="w-full text-xs text-on-surface-variant file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-surface-container file:text-on-surface hover:file:bg-surface-variant cursor-pointer"/>
                    </div>

                    <div class="flex flex-col gap-1.5 pt-2 border-t border-surface-variant/30">
                        <label for="caption_gambar" class="text-xs font-semibold text-on-surface-variant">Keterangan / Caption Gambar</label>
                        <input type="text" id="caption_gambar" name="caption_gambar" value="{{ old('caption_gambar') }}" placeholder="Dokumentasi: Humas Penus"
                               class="w-full px-3 py-1.5 text-xs rounded-lg bg-surface-container border border-surface-variant/50 text-on-surface focus:outline-none"/>
                    </div>
                </div>

                <!-- Penulis / Kontributor Card -->
                <div class="bg-surface-container-lowest p-5 rounded-xl border border-surface-variant/30 shadow-sm flex flex-col gap-3">
                    <h3 class="font-bold text-xs text-on-surface uppercase tracking-wider border-b border-surface-variant/40 pb-2">
                        Atribusi Penulis
                    </h3>

                    <div class="flex flex-col gap-1.5">
                        <label for="penulis_nama" class="text-xs font-semibold text-on-surface-variant">Nama Penulis <span class="text-red-500">*</span></label>
                        <input type="text" id="penulis_nama" name="penulis_nama" value="{{ old('penulis_nama', $authUser['nama_lengkap'] ?? $authUser['username'] ?? 'Tim Humas & BKK Penus') }}" required
                               class="w-full px-3 py-1.5 text-xs rounded-lg bg-surface-container border border-surface-variant/50 text-on-surface focus:outline-none"/>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="penulis_jabatan" class="text-xs font-semibold text-on-surface-variant">Jabatan / Satuan</label>
                        <input type="text" id="penulis_jabatan" name="penulis_jabatan" value="{{ old('penulis_jabatan', 'Koordinator BKK SMK Plus Pelita Nusantara') }}"
                               class="w-full px-3 py-1.5 text-xs rounded-lg bg-surface-container border border-surface-variant/50 text-on-surface focus:outline-none"/>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="penulis_avatar" class="text-xs font-semibold text-on-surface-variant">URL Foto Avatar</label>
                        <input type="url" id="penulis_avatar" name="penulis_avatar" value="{{ old('penulis_avatar') }}" placeholder="https://..."
                               class="w-full px-3 py-1.5 text-xs rounded-lg bg-surface-container border border-surface-variant/50 text-on-surface focus:outline-none"/>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="penulis_bio" class="text-xs font-semibold text-on-surface-variant">Bio Singkat Penulis</label>
                        <textarea id="penulis_bio" name="penulis_bio" rows="2" placeholder="Catatan profil singkat penulis..."
                                  class="w-full px-3 py-1.5 text-xs rounded-lg bg-surface-container border border-surface-variant/50 text-on-surface focus:outline-none">{{ old('penulis_bio') }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
    <!-- EasyMDE Script -->
    <script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Slug Generator
            const judulInput = document.getElementById('judul');
            const slugInput = document.getElementById('slug');
            if (judulInput && slugInput) {
                judulInput.addEventListener('input', function () {
                    if (!slugInput.dataset.manual) {
                        slugInput.value = judulInput.value
                            .toLowerCase()
                            .trim()
                            .replace(/[^a-z0-9\s-]/g, '')
                            .replace(/[\s-]+/g, '-');
                    }
                });
                slugInput.addEventListener('input', function () {
                    slugInput.dataset.manual = "1";
                });
            }

            // EasyMDE Initializer
            const editorEl = document.getElementById('markdownEditor');
            if (editorEl) {
                new EasyMDE({
                    element: editorEl,
                    spellChecker: false,
                    placeholder: "Tuliskan isi berita di sini menggunakan format Markdown...\n\nContoh:\n## Judul Bagian\nParagraf konten artikel.\n\n> Catatan penting kutipan.\n\n* Poin penting satu\n* Poin penting dua",
                    autosave: {
                        enabled: false,
                    },
                    toolbar: [
                        "bold", "italic", "heading-2", "heading-3", "|",
                        "quote", "unordered-list", "ordered-list", "|",
                        "link", "image", "table", "code", "|",
                        "preview", "side-by-side", "fullscreen", "|",
                        "guide"
                    ],
                    minHeight: "420px"
                });
            }
        });
    </script>
@endpush
