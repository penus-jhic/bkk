@extends('me.master')

@section('title', 'Editor CV ATS - BKK SMK Plus Pelita Nusantara')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dompurify@3.2.4/dist/purify.min.js"></script>
<style>
    .editor-textarea {
        tab-size: 2;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    }
    .ai-chat-prose p { margin-bottom: 0.4rem; }
    .ai-chat-prose p:last-child { margin-bottom: 0; }
    .ai-chat-prose ul, .ai-chat-prose ol { padding-left: 1.1rem; margin: 0.35rem 0; }
    .ai-chat-prose ul { list-style-type: disc; }
    .ai-chat-prose ol { list-style-type: decimal; }


    /* Mode tampilan editor: Tulis / Belah / Pratinjau */
    #editorColumnsWrapper[data-view="write"] #previewCard { display: none; }
    #editorColumnsWrapper[data-view="preview"] #editorCard { display: none; }
    @media (min-width: 1024px) {
        #editorColumnsWrapper[data-view="split"] { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        #editorColumnsWrapper[data-view="split"] #editorCard { border-right: 1px solid rgb(36 16 18 / 0.1); }
    }

    /* Mode layar penuh: workspace memenuhi layar, panel AI pindah ke laci */
    #cvEditorWrapper.is-fullscreen {
        position: fixed; inset: 0; z-index: 50; background: #fff;
        padding: 1rem 1.5rem; display: flex; flex-direction: column; gap: .75rem; overflow: hidden;
    }
    #cvEditorWrapper.is-fullscreen .hide-in-fullscreen,
    #cvEditorWrapper.is-fullscreen #desktopAiPanel { display: none !important; }
    #cvEditorWrapper.is-fullscreen .show-in-fullscreen { display: inline-flex !important; }
    #cvEditorWrapper.is-fullscreen #editorWorkspaceGrid { flex: 1; min-height: 0; display: flex; }
    #cvEditorWrapper.is-fullscreen .workspace-frame,
    #cvEditorWrapper.is-fullscreen #workspaceCard { flex: 1; min-height: 0; display: flex; flex-direction: column; }
    #cvEditorWrapper.is-fullscreen #editorColumnsWrapper { flex: 1; min-height: 0; }
    #cvEditorWrapper.is-fullscreen .editor-pane-body { height: 100%; flex: 1; min-height: 0; }

    /* Lembar pratinjau seperti kertas A4 */
    #livePreviewContainer { box-shadow: 0 20px 40px -24px rgb(36 16 18 / 0.25); }
</style>
@endpush

@php
    $role = strtoupper($authUser['role'] ?? 'SISWA');
    $isSiswa = ($role === 'SISWA');
    $isMainCv = (bool) ($activeCv?->is_main ?? true);
    $userJurusan = $profile['jurusan'] ?? ($isSiswa ? 'Rekayasa Perangkat Lunak' : 'Teknik Komputer dan Jaringan');

    // "Versi Baru" (?baru=1): mulai dari templat CV kosong. Menyimpannya membuat versi baru yang menjadi CV Utama,
    // sama seperti menyimpan perubahan biasa; versi lama tetap ada di riwayat.
    $isNewVersion = request()->boolean('baru');
    if ($isNewVersion) {
        $templateName = $authUser['nama_lengkap'] ?? 'Nama Lengkap';
        $templateEmail = $profile['email'] ?? ($authUser['email'] ?? 'email@contoh.com');
        $templatePhone = ($profile['phone'] ?? '-') !== '-' ? $profile['phone'] : '0812-xxxx-xxxx';
        $cvMarkdown = "# {$templateName}\n**Siswa SMK Plus Pelita Nusantara · {$userJurusan}**\n"
            . "Email: {$templateEmail} | Telepon: {$templatePhone} | [Kota]\n\n"
            . "## Ringkasan Profesional\n[Tulis 2-3 kalimat tentang keahlian utama dan posisi yang kamu tuju.]\n\n"
            . "## Pendidikan\n**SMK Plus Pelita Nusantara** — {$userJurusan}\n*[Tahun Masuk] - [Tahun Lulus]* | Nilai rata-rata: [nilai]\n\n"
            . "## Pengalaman PKL & Kerja\n**[Nama Perusahaan]** — [Posisi]\n*[Bulan Tahun] - [Bulan Tahun]*\n- [Apa yang kamu kerjakan + hasil terukur]\n\n"
            . "## Keahlian Teknis\n- [Keahlian 1], [Keahlian 2], [Keahlian 3]\n";
        $cvFeedback = null;
        $cvScore = null;
    }

    // Analisis skor dari versi tersimpan terakhir (cv_resumes.feedback), sama dengan CV & AI Scoring Hub
    $feedback = $cvFeedback ?? [];
    $feedbackScore = $feedback['total_score'] ?? ($activeCv?->score ?? ($cvScore['total'] ?? null));
    $feedbackScore = ($feedbackScore !== null && !$isNewVersion) ? (int) $feedbackScore : null;

    // Tiga warna penanda skor (tanpa gradasi): hijau >= 85, biru 70-84, merah < 70. Sama dengan CV Hub & scoreTone() di JS.
    $scoreBar = fn (int $pct): string => $pct >= 85 ? 'bg-emerald-500' : ($pct >= 70 ? 'bg-navy' : 'bg-brand-darkred');
    $feedbackGrade = $feedback['grade_label'] ?? ($feedbackScore === null ? null : ($feedbackScore >= 90 ? 'Sangat Baik' : ($feedbackScore >= 80 ? 'Baik' : ($feedbackScore >= 70 ? 'Cukup' : 'Perlu Perbaikan'))));
    $percentile = isset($feedback['percentile']) ? (int) $feedback['percentile'] : null;
    $verdict = $feedback['summary_verdict'] ?? null;
    $parameters = $feedback['parameters'] ?? ($cvScore['params'] ?? []);
    $paramIcons = [
        'param_industry_fit' => 'target',
        'param_completeness' => 'layers',
        'param_ats_format' => 'file-search',
        'param_portfolio_impact' => 'award',
    ];

    $viewModes = [
        ['key' => 'write', 'label' => 'Tulis', 'icon' => 'pen-line'],
        ['key' => 'split', 'label' => 'Belah', 'icon' => 'columns-2'],
        ['key' => 'preview', 'label' => 'Pratinjau', 'icon' => 'eye'],
    ];

    // Tombol format Markdown: action = fungsi JS, args = argumen (JSON)
    $formatButtons = [
        ['icon' => 'heading-1', 'title' => 'Nama / judul utama (# )', 'action' => 'prefixLines', 'args' => ['# ']],
        ['icon' => 'heading-2', 'title' => 'Judul bagian (## )', 'action' => 'prefixLines', 'args' => ['## ']],
        ['icon' => 'bold', 'title' => 'Tebal (Ctrl+B)', 'action' => 'wrapSelection', 'args' => ['**', '**', 'teks tebal']],
        ['icon' => 'italic', 'title' => 'Miring (Ctrl+I)', 'action' => 'wrapSelection', 'args' => ['*', '*', 'teks miring']],
        ['icon' => 'list', 'title' => 'Butir daftar (- )', 'action' => 'prefixLines', 'args' => ['- ']],
        ['icon' => 'link', 'title' => 'Tautan', 'action' => 'wrapSelection', 'args' => ['[', '](https://)', 'teks tautan']],
    ];

    $sectionTemplates = [
        'summary' => 'Ringkasan Profesional',
        'education' => 'Pendidikan',
        'experience' => 'Pengalaman PKL & Kerja',
        'skills' => 'Keahlian Teknis',
        'projects' => 'Proyek & Portofolio',
        'certifications' => 'Sertifikasi',
    ];
@endphp

@section('content')
<div id="cvEditorWrapper" class="fade-up space-y-5">
    {{-- ============================================================
         1. HEADER: judul, status dokumen, aksi utama
         ============================================================ --}}
    <header id="cvPageHeader" class="flex flex-col gap-4 2xl:flex-row 2xl:items-end 2xl:justify-between">
        <div class="min-w-0">
            <div class="dash-eyebrow hide-in-fullscreen">
                <a href="{{ route('bkk.me.index') }}">Portal Siswa</a>
                <i data-lucide="chevron-right"></i>
                <a href="{{ route('bkk.me.cv') }}">CV &amp; AI Scoring</a>
                <i data-lucide="chevron-right"></i>
                <span>AI CV Studio</span>
            </div>
            <h1 class="dash-title font-display text-brand-ink">Editor <x-sketch.underline>CV ATS</x-sketch.underline></h1>

            <div class="mt-1 flex flex-wrap items-center gap-2 text-xs">
                <span id="draftDirtyBadge" class="hidden items-center gap-1.5 px-2.5 py-1 rounded-full font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                    <span>Perubahan belum disimpan</span>
                </span>
            </div>
            <p class="hide-in-fullscreen mt-3 max-w-2xl text-sm text-brand-ink/65 leading-relaxed">
                Tulis CV-mu, pantau analisis ATS yang berubah saat kamu mengetik, lalu simpan supaya Penus AI menilai ulang skornya.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2 shrink-0">
            {{-- Ganti versi (hanya versi tersimpan, temp = false) --}}
            @if(isset($cvVersions) && $cvVersions->count() > 1)
                <label class="sr-only" for="cvVersionSwitcher">Ganti versi CV</label>
                <select id="cvVersionSwitcher" onchange="switchCvVersion(this.value)" class="h-10 pl-3.5 pr-8 rounded-full border border-brand-ink/15 bg-white hover:bg-brand-softmist text-brand-ink text-xs font-semibold focus:outline-none focus:border-navy focus:ring-4 focus:ring-navy/10 cursor-pointer" title="Ganti Versi CV">
                    @if($isNewVersion)
                        <option value="" selected disabled>Versi baru (belum disimpan)</option>
                    @endif
                    @foreach($cvVersions as $v)
                        @php
                            $targetUrl = $v->is_main ? route('bkk.me.cv.edit') : route('bkk.me.cv.editVersion', $v->id);
                            $isSelected = !$isNewVersion && ($activeCv?->id === $v->id);
                        @endphp
                        <option value="{{ $targetUrl }}" {{ $isSelected ? 'selected' : '' }}>
                            Versi #{{ $v->id }} {{ $v->is_main ? '⭐ (CV Utama)' : '— ' . ($v->created_at ? $v->created_at->format('d M H:i') : '') }}
                        </option>
                    @endforeach
                </select>
            @endif

            <button type="button" onclick="openVersionModal()" class="h-10 px-4 rounded-full border border-brand-ink/15 bg-white hover:bg-brand-softmist text-brand-ink text-xs font-semibold inline-flex items-center gap-1.5 transition-colors cursor-pointer" title="Lihat Riwayat Versi">
                <i data-lucide="history" class="w-4 h-4 text-brand-darkred"></i>
                <span class="hidden sm:inline">Riwayat</span>
                <span id="versionCountBadge" class="min-w-5 h-5 px-1 rounded-full bg-brand-ink/10 text-brand-ink text-[10px] grid place-items-center font-bold">{{ count($cvVersions ?? []) }}</span>
            </button>

            <button type="button" id="btnToggleFullscreen" onclick="toggleFullscreen()" class="h-10 w-10 sm:w-auto sm:px-4 rounded-full border border-brand-ink/15 bg-white hover:bg-brand-softmist text-brand-ink text-xs font-semibold inline-flex items-center justify-center gap-1.5 transition-colors cursor-pointer" title="Mode fokus layar penuh (Esc untuk keluar)">
                <i data-lucide="maximize-2" id="fullscreenIcon" class="w-4 h-4"></i>
                <span id="fullscreenText" class="hidden sm:inline">Layar Penuh</span>
            </button>

            {{-- Panel AI sebagai laci: di layar kecil & mode layar penuh (di desktop panelnya sudah tampil di kanan) --}}
            <button type="button" onclick="toggleMobileAi(true)" class="show-in-fullscreen xl:hidden h-10 px-4 rounded-full bg-brand-darkred/10 hover:bg-brand-darkred hover:text-white text-brand-darkred text-xs font-semibold inline-flex items-center gap-1.5 transition-colors cursor-pointer" title="Buka Panel Penus AI">
                <i data-lucide="sparkles" class="w-4 h-4"></i>
                <span>Penus AI</span>
            </button>

            @unless($isNewVersion)
                <a href="{{ route('bkk.me.cv.edit', ['baru' => 1]) }}" class="hide-in-fullscreen h-10 px-4 rounded-full border border-brand-ink/15 bg-white hover:bg-brand-softmist text-brand-ink text-xs font-semibold inline-flex items-center gap-1.5 transition-colors" title="Mulai CV baru dari templat">
                    <i data-lucide="file-plus-2" class="w-4 h-4 text-brand-darkred"></i>
                    Versi Baru
                </a>
            @endunless

            <a href="{{ route('bkk.me.cv') }}" class="hide-in-fullscreen h-10 px-4 rounded-full border border-brand-ink/15 bg-white hover:bg-brand-softmist text-brand-ink text-xs font-semibold inline-flex items-center gap-1.5 transition-colors">
                <i data-lucide="file-text" class="w-4 h-4 text-brand-darkred"></i>
                Pratinjau &amp; Skor
            </a>

            <button id="btnSaveCv" onclick="saveCv()" class="h-10 px-5 rounded-full bg-brand-darkred hover:bg-brand-deepred text-white text-xs font-semibold inline-flex items-center gap-2 transition-colors cursor-pointer disabled:opacity-70" title="Simpan versi resmi (Ctrl+S)">
                <i data-lucide="save" class="w-4 h-4"></i>
                <span>Simpan CV</span>
            </button>
        </div>
    </header>

    {{-- Draf lokal yang belum tersimpan (localStorage), ditawarkan untuk dipulihkan --}}
    <div id="draftRestoreBanner" class="hidden relative rounded-card border border-dashed border-brand-signal/40 bg-brand-signal/5 p-4 flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-brand-ink">
        <div class="flex items-start gap-2.5">
            <i data-lucide="life-buoy" class="w-4 h-4 text-brand-darkred mt-0.5 shrink-0"></i>
            <p><b>Ada draf yang belum tersimpan</b> di browser ini (<span id="draftRestoreTime">-</span>). Pulihkan untuk melanjutkan tulisan terakhirmu.</p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <button type="button" onclick="restoreLocalDraft()" class="h-8 px-4 rounded-full bg-brand-darkred hover:bg-brand-deepred text-white font-semibold transition-colors cursor-pointer">Pulihkan Draf</button>
            <button type="button" onclick="discardLocalDraft()" class="h-8 px-3.5 rounded-full border border-brand-ink/15 bg-white hover:bg-brand-softmist font-semibold transition-colors cursor-pointer">Buang</button>
        </div>
    </div>

    {{-- Versi arsip: menyimpan akan menerbitkan versi baru sebagai CV Utama --}}
    @if($isNewVersion)
        <div class="hide-in-fullscreen rounded-card border border-navy/20 bg-navy/5 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-brand-ink">
            <div class="flex items-start gap-2.5">
                <i data-lucide="file-plus-2" class="w-4 h-4 text-navy mt-0.5 shrink-0"></i>
                <p><b>Membuat versi CV baru.</b> Ganti teks dalam [kurung] dengan datamu, lalu tekan Simpan CV. Versi ini akan menjadi CV Utama dan versi lama tetap tersimpan di riwayat.</p>
            </div>
            <a href="{{ route('bkk.me.cv.edit') }}" class="h-8 px-3.5 rounded-full border border-brand-ink/15 bg-white hover:bg-brand-softmist font-semibold transition inline-flex items-center shrink-0">
                Batal
            </a>
        </div>
    @endif

    @if(!$isMainCv && $activeCv && !$isNewVersion)
        <div id="archiveNoticeBanner" class="hide-in-fullscreen rounded-card border border-amber-200 bg-amber-50/70 p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-amber-900">
            <div class="flex items-start gap-2.5">
                <i data-lucide="info" class="w-4 h-4 text-amber-600 mt-0.5 shrink-0"></i>
                <div>
                    <span class="font-bold">Mode Sunting Versi Arsip (#{{ $activeCv->id }}):</span>
                    <span>Versi ini dibuat pada {{ $activeCv->created_at ? $activeCv->created_at->format('d M Y, H:i') : '-' }}. Menyimpan dokumen ini akan menerbitkan versi baru dan otomatis menjadikannya sebagai CV Utama yang aktif.</span>
                </div>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <form action="{{ route('bkk.me.cv.setMain', $activeCv->id) }}" method="POST">
                    @csrf
                    <button type="submit" class="h-8 px-3.5 rounded-full bg-amber-600 hover:bg-amber-700 text-white font-semibold transition cursor-pointer inline-flex items-center gap-1.5">
                        <i data-lucide="check" class="w-3.5 h-3.5"></i>
                        <span>Jadikan CV Utama</span>
                    </button>
                </form>
                <a href="{{ route('bkk.me.cv.edit') }}" class="h-8 px-3 rounded-full border border-amber-300 bg-white hover:bg-amber-100 text-amber-900 font-semibold transition cursor-pointer inline-flex items-center">
                    Buka CV Utama
                </a>
                @include('me.partials.cv-delete-button', ['version' => $cvVersions->firstWhere('id', $activeCv->id) ?? $activeCv, 'compact' => false])
            </div>
        </div>
    @endif

    {{-- ============================================================
         2. WORKSPACE (editor + pratinjau) & PANEL PENUS AI
         ============================================================ --}}
    <div id="editorWorkspaceGrid" class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_350px] 2xl:grid-cols-[minmax(0,1fr)_380px] gap-6 items-start">

        {{-- Bingkai coretan di pembungkus, karena kartunya sendiri overflow-hidden (goresan keluar ±8px) --}}
        <div class="workspace-frame relative min-w-0">
            <x-sketch.box />
        <section id="workspaceCard" class="relative flex flex-col min-w-0 h-full bg-white overflow-hidden" aria-label="Lembar kerja CV">
            {{-- Toolbar: mode tampilan, format Markdown, sisipkan bagian --}}
            <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2.5 px-4 py-3 border-b border-brand-ink/10">
                <div class="flex items-center gap-1" role="tablist" aria-label="Mode tampilan">
                    @foreach($viewModes as $mode)
                        <button type="button" role="tab" data-view-btn="{{ $mode['key'] }}" onclick="setViewMode('{{ $mode['key'] }}')"
                            class="{{ $mode['key'] === 'split' ? 'bg-navy text-white hidden lg:inline-flex' : 'text-brand-ink/60 hover:bg-brand-ink/5 inline-flex' }} items-center gap-1.5 h-8 px-3 rounded-full text-xs font-semibold transition-colors cursor-pointer">
                            <i data-lucide="{{ $mode['icon'] }}" class="w-3.5 h-3.5"></i>
                            {{ $mode['label'] }}
                        </button>
                    @endforeach
                </div>

                <div class="flex flex-wrap items-center gap-1">
                    @foreach($formatButtons as $btn)
                        <button type="button" onclick='{{ $btn['action'] }}(...@json($btn['args']))' title="{{ $btn['title'] }}" aria-label="{{ $btn['title'] }}"
                            class="w-8 h-8 rounded-lg grid place-items-center text-brand-ink/70 hover:bg-brand-ink/5 hover:text-brand-darkred transition-colors cursor-pointer">
                            <i data-lucide="{{ $btn['icon'] }}" class="w-4 h-4"></i>
                        </button>
                    @endforeach
                </div>
            </div>

            {{-- Editor & pratinjau --}}
            <div id="editorColumnsWrapper" data-view="split" class="grid grid-cols-1 flex-1 min-h-0">
                <div id="editorCard" class="flex flex-col min-w-0 min-h-0">
                    <div class="flex items-center justify-between gap-2 px-4 pt-3 pb-2">
                        <span class="text-[11px] font-bold uppercase tracking-[0.14em] text-brand-ink/50">Kode Markdown</span>
                        <span class="hidden sm:inline text-[11px] text-brand-ink/40"># nama · ## bagian · - butir</span>
                    </div>
                    <textarea
                        id="rawMarkdownTextarea"
                        oninput="onRawMarkdownInput()"
                        onkeydown="handleEditorKeydown(event)"
                        spellcheck="false"
                        aria-label="Kode Markdown CV"
                        placeholder="Mulai menulis CV Markdown Anda di sini..."
                        class="editor-pane-body editor-textarea w-full h-[620px] px-4 pb-4 bg-transparent outline-none text-xs 2xl:text-[13px] text-brand-ink leading-relaxed resize-none selection:bg-brand-darkred/20 selection:text-brand-ink"
                    >{{ $cvMarkdown }}</textarea>
                </div>

                <div id="previewCard" class="flex flex-col min-w-0 min-h-0 bg-brand-paper/70">
                    <div class="flex items-center justify-between gap-2 px-4 pt-3 pb-2">
                        <span class="text-[11px] font-bold uppercase tracking-[0.14em] text-brand-ink/50">Pratinjau Lembar ATS</span>
                        <span class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-emerald-700">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Sinkron otomatis
                        </span>
                    </div>
                    <div id="previewScrollContainer" class="editor-pane-body h-[620px] overflow-y-auto px-5 pt-4 pb-8">
                        <div class="relative mx-auto max-w-[720px]">
                            <div id="livePreviewContainer" class="relative bg-white rounded-lg border border-brand-ink/10 p-6 2xl:p-9 md-cv text-brand-ink min-h-[540px]">
                                {{-- Diisi marked.js + DOMPurify --}}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bilah status --}}
            <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5 border-t border-brand-ink/10 text-[11px] text-brand-ink/55">
                <div class="flex items-center gap-2">
                    <span id="wordCount">0 kata</span>
                    <span aria-hidden="true">·</span>
                    <span id="charCount">0 karakter</span>
                    <span aria-hidden="true">·</span>
                    <span id="lineCount">0 baris</span>
                </div>
                <div class="flex items-center gap-3">
                    <span id="saveStatusText" class="inline-flex items-center gap-1.5 font-semibold text-emerald-700">
                        <i data-lucide="cloud-check" class="w-3.5 h-3.5"></i>Tersimpan
                    </span>
                </div>
            </div>
        </section>
        </div>

        {{-- Panel Penus AI (desktop). Di layar kecil isinya dipindah ke laci #mobileAiDrawer --}}
        <div class="ai-panel-frame hide-in-fullscreen hidden xl:block sticky top-4">
            <x-sketch.box />
        <aside id="desktopAiPanel" class="relative flex flex-col max-h-[calc(100vh-6rem)] bg-white overflow-hidden" aria-label="Panel Penus AI">
            <div id="aiAssistantRoot" class="flex flex-col flex-1 min-h-0">
                <div class="px-4 pt-4 pb-3 border-b border-brand-ink/10 shrink-0">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-9 h-9 rounded-full bg-brand-darkred grid place-items-center text-white shadow-sm shrink-0">
                                <i data-lucide="sparkles" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0 leading-tight">
                                <div class="font-display text-base uppercase tracking-wide text-brand-ink truncate">Penus AI Analyzer</div>
                                <div class="text-[11px] text-brand-ink/55 truncate">{{ $userJurusan }}</div>
                            </div>
                        </div>
                        <a href="{{ route('bkk.me.cv') }}" id="compactScorePill" class="px-2.5 py-1 rounded-full border border-brand-ink/10 bg-white hover:bg-brand-softmist transition flex items-center gap-1.5 shrink-0" title="Lihat rincian evaluasi skor ATS di CV Hub">
                            <span class="w-2 h-2 rounded-full {{ $feedbackScore ? 'bg-emerald-500' : 'bg-amber-400' }}" id="compactScoreDot"></span>
                            <span class="text-[11px] font-bold text-brand-ink" id="compactScoreText">Skor: {{ $feedbackScore ? $feedbackScore . '/100' : 'Menunggu' }}</span>
                        </a>
                    </div>

                    {{-- Tab: Analisis (lokal + skor), Saran AI (/suggestions), Tanya AI (/chat) --}}
                    <div class="mt-3 flex items-center gap-1 p-1 rounded-full bg-brand-paper" role="tablist" aria-label="Panel Penus AI">
                        <button type="button" role="tab" id="tabBtnAnalysis" onclick="switchAiTab('analysis')" class="bg-navy text-white flex-1 h-8 rounded-full text-xs font-semibold inline-flex items-center justify-center gap-1.5 cursor-pointer transition-colors">
                            <i data-lucide="scan-search" class="w-3.5 h-3.5"></i>
                            Analisis
                        </button>
                        <button type="button" role="tab" id="tabBtnSuggestions" onclick="switchAiTab('suggestions')" class="text-brand-ink/60 hover:bg-brand-ink/5 flex-1 h-8 rounded-full text-xs font-semibold inline-flex items-center justify-center gap-1.5 cursor-pointer transition-colors">
                            <i data-lucide="wand-2" class="w-3.5 h-3.5"></i>
                            Saran AI
                            <span id="suggestionsCountBadge" class="hidden min-w-4 h-4 px-1 rounded-full bg-brand-darkred text-white text-[10px] font-bold grid place-items-center">0</span>
                        </button>
                        <button type="button" role="tab" id="tabBtnChat" onclick="switchAiTab('chat')" class="text-brand-ink/60 hover:bg-brand-ink/5 flex-1 h-8 rounded-full text-xs font-semibold inline-flex items-center justify-center gap-1.5 cursor-pointer transition-colors">
                            <i data-lucide="message-square-text" class="w-3.5 h-3.5"></i>
                            Tanya AI
                        </button>
                    </div>
                </div>

                {{-- TAB 1: ANALISIS --}}
                <div id="aiTabAnalysis" class="flex-1 overflow-y-auto p-4 space-y-6 text-xs">
                    {{-- Skor ATS versi tersimpan --}}
                    <div class="relative rounded-card bg-brand-paper p-4">
                        @if($feedbackScore !== null)
                            <div class="flex items-center gap-5">
                                <div class="shrink-0">
                                    <span class="font-display text-5xl font-bold text-brand-ink leading-none">{{ $feedbackScore }}</span>
                                    <span class="text-sm font-semibold text-brand-ink/40">/100</span>
                                </div>
                                <div class="min-w-0 space-y-1.5">
                                    <span class="inline-flex px-2.5 py-0.5 rounded-full bg-brand-darkred text-white text-[11px] font-bold">{{ $feedbackGrade }}</span>
                                    @if($percentile)
                                        <p class="text-[11px] text-brand-ink/65 leading-snug">Lebih kuat dari <b class="text-brand-ink">{{ $percentile }}%</b> pelamar dengan keahlian serupa.</p>
                                    @endif
                                </div>
                            </div>
                            @if($verdict)
                                <p class="mt-4 text-[12px] leading-relaxed text-brand-ink/75">{{ $verdict }}</p>
                            @endif
                        @else
                            <div class="text-center py-2">
                                <p class="font-display text-lg uppercase tracking-wide text-brand-ink">Belum Ada Skor</p>
                                <p class="mt-1 text-[11px] text-brand-ink/60">Simpan CV untuk dinilai Penus AI. Sementara itu, ikuti cek langsung di bawah.</p>
                            </div>
                        @endif
                        @if($feedbackScore !== null)
                            <p id="staleScoreNote" class="hidden mt-3 items-start gap-1.5 rounded-lg bg-amber-50 border border-amber-200 px-2.5 py-2 text-[11px] text-amber-900">
                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5 shrink-0 mt-px"></i>
                            <span>Skor ini dari versi tersimpan. Simpan CV untuk menilai ulang perubahanmu.</span>
                        </p>
                        @endif
                    </div>

                    {{-- Rincian parameter skor --}}
                    @if(!empty($parameters))
                        <div>
                            <h2 class="text-[11px] font-bold uppercase tracking-[0.14em] text-brand-ink/50">Rincian Penilaian</h2>
                            <ul class="mt-3">
                                @foreach($parameters as $i => $param)
                                    @php
                                        $pScore = (int) ($param['score'] ?? 0);
                                        $pMax = (int) ($param['max_score'] ?? 100) ?: 100;
                                        $pPct = max(0, min(100, round($pScore / $pMax * 100)));
                                    @endphp
                                    <li class="py-3 first:pt-0 border-t border-brand-ink/10 first:border-t-0">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="flex items-center gap-2 font-semibold text-brand-ink">
                                                <i data-lucide="{{ $paramIcons[$param['id'] ?? ''] ?? 'circle-dot' }}" class="w-3.5 h-3.5 text-brand-darkred"></i>
                                                {{ $param['label'] ?? 'Parameter' }}
                                            </span>
                                            <span class="font-display text-base text-brand-ink">{{ $pScore }}<span class="text-[10px] text-brand-ink/40">/{{ $pMax }}</span></span>
                                        </div>
                                        <div class="mt-2 h-1.5 rounded-full bg-brand-ink/[0.07] overflow-hidden">
                                            <div class="h-full rounded-full {{ $scoreBar((int) $pPct) }}" style="width: {{ $pPct }}%"></div>
                                        </div>
                                        @if(!empty($param['feedback']))
                                            <p class="mt-1.5 text-[11px] leading-relaxed text-brand-ink/60">{{ $param['feedback'] }}</p>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Cek langsung dari isi editor --}}
                    <div>
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-[11px] font-bold uppercase tracking-[0.14em] text-brand-ink/50">Cek Langsung ATS</h2>
                            <span id="liveCheckSummary" class="text-[11px] font-bold text-brand-darkred">0/0 lolos</span>
                        </div>
                        <div class="mt-3 h-1.5 rounded-full bg-brand-ink/[0.07] overflow-hidden">
                            <div id="liveCheckBar" class="h-full rounded-full bg-brand-darkred transition-all duration-300" style="width: 0%"></div>
                        </div>
                        <ul id="liveChecksList" class="mt-3" aria-live="polite"></ul>
                    </div>
                </div>

                {{-- TAB 2: SARAN PERBAIKAN AI (/api/ai/cv/suggestions) --}}
                <div id="aiTabSuggestions" class="hidden flex-1 overflow-y-auto p-4 space-y-3 text-xs">
                    <div class="relative rounded-card bg-brand-paper p-4 space-y-3">
                        <div>
                            <div class="font-display text-sm uppercase tracking-wide text-brand-ink">Saran Siap Pakai</div>
                            <p class="text-[11px] text-brand-ink/60 mt-1 leading-relaxed">
                                Penus AI membaca draf terbarumu (tanpa menambah riwayat versi) lalu menyusun perbaikan yang bisa diterapkan dengan satu klik.
                            </p>
                        </div>
                        <button type="button" id="btnFetchSuggestions" onclick="runAiSuggestions()" class="group w-full h-10 px-4 rounded-full bg-navy hover:bg-navy-dark text-white text-xs font-semibold inline-flex items-center justify-center gap-2 transition cursor-pointer disabled:opacity-60">
                            <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                            <span>Analisis &amp; Minta Saran AI</span>
                        </button>
                    </div>

                    <div id="aiSuggestionsLoading" class="hidden rounded-card p-5 bg-brand-paper border border-brand-ink/10 text-center space-y-2">
                        <div class="inline-flex items-center gap-2 text-brand-ink font-semibold text-xs">
                            <svg class="animate-spin w-4 h-4 text-brand-darkred" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span id="aiSuggestionsLoadingText">Menyiapkan draf &amp; menganalisis CV...</span>
                        </div>
                        <p class="text-[11px] text-brand-ink/55">Menyusun patch perbaikan berstandar ATS kejuruan...</p>
                    </div>

                    <div id="aiSuggestionsList" class="space-y-3">
                        <div class="rounded-card border border-dashed border-brand-ink/15 p-5 text-center text-brand-ink/55 text-[11px] leading-relaxed">
                            Tekan <b class="text-brand-ink">"Analisis &amp; Minta Saran AI"</b> untuk mendapat rekomendasi beserta perubahan teks yang bisa langsung diterapkan ke editor.
                        </div>
                    </div>
                </div>

                {{-- TAB 3: TANYA AI (/api/ai/cv/chat) --}}
                <div id="aiTabChat" class="hidden flex-1 flex flex-col min-h-0 p-4 text-xs">
                    <div id="aiChatHistory" class="flex-1 overflow-y-auto space-y-3 pr-1 min-h-[240px]">
                        <div class="flex gap-2 items-start">
                            <div class="w-7 h-7 rounded-full bg-brand-darkred grid place-items-center text-white shrink-0">
                                <i data-lucide="bot" class="w-3.5 h-3.5"></i>
                            </div>
                            <div class="text-xs rounded-2xl px-3 py-2 bg-brand-paper text-brand-ink rounded-tl-sm leading-relaxed">
                                Halo! Saya <b>Penus AI</b> untuk jurusan <b>{{ $userJurusan }}</b>. Tanyakan cara memperkuat butir pengalaman, kata kunci ATS, atau format CV-mu.
                            </div>
                        </div>
                    </div>

                    <div id="aiSuggestedActionsBox" class="hidden pt-2.5 space-y-1.5 border-t border-brand-ink/10 mt-2">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-brand-ink/50">Aksi Disarankan AI:</div>
                        <div id="aiSuggestedActionsList" class="flex flex-col gap-1.5"></div>
                    </div>

                    <div id="aiQuickChipsContainer" class="flex flex-wrap gap-1.5 pt-2.5 mt-2 border-t border-brand-ink/10">
                        <button type="button" data-chat-prompt="Perbaiki kalimat pengalaman agar lebih formal" class="px-2.5 py-1 rounded-full border border-brand-ink/10 bg-white hover:bg-navy hover:text-white hover:border-navy text-[11px] font-medium text-brand-ink transition cursor-pointer">
                            Perbaiki formalitas
                        </button>
                        <button type="button" data-chat-prompt="Bagaimana membuat ringkasan profil lebih singkat?" class="px-2.5 py-1 rounded-full border border-brand-ink/10 bg-white hover:bg-navy hover:text-white hover:border-navy text-[11px] font-medium text-brand-ink transition cursor-pointer">
                            Ringkas profil
                        </button>
                        <button type="button" data-chat-prompt="Berikan kata kunci ATS terbaik untuk jurusan {{ $userJurusan }}" class="px-2.5 py-1 rounded-full border border-brand-ink/10 bg-white hover:bg-navy hover:text-white hover:border-navy text-[11px] font-medium text-brand-ink transition cursor-pointer">
                            Kata kunci ATS
                        </button>
                    </div>

                    <form class="flex items-center gap-1.5 pt-2.5 shrink-0" onsubmit="event.preventDefault(); sendChatPrompt();">
                        <label for="aiChatInput" class="sr-only">Pertanyaan untuk Penus AI</label>
                        <input
                            type="text"
                            id="aiChatInput"
                            autocomplete="off"
                            placeholder="Ketik pertanyaan untuk AI..."
                            class="flex-1 h-10 px-4 rounded-full border border-brand-ink/15 bg-white focus:border-navy focus:ring-4 focus:ring-navy/10 text-xs outline-none text-brand-ink"
                        />
                        <button type="submit" id="btnSendChat" class="h-10 w-10 rounded-full bg-navy hover:bg-navy-light text-white grid place-items-center cursor-pointer shrink-0" aria-label="Kirim pertanyaan">
                            <i data-lucide="send" class="w-4 h-4"></i>
                        </button>
                    </form>
                </div>
            </div>
        </aside>
        </div>
    </div>
</div>

{{-- Modal Riwayat Versi CV --}}
<div id="versionModalBackdrop" class="fixed inset-0 z-50 bg-brand-ink/50 backdrop-blur-xs hidden grid place-items-center p-4" onclick="if (event.target === this) openVersionModal(false)">
    <div class="bg-white rounded-card max-w-xl w-full p-6 space-y-4 shadow-softpill border border-brand-ink/10" role="dialog" aria-modal="true" aria-labelledby="versionModalTitle">
        <div class="flex items-center justify-between">
            <h3 id="versionModalTitle" class="font-display text-lg uppercase tracking-wide text-brand-ink">Riwayat Versi CV</h3>
            <button onclick="openVersionModal(false)" class="w-8 h-8 rounded-full grid place-items-center hover:bg-brand-ink/5 text-brand-ink/60 cursor-pointer" aria-label="Tutup">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <p class="text-xs text-brand-ink/60">
            Hanya versi resmi yang disimpan lewat tombol <b class="text-brand-ink">"Simpan CV"</b> yang tercatat di sini. Draf sementara saat analisis AI tidak mengotori riwayatmu.
        </p>

        <ul class="max-h-72 overflow-y-auto">
            @forelse($cvVersions ?? [] as $idx => $v)
                <li class="py-3 flex items-center justify-between gap-3 border-t border-brand-ink/10 first:border-t-0">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <span class="font-bold text-xs text-brand-ink">Versi #{{ $v->id }}</span>
                            @if($v->is_main)
                                <span class="px-2 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Utama</span>
                            @endif
                            @if($v->score !== null)
                                <span class="px-2 rounded-full text-[10px] font-semibold bg-brand-ink/5 text-brand-ink">Skor {{ $v->score }}</span>
                            @elseif(is_array($v->feedback) && isset($v->feedback['total_score']))
                                <span class="px-2 rounded-full text-[10px] font-semibold bg-brand-ink/5 text-brand-ink">Skor {{ $v->feedback['total_score'] }}</span>
                            @endif
                            @if($activeCv?->id === $v->id)
                                <span class="px-2 rounded-full text-[10px] font-semibold bg-brand-darkred/10 text-brand-darkred">Sedang Dibuka</span>
                            @endif
                        </div>
                        <div class="mt-0.5 text-[11px] text-brand-ink/50">
                            {{ $v->created_at ? $v->created_at->format('d M Y H:i') : '-' }}
                            @if($v->lamarans_count)
                                · dipakai di {{ $v->lamarans_count }} lamaran
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        @if($activeCv?->id !== $v->id)
                            <a href="{{ $v->is_main ? route('bkk.me.cv.edit') : route('bkk.me.cv.editVersion', $v->id) }}" class="h-8 px-3 rounded-full border border-brand-ink/15 bg-white hover:bg-brand-softmist text-brand-ink text-[11px] font-semibold transition inline-flex items-center">
                                Buka
                            </a>
                        @endif
                        <button type="button" onclick="loadVersionContent({{ $v->id }})" class="h-8 px-3 rounded-full bg-navy hover:bg-navy-light text-white text-[11px] font-semibold transition cursor-pointer">
                            Muat Teks
                        </button>
                        @unless($v->is_main)
                            @include('me.partials.cv-delete-button', ['version' => $v, 'compact' => true])
                        @endunless
                    </div>
                </li>
            @empty
                <li class="p-5 text-center text-xs text-brand-ink/55">Belum ada riwayat versi sebelumnya.</li>
            @endforelse
        </ul>

        <div class="flex justify-end pt-1">
            <button onclick="openVersionModal(false)" class="h-9 px-5 rounded-full border border-brand-ink/15 bg-white hover:bg-brand-softmist text-brand-ink text-xs font-semibold cursor-pointer">
                Tutup
            </button>
        </div>
    </div>
</div>

{{-- Laci Penus AI (layar kecil & mode layar penuh) --}}
<div id="mobileAiBackdrop" class="fixed inset-0 z-[55] bg-brand-ink/50 backdrop-blur-xs hidden" onclick="toggleMobileAi(false)"></div>
<div id="mobileAiDrawer" class="fixed top-0 right-0 bottom-0 z-[60] w-full max-w-md bg-white shadow-2xl transform translate-x-full transition-transform duration-300 flex flex-col" aria-label="Panel Penus AI">
    <div class="px-4 py-3 border-b border-brand-ink/10 flex items-center justify-between shrink-0">
        <span class="font-display text-base uppercase tracking-wide text-brand-ink">Panel Penus AI</span>
        <button onclick="toggleMobileAi(false)" class="w-8 h-8 rounded-full grid place-items-center hover:bg-brand-ink/5 text-brand-ink/60 cursor-pointer" aria-label="Tutup panel">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
    <div id="mobileAiContent" class="flex-1 flex flex-col min-h-0 overflow-hidden"></div>
</div>
@endsection

@push('scripts')
<script>
    const isSiswa = @json($isSiswa);
    const userJurusan = @json($userJurusan);
    const sectionTemplateLabels = @json($sectionTemplates);
    // Mode "Versi Baru": belum ada versi tersimpan untuk templat ini, jadi isinya selalu dianggap belum disimpan
    const isNewVersion = @json($isNewVersion);
    let activeCvId = isNewVersion ? null : @json($activeCv?->id ?? null);
    let lastSavedMarkdown = isNewVersion ? '' : @json($cvMarkdown ?? '');
    let lastAnalyzedDraftId = null;
    let lastAnalyzedMarkdown = null;
    let isFullscreenMode = false;

    // State untuk /api/ai/cv/suggestions & /api/ai/cv/chat
    let currentSuggestions = [];
    let chatMessages = [];
    let isChatStreaming = false;

    // Draf lokal (hanya kenyamanan per-browser, sumber kebenaran tetap versi tersimpan di server)
    const draftKey = `bkk-cv-draft-${activeCvId ?? 'new'}`;
    const viewKey = 'bkk-cv-editor-view';

    function storageGet(key) {
        try { return window.localStorage.getItem(key); } catch (_) { return null; }
    }
    function storageSet(key, value) {
        try { window.localStorage.setItem(key, value); } catch (_) {}
    }
    function storageRemove(key) {
        try { window.localStorage.removeItem(key); } catch (_) {}
    }

    // ------------------------------------------------------------
    // 1. Inisialisasi
    // ------------------------------------------------------------
    document.addEventListener('DOMContentLoaded', () => {
        const raw = getActiveMarkdown();
        refreshDerived(raw);
        updateDirtyIndicator(raw);
        offerLocalDraft();

        const savedView = storageGet(viewKey);
        setViewMode(savedView || (window.matchMedia('(min-width: 1024px)').matches ? 'split' : 'write'), false);

        lucide.createIcons();

        document.addEventListener('click', function (e) {
            const chipBtn = e.target.closest('[data-chat-prompt]');
            if (chipBtn) {
                e.preventDefault();
                const prompt = chipBtn.getAttribute('data-chat-prompt') || '';
                if (prompt) sendChatPrompt(prompt);
            }
        });
    });

    @php
        $clientToken = request()->attributes->get('auth_token')
            ?: (request()->bearerToken()
            ?: (request()->cookie('access_token')
            ?: ''));
    @endphp
    const clientToken = @json($clientToken);

    function getCsrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    }

    function buildApiHeaders(accept = 'application/json') {
        const reqId = 'req-' + (crypto.randomUUID ? crypto.randomUUID() : Date.now());
        const headers = {
            'Content-Type': 'application/json',
            'Accept': accept,
            'X-CSRF-TOKEN': getCsrfToken(),
            'X-Request-ID': reqId,
            'X-App-Client': 'bkk-web-cv-editor/1.0'
        };
        if (clientToken) {
            headers['Authorization'] = 'Bearer ' + clientToken;
        }
        return headers;
    }

    // ------------------------------------------------------------
    // 2. Editor: input, pratinjau, statistik, status simpan
    // ------------------------------------------------------------
    let derivedTimer = null;
    let draftTimer = null;

    function onRawMarkdownInput() {
        const md = getActiveMarkdown();
        updateDirtyIndicator(md);

        // Pratinjau & analisis lokal ditunda sedikit supaya mengetik tetap ringan
        clearTimeout(derivedTimer);
        derivedTimer = setTimeout(() => refreshDerived(md), 180);

        clearTimeout(draftTimer);
        draftTimer = setTimeout(() => saveLocalDraft(md), 800);
    }

    function refreshDerived(md) {
        updateAtsLivePreview(md);
        updateStats(md);
        runLiveChecks(md);
    }

    function isDirty(md) {
        return md.trim() !== (lastSavedMarkdown || '').trim();
    }

    function updateDirtyIndicator(md) {
        const dirty = isDirty(md);
        const badge = document.getElementById('draftDirtyBadge');
        if (badge) {
            badge.classList.toggle('hidden', !dirty);
            badge.classList.toggle('inline-flex', dirty);
        }

        const stale = document.getElementById('staleScoreNote');
        if (stale) {
            stale.classList.toggle('hidden', !dirty);
            stale.classList.toggle('flex', dirty);
        }

        setSaveStatus(dirty ? 'dirty' : 'saved');
    }

    function setSaveStatus(state) {
        const el = document.getElementById('saveStatusText');
        if (!el) return;
        const states = {
            saved: ['text-emerald-700', 'cloud-check', 'Tersimpan'],
            dirty: ['text-amber-700', 'circle-dot', 'Belum disimpan'],
            saving: ['text-brand-ink/60', 'loader', 'Menyimpan…'],
        };
        const [tone, icon, label] = states[state] || states.saved;
        el.className = `inline-flex items-center gap-1.5 font-semibold ${tone}`;
        el.innerHTML = `<i data-lucide="${icon}" class="w-3.5 h-3.5 ${state === 'saving' ? 'animate-spin' : ''}"></i>${label}`;
        lucide.createIcons({ root: el });
    }

    function updateAtsLivePreview(markdownText) {
        const container = document.getElementById('livePreviewContainer');
        if (!container) return;
        if (!markdownText || !markdownText.trim()) {
            container.innerHTML = '<div class="text-center py-20 text-brand-ink/50 italic text-xs">Mulai ketik isi CV di editor untuk melihat pratinjau lembar ATS secara langsung.</div>';
            return;
        }
        const parsed = marked.parse(markdownText);
        container.innerHTML = typeof DOMPurify !== 'undefined' ? DOMPurify.sanitize(parsed) : parsed;
    }

    function countWords(text) {
        return (text.replace(/[#*_>\-\[\]()`|]/g, ' ').match(/[\p{L}\p{N}]+/gu) || []).length;
    }

    function updateStats(text) {
        const set = (id, value) => { const el = document.getElementById(id); if (el) el.innerText = value; };
        set('wordCount', `${countWords(text)} kata`);
        set('charCount', `${text.length} karakter`);
        set('lineCount', `${text.split('\n').length} baris`);
    }

    function getTextarea() {
        return document.getElementById('rawMarkdownTextarea');
    }

    function getActiveMarkdown() {
        const textarea = getTextarea();
        return textarea ? textarea.value : '';
    }

    function setContent(markdownText) {
        const textarea = getTextarea();
        if (textarea) textarea.value = markdownText;
        refreshDerived(markdownText);
        updateDirtyIndicator(markdownText);
        saveLocalDraft(markdownText);
    }

    // Pilih teks di editor lalu gulir ke barisnya, mis. setelah patch AI diterapkan atau klik kerangka
    function selectRange(start, end) {
        const textarea = getTextarea();
        if (!textarea) return;
        if (document.getElementById('editorColumnsWrapper')?.dataset.view === 'preview') setViewMode('split');
        textarea.focus({ preventScroll: true });
        textarea.setSelectionRange(start, end);
        const line = textarea.value.slice(0, start).split('\n').length - 1;
        const lineHeight = parseFloat(getComputedStyle(textarea).lineHeight) || 20;
        textarea.scrollTop = Math.max(0, line * lineHeight - textarea.clientHeight / 3);
    }

    function handleEditorKeydown(e) {
        const mod = e.ctrlKey || e.metaKey;
        if (e.key === 'Tab') {
            e.preventDefault();
            const textarea = e.target;
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            textarea.value = textarea.value.substring(0, start) + "  " + textarea.value.substring(end);
            textarea.selectionStart = textarea.selectionEnd = start + 2;
            onRawMarkdownInput();
        } else if (mod && e.key.toLowerCase() === 'b') {
            e.preventDefault();
            wrapSelection('**', '**', 'teks tebal');
        } else if (mod && e.key.toLowerCase() === 'i') {
            e.preventDefault();
            wrapSelection('*', '*', 'teks miring');
        }
    }

    // Ctrl+S menyimpan dari mana saja di halaman ini
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 's') {
            e.preventDefault();
            saveCv();
        }
        if (e.key === 'Escape') {
            if (isFullscreenMode) toggleFullscreen();
            openVersionModal(false);
        }
    });

    window.addEventListener('beforeunload', (e) => {
        if (isDirty(getActiveMarkdown())) {
            e.preventDefault();
            e.returnValue = '';
        }
    });

    // ------------------------------------------------------------
    // 3. Toolbar format Markdown & templat bagian (dipakai tombol "Tambah" di Cek Langsung)
    // ------------------------------------------------------------
    function wrapSelection(before, after, placeholder) {
        const textarea = getTextarea();
        if (!textarea) return;
        const { selectionStart: start, selectionEnd: end, value } = textarea;
        const selected = value.slice(start, end) || placeholder;
        textarea.value = value.slice(0, start) + before + selected + after + value.slice(end);
        textarea.focus();
        textarea.setSelectionRange(start + before.length, start + before.length + selected.length);
        onRawMarkdownInput();
    }

    function prefixLines(prefix) {
        const textarea = getTextarea();
        if (!textarea) return;
        const { selectionStart, selectionEnd, value } = textarea;
        const lineStart = value.lastIndexOf('\n', selectionStart - 1) + 1;
        const lineEndIdx = value.indexOf('\n', selectionEnd);
        const lineEnd = lineEndIdx === -1 ? value.length : lineEndIdx;
        const block = value.slice(lineStart, lineEnd);
        // Ganti penanda judul/butir yang sudah ada supaya tombol bisa dipakai untuk mengubah jenis baris
        const updated = block.split('\n').map((line) => prefix + line.replace(/^(#{1,6}\s+|[-*]\s+)/, '')).join('\n');
        textarea.value = value.slice(0, lineStart) + updated + value.slice(lineEnd);
        textarea.focus();
        textarea.setSelectionRange(lineStart, lineStart + updated.length);
        onRawMarkdownInput();
    }

    const sectionTemplates = {
        summary: `## Ringkasan Profesional\nSiswa jurusan ${userJurusan} yang [sebutkan keunggulan utama]. Berpengalaman [jumlah] bulan PKL di [perusahaan] dan siap berkontribusi di bidang [posisi tujuan].`,
        education: `## Pendidikan\n**SMK Plus Pelita Nusantara** — ${userJurusan}\n*2023 - 2026* | Nilai rata-rata: [nilai]`,
        experience: `## Pengalaman PKL & Kerja\n**[Nama Perusahaan]** — [Posisi]\n*[Bulan Tahun] - [Bulan Tahun]* | [Kota]\n- [Kata kerja aktif] + [apa yang dikerjakan] sehingga [hasil terukur, mis. 30% lebih cepat]\n- [Capaian kedua dengan angka]`,
        skills: `## Keahlian Teknis\n- **[Kategori]**: [keahlian 1], [keahlian 2], [keahlian 3]\n- **Tools**: [tool 1], [tool 2]`,
        projects: `## Proyek & Portofolio\n**[Nama Proyek]** — [teknologi yang dipakai]\n- [Masalah yang diselesaikan & hasilnya]\n- Tautan: [https://...]`,
        certifications: `## Sertifikasi\n- **[Nama Sertifikat]** — [Penerbit], [Tahun]`,
    };

    function insertSection(key) {
        const template = sectionTemplates[key];
        if (!template) return;
        const current = getActiveMarkdown().replace(/\s+$/, '');
        const start = current.length ? current.length + 2 : 0;
        setContent((current ? current + '\n\n' : '') + template + '\n');
        selectRange(start, start + template.length);
        showToast(`Bagian "${sectionTemplateLabels[key] || key}" ditambahkan. Ganti teks dalam [kurung] dengan datamu.`);
    }

    // ------------------------------------------------------------
    // 4. Analisis lokal: cek ATS (tanpa memanggil AI)
    // ------------------------------------------------------------
    function headings(md) {
        return md.split('\n')
            .map((line, index) => ({ line, index }))
            .filter(({ line }) => /^#{1,3}\s+\S/.test(line));
    }

    function hasSection(md, pattern) {
        return headings(md).some(({ line }) => pattern.test(line));
    }

    const liveChecks = [
        { id: 'name', label: 'Nama lengkap sebagai judul utama (#)', test: (md) => /^#\s+\S/m.test(md), hint: 'Baris pertama: # Nama Lengkap' },
        { id: 'email', label: 'Email aktif tercantum', test: (md) => /[^\s@()<>]+@[^\s@()<>]+\.[a-z]{2,}/i.test(md), hint: 'Tulis email di bawah nama' },
        { id: 'phone', label: 'Nomor telepon / WhatsApp', test: (md) => /(\+62|62|0)8[\d\s.-]{7,}/.test(md), hint: 'Contoh: 0812-3456-7890' },
        { id: 'summary', label: 'Bagian Ringkasan Profesional', test: (md) => hasSection(md, /ringkasan|profil|summary|tentang/i), fix: 'summary' },
        { id: 'education', label: 'Bagian Pendidikan', test: (md) => hasSection(md, /pendidikan|education/i), fix: 'education' },
        { id: 'experience', label: 'Bagian Pengalaman / PKL', test: (md) => hasSection(md, /pengalaman|pkl|magang|experience|kerja/i), fix: 'experience' },
        { id: 'skills', label: 'Bagian Keahlian', test: (md) => hasSection(md, /keahlian|skill|kompetensi/i), fix: 'skills' },
        { id: 'projects', label: 'Bagian Proyek / Portofolio', test: (md) => hasSection(md, /proyek|portofolio|portfolio|project/i), fix: 'projects' },
        { id: 'certifications', label: 'Bagian Sertifikasi', test: (md) => hasSection(md, /sertifikat|sertifikasi|certif/i), fix: 'certifications' },
        {
            id: 'metrics',
            label: 'Minimal 2 butir memakai angka capaian',
            test: (md) => md.split('\n').filter((line) => /^\s*[-*]\s/.test(line) && /\d/.test(line)).length >= 2,
            hint: 'Mis. "memangkas waktu rekap 40%"',
        },
        {
            id: 'length',
            label: 'Panjang ideal 150–900 kata',
            test: (md) => { const words = countWords(md); return words >= 150 && words <= 900; },
            hint: () => `Sekarang ${countWords(getActiveMarkdown())} kata`,
        },
        { id: 'safe', label: 'Tanpa tabel & gambar (aman dibaca ATS)', test: (md) => !/^\s*\|.*\|\s*$/m.test(md) && !/!\[[^\]]*\]\(/.test(md), hint: 'Ubah tabel menjadi butir daftar' },
    ];

    function runLiveChecks(md) {
        const list = document.getElementById('liveChecksList');
        if (!list) return;

        const results = liveChecks.map((check) => ({ ...check, ok: !!check.test(md) }));
        const passed = results.filter((r) => r.ok).length;

        const summary = document.getElementById('liveCheckSummary');
        if (summary) summary.innerText = `${passed}/${results.length} lolos`;
        const bar = document.getElementById('liveCheckBar');
        if (bar) {
            const pct = Math.round(passed / results.length * 100);
            bar.style.width = `${pct}%`;
            // Hijau >= 85%, biru 70-84%, merah < 70%, sama dengan bar skor
            bar.className = `h-full rounded-full transition-all duration-300 ${pct >= 85 ? 'bg-emerald-500' : (pct >= 70 ? 'bg-navy' : 'bg-brand-darkred')}`;
        }

        // Yang belum lolos ditaruh di atas supaya langsung terlihat apa yang perlu dikerjakan
        results.sort((a, b) => Number(a.ok) - Number(b.ok));

        list.innerHTML = results.map((r) => {
            const hint = typeof r.hint === 'function' ? r.hint() : r.hint;
            return `
                <li class="flex items-start gap-2.5 py-2.5 border-t border-brand-ink/10 first:border-t-0">
                    <span class="mt-px w-5 h-5 rounded-full grid place-items-center shrink-0 ${r.ok ? 'bg-emerald-100 text-emerald-700' : 'bg-brand-darkred/10 text-brand-darkred'}">
                        <i data-lucide="${r.ok ? 'check' : 'x'}" class="w-3 h-3"></i>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold ${r.ok ? 'text-brand-ink/55' : 'text-brand-ink'}">${escapeHtml(r.label)}</span>
                        ${!r.ok && hint ? `<span class="block text-[11px] text-brand-ink/50 mt-0.5">${escapeHtml(hint)}</span>` : ''}
                    </span>
                    ${!r.ok && r.fix ? `
                        <button type="button" onclick="insertSection('${r.fix}')" class="shrink-0 h-7 px-2.5 rounded-full border border-brand-darkred/25 text-brand-darkred hover:bg-brand-darkred hover:text-white text-[11px] font-bold inline-flex items-center gap-1 transition-colors cursor-pointer">
                            <i data-lucide="plus" class="w-3 h-3"></i>Tambah
                        </button>
                    ` : ''}
                </li>
            `;
        }).join('');
        lucide.createIcons({ root: list });
    }

    // ------------------------------------------------------------
    // 5. Mode tampilan & tab panel AI
    // ------------------------------------------------------------
    // Tab aktif = pill navy (biru), sama dengan tombol aktif lain di dashboard
    function setTabState(btn, active) {
        btn.classList.toggle('bg-navy', active);
        btn.classList.toggle('text-white', active);
        btn.classList.toggle('text-brand-ink/60', !active);
        btn.classList.toggle('hover:bg-brand-ink/5', !active);
        btn.setAttribute('aria-selected', String(active));
    }

    function setViewMode(mode, remember = true) {
        // "Belah" hanya masuk akal di layar lebar
        if (mode === 'split' && !window.matchMedia('(min-width: 1024px)').matches) mode = 'write';
        const wrapper = document.getElementById('editorColumnsWrapper');
        if (wrapper) wrapper.dataset.view = mode;
        document.querySelectorAll('[data-view-btn]').forEach((btn) => setTabState(btn, btn.dataset.viewBtn === mode));
        if (remember) storageSet(viewKey, mode);
    }

    function switchAiTab(tab) {
        const tabs = { analysis: ['tabBtnAnalysis', 'aiTabAnalysis'], suggestions: ['tabBtnSuggestions', 'aiTabSuggestions'], chat: ['tabBtnChat', 'aiTabChat'] };
        Object.entries(tabs).forEach(([key, [btnId, panelId]]) => {
            const active = key === tab;
            const btn = document.getElementById(btnId);
            const panel = document.getElementById(panelId);
            panel?.classList.toggle('hidden', !active);
            if (btn) setTabState(btn, active);
        });
        if (tab === 'chat') setTimeout(() => document.getElementById('aiChatInput')?.focus(), 50);
    }

    // ------------------------------------------------------------
    // 6. Draf sementara (temp = true) sebelum memanggil /api/ai/cv/suggestions
    // ------------------------------------------------------------
    async function ensureCvIdForAnalysis() {
        const md = getActiveMarkdown();
        if (!md.trim() || md.trim().length < 20) {
            throw new Error('Konten CV tidak boleh kosong atau kurang dari 20 karakter.');
        }

        // Jika konten identik dengan versi resmi terakhir yang tersimpan dan activeCvId tersedia, gunakan langsung
        if (activeCvId && md.trim() === (lastSavedMarkdown || '').trim()) {
            return activeCvId;
        }

        // Jika konten identik dengan draf sementara yang baru saja di-upsert, gunakan ID draf tersebut
        if (lastAnalyzedDraftId && md.trim() === (lastAnalyzedMarkdown || '').trim()) {
            return lastAnalyzedDraftId;
        }

        // Simpan sebagai draf sementara (temp = true) tanpa menambah riwayat versi utama
        const res = await fetch("/bkk/me/cv", {
            method: 'POST',
            headers: buildApiHeaders(),
            credentials: 'include',
            body: JSON.stringify({
                raw_markdown: md,
                temp: true
            })
        });

        const json = await res.json();
        if (!res.ok || !json.success || !json.data?.id) {
            throw new Error(json.message || 'Gagal menyiapkan draf sementara CV.');
        }

        lastAnalyzedDraftId = json.data.id;
        lastAnalyzedMarkdown = md;
        return json.data.id;
    }

    // ------------------------------------------------------------
    // 7. Saran AI berprioritas (POST /api/ai/cv/suggestions)
    // ------------------------------------------------------------
    async function runAiSuggestions() {
        const btn = document.getElementById('btnFetchSuggestions');
        const loadingBox = document.getElementById('aiSuggestionsLoading');
        const listBox = document.getElementById('aiSuggestionsList');

        if (btn) btn.disabled = true;
        if (loadingBox) loadingBox.classList.remove('hidden');
        if (listBox) listBox.classList.add('hidden');

        try {
            const targetCvId = await ensureCvIdForAnalysis();

            const res = await fetch('/api/ai/cv/suggestions', {
                method: 'POST',
                headers: buildApiHeaders(),
                credentials: 'include',
                body: JSON.stringify({
                    cv_id: targetCvId
                })
            });

            const json = await res.json();
            if (!res.ok || !json.success) {
                throw new Error(json.message || 'Gagal memuat saran perbaikan AI.');
            }

            currentSuggestions = json.data?.suggestions || [];
            renderAiSuggestions(currentSuggestions);
            showToast(`Berhasil memuat ${currentSuggestions.length} rekomendasi perbaikan AI.`);
        } catch (err) {
            showToast(err.message || 'Terjadi kesalahan saat menghubungi layanan AI.');
        } finally {
            if (btn) btn.disabled = false;
            if (loadingBox) loadingBox.classList.add('hidden');
            if (listBox) listBox.classList.remove('hidden');
        }
    }

    function updateSuggestionsBadge() {
        const countBadge = document.getElementById('suggestionsCountBadge');
        if (!countBadge) return;
        const open = currentSuggestions.filter((s) => s && !s._done).length;
        countBadge.innerText = open;
        countBadge.classList.toggle('hidden', open === 0);
        countBadge.classList.toggle('grid', open > 0);
    }

    function renderAiSuggestions(suggestions) {
        const listBox = document.getElementById('aiSuggestionsList');
        if (!listBox) return;
        updateSuggestionsBadge();

        if (!suggestions.length) {
            listBox.innerHTML = `
                <div class="rounded-card border border-emerald-200 bg-emerald-50/60 p-4 text-center text-emerald-900 text-xs">
                    CV-mu sudah sangat optimal! Tidak ada saran perbaikan mendesak saat ini.
                </div>
            `;
            return;
        }

        listBox.innerHTML = suggestions.map((s, idx) => {
            const impactClass = s.impact === 'Tinggi'
                ? 'bg-brand-darkred text-white'
                : (s.impact === 'Sedang' ? 'bg-amber-100 text-amber-800' : 'bg-brand-ink/10 text-brand-ink');

            const targetPattern = s.patch?.target_pattern || '';
            const replacement = s.patch?.replacement || '';

            return `
                <div id="editor-sug-card-${idx}" class="rounded-card border border-brand-ink/10 bg-white p-3.5 space-y-2.5 hover:border-brand-darkred/25 transition">
                    <div class="flex flex-wrap items-center justify-between gap-1.5">
                        <div class="flex items-center gap-1.5">
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase ${impactClass}">
                                Dampak ${escapeHtml(s.impact || 'Tinggi')}
                            </span>
                            <span class="text-[10px] font-semibold text-brand-ink/50">· ${escapeHtml(s.section || 'Umum')}</span>
                        </div>
                        <span class="font-mono text-[9px] px-1.5 py-0.5 rounded bg-brand-ink/5 text-brand-ink/50">${escapeHtml(s.category || 'ATS_PATCH')}</span>
                    </div>

                    <div>
                        <h4 class="font-bold text-xs text-brand-ink leading-snug">${escapeHtml(s.title)}</h4>
                        <p class="text-[11px] text-brand-ink/60 mt-1 leading-relaxed">${escapeHtml(s.desc)}</p>
                    </div>

                    ${targetPattern || replacement ? `
                        <div class="rounded-xl bg-brand-paper border border-brand-ink/10 p-2.5 space-y-1.5 font-mono text-[10px]">
                            ${targetPattern ? `
                                <div class="text-red-700 bg-red-50 px-2 py-1 rounded border border-red-100 line-through break-words">
                                    - ${escapeHtml(targetPattern)}
                                </div>
                            ` : ''}
                            ${replacement ? `
                                <div class="text-emerald-800 bg-emerald-50/80 px-2 py-1 rounded border border-emerald-100 break-words whitespace-pre-wrap">+ ${escapeHtml(replacement)}</div>
                            ` : ''}
                        </div>
                    ` : ''}

                    <div class="flex items-center justify-end gap-1.5 pt-1">
                        <button type="button" onclick="dismissSuggestion(${idx})" class="h-8 px-3 rounded-full text-brand-ink/60 hover:bg-brand-ink/5 hover:text-brand-ink text-[11px] font-semibold transition cursor-pointer">
                            Abaikan
                        </button>
                        <button type="button" id="btn-apply-sug-${idx}" onclick="applySuggestionByIndex(${idx})" class="h-8 px-3.5 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-semibold inline-flex items-center gap-1.5 transition cursor-pointer">
                            <i data-lucide="check" class="w-3 h-3"></i>
                            <span>Terapkan Patch</span>
                        </button>
                    </div>
                </div>
            `;
        }).join('');

        lucide.createIcons({ root: listBox });
    }

    function dismissSuggestion(idx) {
        const card = document.getElementById(`editor-sug-card-${idx}`);
        if (currentSuggestions[idx]) currentSuggestions[idx]._done = true;
        card?.classList.add('opacity-50');
        card?.querySelectorAll('button').forEach((b) => { b.disabled = true; });
        updateSuggestionsBadge();
    }

    function applySuggestionByIndex(idx) {
        const sug = currentSuggestions[idx];
        if (!sug || !sug.patch) return;

        const applied = applyAiPatch(sug.patch.target_pattern, sug.patch.replacement);
        if (applied) {
            sug._done = true;
            updateSuggestionsBadge();
            const btn = document.getElementById(`btn-apply-sug-${idx}`);
            if (btn) {
                btn.disabled = true;
                btn.className = "h-8 px-3.5 rounded-full bg-emerald-100 text-emerald-800 text-[11px] font-semibold inline-flex items-center gap-1.5 cursor-not-allowed";
                btn.innerHTML = `<i data-lucide="check-check" class="w-3 h-3"></i><span>Diterapkan</span>`;
                lucide.createIcons({ root: btn });
            }
        }
    }

    /**
     * Implementasi applyAiPatch sesuai Section 6.2 AI_CONTRACT.md.
     * Setelah diterapkan, teks baru diseleksi di editor supaya siswa langsung melihat perubahannya.
     */
    function applyAiPatch(targetPattern, replacement) {
        const textarea = getTextarea();
        if (!textarea) return false;
        const currentContent = textarea.value;

        // Coba persis dulu, lalu versi tanpa spasi di tepi (toleran jika baris di editor sedikit berubah)
        for (const candidate of [targetPattern, (targetPattern || '').trim()]) {
            if (!candidate) continue;
            const at = currentContent.indexOf(candidate);
            if (at === -1) continue;
            setContent(currentContent.slice(0, at) + replacement + currentContent.slice(at + candidate.length));
            selectRange(at, at + (replacement || '').length);
            showToast('Perbaikan AI berhasil diterapkan ke editor CV!');
            return true;
        }

        showToast('Teks asli tidak ditemukan persis di dokumen. Pastikan bagian terkait belum dihapus.');
        return false;
    }

    // ------------------------------------------------------------
    // 8. Tanya AI (POST /api/ai/cv/chat, mode JSON)
    // ------------------------------------------------------------
    let chatActionRegistry = [];

    function extractChatData(resJson) {
        let raw = resJson?.data || resJson;
        let reply = '';
        let actions = [];
        let quickChips = [];

        if (typeof raw === 'string') {
            try {
                raw = JSON.parse(raw);
            } catch (_) {}
        }

        if (raw && typeof raw === 'object') {
            reply = raw.reply || '';
            actions = Array.isArray(raw.suggested_actions) ? raw.suggested_actions : [];
            quickChips = Array.isArray(raw.quick_chips) ? raw.quick_chips : [];

            // Robust parsing jika reply berupa JSON string atau markdown json block
            if (typeof reply === 'string') {
                let trimmed = reply.trim();
                if (trimmed.startsWith('```json') && trimmed.endsWith('```')) {
                    trimmed = trimmed.replace(/^```json\s*/, '').replace(/\s*```$/, '').trim();
                }
                if (trimmed.startsWith('{') && trimmed.endsWith('}')) {
                    try {
                        const parsedInside = JSON.parse(trimmed);
                        if (parsedInside && typeof parsedInside === 'object') {
                            if (parsedInside.reply) reply = parsedInside.reply;
                            if (Array.isArray(parsedInside.suggested_actions) && !actions.length) {
                                actions = parsedInside.suggested_actions;
                            }
                            if (Array.isArray(parsedInside.quick_chips) && !quickChips.length) {
                                quickChips = parsedInside.quick_chips;
                            }
                        }
                    } catch (_) {}
                }
            }
        } else {
            reply = String(raw || 'Berikut saran untuk CV Anda.');
        }

        return { reply, actions, quickChips };
    }

    function renderAiChatResponseElement(bubbleEl, chatData) {
        if (!bubbleEl) return;

        const { reply, actions, quickChips } = chatData;

        // 1. Markdown prose balasan AI
        const parsedReply = marked.parse(reply || 'Berikut saran untuk CV Anda.');
        const safeReply = typeof DOMPurify !== 'undefined' ? DOMPurify.sanitize(parsedReply) : parsedReply;
        let html = `<div class="prose prose-xs max-w-none text-brand-ink leading-relaxed">${safeReply}</div>`;

        // 2. Kartu aksi terstruktur (APPLY_DIFF / PROMPT) langsung di dalam bubble
        if (Array.isArray(actions) && actions.length > 0) {
            html += `
                <div class="mt-3 pt-2.5 border-t border-brand-ink/10 space-y-2">
                    <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-brand-ink/50">
                        <i data-lucide="sparkles" class="w-3 h-3 text-brand-darkred"></i>
                        <span>Tindakan Disarankan AI:</span>
                    </div>
                    <div class="flex flex-col gap-2">
            `;

            actions.forEach(act => {
                const actionId = chatActionRegistry.length;
                chatActionRegistry.push(act);

                if (act.action_type === 'APPLY_DIFF') {
                    const before = act.payload?.before || '';
                    const after = act.payload?.after || '';
                    html += `
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50/70 p-2.5 space-y-2 text-[11px] transition">
                            <div class="flex items-center justify-between gap-1.5">
                                <span class="font-bold text-emerald-950 flex items-center gap-1.5 truncate">
                                    <i data-lucide="wand-2" class="w-3.5 h-3.5 text-emerald-600 shrink-0"></i>
                                    <span class="truncate">${escapeHtml(act.label || 'Terapkan Rekomendasi')}</span>
                                </span>
                                <span class="text-[9px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-emerald-200/70 text-emerald-800 shrink-0">Patch CV</span>
                            </div>
                            ${(before || after) ? `
                                <div class="space-y-1 font-mono text-[10px] bg-white/90 rounded-lg p-2 border border-emerald-100/80 leading-relaxed overflow-x-auto">
                                    ${before ? `
                                        <div class="text-red-700 bg-red-50 px-1.5 py-0.5 rounded">
                                            <span class="font-bold">- Sebelum:</span> ${escapeHtml(before)}
                                        </div>
                                    ` : ''}
                                    ${after ? `
                                        <div class="text-emerald-700 bg-emerald-50/80 px-1.5 py-0.5 rounded font-medium">
                                            <span class="font-bold">+ Sesudah:</span> ${escapeHtml(after)}
                                        </div>
                                    ` : ''}
                                </div>
                            ` : ''}
                            <button type="button" onclick="executeInlineChatAction(${actionId}, this)" class="w-full h-8 rounded-full bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white font-semibold text-[11px] inline-flex items-center justify-center gap-1.5 transition cursor-pointer">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                <span>Terapkan ke Editor CV</span>
                            </button>
                        </div>
                    `;
                } else if (act.action_type === 'PROMPT') {
                    const label = act.label || act.payload || 'Tanyakan ke AI';
                    html += `
                        <button type="button" onclick="executeInlineChatAction(${actionId}, this)" class="w-full text-left px-3 py-2 rounded-xl border border-brand-ink/10 bg-white hover:bg-navy hover:text-white text-brand-ink text-[11px] font-medium transition flex items-center justify-between gap-2 cursor-pointer group">
                            <span class="flex items-center gap-2 truncate">
                                <i data-lucide="message-square-plus" class="w-3.5 h-3.5 text-brand-darkred group-hover:text-white shrink-0 transition"></i>
                                <span class="truncate">${escapeHtml(label)}</span>
                            </span>
                            <i data-lucide="arrow-right" class="w-3 h-3 text-brand-ink/40 group-hover:text-white shrink-0 transition"></i>
                        </button>
                    `;
                }
            });

            html += `
                    </div>
                </div>
            `;
        }

        // 3. Quick chips topik lanjutan di dalam bubble
        if (Array.isArray(quickChips) && quickChips.length > 0) {
            html += `
                <div class="mt-2.5 pt-2 border-t border-brand-ink/10 flex flex-wrap gap-1.5">
                    ${quickChips.map(chip => `
                        <button type="button" data-chat-prompt="${escapeHtml(chip)}" class="px-2.5 py-0.5 rounded-full bg-white border border-brand-ink/10 hover:bg-navy hover:text-white hover:border-navy text-[10px] font-medium text-brand-ink transition cursor-pointer">
                            ${escapeHtml(chip)}
                        </button>
                    `).join('')}
                </div>
            `;
        }

        bubbleEl.innerHTML = html;
        lucide.createIcons({ root: bubbleEl });
    }

    function applyChatDiff(act) {
        const beforeText = act.payload?.before || '';
        const afterText = act.payload?.after || '';
        const ok = applyAiPatch(beforeText, afterText);
        if (!ok && afterText) {
            // Jika teks contoh belum ada di dokumen, sisipkan di akhir dokumen
            const current = getActiveMarkdown();
            const start = current.trimEnd().length + 3;
            setContent(current.trimEnd() + `\n- ${afterText}\n`);
            selectRange(start, start + afterText.length);
            showToast('Rekomendasi kalimat AI berhasil ditambahkan ke CV!');
        }
    }

    function executeInlineChatAction(actionId, btnEl) {
        const act = chatActionRegistry[actionId];
        if (!act) return;

        if (act.action_type === 'APPLY_DIFF' && act.payload) {
            applyChatDiff(act);
            if (btnEl) {
                btnEl.disabled = true;
                btnEl.className = "w-full h-8 rounded-full bg-emerald-100 text-emerald-800 font-semibold text-[11px] inline-flex items-center justify-center gap-1.5 cursor-default transition";
                btnEl.innerHTML = `<i data-lucide="check-check" class="w-3.5 h-3.5 text-emerald-600"></i><span>Perubahan Berhasil Diterapkan</span>`;
                lucide.createIcons({ root: btnEl });
            }
        } else if (act.action_type === 'PROMPT' && typeof act.payload === 'string') {
            sendChatPrompt(act.payload);
        }
    }

    async function sendChatPrompt(promptText) {
        if (isChatStreaming) return;

        const input = document.getElementById('aiChatInput');
        const query = (promptText || input?.value || '').trim();
        if (!query) return;

        switchAiTab('chat');

        const chatContainer = document.getElementById('aiChatHistory');
        if (!chatContainer) return;

        chatMessages.push({ role: 'user', content: query });

        const userDiv = document.createElement('div');
        userDiv.className = "flex justify-end";
        userDiv.innerHTML = `<div class="text-xs rounded-2xl px-3 py-2 max-w-[85%] bg-navy text-white rounded-tr-sm leading-relaxed">${escapeHtml(query)}</div>`;
        chatContainer.appendChild(userDiv);

        if (input && !promptText) input.value = '';

        // Gelembung balasan AI dengan status "Berpikir..."
        const aiDiv = document.createElement('div');
        aiDiv.className = "flex gap-2 items-start";
        aiDiv.innerHTML = `
            <div class="w-7 h-7 rounded-full bg-brand-darkred grid place-items-center text-white shrink-0">
                <i data-lucide="bot" class="w-3.5 h-3.5"></i>
            </div>
            <div class="ai-chat-prose text-xs rounded-2xl px-3.5 py-2.5 max-w-[88%] bg-brand-paper text-brand-ink rounded-tl-sm leading-relaxed">
                <span class="inline-flex items-center gap-1.5 text-brand-ink/60 font-medium italic text-[11px]">
                    <svg class="animate-spin w-3 h-3 text-brand-darkred" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Berpikir...
                </span>
            </div>
        `;
        chatContainer.appendChild(aiDiv);
        lucide.createIcons({ root: aiDiv });
        chatContainer.scrollTop = chatContainer.scrollHeight;

        const bubbleEl = aiDiv.querySelector('.ai-chat-prose');
        isChatStreaming = true;
        const sendBtn = document.getElementById('btnSendChat');
        if (sendBtn) sendBtn.disabled = true;

        try {
            const res = await fetch('/api/ai/cv/chat', {
                method: 'POST',
                headers: buildApiHeaders('application/json'),
                credentials: 'include',
                body: JSON.stringify({
                    messages: chatMessages,
                    context: {
                        jurusan: userJurusan
                    },
                    stream: false
                })
            });

            if (!res.ok) {
                let errMsg = 'Gagal menghubungi asisten AI.';
                try {
                    const errJson = await res.json();
                    errMsg = errJson.message || errMsg;
                } catch (_) {}
                throw new Error(errMsg);
            }

            const json = await res.json();
            const chatData = extractChatData(json);

            renderAiChatResponseElement(bubbleEl, chatData);

            if (chatData.actions && chatData.actions.length) {
                renderChatSuggestedActions(chatData.actions);
            }
            if (chatData.quickChips && chatData.quickChips.length) {
                renderChatQuickChips(chatData.quickChips);
            }

            if (chatData.reply) {
                chatMessages.push({ role: 'assistant', content: chatData.reply });
            }
        } catch (err) {
            if (bubbleEl) {
                bubbleEl.innerHTML = `<span class="text-brand-darkred font-medium">${escapeHtml(err.message || 'Gagal memproses pesan AI.')}</span>`;
            }
        } finally {
            isChatStreaming = false;
            if (sendBtn) sendBtn.disabled = false;
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
    }

    let latestSuggestedActions = [];

    function renderChatSuggestedActions(actions) {
        const box = document.getElementById('aiSuggestedActionsBox');
        const list = document.getElementById('aiSuggestedActionsList');
        if (!box || !list) return;

        latestSuggestedActions = actions || [];
        if (!latestSuggestedActions.length) {
            box.classList.add('hidden');
            list.innerHTML = '';
            return;
        }

        box.classList.remove('hidden');
        list.innerHTML = latestSuggestedActions.map((act, i) => {
            const isDiff = act.action_type === 'APPLY_DIFF';
            const icon = isDiff ? 'wand-2' : 'message-square-plus';
            const btnStyle = isDiff
                ? 'border-emerald-200 bg-emerald-50 hover:bg-emerald-600 hover:text-white text-emerald-900'
                : 'border-brand-ink/10 bg-brand-paper hover:bg-navy hover:text-white text-brand-ink';

            return `
                <button type="button" onclick="triggerChatAction(${i})" class="w-full text-left px-3 py-1.5 rounded-xl border ${btnStyle} text-[11px] font-semibold transition flex items-center gap-2 cursor-pointer">
                    <i data-lucide="${icon}" class="w-3.5 h-3.5 shrink-0"></i>
                    <span class="truncate">${escapeHtml(act.label)}</span>
                </button>
            `;
        }).join('');

        lucide.createIcons({ root: list });
    }

    function triggerChatAction(index) {
        const act = latestSuggestedActions[index];
        if (!act) return;

        if (act.action_type === 'APPLY_DIFF' && act.payload) {
            applyChatDiff(act);
        } else if (act.action_type === 'PROMPT' && typeof act.payload === 'string') {
            sendChatPrompt(act.payload);
        }
    }

    function renderChatQuickChips(chips) {
        const container = document.getElementById('aiQuickChipsContainer');
        if (!container || !Array.isArray(chips) || !chips.length) return;

        container.innerHTML = chips.map(chip => `
            <button type="button" data-chat-prompt="${escapeHtml(chip)}" class="px-2.5 py-1 rounded-full border border-brand-ink/10 bg-white hover:bg-navy hover:text-white hover:border-navy text-[11px] font-medium text-brand-ink transition cursor-pointer">
                ${escapeHtml(chip)}
            </button>
        `).join('');
    }

    // ------------------------------------------------------------
    // 9. Layar penuh & laci AI (elemen panel dipindah, bukan diduplikasi)
    // ------------------------------------------------------------
    function toggleFullscreen() {
        isFullscreenMode = !isFullscreenMode;
        document.getElementById('cvEditorWrapper')?.classList.toggle('is-fullscreen', isFullscreenMode);

        const icon = document.getElementById('fullscreenIcon');
        const text = document.getElementById('fullscreenText');
        if (icon) icon.setAttribute('data-lucide', isFullscreenMode ? 'minimize-2' : 'maximize-2');
        if (text) text.innerText = isFullscreenMode ? 'Keluar Layar Penuh' : 'Layar Penuh';
        document.body.style.overflow = isFullscreenMode ? 'hidden' : '';
        if (!isFullscreenMode) toggleMobileAi(false);
        if (isFullscreenMode) showToast('Mode layar penuh aktif. Tekan Esc untuk keluar.');
        lucide.createIcons();
    }

    function toggleMobileAi(open) {
        const drawer = document.getElementById('mobileAiDrawer');
        const backdrop = document.getElementById('mobileAiBackdrop');
        const mobileContent = document.getElementById('mobileAiContent');
        const desktopPanel = document.getElementById('desktopAiPanel');
        const aiRoot = document.getElementById('aiAssistantRoot');

        if (open) {
            if (mobileContent && aiRoot && aiRoot.parentElement !== mobileContent) {
                mobileContent.appendChild(aiRoot);
            }
            backdrop?.classList.remove('hidden');
            drawer?.classList.remove('translate-x-full');
        } else {
            if (desktopPanel && aiRoot && aiRoot.parentElement !== desktopPanel) {
                desktopPanel.appendChild(aiRoot);
            }
            drawer?.classList.add('translate-x-full');
            backdrop?.classList.add('hidden');
        }
    }

    // ------------------------------------------------------------
    // 10. Versi CV & draf lokal
    // ------------------------------------------------------------
    function switchCvVersion(url) {
        if (!url) return;
        window.location.href = url;
    }

    function openVersionModal(open = true) {
        const modal = document.getElementById('versionModalBackdrop');
        if (modal) modal.classList.toggle('hidden', !open);
    }

    async function loadVersionContent(versionId) {
        if (!versionId) return;
        try {
            showToast('Memuat konten versi CV...');
            const res = await fetch(`/bkk/me/cv/${versionId}/edit`, {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'include'
            });
            const json = await res.json();
            if (!res.ok || !json.success || !json.data) {
                throw new Error(json.message || 'Gagal memuat konten versi CV.');
            }
            const markdown = json.data.raw_markdown || json.data.markdown || '';
            setContent(markdown);
            showToast('Konten versi terpilih berhasil dimuat ke editor.');
            openVersionModal(false);
        } catch (err) {
            showToast(err.message || 'Gagal memuat konten versi.');
        }
    }

    function saveLocalDraft(md) {
        if (isDirty(md)) {
            storageSet(draftKey, JSON.stringify({ md, at: Date.now() }));
        } else {
            storageRemove(draftKey);
        }
    }

    function readLocalDraft() {
        try {
            const draft = JSON.parse(storageGet(draftKey) || 'null');
            return draft && typeof draft.md === 'string' ? draft : null;
        } catch (_) {
            return null;
        }
    }

    function offerLocalDraft() {
        const draft = readLocalDraft();
        const banner = document.getElementById('draftRestoreBanner');
        if (!draft || !banner || !isDirty(draft.md)) return;
        const time = document.getElementById('draftRestoreTime');
        if (time) time.innerText = new Date(draft.at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
        banner.classList.remove('hidden');
        banner.classList.add('flex');
    }

    function hideDraftBanner() {
        const banner = document.getElementById('draftRestoreBanner');
        banner?.classList.add('hidden');
        banner?.classList.remove('flex');
    }

    function restoreLocalDraft() {
        const draft = readLocalDraft();
        if (draft) {
            setContent(draft.md);
            showToast('Draf terakhir dipulihkan. Jangan lupa simpan CV.');
        }
        hideDraftBanner();
    }

    function discardLocalDraft() {
        storageRemove(draftKey);
        hideDraftBanner();
    }

    // ------------------------------------------------------------
    // 11. Simpan CV resmi (temp = false) -> memicu evaluasi /api/ai/cv/score di backend
    // ------------------------------------------------------------
    let isSaving = false;

    async function saveCv() {
        if (isSaving) return;
        const btn = document.getElementById('btnSaveCv');
        const raw = getActiveMarkdown();

        if (!raw.trim()) {
            showToast('Konten CV tidak boleh kosong.');
            return;
        }

        isSaving = true;
        setSaveStatus('saving');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = `
                <svg class="animate-spin w-4 h-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Menyimpan…</span>
            `;
        }

        try {
            const res = await fetch("/bkk/me/cv", {
                method: "POST",
                headers: buildApiHeaders(),
                credentials: 'include',
                body: JSON.stringify({
                    raw_markdown: raw,
                    temp: false
                })
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                showToast(data.message || 'Gagal menyimpan CV ke server.');
                return;
            }

            if (data.data) {
                activeCvId = data.data.id || activeCvId;
                lastSavedMarkdown = raw;
                lastAnalyzedDraftId = null;
                storageRemove(draftKey);
                hideDraftBanner();

                const scoreTextEl = document.getElementById('compactScoreText');
                const scoreDotEl = document.getElementById('compactScoreDot');
                if (data.data.feedback && data.data.feedback.total_score) {
                    if (scoreTextEl) scoreTextEl.innerText = `Skor: ${data.data.feedback.total_score}/100`;
                    if (scoreDotEl) scoreDotEl.className = "w-2 h-2 rounded-full bg-emerald-500";
                } else {
                    if (scoreTextEl) scoreTextEl.innerText = `Skor: Dinilai…`;
                    if (scoreDotEl) scoreDotEl.className = "w-2 h-2 rounded-full bg-amber-400 animate-pulse";
                }
            }

            showToast(data.message || 'CV berhasil disimpan! Evaluasi skor AI sedang diproses di background.');

            @if(!$isMainCv || $isNewVersion)
                setTimeout(() => {
                    window.location.href = "{{ route('bkk.me.cv.edit') }}";
                }, 1000);
            @endif
        } catch (err) {
            showToast('Terjadi kesalahan jaringan saat menyimpan CV.');
        } finally {
            isSaving = false;
            updateDirtyIndicator(getActiveMarkdown());
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = `
                    <i data-lucide="save" class="w-4 h-4"></i>
                    <span>Simpan CV</span>
                `;
                lucide.createIcons({ root: btn });
            }
        }
    }
</script>
@endpush
