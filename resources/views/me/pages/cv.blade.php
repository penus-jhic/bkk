@extends('me.master')

@section('title', 'CV & AI Scoring Hub - BKK SMK Plus Pelita Nusantara')

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
                    <span id="hubChecksumText">{{ $shortChecksum }}…</span>
                </span>
            </div>
            <p class="text-xs sm:text-sm text-muted mt-1">Pratinjau CV profesional ATS dan laporan diagnostik kesiapan kerja dari <code class="text-[11px] bg-navy/5 px-1.5 py-0.5 rounded">cv_resumes.feedback</code>.</p>
        </div>

        <div class="flex gap-2">
            <button onclick="downloadCvPdf()" class="inline-flex items-center gap-2 h-10 px-4 rounded-full border border-line bg-white hover:bg-canvas text-navy text-xs font-semibold shadow-sm cursor-pointer transition-colors">
                <i data-lucide="download" class="w-4 h-4"></i>
                <span>Download PDF</span>
            </button>
            <a href="{{ route('bkk.me.cv.edit') }}" class="inline-flex items-center gap-2 h-10 px-5 rounded-full bg-navy hover:bg-navy-dark text-white text-xs font-semibold shadow-sm transition-colors">
                <i data-lucide="pen-line" class="w-4 h-4"></i>
                <span>Edit CV & Asisten AI</span>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
        <!-- Left Column: Full AI Score Feedback & Versioning (5 cols) -->
        <div class="xl:col-span-5 space-y-6">
            <!-- Penus AI Score & Diagnostic Card (/api/ai/cv/score -> cv_resumes.feedback) -->
            <div class="p-6 bg-white gemini-border rounded-3xl relative overflow-hidden shadow-sm space-y-5">
                <div class="absolute -top-16 -right-16 w-48 h-48 rounded-full bg-gradient-to-br from-maroon/15 to-navy/10 blur-2xl pointer-events-none"></div>

                <div class="relative flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <i data-lucide="sparkles" class="w-5 h-5 text-maroon"></i>
                        <span class="font-bold text-sm gemini-text">Penus AI · Diagnostik Skor ATS</span>
                    </div>
                    <button id="btnReanalyze" onclick="reanalyzeCv()" class="inline-flex items-center gap-1.5 h-8 px-3 rounded-full text-xs font-semibold text-navy bg-navy/5 hover:bg-navy hover:text-white transition-colors cursor-pointer">
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
                                <linearGradient id="scoreGrad" x1="0" x2="1" y1="0" y2="1">
                                    <stop offset="0%" stop-color="#7A1018"></stop>
                                    <stop offset="100%" stop-color="#1E293B"></stop>
                                </linearGradient>
                            </defs>
                            <circle cx="66" cy="66" r="{{ $radius }}" stroke="#EEF2F6" stroke-width="{{ $stroke }}" fill="none"></circle>
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

                    <div class="flex-1 text-center sm:text-left space-y-2">
                        <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                            <span id="gradeLabelBadge" class="rounded-full px-3 py-1 text-xs font-semibold bg-navy text-white">{{ $label }}</span>
                            <span id="percentileBadge" class="rounded-full px-2.5 py-1 text-[11px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                Top {{ max(3, 100 - $percentile) }}% (Persentil {{ $percentile }}%)
                            </span>
                        </div>
                        <p id="summaryVerdictText" class="text-xs text-muted leading-relaxed">
                            {{ $verdict }}
                        </p>
                    </div>
                </div>

                <!-- 4 Detailed Parameters List -->
                <div id="parametersGrid" class="relative space-y-3 pt-1">
                    @foreach($parameters as $idx => $p)
                        @php
                            $pScore = (int) ($p['score'] ?? $p['value'] ?? 85);
                            $pMax = (int) ($p['max_score'] ?? 100);
                            $pStatus = strtoupper($p['status'] ?? ($pScore >= 88 ? 'EXCELLENT' : ($pScore >= 75 ? 'GOOD' : 'NEEDS_IMPROVEMENT')));
                            $pWeight = isset($p['weight']) ? round(((float) $p['weight']) * 100) : 25;
                            $pFeedback = $p['feedback'] ?? '';
                            $pIcon = $paramIcons[$p['id'] ?? ''] ?? ($fallbackIcons[$idx] ?? 'check-circle');
                            $statusBadgeClass = match($pStatus) {
                                'EXCELLENT' => 'bg-emerald-100 text-emerald-800',
                                'GOOD' => 'bg-navy/10 text-navy',
                                default => 'bg-amber-100 text-amber-800',
                            };
                        @endphp
                        <div class="rounded-2xl bg-[#F5F7FA] border border-line p-3.5 space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2 text-xs font-bold text-navy">
                                    <i data-lucide="{{ $pIcon }}" class="w-4 h-4 text-navy shrink-0"></i>
                                    <span>{{ $p['label'] }}</span>
                                    <span class="text-[10px] font-normal text-muted">({{ $pWeight }}%)</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase {{ $statusBadgeClass }}">
                                        {{ $pStatus }}
                                    </span>
                                    <span class="text-xs font-bold text-navy">{{ $pScore }}<span class="text-[10px] text-muted font-normal">/{{ $pMax }}</span></span>
                                </div>
                            </div>

                            <div class="h-1.5 rounded-full bg-line/60 overflow-hidden">
                                <div class="h-full rounded-full {{ $pScore >= 85 ? 'bg-navy' : 'bg-maroon' }}" style="width: {{ min(100, $pScore) }}%"></div>
                            </div>

                            @if($pFeedback)
                                <p class="text-[11px] text-muted leading-relaxed">{{ $pFeedback }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Detected vs Missing Sections -->
                <div id="sectionsAnalysisBox" class="relative pt-2 border-t border-line space-y-2.5 text-xs">
                    <div>
                        <div class="text-[11px] font-bold text-navy mb-1.5">Bagian ATS Terdeteksi:</div>
                        <div id="detectedSectionsList" class="flex flex-wrap gap-1.5">
                            @forelse($detectedSections as $sec)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    <i data-lucide="check" class="w-3 h-3"></i>
                                    <span>{{ $sec }}</span>
                                </span>
                            @empty
                                <span class="text-[11px] text-muted italic">Belum ada bagian terdeteksi.</span>
                            @endforelse
                        </div>
                    </div>

                    <div id="missingSectionsWrapper" class="{{ empty($missingSections) ? 'hidden' : '' }}">
                        <div class="text-[11px] font-bold text-amber-900 mb-1.5">Bagian Disarankan untuk Ditambahkan:</div>
                        <div id="missingSectionsList" class="flex flex-wrap gap-1.5">
                            @foreach($missingSections as $msec)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                    <i data-lucide="alert-circle" class="w-3 h-3"></i>
                                    <span>{{ $msec }}</span>
                                </span>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pintas ke Fitur Saran Patch & Chat AI di CV Editor -->
            <div class="bg-gradient-to-br from-navy to-[#263852] text-white rounded-3xl p-5 shadow-sm space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-9 h-9 rounded-2xl bg-white/10 grid place-items-center text-white shrink-0">
                            <i data-lucide="wand-2" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm">Saran Patch AI & Konsultasi Chat</h3>
                            <p class="text-[11px] text-white/75">Gunakan Asisten AI di Editor CV untuk menyuntikkan perbaikan kalimat dan tanya jawab real-time.</p>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end">
                    <a href="{{ route('bkk.me.cv.edit') }}" class="h-9 px-4 rounded-full bg-white text-navy hover:bg-canvas text-xs font-bold inline-flex items-center gap-1.5 transition">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-maroon"></i>
                        <span>Buka Editor & Gunakan AI</span>
                    </a>
                </div>
            </div>

            <!-- Versioning / Riwayat Versi CV Card (Hanya versi tersimpan temp = false) -->
            <div class="bg-white border border-line rounded-3xl overflow-hidden shadow-sm">
                <div class="p-5 border-b border-line flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full bg-navy/10 text-navy grid place-items-center">
                            <i data-lucide="history" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <div class="font-bold text-sm text-navy">Riwayat Versi CV</div>
                            <div class="text-[11px] text-muted">{{ count($cvVersions ?? []) }} versi resmi tersimpan</div>
                        </div>
                    </div>
                    <span class="text-[11px] text-muted font-mono">Checksum SHA-256</span>
                </div>

                <div class="divide-y divide-line/60 max-h-[300px] overflow-y-auto">
                    @forelse($cvVersions ?? [] as $ver)
                        <div class="p-4 flex items-center justify-between gap-3 hover:bg-[#F5F7FA] transition-colors {{ $ver->is_main ? 'bg-emerald-50/40' : '' }}">
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
                                    @if($ver->score !== null)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-navy/5 text-navy">
                                            Skor {{ $ver->score }}
                                        </span>
                                    @elseif(is_array($ver->feedback) && isset($ver->feedback['total_score']))
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-navy/5 text-navy">
                                            Skor {{ $ver->feedback['total_score'] }}
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
            showToast('Evaluasi skor ATS selesai dan tersimpan di cv_resumes.feedback!');
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
        if (scoreText) scoreText.innerText = newScore;

        if (scoreRing) {
            const radius = 61;
            const c = 2 * Math.PI * radius;
            scoreRing.style.strokeDashoffset = c - (newScore / 100) * c;
        }

        if (gradeBadge && data.grade_label) {
            gradeBadge.innerText = data.grade_label;
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
            paramsGrid.innerHTML = data.parameters.map((p) => {
                const pScore = Number(p.score ?? 85);
                const pMax = Number(p.max_score ?? 100);
                const pStatus = String(p.status || 'GOOD').toUpperCase();
                const pWeight = Math.round(Number(p.weight ?? 0.25) * 100);
                const pIcon = iconMap[p.id] || 'check-circle';
                const badgeClass = pStatus === 'EXCELLENT'
                    ? 'bg-emerald-100 text-emerald-800'
                    : (pStatus === 'GOOD' ? 'bg-navy/10 text-navy' : 'bg-amber-100 text-amber-800');

                return `
                    <div class="rounded-2xl bg-[#F5F7FA] border border-line p-3.5 space-y-2">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 text-xs font-bold text-navy">
                                <i data-lucide="${pIcon}" class="w-4 h-4 text-navy shrink-0"></i>
                                <span>${escapeHtml(p.label)}</span>
                                <span class="text-[10px] font-normal text-muted">(${pWeight}%)</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="px-2 py-0.5 rounded-full text-[9px] font-bold uppercase ${badgeClass}">
                                    ${escapeHtml(pStatus)}
                                </span>
                                <span class="text-xs font-bold text-navy">${pScore}<span class="text-[10px] text-muted font-normal">/${pMax}</span></span>
                            </div>
                        </div>
                        <div class="h-1.5 rounded-full bg-line/60 overflow-hidden">
                            <div class="h-full rounded-full ${pScore >= 85 ? 'bg-navy' : 'bg-maroon'}" style="width: ${Math.min(100, pScore)}%"></div>
                        </div>
                        ${p.feedback ? `<p class="text-[11px] text-muted leading-relaxed">${escapeHtml(p.feedback)}</p>` : ''}
                    </div>
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
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                    <i data-lucide="alert-circle" class="w-3 h-3"></i>
                    <span>${escapeHtml(msec)}</span>
                </span>
            `).join('');
        }

        lucide.createIcons();
    }

    function downloadCvPdf() {
        window.print();
    }
</script>
@endpush
