@extends('admin.master')

@section('title', 'Katalog & Moderasi Lowongan - Admin BKK')

@section('content')
<div class="flex flex-col gap-6">
    <!-- Page Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-muted mb-1">
                <a href="{{ route('bkk.admin.index') }}" class="hover:text-navy">Dashboard</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-navy font-semibold">Lowongan Kerja & PKL</span>
            </div>
            <h1 class="text-2xl font-bold font-headline text-navy">
                Katalog & Moderasi Lowongan
            </h1>
            <p class="text-xs text-muted mt-1">
                Pantau lowongan yang diterbitkan oleh Mitra IDUKA, moderasi status tayang, dan cek kuota penyerapan siswa.
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ url('/bkk/lowongan') }}" target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-white border border-line hover:bg-slate-50 text-navy font-semibold text-xs transition-all shadow-xs">
                <i data-lucide="external-link" class="w-4 h-4 text-maroon"></i>
                <span>Lihat Web Publik</span>
            </a>
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
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="p-4 rounded-2xl bg-white border border-line shadow-xs">
            <div class="text-[11px] font-semibold text-muted uppercase tracking-wider">Total Lowongan</div>
            <div class="text-2xl font-headline font-bold text-navy mt-1">{{ number_format($stats['total'] ?? 0) }}</div>
            <div class="text-[10px] text-muted mt-0.5">Seluruh lowongan terdaftar</div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-line shadow-xs">
            <div class="text-[11px] font-semibold text-emerald-700 uppercase tracking-wider">Tayang (Aktif)</div>
            <div class="text-2xl font-headline font-bold text-emerald-700 mt-1">{{ number_format($stats['aktif'] ?? 0) }}</div>
            <div class="text-[10px] text-muted mt-0.5">Dapat dilamar siswa & alumni</div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-line shadow-xs">
            <div class="text-[11px] font-semibold text-amber-700 uppercase tracking-wider">Draft / Ditutup</div>
            <div class="text-2xl font-headline font-bold text-amber-700 mt-1">{{ number_format(($stats['ditutup'] ?? 0) + ($stats['draft'] ?? 0)) }}</div>
            <div class="text-[10px] text-muted mt-0.5">{{ $stats['draft'] ?? 0 }} draft · {{ $stats['ditutup'] ?? 0 }} ditutup</div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-line shadow-xs">
            <div class="text-[11px] font-semibold text-purple-700 uppercase tracking-wider">Total Berkas Lamaran</div>
            <div class="text-2xl font-headline font-bold text-purple-700 mt-1">{{ number_format($stats['total_pelamar'] ?? 0) }}</div>
            <div class="text-[10px] text-muted mt-0.5">Aplikasi masuk dari pelamar</div>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-4 rounded-2xl border border-line shadow-xs">
        <form method="GET" action="{{ route('bkk.admin.lowongan.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="relative flex-1 w-full">
                <i data-lucide="search" class="w-4 h-4 text-muted absolute left-3.5 top-3"></i>
                <input
                    type="text"
                    name="q"
                    value="{{ $search ?? request('q') }}"
                    placeholder="Cari posisi kerja, target jurusan, atau nama mitra..."
                    class="w-full pl-10 pr-4 py-2 bg-canvas text-navy text-xs rounded-full border border-line focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                />
            </div>

            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                <select name="tipe" onchange="this.form.submit()" class="px-3.5 py-2 bg-canvas text-xs font-medium text-navy rounded-full border border-line focus:outline-none focus:ring-1 focus:ring-navy">
                    <option value="">Semua Tipe</option>
                    <option value="PKL" {{ ($tipe ?? request('tipe')) === 'PKL' ? 'selected' : '' }}>Magang / PKL</option>
                    <option value="Kerja" {{ ($tipe ?? request('tipe')) === 'Kerja' ? 'selected' : '' }}>Kerja Lulusan</option>
                </select>

                <select name="status" onchange="this.form.submit()" class="px-3.5 py-2 bg-canvas text-xs font-medium text-navy rounded-full border border-line focus:outline-none focus:ring-1 focus:ring-navy">
                    <option value="">Semua Status</option>
                    <option value="Aktif" {{ ($status ?? request('status')) === 'Aktif' ? 'selected' : '' }}>Aktif</option>
                    <option value="Ditutup" {{ ($status ?? request('status')) === 'Ditutup' ? 'selected' : '' }}>Ditutup</option>
                    <option value="Draft" {{ ($status ?? request('status')) === 'Draft' ? 'selected' : '' }}>Draft</option>
                </select>

                <button type="submit" class="px-4 py-2 bg-navy text-white text-xs font-semibold rounded-full hover:bg-navy-light transition-colors shrink-0 cursor-pointer">
                    Cari
                </button>

                @if(!empty($search) || !empty($tipe) || !empty($status))
                    <a href="{{ route('bkk.admin.lowongan.index') }}" class="px-3 py-2 text-muted hover:text-navy text-xs font-medium shrink-0">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Data Table Container -->
    <div class="bg-white rounded-2xl border border-line shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-line bg-[#F5F7FA] text-[11px] font-bold text-muted uppercase tracking-wider">
                        <th class="py-3 px-4">Posisi & Mitra IDUKA</th>
                        <th class="py-3 px-4">Tipe & Jurusan</th>
                        <th class="py-3 px-4">Kuota & Deadline</th>
                        <th class="py-3 px-4 text-center">Pelamar</th>
                        <th class="py-3 px-4 text-center">Status Moderasi</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line text-xs">
                    @forelse($lowongans as $row)
                        @php
                            $isPkl = ($row->tipe === 'PKL');
                            $tipeBadge = $isPkl ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800';
                            $statusBadge = match($row->status) {
                                'Aktif' => 'bg-emerald-100 text-emerald-800 border border-emerald-200',
                                'Ditutup' => 'bg-slate-100 text-slate-700 border border-slate-200',
                                default => 'bg-amber-100 text-amber-800 border border-amber-200',
                            };
                            $mitraInitial = strtoupper(substr($row->mitra->nama_perusahaan ?? 'M', 0, 2));
                        @endphp
                        <tr class="hover:bg-canvas/50 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-navy/10 text-navy font-bold text-xs grid place-items-center shrink-0">
                                        {{ $mitraInitial }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-navy hover:text-maroon transition-colors">
                                            {{ $row->judul }}
                                        </div>
                                        <div class="text-[11px] text-muted flex items-center gap-1.5 mt-0.5">
                                            <i data-lucide="building" class="w-3 h-3 text-muted"></i>
                                            <span>{{ $row->mitra->nama_perusahaan ?? 'Mitra Eksternal' }}</span>
                                            <span>·</span>
                                            <i data-lucide="map-pin" class="w-3 h-3 text-muted"></i>
                                            <span>{{ $row->lokasi ?? 'Kantor Perusahaan' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold {{ $tipeBadge }} mb-1">
                                    {{ $row->tipe }}
                                </span>
                                <div class="text-[11px] text-muted font-medium">
                                    {{ $row->target_jurusan }}
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="text-navy font-semibold">
                                    {{ $row->kuota }} Kuota
                                </div>
                                <div class="text-[11px] text-muted mt-0.5 flex items-center gap-1">
                                    <i data-lucide="calendar" class="w-3 h-3 text-muted"></i>
                                    <span>Hingga {{ $row->deadline ? $row->deadline->format('d M Y') : '-' }}</span>
                                </div>
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                <a href="{{ route('bkk.admin.lowongan.siswa', $row->id) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-slate-100 hover:bg-slate-200 text-navy font-semibold text-[11px] transition-colors">
                                    <i data-lucide="users" class="w-3 h-3"></i>
                                    <span>{{ $row->lamarans_count ?? $row->lamarans()->count() }} Pelamar</span>
                                </a>
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                <form method="POST" action="{{ route('bkk.admin.lowongan.updateStatus', $row->id) }}" class="inline-block">
                                    @csrf
                                    <select name="status" onchange="this.form.submit()" class="px-2.5 py-1 rounded-full text-[11px] font-bold {{ $statusBadge }} cursor-pointer border focus:outline-none">
                                        <option value="Aktif" {{ $row->status === 'Aktif' ? 'selected' : '' }}>Aktif</option>
                                        <option value="Ditutup" {{ $row->status === 'Ditutup' ? 'selected' : '' }}>Ditutup</option>
                                        <option value="Draft" {{ $row->status === 'Draft' ? 'selected' : '' }}>Draft</option>
                                    </select>
                                </form>
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('bkk.admin.lowongan.siswa', $row->id) }}" title="Lihat Siswa PKL Diterima" class="p-1.5 rounded-lg text-muted hover:text-navy hover:bg-slate-100 transition-colors">
                                        <i data-lucide="user-check" class="w-4 h-4"></i>
                                    </a>
                                    <a href="{{ route('bkk.admin.lowongan.edit', $row->id) }}" title="Edit Detail Lowongan" class="p-1.5 rounded-lg text-muted hover:text-navy hover:bg-slate-100 transition-colors">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </a>
                                    <form method="POST" action="{{ route('bkk.admin.lowongan.destroy', $row->id) }}" class="inline-block" onsubmit="return confirm('Apakah Anda yakin ingin menghapus lowongan ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Hapus Lowongan" class="p-1.5 rounded-lg text-muted hover:text-red-600 hover:bg-red-50 transition-colors cursor-pointer">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-muted">
                                <div class="w-12 h-12 rounded-full bg-slate-100 mx-auto grid place-items-center mb-3">
                                    <i data-lucide="briefcase" class="w-6 h-6 text-muted"></i>
                                </div>
                                <div class="font-semibold text-navy text-sm">Tidak ada lowongan ditemukan</div>
                                <p class="text-xs text-muted mt-1">Coba gunakan kata kunci pencarian lain atau ubah filter status.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($lowongans->hasPages())
            <div class="p-4 border-t border-line flex items-center justify-between">
                <div class="text-xs text-muted">
                    Menampilkan <span class="font-semibold text-navy">{{ $lowongans->firstItem() ?? 0 }}</span> - <span class="font-semibold text-navy">{{ $lowongans->lastItem() ?? 0 }}</span> dari <span class="font-semibold text-navy">{{ $lowongans->total() }}</span> lowongan
                </div>
                <div>
                    {{ $lowongans->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
