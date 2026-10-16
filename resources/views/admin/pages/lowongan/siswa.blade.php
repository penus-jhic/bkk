@extends('admin.master')

@section('title', 'Siswa Diterima - ' . $lowongan->judul . ' - Admin BKK')

@section('content')
<div class="flex flex-col gap-6">
    <!-- Breadcrumb & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="dash-eyebrow">
                <a href="{{ route('bkk.admin.index') }}" class="hover:text-brand-ink">Dashboard</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <a href="{{ route('bkk.admin.lowongan.index') }}" class="hover:text-brand-ink">Lowongan</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-brand-ink font-semibold">Siswa Terkonfirmasi</span>
            </div>
            <h1 class="dash-title font-display text-brand-ink">Siswa Diterima &amp; <x-sketch.underline>Terkonfirmasi PKL</x-sketch.underline></h1>
            <p class="text-xs text-muted mt-1">
                Daftar siswa yang telah dinyatakan <span class="font-semibold text-emerald-700">Diterima</span> oleh <span class="font-semibold text-brand-ink">{{ $lowongan->mitra->nama_perusahaan ?? 'Mitra IDUKA' }}</span> untuk posisi <span class="font-semibold text-maroon">{{ $lowongan->judul }}</span>.
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <a href="{{ route('bkk.admin.lowongan.index') }}" 
               class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-white border border-line hover:bg-slate-50 text-brand-ink font-semibold text-xs transition-all shadow-xs">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                <span>Kembali ke Katalog</span>
            </a>
        </div>
    </div>

    <!-- Info Overview Card -->
    <div class="p-5 bg-white rounded-3xl border border-line shadow-xs grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div>
            <div class="text-[11px] font-semibold text-muted uppercase tracking-wider">Mitra IDUKA</div>
            <div class="text-sm font-bold text-brand-ink mt-1">{{ $lowongan->mitra->nama_perusahaan ?? '-' }}</div>
            <div class="text-[11px] text-muted">{{ $lowongan->lokasi }}</div>
        </div>
        <div>
            <div class="text-[11px] font-semibold text-muted uppercase tracking-wider">Target Jurusan</div>
            <div class="text-sm font-bold text-brand-ink mt-1">{{ $lowongan->target_jurusan }}</div>
            <div class="text-[11px] text-muted">{{ $lowongan->tipe }}</div>
        </div>
        <div>
            <div class="text-[11px] font-semibold text-muted uppercase tracking-wider">Kuota Diterima</div>
            <div class="text-sm font-bold text-emerald-700 mt-1">
                {{ $lamaransDiterima->total() }} / {{ $lowongan->kuota }} Siswa
            </div>
            <div class="text-[11px] text-muted">
                {{ $lamaransDiterima->total() >= $lowongan->kuota ? 'Kuota Terpenuhi' : 'Sisa ' . ($lowongan->kuota - $lamaransDiterima->total()) . ' kuota' }}
            </div>
        </div>
        <div>
            <div class="text-[11px] font-semibold text-muted uppercase tracking-wider">Batas Waktu (Deadline)</div>
            <div class="text-sm font-bold text-brand-ink mt-1">
                {{ $lowongan->deadline ? $lowongan->deadline->format('d M Y') : '-' }}
            </div>
            <div class="text-[11px] text-muted">Status: {{ $lowongan->status }}</div>
        </div>
    </div>

    <!-- Data Table Container: Siswa Diterima -->
    <div class="bg-white rounded-2xl border border-line shadow-xs overflow-hidden">
        <div class="p-4 border-b border-line flex items-center justify-between">
            <h2 class="text-sm font-bold text-brand-ink uppercase tracking-wider flex items-center gap-2">
                <i data-lucide="user-check" class="w-4 h-4 text-emerald-600"></i>
                <span>Rekapitulasi Siswa Diterima</span>
            </h2>
            <span class="text-xs text-muted font-medium">
                Total {{ $lamaransDiterima->total() }} siswa berstatus Diterima
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-line bg-brand-paper text-[11px] font-bold text-muted uppercase tracking-wider">
                        <th class="py-3 px-4">Nama Siswa / Pelamar</th>
                        <th class="py-3 px-4">Jurusan & Kelas</th>
                        <th class="py-3 px-4">Kode Lamaran</th>
                        <th class="py-3 px-4 text-center">Skor Kecocokan</th>
                        <th class="py-3 px-4 text-center">Status Konfirmasi</th>
                        <th class="py-3 px-4 text-right">Tanggal Diterima</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line text-xs">
                    @forelse($lamaransDiterima as $item)
                        @php
                            $siswa = $item->siswa;
                            $initial = strtoupper(substr($item->nama ?? 'S', 0, 2));
                        @endphp
                        <tr class="hover:bg-canvas/50 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-navy text-white font-bold text-xs grid place-items-center shrink-0">
                                        {{ $initial }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-brand-ink">
                                            {{ $item->nama }}
                                        </div>
                                        <div class="text-[11px] text-muted">
                                            NIS: {{ $siswa->nis ?? '-' }} · NISN: {{ $siswa->nisn ?? '-' }}
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-brand-ink">
                                    {{ $siswa->jurusan ?? $lowongan->target_jurusan }}
                                </div>
                                <div class="text-[11px] text-muted">
                                    {{ $siswa->kelas ?? 'Kelas XII' }} · Angkatan {{ $siswa->angkatan ?? '2024' }}
                                </div>
                            </td>

                            <td class="py-3.5 px-4 font-mono text-[11px] text-muted">
                                {{ $item->kode_lamaran ?? ('LMR-' . $item->id) }}
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                    <i data-lucide="sparkles" class="w-3 h-3 text-emerald-600"></i>
                                    <span>{{ (int) $item->skor_match_ai }}%</span>
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-block px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    Diterima
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-right text-muted">
                                {{ $item->updated_at ? $item->updated_at->format('d M Y H:i') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-muted">
                                <div class="w-12 h-12 rounded-full bg-slate-100 mx-auto grid place-items-center mb-3">
                                    <i data-lucide="user-x" class="w-6 h-6 text-muted"></i>
                                </div>
                                <div class="font-semibold text-brand-ink text-sm">Belum ada siswa yang diterima</div>
                                <p class="text-xs text-muted mt-1">Saat Mitra IDUKA memilih status "Diterima" untuk pelamar, data siswa akan muncul di tabel ini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($lamaransDiterima->hasPages())
            <div class="p-4 border-t border-line">
                {{ $lamaransDiterima->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
