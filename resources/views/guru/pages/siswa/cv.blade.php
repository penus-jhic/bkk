@extends('guru.master')

@section('title', 'Tinjau CV Siswa - ' . ($siswa->nis ?? 'NIS') . ' - Portal Guru')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dompurify@3.2.4/dist/purify.min.js"></script>
<style>
    @media print {
        body * { visibility: hidden; }
        #cvPaper, #cvPaper * { visibility: visible; }
        #cvPaper { position: absolute; left: 0; top: 0; width: 100%; max-width: 100%; border: none; box-shadow: none; padding: 20px; }
    }
</style>
@endpush

@php
    $cvScore = $cv?->score ?? 85;
    $hasDoc = $cv?->has_document_file;
    $rawMd = $cv?->raw_markdown ?? '';
    $reviewStatus = $cv?->status_review_guru ?? 'Belum Direview';
    $catatanGuru = $cv?->catatan_guru ?? '';
    $badgeReview = match($reviewStatus) {
        'Disetujui' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
        'Perlu Revisi' => 'bg-red-100 text-red-800 border-red-300',
        default => 'bg-slate-100 text-slate-700 border-slate-200',
    };
@endphp

@section('content')
<div class="space-y-6 fade-up">
    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-brand-ink/10">
        <div class="flex items-center gap-3">
            <a href="{{ route('bkk.guru.siswa.index') }}" class="w-10 h-10 rounded-full border border-brand-ink/15 hover:bg-brand-paper grid place-items-center text-navy transition-colors shrink-0" title="Kembali ke Direktori">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-bold text-navy tracking-tight">Tinjauan CV Siswa</h1>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $badgeReview }}">
                        <span>Status: {{ $reviewStatus }}</span>
                    </span>
                    @if($cv?->checksum)
                        <span class="hidden sm:inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-mono bg-navy/5 text-navy border border-line" title="Checksum SHA-256">
                            <i data-lucide="hash" class="w-3 h-3 text-muted"></i>
                            <span>{{ substr($cv->checksum, 0, 10) }}…</span>
                        </span>
                    @endif
                </div>
                <p class="text-xs sm:text-sm text-muted mt-0.5">NIS: <strong>{{ $siswa->nis ?? '-' }}</strong> · {{ $siswa->jurusan ?? 'Jurusan' }} ({{ $siswa->kelas ?? 'Kelas XII' }})</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if($hasDoc)
                <a href="{{ $cv->file_url }}" target="_blank" class="h-10 px-4 rounded-full bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold inline-flex items-center gap-2 shadow-xs transition-colors">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    <span>Unduh Berkas {{ strtoupper(pathinfo($cv->file_name, PATHINFO_EXTENSION)) }}</span>
                </a>
            @endif

            <button onclick="window.print()" type="button" class="h-10 px-4 rounded-full border border-brand-ink/15 bg-white hover:bg-brand-paper text-navy text-xs font-semibold inline-flex items-center gap-2 transition-colors cursor-pointer">
                <i data-lucide="printer" class="w-4 h-4"></i>
                <span>Cetak CV</span>
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
        <!-- Kolom Kiri: Profil Siswa, Evaluasi Guru & Status PKL (5 Cols) -->
        <div class="xl:col-span-5 space-y-6">
            <!-- Profil Singkat Siswa -->
            <div class="p-5 bg-white rounded-3xl border border-brand-ink/10 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-brand-ink/10 pb-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-800 grid place-items-center font-bold text-sm">
                            <i data-lucide="user" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <div class="font-bold text-sm text-navy">Profil Siswa</div>
                            <div class="text-[11px] text-muted">{{ $siswa->status_kelulusan ?? 'SISWA_AKTIF' }}</div>
                        </div>
                    </div>
                    <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                        {{ $siswa->angkatan ? 'Angkatan ' . $siswa->angkatan : 'Aktif' }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3 text-xs">
                    <div>
                        <span class="text-muted block text-[10px] uppercase">Nomor Induk Siswa (NIS)</span>
                        <span class="font-semibold text-navy">{{ $siswa->nis ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-muted block text-[10px] uppercase">NISN</span>
                        <span class="font-semibold text-navy">{{ $siswa->nisn ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-muted block text-[10px] uppercase">Jurusan Kejuruan</span>
                        <span class="font-semibold text-navy">{{ $siswa->jurusan ?? '-' }}</span>
                    </div>
                    <div>
                        <span class="text-muted block text-[10px] uppercase">Kelas</span>
                        <span class="font-semibold text-navy">{{ $siswa->kelas ?? 'Kelas XII' }}</span>
                    </div>
                </div>

                @if(!empty($siswa->headline_profesi) || !empty($siswa->bio_singkat))
                    <div class="pt-2 border-t border-brand-ink/10 text-xs space-y-1">
                        @if(!empty($siswa->headline_profesi))
                            <div class="font-semibold text-navy">{{ $siswa->headline_profesi }}</div>
                        @endif
                        @if(!empty($siswa->bio_singkat))
                            <p class="text-muted text-[11px] leading-relaxed">{{ $siswa->bio_singkat }}</p>
                        @endif
                    </div>
                @endif
            </div>

            <!-- Form Evaluasi & Review CV Guru (Actionable!) -->
            <div class="p-5 bg-white rounded-3xl border-2 border-emerald-600/30 shadow-sm space-y-4">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-full bg-emerald-600 text-white grid place-items-center shadow-xs">
                        <i data-lucide="award" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-navy">Form Review Guru Pembimbing</h2>
                        <p class="text-[11px] text-muted">Berikan status validasi dan catatan evaluasi ke siswa.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('bkk.guru.siswa.cv.feedback', $siswa->user_id) }}" class="space-y-4">
                    @csrf
                    @if($cv)
                        <input type="hidden" name="cv_id" value="{{ $cv->id }}" />
                    @endif

                    <div>
                        <label class="block text-xs font-bold text-navy mb-1.5">Status Validasi CV Siswa <span class="text-red-500">*</span></label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="flex items-center gap-2 p-2.5 rounded-xl border border-brand-ink/15 hover:bg-emerald-50 cursor-pointer text-xs font-semibold text-navy">
                                <input type="radio" name="status_review_guru" value="Disetujui" {{ $reviewStatus === 'Disetujui' ? 'checked' : '' }} class="text-emerald-700 focus:ring-emerald-600" />
                                <span>Disetujui</span>
                            </label>
                            <label class="flex items-center gap-2 p-2.5 rounded-xl border border-brand-ink/15 hover:bg-red-50 cursor-pointer text-xs font-semibold text-navy">
                                <input type="radio" name="status_review_guru" value="Perlu Revisi" {{ $reviewStatus === 'Perlu Revisi' ? 'checked' : '' }} class="text-red-600 focus:ring-red-500" />
                                <span>Perlu Revisi</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-navy mb-1.5">Catatan Bimbingan / Feedback untuk Siswa</label>
                        <textarea
                            name="catatan_guru"
                            rows="4"
                            placeholder="Contoh: Susunan pengalaman portofolio sudah baik. Tolong tambahkan tautan proyek GitHub atau sertifikasi keahlian jika ada..."
                            class="w-full p-3 rounded-xl bg-canvas border border-brand-ink/15 text-xs text-navy focus:bg-white focus:outline-none focus:border-emerald-600 transition-all leading-relaxed"
                        >{{ old('catatan_guru', $catatanGuru) }}</textarea>
                        <p class="text-[10px] text-muted mt-1">Catatan ini akan langsung tampil di portal CV siswa dan dikirimkan via notifikasi sistem.</p>
                    </div>

                    <button type="submit" class="w-full h-10 rounded-full bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-xs transition-colors cursor-pointer inline-flex items-center justify-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                        <span>Simpan Hasil Review CV</span>
                    </button>
                </form>
            </div>

            <!-- Status PKL Terkini Siswa -->
            @if($activePenempatan)
                <div class="p-5 bg-white rounded-3xl border border-brand-ink/10 shadow-sm space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i data-lucide="briefcase" class="w-4 h-4 text-emerald-700"></i>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-navy">Status Penempatan PKL</h3>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                            {{ $activePenempatan->status }}
                        </span>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-canvas text-xs space-y-2">
                        <div>
                            <span class="text-muted block text-[10px]">Mitra Industri</span>
                            <strong class="text-navy text-sm">{{ $activePenempatan->mitra->nama_perusahaan ?? 'Mitra' }}</strong>
                        </div>
                        <div class="grid grid-cols-2 gap-2 text-[11px]">
                            <div>
                                <span class="text-muted block text-[10px]">Divisi</span>
                                <span class="font-semibold text-navy">{{ $activePenempatan->unit_kerja_divisi ?? '-' }}</span>
                            </div>
                            <div>
                                <span class="text-muted block text-[10px]">Total Jam</span>
                                <span class="font-semibold text-navy">{{ $activePenempatan->total_jam_tercapai ?? 0 }} / 640 Jam</span>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-brand-ink/10 flex justify-end">
                            <a href="{{ route('bkk.guru.penempatan.show', $activePenempatan->id) }}" class="text-xs font-semibold text-emerald-700 hover:underline inline-flex items-center gap-1">
                                <span>Kelola Jurnal & Nilai PKL</span>
                                <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                            </a>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Kolom Kanan: Pratinjau Berkas Dokumen & CV ATS (7 Cols) -->
        <div class="xl:col-span-7 space-y-4">
            <!-- Kartu Berkas Dokumen Fisik (PDF / DOCX) jika ada -->
            @if($hasDoc)
                <div class="p-4 sm:p-5 rounded-3xl bg-gradient-to-r from-emerald-900 to-teal-900 text-white shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-white/10 grid place-items-center text-emerald-300 shrink-0">
                            <i data-lucide="file-check" class="w-6 h-6"></i>
                        </div>
                        <div>
                            <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-300">Berkas Dokumen Asli Diunggah Siswa</div>
                            <div class="font-bold text-sm text-white mt-0.5 truncate max-w-sm">{{ $cv->file_name ?? 'Berkas Dokumen CV' }}</div>
                            <div class="text-[11px] text-emerald-100/80 mt-0.5">Ukuran: {{ $cv->file_size_formatted }} · Format: {{ strtoupper(pathinfo($cv->file_name, PATHINFO_EXTENSION)) }}</div>
                        </div>
                    </div>

                    <a href="{{ $cv->file_url }}" target="_blank" class="h-10 px-5 rounded-full bg-white text-emerald-900 hover:bg-emerald-50 text-xs font-bold inline-flex items-center justify-center gap-2 shadow-xs transition-colors shrink-0">
                        <i data-lucide="external-link" class="w-4 h-4"></i>
                        <span>Buka / Unduh Berkas</span>
                    </a>
                </div>
            @else
                <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-900 flex items-center gap-3 text-xs">
                    <i data-lucide="info" class="w-5 h-5 text-amber-600 shrink-0"></i>
                    <div>
                        <span class="font-bold">Siswa belum mengunggah dokumen fisik (PDF/DOCX).</span>
                        <p class="text-[11px] text-amber-800 mt-0.5">Berikut adalah pratinjau naskah CV ATS digital yang disusun siswa melalui Editor BKK.</p>
                    </div>
                </div>
            @endif

            <!-- Lembar Pratinjau CV ATS Friendly -->
            <div class="bg-white border border-brand-ink/10 rounded-3xl p-6 sm:p-10 shadow-sm min-h-[700px]">
                <div class="flex items-center justify-between border-b border-brand-ink/10 pb-4 mb-6 text-xs text-muted">
                    <div class="flex items-center gap-2">
                        <i data-lucide="file-text" class="w-4 h-4 text-emerald-700"></i>
                        <span class="font-bold text-navy">Format Naskah CV ATS Standar Industri</span>
                    </div>
                    <span>Skor Penus AI: <strong class="text-navy font-bold">{{ $cvScore }}/100</strong></span>
                </div>

                <div id="cvRenderedContent" class="prose prose-sm max-w-none prose-navy text-xs leading-relaxed">
                    <!-- MarkDown diinject via JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const mdContent = @json($rawMd);

    document.addEventListener('DOMContentLoaded', function() {
        const container = document.getElementById('cvRenderedContent');
        if (container && typeof marked !== 'undefined') {
            if (!mdContent || !mdContent.trim()) {
                container.innerHTML = '<p class="text-muted italic text-center py-10">Konten CV belum diisi oleh siswa.</p>';
                return;
            }
            const parsed = marked.parse(mdContent);
            container.innerHTML = typeof DOMPurify !== 'undefined' ? DOMPurify.sanitize(parsed) : parsed;
        }
    });
</script>
@endpush
