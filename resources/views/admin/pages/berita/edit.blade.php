@extends('admin.master')

@section('title', 'Edit Berita: ' . $berita->judul . ' - Admin BKK')

@push('styles')
    <!-- EasyMDE Markdown Editor CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css"/>
    <style>
        .EasyMDEContainer .CodeMirror {
            border-radius: 0 0 0.75rem 0.75rem;
            border-color: #E2E8F0;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 13px;
            background: #ffffff;
        }
        .EasyMDEContainer .editor-toolbar {
            border-radius: 0.75rem 0.75rem 0 0;
            border-color: #E2E8F0;
            background: #F5F7FA;
        }
        .editor-preview, .editor-preview-side {
            background: #ffffff;
            color: #1E293B;
            padding: 1.5rem;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            line-height: 1.6;
        }
        .editor-preview h1, .editor-preview-side h1 { font-size: 1.75rem; font-weight: 700; margin-bottom: 1rem; color: #7A1018; }
        .editor-preview h2, .editor-preview-side h2 { font-size: 1.35rem; font-weight: 700; margin-top: 1.5rem; margin-bottom: 0.75rem; color: #1E293B; border-bottom: 1px solid #E2E8F0; padding-bottom: 0.25rem; }
        .editor-preview h3, .editor-preview-side h3 { font-size: 1.15rem; font-weight: 600; margin-top: 1rem; margin-bottom: 0.5rem; color: #875300; }
        .editor-preview blockquote, .editor-preview-side blockquote { border-left: 4px solid #7A1018; padding-left: 1rem; color: #475569; font-style: italic; margin: 1rem 0; background: #F5F7FA; padding: 0.5rem 1rem; border-radius: 0 0.5rem 0.5rem 0; }
        .editor-preview ul, .editor-preview-side ul { list-style-type: disc; margin-left: 1.5rem; margin-bottom: 1rem; }
        .editor-preview ol, .editor-preview-side ol { list-style-type: decimal; margin-left: 1.5rem; margin-bottom: 1rem; }
        .editor-preview table, .editor-preview-side table { width: 100%; border-collapse: collapse; margin-bottom: 1rem; }
        .editor-preview th, .editor-preview-side th, .editor-preview td, .editor-preview-side td { border: 1px solid #E2E8F0; padding: 0.5rem; }
        .editor-preview th, .editor-preview-side th { background: #EEF2F6; }
    </style>
@endpush

@section('content')
<div class="flex flex-col gap-6 max-w-5xl mx-auto">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-2 text-xs text-on-surface-variant">
            <a href="{{ route('bkk.admin.berita.index') }}" class="hover:text-primary transition-colors flex items-center gap-1">
                <span class="material-symbols-outlined text-[16px]">arrow_back</span>
                <span>Kembali ke Daftar</span>
            </a>
            <span>/</span>
            <span class="text-on-surface font-semibold truncate max-w-xs sm:max-w-md">Edit: {{ $berita->judul }}</span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('bkk.berita.detail', $berita->slug) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-full bg-surface-container text-on-surface hover:bg-surface-variant font-semibold text-xs transition-colors">
                <span class="material-symbols-outlined text-[16px]">open_in_new</span>
                <span>Buka di Web</span>
            </a>
            <button type="submit" form="formBerita" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-primary text-on-primary font-bold text-xs hover:bg-primary/90 transition-all shadow-sm">
                <span class="material-symbols-outlined text-[18px]">save</span>
                <span>Simpan Perubahan</span>
            </button>
        </div>
    </div>

    <form id="formBerita" method="POST" action="{{ route('bkk.admin.berita.update', $berita->id) }}" enctype="multipart/form-data" class="flex flex-col gap-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left Main Form (2 Cols) -->
            <div class="lg:col-span-2 flex flex-col gap-5">
                <!-- Judul Artikel -->
                <div class="bg-surface-container-lowest p-5 rounded-xl border border-surface-variant/30 shadow-sm flex flex-col gap-2">
                    <label for="judul" class="text-xs font-bold text-on-surface uppercase tracking-wider">
                        Judul Berita / Agenda <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="judul" name="judul" value="{{ old('judul', $berita->judul) }}" required
                           class="w-full px-3.5 py-2.5 text-sm rounded-lg bg-surface-container border border-surface-variant/50 text-on-surface font-semibold focus:outline-none focus:ring-2 focus:ring-primary/20"/>
                    
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-[11px] text-on-surface-variant">Slug URL:</span>
                        <input type="text" id="slug" name="slug" value="{{ old('slug', $berita->slug) }}" required
                               class="flex-1 px-2 py-1 text-[11px] rounded bg-surface-container/60 border border-surface-variant/40 text-on-surface-variant focus:outline-none"/>
                    </div>
                </div>

                <!-- Ringkasan / Excerpt -->
                <div class="bg-surface-container-lowest p-5 rounded-xl border border-surface-variant/30 shadow-sm flex flex-col gap-2">
                    <label for="ringkasan" class="text-xs font-bold text-on-surface uppercase tracking-wider">
                        Ringkasan Singkat (Lead / Excerpt) <span class="text-red-500">*</span>
                    </label>
                    <textarea id="ringkasan" name="ringkasan" rows="3" required
                              class="w-full px-3.5 py-2 text-xs rounded-lg bg-surface-container border border-surface-variant/50 text-on-surface leading-relaxed focus:outline-none focus:ring-2 focus:ring-primary/20">{{ old('ringkasan', $berita->ringkasan) }}</textarea>
                </div>

                <!-- Konten Markdown (EasyMDE) -->
                <div class="bg-surface-container-lowest p-5 rounded-xl border border-surface-variant/30 shadow-sm flex flex-col gap-2">
                    <div class="flex items-center justify-between">
                        <label for="markdownEditor" class="text-xs font-bold text-on-surface uppercase tracking-wider flex items-center gap-1.5">
                            <span class="material-symbols-outlined text-[18px] text-primary">markdown</span>
                            <span>Konten Utama (Markdown Editor)</span>
                            <span class="text-red-500">*</span>
                        </label>
                        <span class="text-[11px] text-on-surface-variant">Live Preview &amp; Toolbar Aktif</span>
                    </div>
                    <textarea id="markdownEditor" name="konten">{{ old('konten', $berita->konten) }}</textarea>
                </div>

                <!-- Danger Zone (Delete) -->
                <div class="bg-red-50/50 p-5 rounded-xl border border-red-200/80 flex items-center justify-between">
                    <div class="flex flex-col">
                        <span class="text-xs font-bold text-red-800 uppercase tracking-wider">Hapus Berita Ini</span>
                        <span class="text-[11px] text-red-600">Tindakan ini permanen dan tidak dapat dipulihkan.</span>
                    </div>
                    <button type="button" onclick="if(confirm('Apakah Anda yakin ingin menghapus artikel ini?')) { document.getElementById('deleteForm').submit(); }"
                            class="px-4 py-2 rounded-lg bg-red-600 text-white font-semibold text-xs hover:bg-red-700 transition-colors">
                        Hapus Artikel
                    </button>
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
                            <option value="PUBLISHED" {{ old('status', $berita->status) === 'PUBLISHED' ? 'selected' : '' }}>Terbitkan Langsung (PUBLISHED)</option>
                            <option value="DRAFT" {{ old('status', $berita->status) === 'DRAFT' ? 'selected' : '' }}>Simpan sebagai Draf (DRAFT)</option>
                        </select>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="kategori_id" class="text-xs font-semibold text-on-surface-variant">Kategori <span class="text-red-500">*</span></label>
                        <select id="kategori_id" name="kategori_id" required class="w-full py-2 px-3 text-xs rounded-lg bg-surface-container border border-surface-variant/50 text-on-surface focus:outline-none">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('kategori_id', $berita->kategori_id) == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->nama }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="flex items-center gap-2 pt-2 border-t border-surface-variant/30">
                        <input type="checkbox" id="is_featured" name="is_featured" value="1" {{ old('is_featured', $berita->is_featured) ? 'checked' : '' }}
                               class="w-4 h-4 rounded text-primary focus:ring-primary border-surface-variant"/>
                        <label for="is_featured" class="text-xs font-semibold text-on-surface cursor-pointer">
                            Jadikan Artikel Hero / Headline Utama
                        </label>
                    </div>

                    <div class="flex flex-col gap-1.5 pt-2 border-t border-surface-variant/30">
                        <label for="estimasi_baca" class="text-xs font-semibold text-on-surface-variant">Estimasi Waktu Baca</label>
                        <input type="text" id="estimasi_baca" name="estimasi_baca" value="{{ old('estimasi_baca', $berita->estimasi_baca) }}"
                               class="w-full px-3 py-1.5 text-xs rounded-lg bg-surface-container border border-surface-variant/50 text-on-surface focus:outline-none"/>
                    </div>

                    <div class="flex flex-col gap-1 pt-2 border-t border-surface-variant/30 text-[11px] text-on-surface-variant">
                        <div class="flex justify-between">
                            <span>Jumlah Tayangan:</span>
                            <span class="font-bold text-on-surface">{{ number_format($berita->views_count) }} kali</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Dibuat:</span>
                            <span>{{ $berita->created_at->format('d M Y H:i') }}</span>
                        </div>
                    </div>
                </div>

                <!-- Gambar Sampul Card -->
                <div class="bg-surface-container-lowest p-5 rounded-xl border border-surface-variant/30 shadow-sm flex flex-col gap-3">
                    <h3 class="font-bold text-xs text-on-surface uppercase tracking-wider border-b border-surface-variant/40 pb-2">
                        Gambar Sampul (Cover)
                    </h3>

                    @if($berita->gambar_sampul)
                        <div class="relative w-full aspect-video rounded-lg overflow-hidden border border-surface-variant/40 mb-2">
                            <img src="{{ $berita->gambar_sampul }}" alt="{{ $berita->judul }}" class="w-full h-full object-cover"/>
                        </div>
                    @endif

                    <div class="flex flex-col gap-1.5">
                        <label for="gambar_sampul" class="text-xs font-semibold text-on-surface-variant">URL Gambar Web</label>
                        <input type="url" id="gambar_sampul" name="gambar_sampul" value="{{ old('gambar_sampul', $berita->gambar_sampul) }}"
                               class="w-full px-3 py-1.5 text-xs rounded-lg bg-surface-container border border-surface-variant/50 text-on-surface focus:outline-none"/>
                    </div>

                    <div class="text-center text-[11px] text-on-surface-variant/60 font-semibold">— ATAU GANTI DENGAN FILE —</div>

                    <div class="flex flex-col gap-1.5">
                        <label for="gambar_sampul_file" class="text-xs font-semibold text-on-surface-variant">Upload Berkas Gambar Baru</label>
                        <input type="file" id="gambar_sampul_file" name="gambar_sampul_file" accept="image/*"
                               class="w-full text-xs text-on-surface-variant file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-surface-container file:text-on-surface hover:file:bg-surface-variant cursor-pointer"/>
                    </div>

                    <div class="flex flex-col gap-1.5 pt-2 border-t border-surface-variant/30">
                        <label for="caption_gambar" class="text-xs font-semibold text-on-surface-variant">Keterangan / Caption Gambar</label>
                        <input type="text" id="caption_gambar" name="caption_gambar" value="{{ old('caption_gambar', $berita->caption_gambar) }}"
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
                        <input type="text" id="penulis_nama" name="penulis_nama" value="{{ old('penulis_nama', $berita->penulis_nama) }}" required
                               class="w-full px-3 py-1.5 text-xs rounded-lg bg-surface-container border border-surface-variant/50 text-on-surface focus:outline-none"/>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="penulis_jabatan" class="text-xs font-semibold text-on-surface-variant">Jabatan / Satuan</label>
                        <input type="text" id="penulis_jabatan" name="penulis_jabatan" value="{{ old('penulis_jabatan', $berita->penulis_jabatan) }}"
                               class="w-full px-3 py-1.5 text-xs rounded-lg bg-surface-container border border-surface-variant/50 text-on-surface focus:outline-none"/>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="penulis_avatar" class="text-xs font-semibold text-on-surface-variant">URL Foto Avatar</label>
                        <input type="url" id="penulis_avatar" name="penulis_avatar" value="{{ old('penulis_avatar', $berita->penulis_avatar) }}"
                               class="w-full px-3 py-1.5 text-xs rounded-lg bg-surface-container border border-surface-variant/50 text-on-surface focus:outline-none"/>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label for="penulis_bio" class="text-xs font-semibold text-on-surface-variant">Bio Singkat Penulis</label>
                        <textarea id="penulis_bio" name="penulis_bio" rows="2"
                                  class="w-full px-3 py-1.5 text-xs rounded-lg bg-surface-container border border-surface-variant/50 text-on-surface focus:outline-none">{{ old('penulis_bio', $berita->penulis_bio) }}</textarea>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Hidden Delete Form -->
    <form id="deleteForm" action="{{ route('bkk.admin.berita.destroy', $berita->id) }}" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</div>
@endsection

@push('scripts')
    <!-- EasyMDE Script -->
    <script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editorEl = document.getElementById('markdownEditor');
            if (editorEl) {
                new EasyMDE({
                    element: editorEl,
                    spellChecker: false,
                    placeholder: "Tuliskan isi berita di sini menggunakan format Markdown...",
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
