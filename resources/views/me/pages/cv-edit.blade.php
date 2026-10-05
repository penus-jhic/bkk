@extends('me.master')

@section('title', 'Editor CV - BKK SMK Plus Pelita Nusantara')

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
@endpush

@php
    $role = strtoupper($authUser['role'] ?? 'SISWA');
    $isSiswa = ($role === 'SISWA');
@endphp

@section('content')
<div class="fade-up space-y-5">
    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2 border-b border-line">
        <div class="flex items-center gap-2">
            <a href="{{ route('bkk.me.cv') }}" class="w-10 h-10 rounded-full grid place-items-center hover:bg-navy/5 cursor-pointer text-muted transition-colors" title="Kembali ke Pratinjau CV">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-navy tracking-tight">Editor CV ATS</h1>
                <p class="text-xs sm:text-sm text-muted">Isi form per bagian, sinkronkan ke Markdown, dan sempurnakan dengan AI.</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button onclick="toggleMobileAi(true)" class="xl:hidden h-9 px-4 rounded-full bg-maroon/10 hover:bg-maroon hover:text-white text-maroon text-xs font-semibold inline-flex items-center gap-1.5 transition-colors cursor-pointer">
                <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                <span>Tanya AI</span>
            </button>
            <a href="{{ route('bkk.me.cv') }}" class="h-9 px-4 rounded-full border border-line bg-white hover:bg-canvas text-navy text-xs font-semibold inline-flex items-center shadow-sm transition-colors cursor-pointer">
                Pratinjau
            </a>
            <button id="btnSaveCv" onclick="saveCv()" class="h-9 px-5 rounded-full bg-maroon hover:bg-maroon-dark text-white text-xs font-semibold inline-flex items-center gap-1.5 shadow-sm transition-colors cursor-pointer">
                <i data-lucide="save" class="w-3.5 h-3.5"></i>
                <span>Simpan</span>
            </button>
        </div>
    </div>

    <!-- Main Grid: Form & Markdown on Left, AI Assistant on Right -->
    <div class="grid grid-cols-1 xl:grid-cols-[minmax(0,1fr)_360px] gap-6 items-start">
        <div class="space-y-6 min-w-0">
            <!-- 1. Form Section with Tab Bar Control -->
            <div class="bg-white border border-line rounded-3xl overflow-hidden shadow-sm">
                <!-- Section Tab Bar (Top Bar Form Controller) -->
                <div class="flex overflow-x-auto border-b border-line px-2 bg-canvas/40" id="sectionTabBar">
                    @php
                        $secList = [
                            ['id' => 'pribadi', 'label' => 'Informasi Pribadi', 'icon' => 'user'],
                            ['id' => 'pendidikan', 'label' => 'Pendidikan', 'icon' => 'graduation-cap'],
                            ['id' => 'pengalaman', 'label' => 'Pengalaman PKL/Kerja', 'icon' => 'briefcase'],
                            ['id' => 'keahlian', 'label' => 'Keahlian Teknis', 'icon' => 'wrench'],
                            ['id' => 'portofolio', 'label' => 'Portofolio & Sertifikat', 'icon' => 'award'],
                        ];
                    @endphp
                    @foreach($secList as $sec)
                        <button
                            type="button"
                            onclick="setSectionTab('{{ $sec['id'] }}')"
                            id="tab-btn-{{ $sec['id'] }}"
                            class="sec-tab-btn relative flex items-center gap-2 px-4 h-12 text-xs sm:text-sm font-semibold whitespace-nowrap cursor-pointer transition-colors {{ $loop->first ? 'text-navy font-bold active' : 'text-muted hover:text-navy' }}"
                        >
                            <i data-lucide="{{ $sec['icon'] }}" class="w-4 h-4"></i>
                            <span>{{ $sec['label'] }}</span>
                            <span id="tab-indicator-{{ $sec['id'] }}" class="absolute bottom-0 left-3 right-3 h-[3px] rounded-t-full bg-navy {{ $loop->first ? '' : 'hidden' }}"></span>
                        </button>
                    @endforeach
                </div>

                <!-- Form Content: Hanya 1 Section yang Tampil Sesuai Tab Aktif -->
                <div class="p-5 sm:p-6">
                    <!-- Section: Informasi Pribadi -->
                    <div id="sec-pribadi" class="sec-content space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <div>
                                <label class="block font-medium text-muted mb-1.5">Nama Lengkap</label>
                                <input type="text" id="fName" value="{{ $profile['name'] }}" oninput="autoSync()" class="w-full h-11 px-3.5 rounded-xl border border-line bg-white focus:border-navy focus:ring-2 focus:ring-navy/10 outline-none text-navy font-medium text-sm transition" />
                            </div>
                            <div>
                                <label class="block font-medium text-muted mb-1.5">Headline / Posisi Tujuan</label>
                                <input type="text" id="fHeadline" value="{{ $isSiswa ? 'Teknisi Jaringan Komputer' : 'Staff Akuntansi' }}" oninput="autoSync()" class="w-full h-11 px-3.5 rounded-xl border border-line bg-white focus:border-navy focus:ring-2 focus:ring-navy/10 outline-none text-navy font-medium text-sm transition" />
                            </div>
                            <div>
                                <label class="block font-medium text-muted mb-1.5">Email</label>
                                <input type="email" id="fEmail" value="{{ $profile['email'] }}" oninput="autoSync()" class="w-full h-11 px-3.5 rounded-xl border border-line bg-white focus:border-navy focus:ring-2 focus:ring-navy/10 outline-none text-navy font-medium text-sm transition" />
                            </div>
                            <div>
                                <label class="block font-medium text-muted mb-1.5">No. WhatsApp / Telepon</label>
                                <input type="text" id="fPhone" value="{{ $profile['phone'] }}" oninput="autoSync()" class="w-full h-11 px-3.5 rounded-xl border border-line bg-white focus:border-navy focus:ring-2 focus:ring-navy/10 outline-none text-navy font-medium text-sm transition" />
                            </div>
                            <div>
                                <label class="block font-medium text-muted mb-1.5">Domisili Kota</label>
                                <input type="text" id="fCity" value="{{ $profile['domisili'] ?? 'Bogor' }}" oninput="autoSync()" class="w-full h-11 px-3.5 rounded-xl border border-line bg-white focus:border-navy focus:ring-2 focus:ring-navy/10 outline-none text-navy font-medium text-sm transition" />
                            </div>
                            <div>
                                <label class="block font-medium text-muted mb-1.5">NIS / Nomor Induk</label>
                                <input type="text" value="{{ $profile['nis'] }}" disabled class="w-full h-11 px-3.5 rounded-xl border border-line bg-canvas text-muted font-medium text-sm cursor-not-allowed" />
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block font-medium text-muted mb-1.5">Ringkasan Profil</label>
                                <textarea id="fSummary" rows="4" oninput="autoSync()" class="w-full p-3.5 rounded-xl border border-line bg-white focus:border-navy focus:ring-2 focus:ring-navy/10 outline-none text-xs sm:text-sm text-navy leading-relaxed transition resize-y">{{ $isSiswa ? 'Siswa kelas XII Teknik Komputer & Jaringan yang antusias di bidang infrastruktur jaringan. Berpengalaman PKL di PT Telkom Akses menangani instalasi FTTH dan troubleshooting jaringan pelanggan.' : 'Alumni Akuntansi & Keuangan Lembaga SMK Penus angkatan 2023 dengan 1,5 tahun pengalaman sebagai kasir senior dan admin keuangan. Teliti, terbiasa dengan software Accurate dan Excel tingkat lanjut.' }}</textarea>
                                <button type="button" onclick="runAiSummary()" class="mt-2 text-xs text-maroon font-semibold inline-flex items-center gap-1 hover:underline cursor-pointer">
                                    <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                                    <span>Generate dengan AI</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Pendidikan -->
                    <div id="sec-pendidikan" class="sec-content space-y-4 hidden">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                            <div>
                                <label class="block font-medium text-muted mb-1.5">Nama Sekolah</label>
                                <input type="text" id="fSchool" value="SMK Penus" oninput="autoSync()" class="w-full h-11 px-3.5 rounded-xl border border-line bg-white focus:border-navy focus:ring-2 focus:ring-navy/10 outline-none text-navy font-medium text-sm transition" />
                            </div>
                            <div>
                                <label class="block font-medium text-muted mb-1.5">Kompetensi Keahlian</label>
                                <input type="text" id="fMajor" value="{{ $isSiswa ? 'Teknik Komputer & Jaringan' : 'Akuntansi & Keuangan Lembaga' }}" oninput="autoSync()" class="w-full h-11 px-3.5 rounded-xl border border-line bg-white focus:border-navy focus:ring-2 focus:ring-navy/10 outline-none text-navy font-medium text-sm transition" />
                            </div>
                            <div>
                                <label class="block font-medium text-muted mb-1.5">Periode Belajar</label>
                                <input type="text" id="fPeriod" value="{{ $isSiswa ? '2022 – 2025' : '2020 – 2023' }}" oninput="autoSync()" class="w-full h-11 px-3.5 rounded-xl border border-line bg-white focus:border-navy focus:ring-2 focus:ring-navy/10 outline-none text-navy font-medium text-sm transition" />
                            </div>
                            <div>
                                <label class="block font-medium text-muted mb-1.5">Nilai / Prestasi</label>
                                <input type="text" id="fGrade" value="{{ $isSiswa ? 'Rata-rata nilai 88,4' : 'Lulusan terbaik jurusan' }}" oninput="autoSync()" class="w-full h-11 px-3.5 rounded-xl border border-line bg-white focus:border-navy focus:ring-2 focus:ring-navy/10 outline-none text-navy font-medium text-sm transition" />
                            </div>
                        </div>
                    </div>

                    <!-- Section: Pengalaman PKL/Kerja -->
                    <div id="sec-pengalaman" class="sec-content space-y-4 hidden">
                        <div id="experienceList" class="space-y-4">
                            <!-- Injected dynamically by JS -->
                        </div>
                        <div class="flex flex-wrap gap-2 pt-2">
                            <button type="button" onclick="addExperience()" class="h-9 px-4 rounded-full border border-line bg-white hover:bg-canvas text-navy text-xs font-semibold inline-flex items-center gap-1.5 cursor-pointer shadow-sm">
                                <i data-lucide="plus" class="w-4 h-4"></i>
                                <span>Tambah Pengalaman</span>
                            </button>
                            <button type="button" onclick="runAiImprove()" class="h-9 px-4 rounded-full bg-maroon/10 hover:bg-maroon hover:text-white text-maroon text-xs font-semibold inline-flex items-center gap-1.5 cursor-pointer transition-colors">
                                <i data-lucide="wand-2" class="w-4 h-4"></i>
                                <span>Improvisasi dengan AI</span>
                            </button>
                        </div>
                    </div>

                    <!-- Section: Keahlian Teknis -->
                    <div id="sec-keahlian" class="sec-content space-y-3 hidden">
                        <div id="skillsList" class="space-y-3">
                            <!-- Injected dynamically by JS -->
                        </div>
                        <button type="button" onclick="addSkill()" class="h-9 px-4 rounded-full border border-line bg-white hover:bg-canvas text-navy text-xs font-semibold inline-flex items-center gap-1.5 cursor-pointer shadow-sm">
                            <i data-lucide="plus" class="w-4 h-4"></i>
                            <span>Tambah Keahlian</span>
                        </button>
                    </div>

                    <!-- Section: Portofolio & Sertifikat -->
                    <div id="sec-portofolio" class="sec-content space-y-3 hidden">
                        <div>
                            <label class="block font-medium text-muted text-xs mb-1.5">Portofolio, prestasi & sertifikat (1 item per baris, format Markdown didukung)</label>
                            <textarea id="fPortfolio" rows="5" oninput="autoSync()" class="w-full p-3.5 rounded-xl border border-line bg-white focus:border-navy focus:ring-2 focus:ring-navy/10 outline-none text-xs sm:text-sm text-navy leading-relaxed transition resize-y">{{ $isSiswa ? "**Juara 2 LKS TKJ** Tingkat Kota Semarang 2024\nProyek: Desain topologi jaringan sekolah (VLAN & Hotspot)" : "**Sertifikat Kompetensi BNSP** — Teknisi Akuntansi Junior\nJuara 1 LKS Akuntansi Tingkat Provinsi 2022" }}</textarea>
                        </div>
                        <div class="rounded-2xl border border-dashed border-line p-5 text-center text-xs text-muted bg-[#f8f9fa]">
                            <i data-lucide="award" class="w-6 h-6 mx-auto text-line mb-1"></i>
                            <span>Seret & lepas dokumen sertifikat kompetensi (PDF/JPG) untuk verifikasi BKK</span>
                        </div>
                    </div>

                    <!-- Sinkronasi Markdown Action Footer -->
                    <div class="mt-6 pt-5 border-t border-line flex flex-wrap items-center justify-between gap-3">
                        <span class="text-xs text-muted leading-relaxed">
                            Perubahan form akan menimpa isi kode Markdown saat disinkronkan.
                        </span>
                        <button type="button" onclick="syncFormToMarkdown(true)" class="h-9 px-5 rounded-full bg-navy hover:bg-navy-dark text-white text-xs font-semibold inline-flex items-center gap-2 shadow-sm transition-colors cursor-pointer">
                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i>
                            <span>Sinkronkan ke Markdown</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. Markdown Editor with Live Preview -->
            <div class="bg-white border border-line rounded-3xl p-5 sm:p-6 shadow-sm space-y-3">
                <div class="flex items-center justify-between pb-3 border-b border-line">
                    <div>
                        <h3 class="font-bold text-sm text-navy">Markdown Editor</h3>
                        <p class="text-xs text-muted">Kiri: kode Markdown · Kanan: pratinjau langsung</p>
                    </div>
                    <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold bg-navy/10 text-navy">
                        Live Sync
                    </span>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <!-- Left: Raw Markdown Editor -->
                    <div class="flex flex-col space-y-1.5">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-muted">Kode Sumber Markdown</span>
                        <textarea
                            id="rawMarkdownTextarea"
                            rows="16"
                            oninput="onMarkdownInput()"
                            class="w-full p-4 rounded-2xl border border-line bg-canvas/40 focus:bg-white focus:border-navy outline-none text-xs font-mono text-navy leading-relaxed resize-y transition"
                        >{{ $cvMarkdown }}</textarea>
                    </div>

                    <!-- Right: Live Rendering Preview -->
                    <div class="flex flex-col space-y-1.5">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-muted">Pratinjau Langsung</span>
                        <div id="liveMarkdownPreview" class="md-cv p-5 bg-white border border-line rounded-2xl min-h-[250px] max-h-[400px] overflow-y-auto">
                            <!-- Rendered by Marked.js -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Docked AI Assistant (360px on Desktop) -->
        <div id="desktopAiPanel" class="hidden xl:flex flex-col sticky top-20 max-h-[calc(100vh-6rem)] bg-white rounded-3xl border gemini-border shadow-md overflow-hidden">
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
                        <p class="text-[11px] text-muted mt-1 leading-relaxed">Buat ringkasan profil berdasarkan jurusan dan pengalaman kejuruan.</p>
                    </button>
                </div>

                <!-- AI Loading Shimmer Indicator -->
                <div id="aiLoadingIndicator" class="hidden rounded-2xl border border-line p-4 space-y-2.5">
                    <div class="text-xs text-muted flex items-center gap-2">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-maroon animate-spin"></i>
                        <span>AI sedang memproses rekomendasi…</span>
                    </div>
                    <div class="h-2.5 rounded-full shimmer w-full"></div>
                    <div class="h-2.5 rounded-full shimmer w-4/5"></div>
                    <div class="h-2.5 rounded-full shimmer w-2/3"></div>
                </div>

                <!-- AI Result Box -->
                <div id="aiResultBox" class="hidden rounded-2xl gemini-border p-4 fade-up space-y-3 bg-canvas/30">
                    <div class="flex items-center justify-between">
                        <span id="aiResultKindPill" class="rounded-full px-2.5 py-0.5 text-[10px] font-bold bg-maroon/10 text-maroon">Hasil AI</span>
                        <button type="button" onclick="reRunAi()" class="text-[11px] text-muted hover:text-navy flex items-center gap-1 cursor-pointer">
                            <i data-lucide="refresh-cw" class="w-3 h-3"></i> Ulangi
                        </button>
                    </div>

                    <div id="aiResultContent" class="text-xs leading-relaxed text-navy">
                        <!-- Injected dynamic result -->
                    </div>

                    <div class="flex gap-2 pt-1">
                        <button type="button" onclick="applyAiResult()" class="h-8 px-4 rounded-full bg-maroon hover:bg-maroon-dark text-white text-xs font-semibold inline-flex items-center gap-1.5 cursor-pointer shadow-sm">
                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                            <span>Terapkan</span>
                        </button>
                        <button type="button" onclick="discardAiResult()" class="h-8 px-3 rounded-full text-xs font-semibold text-muted hover:bg-canvas cursor-pointer">
                            Buang
                        </button>
                    </div>
                </div>

                <!-- Chat History -->
                <div id="aiChatHistory" class="space-y-2.5 pt-2">
                    <!-- Chat bubbles injected here -->
                </div>
            </div>

            <!-- AI Chat Footer -->
            <div class="p-3.5 border-t border-line space-y-2 bg-canvas/30 shrink-0">
                <div class="flex gap-1.5 overflow-x-auto pb-1 text-[11px]">
                    <button type="button" onclick="sendChatPrompt('Buat lebih singkat')" class="whitespace-nowrap rounded-full border border-line px-2.5 py-1 bg-white hover:bg-canvas cursor-pointer text-navy font-medium">Buat lebih singkat</button>
                    <button type="button" onclick="sendChatPrompt('Nada lebih formal')" class="whitespace-nowrap rounded-full border border-line px-2.5 py-1 bg-white hover:bg-canvas cursor-pointer text-navy font-medium">Nada lebih formal</button>
                    <button type="button" onclick="sendChatPrompt('Kata kunci ATS')" class="whitespace-nowrap rounded-full border border-line px-2.5 py-1 bg-white hover:bg-canvas cursor-pointer text-navy font-medium">Kata kunci ATS</button>
                </div>
                <div class="flex items-center gap-2 rounded-full bg-white border border-line pl-3.5 pr-1 h-10 focus-within:ring-1 focus-within:ring-navy">
                    <input type="text" id="aiChatInput" onkeydown="if(event.key==='Enter') sendChatPrompt()" placeholder="Tanya AI tentang CV Anda…" class="flex-1 bg-transparent outline-none text-xs text-navy" />
                    <button type="button" onclick="sendChatPrompt()" class="w-8 h-8 rounded-full bg-maroon text-white grid place-items-center hover:bg-maroon-dark cursor-pointer transition-colors shadow-sm">
                        <i data-lucide="send" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Mobile AI Drawer Overlay -->
<div id="mobileAiBackdrop" class="fixed inset-0 bg-black/40 z-50 hidden xl:hidden" onclick="toggleMobileAi(false)"></div>
<div id="mobileAiDrawer" class="fixed right-0 top-0 bottom-0 w-full sm:w-[400px] bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 xl:hidden flex flex-col">
    <!-- Header -->
    <div class="p-4 border-b border-line flex items-center justify-between">
        <div class="flex items-center gap-2">
            <i data-lucide="sparkles" class="w-4 h-4 text-maroon"></i>
            <span class="font-bold text-sm text-navy">Penus AI Assistant</span>
        </div>
        <button onclick="toggleMobileAi(false)" class="w-8 h-8 rounded-full grid place-items-center hover:bg-canvas cursor-pointer text-muted">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
    <!-- Injected mobile content clone -->
    <div class="flex-1 overflow-y-auto p-4 space-y-4 text-xs" id="mobileAiContent">
        <!-- Cloned or synced from desktop panel -->
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Initial Form State matching Prototype
    const isSiswa = "{{ $isSiswa }}" === "1";
    let currentTab = "pribadi";

    let experiences = isSiswa ? [
        {
            title: "Teknisi Jaringan (PKL)",
            company: "PT Telkom Akses",
            period: "Jan 2025 – Apr 2025",
            bullets: "Membantu instalasi jaringan fiber ke rumah pelanggan\nMembantu teknisi senior memperbaiki gangguan\nMencatat laporan harian pekerjaan"
        }
    ] : [
        {
            title: "Kasir Senior",
            company: "Hypermart",
            period: "Jan 2024 – Sekarang",
            bullets: "Mengelola transaksi kasir harian\nMembuat laporan penjualan shift\nMelatih kasir baru"
        },
        {
            title: "Admin Keuangan (PKL)",
            company: "Koperasi Sejahtera",
            period: "Jul 2022 – Des 2022",
            bullets: "Menginput jurnal umum dan buku besar\nMembantu rekonsiliasi kas bulanan"
        }
    ];

    let skills = isSiswa ? [
        "Mikrotik RouterOS, Cisco Packet Tracer",
        "Instalasi & splicing Fiber Optik",
        "Linux Server (Ubuntu), DNS, DHCP"
    ] : [
        "Accurate 5, MYOB, Zahir",
        "Microsoft Excel (Pivot, VLOOKUP)",
        "Perpajakan dasar (PPh 21, PPN)"
    ];

    let currentAiResult = null;

    document.addEventListener('DOMContentLoaded', function () {
        renderExperiences();
        renderSkills();
        syncMarkdownPreview();
        lucide.createIcons();
    });

    // 1. Controller Top Bar Section Tab
    function setSectionTab(tabId) {
        currentTab = tabId;
        document.querySelectorAll('.sec-tab-btn').forEach(btn => {
            btn.classList.remove('text-navy', 'font-bold', 'active');
            btn.classList.add('text-muted');
        });
        document.querySelectorAll('[id^="tab-indicator-"]').forEach(ind => ind.classList.add('hidden'));

        const activeBtn = document.getElementById('tab-btn-' + tabId);
        const activeInd = document.getElementById('tab-indicator-' + tabId);
        if (activeBtn) {
            activeBtn.classList.remove('text-muted');
            activeBtn.classList.add('text-navy', 'font-bold', 'active');
        }
        if (activeInd) {
            activeInd.classList.remove('hidden');
        }

        // Tampilkan HANYA section yang dipilih, sembunyikan sisanya
        document.querySelectorAll('.sec-content').forEach(sec => sec.classList.add('hidden'));
        const activeSec = document.getElementById('sec-' + tabId);
        if (activeSec) {
            activeSec.classList.remove('hidden');
            activeSec.classList.add('fade-up');
        }
        lucide.createIcons();
    }

    // 2. Render Pengalaman List
    function renderExperiences() {
        const container = document.getElementById('experienceList');
        if (!container) return;

        container.innerHTML = experiences.map((exp, idx) => `
            <div class="rounded-2xl border border-line p-4 space-y-3 bg-[#f8f9fa]/50">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-muted uppercase tracking-wide">Pengalaman #${idx + 1}</span>
                    ${experiences.length > 1 ? `
                        <button type="button" onclick="deleteExperience(${idx})" class="text-muted hover:text-maroon cursor-pointer p-1" title="Hapus Pengalaman">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    ` : ''}
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                    <div>
                        <label class="block font-medium text-muted mb-1">Posisi</label>
                        <input type="text" value="${exp.title}" oninput="updateExpField(${idx}, 'title', this.value)" class="w-full h-10 px-3 rounded-xl border border-line bg-white focus:border-navy outline-none text-navy font-medium" />
                    </div>
                    <div>
                        <label class="block font-medium text-muted mb-1">Perusahaan</label>
                        <input type="text" value="${exp.company}" oninput="updateExpField(${idx}, 'company', this.value)" class="w-full h-10 px-3 rounded-xl border border-line bg-white focus:border-navy outline-none text-navy font-medium" />
                    </div>
                    <div>
                        <label class="block font-medium text-muted mb-1">Periode</label>
                        <input type="text" value="${exp.period}" oninput="updateExpField(${idx}, 'period', this.value)" class="w-full h-10 px-3 rounded-xl border border-line bg-white focus:border-navy outline-none text-navy font-medium" />
                    </div>
                </div>
                <div>
                    <label class="block font-medium text-muted text-xs mb-1">Butir Pencapaian (1 poin per baris)</label>
                    <textarea rows="3" oninput="updateExpField(${idx}, 'bullets', this.value)" class="w-full p-3 rounded-xl border border-line bg-white focus:border-navy outline-none text-xs text-navy leading-relaxed font-mono">${exp.bullets}</textarea>
                </div>
            </div>
        `).join('');

        lucide.createIcons({ root: container });
    }

    function updateExpField(idx, field, val) {
        if (experiences[idx]) {
            experiences[idx][field] = val;
            autoSync();
        }
    }

    function addExperience() {
        experiences.push({ title: "", company: "", period: "", bullets: "" });
        renderExperiences();
        autoSync();
    }

    function deleteExperience(idx) {
        experiences.splice(idx, 1);
        renderExperiences();
        autoSync();
    }

    // 3. Render Keahlian List
    function renderSkills() {
        const container = document.getElementById('skillsList');
        if (!container) return;

        container.innerHTML = skills.map((s, idx) => `
            <div class="flex gap-2 items-center">
                <input type="text" value="${s}" oninput="updateSkill(${idx}, this.value)" class="w-full h-10 px-3.5 rounded-xl border border-line bg-white focus:border-navy outline-none text-xs sm:text-sm text-navy font-medium" />
                <button type="button" onclick="deleteSkill(${idx})" class="w-10 h-10 shrink-0 rounded-xl border border-line grid place-items-center hover:text-maroon hover:border-maroon/40 cursor-pointer text-muted transition-colors">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </div>
        `).join('');

        lucide.createIcons({ root: container });
    }

    function updateSkill(idx, val) {
        skills[idx] = val;
        autoSync();
    }

    function addSkill() {
        skills.push("");
        renderSkills();
    }

    function deleteSkill(idx) {
        skills.splice(idx, 1);
        renderSkills();
        autoSync();
    }

    // 4. Sinkronisasi Form ke Markdown
    function buildMarkdownFromForm() {
        const name = document.getElementById('fName')?.value || 'Nama Siswa';
        const headline = document.getElementById('fHeadline')?.value || '';
        const city = document.getElementById('fCity')?.value || '';
        const email = document.getElementById('fEmail')?.value || '';
        const phone = document.getElementById('fPhone')?.value || '';
        const summary = document.getElementById('fSummary')?.value || '';
        const school = document.getElementById('fSchool')?.value || '';
        const major = document.getElementById('fMajor')?.value || '';
        const period = document.getElementById('fPeriod')?.value || '';
        const grade = document.getElementById('fGrade')?.value || '';
        const portfolio = document.getElementById('fPortfolio')?.value || '';

        const lines = [
            `# ${name}`,
            `*${headline} · ${city} · ${email} · ${phone}*`,
            "",
            "## Ringkasan Profil",
            summary,
            "",
            "## Pendidikan",
            `### ${school} — ${major}`,
            `*${period} · ${grade}*`,
            "",
            `## Pengalaman ${experiences.length > 1 ? "Kerja" : "PKL"}`
        ];

        experiences.forEach(e => {
            lines.push(`### ${e.title} — ${e.company}`);
            lines.push(`*${e.period}*`);
            const bullets = e.bullets.split("\n").filter(Boolean);
            bullets.forEach(b => lines.push(`- ${b.trim()}`));
            lines.push("");
        });

        lines.push("## Keahlian Teknis");
        skills.filter(Boolean).forEach(s => lines.push(`- ${s.trim()}`));
        lines.push("");

        lines.push("## Portofolio & Sertifikat");
        portfolio.split("\n").filter(Boolean).forEach(p => lines.push(`- ${p.trim()}`));
        lines.push("");

        return lines.join("\n");
    }

    function syncFormToMarkdown(showNotification = false) {
        const md = buildMarkdownFromForm();
        const textarea = document.getElementById('rawMarkdownTextarea');
        if (textarea) {
            textarea.value = md;
            syncMarkdownPreview();
        }
        if (showNotification) {
            showToast('Formulir berhasil disinkronkan ke dokumen Markdown.');
        }
    }

    function autoSync() {
        // Otomatis update preview jika user mengetik di form
        const md = buildMarkdownFromForm();
        const textarea = document.getElementById('rawMarkdownTextarea');
        if (textarea) {
            textarea.value = md;
            syncMarkdownPreview();
        }
    }

    function onMarkdownInput() {
        syncMarkdownPreview();
    }

    function syncMarkdownPreview() {
        const raw = document.getElementById('rawMarkdownTextarea')?.value || '';
        const preview = document.getElementById('liveMarkdownPreview');
        if (preview && typeof marked !== 'undefined') {
            preview.innerHTML = marked.parse(raw);
        }
    }

    // 5. AI Assistant Functions (Summary, Polish, Chat)
    const improvedMap = {
        "Membantu instalasi jaringan fiber ke rumah pelanggan": "Mengimplementasikan instalasi jaringan FTTH untuk 60+ pelanggan residensial dengan tingkat keberhasilan aktivasi 98%",
        "Membantu teknisi senior memperbaiki gangguan": "Mendiagnosis dan menyelesaikan 40+ tiket gangguan jaringan per minggu bersama tim teknisi, memenuhi SLA 95%",
        "Mencatat laporan harian pekerjaan": "Menyusun laporan harian terstruktur yang mempercepat evaluasi supervisor hingga 30%",
        "Mengelola transaksi kasir harian": "Memproses 300+ transaksi kasir harian dengan akurasi kas 100% tanpa selisih selama 12 bulan berturut-turut",
        "Membuat laporan penjualan shift": "Menyusun laporan penjualan per shift yang menjadi acuan rekap harian supervisor toko",
        "Melatih kasir baru": "Membimbing 8 kasir baru hingga mandiri dalam 2 minggu, menurunkan kesalahan input sebesar 40%",
        "Menginput jurnal umum dan buku besar": "Mencatat 500+ entri jurnal umum dan buku besar per bulan menggunakan Accurate 5",
        "Membantu rekonsiliasi kas bulanan": "Melaksanakan rekonsiliasi kas bulanan dan mengidentifikasi selisih senilai Rp2,4 jt"
    };

    function runAiImprove() {
        showAiLoading(true);
        hideAiResult();

        setTimeout(() => {
            showAiLoading(false);
            const items = [];
            experiences.forEach(exp => {
                exp.bullets.split("\n").filter(Boolean).forEach(line => {
                    const trimmed = line.trim();
                    const improved = improvedMap[trimmed] || ("Mengeksekusi " + trimmed.replace(/^Membantu\s+/i, '') + " dengan hasil terukur");
                    if (improved !== trimmed) {
                        items.push({ before: trimmed, after: improved });
                    }
                });
            });

            currentAiResult = { kind: "improve", items: items };
            const container = document.getElementById('aiResultContent');
            const pill = document.getElementById('aiResultKindPill');
            pill.innerText = "Hasil Improvisasi";

            if (items.length) {
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
            } else {
                container.innerHTML = `<p class="text-muted">Poin pengalaman Anda sudah sangat kuat dan terukur. 🎉</p>`;
            }

            document.getElementById('aiResultBox').classList.remove('hidden');
            lucide.createIcons();
            showToast('AI berhasil merumuskan poin pengalaman dengan action verbs.');
        }, 1400);
    }

    function runAiSummary() {
        showAiLoading(true);
        hideAiResult();

        setTimeout(() => {
            showAiLoading(false);
            const text = isSiswa ?
                "Calon teknisi jaringan dari jurusan Teknik Komputer & Jaringan SMK Penus dengan pengalaman PKL 4 bulan di PT Telkom Akses. Terampil dalam instalasi FTTH, konfigurasi Mikrotik, dan troubleshooting jaringan; terbukti menangani 40+ tiket gangguan per minggu. Siap berkontribusi di lingkungan ISP maupun infrastruktur IT perusahaan." :
                "Staff akuntansi lulusan terbaik jurusan AKL SMK Penus dengan 1,5+ tahun pengalaman di bidang retail dan koperasi. Menguasai Accurate, Excel tingkat lanjut, dan perpajakan dasar; terbiasa mengelola 300+ transaksi harian dengan akurasi 100%. Berorientasi detail dan siap mendukung fungsi finance & tax perusahaan.";

            currentAiResult = { kind: "summary", text: text };
            const container = document.getElementById('aiResultContent');
            const pill = document.getElementById('aiResultKindPill');
            pill.innerText = "Draf Ringkasan";

            container.innerHTML = `<p class="text-navy font-medium leading-relaxed">${text}</p>`;
            document.getElementById('aiResultBox').classList.remove('hidden');
            lucide.createIcons();
            showToast('Ringkasan profesional berhasil digenerate oleh AI.');
        }, 1200);
    }

    function applyAiResult() {
        if (!currentAiResult) return;

        if (currentAiResult.kind === "improve") {
            experiences.forEach(exp => {
                exp.bullets = exp.bullets.split("\n").map(line => {
                    const trimmed = line.trim();
                    return improvedMap[trimmed] || (trimmed.startsWith("Membantu") ? "Mengeksekusi " + trimmed.replace(/^Membantu\s+/i, '') + " dengan hasil terukur" : trimmed);
                }).join("\n");
            });
            renderExperiences();
            autoSync();
            showToast('Poin pengalaman telah diperbarui dengan bahasa profesional.');
        } else if (currentAiResult.kind === "summary") {
            const summaryEl = document.getElementById('fSummary');
            if (summaryEl) {
                summaryEl.value = currentAiResult.text;
            }
            autoSync();
            showToast('Ringkasan profil profesional berhasil diterapkan ke form & Markdown.');
        }

        hideAiResult();
    }

    function discardAiResult() {
        currentAiResult = null;
        hideAiResult();
    }

    function reRunAi() {
        if (currentAiResult && currentAiResult.kind === "summary") {
            runAiSummary();
        } else {
            runAiImprove();
        }
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

        // User bubble
        const userDiv = document.createElement('div');
        userDiv.className = "flex justify-end";
        userDiv.innerHTML = `<div class="text-xs rounded-2xl px-3 py-2 max-w-[85%] bg-navy text-white rounded-br-sm leading-relaxed">${query}</div>`;
        chatContainer.appendChild(userDiv);

        if (input) input.value = '';

        // AI Reply
        setTimeout(() => {
            let reply = "Saya sudah meninjau CV Anda. Pastikan menyertakan metrik terukur pada pengalaman PKL dan kata kunci standar industri.";
            if (/singkat/i.test(query)) {
                reply = "Saran: batasi ringkasan profil menjadi 2 kalimat dan maksimal 3 poin per pengalaman. CV satu halaman lebih disukai rekruter entry-level.";
            } else if (/formal/i.test(query)) {
                reply = "Gunakan bentuk kalimat aktif yang konsisten, hindari kata santai seperti 'bantu-bantu', dan tambahkan terminologi kejuruan resmi.";
            } else if (/ats|kata kunci/i.test(query)) {
                reply = isSiswa ?
                    "Kata kunci ATS yang direkomendasikan untuk TKJ: FTTH, OTDR, Mikrotik MTCNA, Cisco Packet Tracer, troubleshooting jaringan, SLA 95%." :
                    "Kata kunci ATS yang direkomendasikan untuk AKL: rekonsiliasi kas, jurnal umum, Accurate 5, SAP, PPh 21, accounts payable.";
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
        }, 800);
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

    async function saveCv() {
        const btn = document.getElementById('btnSaveCv');
        const raw = document.getElementById('rawMarkdownTextarea')?.value || buildMarkdownFromForm();

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        if (btn) {
            btn.disabled = true;
            btn.innerHTML = `<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i> <span>Menyimpan…</span>`;
            lucide.createIcons();
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
                    markdown: raw
                })
            });

            const data = await res.json();

            if (!res.ok) {
                showToast(data.message || 'Gagal menyimpan CV ke server.');
                return;
            }

            showToast(data.message || 'Perubahan CV Anda berhasil disimpan ke database!');
        } catch (err) {
            showToast('Terjadi kesalahan jaringan saat menyimpan CV.');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = `<i data-lucide="save" class="w-3.5 h-3.5"></i> <span>Simpan</span>`;
                lucide.createIcons();
            }
        }
    }
</script>
@endpush
