@extends('admin.master')

@section('title', 'Tracer Study Alumni - Admin BKK')

@section('content')
<div class="flex flex-col gap-6">
    <!-- Header & Action -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-muted mb-1">
                <a href="{{ route('bkk.admin.index') }}" class="hover:text-navy">Dashboard</a>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5"></i>
                <span class="text-navy font-semibold">Tracer Study</span>
            </div>
            <h1 class="text-2xl font-bold font-headline text-navy">
                Rekapitulasi Tracer Study Alumni
            </h1>
            <p class="text-xs text-muted mt-1">
                Pantau penelusuran tamatan alumni, agregasi pilar BMW (Bekerja, Melanjutkan, Wirausaha), keselarasan kurikulum kejuruan, dan instrumen kuesioner.
            </p>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <button type="button" onclick="openKuesionerModal()" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-full bg-navy hover:bg-maroon text-white font-semibold text-xs transition-all shadow-sm cursor-pointer">
                <i data-lucide="plus-circle" class="w-4 h-4"></i>
                <span>Buat Instrumen Kuesioner</span>
            </button>
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
    @php
        $totalRespon = $stats['total_respon'] ?? 0;
        $bekerjaCount = $stats['bmw']['bekerja'] ?? 0;
        $wirausahaCount = $stats['bmw']['wirausaha'] ?? 0;
        $melanjutkanCount = $stats['bmw']['melanjutkan'] ?? 0;
        $mencariKerjaCount = $stats['bmw']['mencari_kerja'] ?? 0;

        $terserapCount = $bekerjaCount + $wirausahaCount + $melanjutkanCount;
        $terserapRate = $totalRespon > 0 ? (int) round(($terserapCount / $totalRespon) * 100) : 0;

        $selarasCount = ($stats['keselarasan']['sangat_selaras'] ?? 0) + ($stats['keselarasan']['selaras'] ?? 0);
        $selarasRate = $totalRespon > 0 ? (int) round(($selarasCount / $totalRespon) * 100) : 0;
    @endphp

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        <div class="p-4 rounded-2xl bg-white border border-line shadow-xs">
            <div class="text-[11px] font-semibold text-muted uppercase tracking-wider">Total Responden</div>
            <div class="text-2xl font-headline font-bold text-navy mt-1">{{ number_format($totalRespon) }}</div>
            <div class="text-[10px] text-muted mt-0.5">Alumni telah mengisi survei</div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-line shadow-xs">
            <div class="text-[11px] font-semibold text-emerald-700 uppercase tracking-wider">Tingkat Keterserapan</div>
            <div class="text-2xl font-headline font-bold text-emerald-700 mt-1">{{ $terserapRate }}%</div>
            <div class="text-[10px] text-muted mt-0.5">Bekerja, Melanjutkan, Wirausaha</div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-line shadow-xs">
            <div class="text-[11px] font-semibold text-blue-700 uppercase tracking-wider">Keselarasan Jurusan</div>
            <div class="text-2xl font-headline font-bold text-blue-700 mt-1">{{ $selarasRate }}%</div>
            <div class="text-[10px] text-muted mt-0.5">Sangat Selaras & Selaras</div>
        </div>

        <div class="p-4 rounded-2xl bg-white border border-line shadow-xs">
            <div class="text-[11px] font-semibold text-purple-700 uppercase tracking-wider">Rata-rata Waktu Tunggu</div>
            <div class="text-2xl font-headline font-bold text-purple-700 mt-1">{{ $stats['rata_rata_waktu_tunggu_bulan'] ?? 0 }} <span class="text-xs font-normal text-muted">Bulan</span></div>
            <div class="text-[10px] text-muted mt-0.5">Hingga diterima kerja pertama</div>
        </div>
    </div>

    <!-- Analitik 2 Kolom: Pilar BMW & Keselarasan Kurikulum -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Card 1: Distribusi Pilar BMW Kemendikbud -->
        <div class="bg-white rounded-3xl border border-line p-5 shadow-xs">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-line">
                <div>
                    <h3 class="text-sm font-bold text-navy uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="pie-chart" class="w-4 h-4 text-maroon"></i>
                        <span>Distribusi Pilar BMW Kemendikbud</span>
                    </h3>
                    <p class="text-[11px] text-muted mt-0.5">Komposisi penyerapan lulusan SMK Pelita Nusantara</p>
                </div>
            </div>

            <div class="space-y-4">
                @php
                    $bmwItems = [
                        ['label' => 'Bekerja di Industri', 'count' => $bekerjaCount, 'color' => 'bg-navy', 'text' => 'text-navy'],
                        ['label' => 'Melanjutkan Pendidikan (Kuliah)', 'count' => $melanjutkanCount, 'color' => 'bg-blue-600', 'text' => 'text-blue-600'],
                        ['label' => 'Wirausaha Mandiri', 'count' => $wirausahaCount, 'color' => 'bg-purple-600', 'text' => 'text-purple-600'],
                        ['label' => 'Sedang Mencari Kerja', 'count' => $mencariKerjaCount, 'color' => 'bg-amber-600', 'text' => 'text-amber-600'],
                    ];
                @endphp

                @foreach($bmwItems as $item)
                    @php
                        $pct = $totalRespon > 0 ? (int) round(($item['count'] / $totalRespon) * 100) : 0;
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1.5 font-medium">
                            <span class="text-navy flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full {{ $item['color'] }}"></span>
                                <span>{{ $item['label'] }}</span>
                            </span>
                            <span class="font-bold text-navy">{{ $item['count'] }} orang ({{ $pct }}%)</span>
                        </div>
                        <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500 {{ $item['color'] }}" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Card 2: Keselarasan Kompetensi Kejuruan -->
        <div class="bg-white rounded-3xl border border-line p-5 shadow-xs">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-line">
                <div>
                    <h3 class="text-sm font-bold text-navy uppercase tracking-wider flex items-center gap-2">
                        <i data-lucide="award" class="w-4 h-4 text-emerald-600"></i>
                        <span>Relevansi Kurikulum & Kejuruan</span>
                    </h3>
                    <p class="text-[11px] text-muted mt-0.5">Kesesuaian jurusan sekolah dengan profesi kerja saat ini</p>
                </div>
            </div>

            <div class="space-y-4">
                @php
                    $relevansiItems = [
                        ['label' => 'Sangat Selaras (Relevan Tinggi)', 'key' => 'sangat_selaras', 'badge' => 'bg-emerald-100 text-emerald-800', 'bar' => 'bg-emerald-600'],
                        ['label' => 'Selaras (Cukup Relevan)', 'key' => 'selaras', 'badge' => 'bg-blue-100 text-blue-800', 'bar' => 'bg-blue-600'],
                        ['label' => 'Kurang Selaras', 'key' => 'kurang_selaras', 'badge' => 'bg-amber-100 text-amber-800', 'bar' => 'bg-amber-600'],
                        ['label' => 'Tidak Selaras (Lintas Bidang)', 'key' => 'tidak_selaras', 'badge' => 'bg-rose-100 text-rose-800', 'bar' => 'bg-rose-600'],
                    ];
                @endphp

                @foreach($relevansiItems as $rel)
                    @php
                        $cnt = $stats['keselarasan'][$rel['key']] ?? 0;
                        $pct = $totalRespon > 0 ? (int) round(($cnt / $totalRespon) * 100) : 0;
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1.5 font-medium">
                            <span class="text-navy flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full {{ $rel['bar'] }}"></span>
                                <span>{{ $rel['label'] }}</span>
                            </span>
                            <span class="font-bold text-navy">{{ $cnt }} responden ({{ $pct }}%)</span>
                        </div>
                        <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500 {{ $rel['bar'] }}" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Section: Instrumen Survei Kuesioner Tracer Study -->
    <div class="bg-white rounded-2xl border border-line shadow-xs overflow-hidden">
        <div class="p-4 border-b border-line flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-navy uppercase tracking-wider flex items-center gap-2">
                    <i data-lucide="clipboard-list" class="w-4 h-4 text-maroon"></i>
                    <span>Instrumen Kuesioner Tracer Study</span>
                </h2>
                <p class="text-xs text-muted mt-0.5">Daftar bank survei evaluasi berkala untuk sasaran angkatan alumni.</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-navy/5 text-navy">
                {{ $kuesioners->count() }} Survei Diterbitkan
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-line bg-[#F5F7FA] text-[11px] font-bold text-muted uppercase tracking-wider">
                        <th class="py-3 px-4">Nama Survei Kuesioner</th>
                        <th class="py-3 px-4">Target Angkatan</th>
                        <th class="py-3 px-4">Periode Pengisian</th>
                        <th class="py-3 px-4 text-center">Butir Pertanyaan</th>
                        <th class="py-3 px-4 text-center">Responden Masuk</th>
                        <th class="py-3 px-4 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line text-xs">
                    @forelse($kuesioners as $k)
                        <tr class="hover:bg-canvas/50 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-navy">
                                    {{ $k->judul }}
                                </div>
                                <div class="text-[11px] text-muted mt-0.5">
                                    {{ $k->deskripsi ?? 'Survei keterserapan alumni dan evaluasi kurikulum' }}
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-800">
                                    Lulusan {{ $k->tahun_sasaran_lulusan }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-muted">
                                {{ $k->tanggal_mulai ? \Carbon\Carbon::parse($k->tanggal_mulai)->format('d M Y') : '-' }} s/d 
                                {{ $k->tanggal_selesai ? \Carbon\Carbon::parse($k->tanggal_selesai)->format('d M Y') : '-' }}
                            </td>

                            <td class="py-3.5 px-4 text-center font-bold text-navy">
                                {{ $k->pertanyaans_count ?? 0 }} Butir
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800">
                                    {{ $k->respons_count ?? 0 }} Respon
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                @if($k->is_aktif)
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        Aktif
                                    </span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                        Ditutup
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-10 text-center text-muted">
                                Belum ada instrumen kuesioner tracer study. Klik tombol "Buat Instrumen Kuesioner" untuk menerbitkan survei baru.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section: Riwayat Respon Alumni Terbaru -->
    <div class="bg-white rounded-2xl border border-line shadow-xs overflow-hidden">
        <div class="p-4 border-b border-line flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-navy uppercase tracking-wider flex items-center gap-2">
                    <i data-lucide="history" class="w-4 h-4 text-navy"></i>
                    <span>Riwayat Respon Pengisian Alumni Terkini</span>
                </h2>
                <p class="text-xs text-muted mt-0.5">Daftar entri data tracer study yang masuk dari portal siswa/alumni.</p>
            </div>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">
                Total {{ $recentResponses->total() }} Respon
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="border-b border-line bg-[#F5F7FA] text-[11px] font-bold text-muted uppercase tracking-wider">
                        <th class="py-3 px-4">Alumni & Jurusan</th>
                        <th class="py-3 px-4">Status Keterserapan (BMW)</th>
                        <th class="py-3 px-4">Instansi / Perusahaan / Kampus</th>
                        <th class="py-3 px-4">Keselarasan</th>
                        <th class="py-3 px-4">Waktu Tunggu</th>
                        <th class="py-3 px-4 text-right">Tanggal Pengisian</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line text-xs">
                    @forelse($recentResponses as $res)
                        @php
                            $siswa = $res->profilSiswa;
                            $bmwBadge = match($res->status_keterserapan) {
                                'Bekerja' => 'bg-navy text-white',
                                'Melanjutkan Pendidikan' => 'bg-blue-100 text-blue-800',
                                'Wirausaha' => 'bg-purple-100 text-purple-800',
                                default => 'bg-amber-100 text-amber-800',
                            };
                        @endphp
                        <tr class="hover:bg-canvas/50 transition-colors">
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-navy">
                                    {{ $siswa->nis ?? 'Alumni Penus' }}
                                </div>
                                <div class="text-[11px] text-muted">
                                    {{ $siswa->jurusan ?? 'Alumni' }} · Angkatan {{ $siswa->angkatan ?? '2023' }}
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold {{ $bmwBadge }}">
                                    {{ $res->status_keterserapan }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-navy">
                                    {{ $res->nama_instansi_atau_usaha ?? '-' }}
                                </div>
                                <div class="text-[11px] text-muted">
                                    {{ $res->rentang_gaji_atau_omzet ? 'Gaji/Omzet: ' . $res->rentang_gaji_atau_omzet : '' }}
                                </div>
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="font-medium text-navy">
                                    {{ $res->keselarasan_jurusan ?? '-' }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="font-semibold text-navy">
                                    {{ $res->waktu_tunggu_bulan !== null ? $res->waktu_tunggu_bulan . ' Bulan' : '-' }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-right text-muted">
                                {{ $res->tanggal_pengisian ? $res->tanggal_pengisian->format('d M Y H:i') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-muted">
                                Belum ada riwayat pengisian kuesioner dari alumni.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($recentResponses->hasPages())
            <div class="p-4 border-t border-line">
                {{ $recentResponses->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal Buat Kuesioner Baru -->
<div id="kuesionerModal" class="fixed inset-0 bg-black/40 z-50 hidden place-items-center p-4">
    <div class="bg-white rounded-3xl border border-line max-w-lg w-full p-6 shadow-xl relative">
        <h3 class="text-sm font-bold text-navy mb-1 flex items-center gap-2">
            <i data-lucide="plus-circle" class="w-4 h-4 text-maroon"></i>
            <span>Terbitkan Instrumen Kuesioner Tracer Study Baru</span>
        </h3>
        <p class="text-xs text-muted mb-4">Buat instrumen survei penelusuran tamatan untuk angkatan alumni tertentu.</p>

        <form method="POST" action="{{ route('bkk.admin.tracer.kuesioner.store') }}" class="space-y-4">
            @csrf
            <div>
                <label for="judul" class="block text-xs font-semibold text-navy mb-1">
                    Nama / Judul Kuesioner <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="judul"
                    name="judul"
                    required
                    placeholder="Contoh: Tracer Study 1 Tahun Lulusan 2024"
                    class="w-full px-3.5 py-2 bg-canvas text-navy text-xs rounded-xl border border-line focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                />
            </div>

            <div>
                <label for="tahun_sasaran_lulusan" class="block text-xs font-semibold text-navy mb-1">
                    Tahun Sasaran Lulusan <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="tahun_sasaran_lulusan"
                    name="tahun_sasaran_lulusan"
                    required
                    placeholder="Contoh: 2024"
                    class="w-full px-3.5 py-2 bg-canvas text-navy text-xs rounded-xl border border-line focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="tanggal_mulai" class="block text-xs font-semibold text-navy mb-1">
                        Tanggal Mulai <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="date"
                        id="tanggal_mulai"
                        name="tanggal_mulai"
                        required
                        value="{{ date('Y-m-d') }}"
                        class="w-full px-3.5 py-2 bg-canvas text-navy text-xs rounded-xl border border-line focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                    />
                </div>
                <div>
                    <label for="tanggal_selesai" class="block text-xs font-semibold text-navy mb-1">
                        Tanggal Selesai <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="date"
                        id="tanggal_selesai"
                        name="tanggal_selesai"
                        required
                        value="{{ date('Y-m-d', strtotime('+3 months')) }}"
                        class="w-full px-3.5 py-2 bg-canvas text-navy text-xs rounded-xl border border-line focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                    />
                </div>
            </div>

            <div>
                <label for="deskripsi" class="block text-xs font-semibold text-navy mb-1">
                    Deskripsi & Petunjuk Pengisian
                </label>
                <textarea
                    id="deskripsi"
                    name="deskripsi"
                    rows="3"
                    placeholder="Tuliskan tujuan survei atau imbauan bagi alumni..."
                    class="w-full p-3 bg-canvas text-navy text-xs rounded-xl border border-line focus:bg-white focus:outline-none focus:ring-1 focus:ring-navy"
                ></textarea>
            </div>

            <div class="flex items-center justify-end gap-2 pt-2 border-t border-line">
                <button type="button" onclick="closeKuesionerModal()" class="px-4 py-2 rounded-full text-xs font-semibold text-muted hover:text-navy">
                    Batal
                </button>
                <button type="submit" class="px-5 py-2 rounded-full bg-navy hover:bg-maroon text-white font-semibold text-xs cursor-pointer shadow-xs transition-colors">
                    Terbitkan Kuesioner
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openKuesionerModal() {
        const modal = document.getElementById('kuesionerModal');
        modal.classList.remove('hidden');
        modal.classList.add('grid');
    }

    function closeKuesionerModal() {
        const modal = document.getElementById('kuesionerModal');
        modal.classList.add('hidden');
        modal.classList.remove('grid');
    }
</script>
@endsection
