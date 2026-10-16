@extends('admin.master')

@section('title', 'Kelola Berita & Agenda - Admin BKK')

@section('content')
<div class="flex flex-col gap-6">
    <!-- Page Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <p class="dash-eyebrow">Panel Admin · Publikasi</p>
            <h1 class="dash-title font-display text-brand-ink">Kelola Berita &amp; <x-sketch.underline>Agenda BKK</x-sketch.underline></h1>
            <p class="text-xs text-on-surface-variant mt-1">
                Daftar seluruh publikasi warta bursa kerja, agenda rekrutmen, dan tips karier siswa.
            </p>
        </div>
        <a href="{{ route('bkk.admin.berita.create') }}" 
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-primary text-on-primary font-semibold text-xs hover:bg-primary/90 transition-all shadow-sm shrink-0">
            <span class="material-symbols-outlined text-[18px]">add</span>
            <span>Tulis Berita Baru</span>
        </a>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-surface-container-lowest p-4 rounded-xl border border-surface-variant/30 shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
        <form method="GET" action="{{ route('bkk.admin.berita.index') }}" class="w-full flex flex-col sm:flex-row items-center gap-3">
            <!-- Search Input -->
            <div class="relative w-full sm:w-72">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant text-[18px]">search</span>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari judul atau ringkasan..." 
                       class="w-full pl-9 pr-3 py-2 text-xs rounded-lg bg-surface-container border border-surface-variant/50 text-on-surface placeholder:text-on-surface-variant/60 focus:outline-none focus:ring-2 focus:ring-primary/20"/>
            </div>

            <!-- Filter Kategori -->
            <select name="kategori_id" onchange="this.form.submit()" class="w-full sm:w-48 py-2 px-3 text-xs rounded-lg bg-surface-container border border-surface-variant/50 text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/20">
                <option value="">Semua Kategori</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('kategori_id') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->nama }}
                    </option>
                @endforeach
            </select>

            <!-- Filter Status -->
            <select name="status" onchange="this.form.submit()" class="w-full sm:w-36 py-2 px-3 text-xs rounded-lg bg-surface-container border border-surface-variant/50 text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/20">
                <option value="">Semua Status</option>
                <option value="PUBLISHED" {{ request('status') === 'PUBLISHED' ? 'selected' : '' }}>Terbit (Published)</option>
                <option value="DRAFT" {{ request('status') === 'DRAFT' ? 'selected' : '' }}>Draf</option>
            </select>

            <button type="submit" class="w-full sm:w-auto px-4 py-2 bg-surface-container text-on-surface hover:bg-surface-variant rounded-lg text-xs font-semibold transition-colors">
                Terapkan
            </button>

            @if(request('q') || request('kategori_id') || request('status'))
                <a href="{{ route('bkk.admin.berita.index') }}" class="text-xs text-primary font-semibold hover:underline">
                    Reset
                </a>
            @endif
        </form>
    </div>

    <!-- Data Table -->
    <div class="bg-surface-container-lowest rounded-xl border border-surface-variant/30 shadow-sm overflow-hidden flex flex-col">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-surface-container-low border-b border-surface-variant/40 text-on-surface-variant font-semibold">
                    <tr>
                        <th class="py-3 px-4 w-12 text-center">#</th>
                        <th class="py-3 px-4">Artikel Berita</th>
                        <th class="py-3 px-4">Kategori</th>
                        <th class="py-3 px-4">Penulis</th>
                        <th class="py-3 px-4 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Tayangan</th>
                        <th class="py-3 px-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-variant/30">
                    @forelse($beritas as $index => $item)
                        <tr class="hover:bg-surface-container-low/60 transition-colors">
                            <td class="py-3.5 px-4 text-center text-on-surface-variant font-medium">
                                {{ $beritas->firstItem() + $index }}
                            </td>
                            <td class="py-3.5 px-4">
                                <div class="flex items-start gap-3">
                                    @if($item->gambar_sampul)
                                        <img src="{{ $item->gambar_sampul }}" alt="{{ $item->judul }}" class="w-12 h-12 rounded-lg object-cover shrink-0 mt-0.5"/>
                                    @else
                                        <div class="w-12 h-12 rounded-lg bg-surface-container flex items-center justify-center text-on-surface-variant shrink-0 mt-0.5">
                                            <span class="material-symbols-outlined text-[20px]">image</span>
                                        </div>
                                    @endif
                                    <div class="flex flex-col min-w-0">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            @if($item->is_featured)
                                                <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-primary text-on-primary uppercase tracking-wide">
                                                    Hero Utama
                                                </span>
                                            @endif
                                            <a href="{{ route('bkk.admin.berita.edit', $item->id) }}" class="font-bold text-sm text-on-surface hover:text-primary transition-colors line-clamp-1">
                                                {{ $item->judul }}
                                            </a>
                                        </div>
                                        <p class="text-[11px] text-on-surface-variant line-clamp-1 mt-0.5">
                                            {{ $item->ringkasan }}
                                        </p>
                                        <span class="text-[10px] text-on-surface-variant/70 mt-1">
                                            Terbit: {{ $item->formatted_date }} • {{ $item->estimasi_baca ?? '3 Menit' }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-surface-container text-on-surface">
                                    {{ $item->kategori->nama ?? '-' }}
                                </span>
                            </td>
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="flex flex-col">
                                    <span class="font-semibold text-on-surface">{{ $item->penulis_nama }}</span>
                                    <span class="text-[10px] text-on-surface-variant">{{ $item->penulis_jabatan ?? 'Kontributor' }}</span>
                                </div>
                            </td>
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                @if($item->status === 'PUBLISHED')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-600"></span>
                                        Terbit
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                        Draf
                                    </span>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center font-medium text-on-surface whitespace-nowrap">
                                {{ number_format($item->views_count) }}
                            </td>
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a href="{{ route('bkk.berita.detail', $item->slug) }}" target="_blank" 
                                       class="p-1.5 rounded-lg text-on-surface-variant hover:text-primary hover:bg-surface-container transition-colors" title="Lihat di Web Publik">
                                        <span class="material-symbols-outlined text-[18px]">open_in_new</span>
                                    </a>
                                    <a href="{{ route('bkk.admin.berita.edit', $item->id) }}" 
                                       class="p-1.5 rounded-lg text-on-surface-variant hover:text-primary hover:bg-surface-container transition-colors" title="Edit Berita">
                                        <span class="material-symbols-outlined text-[18px]">edit</span>
                                    </a>
                                    <form action="{{ route('bkk.admin.berita.destroy', $item->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus artikel \'{{ addslashes($item->judul) }}\'?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 rounded-lg text-on-surface-variant hover:text-red-600 hover:bg-red-50 transition-colors" title="Hapus Berita">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-10 text-on-surface-variant">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="material-symbols-outlined text-[36px] text-on-surface-variant/40">article</span>
                                    <p class="font-medium text-sm">Tidak ditemukan artikel berita yang sesuai kriteria.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($beritas->hasPages())
            <div class="p-4 border-t border-surface-variant/30 flex items-center justify-between">
                <span class="text-xs text-on-surface-variant">
                    Menampilkan {{ $beritas->firstItem() }} - {{ $beritas->lastItem() }} dari total {{ $beritas->total() }} berita
                </span>
                <div class="flex items-center gap-1">
                    {{ $beritas->links() }}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
