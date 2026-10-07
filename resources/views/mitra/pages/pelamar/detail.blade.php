@extends('mitra.master')

@section('title', 'Detail Pelamar: ' . $applicant['nama'] . ' - Mitra IDUKA')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb & Top Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-muted mb-1">
                <a href="{{ route('bkk.mitra.dashboard') }}" class="hover:text-navy">Dashboard</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <a href="{{ route('bkk.mitra.lowongan.index') }}" class="hover:text-navy">Lowongan</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <a href="{{ route('bkk.mitra.pelamar.index', $vacancy['id']) }}" class="hover:text-navy truncate max-w-[150px] sm:max-w-none">Pelamar: {{ $vacancy['title'] }}</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-navy font-semibold">{{ $applicant['nama'] }}</span>
            </div>
            <h1 class="text-2xl font-headline font-bold text-navy truncate">
                Tinjauan CV & Rekam Jejak Pelamar
            </h1>
            <p class="text-xs text-muted mt-0.5">
                Posisi: <strong class="text-navy">{{ $vacancy['title'] }}</strong> ({{ $vacancy['tipe_badge'] }})
            </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('bkk.mitra.pelamar.index', $vacancy['id']) }}" class="px-4 py-2 rounded-full border border-line hover:bg-canvas text-navy text-xs font-semibold transition-colors flex items-center gap-1.5">
                <i data-lucide="arrow-left" class="w-3.5 h-3.5"></i>
                <span>Kembali ke Daftar Pelamar</span>
            </a>
        </div>
    </div>

    <!-- Candidate Profile Banner -->
    <div class="google-card p-6">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-start sm:items-center gap-4">
                <div class="w-16 h-16 rounded-2xl bg-navy text-white text-2xl font-bold flex items-center justify-center shrink-0 shadow-sm">
                    {{ substr($applicant['nama'], 0, 1) }}
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-xl font-headline font-bold text-navy">{{ $applicant['nama'] }}</h2>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                            <i data-lucide="sparkles" class="w-3 h-3 mr-1 text-emerald-600"></i> AI Match: {{ $applicant['cv_score'] }}%
                        </span>
                    </div>
                    <div class="text-xs text-muted mt-1 flex flex-wrap items-center gap-x-4 gap-y-1">
                        <span>{{ $applicant['status_pendidikan'] }}</span>
                        <span>•</span>
                        <span>Jurusan: <strong class="text-navy font-semibold">{{ $applicant['jurusan'] }}</strong></span>
                        <span>•</span>
                        <span>NIS: <strong class="text-navy font-semibold">{{ $applicant['nis_nisn'] }}</strong></span>
                    </div>
                </div>
            </div>

            <!-- Current Status Chip -->
            <div class="flex flex-col sm:items-end gap-1.5 pt-3 sm:pt-0 border-t sm:border-t-0 border-line">
                <span class="text-[11px] text-muted font-medium uppercase tracking-wider">Status Seleksi Saat Ini</span>
                @php
                    $badgeColor = match($applicant['status']) {
                        'Dipanggil Interview' => 'bg-amber-50 text-amber-800 border-amber-300',
                        'Sedang Ditinjau' => 'bg-blue-50 text-blue-800 border-blue-300',
                        'Diterima' => 'bg-emerald-50 text-emerald-800 border-emerald-300',
                        'Ditolak' => 'bg-red-50 text-red-800 border-red-300',
                        default => 'bg-gray-100 text-gray-800 border-gray-300',
                    };
                @endphp
                <span class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-sm font-bold border {{ $badgeColor }}">
                    <span class="w-2 h-2 rounded-full bg-current"></span>
                    {{ $applicant['status'] }}
                </span>
                <span class="text-[11px] text-muted">Melamar pada {{ \Carbon\Carbon::parse($applicant['tanggal_melamar'])->translatedFormat('d F Y') }}</span>
            </div>
        </div>
    </div>

    <!-- Main Content: Left CV Document Preview vs Right Status Updater -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Document Preview CV (2 Cols) -->
        <div class="lg:col-span-2 space-y-4">
            <div class="google-card p-6 sm:p-8 bg-white shadow-xs">
                <div class="flex items-center justify-between pb-4 mb-4 border-b border-line">
                    <div class="flex items-center gap-2">
                        <i data-lucide="file-check" class="w-5 h-5 text-navy"></i>
                        <span class="font-headline font-bold text-navy text-sm uppercase tracking-wider">Berkas CV Siswa Terverifikasi BKK</span>
                    </div>
                    <button onclick="window.print()" class="text-xs text-muted hover:text-navy font-semibold flex items-center gap-1">
                        <i data-lucide="printer" class="w-3.5 h-3.5"></i> Cetak / Simpan PDF
                    </button>
                </div>

                <!-- Markdown Content Container Styled as Professional Resume -->
                <div class="md-cv prose max-w-none text-navy">
                    {!! \Illuminate\Support\Str::markdown($cvMarkdown, [
                        'html_input' => 'strip',
                        'allow_unsafe_links' => false,
                    ]) !!}
                </div>

                <!-- Skill Tags Box -->
                <div class="mt-8 pt-6 border-t border-line">
                    <h4 class="text-xs font-bold text-navy uppercase tracking-wider mb-2.5">
                        Keahlian & Penguasaan Teknologi
                    </h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($applicant['skills'] as $skill)
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-canvas text-navy border border-line">
                                {{ $skill }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Status Updater & Contact Details (1 Col) -->
        <div class="space-y-4">
            <!-- Card 1: Form Update Status Seleksi (According to Sitemap) -->
            <div class="google-card p-5">
                <h3 class="font-headline font-bold text-navy text-base mb-1 flex items-center gap-2">
                    <i data-lucide="user-check" class="w-4 h-4 text-maroon"></i>
                    Update Status Seleksi
                </h3>
                <p class="text-xs text-muted mb-4">
                    Ubah tahapan seleksi siswa/alumni ini. Status baru akan otomatis tersinkronisasi ke portal siswa BKK.
                </p>

                <form action="{{ route('bkk.mitra.pelamar.updateStatus', [$vacancy['id'], $applicant['id']]) }}" method="POST" class="space-y-4">
                    @csrf
                    <input type="hidden" name="nama" value="{{ $applicant['nama'] }}">

                    <div class="space-y-2">
                        <label class="text-xs font-semibold text-navy">Pilih Status Seleksi:</label>
                        <div class="space-y-1.5">
                            @foreach(['Sedang Ditinjau', 'Dipanggil Interview', 'Diterima', 'Ditolak'] as $statusOption)
                                <label class="flex items-center gap-3 p-2.5 rounded-xl border border-line hover:bg-canvas cursor-pointer transition-colors text-xs font-medium text-navy {{ $applicant['status'] === $statusOption ? 'bg-navy/5 border-navy font-semibold' : '' }}">
                                    <input
                                        type="radio"
                                        name="status"
                                        value="{{ $statusOption }}"
                                        {{ $applicant['status'] === $statusOption ? 'checked' : '' }}
                                        class="text-navy focus:ring-navy"
                                    />
                                    <span>{{ $statusOption }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label for="catatan_seleksi" class="text-xs font-semibold text-navy">Catatan Seleksi / Feedback HRD</label>
                        <textarea
                            name="catatan_seleksi"
                            id="catatan_seleksi"
                            rows="3"
                            placeholder="Tuliskan catatan teknis atau arahan sesi wawancara untuk siswa..."
                            class="w-full px-3.5 py-2.5 rounded-xl border border-line bg-canvas text-navy text-xs focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                        >{{ $applicant['catatan_seleksi'] ?? '' }}</textarea>
                    </div>

                    <button type="submit" class="w-full py-2.5 rounded-full bg-navy hover:bg-navy-light text-white font-semibold text-xs shadow-sm transition-all flex items-center justify-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4"></i>
                        <span>Simpan Status Seleksi</span>
                    </button>
                </form>
            </div>

            <!-- Card 2: Informasi Kontak Kandidat -->
            <div class="google-card p-5 space-y-3">
                <h4 class="font-headline font-bold text-navy text-sm flex items-center gap-2">
                    <i data-lucide="contact" class="w-4 h-4 text-navy"></i>
                    Informasi Kontak Siswa
                </h4>
                <div class="space-y-2.5 text-xs">
                    <div class="flex items-center gap-2.5 text-muted">
                        <i data-lucide="mail" class="w-4 h-4 text-navy shrink-0"></i>
                        <a href="mailto:{{ $applicant['email'] }}" class="text-navy font-medium hover:underline truncate">
                            {{ $applicant['email'] }}
                        </a>
                    </div>
                    <div class="flex items-center gap-2.5 text-muted">
                        <i data-lucide="phone" class="w-4 h-4 text-navy shrink-0"></i>
                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $applicant['no_hp']) }}" target="_blank" class="text-navy font-medium hover:underline">
                            {{ $applicant['no_hp'] }}
                        </a>
                    </div>
                    <div class="flex items-center gap-2.5 text-muted">
                        <i data-lucide="map-pin" class="w-4 h-4 text-navy shrink-0"></i>
                        <span class="text-navy font-medium">{{ $applicant['lokasi'] }}</span>
                    </div>
                    <div class="flex items-center gap-2.5 text-muted">
                        <i data-lucide="globe" class="w-4 h-4 text-navy shrink-0"></i>
                        <a href="{{ $applicant['portfolio_url'] }}" target="_blank" class="text-maroon font-semibold hover:underline truncate">
                            {{ $applicant['portfolio_url'] }}
                        </a>
                    </div>
                </div>

                <div class="pt-3 border-t border-line">
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $applicant['no_hp']) }}?text=Halo%20{{ urlencode($applicant['nama']) }},%20kami%20dari%20tim%20HRD%20{{ urlencode($mitra->nama_perusahaan) }}%20(Mitra%20BKK%20SMK%20Penus)." target="_blank" class="w-full py-2 rounded-full border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-semibold transition-colors flex items-center justify-center gap-2">
                        <i data-lucide="message-circle" class="w-4 h-4"></i>
                        <span>Hubungi via WhatsApp</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
