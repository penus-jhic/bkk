@extends('admin.master')

@section('title', 'Monitoring & Validasi PKL - Admin BKK')

@section('content')
<div class="flex flex-col gap-6">
    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="dash-eyebrow">
                <a href="{{ route('bkk.admin.index') }}" class="hover:text-brand-ink">Dashboard</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-brand-ink font-semibold">Monitoring PKL</span>
            </div>
            <h1 class="dash-title font-display text-brand-ink">Monitoring PKL &amp; <x-sketch.underline>Validasi Laporan</x-sketch.underline></h1>
            <p class="text-xs text-muted mt-1">
                Pantau seluruh siswa aktif di industri mitra, verifikasi log jurnal harian, dan validasi draf naskah laporan akhir.
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <span class="px-3.5 py-1.5 rounded-full bg-brand-ink/5 text-brand-ink font-semibold text-xs border border-line flex items-center gap-2">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                <span>Standar Target PKL: 640 Jam</span>
            </span>
        </div>
    </div>

    <!-- Alert Notifikasi Flash -->
    @if(session('success'))
        <div class="p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-3">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-600 shrink-0"></i>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Quick Stats Cards (Google Style) -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-3.5">
        <div class="p-5 rounded-card bg-white border border-brand-ink/10 shadow-card hover:border-brand-darkred/25 hover:shadow-softpill transition-all">
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-brand-ink/60">Total Penempatan</div>
            <div class="mt-3 font-display text-4xl font-bold leading-none text-brand-ink">{{ number_format($stats['total_penempatan'] ?? 0) }}</div>
            <div class="mt-3 pt-3 border-t border-brand-ink/10 text-xs text-brand-ink/60">Siswa terdaftar PKL</div>
        </div>

        <div class="p-5 rounded-card bg-white border border-brand-ink/10 shadow-card hover:border-brand-darkred/25 hover:shadow-softpill transition-all">
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-brand-ink/60"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Sedang Aktif</div>
            <div class="mt-3 font-display text-4xl font-bold leading-none text-brand-ink">{{ number_format($stats['berjalan'] ?? 0) }}</div>
            <div class="mt-3 pt-3 border-t border-brand-ink/10 text-xs text-brand-ink/60">Berjalan di industri</div>
        </div>

        <div class="p-5 rounded-card bg-white border border-brand-ink/10 shadow-card hover:border-brand-darkred/25 hover:shadow-softpill transition-all">
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-brand-ink/60"><span class="w-1.5 h-1.5 rounded-full bg-brand-darkred"></span>Telah Selesai</div>
            <div class="mt-3 font-display text-4xl font-bold leading-none text-brand-ink">{{ number_format($stats['selesai'] ?? 0) }}</div>
            <div class="mt-3 pt-3 border-t border-brand-ink/10 text-xs text-brand-ink/60">Menuntaskan target jam</div>
        </div>

        <div class="p-5 rounded-card bg-white border border-brand-ink/10 shadow-card hover:border-brand-darkred/25 hover:shadow-softpill transition-all">
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-brand-ink/60"><span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Jurnal Menunggu</div>
            <div class="mt-3 font-display text-4xl font-bold leading-none text-brand-ink">{{ number_format($stats['jurnal_menunggu'] ?? 0) }}</div>
            <div class="mt-3 pt-3 border-t border-brand-ink/10 text-xs text-brand-ink/60">Perlu validasi harian</div>
        </div>

        <div class="p-5 rounded-card bg-white border border-brand-ink/10 shadow-card hover:border-brand-darkred/25 hover:shadow-softpill transition-all">
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-brand-ink/60"><span class="w-1.5 h-1.5 rounded-full bg-brand-darkred"></span>Laporan Ditinjau</div>
            <div class="mt-3 font-display text-4xl font-bold leading-none text-brand-ink">{{ number_format($stats['laporan_ditinjau'] ?? 0) }}</div>
            <div class="mt-3 pt-3 border-t border-brand-ink/10 text-xs text-brand-ink/60">Draf bab akhir</div>
        </div>
    </div>

    <!-- Navigation Tabs (Google Style) -->
    <div class="flex items-center gap-2 border-b border-line pb-px overflow-x-auto">
        <a href="{{ route('bkk.admin.pkl.monitoring', ['tab' => 'penempatan']) }}" 
           class="px-5 py-3 text-xs font-bold transition-all border-b-2 flex items-center gap-2 whitespace-nowrap {{ $activeTab === 'penempatan' ? 'border-navy text-brand-ink' : 'border-transparent text-muted hover:text-brand-ink' }}">
            <i data-lucide="users" class="w-4 h-4"></i>
            <span>Penempatan Siswa Aktif</span>
            <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-slate-100 text-slate-700">
                {{ $penempatan->total() }}
            </span>
        </a>

        <a href="{{ route('bkk.admin.pkl.monitoring', ['tab' => 'jurnal']) }}" 
           class="px-5 py-3 text-xs font-bold transition-all border-b-2 flex items-center gap-2 whitespace-nowrap {{ $activeTab === 'jurnal' ? 'border-navy text-brand-ink' : 'border-transparent text-muted hover:text-brand-ink' }}">
            <i data-lucide="book-open" class="w-4 h-4"></i>
            <span>Validasi Log Jurnal</span>
            @if(($stats['jurnal_menunggu'] ?? 0) > 0)
                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-amber-100 text-amber-800">
                    {{ $stats['jurnal_menunggu'] }}
                </span>
            @endif
        </a>

        <a href="{{ route('bkk.admin.pkl.monitoring', ['tab' => 'laporan']) }}" 
           class="px-5 py-3 text-xs font-bold transition-all border-b-2 flex items-center gap-2 whitespace-nowrap {{ $activeTab === 'laporan' ? 'border-navy text-brand-ink' : 'border-transparent text-muted hover:text-brand-ink' }}">
            <i data-lucide="file-check" class="w-4 h-4"></i>
            <span>Review Naskah Laporan</span>
            @if(($stats['laporan_ditinjau'] ?? 0) > 0)
                <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-brand-rose/15 text-brand-rosedeep">
                    {{ $stats['laporan_ditinjau'] }}
                </span>
            @endif
        </a>
    </div>

    <!-- TAB 1: Penempatan Siswa -->
    @if($activeTab === 'penempatan')
        <!-- Filter Bar -->
        <div class="bg-white p-4 rounded-2xl border border-line shadow-xs">
            <form method="GET" action="{{ route('bkk.admin.pkl.monitoring') }}" class="flex flex-col sm:flex-row items-center gap-3">
                <input type="hidden" name="tab" value="penempatan">
                <div class="relative flex-1 w-full">
                    <i data-lucide="search" class="w-4 h-4 text-muted absolute left-3.5 top-3"></i>
                    <input
                        type="text"
                        name="q"
                        value="{{ $search ?? request('q') }}"
                        placeholder="Cari nama siswa, NIS, jurusan, mitra, atau pembimbing..."
                        class="w-full pl-10 pr-4 py-2 bg-canvas text-brand-ink text-xs rounded-full border border-line focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                    />
                </div>

                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <select name="status" onchange="this.form.submit()" class="px-3.5 py-2 bg-canvas text-xs font-medium text-brand-ink rounded-full border border-line focus:outline-none focus:ring-1 focus:ring-navy">
                        <option value="">Semua Status PKL</option>
                        <option value="BERJALAN" {{ ($status ?? request('status')) === 'BERJALAN' ? 'selected' : '' }}>Sedang Berjalan</option>
                        <option value="SELESAI" {{ ($status ?? request('status')) === 'SELESAI' ? 'selected' : '' }}>Selesai</option>
                        <option value="MENUNGGU_SURAT" {{ ($status ?? request('status')) === 'MENUNGGU_SURAT' ? 'selected' : '' }}>Menunggu Surat</option>
                    </select>

                    <button type="submit" class="px-4 py-2 bg-navy text-white text-xs font-semibold rounded-full hover:bg-navy-light transition-colors shrink-0 cursor-pointer">
                        Cari
                    </button>

                    @if(!empty($search) || !empty($status))
                        <a href="{{ route('bkk.admin.pkl.monitoring', ['tab' => 'penempatan']) }}" class="px-3 py-2 text-muted hover:text-brand-ink text-xs font-medium shrink-0">
                            Reset
                        </a>
                    @endif
                </div>
            </form>
        </div>

        <!-- Tabel Penempatan -->
        <div class="bg-white rounded-2xl border border-line shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-line bg-brand-paper text-[11px] font-bold text-muted uppercase tracking-wider">
                            <th class="py-3 px-4">Siswa & Jurusan</th>
                            <th class="py-3 px-4">Mitra IDUKA & Penempatan</th>
                            <th class="py-3 px-4">Periode Tanggal</th>
                            <th class="py-3 px-4">Akumulasi Jam Kerja</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line text-xs">
                        @forelse($penempatan as $row)
                            @php
                                $siswa = $row->siswa;
                                $target = $row->target_jam > 0 ? $row->target_jam : 640;
                                $achieved = $row->total_jam_tercapai ?? 0;
                                $percent = min(100, (int) round(($achieved / $target) * 100));
                                $statusBadge = match($row->status) {
                                    'BERJALAN' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                    'SELESAI' => 'bg-navy/10 text-navy border-navy/20',
                                    default => 'bg-amber-100 text-amber-800 border-amber-200',
                                };
                            @endphp
                            <tr class="hover:bg-canvas/50 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-brand-ink">
                                        {{ $siswa->nis ?? 'Siswa PKL' }} - {{ $siswa->jurusan ?? 'Jurusan' }}
                                    </div>
                                    <div class="text-[11px] text-muted mt-0.5">
                                        NIS: {{ $siswa->nis ?? '-' }} · {{ $siswa->kelas ?? 'Kelas XII' }}
                                    </div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-brand-ink">
                                        {{ $row->mitra->nama_perusahaan ?? 'Mitra Industri' }}
                                    </div>
                                    <div class="text-[11px] text-muted flex items-center gap-1.5 mt-0.5">
                                        <i data-lucide="user" class="w-3 h-3 text-muted"></i>
                                        <span>Pembimbing: {{ $row->pembimbing_industri_nama ?? 'Mentor Industri' }}</span>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="text-brand-ink font-medium">
                                        {{ $row->tanggal_mulai ? $row->tanggal_mulai->format('d M Y') : '-' }} s/d
                                    </div>
                                    <div class="text-[11px] text-muted">
                                        {{ $row->tanggal_selesai ? $row->tanggal_selesai->format('d M Y') : '-' }}
                                    </div>
                                </td>

                                <td class="py-3.5 px-4 w-48">
                                    <div class="flex items-center justify-between text-[11px] mb-1">
                                        <span class="font-bold text-brand-ink">{{ $achieved }} Jam</span>
                                        <span class="text-muted">{{ $percent }}%</span>
                                    </div>
                                    <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500 bg-navy" style="width: {{ $percent }}%"></div>
                                    </div>
                                    <div class="text-[10px] text-muted mt-1">Target: {{ $target }} jam standar</div>
                                </td>

                                <td class="py-3.5 px-4 text-center">
                                    <span class="inline-block px-3 py-1 rounded-full text-[10px] font-bold border {{ $statusBadge }}">
                                        {{ $row->status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-12 text-center text-muted">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 mx-auto grid place-items-center mb-3">
                                        <i data-lucide="activity" class="w-6 h-6 text-muted"></i>
                                    </div>
                                    <div class="font-semibold text-brand-ink text-sm">Tidak ada data penempatan PKL</div>
                                    <p class="text-xs text-muted mt-1">Gunakan kata kunci pencarian lain atau sesuaikan filter status.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($penempatan->hasPages())
                <div class="p-4 border-t border-line">
                    {{ $penempatan->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- TAB 2: Validasi Jurnal Harian -->
    @if($activeTab === 'jurnal')
        <div class="bg-white rounded-2xl border border-line shadow-xs overflow-hidden">
            <div class="p-4 border-b border-line flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-brand-ink uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="clock" class="w-4 h-4 text-amber-600"></i>
                        <span>Antrean Log Jurnal Menunggu Validasi</span>
                    </h2>
                    <p class="text-xs text-muted mt-0.5">Catatan aktivitas pekerjaan teknis harian siswa yang butuh verifikasi guru/admin.</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-amber-100 text-amber-800">
                    {{ $jurnalsPending->total() }} Menunggu
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-line bg-brand-paper text-[11px] font-bold text-muted uppercase tracking-wider">
                            <th class="py-3 px-4">Tanggal & Durasi</th>
                            <th class="py-3 px-4">Siswa & Mitra IDUKA</th>
                            <th class="py-3 px-4">Rincian Aktivitas Harian</th>
                            <th class="py-3 px-4 text-right">Tindakan Validasi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line text-xs">
                        @forelse($jurnalsPending as $jurnal)
                            <tr class="hover:bg-canvas/50 transition-colors">
                                <td class="py-3.5 px-4 w-40">
                                    <div class="font-bold text-brand-ink">
                                        {{ $jurnal->tanggal ? $jurnal->tanggal->format('d M Y') : '-' }}
                                    </div>
                                    <span class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700">
                                        {{ $jurnal->durasi_jam ?? 8 }} Jam Kerja
                                    </span>
                                </td>

                                <td class="py-3.5 px-4 w-60">
                                    <div class="font-semibold text-brand-ink">
                                        {{ $jurnal->penempatan->siswa->nis ?? 'Siswa' }} ({{ $jurnal->penempatan->siswa->jurusan ?? '-' }})
                                    </div>
                                    <div class="text-[11px] text-muted flex items-center gap-1 mt-0.5">
                                        <i data-lucide="building" class="w-3 h-3 text-muted"></i>
                                        <span>{{ $jurnal->penempatan->mitra->nama_perusahaan ?? 'Mitra' }}</span>
                                    </div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <p class="text-brand-ink font-normal leading-relaxed">
                                        {{ $jurnal->aktivitas }}
                                    </p>
                                    @if($jurnal->foto_dokumentasi_url)
                                        <a href="{{ $jurnal->foto_dokumentasi_url }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-semibold text-maroon hover:underline mt-1">
                                            <i data-lucide="image" class="w-3 h-3"></i>
                                            <span>Lihat Foto Dokumentasi</span>
                                        </a>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-right shrink-0">
                                    <div class="flex items-center justify-end gap-2">
                                        <form method="POST" action="{{ route('bkk.admin.pkl.validateJurnal', $jurnal->id) }}">
                                            @csrf
                                            <input type="hidden" name="status" value="Disetujui">
                                            <button type="submit" class="px-3 py-1.5 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-[11px] transition-colors cursor-pointer flex items-center gap-1">
                                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                                <span>Setujui</span>
                                            </button>
                                        </form>

                                        <button type="button" onclick="openRevisiJurnalModal('{{ $jurnal->id }}')" class="px-3 py-1.5 rounded-full bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 font-semibold text-[11px] transition-colors cursor-pointer flex items-center gap-1">
                                            <i data-lucide="message-square" class="w-3.5 h-3.5"></i>
                                            <span>Revisi</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-12 text-center text-muted">
                                    <div class="w-12 h-12 rounded-full bg-emerald-50 mx-auto grid place-items-center mb-3">
                                        <i data-lucide="check-check" class="w-6 h-6 text-emerald-600"></i>
                                    </div>
                                    <div class="font-semibold text-brand-ink text-sm">Semua Jurnal Telah Divalidasi</div>
                                    <p class="text-xs text-muted mt-1">Tidak ada antrean log aktivitas harian siswa yang pending.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($jurnalsPending->hasPages())
                <div class="p-4 border-t border-line">
                    {{ $jurnalsPending->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- TAB 3: Review Laporan Akhir -->
    @if($activeTab === 'laporan')
        <div class="bg-white rounded-2xl border border-line shadow-xs overflow-hidden">
            <div class="p-4 border-b border-line flex items-center justify-between">
                <div>
                    <h2 class="text-sm font-bold text-brand-ink uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="file-text" class="w-4 h-4 text-brand-rosedeep"></i>
                        <span>Antrean Naskah Laporan Akhir Ditinjau</span>
                    </h2>
                    <p class="text-xs text-muted mt-0.5">Pengumpulan draf naskah laporan pertanggungjawaban PKL per Bab.</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-brand-rose/15 text-brand-rosedeep">
                    {{ $laporansPending->total() }} Ditinjau
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="border-b border-line bg-brand-paper text-[11px] font-bold text-muted uppercase tracking-wider">
                            <th class="py-3 px-4">Bab & Judul Laporan</th>
                            <th class="py-3 px-4">Siswa & Mitra IDUKA</th>
                            <th class="py-3 px-4">File Naskah</th>
                            <th class="py-3 px-4 text-right">Tindakan Review</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line text-xs">
                        @forelse($laporansPending as $laporan)
                            <tr class="hover:bg-canvas/50 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="font-bold text-brand-ink">
                                        BAB {{ $laporan->nomor_bab }}: {{ $laporan->judul_bab }}
                                    </div>
                                    <div class="text-[11px] text-muted mt-0.5">
                                        Unggahan terakhir: {{ $laporan->updated_at ? $laporan->updated_at->format('d M Y H:i') : '-' }}
                                    </div>
                                </td>

                                <td class="py-3.5 px-4">
                                    <div class="font-semibold text-brand-ink">
                                        {{ $laporan->penempatan->siswa->nis ?? 'Siswa' }} ({{ $laporan->penempatan->siswa->jurusan ?? '-' }})
                                    </div>
                                    <div class="text-[11px] text-muted mt-0.5">
                                        {{ $laporan->penempatan->mitra->nama_perusahaan ?? 'Mitra Industri' }}
                                    </div>
                                </td>

                                <td class="py-3.5 px-4">
                                    @if($laporan->file_draft_url)
                                        <a href="{{ $laporan->file_draft_url }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-100 hover:bg-slate-200 text-brand-ink font-semibold text-[11px] transition-colors">
                                            <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                            <span>Unduh Dokumen Draf</span>
                                        </a>
                                    @else
                                        <span class="text-muted text-[11px]">Naskah online di editor siswa</span>
                                    @endif
                                </td>

                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <form method="POST" action="{{ route('bkk.admin.pkl.validateLaporan', $laporan->id) }}">
                                            @csrf
                                            <input type="hidden" name="status" value="Disetujui">
                                            <button type="submit" class="px-3 py-1.5 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-[11px] transition-colors cursor-pointer flex items-center gap-1">
                                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                                <span>Setujui Bab</span>
                                            </button>
                                        </form>

                                        <button type="button" onclick="openRevisiLaporanModal('{{ $laporan->id }}')" class="px-3 py-1.5 rounded-full bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 font-semibold text-[11px] transition-colors cursor-pointer flex items-center gap-1">
                                            <i data-lucide="edit" class="w-3.5 h-3.5"></i>
                                            <span>Minta Revisi</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-12 text-center text-muted">
                                    <div class="w-12 h-12 rounded-full bg-emerald-50 mx-auto grid place-items-center mb-3">
                                        <i data-lucide="check-check" class="w-6 h-6 text-emerald-600"></i>
                                    </div>
                                    <div class="font-semibold text-brand-ink text-sm">Semua Bab Laporan Telah Selesai Ditinjau</div>
                                    <p class="text-xs text-muted mt-1">Belum ada bab laporan akhir baru yang dikirimkan oleh siswa.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($laporansPending->hasPages())
                <div class="p-4 border-t border-line">
                    {{ $laporansPending->links() }}
                </div>
            @endif
        </div>
    @endif
</div>

<!-- Modal Revisi Jurnal Harian -->
<div id="revisiJurnalModal" class="fixed inset-0 bg-black/40 z-50 hidden place-items-center p-4">
    <div class="bg-white rounded-3xl border border-line max-w-md w-full p-6 shadow-xl relative">
        <h3 class="text-sm font-bold text-brand-ink mb-1 flex items-center gap-2">
            <i data-lucide="message-square" class="w-4 h-4 text-amber-600"></i>
            <span>Catatan Revisi Log Jurnal</span>
        </h3>
        <p class="text-xs text-muted mb-4">Berikan instruksi atau perbaikan detail pekerjaan yang harus dilengkapi oleh siswa.</p>

        <form id="revisiJurnalForm" method="POST" action="" class="space-y-4">
            @csrf
            <input type="hidden" name="status" value="Revisi">
            <div>
                <label for="catatan_revisi" class="block text-xs font-semibold text-brand-ink mb-1">Catatan Pembimbing</label>
                <textarea
                    id="catatan_revisi"
                    name="catatan_revisi"
                    rows="3"
                    required
                    placeholder="Contoh: Tolong lengkapi dokumentasi foto saat perakitan kabel..."
                    class="w-full p-3 bg-canvas text-brand-ink text-xs rounded-xl border border-line focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                ></textarea>
            </div>
            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeRevisiJurnalModal()" class="px-4 py-2 rounded-full text-xs font-semibold text-muted hover:text-brand-ink">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-full bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs cursor-pointer shadow-xs">
                    Kirim Catatan Revisi
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Revisi Laporan Akhir -->
<div id="revisiLaporanModal" class="fixed inset-0 bg-black/40 z-50 hidden place-items-center p-4">
    <div class="bg-white rounded-3xl border border-line max-w-md w-full p-6 shadow-xl relative">
        <h3 class="text-sm font-bold text-brand-ink mb-1 flex items-center gap-2">
            <i data-lucide="edit" class="w-4 h-4 text-amber-600"></i>
            <span>Catatan Revisi Naskah Bab Laporan</span>
        </h3>
        <p class="text-xs text-muted mb-4">Tuliskan evaluasi koreksi format atau konten naskah bab kepada siswa.</p>

        <form id="revisiLaporanForm" method="POST" action="" class="space-y-4">
            @csrf
            <input type="hidden" name="status" value="Revisi">
            <div>
                <label for="catatan_pembimbing" class="block text-xs font-semibold text-brand-ink mb-1">Catatan Koreksi</label>
                <textarea
                    id="catatan_pembimbing"
                    name="catatan_pembimbing"
                    rows="3"
                    required
                    placeholder="Contoh: Perbaiki format penulisan tujuan PKL pada sub-bab 1.2..."
                    class="w-full p-3 bg-canvas text-brand-ink text-xs rounded-xl border border-line focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                ></textarea>
            </div>
            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeRevisiLaporanModal()" class="px-4 py-2 rounded-full text-xs font-semibold text-muted hover:text-brand-ink">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-full bg-amber-600 hover:bg-amber-700 text-white font-semibold text-xs cursor-pointer shadow-xs">
                    Kirim Catatan Koreksi
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRevisiJurnalModal(jurnalId) {
        const modal = document.getElementById('revisiJurnalModal');
        const form = document.getElementById('revisiJurnalForm');
        form.action = `/bkk/admin/pkl/jurnal/${jurnalId}/validate`;
        modal.classList.remove('hidden');
        modal.classList.add('grid');
    }

    function closeRevisiJurnalModal() {
        const modal = document.getElementById('revisiJurnalModal');
        modal.classList.add('hidden');
        modal.classList.remove('grid');
    }

    function openRevisiLaporanModal(laporanId) {
        const modal = document.getElementById('revisiLaporanModal');
        const form = document.getElementById('revisiLaporanForm');
        form.action = `/bkk/admin/pkl/laporan/${laporanId}/validate`;
        modal.classList.remove('hidden');
        modal.classList.add('grid');
    }

    function closeRevisiLaporanModal() {
        const modal = document.getElementById('revisiLaporanModal');
        modal.classList.add('hidden');
        modal.classList.remove('grid');
    }
</script>
@endsection
