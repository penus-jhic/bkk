@extends('admin.master')

@section('title', 'Permohonan Kerja Sama - Admin BKK')

@section('content')
@php
    $statusStyles = [
        'MENUNGGU_REVIEW' => ['label' => 'Menunggu Review', 'class' => 'bg-amber-50 text-amber-800 border-amber-200', 'dot' => 'bg-amber-500'],
        'DISETUJUI' => ['label' => 'Disetujui', 'class' => 'bg-emerald-50 text-emerald-800 border-emerald-200', 'dot' => 'bg-emerald-500'],
        'DITOLAK' => ['label' => 'Ditolak', 'class' => 'bg-red-50 text-red-800 border-red-200', 'dot' => 'bg-red-500'],
    ];
@endphp
<div class="flex flex-col gap-6">
    <!-- Page Header -->
    <div>
        <div class="flex items-center gap-2 text-xs text-muted mb-1">
            <a href="{{ route('bkk.admin.index') }}" class="hover:text-navy">Dashboard</a>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            <a href="{{ route('bkk.admin.mitra.index') }}" class="hover:text-navy">Mitra IDUKA</a>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
            <span class="text-navy font-semibold">Permohonan Kerja Sama</span>
        </div>
        <h1 class="text-2xl font-bold font-headline text-navy">
            Permohonan Kerja Sama
        </h1>
        <p class="text-xs text-muted mt-1">
            Pengajuan kemitraan dari perusahaan melalui formulir kerja sama di web publik BKK.
        </p>
    </div>

    <!-- Data Table Container -->
    <div class="bg-white rounded-2xl border border-line shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-canvas border-b border-line text-muted uppercase font-semibold text-[11px] tracking-wider">
                    <tr>
                        <th class="py-3.5 px-4 w-12 text-center">#</th>
                        <th class="py-3.5 px-4">Perusahaan</th>
                        <th class="py-3.5 px-4">Person-in-Charge (PIC)</th>
                        <th class="py-3.5 px-4">Jenis Kerja Sama & Pesan</th>
                        <th class="py-3.5 px-4 text-center">Status</th>
                        <th class="py-3.5 px-5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @forelse($permohonan as $index => $item)
                        @php $style = $statusStyles[$item->status] ?? $statusStyles['MENUNGGU_REVIEW']; @endphp
                        <tr class="hover:bg-canvas/50 transition-colors align-top">
                            <td class="py-4 px-4 text-center text-muted font-mono">
                                {{ $permohonan->firstItem() + $index }}
                            </td>

                            <!-- Perusahaan -->
                            <td class="py-4 px-4">
                                <div class="font-headline font-bold text-navy text-sm">{{ $item->nama_perusahaan }}</div>
                                <div class="text-[11px] text-muted mt-0.5">{{ $item->bidang_usaha }}</div>
                                <div class="text-[11px] text-muted mt-0.5 max-w-[220px]">{{ $item->alamat_perusahaan }}</div>
                                <div class="text-[10px] text-muted mt-1">Diajukan {{ $item->created_at?->format('d M Y H:i') }}</div>
                            </td>

                            <!-- PIC -->
                            <td class="py-4 px-4">
                                <div class="font-semibold text-navy">{{ $item->nama_pic }}</div>
                                <div class="text-[11px] text-muted">{{ $item->jabatan_pic }}</div>
                                <div class="text-[11px] text-maroon font-mono mt-0.5 flex items-center gap-1">
                                    <i data-lucide="phone" class="w-3 h-3"></i>
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $item->no_telepon) }}" target="_blank" class="hover:underline">{{ $item->no_telepon }}</a>
                                </div>
                                <div class="text-[11px] text-muted mt-0.5 flex items-center gap-1">
                                    <i data-lucide="mail" class="w-3 h-3"></i>
                                    <a href="mailto:{{ $item->email_resmi }}" class="hover:underline">{{ $item->email_resmi }}</a>
                                </div>
                            </td>

                            <!-- Jenis & Pesan -->
                            <td class="py-4 px-4">
                                <div class="flex flex-wrap gap-1 max-w-[260px]">
                                    @foreach((array) $item->jenis_kerjasama as $jenis)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">{{ $jenis }}</span>
                                    @endforeach
                                </div>
                                @if($item->pesan_tambahan)
                                    <p class="text-[11px] text-muted mt-1.5 max-w-[260px] leading-relaxed">{{ \Illuminate\Support\Str::limit($item->pesan_tambahan, 160) }}</p>
                                @endif
                                @if($item->catatan_admin)
                                    <p class="text-[11px] text-navy mt-1.5 max-w-[260px]"><span class="font-semibold">Catatan admin:</span> {{ $item->catatan_admin }}</p>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="py-4 px-4 text-center">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $style['class'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $style['dot'] }}"></span>
                                    <span>{{ $style['label'] }}</span>
                                </span>
                            </td>

                            <!-- Aksi -->
                            <td class="py-4 px-5 text-right">
                                @if($item->status === 'MENUNGGU_REVIEW')
                                    <div class="flex items-center justify-end gap-1.5">
                                        <form action="{{ route('bkk.admin.mitra.permohonan.updateStatus', $item->id) }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="status" value="DISETUJUI">
                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-[11px] font-semibold bg-emerald-600 hover:bg-emerald-700 text-white transition-colors">
                                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                                <span>Setujui</span>
                                            </button>
                                        </form>
                                        <form action="{{ route('bkk.admin.mitra.permohonan.updateStatus', $item->id) }}" method="POST" class="inline" onsubmit="return confirm('Tolak permohonan kerja sama dari {{ addslashes($item->nama_perusahaan) }}?')">
                                            @csrf
                                            <input type="hidden" name="status" value="DITOLAK">
                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-[11px] font-semibold border border-line text-red-700 hover:bg-red-50 transition-colors">
                                                <i data-lucide="x" class="w-3.5 h-3.5"></i>
                                                <span>Tolak</span>
                                            </button>
                                        </form>
                                    </div>
                                @else
                                    <span class="text-[11px] text-muted">Sudah diproses</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center">
                                <i data-lucide="inbox" class="w-10 h-10 mx-auto text-muted/60"></i>
                                <p class="text-sm font-semibold text-navy mt-3">Belum ada permohonan kerja sama</p>
                                <p class="text-xs text-muted mt-1">Pengajuan dari formulir kerja sama publik akan muncul di sini.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($permohonan->hasPages())
            <div class="px-4 py-3 border-t border-line">
                {{ $permohonan->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
