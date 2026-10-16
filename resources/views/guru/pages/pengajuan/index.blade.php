@extends('guru.master')

@section('title', 'Persetujuan (Accept) Pengajuan PKL - Portal Guru')

@section('content')
<div class="space-y-6 fade-up">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-brand-ink/10">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-navy tracking-tight">Persetujuan (Accept) PKL Siswa</h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                    {{ $counts['menunggu'] }} Pengajuan Perlu Verifikasi
                </span>
            </div>
            <p class="text-xs sm:text-sm text-muted mt-1">
                Tinjau permohonan PKL siswa mandiri maupun lamaran lowongan mitra IDUKA. Berikan persetujuan (Accept) agar siswa resmi berstatus aktif PKL.
            </p>
        </div>

        <a href="{{ route('bkk.guru.siswa.index') }}" class="inline-flex items-center gap-2 h-10 px-4 rounded-full bg-white hover:bg-brand-paper border border-brand-ink/15 text-navy text-xs font-semibold shadow-sm transition-all cursor-pointer">
            <i data-lucide="file-search" class="w-4 h-4 text-emerald-700"></i>
            <span>Tinjau Berkas CV Siswa</span>
        </a>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex items-center gap-2 border-b border-brand-ink/10 pb-px overflow-x-auto">
        <a href="{{ route('bkk.guru.pengajuan.index', ['tab' => 'menunggu']) }}" 
           class="px-5 py-3 text-xs font-bold transition-all border-b-2 flex items-center gap-2 whitespace-nowrap {{ ($tab ?? 'menunggu') === 'menunggu' ? 'border-emerald-700 text-emerald-900 bg-emerald-50/50 rounded-t-xl' : 'border-transparent text-muted hover:text-navy' }}">
            <i data-lucide="clock" class="w-4 h-4"></i>
            <span>Pengajuan PKL Siswa</span>
            @if($counts['menunggu'] > 0)
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-200 text-amber-900">
                    {{ $counts['menunggu'] }}
                </span>
            @endif
        </a>

        <a href="{{ route('bkk.guru.pengajuan.index', ['tab' => 'lamaran']) }}" 
           class="px-5 py-3 text-xs font-bold transition-all border-b-2 flex items-center gap-2 whitespace-nowrap {{ ($tab ?? '') === 'lamaran' ? 'border-emerald-700 text-emerald-900 bg-emerald-50/50 rounded-t-xl' : 'border-transparent text-muted hover:text-navy' }}">
            <i data-lucide="briefcase" class="w-4 h-4"></i>
            <span>Lamaran Lowongan PKL Industri</span>
            @if($counts['lamaran'] > 0)
                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-blue-100 text-blue-800">
                    {{ $counts['lamaran'] }}
                </span>
            @endif
        </a>

        <a href="{{ route('bkk.guru.pengajuan.index', ['tab' => 'berjalan']) }}" 
           class="px-5 py-3 text-xs font-bold transition-all border-b-2 flex items-center gap-2 whitespace-nowrap {{ ($tab ?? '') === 'berjalan' ? 'border-emerald-700 text-emerald-900 bg-emerald-50/50 rounded-t-xl' : 'border-transparent text-muted hover:text-navy' }}">
            <i data-lucide="activity" class="w-4 h-4"></i>
            <span>Penempatan Berjalan</span>
            <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-slate-100 text-slate-700">
                {{ $counts['berjalan'] }}
            </span>
        </a>

        <a href="{{ route('bkk.guru.pengajuan.index', ['tab' => 'selesai']) }}" 
           class="px-5 py-3 text-xs font-bold transition-all border-b-2 flex items-center gap-2 whitespace-nowrap {{ ($tab ?? '') === 'selesai' ? 'border-emerald-700 text-emerald-900 bg-emerald-50/50 rounded-t-xl' : 'border-transparent text-muted hover:text-navy' }}">
            <i data-lucide="check-check" class="w-4 h-4"></i>
            <span>Riwayat Selesai</span>
            <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-slate-100 text-slate-700">
                {{ $counts['selesai'] }}
            </span>
        </a>
    </div>

    <!-- TAB 1: Pengajuan PKL Siswa yang Menunggu Persetujuan -->
    @if(($tab ?? 'menunggu') === 'menunggu')
        <div class="space-y-4">
            @forelse($pengajuanMenunggu as $p)
                @php
                    $siswa = $p->siswa;
                    $cv = $siswa?->mainResume;
                    $hasDoc = $cv?->has_document_file;
                @endphp
                <div class="p-5 sm:p-6 rounded-3xl bg-white border border-brand-ink/10 shadow-sm space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4 border-b border-brand-ink/10 pb-4">
                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-bold text-base text-navy">NIS: {{ $siswa->nis ?? '-' }} — {{ $siswa->jurusan ?? 'Jurusan' }}</h3>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                                    Menunggu Persetujuan (Pending Accept)
                                </span>
                                @if($p->pengajuan_mandiri)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-purple-100 text-purple-800">
                                        Mandiri Siswa
                                    </span>
                                @endif
                            </div>
                            <div class="text-xs text-muted flex flex-wrap items-center gap-x-4 gap-y-1">
                                <span>Kelas: <strong>{{ $siswa->kelas ?? 'Kelas XII' }}</strong></span>
                                <span>Angkatan: {{ $siswa->angkatan ?? '-' }}</span>
                                <span>Diajukan pada: {{ $p->created_at->format('d M Y, H:i') }} WIB</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ route('bkk.guru.siswa.cv', $p->siswa_id) }}" class="h-9 px-3.5 rounded-full border border-brand-ink/15 hover:bg-brand-paper text-navy text-xs font-semibold inline-flex items-center gap-1.5 transition-colors">
                                <i data-lucide="file-text" class="w-4 h-4 text-emerald-700"></i>
                                <span>Buka CV Siswa</span>
                                @if($hasDoc)
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                @endif
                            </a>
                        </div>
                    </div>

                    <!-- Detail Rencana PKL -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 p-4 rounded-2xl bg-canvas text-xs">
                        <div>
                            <span class="text-muted block text-[10px] uppercase font-bold">Mitra / Perusahaan PKL</span>
                            <strong class="text-navy text-sm">{{ $p->mitra->nama_perusahaan ?? 'Mitra Industri' }}</strong>
                            <div class="text-[11px] text-muted mt-0.5">{{ $p->mitra->kota ?? 'Lokasi Perusahaan' }}</div>
                        </div>

                        <div>
                            <span class="text-muted block text-[10px] uppercase font-bold">Divisi & Unit Kerja</span>
                            <span class="font-semibold text-navy text-sm">{{ $p->unit_kerja_divisi ?? '-' }}</span>
                            <div class="text-[11px] text-muted mt-0.5">Mentor: {{ $p->pembimbing_industri_nama ?? 'Mentor Lapangan' }}</div>
                        </div>

                        <div>
                            <span class="text-muted block text-[10px] uppercase font-bold">Periode Pelaksanaan</span>
                            <span class="font-semibold text-navy">{{ $p->tanggal_mulai ? $p->tanggal_mulai->format('d M Y') : '-' }}</span>
                            <div class="text-muted text-[11px]">s/d {{ $p->tanggal_selesai ? $p->tanggal_selesai->format('d M Y') : '-' }}</div>
                        </div>

                        <div>
                            <span class="text-muted block text-[10px] uppercase font-bold">Target Jam Standar</span>
                            <span class="font-bold text-navy text-sm">{{ $p->target_jam ?? 640 }} Jam</span>
                            <div class="text-emerald-700 text-[11px] font-medium">Kurikulum BKK SMK</div>
                        </div>
                    </div>

                    @if(!empty($p->catatan_guru))
                        <div class="p-3 rounded-xl bg-amber-50 text-amber-900 border border-amber-200 text-xs">
                            <strong>Catatan Pengajuan:</strong> {{ $p->catatan_guru }}
                        </div>
                    @endif

                    <!-- Action Bar Guru: ACCEPT vs REJECT -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-3 border-t border-brand-ink/10">
                        <div class="text-xs text-muted flex items-center gap-1.5">
                            <i data-lucide="info" class="w-4 h-4 text-emerald-700"></i>
                            <span>Menyetujui permohonan ini akan menetapkan Anda sebagai Guru Pembimbing dan mengaktifkan status PKL.</span>
                        </div>

                        <div class="flex items-center gap-2.5 shrink-0">
                            <!-- Tombol Tolak (Modal Trigger) -->
                            <button type="button" onclick="openRejectModal('{{ $p->id }}', '{{ $siswa->nis ?? '-' }}')" class="h-9 px-4 rounded-full border border-red-300 text-red-700 hover:bg-red-50 text-xs font-semibold inline-flex items-center gap-1.5 transition-colors cursor-pointer">
                                <i data-lucide="x" class="w-4 h-4"></i>
                                <span>Tolak / Revisi</span>
                            </button>

                            <!-- Form Accept Langsung -->
                            <form action="{{ route('bkk.guru.pengajuan.accept', $p->id) }}" method="POST" onsubmit="return confirm('Konfirmasi persetujuan (Accept) pengajuan PKL siswa ini?')">
                                @csrf
                                <button type="submit" class="h-9 px-5 rounded-full bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold inline-flex items-center gap-2 shadow-xs transition-colors cursor-pointer">
                                    <i data-lucide="check-check" class="w-4 h-4"></i>
                                    <span>Accept & Setujui PKL</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center rounded-3xl bg-white border border-brand-ink/10 space-y-3">
                    <div class="w-12 h-12 rounded-full bg-emerald-100 text-emerald-700 grid place-items-center mx-auto">
                        <i data-lucide="check" class="w-6 h-6"></i>
                    </div>
                    <h3 class="font-bold text-base text-navy">Tidak Ada Pengajuan PKL yang Menunggu</h3>
                    <p class="text-xs text-muted max-w-md mx-auto">Semua pengajuan permohonan PKL siswa telah diverifikasi oleh Guru Pembimbing.</p>
                </div>
            @endforelse

            @if($pengajuanMenunggu->hasPages())
                <div class="p-4 bg-white rounded-2xl border border-brand-ink/10">
                    {{ $pengajuanMenunggu->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- TAB 2: Lamaran Lowongan PKL Industri -->
    @if(($tab ?? '') === 'lamaran')
        <div class="space-y-4">
            @forelse($lamaranPkl as $l)
                @php
                    $siswa = $l->profilSiswa;
                    $lowongan = $l->lowongan;
                    $cv = $l->cvResume;
                @endphp
                <div class="p-5 sm:p-6 rounded-3xl bg-white border border-brand-ink/10 shadow-sm space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="font-bold text-base text-navy">NIS: {{ $siswa->nis ?? '-' }} — {{ $lowongan->judul ?? 'Lowongan PKL' }}</h3>
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-100 text-blue-800">
                                    Status: {{ $l->status }}
                                </span>
                            </div>
                            <div class="text-xs text-muted flex flex-wrap items-center gap-x-4 gap-y-1">
                                <span>Perusahaan: <strong>{{ $lowongan->mitra->nama_perusahaan ?? '-' }}</strong></span>
                                <span>Jurusan Siswa: {{ $siswa->jurusan ?? '-' }}</span>
                                <span>Tanggal Melamar: {{ $l->tanggal_melamar ? $l->tanggal_melamar->format('d M Y') : '-' }}</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ route('bkk.guru.siswa.cv', $l->siswa_id) }}" class="h-9 px-3.5 rounded-full border border-brand-ink/15 hover:bg-brand-paper text-navy text-xs font-semibold inline-flex items-center gap-1.5 transition-colors">
                                <i data-lucide="file-text" class="w-4 h-4 text-emerald-700"></i>
                                <span>Lihat CV Pelamar</span>
                            </a>

                            <form action="{{ route('bkk.guru.pengajuan.lamaran.accept', $l->id) }}" method="POST" onsubmit="return confirm('Setujui lamaran ini dan terbitkan Penempatan PKL resmi sekolah?')">
                                @csrf
                                <button type="submit" class="h-9 px-4 rounded-full bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold inline-flex items-center gap-1.5 shadow-xs transition-colors cursor-pointer">
                                    <i data-lucide="check" class="w-4 h-4"></i>
                                    <span>Accept ke Penempatan PKL</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center rounded-3xl bg-white border border-brand-ink/10 text-xs text-muted">
                    Tidak ada lamaran lowongan PKL yang berstatus Terkirim / Sedang Ditinjau saat ini.
                </div>
            @endforelse

            @if($lamaranPkl->hasPages())
                <div class="p-4 bg-white rounded-2xl border border-brand-ink/10">
                    {{ $lamaranPkl->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- TAB 3: Penempatan Berjalan -->
    @if(($tab ?? '') === 'berjalan')
        <div class="space-y-4">
            @forelse($penempatanBerjalan as $pb)
                @php
                    $siswa = $pb->siswa;
                    $achieved = $pb->total_jam_tercapai ?? 0;
                    $target = $pb->target_jam > 0 ? $pb->target_jam : 640;
                    $pct = min(100, (int) round(($achieved / $target) * 100));
                @endphp
                <div class="p-5 rounded-3xl bg-white border border-brand-ink/10 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="space-y-1.5 min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="font-bold text-sm text-navy">{{ $siswa->nis ?? '-' }} — {{ $siswa->jurusan ?? 'Jurusan' }}</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                Berjalan di {{ $pb->mitra->nama_perusahaan ?? 'Mitra' }}
                            </span>
                        </div>
                        <div class="text-xs text-muted flex items-center gap-4">
                            <span>Divisi: {{ $pb->unit_kerja_divisi ?? '-' }}</span>
                            <span>Akumulasi: <strong>{{ $achieved }}/{{ $target }} Jam ({{ $pct }}%)</strong></span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('bkk.guru.siswa.cv', $pb->siswa_id) }}" class="h-8 px-3 rounded-full border border-brand-ink/15 hover:bg-brand-paper text-navy text-xs font-semibold inline-flex items-center gap-1 transition-colors">
                            <i data-lucide="file-text" class="w-3.5 h-3.5 text-muted"></i>
                            <span>CV</span>
                        </a>
                        <a href="{{ route('bkk.guru.penempatan.show', $pb->id) }}" class="h-8 px-4 rounded-full bg-navy hover:bg-navy-dark text-white text-xs font-semibold inline-flex items-center gap-1.5 transition-colors">
                            <span>Pantau Detail & Nilai</span>
                            <i data-lucide="arrow-right" class="w-3 h-3"></i>
                        </a>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center rounded-3xl bg-white border border-brand-ink/10 text-xs text-muted">
                    Tidak ada penempatan PKL aktif saat ini.
                </div>
            @endforelse

            @if($penempatanBerjalan->hasPages())
                <div class="p-4 bg-white rounded-2xl border border-brand-ink/10">
                    {{ $penempatanBerjalan->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- TAB 4: Riwayat Selesai -->
    @if(($tab ?? '') === 'selesai')
        <div class="space-y-4">
            @forelse($penempatanSelesai as $ps)
                <div class="p-5 rounded-3xl bg-white border border-brand-ink/10 shadow-sm flex items-center justify-between gap-4 text-xs">
                    <div>
                        <div class="font-bold text-sm text-navy">{{ $ps->siswa->nis ?? '-' }} — {{ $ps->mitra->nama_perusahaan ?? '-' }}</div>
                        <div class="text-muted mt-0.5">Status: <strong>{{ $ps->status }}</strong> · Nilai Sekolah: <strong>{{ $ps->nilai_akhir_sekolah ?? '-' }}</strong></div>
                    </div>
                    <a href="{{ route('bkk.guru.penempatan.show', $ps->id) }}" class="h-8 px-3.5 rounded-full border border-brand-ink/15 hover:bg-brand-paper text-navy font-semibold inline-flex items-center gap-1">
                        <span>Lihat Berkas</span>
                    </a>
                </div>
            @empty
                <div class="p-12 text-center rounded-3xl bg-white border border-brand-ink/10 text-xs text-muted">
                    Belum ada riwayat penempatan PKL yang berstatus selesai.
                </div>
            @endforelse
        </div>
    @endif
</div>

<!-- Modal Tolak / Minta Revisi Pengajuan PKL -->
<div id="rejectModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-xl fade-up">
        <div class="flex items-center justify-between border-b border-brand-ink/10 pb-3">
            <h3 class="font-bold text-base text-navy">Tolak / Revisi Pengajuan PKL</h3>
            <button type="button" onclick="closeRejectModal()" class="text-muted hover:text-navy text-lg font-bold">×</button>
        </div>

        <p class="text-xs text-muted">Sertakan catatan atau alasan penolakan/revisi agar siswa dapat memperbaiki permohonannya:</p>

        <form id="rejectForm" method="POST" action="">
            @csrf
            <textarea
                name="alasan"
                rows="4"
                required
                placeholder="Contoh: Lokasi mitra belum memiliki pembimbing industri teknis yang sesuai dengan kompetensi jurusan..."
                class="w-full p-3 rounded-xl bg-canvas border border-brand-ink/15 text-xs text-navy focus:bg-white focus:outline-none focus:border-red-500 transition-all leading-relaxed"
            ></textarea>

            <div class="flex items-center justify-end gap-2 mt-4">
                <button type="button" onclick="closeRejectModal()" class="h-9 px-4 rounded-full border border-brand-ink/15 text-xs font-semibold hover:bg-brand-paper text-navy">
                    Batal
                </button>
                <button type="submit" class="h-9 px-4 rounded-full bg-red-600 hover:bg-red-700 text-white text-xs font-bold">
                    Konfirmasi Tolak
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRejectModal(id, nis) {
        const modal = document.getElementById('rejectModal');
        const form = document.getElementById('rejectForm');
        form.action = "{{ url('/bkk/guru/pengajuan') }}/" + id + "/reject";
        modal.classList.remove('hidden');
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').classList.add('hidden');
    }
</script>
@endsection
