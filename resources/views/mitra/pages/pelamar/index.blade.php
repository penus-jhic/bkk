@extends('mitra.master')

@section('title', 'Review CV Pelamar: ' . $vacancy['title'] . ' - Mitra IDUKA')

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
                <span class="text-navy font-semibold">Review Pelamar</span>
            </div>
            <h1 class="text-2xl font-headline font-bold text-navy truncate max-w-2xl">
                Pelamar: {{ $vacancy['title'] }}
            </h1>
            <p class="text-xs text-muted mt-0.5 flex flex-wrap items-center gap-2">
                <span>Tipe: <strong class="text-navy">{{ $vacancy['tipe_badge'] }}</strong></span>
                <span>•</span>
                <span>Jurusan: <strong class="text-navy">{{ $vacancy['jurusan'] }}</strong></span>
                <span>•</span>
                <span>Total: <strong class="text-navy">{{ count($applicants) }} Pelamar</strong></span>
            </p>
        </div>

        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('bkk.mitra.lowongan.edit', $vacancy['id']) }}" class="px-4 py-2 rounded-full border border-line hover:bg-canvas text-navy text-xs font-semibold transition-colors flex items-center gap-1.5">
                <i data-lucide="settings" class="w-4 h-4"></i>
                <span>Pengaturan Lowongan</span>
            </a>
            <a href="{{ route('bkk.mitra.lowongan.index') }}" class="px-4 py-2 rounded-full bg-canvas text-muted hover:text-navy text-xs font-semibold transition-colors">
                Kembali ke Lowongan
            </a>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="google-card p-4 space-y-3">
        <form action="{{ route('bkk.mitra.pelamar.index', $vacancy['id']) }}" method="GET" class="flex flex-col md:flex-row gap-3">
            <div class="relative flex-1">
                <i data-lucide="search" class="w-4 h-4 text-muted absolute left-3.5 top-3"></i>
                <input
                    type="text"
                    name="q"
                    value="{{ $search ?? '' }}"
                    placeholder="Cari nama kandidat siswa/alumni atau jurusan..."
                    class="w-full pl-10 pr-4 py-2 bg-canvas text-navy placeholder:text-muted text-sm rounded-full border border-line focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                />
            </div>

            <div class="flex items-center gap-2 overflow-x-auto pb-1 md:pb-0">
                <select name="status" onchange="this.form.submit()" class="px-3.5 py-2 bg-canvas text-xs font-medium text-navy rounded-full border border-line focus:outline-none focus:ring-1 focus:ring-navy">
                    <option value="Semua" {{ ($statusFilter ?? 'Semua') === 'Semua' ? 'selected' : '' }}>Semua Status Seleksi</option>
                    <option value="Sedang Ditinjau" {{ ($statusFilter ?? '') === 'Sedang Ditinjau' ? 'selected' : '' }}>Sedang Ditinjau</option>
                    <option value="Dipanggil Interview" {{ ($statusFilter ?? '') === 'Dipanggil Interview' ? 'selected' : '' }}>Dipanggil Interview</option>
                    <option value="Diterima" {{ ($statusFilter ?? '') === 'Diterima' ? 'selected' : '' }}>Diterima</option>
                    <option value="Ditolak" {{ ($statusFilter ?? '') === 'Ditolak' ? 'selected' : '' }}>Ditolak</option>
                </select>

                <button type="submit" class="px-4 py-2 bg-navy text-white text-xs font-semibold rounded-full hover:bg-navy-light transition-colors">
                    Filter
                </button>

                @if(!empty($search) || (!empty($statusFilter) && $statusFilter !== 'Semua'))
                    <a href="{{ route('bkk.mitra.pelamar.index', $vacancy['id']) }}" class="px-3 py-2 text-muted hover:text-navy text-xs font-medium">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Applicants Table / Grid -->
    <div class="google-card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="bg-canvas border-b border-line text-muted uppercase font-semibold text-[11px] tracking-wider">
                        <th class="py-3.5 px-5">Kandidat Siswa / Alumni</th>
                        <th class="py-3.5 px-4 hidden md:table-cell">Jurusan</th>
                        <th class="py-3.5 px-4 text-center">Kecocokan CV</th>
                        <th class="py-3.5 px-4 hidden sm:table-cell">Tgl Melamar</th>
                        <th class="py-3.5 px-4">Status Seleksi</th>
                        <th class="py-3.5 px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($applicants as $item)
                        @php
                            $badgeColor = match($item['status']) {
                                'Dipanggil Interview' => 'bg-amber-50 text-amber-800 border-amber-200',
                                'Sedang Ditinjau' => 'bg-blue-50 text-blue-800 border-blue-200',
                                'Diterima' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
                                'Ditolak' => 'bg-red-50 text-red-800 border-red-200',
                                default => 'bg-gray-100 text-gray-700 border-gray-200',
                            };
                        @endphp
                        <tr class="hover:bg-canvas/50 transition-colors">
                            <!-- Kandidat Info -->
                            <td class="py-4 px-5">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-navy/10 text-navy font-bold flex items-center justify-center text-xs shrink-0">
                                        {{ substr($item['nama'], 0, 1) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-headline font-bold text-navy text-sm truncate">
                                            <a href="{{ route('bkk.mitra.pelamar.show', [$vacancy['id'], $item['id']]) }}" class="hover:text-maroon">
                                                {{ $item['nama'] }}
                                            </a>
                                        </div>
                                        <div class="text-[11px] text-muted truncate">
                                            {{ $item['status_pendidikan'] }} • NIS: {{ $item['nis_nisn'] }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Jurusan -->
                            <td class="py-4 px-4 hidden md:table-cell font-medium text-navy">
                                {{ $item['jurusan'] }}
                            </td>

                            <!-- AI CV Match Score -->
                            <td class="py-4 px-4 text-center">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold {{ $item['cv_score'] >= 90 ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                                    <i data-lucide="sparkles" class="w-3 h-3"></i>
                                    {{ $item['cv_score'] }}%
                                </span>
                            </td>

                            <!-- Tanggal Melamar -->
                            <td class="py-4 px-4 hidden sm:table-cell text-muted">
                                {{ \Carbon\Carbon::parse($item['tanggal_melamar'])->translatedFormat('d M Y') }}
                            </td>

                            <!-- Status Badge -->
                            <td class="py-4 px-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $badgeColor }}">
                                    {{ $item['status'] }}
                                </span>
                            </td>

                            <!-- Aksi -->
                            <td class="py-4 px-5 text-right">
                                <a href="{{ route('bkk.mitra.pelamar.show', [$vacancy['id'], $item['id']]) }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full bg-navy hover:bg-navy-light text-white font-medium text-xs transition-colors">
                                    <i data-lucide="file-text" class="w-3.5 h-3.5"></i>
                                    <span>Tinjau CV</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-muted">
                                <i data-lucide="inbox" class="w-8 h-8 mx-auto text-muted/60 mb-2"></i>
                                <p class="font-medium text-xs">Belum ada pelamar yang cocok dengan kriteria filter.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(method_exists($applicants, 'links'))
            <div class="p-4 border-t border-line">
                {{ $applicants->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
