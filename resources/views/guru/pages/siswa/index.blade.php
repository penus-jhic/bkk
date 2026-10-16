@extends('guru.master')

@section('title', 'Direktori Siswa & Monitoring CV - Portal Guru')

@section('content')
<div class="space-y-6 fade-up">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-brand-ink/10">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-navy tracking-tight">Direktori Siswa & CV</h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                    {{ $siswaList->total() }} Siswa Terdaftar
                </span>
            </div>
            <p class="text-xs sm:text-sm text-muted mt-1">Daftar siswa bimbingan PKL, kelengkapan berkas CV (PDF/DOCX & ATS Markdown), serta status review Guru Pembimbing.</p>
        </div>

        <a href="{{ route('bkk.guru.pengajuan.index') }}" class="inline-flex items-center gap-2 h-10 px-4 rounded-full bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold shadow-sm transition-all cursor-pointer">
            <i data-lucide="check-circle" class="w-4 h-4"></i>
            <span>Persetujuan PKL Siswa</span>
        </a>
    </div>

    <!-- Filter & Pencarian -->
    <div class="p-4 rounded-2xl bg-white border border-brand-ink/10 shadow-xs">
        <form method="GET" action="{{ route('bkk.guru.siswa.index') }}" class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            <div class="flex-1 relative">
                <i data-lucide="search" class="w-4 h-4 text-muted absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Cari siswa berdasarkan nama, NIS, NISN, jurusan, atau kelas…"
                    class="w-full h-10 pl-10 pr-4 rounded-full bg-canvas border border-brand-ink/15 text-xs text-navy focus:outline-none focus:border-emerald-600 focus:bg-white transition-all"
                />
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <select name="jurusan" onchange="this.form.submit()" class="h-10 px-3.5 rounded-full bg-canvas border border-brand-ink/15 text-xs font-medium text-navy focus:outline-none focus:border-emerald-600 cursor-pointer">
                    <option value="">Semua Jurusan</option>
                    @foreach($daftarJurusan as $j)
                        <option value="{{ $j }}" {{ $jurusan === $j ? 'selected' : '' }}>{{ $j }}</option>
                    @endforeach
                </select>

                <select name="status_cv" onchange="this.form.submit()" class="h-10 px-3.5 rounded-full bg-canvas border border-brand-ink/15 text-xs font-medium text-navy focus:outline-none focus:border-emerald-600 cursor-pointer">
                    <option value="">Semua Status CV</option>
                    <option value="Ada Dokumen" {{ $statusCv === 'Ada Dokumen' ? 'selected' : '' }}>Ada Berkas Fisik (PDF/DOCX)</option>
                    <option value="Belum Direview" {{ $statusCv === 'Belum Direview' ? 'selected' : '' }}>Belum Direview Guru</option>
                    <option value="Disetujui" {{ $statusCv === 'Disetujui' ? 'selected' : '' }}>CV Disetujui</option>
                </select>

                <select name="status_pkl" onchange="this.form.submit()" class="h-10 px-3.5 rounded-full bg-canvas border border-brand-ink/15 text-xs font-medium text-navy focus:outline-none focus:border-emerald-600 cursor-pointer">
                    <option value="">Semua Status PKL</option>
                    <option value="BERJALAN" {{ $statusPkl === 'BERJALAN' ? 'selected' : '' }}>Sedang PKL (Aktif)</option>
                    <option value="MENUNGGU_PERSETUJUAN" {{ $statusPkl === 'MENUNGGU_PERSETUJUAN' ? 'selected' : '' }}>Menunggu Persetujuan</option>
                    <option value="SELESAI" {{ $statusPkl === 'SELESAI' ? 'selected' : '' }}>Tuntas PKL</option>
                    <option value="BELUM_PKL" {{ $statusPkl === 'BELUM_PKL' ? 'selected' : '' }}>Belum Penempatan</option>
                </select>

                @if($canAssignGuru)
                    <select name="pembimbing" onchange="this.form.submit()" class="h-10 px-3.5 rounded-full bg-canvas border border-brand-ink/15 text-xs font-medium text-navy focus:outline-none focus:border-emerald-600 cursor-pointer">
                        <option value="">Semua Pembimbing</option>
                        <option value="none" {{ $pembimbing === 'none' ? 'selected' : '' }}>Belum Ada Pembimbing</option>
                        @foreach($daftarGuru as $g)
                            <option value="{{ $g->user_id }}" {{ $pembimbing === $g->user_id ? 'selected' : '' }}>{{ $g->nama_lengkap ?: $g->user_id }}</option>
                        @endforeach
                    </select>
                @endif

                <button type="submit" class="h-10 px-5 rounded-full bg-navy hover:bg-navy-dark text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer">
                    Cari
                </button>

                @if(!empty($search) || !empty($jurusan) || !empty($statusCv) || !empty($statusPkl) || !empty($pembimbing))
                    <a href="{{ route('bkk.guru.siswa.index') }}" class="h-10 px-3.5 rounded-full border border-brand-ink/15 hover:bg-brand-paper text-xs text-muted font-medium inline-flex items-center transition-colors">
                        Reset
                    </a>
                @endif
            </div>
        </form>
        @if($canAssignGuru)
            <p class="mt-3 text-[11px] text-muted">
                Guru muncul di pilihan pembimbing setelah membuka Portal Guru minimal sekali. Siswa tanpa pembimbing hanya terlihat oleh admin.
            </p>
        @endif
    </div>

    <!-- Tabel Daftar Siswa & CV -->
    <div class="bg-white rounded-3xl border border-brand-ink/10 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-brand-ink/10 bg-brand-paper text-[11px] font-bold text-muted uppercase tracking-wider">
                        <th class="py-3.5 px-5">Siswa / Identitas</th>
                        <th class="py-3.5 px-5">Jurusan & Kelas</th>
                        <th class="py-3.5 px-5">Guru Pembimbing</th>
                        <th class="py-3.5 px-5">Berkas & Format CV</th>
                        <th class="py-3.5 px-5 text-center">Review Guru</th>
                        <th class="py-3.5 px-5 text-center">Status PKL</th>
                        <th class="py-3.5 px-5 text-right">Tindakan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-brand-ink/10 text-xs">
                    @forelse($siswaList as $s)
                        @php
                            $cv = $s->mainResume;
                            $hasDoc = $cv?->has_document_file;
                            $activePkl = $s->activePkl;
                            $reviewStatus = $cv->status_review_guru ?? 'Belum Direview';
                            $reviewBadge = match($reviewStatus) {
                                'Disetujui' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                'Perlu Revisi' => 'bg-red-100 text-red-800 border-red-300',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                            $pklBadge = match($activePkl?->status ?? 'BELUM') {
                                'BERJALAN' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                'MENUNGGU_PERSETUJUAN' => 'bg-amber-100 text-amber-800 border-amber-300',
                                'SELESAI' => 'bg-blue-100 text-blue-800 border-blue-300',
                                default => 'bg-slate-100 text-slate-600 border-slate-200',
                            };
                        @endphp
                        <tr class="hover:bg-canvas/50 transition-colors">
                            <!-- Siswa / Identitas -->
                            <td class="py-4 px-5">
                                <div class="font-bold text-navy text-sm">{{ $s->nama_lengkap ?: 'NIS ' . ($s->nis ?? '-') }}</div>
                                <div class="text-[11px] text-muted flex items-center gap-1.5 mt-0.5">
                                    <span>NIS: {{ $s->nis ?? '-' }}</span>
                                    <span>·</span>
                                    <span>NISN: {{ $s->nisn ?? '-' }}</span>
                                    <span>·</span>
                                    <span>Angkatan {{ $s->angkatan ?? '-' }}</span>
                                </div>
                            </td>

                            <!-- Jurusan & Kelas -->
                            <td class="py-4 px-5">
                                <div class="font-semibold text-navy">{{ $s->jurusan ?? 'Jurusan' }}</div>
                                <div class="text-[11px] text-muted mt-0.5">{{ $s->kelas ?? '-' }}</div>
                            </td>

                            <!-- Guru Pembimbing (admin dapat menetapkan / mengganti) -->
                            <td class="py-4 px-5">
                                @if($canAssignGuru)
                                    <form method="POST" action="{{ route('bkk.guru.siswa.pembimbing', $s->user_id) }}">
                                        @csrf
                                        <select name="guru_pembimbing_id" onchange="this.form.submit()" aria-label="Guru pembimbing {{ $s->nama_lengkap ?: $s->nis }}" class="h-8 max-w-[180px] px-2.5 rounded-full bg-canvas border border-brand-ink/15 text-[11px] font-medium text-navy focus:outline-none focus:border-emerald-600 cursor-pointer">
                                            <option value="">Belum ditetapkan</option>
                                            @foreach($daftarGuru as $g)
                                                <option value="{{ $g->user_id }}" {{ $s->guru_pembimbing_id === $g->user_id ? 'selected' : '' }}>{{ $g->nama_lengkap ?: $g->user_id }}</option>
                                            @endforeach
                                            @if($s->guru_pembimbing_id && !$daftarGuru->contains('user_id', $s->guru_pembimbing_id))
                                                <option value="{{ $s->guru_pembimbing_id }}" selected disabled>{{ $s->guru_pembimbing_id }} (belum login)</option>
                                            @endif
                                        </select>
                                    </form>
                                @else
                                    <span class="text-[11px] text-navy font-medium">{{ $s->guruPembimbing?->nama_lengkap ?? 'Anda' }}</span>
                                @endif
                            </td>

                            <!-- Berkas & Format CV -->
                            <td class="py-4 px-5">
                                @if($cv)
                                    <div class="space-y-1">
                                        @if($hasDoc)
                                            <a href="{{ $cv->file_url }}" target="_blank" class="inline-flex items-center gap-1.5 text-emerald-700 hover:text-emerald-800 font-semibold group" title="Unduh Berkas Fisik">
                                                <i data-lucide="file-check" class="w-4 h-4 text-emerald-600 group-hover:scale-110 transition-transform"></i>
                                                <span class="truncate max-w-[180px]">{{ $cv->file_name ?? 'Berkas CV' }}</span>
                                                <span class="text-[10px] text-muted">({{ $cv->file_size_formatted }})</span>
                                            </a>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 text-muted">
                                                <i data-lucide="file-text" class="w-4 h-4"></i>
                                                <span>ATS Markdown Saja</span>
                                            </span>
                                        @endif
                                        <div class="text-[10px] text-muted">Skor ATS: <strong>{{ $cv->score !== null ? $cv->score . '/100' : 'belum dinilai' }}</strong></div>
                                    </div>
                                @else
                                    <span class="text-muted text-[11px] italic">Belum membuat CV</span>
                                @endif
                            </td>

                            <!-- Review Guru -->
                            <td class="py-4 px-5 text-center">
                                @if($cv)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $reviewBadge }}">
                                        @if($reviewStatus === 'Disetujui')
                                            <i data-lucide="check" class="w-3 h-3 text-emerald-600"></i>
                                        @elseif($reviewStatus === 'Perlu Revisi')
                                            <i data-lucide="alert-circle" class="w-3 h-3 text-red-600"></i>
                                        @else
                                            <i data-lucide="clock" class="w-3 h-3 text-slate-500"></i>
                                        @endif
                                        <span>{{ $reviewStatus }}</span>
                                    </span>
                                @else
                                    <span class="text-muted text-[11px]">-</span>
                                @endif
                            </td>

                            <!-- Status PKL -->
                            <td class="py-4 px-5 text-center">
                                @if($activePkl)
                                    <div class="space-y-0.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold border {{ $pklBadge }}">
                                            {{ str_replace('_', ' ', $activePkl->status) }}
                                        </span>
                                        <div class="text-[10px] text-muted truncate max-w-[130px] mx-auto">
                                            {{ $activePkl->mitra->nama_perusahaan ?? 'Mitra' }}
                                        </div>
                                    </div>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        Belum PKL
                                    </span>
                                @endif
                            </td>

                            <!-- Tindakan -->
                            <td class="py-4 px-5 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('bkk.guru.siswa.cv', $s->user_id) }}" class="h-8 px-3 rounded-full bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold inline-flex items-center gap-1.5 shadow-xs transition-colors" title="Lihat CV & Berikan Review">
                                        <i data-lucide="file-search" class="w-3.5 h-3.5"></i>
                                        <span>Review CV</span>
                                    </a>

                                    @if($activePkl)
                                        <a href="{{ route('bkk.guru.penempatan.show', $activePkl->id) }}" class="h-8 px-3 rounded-full border border-brand-ink/15 hover:bg-brand-paper text-navy text-xs font-semibold inline-flex items-center gap-1 transition-colors" title="Lihat Penempatan PKL Siswa">
                                            <i data-lucide="activity" class="w-3.5 h-3.5 text-muted"></i>
                                            <span>PKL</span>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="p-8 text-center text-xs text-muted">
                                Tidak ada data siswa yang cocok dengan kriteria pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($siswaList->hasPages())
            <div class="p-4 border-t border-brand-ink/10 flex items-center justify-between">
                {{ $siswaList->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
