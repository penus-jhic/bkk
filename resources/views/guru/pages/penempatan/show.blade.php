@extends('guru.master')

@section('title', 'Detail Penempatan PKL Siswa - Portal Guru')

@php
    $siswa = $penempatan->siswa;
    $mitra = $penempatan->mitra;
    $cv = $siswa?->mainResume;
    $achieved = $penempatan->total_jam_tercapai ?? 0;
    $target = $penempatan->target_jam > 0 ? $penempatan->target_jam : 640;
    $pct = min(100, (int) round(($achieved / $target) * 100));
    $statusBadge = match($penempatan->status) {
        'BERJALAN' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
        'SELESAI' => 'bg-blue-100 text-blue-800 border-blue-300',
        'DIBATALKAN' => 'bg-red-100 text-red-800 border-red-300',
        default => 'bg-amber-100 text-amber-800 border-amber-300',
    };
@endphp

@section('content')
<div class="space-y-6 fade-up">
    <!-- Top Action Bar -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-brand-ink/10">
        <div class="flex items-center gap-3">
            <a href="{{ route('bkk.guru.pengajuan.index') }}" class="w-10 h-10 rounded-full border border-brand-ink/15 hover:bg-brand-paper grid place-items-center text-navy transition-colors shrink-0" title="Kembali ke Daftar Penempatan">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-bold text-navy tracking-tight">Detail Penempatan PKL</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $statusBadge }}">
                        {{ $penempatan->status }}
                    </span>
                    @if($penempatan->pengajuan_mandiri)
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-purple-100 text-purple-800">
                            Pengajuan Mandiri
                        </span>
                    @endif
                </div>
                <p class="text-xs sm:text-sm text-muted mt-0.5">
                    NIS: <strong>{{ $siswa->nis ?? '-' }}</strong> · {{ $siswa->jurusan ?? 'Jurusan' }} di <strong>{{ $mitra->nama_perusahaan ?? 'Mitra Industri' }}</strong>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if($cv)
                <a href="{{ route('bkk.guru.siswa.cv', $penempatan->siswa_id) }}" class="h-10 px-4 rounded-full border border-brand-ink/15 hover:bg-brand-paper text-navy text-xs font-semibold inline-flex items-center gap-1.5 transition-colors">
                    <i data-lucide="file-text" class="w-4 h-4 text-emerald-700"></i>
                    <span>Tinjau CV Siswa</span>
                </a>
            @endif

            @if($penempatan->status === 'BERJALAN')
                <form action="{{ route('bkk.guru.penempatan.selesaikan', $penempatan->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menyatakan penempatan PKL siswa ini resmi SELESAI?')">
                    @csrf
                    <button type="submit" class="h-10 px-4 rounded-full bg-blue-700 hover:bg-blue-800 text-white text-xs font-bold inline-flex items-center gap-1.5 shadow-xs transition-colors cursor-pointer">
                        <i data-lucide="award" class="w-4 h-4"></i>
                        <span>Selesaikan PKL</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Info Banner & Progress Jam -->
    <div class="p-5 sm:p-6 rounded-3xl bg-white border border-brand-ink/10 shadow-sm space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
            <div>
                <span class="text-muted block text-[10px] uppercase font-bold">Mitra IDUKA</span>
                <strong class="text-navy text-sm">{{ $mitra->nama_perusahaan ?? '-' }}</strong>
                <div class="text-[11px] text-muted mt-0.5">{{ $mitra->kota ?? 'Lokasi Perusahaan' }}</div>
            </div>

            <div>
                <span class="text-muted block text-[10px] uppercase font-bold">Divisi Penempatan</span>
                <strong class="text-navy text-sm">{{ $penempatan->unit_kerja_divisi ?? '-' }}</strong>
                <div class="text-[11px] text-muted mt-0.5">Mentor: {{ $penempatan->pembimbing_industri_nama ?? 'Mentor Lapangan' }}</div>
            </div>

            <div>
                <span class="text-muted block text-[10px] uppercase font-bold">Periode Pelaksanaan</span>
                <span class="font-semibold text-navy">{{ $penempatan->tanggal_mulai ? $penempatan->tanggal_mulai->format('d M Y') : '-' }}</span>
                <div class="text-muted text-[11px]">s/d {{ $penempatan->tanggal_selesai ? $penempatan->tanggal_selesai->format('d M Y') : '-' }}</div>
            </div>

            <div>
                <span class="text-muted block text-[10px] uppercase font-bold">Akumulasi Jam Kerja</span>
                <div class="flex items-baseline gap-1 mt-0.5">
                    <span class="text-lg font-bold text-navy">{{ $achieved }}</span>
                    <span class="text-xs text-muted">/ {{ $target }} Jam ({{ $pct }}%)</span>
                </div>
            </div>
        </div>

        <!-- Progress Bar -->
        <div class="w-full bg-canvas rounded-full h-3 overflow-hidden border border-brand-ink/10">
            <div class="bg-gradient-to-r from-emerald-600 to-teal-600 h-3 rounded-full transition-all duration-500" style="width: {{ $pct }}%"></div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
        <!-- Kolom Kiri: Penilaian Akhir Guru & Catatan Bimbingan (5 Cols) -->
        <div class="xl:col-span-5 space-y-6">
            <!-- Form Input Nilai Akhir Sekolah -->
            <div class="p-5 sm:p-6 bg-white rounded-3xl border border-brand-ink/10 shadow-sm space-y-4">
                <div class="flex items-center gap-2.5 border-b border-brand-ink/10 pb-3">
                    <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-800 grid place-items-center font-bold">
                        <i data-lucide="award" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-navy">Penilaian Akhir PKL Sekolah</h2>
                        <p class="text-[11px] text-muted">Nilai laporan, presentasi & kedisiplinan dari Guru.</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('bkk.guru.penempatan.nilai', $penempatan->id) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-navy mb-1.5">Nilai Akhir Sekolah (Skala 0 - 100) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input
                                type="number"
                                name="nilai_akhir_sekolah"
                                step="0.01"
                                min="0"
                                max="100"
                                required
                                value="{{ old('nilai_akhir_sekolah', $penempatan->nilai_akhir_sekolah) }}"
                                placeholder="Contoh: 88.50"
                                class="w-full h-11 px-4 rounded-xl bg-canvas border border-brand-ink/15 text-sm font-bold text-navy focus:bg-white focus:outline-none focus:border-blue-600 transition-all"
                            />
                            <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-xs font-bold text-muted">/ 100</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-navy mb-1.5">Catatan Evaluasi Guru Pembimbing</label>
                        <textarea
                            name="catatan_guru"
                            rows="3"
                            placeholder="Catatan kelebihan dan saran pengembangan karir siswa..."
                            class="w-full p-3 rounded-xl bg-canvas border border-brand-ink/15 text-xs text-navy focus:bg-white focus:outline-none focus:border-blue-600 leading-relaxed"
                        >{{ old('catatan_guru', $penempatan->catatan_guru) }}</textarea>
                    </div>

                    <button type="submit" class="w-full h-10 rounded-full bg-blue-700 hover:bg-blue-800 text-white text-xs font-bold shadow-xs transition-colors cursor-pointer inline-flex items-center justify-center gap-2">
                        <i data-lucide="check" class="w-4 h-4"></i>
                        <span>Simpan Nilai Akhir Sekolah</span>
                    </button>
                </form>
            </div>

            <!-- Ringkasan Status Nilai Industri & Berkas -->
            <div class="p-5 bg-white rounded-3xl border border-brand-ink/10 shadow-sm space-y-3 text-xs">
                <h3 class="font-bold text-navy text-xs uppercase tracking-wider">Rekapitulasi Nilai & Jam</h3>
                <div class="space-y-2">
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-canvas">
                        <span class="text-muted">Nilai Mentor Industri:</span>
                        <strong class="text-navy">{{ $penempatan->nilai_akhir_industri ? number_format($penempatan->nilai_akhir_industri, 2) : 'Belum Diinput Mitra' }}</strong>
                    </div>
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-canvas">
                        <span class="text-muted">Nilai Guru Sekolah:</span>
                        <strong class="text-blue-800">{{ $penempatan->nilai_akhir_sekolah ? number_format($penempatan->nilai_akhir_sekolah, 2) : 'Belum Diinput' }}</strong>
                    </div>
                    <div class="flex items-center justify-between p-2.5 rounded-xl bg-canvas">
                        <span class="text-muted">Total Jurnal Disetujui:</span>
                        <strong class="text-emerald-800">{{ $penempatan->jurnals->where('status', 'Disetujui')->count() }} Catatan</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Log Jurnal Siswa & Review Laporan (7 Cols) -->
        <div class="xl:col-span-7 space-y-6">
            <!-- Tab Laporan Akhir Siswa -->
            <div class="p-5 sm:p-6 bg-white rounded-3xl border border-brand-ink/10 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-brand-ink/10 pb-3">
                    <div class="flex items-center gap-2">
                        <i data-lucide="book-open" class="w-4 h-4 text-purple-700"></i>
                        <h2 class="text-sm font-bold text-navy">Naskah Laporan Akhir PKL</h2>
                    </div>
                    <span class="text-xs text-muted">Per Bab (I - V)</span>
                </div>

                <div class="space-y-2.5">
                    @forelse($penempatan->laporans as $lap)
                        @php
                            $lapBadge = match($lap->status) {
                                'Disetujui' => 'bg-emerald-100 text-emerald-800',
                                'Revisi' => 'bg-red-100 text-red-800',
                                'Ditinjau' => 'bg-purple-100 text-purple-800',
                                default => 'bg-slate-100 text-slate-600',
                            };
                        @endphp
                        <div class="p-3 rounded-2xl border border-brand-ink/10 bg-canvas flex items-center justify-between gap-3 text-xs">
                            <div class="min-w-0">
                                <div class="font-bold text-navy truncate">{{ $lap->judul_bab }}</div>
                                <div class="text-[11px] text-muted mt-0.5">Status: <span class="font-semibold {{ $lap->status === 'Disetujui' ? 'text-emerald-700' : '' }}">{{ $lap->status }}</span></div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                @if(!empty($lap->file_draft_url))
                                    <a href="{{ $lap->file_draft_url }}" target="_blank" class="h-7 px-2.5 rounded-full border border-brand-ink/15 hover:bg-white text-navy text-[11px] font-semibold inline-flex items-center gap-1">
                                        <i data-lucide="download" class="w-3 h-3 text-muted"></i>
                                        <span>Draf</span>
                                    </a>
                                @endif

                                <a href="{{ route('bkk.guru.laporan.index') }}" class="h-7 px-3 rounded-full bg-emerald-700 hover:bg-emerald-800 text-white text-[11px] font-semibold inline-flex items-center gap-1">
                                    <span>Review</span>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="p-6 text-center text-xs text-muted">Belum ada berkas bab laporan yang diunggah siswa.</div>
                    @endforelse
                </div>
            </div>

            <!-- Tab Riwayat Log Jurnal Terkini -->
            <div class="p-5 sm:p-6 bg-white rounded-3xl border border-brand-ink/10 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-brand-ink/10 pb-3">
                    <div class="flex items-center gap-2">
                        <i data-lucide="notebook-pen" class="w-4 h-4 text-blue-700"></i>
                        <h2 class="text-sm font-bold text-navy">Log Jurnal Harian Terkini</h2>
                    </div>
                    <a href="{{ route('bkk.guru.jurnal.index') }}" class="text-xs font-semibold text-blue-700 hover:underline">Semua Jurnal →</a>
                </div>

                <div class="space-y-3">
                    @forelse($penempatan->jurnals->take(5) as $jr)
                        <div class="p-3.5 rounded-2xl border border-brand-ink/10 bg-canvas text-xs space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-navy">{{ $jr->tanggal ? $jr->tanggal->format('d M Y') : '-' }}</span>
                                <span class="font-bold text-blue-800">{{ $jr->durasi_jam }} Jam ({{ $jr->status }})</span>
                            </div>
                            <p class="text-muted leading-relaxed text-[11px] line-clamp-2">{{ $jr->aktivitas }}</p>
                        </div>
                    @empty
                        <div class="p-6 text-center text-xs text-muted">Belum ada catatan jurnal yang diisi siswa.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
