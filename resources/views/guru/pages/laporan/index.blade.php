@extends('guru.master')

@section('title', 'Review Naskah Laporan PKL - Portal Guru')

@section('content')
<div class="space-y-6 fade-up">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-brand-ink/10">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-navy tracking-tight">Review Naskah Laporan PKL</h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">
                    {{ $stats['ditinjau'] }} Bab Ditinjau
                </span>
            </div>
            <p class="text-xs sm:text-sm text-muted mt-1">Review naskah pertanggungjawaban PKL siswa per Bab (BAB I - V) dan berikan catatan pembimbing.</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-xs text-muted">Format berkas: <strong>PDF / Dokumen Naskah</strong></span>
        </div>
    </div>

    <!-- Filter & Pencarian -->
    <div class="p-4 rounded-2xl bg-white border border-brand-ink/10 shadow-xs">
        <form method="GET" action="{{ route('bkk.guru.laporan.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="flex-1 relative">
                <i data-lucide="search" class="w-4 h-4 text-muted absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Cari bab laporan, judul, nama siswa, atau mitra industri…"
                    class="w-full h-10 pl-10 pr-4 rounded-full bg-canvas border border-brand-ink/15 text-xs text-navy focus:outline-none focus:border-emerald-600 focus:bg-white transition-all"
                />
            </div>

            <div class="flex items-center gap-2">
                <select name="status" onchange="this.form.submit()" class="h-10 px-4 rounded-full bg-canvas border border-brand-ink/15 text-xs font-medium text-navy focus:outline-none focus:border-emerald-600 cursor-pointer">
                    <option value="Semua">Semua Status</option>
                    <option value="Ditinjau" {{ $statusFilter === 'Ditinjau' ? 'selected' : '' }}>Ditinjau ({{ $stats['ditinjau'] }})</option>
                    <option value="Disetujui" {{ $statusFilter === 'Disetujui' ? 'selected' : '' }}>Disetujui ({{ $stats['disetujui'] }})</option>
                    <option value="Revisi" {{ $statusFilter === 'Revisi' ? 'selected' : '' }}>Perlu Revisi ({{ $stats['revisi'] }})</option>
                </select>

                <button type="submit" class="h-10 px-5 rounded-full bg-navy hover:bg-navy-dark text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Daftar Bab Laporan -->
    <div class="space-y-3">
        @forelse($laporans as $l)
            @php
                $siswa = $l->penempatan?->siswa;
                $mitra = $l->penempatan?->mitra;
                $statusBadge = match($l->status) {
                    'Disetujui' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                    'Revisi' => 'bg-red-100 text-red-800 border-red-300',
                    default => 'bg-purple-100 text-purple-800 border-purple-300',
                };
            @endphp
            <div class="p-5 rounded-3xl bg-white border border-brand-ink/10 shadow-sm space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 border-b border-brand-ink/10 pb-3">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-bold text-sm text-navy">{{ $l->judul_bab }}</span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold border {{ $statusBadge }}">
                                {{ $l->status }}
                            </span>
                        </div>
                        <div class="text-xs text-muted flex items-center gap-3">
                            <span>Siswa: <strong>{{ $siswa->nis ?? '-' }}</strong> ({{ $siswa->jurusan ?? '-' }})</span>
                            <span>Mitra: {{ $mitra->nama_perusahaan ?? '-' }}</span>
                            @if($l->terakhir_diperbarui)
                                <span>Diperbarui: {{ $l->terakhir_diperbarui->format('d M Y') }}</span>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        @if(!empty($l->file_draft_url))
                            <a href="{{ $l->file_draft_url }}" target="_blank" class="h-8 px-3.5 rounded-full border border-brand-ink/15 hover:bg-brand-paper text-navy text-xs font-semibold inline-flex items-center gap-1.5 transition-colors">
                                <i data-lucide="download" class="w-3.5 h-3.5 text-muted"></i>
                                <span>Unduh Draf</span>
                            </a>
                        @endif

                        <button type="button" onclick="openLaporanModal('{{ $l->id }}', '{{ $l->catatan_pembimbing }}', '{{ $l->status }}')" class="h-8 px-3.5 rounded-full bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold inline-flex items-center gap-1.5 shadow-xs transition-colors cursor-pointer">
                            <i data-lucide="check-square" class="w-3.5 h-3.5"></i>
                            <span>Validasi Naskah</span>
                        </button>
                    </div>
                </div>

                @if(!empty($l->catatan_pembimbing))
                    <div class="text-xs text-navy bg-canvas p-3 rounded-2xl border border-brand-ink/10 flex items-start gap-2">
                        <i data-lucide="message-square" class="w-4 h-4 text-emerald-700 mt-0.5 shrink-0"></i>
                        <div>
                            <span class="font-semibold block text-[11px] text-muted">Catatan Pembimbing:</span>
                            <p class="leading-relaxed mt-0.5">{{ $l->catatan_pembimbing }}</p>
                        </div>
                    </div>
                @endif
            </div>
        @empty
            <div class="p-12 text-center rounded-3xl bg-white border border-brand-ink/10 text-xs text-muted">
                Tidak ada draf bab laporan PKL yang cocok dengan filter.
            </div>
        @endforelse

        @if($laporans->hasPages())
            <div class="p-4 bg-white rounded-2xl border border-brand-ink/10">
                {{ $laporans->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Validasi Laporan -->
<div id="laporanModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-xl fade-up">
        <div class="flex items-center justify-between border-b border-brand-ink/10 pb-3">
            <h3 class="font-bold text-base text-navy">Validasi Bab Laporan PKL</h3>
            <button type="button" onclick="closeLaporanModal()" class="text-muted hover:text-navy text-lg font-bold">×</button>
        </div>

        <form id="laporanForm" method="POST" action="" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-navy mb-1.5">Status Bab Laporan</label>
                <select name="status" id="modalLaporanStatus" class="w-full h-10 px-3.5 rounded-xl bg-canvas border border-brand-ink/15 text-xs font-medium text-navy">
                    <option value="Disetujui">Disetujui (Bab tuntas)</option>
                    <option value="Revisi">Perlu Revisi (Siswa wajib upload revisi)</option>
                    <option value="Ditinjau">Sedang Ditinjau</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-navy mb-1.5">Catatan Evaluasi / Koreksi Guru</label>
                <textarea
                    name="catatan_pembimbing"
                    id="modalLaporanCatatan"
                    rows="3"
                    placeholder="Contoh: Perbaiki format penulisan tabel dan tambahkan dokumentasi foto kegiatan bab II..."
                    class="w-full p-3 rounded-xl bg-canvas border border-brand-ink/15 text-xs text-navy focus:bg-white focus:outline-none focus:border-emerald-600 leading-relaxed"
                ></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeLaporanModal()" class="h-9 px-4 rounded-full border border-brand-ink/15 text-xs font-semibold hover:bg-brand-paper text-navy">
                    Batal
                </button>
                <button type="submit" class="h-9 px-5 rounded-full bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-xs">
                    Simpan Validasi
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openLaporanModal(id, catatan, status) {
        const modal = document.getElementById('laporanModal');
        const form = document.getElementById('laporanForm');
        form.action = "{{ url('/bkk/guru/laporan') }}/" + id + "/validate";
        document.getElementById('modalLaporanStatus').value = status || 'Disetujui';
        document.getElementById('modalLaporanCatatan').value = catatan || '';
        modal.classList.remove('hidden');
    }

    function closeLaporanModal() {
        document.getElementById('laporanModal').classList.add('hidden');
    }
</script>
@endsection
