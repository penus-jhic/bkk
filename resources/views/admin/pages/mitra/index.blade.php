@extends('admin.master')

@section('title', 'Kelola Mitra IDUKA - Admin BKK')

@section('content')
<div class="flex flex-col gap-6">
    <!-- Page Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-muted mb-1">
                <a href="{{ route('bkk.admin.index') }}" class="hover:text-navy">Dashboard</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-navy font-semibold">Mitra IDUKA</span>
            </div>
            <h1 class="text-2xl font-bold font-headline text-navy">
                Kelola Mitra Industri (IDUKA)
            </h1>
            <p class="text-xs text-muted mt-1">
                Daftar seluruh perusahaan rekanan SMK Plus Pelita Nusantara, verifikasi kemitraan, dan publikasi lowongan.
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('bkk.admin.mitra.create') }}" 
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-navy hover:bg-maroon text-white font-semibold text-xs transition-all shadow-sm">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Tambah Mitra Baru</span>
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
            <div class="text-[11px] font-semibold text-muted uppercase tracking-wider">Total Mitra Terdaftar</div>
            <div class="text-2xl font-headline font-bold text-navy mt-1">{{ $stats['total_mitra'] ?? 0 }}</div>
            <div class="text-[10px] text-muted mt-0.5">Perusahaan aktif di sistem</div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-line shadow-xs">
            <div class="text-[11px] font-semibold text-emerald-700 uppercase tracking-wider">Terverifikasi</div>
            <div class="text-2xl font-headline font-bold text-emerald-700 mt-1">{{ $stats['verified'] ?? 0 }}</div>
            <div class="text-[10px] text-muted mt-0.5">Dapat login & pasang lowongan</div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-line shadow-xs">
            <div class="text-[11px] font-semibold text-amber-700 uppercase tracking-wider">Pending / Nonaktif</div>
            <div class="text-2xl font-headline font-bold text-amber-700 mt-1">{{ $stats['pending_verifikasi'] ?? 0 }}</div>
            <div class="text-[10px] text-muted mt-0.5">Menunggu peninjauan admin</div>
        </div>

        <a href="{{ route('bkk.admin.mitra.permohonan.index') }}" class="block p-4 rounded-2xl bg-white border border-line shadow-xs hover:border-purple-300 transition-colors">
            <div class="text-[11px] font-semibold text-purple-700 uppercase tracking-wider">Permohonan Baru</div>
            <div class="text-2xl font-headline font-bold text-purple-700 mt-1">{{ $stats['permohonan_masuk'] ?? 0 }}</div>
            <div class="text-[10px] text-muted mt-0.5">Pengajuan kerja sama via web &rarr; Lihat</div>
        </a>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="bg-white p-4 rounded-2xl border border-line shadow-xs">
        <form method="GET" action="{{ route('bkk.admin.mitra.index') }}" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="relative flex-1 w-full">
                <i data-lucide="search" class="w-4 h-4 text-muted absolute left-3.5 top-3"></i>
                <input
                    type="text"
                    name="q"
                    value="{{ $search ?? request('q') }}"
                    placeholder="Cari nama perusahaan, kota, sektor, atau NPWP..."
                    class="w-full pl-10 pr-4 py-2 bg-canvas text-navy text-xs rounded-full border border-line focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                />
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <select name="status" onchange="this.form.submit()" class="px-3.5 py-2 bg-canvas text-xs font-medium text-navy rounded-full border border-line focus:outline-none focus:ring-1 focus:ring-navy">
                    <option value="">Semua Status Verifikasi</option>
                    <option value="verified" {{ ($filterVerifikasi ?? request('status')) === 'verified' ? 'selected' : '' }}>Terverifikasi</option>
                    <option value="pending" {{ ($filterVerifikasi ?? request('status')) === 'pending' ? 'selected' : '' }}>Pending / Nonaktif</option>
                </select>

                <button type="submit" class="px-4 py-2 bg-navy text-white text-xs font-semibold rounded-full hover:bg-navy-light transition-colors shrink-0 cursor-pointer">
                    Cari
                </button>

                @if(!empty($search) || !empty($filterVerifikasi))
                    <a href="{{ route('bkk.admin.mitra.index') }}" class="px-3 py-2 text-muted hover:text-navy text-xs font-medium shrink-0">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Data Table Container -->
    <div class="bg-white rounded-2xl border border-line shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-canvas border-b border-line text-muted uppercase font-semibold text-[11px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 w-12 text-center">#</th>
                        <th class="py-3.5 px-4">Nama Perusahaan & Identitas</th>
                        <th class="py-3.5 px-4">Sektor & Domisili</th>
                        <th class="py-3.5 px-4">Person-in-Charge (PIC)</th>
                        <th class="py-3.5 px-4 text-center">Aktivitas BKK</th>
                        <th class="py-3.5 px-4 text-center">Status Verifikasi</th>
                        <th class="py-3.5 px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($mitras as $index => $item)
                        <tr class="hover:bg-canvas/50 transition-colors">
                            <td class="py-4 px-4 text-center text-muted font-mono">
                                {{ $mitras->firstItem() + $index }}
                            </td>

                            <!-- Perusahaan Info -->
                            <td class="py-4 px-4">
                                <div class="flex items-center gap-3">
                                    @if($item->logo_url)
                                        <img src="{{ $item->logo_url }}" alt="{{ $item->nama_perusahaan }}" class="w-10 h-10 rounded-xl object-cover border border-line shrink-0"/>
                                    @else
                                        <div class="w-10 h-10 rounded-xl bg-navy text-white font-bold flex items-center justify-center text-xs shrink-0 shadow-inner">
                                            {{ $item->singkatan ?: substr($item->nama_perusahaan, 0, 3) }}
                                        </div>
                                    @endif
                                    <div class="min-w-0">
                                        <div class="font-headline font-bold text-navy text-sm truncate">
                                            <a href="{{ route('bkk.admin.mitra.edit', $item->id) }}" class="hover:text-maroon">
                                                {{ $item->nama_perusahaan }}
                                            </a>
                                        </div>
                                        <div class="text-[11px] text-muted flex items-center gap-2 mt-0.5">
                                            @if($item->singkatan)
                                                <span class="font-semibold text-navy">{{ $item->singkatan }}</span>
                                                <span>•</span>
                                            @endif
                                            <span class="font-mono">NPWP: {{ $item->npwp }}</span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Sektor & Domisili -->
                            <td class="py-4 px-4">
                                <div class="font-medium text-navy truncate max-w-[180px]">{{ $item->sektor_industri }}</div>
                                <div class="text-[11px] text-muted flex items-center gap-1 mt-0.5">
                                    <i data-lucide="map-pin" class="w-3 h-3 text-muted"></i>
                                    <span>{{ $item->kota }}</span>
                                </div>
                            </td>

                            <!-- PIC -->
                            <td class="py-4 px-4">
                                <div class="font-semibold text-navy">{{ $item->pic_name }}</div>
                                <div class="text-[11px] text-muted truncate max-w-[180px]">{{ $item->pic_role }}</div>
                                <div class="text-[11px] text-maroon font-mono mt-0.5 flex items-center gap-1">
                                    <i data-lucide="phone" class="w-3 h-3"></i>
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $item->pic_phone) }}" target="_blank" class="hover:underline">
                                        {{ $item->pic_phone }}
                                    </a>
                                </div>
                            </td>

                            <!-- Aktivitas -->
                            <td class="py-4 px-4 text-center">
                                <div class="inline-flex flex-col items-center gap-0.5">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                        <i data-lucide="briefcase" class="w-3 h-3"></i>
                                        <span>{{ $item->lowongans_count ?? 0 }} Lowongan</span>
                                    </span>
                                    <span class="text-[10px] text-muted">
                                        {{ $item->penempatan_pkl_count ?? 0 }} Siswa PKL
                                    </span>
                                </div>
                            </td>

                            <!-- Status Verifikasi & Toggle Button -->
                            <td class="py-4 px-4 text-center">
                                <form action="{{ route('bkk.admin.mitra.verify', $item->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" 
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold transition-all cursor-pointer border {{ $item->is_verified ? 'bg-emerald-50 text-emerald-800 border-emerald-200 hover:bg-emerald-100' : 'bg-amber-50 text-amber-800 border-amber-200 hover:bg-amber-100' }}"
                                            title="Klik untuk mengubah status verifikasi">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $item->is_verified ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                                        <span>{{ $item->is_verified ? 'Terverifikasi' : 'Pending' }}</span>
                                    </button>
                                </form>
                            </td>

                            <!-- Aksi -->
                            <td class="py-4 px-5 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('bkk.admin.mitra.edit', $item->id) }}" 
                                       class="p-1.5 rounded-full hover:bg-canvas text-navy border border-line transition-colors" 
                                       title="Edit Data Mitra">
                                        <i data-lucide="edit-3" class="w-4 h-4"></i>
                                    </a>

                                    <form action="{{ route('bkk.admin.mitra.destroy', $item->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus data kemitraan ini beserta riwayatnya?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="p-1.5 rounded-full hover:bg-rose-50 text-rose-600 border border-line transition-colors cursor-pointer" 
                                                title="Hapus Mitra">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-muted">
                                <i data-lucide="building" class="w-10 h-10 mx-auto text-muted/40 mb-2"></i>
                                <h3 class="text-sm font-bold text-navy">Belum ada Mitra IDUKA</h3>
                                <p class="text-xs text-muted mt-1">Tidak ada data mitra yang cocok dengan filter atau kata kunci pencarian.</p>
                                <a href="{{ route('bkk.admin.mitra.create') }}" class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 rounded-full bg-navy text-white text-xs font-semibold hover:bg-maroon transition-all">
                                    <i data-lucide="plus-circle" class="w-4 h-4"></i>
                                    <span>Tambah Mitra Baru</span>
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($mitras->hasPages())
            <div class="p-4 border-t border-line">
                {{ $mitras->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
