@extends('guru.master')

@section('title', 'Validasi Log Jurnal PKL - Portal Guru')

@section('content')
<div class="space-y-6 fade-up">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-brand-ink/10">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold text-navy tracking-tight">Validasi Log Jurnal PKL</h1>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                    {{ $stats['menunggu'] }} Menunggu Validasi
                </span>
            </div>
            <p class="text-xs sm:text-sm text-muted mt-1">Periksa catatan kegiatan harian siswa di industri dan lakukan validasi jam kerja akumulatif.</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="text-xs text-muted">Akumulasi standar jam SMK: <strong>640 Jam</strong></span>
        </div>
    </div>

    <!-- Filter & Pencarian -->
    <div class="p-4 rounded-2xl bg-white border border-brand-ink/10 shadow-xs">
        <form method="GET" action="{{ route('bkk.guru.jurnal.index') }}" class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="flex-1 relative">
                <i data-lucide="search" class="w-4 h-4 text-muted absolute left-3.5 top-1/2 -translate-y-1/2"></i>
                <input
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Cari aktivitas jurnal, nama siswa, atau mitra industri…"
                    class="w-full h-10 pl-10 pr-4 rounded-full bg-canvas border border-brand-ink/15 text-xs text-navy focus:outline-none focus:border-emerald-600 focus:bg-white transition-all"
                />
            </div>

            <div class="flex items-center gap-2">
                <select name="status" onchange="this.form.submit()" class="h-10 px-4 rounded-full bg-canvas border border-brand-ink/15 text-xs font-medium text-navy focus:outline-none focus:border-emerald-600 cursor-pointer">
                    <option value="Semua">Semua Status</option>
                    <option value="Menunggu" {{ $statusFilter === 'Menunggu' ? 'selected' : '' }}>Menunggu Validasi ({{ $stats['menunggu'] }})</option>
                    <option value="Disetujui" {{ $statusFilter === 'Disetujui' ? 'selected' : '' }}>Disetujui ({{ $stats['disetujui'] }})</option>
                    <option value="Revisi" {{ $statusFilter === 'Revisi' ? 'selected' : '' }}>Perlu Revisi ({{ $stats['revisi'] }})</option>
                </select>

                <button type="submit" class="h-10 px-5 rounded-full bg-navy hover:bg-navy-dark text-white text-xs font-semibold shadow-xs transition-colors cursor-pointer">
                    Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Daftar Log Jurnal Harian -->
    <div class="space-y-3">
        @forelse($jurnals as $j)
            @php
                $siswa = $j->penempatan?->siswa;
                $mitra = $j->penempatan?->mitra;
                $statusBadge = match($j->status) {
                    'Disetujui' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                    'Revisi' => 'bg-red-100 text-red-800 border-red-300',
                    default => 'bg-amber-100 text-amber-800 border-amber-300',
                };
            @endphp
            <div class="p-5 rounded-3xl bg-white border border-brand-ink/10 shadow-sm space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 border-b border-brand-ink/10 pb-3">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="font-bold text-sm text-navy">{{ $siswa->nis ?? '-' }} — {{ $siswa->jurusan ?? 'Jurusan' }}</span>
                            <span class="text-xs text-muted">di <strong>{{ $mitra->nama_perusahaan ?? 'Mitra Industri' }}</strong></span>
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold border {{ $statusBadge }}">
                                {{ $j->status }}
                            </span>
                        </div>
                        <div class="text-[11px] text-muted flex items-center gap-3">
                            <span>Tanggal: <strong>{{ $j->tanggal ? $j->tanggal->format('l, d F Y') : '-' }}</strong></span>
                            <span>Durasi: <strong class="text-navy">{{ $j->durasi_jam }} Jam</strong></span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                        <!-- Quick Action Setujui Langsung -->
                        @if($j->status !== 'Disetujui')
                            <form action="{{ route('bkk.guru.jurnal.validate', $j->id) }}" method="POST">
                                @csrf
                                <input type="hidden" name="status" value="Disetujui" />
                                <button type="submit" class="h-8 px-3.5 rounded-full bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold inline-flex items-center gap-1 shadow-xs transition-colors cursor-pointer" title="Langsung Setujui Jurnal">
                                    <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                    <span>Setujui</span>
                                </button>
                            </form>
                        @endif

                        <!-- Tombol Revisi Modal -->
                        <button type="button" onclick="openJurnalModal('{{ $j->id }}', '{{ $j->catatan_revisi }}', '{{ $j->status }}')" class="h-8 px-3.5 rounded-full border border-brand-ink/15 hover:bg-brand-paper text-navy text-xs font-semibold inline-flex items-center gap-1 transition-colors cursor-pointer">
                            <i data-lucide="edit-3" class="w-3.5 h-3.5 text-muted"></i>
                            <span>Review / Catatan</span>
                        </button>
                    </div>
                </div>

                <!-- Teks Aktivitas -->
                <div class="p-3.5 rounded-2xl bg-canvas text-xs text-navy leading-relaxed">
                    {{ $j->aktivitas }}
                </div>

                @if(!empty($j->catatan_revisi))
                    <div class="text-[11px] text-red-700 bg-red-50 p-2.5 rounded-xl border border-red-200 flex items-center gap-2">
                        <i data-lucide="alert-circle" class="w-3.5 h-3.5 shrink-0"></i>
                        <span><strong>Catatan Guru:</strong> {{ $j->catatan_revisi }}</span>
                    </div>
                @endif
            </div>
        @empty
            <div class="p-12 text-center rounded-3xl bg-white border border-brand-ink/10 text-xs text-muted">
                Tidak ada log jurnal yang cocok dengan filter.
            </div>
        @endforelse

        @if($jurnals->hasPages())
            <div class="p-4 bg-white rounded-2xl border border-brand-ink/10">
                {{ $jurnals->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Validasi Jurnal -->
<div id="jurnalModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 space-y-4 shadow-xl fade-up">
        <div class="flex items-center justify-between border-b border-brand-ink/10 pb-3">
            <h3 class="font-bold text-base text-navy">Validasi Log Jurnal Siswa</h3>
            <button type="button" onclick="closeJurnalModal()" class="text-muted hover:text-navy text-lg font-bold">×</button>
        </div>

        <form id="jurnalForm" method="POST" action="" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-bold text-navy mb-1.5">Pilih Status Validasi</label>
                <select name="status" id="modalJurnalStatus" class="w-full h-10 px-3.5 rounded-xl bg-canvas border border-brand-ink/15 text-xs font-medium text-navy">
                    <option value="Disetujui">Disetujui (Jam kerja dihitung akumulatif)</option>
                    <option value="Revisi">Perlu Revisi (Siswa wajib memperbaiki catatan)</option>
                    <option value="Menunggu">Menunggu</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-navy mb-1.5">Catatan Revisi / Evaluasi (Opsional)</label>
                <textarea
                    name="catatan_revisi"
                    id="modalJurnalCatatan"
                    rows="3"
                    placeholder="Tuliskan catatan arahan jika diperlukan perbaikan aktivitas..."
                    class="w-full p-3 rounded-xl bg-canvas border border-brand-ink/15 text-xs text-navy focus:bg-white focus:outline-none focus:border-emerald-600 leading-relaxed"
                ></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2">
                <button type="button" onclick="closeJurnalModal()" class="h-9 px-4 rounded-full border border-brand-ink/15 text-xs font-semibold hover:bg-brand-paper text-navy">
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
    function openJurnalModal(id, catatan, status) {
        const modal = document.getElementById('jurnalModal');
        const form = document.getElementById('jurnalForm');
        form.action = "{{ url('/bkk/guru/jurnal') }}/" + id + "/validate";
        document.getElementById('modalJurnalStatus').value = status || 'Disetujui';
        document.getElementById('modalJurnalCatatan').value = catatan || '';
        modal.classList.remove('hidden');
    }

    function closeJurnalModal() {
        document.getElementById('jurnalModal').classList.add('hidden');
    }
</script>
@endsection
