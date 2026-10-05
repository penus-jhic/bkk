@extends('me.master')

@section('title', 'Jurnal PKL Harian - BKK SMK Plus Pelita Nusantara')

@section('content')
@if(!$isSiswa)
    @include('me.partials.siswa-only')
@else
    <div class="space-y-6 fade-up">
        <!-- Title & Subtitle -->
        <div>
            <h1 class="text-2xl font-bold text-navy tracking-tight">Jurnal PKL Harian</h1>
            <p class="text-xs sm:text-sm text-muted">Catat agenda dan aktivitas harian PKL untuk divalidasi oleh pembimbing industri & guru sekolah.</p>
        </div>

        <!-- 3 Stats Cards -->
        @php
            $totalHours = $totalHours ?? 0;
            $targetHours = $targetHours ?? 640;
            $pctHours = $targetHours > 0 ? min(100, round(($totalHours / $targetHours) * 100)) : 0;
            $approvedCount = $approvedCount ?? count(array_filter($jurnalEntries, fn($j) => $j['status'] === 'Disetujui'));
            $revisionCount = $revisionCount ?? count(array_filter($jurnalEntries, fn($j) => $j['status'] === 'Revisi'));
            $mitraNama = $mitraNama ?? 'Belum Ada Penempatan';
            $pembimbingNama = $pembimbingNama ?? 'Belum terdaftar penempatan PKL';
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-5 bg-white border border-line rounded-2xl shadow-sm">
                <div class="text-xs text-muted font-medium flex items-center gap-2">
                    <i data-lucide="clock" class="w-4 h-4 text-navy"></i>
                    <span>Total Jam PKL</span>
                </div>
                <div class="text-2xl sm:text-3xl font-bold text-navy mt-2">
                    {{ $totalHours }}<span class="text-sm text-muted font-normal"> / {{ $targetHours }} jam</span>
                </div>
                <div class="h-2 rounded-full bg-line/60 overflow-hidden mt-3">
                    <div class="h-full rounded-full bg-navy" style="width: {{ $pctHours }}%"></div>
                </div>
            </div>

            <div class="p-5 bg-white border border-line rounded-2xl shadow-sm">
                <div class="text-xs text-muted font-medium flex items-center gap-2">
                    <i data-lucide="map-pin" class="w-4 h-4 text-navy"></i>
                    <span>Tempat PKL</span>
                </div>
                <div class="font-bold text-base sm:text-lg text-navy mt-2 truncate">{{ $mitraNama }}</div>
                <div class="text-xs text-muted mt-1 truncate">{{ $pembimbingNama }}</div>
            </div>

            <div class="p-5 bg-white border border-line rounded-2xl shadow-sm">
                <div class="text-xs text-muted font-medium flex items-center gap-2">
                    <i data-lucide="check-circle-2" class="w-4 h-4 text-emerald-600"></i>
                    <span>Jurnal Disetujui</span>
                </div>
                <div class="text-2xl sm:text-3xl font-bold text-navy mt-2">{{ $approvedCount }}</div>
                <div class="text-xs text-maroon font-semibold mt-1">{{ $revisionCount }} catatan perlu perbaikan</div>
            </div>
        </div>

        <!-- Main Content: Form Log Hari Ini + Daftar Entri -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-start">
            <!-- Left: Form Input (2 cols) -->
            <div class="lg:col-span-2 p-5 sm:p-6 bg-white border border-line rounded-3xl shadow-sm space-y-4">
                <div>
                    <div class="flex items-center gap-2 font-bold text-navy text-sm sm:text-base">
                        <i data-lucide="notebook-pen" class="w-5 h-5 text-maroon"></i>
                        <span>Log Cepat Hari Ini</span>
                    </div>
                    <div class="text-xs text-muted mt-1 flex items-center gap-1">
                        <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                        <span>{{ date('l, d F Y') }}</span>
                    </div>
                </div>

                <div>
                    <textarea
                        id="jurnalActivity"
                        rows="5"
                        placeholder="Contoh: Konfigurasi router Mikrotik dan penyambungan kabel drop core FO ke pelanggan…"
                        class="w-full p-3.5 rounded-2xl border border-line bg-canvas/40 focus:bg-white focus:border-navy outline-none text-xs sm:text-sm text-navy leading-relaxed transition"
                    ></textarea>
                </div>

                <!-- Durasi Jam -->
                <div>
                    <span class="text-xs font-medium text-muted block mb-1.5">Durasi Jam Kerja</span>
                    <div class="flex gap-1.5" id="hoursButtonGroup">
                        @foreach([4, 6, 7, 8] as $h)
                            <button
                                type="button"
                                onclick="selectHours({{ $h }})"
                                data-hours="{{ $h }}"
                                class="h-8 px-3.5 rounded-full text-xs font-semibold border transition-all cursor-pointer {{ $h === 8 ? 'bg-navy text-white border-navy' : 'bg-white text-navy border-line hover:bg-canvas' }}"
                            >
                                {{ $h }} jam
                            </button>
                        @endforeach
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex gap-2 pt-2">
                    <button
                        type="button"
                        id="btnPolishJurnal"
                        onclick="polishJurnalWithAi()"
                        class="h-9 px-3.5 rounded-full bg-maroon/10 hover:bg-maroon hover:text-white text-maroon text-xs font-semibold inline-flex items-center gap-1.5 transition-colors cursor-pointer"
                    >
                        <i data-lucide="sparkles" class="w-3.5 h-3.5"></i>
                        <span id="btnPolishText">Rapikan AI</span>
                    </button>
                    <button
                        type="button"
                        onclick="submitJurnalEntry()"
                        class="flex-1 h-9 px-4 rounded-full bg-navy hover:bg-navy-dark text-white text-xs font-semibold inline-flex items-center justify-center gap-1.5 shadow-sm transition-colors cursor-pointer"
                    >
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Kirim Jurnal</span>
                    </button>
                </div>
            </div>

            <!-- Right: Daftar Riwayat Jurnal (3 cols) -->
            <div class="lg:col-span-3 space-y-3" id="jurnalListContainer">
                @forelse($jurnalEntries as $entry)
                    @php
                        $badgeCls = match($entry['status']) {
                            'Disetujui' => 'bg-emerald-100 text-emerald-800',
                            'Menunggu' => 'bg-amber-100 text-amber-800',
                            'Revisi' => 'bg-maroon text-white',
                            default => 'bg-gray-100 text-muted'
                        };
                    @endphp
                    <div class="p-4 sm:p-5 bg-white border border-line rounded-2xl shadow-sm hover:shadow-md transition-shadow">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-xs text-muted font-medium">{{ $entry['date'] }} · <span class="text-navy font-semibold">{{ $entry['hours'] }} jam</span></div>
                                <div class="text-xs sm:text-sm text-navy mt-1.5 leading-relaxed font-medium">{{ $entry['activity'] }}</div>
                                @if(!empty($entry['catatan']))
                                    <div class="mt-2 text-xs bg-canvas p-2.5 rounded-xl border border-line text-navy/90">
                                        <span class="font-semibold text-maroon">Catatan Pembimbing:</span> {{ $entry['catatan'] }}
                                    </div>
                                @endif
                            </div>
                            <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold whitespace-nowrap {{ $badgeCls }}">
                                {{ $entry['status'] }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div id="jurnalEmptyCard" class="p-8 bg-white border border-line rounded-2xl text-center space-y-2">
                        <div class="w-12 h-12 rounded-full bg-canvas border border-line flex items-center justify-center mx-auto text-muted">
                            <i data-lucide="notebook" class="w-6 h-6"></i>
                        </div>
                        <div class="font-bold text-sm text-navy">Belum Ada Catatan Jurnal</div>
                        <p class="text-xs text-muted max-w-sm mx-auto">Anda belum mencatat aktivitas PKL. Gunakan formulir di sebelah kiri untuk mengisi log aktivitas harian Anda.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
@endif
@endsection

@push('scripts')
<script>
    let selectedDuration = 8;

    function selectHours(h) {
        selectedDuration = h;
        document.querySelectorAll('#hoursButtonGroup button').forEach(btn => {
            const val = parseInt(btn.getAttribute('data-hours'));
            if (val === h) {
                btn.className = "h-8 px-3.5 rounded-full text-xs font-semibold border transition-all cursor-pointer bg-navy text-white border-navy";
            } else {
                btn.className = "h-8 px-3.5 rounded-full text-xs font-semibold border transition-all cursor-pointer bg-white text-navy border-line hover:bg-canvas";
            }
        });
    }

    function polishJurnalWithAi() {
        const textarea = document.getElementById('jurnalActivity');
        const textBtn = document.getElementById('btnPolishText');
        const btn = document.getElementById('btnPolishJurnal');
        if (!textarea || !textarea.value.trim()) {
            showToast('Silakan tuliskan catatan kegiatan terlebih dahulu.');
            return;
        }

        btn.disabled = true;
        textBtn.innerText = 'Memoles…';

        setTimeout(() => {
            const raw = textarea.value.trim().replace(/\.$/, '');
            textarea.value = `Melaksanakan ${raw.charAt(0).toLowerCase() + raw.slice(1)} sesuai SOP standar industri dan melaporkan rekap hasil pengujian kepada pembimbing teknis lapangan.`;
            btn.disabled = false;
            textBtn.innerText = 'Rapikan AI';
            showToast('Kalimat jurnal berhasil dirapikan dengan gaya formal SOP.');
        }, 1000);
    }

    async function submitJurnalEntry() {
        const textarea = document.getElementById('jurnalActivity');
        const list = document.getElementById('jurnalListContainer');
        const submitBtn = document.querySelector('button[onclick="submitJurnalEntry()"]');

        if (!textarea || !textarea.value.trim() || !list) {
            showToast('Aktivitas tidak boleh kosong.');
            return;
        }

        const activity = textarea.value.trim();
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const todayDate = `${year}-${month}-${day}`;

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = `<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Menyimpan…`;
            lucide.createIcons();
        }

        try {
            const res = await fetch("{{ route('bkk.me.jurnal.store') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": csrfToken
                },
                body: JSON.stringify({
                    tanggal: todayDate,
                    aktivitas: activity,
                    durasi_jam: selectedDuration
                })
            });

            const data = await res.json();

            if (!res.ok) {
                showToast(data.message || 'Gagal menyimpan aktivitas harian.');
                return;
            }

            const emptyCard = document.getElementById('jurnalEmptyCard');
            if (emptyCard) {
                emptyCard.remove();
            }

            const dateStr = now.toLocaleDateString('id-ID', { weekday: 'long', day: '2-digit', month: 'short', year: 'numeric' });
            const card = document.createElement('div');
            card.className = "p-4 sm:p-5 bg-white border border-line rounded-2xl shadow-sm hover:shadow-md transition-shadow fade-up";
            card.innerHTML = `
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <div class="text-xs text-muted font-medium">${dateStr} · <span class="text-navy font-semibold">${selectedDuration} jam</span></div>
                        <div class="text-xs sm:text-sm text-navy mt-1.5 leading-relaxed font-medium">${activity}</div>
                    </div>
                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold whitespace-nowrap bg-amber-100 text-amber-800">
                        Menunggu
                    </span>
                </div>
            `;

            list.insertBefore(card, list.firstChild);
            textarea.value = '';
            showToast(data.message || 'Jurnal kegiatan harian berhasil dikirimkan ke pembimbing.');
        } catch (err) {
            showToast('Terjadi kesalahan jaringan saat menyimpan jurnal.');
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = `<span>Kirim Log Aktivitas</span> <i data-lucide="arrow-right" class="w-4 h-4"></i>`;
                lucide.createIcons();
            }
        }
    }
</script>
@endpush
