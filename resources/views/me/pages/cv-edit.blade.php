@extends('me.master')

@section('title', 'Editor CV ATS - BKK SMK Plus Pelita Nusantara')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
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
</style>
@endpush

@php
    $role = strtoupper($authUser['role'] ?? 'SISWA');
    $isSiswa = ($role === 'SISWA');
    $isMainCv = (bool) ($activeCv?->is_main ?? true);
    $shortChecksum = substr($checksum ?? '', 0, 12);
    $userJurusan = $profile['jurusan'] ?? ($isSiswa ? 'Rekayasa Perangkat Lunak' : 'Teknik Komputer dan Jaringan');
    $feedbackScore = $cvFeedback['total_score'] ?? null;
    $feedbackGrade = $cvFeedback['grade_label'] ?? null;
@endphp

@section('content')
<div id="cvEditorWrapper" class="fade-up space-y-5 transition-all duration-150">
    <!-- Top Action Bar -->
    <div id="cvTopActionBar" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-line shrink-0">
        <div class="flex items-center gap-3">
            <a href="{{ route('bkk.me.cv') }}" class="w-10 h-10 rounded-full grid place-items-center hover:bg-navy/5 cursor-pointer text-navy transition-colors shrink-0" title="Kembali ke Pratinjau CV">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-bold text-navy tracking-tight">Editor CV ATS</h1>
                    @if($isMainCv)
                        <span id="activeMainBadge" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                            <span>CV Utama (Aktif)</span>
                        </span>
                    @else
                        <span id="activeMainBadge" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            <span>Versi Arsip #{{ $activeCv->id }}</span>
                        </span>
                    @endif
                    <span id="activeChecksumBadge" class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-mono bg-navy/5 text-navy border border-line" title="Checksum SHA-256 Aktif">
                        <i data-lucide="shield-check" class="w-3 h-3 text-emerald-600"></i>
                        <span id="checksumText">sha256: {{ $shortChecksum }}…</span>
                    </span>
                    <span id="draftDirtyBadge" class="hidden items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        <span>Perubahan Belum Disimpan</span>
                    </span>
                </div>
                <p class="text-xs sm:text-sm text-muted mt-0.5">Tulis kode Markdown langsung, terapkan patch saran AI, dan simpan versi resmi untuk evaluasi skor ATS.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <!-- CV Version Switcher Dropdown (Hanya versi tersimpan temp = false) -->
            @if(isset($cvVersions) && $cvVersions->count() > 1)
                <div class="flex items-center gap-1.5">
                    <select id="cvVersionSwitcher" onchange="switchCvVersion(this.value)" class="h-9 px-3 rounded-full border border-line bg-white hover:bg-canvas text-navy text-xs font-semibold shadow-sm focus:outline-none focus:border-navy cursor-pointer" title="Ganti Versi CV">
                        @foreach($cvVersions as $v)
                            @php
                                $targetUrl = $v->is_main ? route('bkk.me.cv.edit') : route('bkk.me.cv.editVersion', $v->id);
                                $isSelected = ($activeCv?->id === $v->id);
                            @endphp
                            <option value="{{ $targetUrl }}" {{ $isSelected ? 'selected' : '' }}>
                                Versi #{{ $v->id }} {{ $v->is_main ? '⭐ (CV Utama)' : '— ' . ($v->created_at ? $v->created_at->format('d M H:i') : '') }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <button type="button" onclick="openVersionModal()" class="h-9 px-3.5 rounded-full border border-line bg-white hover:bg-canvas text-navy text-xs font-semibold inline-flex items-center gap-1.5 shadow-sm transition-colors cursor-pointer" title="Lihat Riwayat Versi">
                <i data-lucide="history" class="w-3.5 h-3.5 text-muted"></i>
                <span class="hidden sm:inline">Riwayat</span>
                <span id="versionCountBadge" class="w-4 h-4 rounded-full bg-navy/10 text-navy text-[10px] grid place-items-center font-bold">{{ count($cvVersions ?? []) }}</span>
            </button>

            <!-- Toggle Fullscreen Button -->
            <button type="button" id="btnToggleFullscreen" onclick="toggleFullscreen()" class="h-9 px-3.5 rounded-full border border-line bg-white hover:bg-canvas text-navy text-xs font-semibold inline-flex items-center gap-1.5 shadow-sm transition-colors cursor-pointer" title="Mode Layar Penuh (Fokus)">
                <i data-lucide="maximize-2" id="fullscreenIcon" class="w-3.5 h-3.5 text-navy"></i>
                <span id="fullscreenText" class="hidden sm:inline">Layar Penuh</span>
            </button>

            <!-- Tanya AI Button (Mobile / Fullscreen Drawer Trigger) -->
            <button type="button" onclick="toggleMobileAi(true)" class="h-9 px-3.5 rounded-full bg-maroon/10 hover:bg-maroon hover:text-white text-maroon text-xs font-semibold inline-flex items-center gap-1.5 transition-colors cursor-pointer" title="Buka Panel Penus AI">
                <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                <span>Penus AI</span>
            </button>

            <a href="{{ route('bkk.me.cv') }}" class="h-9 px-4 rounded-full border border-line bg-white hover:bg-canvas text-navy text-xs font-semibold inline-flex items-center shadow-sm transition-colors cursor-pointer">
                Pratinjau & Skor
            </a>

            <button id="btnSaveCv" onclick="saveCv()" class="h-9 px-5 rounded-full bg-maroon hover:bg-maroon-dark text-white text-xs font-semibold inline-flex items-center gap-1.5 shadow-sm transition-colors cursor-pointer">
                <i data-lucide="save" id="btnSaveIcon" class="w-3.5 h-3.5"></i>
                <span id="btnSaveText">Simpan CV</span>
            </button>
        </div>
    </div>

    <!-- Notice Banner for Archive / Non-Main Version -->
    @if(!$isMainCv && $activeCv)
        <div id="archiveNoticeBanner" class="rounded-2xl border border-amber-200 bg-amber-50/70 p-3.5 sm:p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-amber-900 shadow-sm shrink-0">
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
            </div>
        </div>
    @endif

    <!-- Main Grid: Left Editor & Preview Workspace, Right Docked AI Assistant -->
    <div id="editorWorkspaceGrid" class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_390px] gap-6 items-start">
        <!-- Editor & Preview Columns -->
        <div id="editorColumnsWrapper" class="grid grid-cols-1 lg:grid-cols-2 gap-4 items-start min-w-0">
            <!-- Left: Raw Markdown Code Editor Card -->
            <div id="editorCard" class="bg-white border border-line rounded-3xl p-4 sm:p-5 shadow-sm space-y-2">
                <div class="flex items-center justify-between pb-2 border-b border-line shrink-0">
                    <div class="flex items-center gap-2">
                        <i data-lucide="code" class="w-4 h-4 text-navy"></i>
                        <span class="font-bold text-xs uppercase tracking-wider text-navy">Kode Sumber Markdown</span>
                    </div>
                    <div class="flex items-center gap-2 text-[11px] text-muted">
                        <span id="charCount">0 karakter</span>
                        <span>•</span>
                        <span id="lineCount">0 baris</span>
                    </div>
                </div>

                <textarea
                    id="rawMarkdownTextarea"
                    rows="26"
                    oninput="onRawMarkdownInput()"
                    onkeydown="handleEditorKeydown(event)"
                    placeholder="Mulai menulis CV Markdown Anda di sini..."
                    class="editor-textarea w-full p-4 rounded-2xl border border-line bg-canvas/30 focus:bg-white focus:border-navy focus:ring-2 focus:ring-navy/10 outline-none text-xs text-navy leading-relaxed resize-y transition"
                >{{ $cvMarkdown }}</textarea>
            </div>

            <!-- Right: Live ATS Preview Paper Card -->
            <div id="previewCard" class="bg-white border border-line rounded-3xl p-4 sm:p-5 shadow-sm space-y-2">
                <div class="flex items-center justify-between pb-2 border-b border-line shrink-0">
                    <div class="flex items-center gap-2">
                        <i data-lucide="eye" class="w-4 h-4 text-emerald-600"></i>
                        <span class="font-bold text-xs uppercase tracking-wider text-navy">Pratinjau Langsung ATS</span>
                    </div>
                    <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                        Sinkron Otomatis
                    </span>
                </div>

                <div id="previewScrollContainer" class="rounded-2xl border border-line bg-[#f8f9fa] p-3 sm:p-4 max-h-[640px] overflow-y-auto">
                    <div id="livePreviewContainer" class="bg-white rounded-xl p-5 sm:p-8 shadow-xs border border-line/60 md-cv text-navy min-h-[500px]">
                        <!-- Injected by marked.js -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Docked AI Assistant (Visible on Desktop Normal Mode) -->
        <div id="desktopAiPanel" class="hidden xl:flex flex-col sticky top-20 max-h-[calc(100vh-6rem)] bg-white rounded-3xl border gemini-border shadow-md overflow-hidden shrink-0">
            <div id="aiAssistantRoot" class="flex flex-col flex-1 min-h-0">
                <!-- AI Header + Compact Score Badge from cv_resumes.feedback -->
                <div class="p-4 border-b border-line bg-canvas/30 space-y-3 shrink-0">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-maroon to-navy grid place-items-center text-white shadow-sm shrink-0">
                                <i data-lucide="sparkles" class="w-4 h-4"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="font-bold text-sm gemini-text truncate">Penus AI Assistant</div>
                                <div class="text-[11px] text-muted truncate">{{ $userJurusan }}</div>
                            </div>
                        </div>

                        <!-- Compact Score Pill (Read from cv_resumes.feedback) -->
                        <a href="{{ route('bkk.me.cv') }}" id="compactScorePill" class="px-2.5 py-1 rounded-2xl border border-line bg-white hover:bg-canvas transition flex items-center gap-1.5 shrink-0" title="Lihat rincian evaluasi skor ATS di CV Hub">
                            <span class="w-2 h-2 rounded-full {{ $feedbackScore ? 'bg-emerald-500' : 'bg-amber-400' }}" id="compactScoreDot"></span>
                            <span class="text-[11px] font-bold text-navy" id="compactScoreText">
                                @if($feedbackScore)
                                    Skor: {{ $feedbackScore }}/100
                                @else
                                    Skor: Menunggu
                                @endif
                            </span>
                        </a>
                    </div>

                    <!-- Segmented Tabs: Saran AI (/suggestions) & Tanya AI (/chat) -->
                    <div class="grid grid-cols-2 gap-1 p-1 rounded-2xl bg-navy/5 border border-line/60">
                        <button type="button" id="tabBtnSuggestions" onclick="switchAiTab('suggestions')" class="h-8 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 bg-white text-navy shadow-xs cursor-pointer">
                            <i data-lucide="wand-2" class="w-3.5 h-3.5 text-maroon"></i>
                            <span>Saran AI</span>
                            <span id="suggestionsCountBadge" class="hidden px-1.5 py-0.2 rounded-full bg-maroon/10 text-maroon text-[10px] font-bold">0</span>
                        </button>
                        <button type="button" id="tabBtnChat" onclick="switchAiTab('chat')" class="h-8 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 text-muted hover:text-navy cursor-pointer">
                            <i data-lucide="message-square-text" class="w-3.5 h-3.5"></i>
                            <span>Tanya AI</span>
                        </button>
                    </div>
                </div>

                <!-- TAB 1: SARAN PERBAIKAN AI (/api/ai/cv/suggestions) -->
                <div id="aiTabSuggestions" class="flex-1 overflow-y-auto p-4 space-y-3 text-xs">
                    <div class="rounded-2xl border border-line bg-canvas/40 p-3.5 space-y-2.5">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <div class="font-bold text-navy text-xs">Rekomendasi Berbasis Patch Diff</div>
                                <p class="text-[11px] text-muted mt-0.5 leading-relaxed">
                                    AI memeriksa draf CV Anda tanpa mengotori riwayat versi (<code class="text-[10px] bg-navy/5 px-1 rounded">temp=true</code>) dan menyusun patch siap pakai.
                                </p>
                            </div>
                        </div>
                        <button type="button" id="btnFetchSuggestions" onclick="runAiSuggestions()" class="w-full h-9 px-4 rounded-xl bg-navy hover:bg-navy-dark text-white text-xs font-semibold inline-flex items-center justify-center gap-2 shadow-xs transition cursor-pointer">
                            <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                            <span>Analisis & Minta Saran AI</span>
                        </button>
                    </div>

                    <!-- Loading State Suggestions -->
                    <div id="aiSuggestionsLoading" class="hidden rounded-2xl p-5 bg-canvas/50 border border-line text-center space-y-2">
                        <div class="inline-flex items-center gap-2 text-navy font-semibold text-xs">
                            <svg class="animate-spin w-4 h-4 text-maroon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span id="aiSuggestionsLoadingText">Menyiapkan draf & menganalisis CV...</span>
                        </div>
                        <p class="text-[11px] text-muted">Menyusun patch perbaikan berstandar ATS kejuruan...</p>
                    </div>

                    <!-- Suggestions List Container -->
                    <div id="aiSuggestionsList" class="space-y-3">
                        <div class="rounded-2xl border border-dashed border-line p-5 text-center text-muted text-[11px] leading-relaxed">
                            Tekan tombol <b>"Analisis & Minta Saran AI"</b> di atas untuk mendapatkan rekomendasi perbaikan beserta diff kode Markdown yang dapat langsung disuntikkan ke editor.
                        </div>
                    </div>
                </div>

                <!-- TAB 2: KONSULTASI INTERAKTIF / TANYA AI (/api/ai/cv/chat SSE) -->
                <div id="aiTabChat" class="hidden flex-1 flex flex-col min-h-0 p-4 text-xs">
                    <!-- Chat Messages History -->
                    <div id="aiChatHistory" class="flex-1 overflow-y-auto space-y-3 pr-1 min-h-[240px] max-h-[380px]">
                        <div class="flex gap-2 items-start">
                            <div class="w-6 h-6 rounded-full bg-navy grid place-items-center text-white shrink-0 mt-0.5">
                                <i data-lucide="bot" class="w-3.5 h-3.5"></i>
                            </div>
                            <div class="text-xs rounded-2xl px-3 py-2 bg-[#f1f3f4] text-navy rounded-bl-sm leading-relaxed">
                                Halo! Saya <b>Penus AI Assistant</b> untuk jurusan <b>{{ $userJurusan }}</b>. Tanyakan cara memperkuat butir pengalaman, kata kunci ATS, atau format CV Anda.
                            </div>
                        </div>
                    </div>

                    <!-- Suggested Actions from Last Reply (APPLY_DIFF / PROMPT) -->
                    <div id="aiSuggestedActionsBox" class="hidden pt-2.5 space-y-1.5 border-t border-line/60 mt-2">
                        <div class="text-[10px] font-bold uppercase tracking-wider text-muted">Aksi Disarankan AI:</div>
                        <div id="aiSuggestedActionsList" class="flex flex-col gap-1.5"></div>
                    </div>

                    <!-- Dynamic Quick Chips -->
                    <div id="aiQuickChipsContainer" class="flex flex-wrap gap-1.5 pt-2.5 mt-2 border-t border-line">
                        <button type="button" onclick="sendChatPrompt('Perbaiki kalimat pengalaman agar lebih formal')" class="px-2.5 py-1 rounded-lg bg-navy/5 hover:bg-navy hover:text-white text-[10px] font-medium text-navy transition cursor-pointer">
                            Perbaiki formalitas
                        </button>
                        <button type="button" onclick="sendChatPrompt('Bagaimana membuat ringkasan profil lebih singkat?')" class="px-2.5 py-1 rounded-lg bg-navy/5 hover:bg-navy hover:text-white text-[10px] font-medium text-navy transition cursor-pointer">
                            Ringkas profil
                        </button>
                        <button type="button" onclick="sendChatPrompt('Berikan kata kunci ATS terbaik untuk jurusan {{ $userJurusan }}')" class="px-2.5 py-1 rounded-lg bg-navy/5 hover:bg-navy hover:text-white text-[10px] font-medium text-navy transition cursor-pointer">
                            Kata kunci ATS
                        </button>
                    </div>

                    <!-- Input Form -->
                    <div class="flex items-center gap-1.5 pt-2.5 shrink-0">
                        <input
                            type="text"
                            id="aiChatInput"
                            placeholder="Ketik pertanyaan untuk AI..."
                            onkeydown="if(event.key==='Enter'){ event.preventDefault(); sendChatPrompt(); }"
                            class="flex-1 h-9 px-3 rounded-xl border border-line bg-canvas/30 focus:bg-white focus:border-navy text-xs outline-none text-navy"
                        />
                        <button type="button" id="btnSendChat" onclick="sendChatPrompt()" class="h-9 px-3.5 rounded-xl bg-navy hover:bg-navy-light text-white text-xs font-semibold grid place-items-center cursor-pointer shrink-0">
                            <i data-lucide="send" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Riwayat Versi CV -->
<div id="versionModalBackdrop" class="fixed inset-0 z-50 bg-navy/60 backdrop-blur-xs hidden grid place-items-center p-4">
    <div class="bg-white rounded-3xl max-w-xl w-full p-6 space-y-4 shadow-2xl border border-line">
        <div class="flex items-center justify-between pb-3 border-b border-line">
            <div class="flex items-center gap-2">
                <i data-lucide="history" class="w-5 h-5 text-navy"></i>
                <h3 class="font-bold text-base text-navy">Riwayat Versi CV Tersimpan</h3>
            </div>
            <button onclick="openVersionModal(false)" class="w-8 h-8 rounded-full grid place-items-center hover:bg-navy/5 text-muted cursor-pointer">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>

        <p class="text-xs text-muted">
            Hanya versi resmi yang disimpan melalui tombol <b>"Simpan CV"</b> yang tercatat di sini. Draf sementara saat analisis AI (<code class="text-[11px]">temp=true</code>) tidak mengotori daftar riwayat Anda.
        </p>

        <div class="max-h-72 overflow-y-auto divide-y divide-line/60 rounded-2xl border border-line/60">
            @forelse($cvVersions ?? [] as $idx => $v)
                <div class="p-3 sm:p-3.5 flex items-center justify-between gap-3 hover:bg-canvas/40 transition {{ $activeCv?->id === $v->id ? 'bg-navy/[0.03]' : '' }}">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-xs text-navy">Versi #{{ $v->id }}</span>
                            @if($v->is_main)
                                <span class="px-2 py-0.2 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">Utama</span>
                            @endif
                            @if(is_array($v->feedback) && isset($v->feedback['total_score']))
                                <span class="px-2 py-0.2 rounded-full text-[10px] font-semibold bg-navy/5 text-navy">Skor {{ $v->feedback['total_score'] }}</span>
                            @endif
                            @if($activeCv?->id === $v->id)
                                <span class="px-2 py-0.2 rounded-full text-[10px] font-semibold bg-navy/10 text-navy">Sedang Dibuka</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2 mt-0.5 text-[11px] text-muted">
                            <span class="font-mono">{{ substr($v->checksum, 0, 12) }}…</span>
                            <span>•</span>
                            <span>{{ $v->created_at ? $v->created_at->format('d M Y H:i') : '-' }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        @if($activeCv?->id !== $v->id)
                            <a href="{{ $v->is_main ? route('bkk.me.cv.edit') : route('bkk.me.cv.editVersion', $v->id) }}" class="h-7 px-3 rounded-full border border-line bg-white hover:bg-navy hover:text-white text-navy text-[11px] font-semibold transition cursor-pointer inline-flex items-center">
                                Buka
                            </a>
                        @endif
                        <button type="button" onclick="loadVersionContent(@js($v->raw_markdown))" class="h-7 px-3 rounded-full bg-navy/5 hover:bg-navy hover:text-white text-navy text-[11px] font-semibold transition cursor-pointer">
                            Muat Teks
                        </button>
                    </div>
                </div>
            @empty
                <div class="p-5 text-center text-xs text-muted">Belum ada riwayat versi sebelumnya.</div>
            @endforelse
        </div>

        <div class="flex justify-end pt-2">
            <button onclick="openVersionModal(false)" class="h-9 px-5 rounded-full border border-line bg-white hover:bg-canvas text-navy text-xs font-semibold cursor-pointer">
                Tutup
            </button>
        </div>
    </div>
</div>

<!-- Drawer Penus AI Assistant (Mobile & Fullscreen) -->
<div id="mobileAiBackdrop" class="fixed inset-0 z-50 bg-navy/50 backdrop-blur-xs hidden" onclick="toggleMobileAi(false)"></div>
<div id="mobileAiDrawer" class="fixed top-0 right-0 bottom-0 z-50 w-full max-w-md bg-white shadow-2xl transform translate-x-full transition-transform duration-300 flex flex-col">
    <div class="p-3.5 border-b border-line flex items-center justify-between shrink-0">
        <div class="flex items-center gap-2">
            <i data-lucide="sparkles" class="w-4 h-4 text-maroon"></i>
            <span class="font-bold text-sm text-navy">Panel Penus AI</span>
        </div>
        <button onclick="toggleMobileAi(false)" class="w-8 h-8 rounded-full grid place-items-center hover:bg-navy/5 text-muted cursor-pointer">
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
    let activeCvId = @json($activeCv?->id ?? null);
    let lastSavedMarkdown = @json($cvMarkdown ?? '');
    let lastAnalyzedDraftId = null;
    let lastAnalyzedMarkdown = null;
    let isFullscreenMode = false;

    // State untuk /api/ai/cv/suggestions & /api/ai/cv/chat
    let currentSuggestions = [];
    let chatMessages = [];
    let isChatStreaming = false;

    // 1. Inisialisasi & Live ATS Preview
    document.addEventListener('DOMContentLoaded', () => {
        const raw = document.getElementById('rawMarkdownTextarea')?.value || '';
        updateAtsLivePreview(raw);
        updateStats(raw);
        lucide.createIcons();
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

    function onRawMarkdownInput() {
        const textarea = document.getElementById('rawMarkdownTextarea');
        const md = textarea ? textarea.value : '';
        updateAtsLivePreview(md);
        updateStats(md);
        updateDirtyIndicator(md);
    }

    function updateDirtyIndicator(md) {
        const badge = document.getElementById('draftDirtyBadge');
        if (!badge) return;
        const isDirty = md.trim() !== (lastSavedMarkdown || '').trim();
        badge.classList.toggle('hidden', !isDirty);
        badge.classList.toggle('inline-flex', isDirty);
    }

    function updateAtsLivePreview(markdownText) {
        const container = document.getElementById('livePreviewContainer');
        if (!container) return;
        if (!markdownText || !markdownText.trim()) {
            container.innerHTML = '<div class="text-center py-16 text-muted italic text-xs">Mulai ketik isi CV di editor untuk melihat pratinjau lembar ATS secara langsung.</div>';
            return;
        }
        container.innerHTML = marked.parse(markdownText);
    }

    function updateStats(text) {
        const charEl = document.getElementById('charCount');
        const lineEl = document.getElementById('lineCount');
        if (charEl) charEl.innerText = `${text.length} karakter`;
        if (lineEl) lineEl.innerText = `${text.split('\n').length} baris`;
    }

    function getActiveMarkdown() {
        const textarea = document.getElementById('rawMarkdownTextarea');
        return textarea ? textarea.value : '';
    }

    function setContent(markdownText) {
        const textarea = document.getElementById('rawMarkdownTextarea');
        if (textarea) textarea.value = markdownText;
        updateAtsLivePreview(markdownText);
        updateStats(markdownText);
        updateDirtyIndicator(markdownText);
    }

    function handleEditorKeydown(e) {
        if (e.key === 'Tab') {
            e.preventDefault();
            const textarea = e.target;
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            textarea.value = textarea.value.substring(0, start) + "  " + textarea.value.substring(end);
            textarea.selectionStart = textarea.selectionEnd = start + 2;
            onRawMarkdownInput();
        }
    }

    // 2. Tab Switcher (Saran AI vs Tanya AI)
    function switchAiTab(tab) {
        const tabSug = document.getElementById('aiTabSuggestions');
        const tabChat = document.getElementById('aiTabChat');
        const btnSug = document.getElementById('tabBtnSuggestions');
        const btnChat = document.getElementById('tabBtnChat');

        if (tab === 'chat') {
            tabSug?.classList.add('hidden');
            tabChat?.classList.remove('hidden');
            if (btnChat) btnChat.className = "h-8 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 bg-white text-navy shadow-xs cursor-pointer";
            if (btnSug) btnSug.className = "h-8 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 text-muted hover:text-navy cursor-pointer";
            setTimeout(() => document.getElementById('aiChatInput')?.focus(), 50);
        } else {
            tabChat?.classList.add('hidden');
            tabSug?.classList.remove('hidden');
            if (btnSug) btnSug.className = "h-8 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 bg-white text-navy shadow-xs cursor-pointer";
            if (btnChat) btnChat.className = "h-8 rounded-xl text-xs font-bold transition flex items-center justify-center gap-1.5 text-muted hover:text-navy cursor-pointer";
        }
    }

    // 3. Sinkronisasi Draf Sementara (temp = true) sebelum memanggil /api/ai/cv/suggestions
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
        const res = await fetch("{{ route('bkk.me.cv.update') }}", {
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

    // 4. Feature: Prioritized Actionable Suggestions (POST /api/ai/cv/suggestions)
    async function runAiSuggestions() {
        const btn = document.getElementById('btnFetchSuggestions');
        const loadingBox = document.getElementById('aiSuggestionsLoading');
        const listBox = document.getElementById('aiSuggestionsList');

        if (btn) btn.disabled = true;
        if (loadingBox) loadingBox.classList.remove('hidden');

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

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function renderAiSuggestions(suggestions) {
        const listBox = document.getElementById('aiSuggestionsList');
        const countBadge = document.getElementById('suggestionsCountBadge');
        if (!listBox) return;

        if (countBadge) {
            countBadge.innerText = suggestions.length;
            countBadge.classList.toggle('hidden', suggestions.length === 0);
        }

        if (!suggestions.length) {
            listBox.innerHTML = `
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-4 text-center text-emerald-900 text-xs">
                    CV Anda sudah sangat optimal! Tidak ada saran perbaikan mendesak saat ini.
                </div>
            `;
            return;
        }

        listBox.innerHTML = suggestions.map((s, idx) => {
            const impactClass = s.impact === 'Tinggi'
                ? 'bg-maroon text-white'
                : (s.impact === 'Sedang' ? 'bg-amber-100 text-amber-800' : 'bg-navy/10 text-navy');

            const targetPattern = s.patch?.target_pattern || '';
            const replacement = s.patch?.replacement || '';

            return `
                <div id="editor-sug-card-${idx}" class="rounded-2xl border border-line bg-white p-3.5 space-y-2.5 shadow-2xs hover:border-navy/30 transition">
                    <div class="flex flex-wrap items-center justify-between gap-1.5">
                        <div class="flex items-center gap-1.5">
                            <span class="rounded-full px-2 py-0.5 text-[10px] font-bold uppercase ${impactClass}">
                                Dampak ${escapeHtml(s.impact || 'Tinggi')}
                            </span>
                            <span class="text-[10px] font-semibold text-muted">• ${escapeHtml(s.section || 'Umum')}</span>
                        </div>
                        <span class="font-mono text-[9px] px-1.5 py-0.5 rounded bg-navy/5 text-muted">${escapeHtml(s.category || 'ATS_PATCH')}</span>
                    </div>

                    <div>
                        <h4 class="font-bold text-xs text-navy leading-snug">${escapeHtml(s.title)}</h4>
                        <p class="text-[11px] text-muted mt-1 leading-relaxed">${escapeHtml(s.desc)}</p>
                    </div>

                    ${targetPattern || replacement ? `
                        <div class="rounded-xl bg-[#f8f9fa] border border-line/80 p-2.5 space-y-1.5 font-mono text-[10px]">
                            ${targetPattern ? `
                                <div class="text-red-700 bg-red-50/80 px-2 py-1 rounded border border-red-100 line-through break-words">
                                    - ${escapeHtml(targetPattern)}
                                </div>
                            ` : ''}
                            ${replacement ? `
                                <div class="text-emerald-800 bg-emerald-50/80 px-2 py-1 rounded border border-emerald-100 break-words whitespace-pre-wrap">+ ${escapeHtml(replacement)}</div>
                            ` : ''}
                        </div>
                    ` : ''}

                    <div class="flex justify-end pt-1">
                        <button type="button" id="btn-apply-sug-${idx}" onclick="applySuggestionByIndex(${idx})" class="h-7 px-3 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-semibold inline-flex items-center gap-1.5 transition cursor-pointer">
                            <i data-lucide="check" class="w-3 h-3"></i>
                            <span>Terapkan Patch</span>
                        </button>
                    </div>
                </div>
            `;
        }).join('');

        lucide.createIcons({ root: listBox });
    }

    function applySuggestionByIndex(idx) {
        const sug = currentSuggestions[idx];
        if (!sug || !sug.patch) return;

        const applied = applyAiPatch(sug.patch.target_pattern, sug.patch.replacement);
        if (applied) {
            const btn = document.getElementById(`btn-apply-sug-${idx}`);
            if (btn) {
                btn.disabled = true;
                btn.className = "h-7 px-3 rounded-full bg-emerald-100 text-emerald-800 text-[11px] font-semibold inline-flex items-center gap-1.5 cursor-not-allowed";
                btn.innerHTML = `<i data-lucide="check-check" class="w-3 h-3"></i><span>Diterapkan</span>`;
                lucide.createIcons({ root: btn });
            }
        }
    }

    /**
     * Implementasi applyAiPatch sesuai Section 6.2 AI_CONTRACT.md
     */
    function applyAiPatch(targetPattern, replacement) {
        const textarea = document.getElementById('rawMarkdownTextarea');
        if (!textarea) return false;
        let currentContent = textarea.value;

        if (targetPattern && currentContent.includes(targetPattern)) {
            currentContent = currentContent.replace(targetPattern, replacement);
            setContent(currentContent);
            showToast('Perbaikan AI berhasil diterapkan ke editor CV!');
            return true;
        }

        // Fallback toleran jika baris di editor sudah sedikit dimodifikasi
        const trimmedTarget = (targetPattern || '').trim();
        if (trimmedTarget && currentContent.includes(trimmedTarget)) {
            currentContent = currentContent.replace(trimmedTarget, replacement);
            setContent(currentContent);
            showToast('Perbaikan AI berhasil diterapkan ke editor CV!');
            return true;
        }

        showToast('Teks asli tidak ditemukan persis di dokumen. Pastikan bagian terkait belum dihapus.');
        return false;
    }

    // 5. Feature: Context-Aware Interactive CV Chat Assistant (POST /api/ai/cv/chat dengan stream: true SSE)
    async function sendChatPrompt(promptText) {
        if (isChatStreaming) return;

        const input = document.getElementById('aiChatInput');
        const query = (promptText || input?.value || '').trim();
        if (!query) return;

        switchAiTab('chat');

        const chatContainer = document.getElementById('aiChatHistory');
        if (!chatContainer) return;

        // Tambahkan pesan user ke UI dan riwayat messages kontrak
        chatMessages.push({ role: 'user', content: query });

        const userDiv = document.createElement('div');
        userDiv.className = "flex justify-end";
        userDiv.innerHTML = `<div class="text-xs rounded-2xl px-3 py-2 max-w-[85%] bg-navy text-white rounded-br-sm leading-relaxed">${escapeHtml(query)}</div>`;
        chatContainer.appendChild(userDiv);

        if (input && !promptText) input.value = '';

        // Buat gelembung balasan AI untuk diisi secara streaming SSE
        const aiDiv = document.createElement('div');
        aiDiv.className = "flex gap-2 items-start";
        aiDiv.innerHTML = `
            <div class="w-6 h-6 rounded-full bg-navy grid place-items-center text-white shrink-0 mt-0.5">
                <i data-lucide="bot" class="w-3.5 h-3.5"></i>
            </div>
            <div class="ai-chat-prose text-xs rounded-2xl px-3 py-2 max-w-[88%] bg-[#f1f3f4] text-navy rounded-bl-sm leading-relaxed">
                <span class="inline-flex items-center gap-1 text-muted italic">Mengetik...</span>
            </div>
        `;
        chatContainer.appendChild(aiDiv);
        lucide.createIcons({ root: aiDiv });
        chatContainer.scrollTop = chatContainer.scrollHeight;

        const bubbleEl = aiDiv.querySelector('.ai-chat-prose');
        isChatStreaming = true;

        try {
            const res = await fetch('/api/ai/cv/chat', {
                method: 'POST',
                headers: buildApiHeaders('text/event-stream, application/json'),
                credentials: 'include',
                body: JSON.stringify({
                    messages: chatMessages,
                    context: {
                        jurusan: userJurusan
                    },
                    stream: true
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

            const contentType = res.headers.get('content-type') || '';
            let fullReply = '';

            if (contentType.includes('text/event-stream') && res.body) {
                const reader = res.body.getReader();
                const decoder = new TextDecoder('utf-8');
                let sseBuffer = '';
                let currentEvent = 'message';

                while (true) {
                    const { value, done } = await reader.read();
                    if (done) break;

                    sseBuffer += decoder.decode(value, { stream: true });
                    const blocks = sseBuffer.split('\n\n');
                    sseBuffer = blocks.pop() || '';

                    for (const block of blocks) {
                        const lines = block.split('\n');
                        currentEvent = 'message';
                        let dataStr = '';

                        for (const line of lines) {
                            if (line.startsWith('event:')) {
                                currentEvent = line.slice(6).trim();
                            } else if (line.startsWith('data:')) {
                                dataStr += line.slice(5).trim();
                            }
                        }

                        if (!dataStr) continue;

                        try {
                            const parsed = JSON.parse(dataStr);
                            if (currentEvent === 'delta' && typeof parsed.content === 'string') {
                                fullReply += parsed.content;
                                if (bubbleEl) {
                                    bubbleEl.innerHTML = marked.parse(fullReply);
                                }
                                chatContainer.scrollTop = chatContainer.scrollHeight;
                            } else if (currentEvent === 'done') {
                                if (Array.isArray(parsed.suggested_actions)) {
                                    renderChatSuggestedActions(parsed.suggested_actions);
                                }
                                if (Array.isArray(parsed.quick_chips) && parsed.quick_chips.length) {
                                    renderChatQuickChips(parsed.quick_chips);
                                }
                            }
                        } catch (_) {}
                    }
                }
            } else {
                // Fallback jika server mengembalikan JSON non-stream
                const json = await res.json();
                fullReply = json.data?.reply || 'Berikut saran untuk CV Anda.';
                if (bubbleEl) {
                    bubbleEl.innerHTML = marked.parse(fullReply);
                }
                if (Array.isArray(json.data?.suggested_actions)) {
                    renderChatSuggestedActions(json.data.suggested_actions);
                }
                if (Array.isArray(json.data?.quick_chips) && json.data.quick_chips.length) {
                    renderChatQuickChips(json.data.quick_chips);
                }
            }

            if (fullReply) {
                chatMessages.push({ role: 'assistant', content: fullReply });
            }
        } catch (err) {
            if (bubbleEl) {
                bubbleEl.innerHTML = `<span class="text-maroon font-medium">${escapeHtml(err.message || 'Gagal memproses pesan AI.')}</span>`;
            }
        } finally {
            isChatStreaming = false;
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
                : 'border-line bg-canvas/60 hover:bg-navy hover:text-white text-navy';

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
            const beforeText = act.payload.before || '';
            const afterText = act.payload.after || '';
            const ok = applyAiPatch(beforeText, afterText);
            if (!ok && afterText) {
                // Jika teks contoh belum ada di dokumen, sisipkan ke baris pengalaman/akhir dokumen
                const current = getActiveMarkdown();
                setContent(current.trimEnd() + `\n- ${afterText}\n`);
                showToast('Rekomendasi kalimat AI berhasil ditambahkan ke CV!');
            }
        } else if (act.action_type === 'PROMPT' && typeof act.payload === 'string') {
            sendChatPrompt(act.payload);
        }
    }

    function renderChatQuickChips(chips) {
        const container = document.getElementById('aiQuickChipsContainer');
        if (!container || !Array.isArray(chips) || !chips.length) return;

        container.innerHTML = chips.map(chip => `
            <button type="button" onclick="sendChatPrompt('${escapeHtml(chip).replace(/'/g, "\\'")}')" class="px-2.5 py-1 rounded-lg bg-navy/5 hover:bg-navy hover:text-white text-[10px] font-medium text-navy transition cursor-pointer">
                ${escapeHtml(chip)}
            </button>
        `).join('');
    }

    // 6. Fullscreen Toggle & Mobile Drawer (Tanpa duplikasi ID DOM)
    function toggleFullscreen() {
        isFullscreenMode = !isFullscreenMode;
        const wrapper = document.getElementById('cvEditorWrapper');
        const icon = document.getElementById('fullscreenIcon');
        const text = document.getElementById('fullscreenText');
        const workspace = document.getElementById('editorWorkspaceGrid');
        const columnsWrapper = document.getElementById('editorColumnsWrapper');
        const desktopAi = document.getElementById('desktopAiPanel');
        const editorCard = document.getElementById('editorCard');
        const previewCard = document.getElementById('previewCard');
        const textarea = document.getElementById('rawMarkdownTextarea');
        const previewScroll = document.getElementById('previewScrollContainer');

        if (isFullscreenMode) {
            wrapper.className = "fixed inset-0 z-50 bg-[#f8f9fa] p-4 sm:p-6 flex flex-col h-screen w-screen overflow-hidden space-y-3";
            workspace.className = "flex-1 min-h-0 flex flex-col";
            columnsWrapper.className = "flex-1 min-h-0 grid grid-cols-1 lg:grid-cols-2 gap-4 items-stretch";
            if (desktopAi) desktopAi.classList.add('!hidden');

            editorCard.className = "bg-white border border-line rounded-2xl p-4 shadow-sm flex flex-col h-full min-h-0 space-y-2";
            previewCard.className = "bg-white border border-line rounded-2xl p-4 shadow-sm flex flex-col h-full min-h-0 space-y-2";

            textarea.className = "editor-textarea flex-1 w-full p-4 rounded-xl border border-line bg-canvas/30 focus:bg-white focus:border-navy focus:ring-2 focus:ring-navy/10 outline-none text-xs text-navy leading-relaxed resize-none overflow-y-auto";
            previewScroll.className = "flex-1 min-h-0 rounded-xl border border-line bg-[#f8f9fa] p-4 sm:p-6 overflow-y-auto";

            if (icon) icon.setAttribute('data-lucide', 'minimize-2');
            if (text) text.innerText = 'Keluar Layar Penuh';
            document.body.style.overflow = 'hidden';
            showToast('Mode Layar Penuh aktif. Tekan ESC untuk keluar.');
        } else {
            wrapper.className = "fade-up space-y-5 transition-all duration-150";
            workspace.className = "grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_390px] gap-6 items-start";
            columnsWrapper.className = "grid grid-cols-1 lg:grid-cols-2 gap-4 items-start min-w-0";
            if (desktopAi) desktopAi.classList.remove('!hidden');

            editorCard.className = "bg-white border border-line rounded-3xl p-4 sm:p-5 shadow-sm space-y-2";
            previewCard.className = "bg-white border border-line rounded-3xl p-4 sm:p-5 shadow-sm space-y-2";

            textarea.className = "editor-textarea w-full p-4 rounded-2xl border border-line bg-canvas/30 focus:bg-white focus:border-navy focus:ring-2 focus:ring-navy/10 outline-none text-xs text-navy leading-relaxed resize-y transition";
            previewScroll.className = "rounded-2xl border border-line bg-[#f8f9fa] p-3 sm:p-4 max-h-[640px] overflow-y-auto";

            if (icon) icon.setAttribute('data-lucide', 'maximize-2');
            if (text) text.innerText = 'Layar Penuh';
            document.body.style.overflow = '';
        }
        lucide.createIcons();
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isFullscreenMode) {
            toggleFullscreen();
        }
    });

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

    function switchCvVersion(url) {
        if (!url) return;
        window.location.href = url;
    }

    function openVersionModal(open = true) {
        const modal = document.getElementById('versionModalBackdrop');
        if (modal) modal.classList.toggle('hidden', !open);
    }

    function loadVersionContent(markdown) {
        if (!markdown) return;
        setContent(markdown);
        showToast('Konten versi terpilih berhasil dimuat ke editor.');
        openVersionModal(false);
    }

    // 7. Simpan CV Resmi (temp = false) -> Memicu Evaluasi /api/ai/cv/score & Notifikasi di Backend
    async function saveCv() {
        const btn = document.getElementById('btnSaveCv');
        const raw = getActiveMarkdown();

        if (!raw.trim()) {
            showToast('Konten CV tidak boleh kosong.');
            return;
        }

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = `
                <svg class="animate-spin w-3.5 h-3.5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span>Menyimpan…</span>
            `;
        }

        try {
            const res = await fetch("{{ route('bkk.me.cv.update') }}", {
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
                updateDirtyIndicator(raw);

                if (data.data.checksum) {
                    const newSha = data.data.checksum;
                    const checksumEl = document.getElementById('checksumText');
                    if (checksumEl) checksumEl.innerText = `sha256: ${newSha.substring(0, 12)}…`;
                }

                const scoreTextEl = document.getElementById('compactScoreText');
                const scoreDotEl = document.getElementById('compactScoreDot');
                if (data.data.feedback && data.data.feedback.total_score) {
                    if (scoreTextEl) scoreTextEl.innerText = `Skor: ${data.data.feedback.total_score}/100`;
                    if (scoreDotEl) scoreDotEl.className = "w-2 h-2 rounded-full bg-emerald-500";
                } else {
                    if (scoreTextEl) scoreTextEl.innerText = `Skor: Dievaluasi BE…`;
                    if (scoreDotEl) scoreDotEl.className = "w-2 h-2 rounded-full bg-amber-400 animate-pulse";
                }
            }

            showToast(data.message || 'CV berhasil disimpan! Evaluasi skor AI sedang diproses di background.');

            @if(!$isMainCv)
                setTimeout(() => {
                    window.location.href = "{{ route('bkk.me.cv.edit') }}";
                }, 1000);
            @endif
        } catch (err) {
            showToast('Terjadi kesalahan jaringan saat menyimpan CV.');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = `
                    <i data-lucide="save" class="w-3.5 h-3.5"></i>
                    <span>Simpan CV</span>
                `;
                lucide.createIcons({ root: btn });
            }
        }
    }
</script>
@endpush
