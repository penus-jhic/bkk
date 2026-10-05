@extends('me.master')

@section('title', 'Laporan Akhir PKL - BKK SMK Plus Pelita Nusantara')

@section('content')
@if(!$isSiswa)
    @include('me.partials.siswa-only')
@else
    <div class="space-y-6 fade-up">
        <!-- Header -->
        <div>
            <h1 class="text-2xl font-bold text-navy tracking-tight">Laporan Akhir PKL</h1>
            <p class="text-xs sm:text-sm text-muted">Pantau status validasi dan pengumpulan draf naskah laporan pertanggungjawaban PKL per bab.</p>
        </div>

        @php
            $approvedBab = count(array_filter($laporanSections, fn($s) => $s['status'] === 'Disetujui'));
            $totalBab = count($laporanSections);
            $pctBab = round(($approvedBab / $totalBab) * 100);
        @endphp

        <!-- Overall Progress Card -->
        <div class="p-6 bg-white border border-line rounded-3xl shadow-sm">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                <div class="flex-1">
                    <div class="flex justify-between text-sm font-semibold mb-2">
                        <span class="text-navy">Progres Persetujuan Laporan</span>
                        <span class="text-navy" id="progressPctText">{{ $pctBab }}%</span>
                    </div>
                    <div class="h-3 rounded-full bg-line/60 overflow-hidden">
                        <div id="progressBarFill" class="h-full rounded-full bg-navy transition-all duration-700" style="width: {{ $pctBab }}%"></div>
                    </div>
                    <div class="text-xs text-muted mt-2">
                        <span id="approvedBabCount">{{ $approvedBab }}</span> dari {{ $totalBab }} bagian telah disetujui oleh pembimbing sekolah
                    </div>
                </div>

                <div class="flex items-center gap-3 rounded-2xl bg-maroon/5 border border-maroon/20 px-5 py-3.5 shrink-0">
                    <div class="w-10 h-10 rounded-xl bg-maroon/10 text-maroon grid place-items-center">
                        <i data-lucide="calendar-clock" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <div class="text-[11px] text-muted font-medium">Batas Pengumpulan Final</div>
                        <div class="font-bold text-sm text-maroon">{{ $deadlineStr ?? '30 April 2025' }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sections List -->
        <div class="space-y-3">
            @foreach($laporanSections as $sec)
                @php
                    $statusCls = match($sec['status']) {
                        'Disetujui' => 'bg-emerald-100 text-emerald-800',
                        'Revisi' => 'bg-maroon text-white',
                        'Ditinjau' => 'bg-amber-100 text-amber-800',
                        default => 'bg-gray-100 text-muted'
                    };
                @endphp
                <div class="p-5 bg-white border border-line rounded-2xl shadow-sm hover:shadow-md transition-shadow" id="bab-card-{{ $sec['id'] }}">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-start gap-3.5 flex-1">
                            <div class="mt-0.5">
                                @if($sec['status'] === 'Disetujui')
                                    <i data-lucide="check-circle-2" class="w-6 h-6 text-emerald-600"></i>
                                @elseif($sec['status'] === 'Revisi')
                                    <i data-lucide="alert-triangle" class="w-6 h-6 text-maroon"></i>
                                @else
                                    <i data-lucide="circle" class="w-6 h-6 text-line"></i>
                                @endif
                            </div>
                            <div>
                                <h3 class="font-bold text-sm text-navy">{{ $sec['title'] }}</h3>
                                <div class="text-xs text-muted mt-1 leading-relaxed">
                                    Catatan: <span class="font-medium text-navy/80">{{ $sec['note'] }}</span>
                                    <span class="mx-1.5">·</span>
                                    Diperbarui: <span class="text-muted/80">{{ $sec['updated'] }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 sm:justify-end shrink-0">
                            <span class="rounded-full px-3 py-1 text-xs font-semibold whitespace-nowrap {{ $statusCls }}" id="badge-{{ $sec['id'] }}">
                                {{ $sec['status'] === 'Belum' ? 'Belum Dikumpulkan' : $sec['status'] }}
                            </span>

                            @if($sec['status'] !== 'Disetujui')
                                <button
                                    id="btn-upload-{{ $sec['id'] }}"
                                    onclick="uploadBabDraft('{{ $sec['id'] }}', '{{ $sec['title'] }}')"
                                    class="h-8 px-4 rounded-full text-xs font-semibold inline-flex items-center gap-1.5 transition-colors shadow-sm cursor-pointer {{ $sec['status'] === 'Revisi' ? 'bg-maroon hover:bg-maroon-dark text-white' : 'border border-line bg-white hover:bg-canvas text-navy' }}"
                                >
                                    <i data-lucide="{{ $sec['status'] === 'Revisi' ? 'file-up' : 'upload-cloud' }}" class="w-3.5 h-3.5"></i>
                                    <span>{{ $sec['status'] === 'Revisi' ? 'Unggah Revisi' : 'Unggah Draf' }}</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
    async function uploadBabDraft(id, title) {
        const btn = document.getElementById('btn-upload-' + id);
        const badge = document.getElementById('badge-' + id);
        if (!btn) return;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        btn.disabled = true;
        btn.innerHTML = `<i data-lucide="loader-2" class="w-3.5 h-3.5 animate-spin"></i> Mengunggah…`;
        lucide.createIcons();

        try {
            const res = await fetch("{{ route('bkk.me.laporan.store') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": csrfToken
                },
                body: JSON.stringify({
                    nomor_bab: parseInt(id),
                    judul_bab: title
                })
            });

            const data = await res.json();

            if (!res.ok) {
                showToast(data.message || 'Gagal mengunggah draf bab laporan.');
                btn.disabled = false;
                btn.innerHTML = `<i data-lucide="upload-cloud" class="w-3.5 h-3.5"></i> <span>Unggah Draf</span>`;
                lucide.createIcons();
                return;
            }

            btn.innerHTML = `<i data-lucide="check" class="w-3.5 h-3.5"></i> Terkirim`;
            btn.className = "h-8 px-4 rounded-full bg-amber-100 text-amber-800 text-xs font-semibold cursor-not-allowed border border-amber-300";
            if (badge) {
                badge.className = "rounded-full px-3 py-1 text-xs font-semibold whitespace-nowrap bg-amber-100 text-amber-800";
                badge.innerText = "Ditinjau";
            }
            lucide.createIcons();
            showToast(data.message || `Berkas draf untuk "${title}" berhasil diunggah dan sedang ditinjau pembimbing.`);
        } catch (err) {
            btn.disabled = false;
            btn.innerHTML = `<i data-lucide="upload-cloud" class="w-3.5 h-3.5"></i> <span>Unggah Draf</span>`;
            lucide.createIcons();
            showToast('Terjadi kesalahan jaringan saat mengunggah draf bab.');
        }
    }
</script>
@endpush
