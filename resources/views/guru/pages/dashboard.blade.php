@extends('guru.master')

@section('title', 'Dashboard Guru Pembimbing PKL - BKK SMK Plus Pelita Nusantara')

@section('content')
<div class="space-y-6 fade-up">
    <!-- Header Dashboard -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-brand-ink/10">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-navy tracking-tight">Portal Guru Pembimbing PKL</h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-300">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span>
                    <span>Monitoring & Persetujuan Aktif</span>
                </span>
            </div>
            <p class="text-xs sm:text-sm text-muted mt-1">
                Selamat datang, <strong>{{ $authUser['nama_lengkap'] ?? 'Bpk/Ibu Guru' }}</strong>. Tinjau CV siswa, berikan persetujuan (Accept) PKL, dan pantau log jurnal harian siswa.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('bkk.guru.pengajuan.index') }}" class="inline-flex items-center gap-2 h-10 px-4 rounded-full bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold shadow-sm transition-all cursor-pointer">
                <i data-lucide="check-circle" class="w-4 h-4"></i>
                <span>Persetujuan PKL</span>
                @if(($stats['pengajuan_menunggu'] ?? 0) > 0)
                    <span class="px-2 py-0.5 rounded-full bg-white text-emerald-800 text-[10px] font-bold">{{ $stats['pengajuan_menunggu'] }}</span>
                @endif
            </a>

            <a href="{{ route('bkk.guru.siswa.index') }}" class="inline-flex items-center gap-2 h-10 px-4 rounded-full bg-white hover:bg-brand-paper border border-brand-ink/15 text-navy text-xs font-semibold shadow-sm transition-all cursor-pointer">
                <i data-lucide="file-text" class="w-4 h-4 text-emerald-700"></i>
                <span>Lihat Semua CV Siswa</span>
            </a>
        </div>
    </div>

    <!-- Metrik Statistik Utama -->
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
        <!-- PKL Berjalan -->
        <div class="p-4 rounded-2xl bg-white border border-brand-ink/10 shadow-xs">
            <div class="flex items-center justify-between text-muted">
                <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">PKL Berjalan</span>
                <i data-lucide="activity" class="w-4 h-4 text-emerald-600"></i>
            </div>
            <div class="text-2xl font-bold text-navy mt-1.5">{{ number_format($stats['pkl_berjalan'] ?? 0) }}</div>
            <div class="text-[10px] text-muted mt-0.5">Siswa aktif industri</div>
        </div>

        <!-- Pengajuan Menunggu (Actionable Highlight) -->
        <a href="{{ route('bkk.guru.pengajuan.index') }}" class="p-4 rounded-2xl bg-amber-50/70 border border-amber-300 hover:bg-amber-100/70 transition-all shadow-xs block">
            <div class="flex items-center justify-between text-amber-800">
                <span class="text-[10px] font-bold uppercase tracking-wider">Perlu Accept</span>
                <span class="w-2 h-2 rounded-full bg-amber-500 animate-ping"></span>
            </div>
            <div class="text-2xl font-bold text-amber-900 mt-1.5">{{ number_format($stats['pengajuan_menunggu'] ?? 0) }}</div>
            <div class="text-[10px] text-amber-700 mt-0.5 font-medium">Pengajuan PKL</div>
        </a>

        <!-- Lamaran PKL Industri -->
        <a href="{{ route('bkk.guru.pengajuan.index', ['tab' => 'lamaran']) }}" class="p-4 rounded-2xl bg-white border border-brand-ink/10 hover:border-emerald-300 transition-all shadow-xs block">
            <div class="flex items-center justify-between text-muted">
                <span class="text-[10px] font-bold uppercase tracking-wider text-navy">Lamaran Mitra</span>
                <i data-lucide="building-2" class="w-4 h-4 text-muted"></i>
            </div>
            <div class="text-2xl font-bold text-navy mt-1.5">{{ number_format($stats['lamaran_pkl_menunggu'] ?? 0) }}</div>
            <div class="text-[10px] text-muted mt-0.5">Menunggu respon</div>
        </a>

        <!-- CV Perlu Review -->
        <a href="{{ route('bkk.guru.siswa.index', ['status_cv' => 'Belum Direview']) }}" class="p-4 rounded-2xl bg-white border border-brand-ink/10 hover:border-emerald-300 transition-all shadow-xs block">
            <div class="flex items-center justify-between text-muted">
                <span class="text-[10px] font-bold uppercase tracking-wider text-purple-700">Review CV</span>
                <i data-lucide="file-check" class="w-4 h-4 text-purple-600"></i>
            </div>
            <div class="text-2xl font-bold text-purple-900 mt-1.5">{{ number_format($stats['cv_perlu_review'] ?? 0) }}</div>
            <div class="text-[10px] text-muted mt-0.5">CV belum direview</div>
        </a>

        <!-- Jurnal Menunggu -->
        <a href="{{ route('bkk.guru.jurnal.index') }}" class="p-4 rounded-2xl bg-white border border-brand-ink/10 hover:border-emerald-300 transition-all shadow-xs block">
            <div class="flex items-center justify-between text-muted">
                <span class="text-[10px] font-bold uppercase tracking-wider text-blue-700">Log Jurnal</span>
                <i data-lucide="notebook-pen" class="w-4 h-4 text-blue-600"></i>
            </div>
            <div class="text-2xl font-bold text-blue-900 mt-1.5">{{ number_format($stats['jurnal_menunggu'] ?? 0) }}</div>
            <div class="text-[10px] text-muted mt-0.5">Antrean validasi</div>
        </a>

        <!-- Laporan Ditinjau -->
        <a href="{{ route('bkk.guru.laporan.index') }}" class="p-4 rounded-2xl bg-white border border-brand-ink/10 hover:border-emerald-300 transition-all shadow-xs block">
            <div class="flex items-center justify-between text-muted">
                <span class="text-[10px] font-bold uppercase tracking-wider text-teal-700">Naskah Akhir</span>
                <i data-lucide="book-open" class="w-4 h-4 text-teal-600"></i>
            </div>
            <div class="text-2xl font-bold text-teal-900 mt-1.5">{{ number_format($stats['laporan_ditinjau'] ?? 0) }}</div>
            <div class="text-[10px] text-muted mt-0.5">Draf bab akhir</div>
        </a>

        <!-- PKL Selesai -->
        <div class="p-4 rounded-2xl bg-white border border-brand-ink/10 shadow-xs">
            <div class="flex items-center justify-between text-muted">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-600">PKL Tuntas</span>
                <i data-lucide="award" class="w-4 h-4 text-slate-500"></i>
            </div>
            <div class="text-2xl font-bold text-slate-800 mt-1.5">{{ number_format($stats['pkl_selesai'] ?? 0) }}</div>
            <div class="text-[10px] text-muted mt-0.5">Penilaian selesai</div>
        </div>
    </div>

    <!-- Main Section: Dua Kolom Utama (Pengajuan PKL & Review CV) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Kolom Kiri (7 Cols): Pengajuan PKL Menunggu Persetujuan Guru -->
        <div class="lg:col-span-7 space-y-6">
            <div class="bg-white rounded-3xl border border-brand-ink/10 shadow-sm p-5 sm:p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-800 grid place-items-center">
                            <i data-lucide="check-check" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-navy">Pengajuan PKL Menunggu Persetujuan</h2>
                            <p class="text-xs text-muted">Permohonan PKL siswa yang memerlukan tindakan persetujuan (Accept) dari Guru.</p>
                        </div>
                    </div>
                    <a href="{{ route('bkk.guru.pengajuan.index') }}" class="text-xs font-semibold text-emerald-700 hover:text-emerald-800">Lihat Semua →</a>
                </div>

                @if($pendingPengajuan->isEmpty())
                    <div class="p-8 text-center rounded-2xl bg-brand-paper border border-dashed border-brand-ink/15 text-muted space-y-2">
                        <i data-lucide="check-circle-2" class="w-8 h-8 mx-auto text-emerald-600"></i>
                        <p class="text-xs font-medium">Semua pengajuan PKL siswa telah ditinjau dan disetujui!</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($pendingPengajuan as $p)
                            @php
                                $siswa = $p->siswa;
                                $cv = $siswa?->mainResume;
                            @endphp
                            <div class="p-4 rounded-2xl border border-brand-ink/10 bg-canvas hover:border-emerald-300 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="min-w-0 space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-sm text-navy truncate">{{ $siswa->nis ?? '-' }} — {{ $siswa->jurusan ?? 'Jurusan' }}</span>
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                                            Menunggu Persetujuan
                                        </span>
                                    </div>
                                    <div class="text-xs text-muted flex flex-wrap items-center gap-x-3 gap-y-1">
                                        <span class="flex items-center gap-1">
                                            <i data-lucide="building-2" class="w-3.5 h-3.5 text-muted"></i>
                                            <strong>{{ $p->mitra->nama_perusahaan ?? 'Mitra Industri' }}</strong>
                                        </span>
                                        <span>Divisi: {{ $p->unit_kerja_divisi ?? '-' }}</span>
                                        <span>Periode: {{ $p->tanggal_mulai ? $p->tanggal_mulai->format('d M') : '-' }} s/d {{ $p->tanggal_selesai ? $p->tanggal_selesai->format('d M Y') : '-' }}</span>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2 shrink-0">
                                    <a href="{{ route('bkk.guru.siswa.cv', $p->siswa_id) }}" class="h-8 px-3 rounded-full border border-brand-ink/15 bg-white hover:bg-brand-paper text-navy text-xs font-semibold inline-flex items-center gap-1.5 transition-colors" title="Lihat Berkas CV Siswa">
                                        <i data-lucide="file-text" class="w-3.5 h-3.5 text-emerald-700"></i>
                                        <span>Lihat CV</span>
                                    </a>

                                    <form action="{{ route('bkk.guru.pengajuan.accept', $p->id) }}" method="POST" onsubmit="return confirm('Setujui (Accept) pengajuan PKL siswa ini? Status akan aktif menjadi BERJALAN.')">
                                        @csrf
                                        <button type="submit" class="h-8 px-3.5 rounded-full bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold inline-flex items-center gap-1.5 shadow-xs transition-colors cursor-pointer">
                                            <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                            <span>Accept PKL</span>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Antrean Log Jurnal & Naskah Akhir -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <!-- Jurnal Harian -->
                <div class="bg-white rounded-3xl border border-brand-ink/10 shadow-sm p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i data-lucide="notebook-pen" class="w-4 h-4 text-blue-700"></i>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-navy">Jurnal Menunggu Validasi</h3>
                        </div>
                        <a href="{{ route('bkk.guru.jurnal.index') }}" class="text-[11px] font-semibold text-blue-700 hover:underline">Semua</a>
                    </div>

                    @forelse($pendingJurnals as $j)
                        <div class="p-3 rounded-xl bg-canvas border border-brand-ink/10 text-xs space-y-1">
                            <div class="flex items-center justify-between font-semibold text-navy">
                                <span>{{ $j->penempatan->siswa->nis ?? 'NIS' }} · {{ $j->tanggal->format('d M Y') }}</span>
                                <span class="text-blue-700 font-bold">{{ $j->durasi_jam }} Jam</span>
                            </div>
                            <p class="text-[11px] text-muted line-clamp-2">{{ $j->aktivitas }}</p>
                        </div>
                    @empty
                        <div class="p-4 text-center text-xs text-muted">Tidak ada jurnal harian yang menunggu validasi.</div>
                    @endforelse
                </div>

                <!-- Naskah Laporan Akhir -->
                <div class="bg-white rounded-3xl border border-brand-ink/10 shadow-sm p-5 space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <i data-lucide="book-open" class="w-4 h-4 text-purple-700"></i>
                            <h3 class="text-xs font-bold uppercase tracking-wider text-navy">Review Naskah Bab</h3>
                        </div>
                        <a href="{{ route('bkk.guru.laporan.index') }}" class="text-[11px] font-semibold text-purple-700 hover:underline">Semua</a>
                    </div>

                    @forelse($pendingLaporans as $l)
                        <div class="p-3 rounded-xl bg-canvas border border-brand-ink/10 text-xs space-y-1">
                            <div class="flex items-center justify-between font-semibold text-navy">
                                <span>{{ $l->penempatan->siswa->nis ?? 'NIS' }}</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] bg-purple-100 text-purple-800">Ditinjau</span>
                            </div>
                            <div class="text-[11px] font-medium text-navy">{{ $l->judul_bab }}</div>
                        </div>
                    @empty
                        <div class="p-4 text-center text-xs text-muted">Tidak ada draf laporan yang menunggu review.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Kolom Kanan (5 Cols): CV Siswa Terbaru & Ringkasan Berkas -->
        <div class="lg:col-span-5 space-y-6">
            <div class="bg-white rounded-3xl border border-brand-ink/10 shadow-sm p-5 sm:p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full bg-purple-100 text-purple-800 grid place-items-center">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-navy">CV Siswa Terbaru</h2>
                            <p class="text-xs text-muted">Berkas dokumen PDF & ATS Markdown siswa bimbingan.</p>
                        </div>
                    </div>
                    <a href="{{ route('bkk.guru.siswa.index') }}" class="text-xs font-semibold text-purple-700 hover:text-purple-800">Semua →</a>
                </div>

                <div class="space-y-3">
                    @forelse($recentCvs as $cv)
                        @php
                            $siswa = $cv->profilSiswa;
                            $hasDoc = $cv->has_document_file;
                            $statusReview = $cv->status_review_guru ?? 'Belum Direview';
                            $badgeClass = match($statusReview) {
                                'Disetujui' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                'Perlu Revisi' => 'bg-red-100 text-red-800 border-red-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                        @endphp
                        <div class="p-3.5 rounded-2xl border border-brand-ink/10 bg-canvas/60 hover:bg-canvas transition-colors flex items-center justify-between gap-3">
                            <div class="min-w-0 space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-xs text-navy truncate">{{ $siswa->nis ?? '-' }} — {{ $siswa->jurusan ?? 'Siswa' }}</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $badgeClass }}">
                                        {{ $statusReview }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 text-[11px] text-muted">
                                    @if($hasDoc)
                                        <span class="inline-flex items-center gap-1 text-emerald-700 font-medium">
                                            <i data-lucide="file-check" class="w-3.5 h-3.5"></i> Berkas {{ strtoupper(pathinfo($cv->file_name, PATHINFO_EXTENSION)) }} ({{ $cv->file_size_formatted }})
                                        </span>
                                    @else
                                        <span class="text-muted">ATS Markdown Digital</span>
                                    @endif
                                    <span>· Skor: {{ $cv->score ?? 85 }}</span>
                                </div>
                            </div>

                            <a href="{{ route('bkk.guru.siswa.cv', $cv->user_id) }}" class="h-8 px-3 rounded-full bg-navy hover:bg-navy-dark text-white text-xs font-semibold inline-flex items-center gap-1 shrink-0 transition-colors">
                                <span>Tinjau</span>
                                <i data-lucide="arrow-right" class="w-3 h-3"></i>
                            </a>
                        </div>
                    @empty
                        <div class="p-6 text-center text-xs text-muted">Belum ada CV yang tersimpan di sistem.</div>
                    @endforelse
                </div>
            </div>

            <!-- Petunjuk Guru Pembimbing -->
            <div class="p-5 rounded-3xl bg-gradient-to-br from-emerald-900 to-teal-900 text-white space-y-3 shadow-sm">
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-emerald-300">
                    <i data-lucide="info" class="w-4 h-4"></i>
                    <span>Alur Pembimbingan PKL</span>
                </div>
                <ul class="text-xs text-emerald-100/90 space-y-2 list-disc list-inside">
                    <li><strong>Review CV:</strong> Pastikan data kompetensi, kontak, dan portofolio siswa memenuhi standar mitra IDUKA.</li>
                    <li><strong>Accept PKL:</strong> Berikan persetujuan permohonan PKL agar siswa resmi memiliki status BERJALAN di industri.</li>
                    <li><strong>Validasi Jurnal:</strong> Periksa log harian secara berkala untuk memenuhi target 640 jam standar SMK.</li>
                    <li><strong>Penilaian Akhir:</strong> Masukkan nilai akhir sekolah (0-100) dan selesaikan PKL siswa.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
