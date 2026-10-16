@extends('me.master')

@section('title', 'CV & AI Scoring Hub - BKK SMK Plus Pelita Nusantara')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dompurify@3.2.4/dist/purify.min.js"></script>
<style>
    /* Cetak / simpan PDF: hanya salinan lembar CV di #cvPrintRoot (dibuat saat beforeprint), elemen lain disembunyikan
       dengan display:none supaya tidak menyisakan halaman kosong. Margin halaman 0 membuat browser tidak mencetak
       judul tab & URL (mis. "localhost") di atas/bawah kertas, jadi jarak tepi diganti padding lembar CV. */
    @page { size: A4; margin: 0; }
    #cvPrintRoot { display: none; }
    @media print {
        body > *:not(#cvPrintRoot) { display: none !important; }
        html, body { background: #fff !important; }
        #cvPrintRoot { display: block; padding: 16mm 18mm; }
        #cvPrintRoot [data-sketch] { display: none; }
    }
</style>
@endpush

@php
    $feedback = $cvFeedback ?? [];
    $scoreVal = (int) ($activeCv?->score ?? ($feedback['total_score'] ?? $cvScore['total'] ?? 85));
    $label = $feedback['grade_label'] ?? (($scoreVal >= 90) ? 'Sangat Baik' : (($scoreVal >= 80) ? 'Baik' : (($scoreVal >= 70) ? 'Cukup' : 'Perlu Perbaikan')));
    $percentile = (int) ($feedback['percentile'] ?? min(97, max(50, $scoreVal - 6)));
    $verdict = $feedback['summary_verdict'] ?? "CV Anda dinilai lebih optimal dari {$percentile}% pelamar pada keahlian serupa. Buka Editor CV untuk menerapkan rekomendasi perbaikan AI.";
    $parameters = $feedback['parameters'] ?? ($cvScore['params'] ?? []);
    $detectedSections = $feedback['detected_sections'] ?? ['HEADER_IDENTITY', 'SUMMARY', 'EXPERIENCE', 'EDUCATION', 'SKILLS'];
    $missingSections = $feedback['missing_sections'] ?? [];
    $paramIcons = [
        'param_industry_fit' => 'target',
        'param_completeness' => 'layers',
        'param_ats_format' => 'file-search',
        'param_portfolio_impact' => 'award',
    ];
    $fallbackIcons = ['target', 'layers', 'file-search', 'award'];

    // Tiga warna penanda skor (tanpa gradasi): hijau >= 85, biru 70-84, merah < 70. Sama dengan scoreTone() di JS.
    $scoreTone = fn (int $score): array => match (true) {
        $score >= 85 => ['bar' => 'bg-emerald-500', 'badge' => 'bg-emerald-100 text-emerald-800', 'hex' => '#10B981'],
        $score >= 70 => ['bar' => 'bg-navy', 'badge' => 'bg-navy/10 text-navy', 'hex' => '#1E293B'],
        default => ['bar' => 'bg-brand-darkred', 'badge' => 'bg-brand-darkred/10 text-brand-darkred', 'hex' => '#7A1018'],
    };
    $totalTone = $scoreTone($scoreVal);
    $candidateName = $authUser['nama_lengkap'] ?? $authUser['username'] ?? 'Siswa';
@endphp

@section('content')
<div class="space-y-8 fade-up">
    {{-- Header --}}
    <div class="flex flex-col lg:flex-row lg:items-end justify-between gap-4">
        <div>
            <p class="dash-eyebrow">Portal Siswa · AI CV Enhancer</p>
            <h1 class="dash-title font-display text-brand-ink">CV &amp; <x-sketch.underline>AI Scoring</x-sketch.underline> Hub</h1>
            <p class="text-sm text-brand-ink/65 mt-1 max-w-2xl">Pratinjau CV berstandar ATS dan hasil penilaian kesiapan kerja dari Penus AI.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <button onclick="downloadCvPdf()" class="inline-flex items-center gap-2 h-10 px-4 rounded-full border border-brand-ink/15 bg-white hover:bg-brand-softmist text-brand-ink text-xs font-semibold cursor-pointer transition-colors">
                <i data-lucide="download" class="w-4 h-4"></i>
                <span>Download PDF</span>
            </button>
            <a href="{{ route('bkk.me.cv.edit', ['baru' => 1]) }}" class="inline-flex items-center gap-2 h-10 px-4 rounded-full border border-brand-ink/15 bg-white hover:bg-brand-softmist text-brand-ink text-xs font-semibold transition-colors">
                <i data-lucide="file-plus-2" class="w-4 h-4"></i>
                <span>Versi Baru</span>
            </a>
            <a href="{{ route('bkk.me.cv.edit') }}" class="inline-flex items-center gap-2 h-10 px-5 rounded-full bg-navy hover:bg-navy-dark text-white text-xs font-semibold transition-colors">
                <i data-lucide="pen-line" class="w-4 h-4"></i>
                <span>Edit CV &amp; Asisten AI</span>
            </a>
        </div>
    </div>

<div class="grid grid-cols-1 xl:grid-cols-12 gap-8 items-start">
        {{-- Kolom kiri: berkas CV fisik, status review guru, skor ATS, saran AI, riwayat versi --}}
        <div class="xl:col-span-5 space-y-6">

            <!-- Kartu Unggah Berkas Fisik Dokumen CV (PDF / DOCX) & Review Guru -->
            <div class="relative bg-white p-6 space-y-4">
                <x-sketch.box />

                <div class="flex items-center justify-between border-b border-brand-ink/10 pb-3 gap-2">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-800 grid place-items-center shrink-0">
                            <i data-lucide="file-up" class="w-5 h-5"></i>
                        </div>
                        <div class="min-w-0">
                            <h2 class="text-xs font-bold text-navy truncate">Dokumen CV Resmi (PDF/DOCX)</h2>
                            <p class="text-[11px] text-brand-ink/55 truncate">Ditinjau Guru Pembimbing & Mitra</p>
                        </div>
                    </div>

                    @php
                        $guruStatus = $activeCv?->status_review_guru ?? 'Belum Direview';
                        $guruBadge = match($guruStatus) {
                            'Disetujui' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                            'Perlu Revisi' => 'bg-red-100 text-red-800 border-red-300',
                            default => 'bg-slate-100 text-slate-700 border-slate-200',
                        };
                    @endphp
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold border shrink-0 {{ $guruBadge }}" title="Status Review Guru">
                        @if($guruStatus === 'Disetujui')
                            <i data-lucide="check" class="w-3 h-3 text-emerald-600"></i>
                        @elseif($guruStatus === 'Perlu Revisi')
                            <i data-lucide="alert-circle" class="w-3 h-3 text-red-600"></i>
                        @else
                            <i data-lucide="clock" class="w-3 h-3 text-slate-500"></i>
                        @endif
                        <span>Guru: {{ $guruStatus }}</span>
                    </span>
                </div>

                @if(!empty($activeCv?->catatan_guru))
                    <div class="p-3 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-900 flex items-start gap-2.5">
                        <i data-lucide="message-square" class="w-4 h-4 text-amber-700 shrink-0 mt-0.5"></i>
                        <div>
                            <span class="font-bold text-[11px] block">Catatan Review Guru:</span>
                            <p class="text-[11px] text-amber-800 mt-0.5 leading-relaxed">{{ $activeCv->catatan_guru }}</p>
                        </div>
                    </div>
                @endif

                @if($activeCv?->has_document_file)
                    <div class="p-3 rounded-2xl bg-brand-softmist/50 border border-brand-ink/10 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-xl bg-emerald-700 text-white grid place-items-center font-bold text-xs shrink-0">
                                <i data-lucide="file-check" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-xs text-navy truncate">{{ $activeCv->file_name }}</div>
                                <div class="text-[11px] text-brand-ink/50 flex items-center gap-2 mt-0.5">
                                    <span>{{ $activeCv->file_size_formatted }}</span>
                                    <span>·</span>
                                    <span class="text-emerald-700 font-semibold">Tersimpan</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5 shrink-0">
                            <a href="{{ $activeCv->file_url }}" target="_blank" class="h-8 px-3 rounded-full bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold inline-flex items-center gap-1 transition-colors">
                                <i data-lucide="download" class="w-3 h-3"></i>
                                <span>Unduh</span>
                            </a>
                            <form action="{{ route('bkk.me.cv.deleteFile') }}" method="POST" onsubmit="return confirm('Hapus berkas CV yang diunggah?')">
                                @csrf
                                <button type="submit" class="w-8 h-8 rounded-full border border-red-200 hover:bg-red-50 text-red-600 grid place-items-center transition-colors cursor-pointer" title="Hapus Berkas">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <form action="{{ route('bkk.me.cv.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <div class="p-4 rounded-2xl border-2 border-dashed border-brand-ink/15 hover:border-emerald-600 bg-brand-softmist/30 text-center space-y-2 transition-colors">
                            <i data-lucide="upload-cloud" class="w-6 h-6 mx-auto text-emerald-700"></i>
                            <div class="text-xs text-navy font-semibold">Pilih berkas dokumen CV Anda</div>
                            <p class="text-[10px] text-brand-ink/50">Format PDF, DOC, DOCX (Maks. 5 MB)</p>
                            <input
                                type="file"
                                name="cv_file"
                                required
                                accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                                class="text-xs text-brand-ink/60 file:mr-2 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-emerald-100 file:text-emerald-800 hover:file:bg-emerald-200 cursor-pointer w-full max-w-xs mx-auto"
                            />
                        </div>

                        <button type="submit" class="w-full h-9 rounded-full bg-navy hover:bg-navy-dark text-white text-xs font-bold transition-colors cursor-pointer inline-flex items-center justify-center gap-2">
                            <i data-lucide="upload" class="w-3.5 h-3.5"></i>
                            <span>Unggah Berkas CV</span>
                        </button>
                    </form>
                @endif
            </div>

            {{-- Skor ATS (cv_resumes.feedback) --}}
            <div class="relative bg-white p-6 space-y-5">
                <x-sketch.box />

                <div class="flex items-center justify-between gap-3">
                    <h2 class="font-display text-lg uppercase tracking-wide text-brand-ink">Diagnostik Skor ATS</h2>
                    <button id="btnReanalyze" onclick="reanalyzeCv()" class="inline-flex items-center gap-1.5 h-8 px-3 rounded-full text-xs font-semibold text-brand-ink border border-brand-ink/15 hover:bg-navy hover:text-white hover:border-navy transition-colors cursor-pointer">
                        <i data-lucide="refresh-cw" id="reanalyzeIcon" class="w-3.5 h-3.5"></i>
                        <span id="reanalyzeText">Analisis ulang</span>
                    </button>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-6">
                    @php
                        $size = 132;
                        $stroke = 10;
                        $radius = ($size - $stroke) / 2;
                        $circumference = 2 * M_PI * $radius;
                        $offset = $circumference - ($scoreVal / 100) * $circumference;
                    @endphp
                    <div class="relative w-[132px] h-[132px] shrink-0">
                        <svg width="132" height="132" class="-rotate-90" aria-hidden="true">
                            <circle cx="66" cy="66" r="{{ $radius }}" stroke="#F1ECEB" stroke-width="{{ $stroke }}" fill="none"></circle>
                            <circle id="scoreRingCircle" cx="66" cy="66" r="{{ $radius }}" stroke="{{ $totalTone['hex'] }}" stroke-width="{{ $stroke }}" fill="none" stroke-linecap="round"
                                    stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}" style="transition: stroke-dashoffset 0.8s ease, stroke 0.3s ease;"></circle>
                        </svg>
                        <div class="absolute inset-0 grid place-items-center text-center">
                            <div>
                                <div id="scoreText" class="font-display font-bold text-brand-ink text-4xl leading-none">{{ $scoreVal }}</div>
                                <div class="text-[10px] text-brand-ink/50 mt-1">/ 100</div>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 text-center sm:text-left space-y-2">
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                            <span id="gradeLabelBadge" class="rounded-full px-3 py-1 text-xs font-semibold {{ $totalTone['badge'] }}">{{ $label }}</span>
                            <span id="percentileBadge" class="text-[11px] font-semibold text-brand-ink/60">
                                Top {{ max(3, 100 - $percentile) }}% (Persentil {{ $percentile }}%)
                            </span>
                        </div>
                        <p id="summaryVerdictText" class="text-xs text-brand-ink/70 leading-relaxed">
                            {{ $verdict }}
                        </p>
                    </div>
                </div>

                {{-- Rincian 4 parameter, dipisah garis coretan --}}
                <ul id="parametersGrid" class="pt-1">
                    @foreach($parameters as $idx => $p)
                        @php
                            $pScore = (int) ($p['score'] ?? $p['value'] ?? 85);
                            $pMax = (int) ($p['max_score'] ?? 100);
                            $pStatus = strtoupper($p['status'] ?? ($pScore >= 88 ? 'EXCELLENT' : ($pScore >= 75 ? 'GOOD' : 'NEEDS_IMPROVEMENT')));
                            $pWeight = isset($p['weight']) ? round(((float) $p['weight']) * 100) : 25;
                            $pFeedback = $p['feedback'] ?? '';
                            $pIcon = $paramIcons[$p['id'] ?? ''] ?? ($fallbackIcons[$idx] ?? 'check-circle');
                            $pTone = $scoreTone($pScore);
                        @endphp
                        <li class="relative py-4 first:pt-0 last:pb-0 space-y-2">
                            @if($idx > 0)
                                <x-sketch.rule :delay="$idx * 100" class="text-brand-darkred/25 -left-1 -right-1 -top-1.5 h-3" />
                            @endif
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 text-xs font-bold text-brand-ink">
                                    <i data-lucide="{{ $pIcon }}" class="w-4 h-4 text-brand-darkred shrink-0"></i>
                                    <span>{{ $p['label'] }}</span>
                                    <span class="text-[10px] font-normal text-brand-ink/50">({{ $pWeight }}%)</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase {{ $pTone['badge'] }}">{{ $pStatus }}</span>
                                    <span class="font-display text-base text-brand-ink">{{ $pScore }}<span class="text-[10px] text-brand-ink/40">/{{ $pMax }}</span></span>
                                </div>
                            </div>
                            <div class="h-1.5 rounded-full bg-brand-ink/[0.07] overflow-hidden">
                                <div class="h-full rounded-full {{ $pTone['bar'] }}" style="width: {{ min(100, $pScore) }}%"></div>
                            </div>
                            @if($pFeedback)
                                <p class="text-[11px] text-brand-ink/60 leading-relaxed">{{ $pFeedback }}</p>
                            @endif
                        </li>
                    @endforeach
                </ul>

                {{-- Bagian terdeteksi & yang disarankan --}}
                <div id="sectionsAnalysisBox" class="relative pt-5 space-y-3 text-xs">
                    <x-sketch.rule class="text-brand-darkred/25 -left-1 -right-1 -top-1.5 h-3" />
                    <div>
                        <div class="text-[11px] font-bold text-brand-ink mb-1.5">Bagian ATS Terdeteksi:</div>
                        <div id="detectedSectionsList" class="flex flex-wrap gap-1.5">
                            @forelse($detectedSections as $sec)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    <i data-lucide="check" class="w-3 h-3"></i>
                                    <span>{{ $sec }}</span>
                                </span>
                            @empty
                                <span class="text-[11px] text-brand-ink/50 italic">Belum ada bagian terdeteksi.</span>
                            @endforelse
                        </div>
                    </div>

                    <div id="missingSectionsWrapper" class="{{ empty($missingSections) ? 'hidden' : '' }}">
                        <div class="text-[11px] font-bold text-brand-darkred mb-1.5">Bagian Disarankan untuk Ditambahkan:</div>
                        <div id="missingSectionsList" class="flex flex-wrap gap-1.5">
                            @foreach($missingSections as $msec)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-brand-darkred/5 text-brand-darkred border border-brand-darkred/20">
                                    <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                    <span>{{ $msec }}</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- Pintas ke saran & chat AI di Editor CV --}}
            <div class="relative bg-white p-6">
                <x-sketch.box />
                <div class="flex items-start gap-3">
                    <i data-lucide="wand-2" class="w-5 h-5 text-brand-darkred shrink-0 mt-0.5"></i>
                    <div>
                        <h2 class="font-display text-lg uppercase tracking-wide text-brand-ink">Saran AI &amp; Konsultasi</h2>
                        <p class="mt-1 text-xs text-brand-ink/65 leading-relaxed">Terapkan perbaikan kalimat dari Penus AI dan tanya jawab langsung di Editor CV.</p>
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <a href="{{ route('bkk.me.cv.edit') }}" class="group h-9 px-4 rounded-full bg-navy hover:bg-navy-dark text-white text-xs font-semibold inline-flex items-center gap-2 transition-colors">
                        Buka Editor CV
                        <x-sketch.arrow class="w-6 h-3 transition-transform group-hover:translate-x-0.5" />
                    </a>
                </div>
            </div>

            {{-- Riwayat versi CV (hanya versi tersimpan, temp = false) --}}
            <div class="relative bg-white p-6">
                <x-sketch.box />
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="font-display text-lg uppercase tracking-wide text-brand-ink">Riwayat Versi CV</h2>
                        <p class="text-[11px] text-brand-ink/55">{{ count($cvVersions ?? []) }} versi tersimpan</p>
                    </div>
                    <a href="{{ route('bkk.me.cv.edit', ['baru' => 1]) }}" class="h-8 px-3 rounded-full border border-brand-ink/15 bg-white hover:bg-navy hover:text-white hover:border-navy text-brand-ink text-[11px] font-semibold inline-flex items-center gap-1.5 transition-colors">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        Versi Baru
                    </a>
                </div>

                <ul class="mt-4 max-h-[340px] overflow-y-auto">
                    @forelse($cvVersions ?? [] as $i => $ver)
                        @php $verScore = $ver->score ?? (is_array($ver->feedback) ? ($ver->feedback['total_score'] ?? null) : null); @endphp
                        <li class="relative py-3.5 flex items-center justify-between gap-3">
                            @if($i > 0)
                                <x-sketch.rule :delay="$i * 80" class="text-brand-darkred/20 -left-1 -right-1 -top-1.5 h-3" />
                            @endif
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="text-xs font-bold text-brand-ink">Versi #{{ $ver->id }}</span>
                                    @if($ver->is_main)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-navy text-white">Utama</span>
                                    @endif
                                    @if($verScore !== null)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $scoreTone((int) $verScore)['badge'] }}">Skor {{ $verScore }}</span>
                                    @endif
                                </div>
                                <div class="mt-0.5 text-[11px] text-brand-ink/50">
                                    {{ $ver->created_at ? $ver->created_at->format('d M Y, H:i') : '-' }}
                                    @if($ver->lamarans_count)
                                        · dipakai di {{ $ver->lamarans_count }} lamaran
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-1.5 shrink-0">
                                <a href="{{ $ver->is_main ? route('bkk.me.cv.edit') : route('bkk.me.cv.editVersion', $ver->id) }}" class="h-7 px-2.5 rounded-full border border-brand-ink/15 bg-white hover:bg-brand-softmist text-brand-ink text-[11px] font-semibold transition-colors inline-flex items-center gap-1" title="Sunting versi ini">
                                    <i data-lucide="edit-3" class="w-3 h-3"></i>
                                    <span>Sunting</span>
                                </a>
                                @unless($ver->is_main)
                                    <form action="{{ route('bkk.me.cv.setMain', $ver->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="h-7 px-2.5 rounded-full border border-brand-ink/15 bg-white hover:bg-navy hover:text-white hover:border-navy text-brand-ink text-[11px] font-semibold transition-colors cursor-pointer" title="Jadikan versi ini sebagai CV Utama">
                                            Aktifkan
                                        </button>
                                    </form>
                                    @include('me.partials.cv-delete-button', ['version' => $ver, 'compact' => true])
                                @endunless
                            </div>
                        </li>
                    @empty
                        <li class="py-5 text-center text-xs text-brand-ink/55">Belum ada riwayat versi CV.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        {{-- Kolom kanan: lembar CV ATS --}}
        <div class="xl:col-span-7 space-y-3">
            <div class="flex items-center gap-1.5 text-xs font-medium text-brand-ink/60 px-1">
                <i data-lucide="file-check" class="w-4 h-4 text-emerald-600"></i>
                <span>Format Standar ATS Friendly (A4 Ready)</span>
            </div>

            <div id="cvPaper" class="relative bg-white border border-brand-ink/10 rounded-lg p-6 sm:p-10 min-h-[700px] md-cv shadow-[0_20px_40px_-24px_rgb(36_16_18/0.25)]">
                <x-sketch.corner class="-left-3 -top-3 w-24 h-10" />
                <x-sketch.corner :delay="350" class="-right-3 -bottom-3 rotate-180 w-24 h-10" />
                <div id="cvRenderedContent">
                    {{-- Diisi marked.js + DOMPurify dari raw markdown --}}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const rawMarkdown = @json($cvMarkdown);
    const candidateName = @json($candidateName);
    let activeCvId = @json($activeCv?->id ?? null);

    document.addEventListener('DOMContentLoaded', function() {
        renderCvMarkdown(rawMarkdown);
    });

    function renderCvMarkdown(md) {
        const container = document.getElementById('cvRenderedContent');
        if (container && typeof marked !== 'undefined') {
            const parsed = marked.parse(md);
            container.innerHTML = typeof DOMPurify !== 'undefined' ? DOMPurify.sanitize(parsed) : parsed;
        }
    }

    // Tiga warna penanda skor, sama dengan $scoreTone di Blade
    function scoreTone(score) {
        if (score >= 85) return { bar: 'bg-emerald-500', badge: 'bg-emerald-100 text-emerald-800', hex: '#10B981' };
        if (score >= 70) return { bar: 'bg-navy', badge: 'bg-navy/10 text-navy', hex: '#1E293B' };
        return { bar: 'bg-brand-darkred', badge: 'bg-brand-darkred/10 text-brand-darkred', hex: '#7A1018' };
    }

    async function reanalyzeCv() {
        const btn = document.getElementById('btnReanalyze');
        const icon = document.getElementById('reanalyzeIcon');
        const text = document.getElementById('reanalyzeText');
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        if (icon) icon.classList.add('animate-spin');
        if (text) text.innerText = 'Menganalisis…';
        if (btn) btn.disabled = true;

        try {
            // Pastikan CV sudah tersimpan di cv_resumes jika activeCvId belum ada
            if (!activeCvId && rawMarkdown.trim()) {
                const saveRes = await fetch("{{ route('bkk.me.cv.update') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        raw_markdown: rawMarkdown,
                        temp: false
                    })
                });
                const saveJson = await saveRes.json();
                if (saveJson.data?.id) {
                    activeCvId = saveJson.data.id;
                }
            }

            @php
                $clientToken = request()->attributes->get('auth_token')
                    ?: (request()->bearerToken()
                    ?: (request()->cookie('access_token')
                    ?: ''));
            @endphp
            const clientToken = @json($clientToken);
            const authHeaders = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-App-Client': 'bkk-web-cv-editor/1.0'
            };
            if (clientToken) {
                authHeaders['Authorization'] = 'Bearer ' + clientToken;
            }

            const res = await fetch('/api/ai/cv/score', {
                method: 'POST',
                headers: authHeaders,
                credentials: 'include',
                body: JSON.stringify({
                    cv_id: activeCvId
                })
            });

            const json = await res.json();
            if (!res.ok || !json.success || !json.data) {
                throw new Error(json.message || 'Gagal mengevaluasi skor CV.');
            }

            updateScoreFeedbackUi(json.data);
            showToast('Evaluasi skor ATS selesai dan tersimpan.');
        } catch (err) {
            showToast(err.message || 'Gagal menghubungi layanan evaluasi AI.');
        } finally {
            if (icon) icon.classList.remove('animate-spin');
            if (text) text.innerText = 'Analisis ulang';
            if (btn) btn.disabled = false;
        }
    }

    function updateScoreFeedbackUi(data) {
        const scoreRing = document.getElementById('scoreRingCircle');
        const scoreText = document.getElementById('scoreText');
        const gradeBadge = document.getElementById('gradeLabelBadge');
        const percentileBadge = document.getElementById('percentileBadge');
        const verdictText = document.getElementById('summaryVerdictText');
        const paramsGrid = document.getElementById('parametersGrid');
        const detectedList = document.getElementById('detectedSectionsList');
        const missingWrap = document.getElementById('missingSectionsWrapper');
        const missingList = document.getElementById('missingSectionsList');

        const newScore = Number(data.total_score ?? 88);
        const tone = scoreTone(newScore);
        if (scoreText) scoreText.innerText = newScore;

        if (scoreRing) {
            const radius = 61;
            const c = 2 * Math.PI * radius;
            scoreRing.style.strokeDashoffset = c - (newScore / 100) * c;
            scoreRing.setAttribute('stroke', tone.hex);
        }

        if (gradeBadge) {
            gradeBadge.className = `rounded-full px-3 py-1 text-xs font-semibold ${tone.badge}`;
            if (data.grade_label) gradeBadge.innerText = data.grade_label;
        }

        if (percentileBadge && data.percentile) {
            const topPct = Math.max(3, 100 - Number(data.percentile));
            percentileBadge.innerText = `Top ${topPct}% (Persentil ${data.percentile}%)`;
        }

        if (verdictText && data.summary_verdict) {
            verdictText.innerText = data.summary_verdict;
        }

        const iconMap = {
            param_industry_fit: 'target',
            param_completeness: 'layers',
            param_ats_format: 'file-search',
            param_portfolio_impact: 'award'
        };

        if (paramsGrid && Array.isArray(data.parameters)) {
            paramsGrid.innerHTML = data.parameters.map((p, idx) => {
                const pScore = Number(p.score ?? 85);
                const pMax = Number(p.max_score ?? 100);
                const pStatus = String(p.status || 'GOOD').toUpperCase();
                const pWeight = Math.round(Number(p.weight ?? 0.25) * 100);
                const pIcon = iconMap[p.id] || 'check-circle';
                const pTone = scoreTone(pScore);

                return `
                    <li class="relative py-4 first:pt-0 last:pb-0 space-y-2">
                        ${idx > 0 ? '<span data-sketch="rule" data-duration="900" data-threshold="0.6" aria-hidden="true" class="pointer-events-none absolute text-brand-darkred/25 -left-1 -right-1 -top-1.5 h-3"></span>' : ''}
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 text-xs font-bold text-brand-ink">
                                <i data-lucide="${pIcon}" class="w-4 h-4 text-brand-darkred shrink-0"></i>
                                <span>${escapeHtml(p.label)}</span>
                                <span class="text-[10px] font-normal text-brand-ink/50">(${pWeight}%)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase ${pTone.badge}">${escapeHtml(pStatus)}</span>
                                <span class="font-display text-base text-brand-ink">${pScore}<span class="text-[10px] text-brand-ink/40">/${pMax}</span></span>
                            </div>
                        </div>
                        <div class="h-1.5 rounded-full bg-brand-ink/[0.07] overflow-hidden">
                            <div class="h-full rounded-full ${pTone.bar}" style="width: ${Math.min(100, pScore)}%"></div>
                        </div>
                        ${p.feedback ? `<p class="text-[11px] text-brand-ink/60 leading-relaxed">${escapeHtml(p.feedback)}</p>` : ''}
                    </li>
                `;
            }).join('');
        }

        if (detectedList && Array.isArray(data.detected_sections)) {
            detectedList.innerHTML = data.detected_sections.map(sec => `
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                    <i data-lucide="check" class="w-3 h-3"></i>
                    <span>${escapeHtml(sec)}</span>
                </span>
            `).join('');
        }

        if (missingWrap && missingList && Array.isArray(data.missing_sections)) {
            missingWrap.classList.toggle('hidden', data.missing_sections.length === 0);
            missingList.innerHTML = data.missing_sections.map(msec => `
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-brand-darkred/5 text-brand-darkred border border-brand-darkred/20">
                    <i data-lucide="alert-circle" class="w-3 h-3"></i>
                    <span>${escapeHtml(msec)}</span>
                </span>
            `).join('');
        }

        lucide.createIcons();
    }

    // Cetak hanya isi CV, baik dari tombol Download PDF maupun Ctrl+P. Judul tab dipakai browser sebagai nama file PDF,
    // jadi diganti sementara menjadi nama kandidat.
    const pageTitle = document.title;

    window.addEventListener('beforeprint', () => {
        document.getElementById('cvPrintRoot')?.remove();
        const root = document.createElement('div');
        root.id = 'cvPrintRoot';
        root.className = 'md-cv text-brand-ink';
        root.innerHTML = document.getElementById('cvRenderedContent')?.innerHTML || '';
        document.body.appendChild(root);
        document.title = `CV ${candidateName}`;
    });

    window.addEventListener('afterprint', () => {
        document.getElementById('cvPrintRoot')?.remove();
        document.title = pageTitle;
    });

    function downloadCvPdf() {
        window.print();
    }
</script>
@endpush
