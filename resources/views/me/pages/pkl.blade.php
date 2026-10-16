@extends('me.master')

@section('title', 'Status & Pengajuan PKL Siswa - BKK SMK Plus Pelita Nusantara')

@php
    $hasPkl = !empty($activePkl);
    $statusPkl = $activePkl->status ?? null;
    $isMenunggu = ($statusPkl === 'MENUNGGU_PERSETUJUAN');
    $isBerjalan = ($statusPkl === 'BERJALAN');
    $isSelesai = ($statusPkl === 'SELESAI');
    $isDibatalkan = ($statusPkl === 'DIBATALKAN');
    $statusBadge = match($statusPkl) {
        'BERJALAN' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
        'SELESAI' => 'bg-blue-100 text-blue-800 border-blue-300',
        'DIBATALKAN' => 'bg-red-100 text-red-800 border-red-300',
        default => 'bg-amber-100 text-amber-800 border-amber-300',
    };
    $achieved = $activePkl->total_jam_tercapai ?? 0;
    $target = $activePkl?->target_jam > 0 ? $activePkl->target_jam : 640;
    $pct = min(100, (int) round(($achieved / $target) * 100));
@endphp

@section('content')
<div class="space-y-6 fade-up">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-brand-ink/10">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-navy tracking-tight">Status & Pengajuan PKL Siswa</h1>
                @if($hasPkl)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $statusBadge }}">
                        {{ str_replace('_', ' ', $statusPkl) }}
                    </span>
                @else
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                        Belum Terdaftar PKL
                    </span>
                @endif
            </div>
            <p class="text-xs sm:text-sm text-muted mt-1">Pemantauan status penempatan magang industri, bimbingan guru sekolah, dan formulir permohonan PKL mandiri.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('bkk.me.cv') }}" class="inline-flex items-center gap-2 h-10 px-4 rounded-full border border-brand-ink/15 hover:bg-brand-paper text-navy text-xs font-semibold shadow-xs transition-colors">
                <i data-lucide="file-text" class="w-4 h-4 text-emerald-700"></i>
                <span>Kelola Berkas CV</span>
            </a>
            @if($isBerjalan)
                <a href="{{ route('bkk.me.jurnal') }}" class="inline-flex items-center gap-2 h-10 px-4 rounded-full bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold shadow-xs transition-colors">
                    <i data-lucide="notebook-pen" class="w-4 h-4"></i>
                    <span>Tulis Jurnal Harian</span>
                </a>
            @endif
        </div>
    </div>

    @if($hasPkl)
        <!-- Kartu Status Penempatan Aktif -->
        <div class="p-6 bg-white rounded-3xl border border-brand-ink/10 shadow-sm space-y-6">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 border-b border-brand-ink/10 pb-4">
                <div class="space-y-1">
                    <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">Mitra Industri Tempat PKL</div>
                    <h2 class="text-xl font-bold text-navy">{{ $activePkl->mitra->nama_perusahaan ?? 'Mitra Industri' }}</h2>
                    <p class="text-xs text-muted">{{ $activePkl->mitra->alamat_kantor ?? $activePkl->mitra->kota ?? 'Lokasi Perusahaan' }}</p>
                </div>

                <div class="text-right">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border {{ $statusBadge }}">
                        @if($isBerjalan)
                            <span class="w-2 h-2 rounded-full bg-emerald-600 animate-pulse"></span>
                            <span>Sedang Berjalan</span>
                        @elseif($isMenunggu)
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
                            <span>Menunggu Persetujuan Guru</span>
                        @elseif($isSelesai)
                            <i data-lucide="check-check" class="w-3.5 h-3.5 text-blue-700"></i>
                            <span>Resmi Tuntas</span>
                        @else
                            <span>{{ $statusPkl }}</span>
                        @endif
                    </span>
                </div>
            </div>

            <!-- Rincian Penempatan -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 p-4 rounded-2xl bg-canvas text-xs">
                <div>
                    <span class="text-muted block text-[10px] uppercase font-bold">Divisi Penempatan</span>
                    <strong class="text-navy text-sm">{{ $activePkl->unit_kerja_divisi ?? '-' }}</strong>
                    <div class="text-[11px] text-muted mt-0.5">Mentor: {{ $activePkl->pembimbing_industri_nama ?? 'Mentor Lapangan' }}</div>
                </div>

                <div>
                    <span class="text-muted block text-[10px] uppercase font-bold">Guru Pembimbing Sekolah</span>
                    <strong class="text-navy text-sm">Bpk/Ibu Guru Pembimbing</strong>
                    <div class="text-[11px] text-emerald-700 font-medium mt-0.5">SMK Plus Pelita Nusantara</div>
                </div>

                <div>
                    <span class="text-muted block text-[10px] uppercase font-bold">Periode Tanggal</span>
                    <span class="font-semibold text-navy">{{ $activePkl->tanggal_mulai ? $activePkl->tanggal_mulai->format('d M Y') : '-' }}</span>
                    <div class="text-[11px] text-muted">s/d {{ $activePkl->tanggal_selesai ? $activePkl->tanggal_selesai->format('d M Y') : '-' }}</div>
                </div>

                <div>
                    <span class="text-muted block text-[10px] uppercase font-bold">Akumulasi Jam Kerja</span>
                    <div class="flex items-baseline gap-1 mt-0.5">
                        <span class="text-lg font-bold text-navy">{{ $achieved }}</span>
                        <span class="text-xs text-muted">/ {{ $target }} Jam ({{ $pct }}%)</span>
                    </div>
                </div>
            </div>

            <!-- Progress Bar Akumulasi Jam -->
            <div class="space-y-1.5">
                <div class="flex items-center justify-between text-xs text-muted">
                    <span>Progress Jam Kerja Disetujui Guru</span>
                    <span class="font-bold text-navy">{{ $pct }}%</span>
                </div>
                <div class="w-full bg-canvas rounded-full h-3 overflow-hidden border border-brand-ink/10">
                    <div class="bg-gradient-to-r from-emerald-600 to-teal-600 h-3 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
                </div>
            </div>

            <!-- Feedback / Catatan dari Guru -->
            @if(!empty($activePkl->catatan_guru))
                <div class="p-4 rounded-2xl bg-brand-paper border border-brand-ink/10 text-xs space-y-1">
                    <div class="flex items-center gap-2 text-emerald-800 font-bold">
                        <i data-lucide="message-square" class="w-4 h-4 text-emerald-700"></i>
                        <span>Catatan Guru Pembimbing:</span>
                    </div>
                    <p class="text-navy leading-relaxed pl-6">{{ $activePkl->catatan_guru }}</p>
                </div>
            @endif

            <!-- Nilai Akhir jika sudah diinput -->
            @if($activePkl->nilai_akhir_sekolah !== null || $activePkl->nilai_akhir_industri !== null)
                <div class="p-4 rounded-2xl bg-blue-50/70 border border-blue-200 text-xs space-y-2">
                    <span class="font-bold text-blue-900 block uppercase tracking-wider text-[10px]">Nilai Akhir Evaluasi PKL</span>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <span class="text-muted block text-[10px]">Nilai Mentor Industri</span>
                            <span class="text-base font-bold text-navy">{{ $activePkl->nilai_akhir_industri ? number_format($activePkl->nilai_akhir_industri, 2) : 'Belum diisi' }}</span>
                        </div>
                        <div>
                            <span class="text-muted block text-[10px]">Nilai Guru Pembimbing Sekolah</span>
                            <span class="text-base font-bold text-blue-800">{{ $activePkl->nilai_akhir_sekolah ? number_format($activePkl->nilai_akhir_sekolah, 2) : 'Belum diisi' }}</span>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Quick Action Links -->
            <div class="flex flex-wrap items-center gap-3 pt-2">
                <a href="{{ route('bkk.me.jurnal') }}" class="h-9 px-4 rounded-full bg-navy hover:bg-navy-dark text-white text-xs font-semibold inline-flex items-center gap-1.5 transition-colors">
                    <i data-lucide="notebook-pen" class="w-3.5 h-3.5"></i>
                    <span>Buka Jurnal PKL</span>
                </a>
                <a href="{{ route('bkk.me.laporan') }}" class="h-9 px-4 rounded-full border border-brand-ink/15 hover:bg-brand-paper text-navy text-xs font-semibold inline-flex items-center gap-1.5 transition-colors">
                    <i data-lucide="book-open" class="w-3.5 h-3.5 text-muted"></i>
                    <span>Kelola Naskah Laporan Akhir</span>
                </a>
            </div>
        </div>
    @endif

    <!-- Form Pengajuan PKL Mandiri (Hanya jika belum memiliki penempatan berjalan) -->
    @if(!$isBerjalan && !$isSelesai)
        <div class="p-6 bg-white rounded-3xl border border-brand-ink/10 shadow-sm space-y-5">
            <div class="flex items-center gap-3 border-b border-brand-ink/10 pb-4">
                <div class="w-10 h-10 rounded-2xl bg-emerald-100 text-emerald-800 grid place-items-center font-bold">
                    <i data-lucide="send" class="w-5 h-5"></i>
                </div>
                <div>
                    <h2 class="text-base font-bold text-navy">Formulir Pengajuan Penempatan PKL Mandiri</h2>
                    <p class="text-xs text-muted">Ajukan tempat PKL mandiri atau rekomendasi baru untuk disetujui (Accepted) oleh Guru Pembimbing.</p>
                </div>
            </div>

            <form action="{{ route('bkk.me.pkl.pengajuan') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Pilihan Tipe Mitra -->
                <div>
                    <label class="block text-xs font-bold text-navy mb-1.5">Jenis Mitra PKL <span class="text-red-500">*</span></label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <label class="flex items-center gap-3 p-3.5 rounded-2xl border border-brand-ink/15 hover:bg-canvas cursor-pointer">
                            <input type="radio" name="tipe_mitra" value="TERDAFTAR" checked onchange="toggleMitraFields()" class="text-emerald-700 focus:ring-emerald-600" />
                            <div>
                                <strong class="text-xs text-navy block">Mitra IDUKA Terafiliasi</strong>
                                <span class="text-[10px] text-muted">Pilih perusahaan yang sudah bekerja sama resmi dengan BKK SMK</span>
                            </div>
                        </label>
                        <label class="flex items-center gap-3 p-3.5 rounded-2xl border border-brand-ink/15 hover:bg-canvas cursor-pointer">
                            <input type="radio" name="tipe_mitra" value="MANDIRI" onchange="toggleMitraFields()" class="text-emerald-700 focus:ring-emerald-600" />
                            <div>
                                <strong class="text-xs text-navy block">Tempat PKL Baru (Mandiri)</strong>
                                <span class="text-[10px] text-muted">Rekomendasi perusahaan dari siswa atau orang tua</span>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Dropdown Mitra Terdaftar -->
                <div id="sectionMitraTerdaftar">
                    <label class="block text-xs font-bold text-navy mb-1.5">Pilih Perusahaan Mitra IDUKA <span class="text-red-500">*</span></label>
                    <select name="mitra_id" class="w-full h-11 px-4 rounded-xl bg-canvas border border-brand-ink/15 text-xs text-navy focus:bg-white focus:outline-none focus:border-emerald-600">
                        <option value="">-- Pilih Perusahaan Industri --</option>
                        @foreach($mitras as $m)
                            <option value="{{ $m->id }}">{{ $m->nama_perusahaan }} ({{ $m->kota ?? 'Jawa Barat' }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Input Mitra Mandiri (Hidden by default) -->
                <div id="sectionMitraMandiri" class="hidden space-y-3 p-4 rounded-2xl bg-canvas border border-brand-ink/10">
                    <div>
                        <label class="block text-xs font-bold text-navy mb-1">Nama Perusahaan / Instansi Mandiri <span class="text-red-500">*</span></label>
                        <input type="text" name="nama_perusahaan_mandiri" placeholder="Contoh: PT Kreasi Solusi Informatika" class="w-full h-10 px-3 rounded-xl bg-white border border-brand-ink/15 text-xs text-navy focus:outline-none focus:border-emerald-600" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-navy mb-1">Kota Domisili Kantor</label>
                            <input type="text" name="kota_mandiri" placeholder="Contoh: Bogor / Jakarta" class="w-full h-10 px-3 rounded-xl bg-white border border-brand-ink/15 text-xs text-navy focus:outline-none focus:border-emerald-600" />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-navy mb-1">Alamat Kantor Lengkap</label>
                            <input type="text" name="alamat_mandiri" placeholder="Nama jalan, gedung, nomor..." class="w-full h-10 px-3 rounded-xl bg-white border border-brand-ink/15 text-xs text-navy focus:outline-none focus:border-emerald-600" />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-navy mb-1.5">Divisi / Bidang Kerja Penempatan <span class="text-red-500">*</span></label>
                        <input type="text" name="unit_kerja_divisi" required placeholder="Contoh: IT Support / Software Engineering / DKV Studio" class="w-full h-10 px-3 rounded-xl bg-canvas border border-brand-ink/15 text-xs text-navy focus:bg-white focus:outline-none focus:border-emerald-600" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-navy mb-1.5">Nama Mentor Lapangan / PIC</label>
                        <input type="text" name="pembimbing_industri_nama" placeholder="Contoh: Bpk. Agus Setiawan (Opsional)" class="w-full h-10 px-3 rounded-xl bg-canvas border border-brand-ink/15 text-xs text-navy focus:bg-white focus:outline-none focus:border-emerald-600" />
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-navy mb-1.5">Tanggal Mulai PKL <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal_mulai" required class="w-full h-10 px-3 rounded-xl bg-canvas border border-brand-ink/15 text-xs text-navy focus:bg-white focus:outline-none focus:border-emerald-600 cursor-pointer" />
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-navy mb-1.5">Tanggal Selesai PKL <span class="text-red-500">*</span></label>
                        <input type="date" name="tanggal_selesai" required class="w-full h-10 px-3 rounded-xl bg-canvas border border-brand-ink/15 text-xs text-navy focus:bg-white focus:outline-none focus:border-emerald-600 cursor-pointer" />
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-navy mb-1.5">Catatan Tambahan untuk Guru Pembimbing</label>
                    <textarea name="catatan_siswa" rows="2" placeholder="Sertakan informasi pendukung jika ada..." class="w-full p-3 rounded-xl bg-canvas border border-brand-ink/15 text-xs text-navy focus:bg-white focus:outline-none focus:border-emerald-600 leading-relaxed"></textarea>
                </div>

                <div class="pt-2">
                    <button type="submit" class="h-11 px-6 rounded-full bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-xs transition-colors cursor-pointer inline-flex items-center gap-2">
                        <i data-lucide="send" class="w-4 h-4"></i>
                        <span>Kirim Pengajuan PKL ke Guru Pembimbing</span>
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>

<script>
    function toggleMitraFields() {
        const isMandiri = document.querySelector('input[name="tipe_mitra"][value="MANDIRI"]').checked;
        const terdaftarSection = document.getElementById('sectionMitraTerdaftar');
        const mandiriSection = document.getElementById('sectionMitraMandiri');

        if (isMandiri) {
            terdaftarSection.classList.add('hidden');
            mandiriSection.classList.remove('hidden');
        } else {
            terdaftarSection.classList.remove('hidden');
            mandiriSection.classList.add('hidden');
        }
    }
</script>
@endsection
