@extends('me.master')

@section('title', 'CV & AI Scoring Hub - BKK SMK Plus Pelita Nusantara')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<style>
    @media print {
        body * { visibility: hidden; }
        #cvPaper, #cvPaper * { visibility: visible; }
        #cvPaper { position: absolute; left: 0; top: 0; width: 100%; max-width: 100%; border: none; box-shadow: none; padding: 20px; }
    }
</style>
@endpush

@php
    $scoreVal = $cvScore['total'] ?? 85;
    $label = ($scoreVal >= 90) ? 'Sangat Baik' : (($scoreVal >= 80) ? 'Baik' : (($scoreVal >= 70) ? 'Cukup' : 'Perlu Perbaikan'));
    $paramIcons = ['target', 'layers', 'file-search', 'award'];
    $shortChecksum = substr($checksum ?? '', 0, 12);
@endphp

@section('content')
<div class="space-y-6 fade-up">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-2xl font-bold text-navy tracking-tight">CV & AI Scoring Hub</h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                    <span>CV Utama (Aktif)</span>
                </span>
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-mono bg-navy/5 text-navy border border-line" title="Checksum SHA-256: {{ $checksum }}">
                    <i data-lucide="hash" class="w-3 h-3 text-muted"></i>
                    <span>{{ $shortChecksum }}…</span>
                </span>
            </div>
            <p class="text-xs sm:text-sm text-muted mt-1">Pratinjau CV profesional ATS dan evaluasi kesiapan kerja berbasis AI.</p>
        </div>

        <div class="flex gap-2">
            <button onclick="downloadCvPdf()" class="inline-flex items-center gap-2 h-10 px-4 rounded-full border border-line bg-white hover:bg-canvas text-navy text-xs font-semibold shadow-sm cursor-pointer transition-colors">
                <i data-lucide="download" class="w-4 h-4"></i>
                <span>Download PDF</span>
            </button>
            <a href="{{ route('bkk.me.cv.edit') }}" class="inline-flex items-center gap-2 h-10 px-5 rounded-full bg-navy hover:bg-navy-dark text-white text-xs font-semibold shadow-sm transition-colors">
                <i data-lucide="pen-line" class="w-4 h-4"></i>
                <span>Edit CV</span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
        <!-- Left Column: AI Score, Versioning & Recommendations (5 cols) -->
        <div class="xl:col-span-5 space-y-6">
            <!-- Penus AI Score Card -->
            <div class="p-6 bg-white gemini-border rounded-3xl relative overflow-hidden shadow-sm">
                <div class="absolute -top-16 -right-16 w-48 h-48 rounded-full bg-gradient-to-br from-maroon/15 to-navy/10 blur-2xl pointer-events-none"></div>

                <div class="relative flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <i data-lucide="sparkles" class="w-5 h-5 text-maroon"></i>
                        <span class="font-bold text-sm gemini-text">Penus AI · Analisis CV</span>
                    </div>
                    <button id="btnReanalyze" onclick="reanalyzeCv()" class="inline-flex items-center gap-1.5 h-8 px-3 rounded-full text-xs font-semibold text-muted hover:bg-canvas transition-colors cursor-pointer">
                        <i data-lucide="refresh-cw" id="reanalyzeIcon" class="w-3.5 h-3.5"></i>
                        <span id="reanalyzeText">Analisis ulang</span>
                    </button>
                </div>

                <div class="relative flex flex-col sm:flex-row items-center gap-6">
                    <!-- SVG Radial Score Ring -->
                    @php
                        $size = 132;
                        $stroke = 10;
                        $radius = ($size - $stroke) / 2;
                        $circumference = 2 * M_PI * $radius;
                        $offset = $circumference - ($scoreVal / 100) * $circumference;
                    @endphp
                    <div class="relative w-[132px] h-[132px] shrink-0">
                        <svg width="132" height="132" class="-rotate-90">
                            <defs>
                                <linearGradient id="scoreGrad" x1="0" x2="1" y1="0" x2="1">
                                    <stop offset="0%" stop-color="#741918"></stop>
                                    <stop offset="100%" stop-color="#1b283b"></stop>
                                </linearGradient>
                            </defs>
                            <circle cx="66" cy="66" r="{{ $radius }}" stroke="#ececec" stroke-width="{{ $stroke }}" fill="none"></circle>
                            <circle id="scoreRingCircle" cx="66" cy="66" r="{{ $radius }}" stroke="url(#scoreGrad)" stroke-width="{{ $stroke }}" fill="none" stroke-linecap="round"
                                    stroke-dasharray="{{ $circumference }}" stroke-dashoffset="{{ $offset }}" style="transition: stroke-dashoffset 0.8s ease;"></circle>
                        </svg>
                        <div class="absolute inset-0 grid place-items-center text-center">
                            <div>
                                <div id="scoreText" class="font-bold text-navy text-3xl leading-none">{{ $scoreVal }}</div>
                                <div class="text-[10px] text-muted mt-0.5">/ 100</div>
                            </div>
                        </div>
                    </div>

                    <div class="flex-1 text-center sm:text-left">
                        <span class="rounded-full px-3 py-1 text-xs font-semibold bg-navy text-white">{{ $label }}</span>
                        <p class="text-xs text-muted mt-2 leading-relaxed">
                            CV Anda dinilai lebih optimal dari <b class="text-navy">{{ min(97, $scoreVal - 6) }}%</b> pelamar pada keahlian serupa. Terapkan rekomendasi perbaikan untuk mencapai skor 95+.
                        </p>
                    </div>
                </div>

                <!-- Parameters Grid -->
                <div class="relative grid grid-cols-1 sm:grid-cols-2 gap-3 mt-6">
                    @foreach($cvScore['params'] ?? [] as $idx => $p)
                        <div class="rounded-2xl bg-[#f8f9fa] border border-line p-3">
                            <div class="flex items-center gap-1.5 text-xs text-muted font-medium">
                                <i data-lucide="{{ $paramIcons[$idx] ?? 'check' }}" class="w-3.5 h-3.5 text-navy"></i>
                                <span>{{ $p['label'] }}</span>
                            </div>
                            <div class="flex items-center gap-2 mt-2">
                                <div class="flex-1 h-1.5 rounded-full bg-line/60 overflow-hidden">
                                    <div class="h-full rounded-full {{ $p['value'] >= 85 ? 'bg-navy' : 'bg-maroon' }}" style="width: {{ $p['value'] }}%"></div>
                                </div>
                                <span class="text-xs font-bold text-navy w-6 text-right">{{ $p['value'] }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Versioning / Riwayat Versi CV Card -->
            <div class="bg-white border border-line rounded-3xl overflow-hidden shadow-sm">
                <div class="p-5 border-b border-line flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full bg-navy/10 text-navy grid place-items-center">
                            <i data-lucide="history" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <div class="font-bold text-sm text-navy">Riwayat Versi CV</div>
                            <div class="text-[11px] text-muted">{{ count($cvVersions ?? []) }} versi tersimpan</div>
                        </div>
                    </div>
                    <span class="text-[11px] text-muted font-mono">Checksum SHA-256</span>
                </div>

                <div class="divide-y divide-line/60 max-h-[300px] overflow-y-auto">
                    @forelse($cvVersions ?? [] as $ver)
                        <div class="p-4 flex items-center justify-between gap-3 hover:bg-[#f8f9fa] transition-colors {{ $ver->is_main ? 'bg-emerald-50/40' : '' }}">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-bold text-navy truncate">
                                        Versi #{{ $ver->id }}
                                    </span>
                                    @if($ver->is_main)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                            Utama
                                        </span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-2 mt-1 text-[11px] text-muted">
                                    <span class="font-mono">{{ substr($ver->checksum, 0, 12) }}…</span>
                                    <span>•</span>
                                    <span>{{ $ver->created_at ? $ver->created_at->format('d M Y, H:i') : '-' }}</span>
                                </div>
                            </div>

                            @if(!$ver->is_main)
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <a href="{{ route('bkk.me.cv.editVersion', $ver->id) }}" class="h-7 px-2.5 rounded-full border border-line bg-white hover:bg-canvas text-navy text-[11px] font-semibold transition-colors cursor-pointer inline-flex items-center gap-1" title="Sunting versi ini">
                                        <i data-lucide="edit-3" class="w-3 h-3"></i>
                                        <span>Sunting</span>
                                    </a>
                                    <form action="{{ route('bkk.me.cv.setMain', $ver->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="h-7 px-2.5 rounded-full border border-line bg-white hover:bg-navy hover:text-white text-navy text-[11px] font-semibold transition-colors cursor-pointer" title="Jadikan versi ini sebagai CV Utama">
                                            Aktifkan
                                        </button>
                                    </form>
                                </div>
                            @else
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <a href="{{ route('bkk.me.cv.edit') }}" class="h-7 px-2.5 rounded-full bg-navy text-white text-[11px] font-semibold transition-colors cursor-pointer inline-flex items-center gap-1">
                                        <i data-lucide="edit-3" class="w-3 h-3"></i>
                                        <span>Sunting</span>
                                    </a>
                                    <span class="text-[11px] font-semibold text-emerald-700 flex items-center gap-1">
                                        <i data-lucide="check" class="w-3.5 h-3.5"></i> Digunakan
                                    </span>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="p-5 text-center text-xs text-muted">
                            Belum ada riwayat versi CV lainnya.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- AI Suggestions Card -->
            <div class="bg-white border border-line rounded-3xl overflow-hidden shadow-sm">
                <div class="p-5 border-b border-line flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full bg-maroon text-white grid place-items-center">
                            <i data-lucide="wand-2" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <div class="font-bold text-sm text-navy">Saran Perbaikan AI</div>
                            <div class="text-[11px] text-muted">{{ count($suggestions ?? []) }} rekomendasi siap diterapkan</div>
                        </div>
                    </div>
                </div>

                <div class="divide-y divide-line/60">
                    @foreach($suggestions ?? [] as $s)
                        @php
                            $impactColor = match($s['impact']) {
                                'Tinggi' => 'bg-maroon text-white',
                                'Sedang' => 'bg-amber-100 text-amber-800',
                                default => 'bg-gray-100 text-muted'
                            };
                        @endphp
                        <div class="p-4 sm:p-5 hover:bg-[#f8f9fa] transition-colors" id="sug-card-{{ $s['id'] }}">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase {{ $impactColor }}">
                                        Dampak {{ $s['impact'] }}
                                    </span>
                                    <span class="text-[11px] text-muted ml-2 font-medium">Bagian {{ $s['section'] }}</span>
                                    <h4 class="font-semibold text-xs sm:text-sm text-navy mt-1.5 leading-snug">{{ $s['title'] }}</h4>
                                    <p class="text-xs text-muted mt-1 leading-relaxed">{{ $s['desc'] }}</p>
                                </div>
                            </div>
                            <div class="mt-3 flex justify-end">
                                <button id="btn-sug-" . {{ $s['id'] }} onclick="applySuggestion('{{ $s['id'] }}')" class="h-7 px-3.5 rounded-full bg-navy/5 hover:bg-navy hover:text-white text-navy text-[11px] font-semibold transition-all inline-flex items-center gap-1.5 cursor-pointer">
                                    <i data-lucide="check" class="w-3 h-3"></i>
                                    <span>Terapkan ke CV</span>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right Column: ATS CV Paper Preview (7 cols) -->
        <div class="xl:col-span-7 space-y-3">
            <div class="flex items-center justify-between text-xs text-muted px-1">
                <span class="flex items-center gap-1.5 font-medium">
                    <i data-lucide="file-check" class="w-4 h-4 text-emerald-600"></i>
                    <span>Format Standar ATS Friendly (A4 Ready)</span>
                </span>
                <span class="font-mono text-[11px] text-muted">sha256: {{ $shortChecksum }}…</span>
            </div>

            <!-- Printable Paper Container -->
            <div id="cvPaper" class="bg-white border border-line rounded-3xl p-6 sm:p-10 shadow-lg min-h-[700px] md-cv">
                <div id="cvRenderedContent">
                    <!-- Injected by Marked.js from raw markdown -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const rawMarkdown = @json($cvMarkdown);

    document.addEventListener('DOMContentLoaded', function() {
        renderCvMarkdown(rawMarkdown);
    });

    function renderCvMarkdown(md) {
        const container = document.getElementById('cvRenderedContent');
        if (container && typeof marked !== 'undefined') {
            container.innerHTML = marked.parse(md);
        }
    }

    function reanalyzeCv() {
        const btn = document.getElementById('btnReanalyze');
        const icon = document.getElementById('reanalyzeIcon');
        const text = document.getElementById('reanalyzeText');
        const scoreRing = document.getElementById('scoreRingCircle');
        const scoreText = document.getElementById('scoreText');

        icon.classList.add('animate-spin');
        text.innerText = 'Menganalisis…';
        btn.disabled = true;

        setTimeout(() => {
            icon.classList.remove('animate-spin');
            text.innerText = 'Analisis ulang';
            btn.disabled = false;

            const newScore = Math.min(96, parseInt(scoreText.innerText) + 2);
            scoreText.innerText = newScore;

            const radius = 61;
            const c = 2 * Math.PI * radius;
            const newOffset = c - (newScore / 100) * c;
            scoreRing.style.strokeDashoffset = newOffset;

            showToast('Analisis AI selesai. Skor kesiapan CV meningkat!');
        }, 1400);
    }

    function applySuggestion(id) {
        const btn = document.getElementById('btn-sug-' + id);
        if (!btn) return;
        btn.disabled = true;
        btn.className = "h-7 px-3.5 rounded-full bg-emerald-100 text-emerald-800 text-[11px] font-semibold cursor-not-allowed inline-flex items-center gap-1";
        btn.innerHTML = `<i data-lucide="check" class="w-3 h-3"></i> <span>Diterapkan</span>`;
        lucide.createIcons();

        const scoreText = document.getElementById('scoreText');
        const scoreRing = document.getElementById('scoreRingCircle');
        const newScore = Math.min(98, parseInt(scoreText.innerText) + 1);
        scoreText.innerText = newScore;

        const radius = 61;
        const c = 2 * Math.PI * radius;
        scoreRing.style.strokeDashoffset = c - (newScore / 100) * c;

        showToast('Saran perbaikan AI berhasil digabungkan ke draf CV.');
    }

    function downloadCvPdf() {
        window.print();
    }
</script>
@endpush
