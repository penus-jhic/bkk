@extends('me.master')

@section('title', 'Editor CV ATS - BKK SMK Plus Pelita Nusantara')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<style>
    .editor-textarea {
        tab-size: 2;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
    }
</style>
@endpush

@php
    $role = strtoupper($authUser['role'] ?? 'SISWA');
    $isSiswa = ($role === 'SISWA');
    $isMainCv = (bool) ($activeCv?->is_main ?? true);
    $shortChecksum = substr($checksum ?? '', 0, 12);
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
                </div>
                <p class="text-xs sm:text-sm text-muted mt-0.5">Tulis kode Markdown langsung, pratinjau real-time standar ATS, dan simpan versi dokumen.</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <!-- CV Version Switcher Dropdown -->
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

            <!-- Tanya AI Button -->
            <button type="button" onclick="toggleMobileAi(true)" class="h-9 px-3.5 rounded-full bg-maroon/10 hover:bg-maroon hover:text-white text-maroon text-xs font-semibold inline-flex items-center gap-1.5 transition-colors cursor-pointer" title="Konsultasi Penus AI">
                <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                <span>Tanya AI</span>
            </button>

            <a href="{{ route('bkk.me.cv') }}" class="h-9 px-4 rounded-full border border-line bg-white hover:bg-canvas text-navy text-xs font-semibold inline-flex items-center shadow-sm transition-colors cursor-pointer">
                Pratinjau
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
    <div id="editorWorkspaceGrid" class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_360px] gap-6 items-start">
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
            <!-- AI Header -->
            <div class="p-5 border-b border-line flex items-center gap-3 bg-canvas/30 shrink-0">
                <div class="w-9 h-9 rounded-full bg-gradient-to-br from-maroon to-navy grid place-items-center text-white shadow-sm">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="font-bold text-sm gemini-text">Penus AI Assistant</div>
                    <div class="text-[11px] text-muted truncate">Asisten CV · {{ $isSiswa ? 'Mode Siswa PKL' : 'Mode Alumni' }}</div>
                </div>
            </div>

            <!-- AI Body Scrollable -->
            <div class="flex-1 overflow-y-auto p-4 sm:p-5 space-y-4 text-xs">
                <!-- Action Buttons: Improvisasi & Summary -->
                <div class="grid gap-2.5">
                    <button type="button" onclick="runAiImprove()" class="text-left rounded-2xl border border-line p-3.5 hover:border-maroon/50 hover:bg-maroon/5 transition-all cursor-pointer group">
                        <div class="flex items-center gap-2 font-bold text-navy text-xs">
                            <i data-lucide="wand-2" class="w-4 h-4 text-maroon"></i>
                            <span>Improvisasi dengan AI</span>
                        </div>
                        <p class="text-[11px] text-muted mt-1 leading-relaxed">Ubah butir pengalaman menjadi kalimat berdampak dengan action verbs & metrik angka.</p>
                    </button>

                    <button type="button" onclick="runAiSummary()" class="text-left rounded-2xl border border-line p-3.5 hover:border-maroon/50 hover:bg-maroon/5 transition-all cursor-pointer group">
                        <div class="flex items-center gap-2 font-bold text-navy text-xs">
                            <i data-lucide="sparkles" class="w-4 h-4 text-maroon"></i>
                            <span>Generate Summary</span>
                        </div>
                        <p class="text-[11px] text-muted mt-1 leading-relaxed">Tulis ringkasan profil profesional singkat yang memikat rekruter HRD.</p>
                    </button>
                </div>

                <!-- Loading AI State -->
                <div id="aiLoadingIndicator" class="hidden rounded-2xl p-4 bg-canvas border border-line text-center space-y-2">
                    <div class="inline-flex items-center gap-2 text-navy font-semibold text-xs">
                        <i data-lucide="loader-2" class="w-4 h-4 animate-spin text-maroon"></i>
                        <span>AI sedang menganalisis konten CV...</span>
                    </div>
                    <p class="text-[11px] text-muted">Menyesuaikan dengan standar ATS & kompetensi vokasi SMK...</p>
                </div>

                <!-- AI Result Box -->
                <div id="aiResultBox" class="hidden rounded-2xl border border-maroon/20 bg-maroon/[0.02] p-4 space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-line">
                        <span id="aiResultKindPill" class="rounded-full px-2 py-0.5 text-[10px] font-bold bg-maroon text-white">Hasil AI</span>
                        <div class="flex items-center gap-1">
                            <button onclick="applyAiResult()" class="h-6 px-2.5 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white text-[11px] font-semibold transition cursor-pointer">
                                Terapkan
                            </button>
                            <button onclick="discardAiResult()" class="h-6 px-2 rounded-full hover:bg-navy/5 text-muted text-[11px] transition cursor-pointer">
                                Batalkan
                            </button>
                        </div>
                    </div>
                    <div id="aiResultContent" class="text-xs text-navy leading-relaxed space-y-2"></div>
                </div>

                <!-- Interactive Chat Box with AI -->
                <div class="space-y-2 pt-2 border-t border-line">
                    <div class="font-bold text-navy text-[11px] uppercase tracking-wider">Konsultasi Interaktif:</div>
                    <div id="aiChatHistory" class="space-y-2 max-h-48 overflow-y-auto pr-1">
                        <div class="flex gap-2 items-start">
                            <div class="w-6 h-6 rounded-full bg-navy grid place-items-center text-white shrink-0 mt-0.5">
                                <i data-lucide="bot" class="w-3.5 h-3.5"></i>
                            </div>
                            <div class="text-xs rounded-2xl px-3 py-2 bg-[#f1f3f4] text-navy rounded-bl-sm leading-relaxed">
                                Halo! Saya asisten CV BKK Penus. Anda bisa meminta saya memperbaiki kalimat kerja, menambahkan action verb, atau menyesuaikan kata kunci ATS.
                            </div>
                        </div>
                    </div>

                    <!-- Chat Quick Chips -->
                    <div class="flex flex-wrap gap-1.5 pt-1">
                        <button onclick="sendChatPrompt('Perbaiki kalimat pengalaman agar lebih formal')" class="px-2 py-1 rounded-lg bg-navy/5 hover:bg-navy hover:text-white text-[10px] text-navy transition cursor-pointer">
                            Perbaiki formalitas
                        </button>
                        <button onclick="sendChatPrompt('Bagaimana membuat ringkasan lebih singkat?')" class="px-2 py-1 rounded-lg bg-navy/5 hover:bg-navy hover:text-white text-[10px] text-navy transition cursor-pointer">
                            Ringkas profil
                        </button>
                        <button onclick="sendChatPrompt('Berikan kata kunci ATS terbaik untuk kejuruan saya')" class="px-2 py-1 rounded-lg bg-navy/5 hover:bg-navy hover:text-white text-[10px] text-navy transition cursor-pointer">
                            Kata kunci ATS
                        </button>
                    </div>

                    <div class="flex items-center gap-1.5 pt-1">
                        <input
                            type="text"
                            id="aiChatInput"
                            placeholder="Ketik pertanyaan untuk AI..."
                            onkeydown="if(event.key==='Enter') sendChatPrompt()"
                            class="flex-1 h-8 px-3 rounded-xl border border-line bg-canvas/30 focus:bg-white focus:border-navy text-xs outline-none text-navy"
                        />
                        <button onclick="sendChatPrompt()" class="h-8 px-3 rounded-xl bg-navy hover:bg-navy-light text-white text-xs font-semibold grid place-items-center cursor-pointer">
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
            Setiap kali Anda menekan "Simpan CV", sistem membuat salinan versi baru dengan checksum SHA-256 tersendiri. Anda dapat beralih ke editor versi tersebut atau memuat isinya ke editor saat ini.
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
<div id="mobileAiDrawer" class="fixed top-0 right-0 bottom-0 z-50 w-full max-w-sm bg-white shadow-2xl transform translate-x-full transition-transform duration-300 flex flex-col">
    <div class="p-4 border-b border-line flex items-center justify-between">
        <div class="flex items-center gap-2">
            <i data-lucide="sparkles" class="w-4 h-4 text-maroon"></i>
            <span class="font-bold text-sm text-navy">Penus AI Assistant</span>
        </div>
        <button onclick="toggleMobileAi(false)" class="w-8 h-8 rounded-full grid place-items-center hover:bg-navy/5 text-muted cursor-pointer">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
    <div id="mobileAiContent" class="flex-1 overflow-y-auto"></div>
</div>
@endsection

@push('scripts')
<script>
    const isSiswa = @json($isSiswa);
    let currentAiResult = null;
    let isFullscreenMode = false;

    // 1. Inisialisasi & Live ATS Preview
    document.addEventListener('DOMContentLoaded', () => {
        const raw = document.getElementById('rawMarkdownTextarea')?.value || '';
        updateAtsLivePreview(raw);
        updateStats(raw);
        lucide.createIcons();
    });

    function onRawMarkdownInput() {
        const textarea = document.getElementById('rawMarkdownTextarea');
        const md = textarea ? textarea.value : '';
        updateAtsLivePreview(md);
        updateStats(md);
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

    // 2. Fullscreen Toggle (Zen Focus Mode)
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
            // Fullscreen ON: fixed 100vw x 100vh overlay, menutupi header & sidebar
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
            // Fullscreen OFF: kembali ke layout normal portal
            wrapper.className = "fade-up space-y-5 transition-all duration-150";
            workspace.className = "grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_360px] gap-6 items-start";
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

    // ESC key listener to exit fullscreen
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && isFullscreenMode) {
            toggleFullscreen();
        }
    });

    // 3. CV Switcher Helper
    function switchCvVersion(url) {
        if (!url) return;
        window.location.href = url;
    }

    // 4. AI Assistant
    const improvedMap = {
        "Membantu instalasi jaringan fiber ke rumah pelanggan": "Mengimplementasikan instalasi jaringan FTTH untuk 60+ pelanggan residensial dengan tingkat keberhasilan aktivasi 98%",
        "Membantu teknisi senior memperbaiki gangguan": "Mendiagnosis dan menyelesaikan 40+ tiket gangguan jaringan per minggu bersama tim teknisi, memenuhi SLA 95%",
        "Mencatat laporan harian pekerjaan": "Menyusun laporan harian terstruktur yang mempercepat evaluasi supervisor hingga 30%",
        "Mengelola transaksi kasir harian": "Memproses 300+ transaksi kasir harian dengan akurasi kas 100% tanpa selisih selama 12 bulan berturut-turut",
        "Membuat laporan penjualan shift": "Menyusun laporan penjualan per shift yang menjadi acuan rekap harian supervisor toko",
        "Melatih kasir baru": "Membimbing 8 kasir baru hingga mandiri dalam 2 minggu, menurunkan kesalahan input sebesar 40%"
    };

    function runAiImprove() {
        showAiLoading(true);
        hideAiResult();

        setTimeout(() => {
            showAiLoading(false);
            const md = getActiveMarkdown();
            const lines = md.split('\n');
            const items = [];

            lines.forEach(line => {
                const trimmed = line.trim();
                if (trimmed.startsWith('- ')) {
                    const content = trimmed.substring(2);
                    const improved = improvedMap[content] || ("Mengeksekusi " + content.replace(/^Membantu\s+/i, '') + " dengan hasil terukur");
                    if (improved !== content) {
                        items.push({ before: content, after: improved });
                    }
                }
            });

            if (!items.length) {
                items.push({
                    before: "Melaksanakan penugasan kerja industri",
                    after: "Mengeksekusi 40+ penugasan kerja industri harian dengan tingkat akurasi 98% sesuai SOP"
                });
            }

            currentAiResult = { kind: "improve", items: items };
            const container = document.getElementById('aiResultContent');
            const pill = document.getElementById('aiResultKindPill');
            pill.innerText = "Hasil Improvisasi";

            container.innerHTML = `
                <div class="space-y-3">
                    ${items.map(it => `
                        <div>
                            <div class="text-muted line-through decoration-maroon/50">${it.before}</div>
                            <div class="mt-1 font-semibold text-navy flex gap-1.5 items-start">
                                <i data-lucide="check" class="w-3.5 h-3.5 text-emerald-600 mt-0.5 shrink-0"></i>
                                <span>${it.after}</span>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;

            document.getElementById('aiResultBox').classList.remove('hidden');
            lucide.createIcons();
            showToast('AI berhasil merumuskan poin pengalaman dengan action verbs.');
        }, 1200);
    }

    function runAiSummary() {
        showAiLoading(true);
        hideAiResult();

        setTimeout(() => {
            showAiLoading(false);
            const generatedSummary = isSiswa ?
                "Siswa Kejuruan Teknik Komputer & Jaringan SMK Plus Pelita Nusantara berintegritas tinggi dengan pengalaman praktik instalasi FTTH dan konfigurasi router Mikrotik. Terbiasa bekerja sesuai SOP industri dan siap berkontribusi aktif pada program PKL perusahaan." :
                "Lulusan Akuntansi & Keuangan Lembaga SMK Plus Pelita Nusantara dengan rekam jejak pengelolaan 300+ transaksi kasir harian dan rekonsiliasi kas tanpa selisih. Memiliki kedisiplinan tinggi, penguasaan aplikasi akuntansi, dan siap menempati posisi finance operasional.";

            currentAiResult = { kind: "summary", text: generatedSummary };
            const container = document.getElementById('aiResultContent');
            const pill = document.getElementById('aiResultKindPill');
            pill.innerText = "Summary Siap Pakai";

            container.innerHTML = `
                <div class="rounded-xl bg-canvas p-3 border border-line text-xs italic text-navy leading-relaxed">
                    "${generatedSummary}"
                </div>
            `;

            document.getElementById('aiResultBox').classList.remove('hidden');
            showToast('AI berhasil menyusun ringkasan profil profesional.');
        }, 1200);
    }

    function applyAiResult() {
        if (!currentAiResult) return;
        let md = getActiveMarkdown();

        if (currentAiResult.kind === "improve") {
            currentAiResult.items.forEach(it => {
                md = md.replace(`- ${it.before}`, `- ${it.after}`);
            });
            setContent(md);
            showToast('Improvisasi AI diterapkan ke CV!');
        } else if (currentAiResult.kind === "summary") {
            const sumBlock = `## Ringkasan Profil\n${currentAiResult.text}\n\n`;
            if (md.includes('## Ringkasan Profil')) {
                md = md.replace(/## Ringkasan Profil[\s\S]*?(?=\n## |\n---|$)/, sumBlock.trim());
            } else {
                md = sumBlock + md;
            }
            setContent(md);
            showToast('Ringkasan AI berhasil disisipkan ke CV!');
        }

        hideAiResult();
    }

    function discardAiResult() {
        currentAiResult = null;
        hideAiResult();
    }

    function showAiLoading(show) {
        const el = document.getElementById('aiLoadingIndicator');
        if (el) el.classList.toggle('hidden', !show);
    }

    function hideAiResult() {
        const el = document.getElementById('aiResultBox');
        if (el) el.classList.add('hidden');
    }

    function sendChatPrompt(promptText) {
        const input = document.getElementById('aiChatInput');
        const query = (promptText || input?.value || '').trim();
        if (!query) return;

        const chatContainer = document.getElementById('aiChatHistory');
        if (!chatContainer) return;

        const userDiv = document.createElement('div');
        userDiv.className = "flex justify-end";
        userDiv.innerHTML = `<div class="text-xs rounded-2xl px-3 py-2 max-w-[85%] bg-navy text-white rounded-br-sm leading-relaxed">${query}</div>`;
        chatContainer.appendChild(userDiv);

        if (input) input.value = '';

        setTimeout(() => {
            let reply = "Saya sudah meninjau dokumen CV Anda. Pastikan menyertakan metrik terukur pada pengalaman PKL dan kata kunci standar industri.";
            if (/singkat/i.test(query)) {
                reply = "Saran: batasi ringkasan profil menjadi 2 kalimat dan maksimal 3 poin per pengalaman. CV satu halaman lebih disukai rekruter entry-level.";
            } else if (/formal/i.test(query)) {
                reply = "Gunakan bentuk kalimat aktif yang konsisten, hindari kata santai seperti 'bantu-bantu', dan tambahkan terminologi kejuruan resmi.";
            } else if (/ats|kata kunci/i.test(query)) {
                reply = isSiswa ?
                    "Kata kunci ATS yang direkomendasikan untuk TKJ/RPL: Git, Laravel, API, troubleshooting jaringan, FTTH, SLA 95%." :
                    "Kata kunci ATS yang direkomendasikan untuk Akuntansi: rekonsiliasi kas, jurnal umum, Accurate 5, SAP, PPh 21, accounts payable.";
            }

            const aiDiv = document.createElement('div');
            aiDiv.className = "flex gap-2 items-start";
            aiDiv.innerHTML = `
                <div class="w-6 h-6 rounded-full bg-navy grid place-items-center text-white shrink-0 mt-0.5">
                    <i data-lucide="bot" class="w-3.5 h-3.5"></i>
                </div>
                <div class="text-xs rounded-2xl px-3 py-2 max-w-[85%] bg-[#f1f3f4] text-navy rounded-bl-sm leading-relaxed">${reply}</div>
            `;
            chatContainer.appendChild(aiDiv);
            lucide.createIcons({ root: aiDiv });
        }, 700);
    }

    function toggleMobileAi(open) {
        const drawer = document.getElementById('mobileAiDrawer');
        const backdrop = document.getElementById('mobileAiBackdrop');
        const content = document.getElementById('mobileAiContent');
        const desktopPanel = document.getElementById('desktopAiPanel');

        if (open) {
            if (content && desktopPanel) {
                content.innerHTML = desktopPanel.innerHTML;
                lucide.createIcons({ root: content });
            }
            backdrop.classList.remove('hidden');
            drawer.classList.remove('translate-x-full');
        } else {
            drawer.classList.add('translate-x-full');
            backdrop.classList.add('hidden');
        }
    }

    // 5. Modal Riwayat Versi
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

    // 6. Robust Save CV via AJAX (Anti-stuck spinner)
    async function saveCv() {
        const btn = document.getElementById('btnSaveCv');
        const raw = getActiveMarkdown();

        if (!raw.trim()) {
            showToast('Konten CV tidak boleh kosong.');
            return;
        }

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        // Tampilkan State Loading dengan Inline SVG yang kebal DOM replacement
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
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": csrfToken
                },
                body: JSON.stringify({
                    raw_markdown: raw
                })
            });

            const data = await res.json();

            if (!res.ok) {
                showToast(data.message || 'Gagal menyimpan CV ke server.');
                return;
            }

            if (data.data && data.data.checksum) {
                const newSha = data.data.checksum;
                const checksumEl = document.getElementById('checksumText');
                if (checksumEl) checksumEl.innerText = `sha256: ${newSha.substring(0, 12)}…`;
            }

            showToast(data.message || 'Perubahan CV Anda berhasil disimpan sebagai versi terbaru!');

            // Jika sebelumnya menyunting versi arsip, arahkan ke CV Utama setelah jeda singkat
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
