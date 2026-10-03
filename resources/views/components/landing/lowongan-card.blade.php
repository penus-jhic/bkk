{{-- Kartu lowongan, dipakai di beranda, daftar lowongan & "Lowongan Lainnya" di halaman detail --}}
@props(['lowongan'])
@php
    $mitra = $lowongan->mitra;
    $isPkl = $lowongan->tipe === 'PKL';
    $daysLeft = $lowongan->deadline
        ? (int) now()->startOfDay()->diffInDays($lowongan->deadline->copy()->startOfDay(), false)
        : null;
    [$deadlineLabel, $urgent] = match (true) {
        $daysLeft === null => ['Tanpa batas waktu', false],
        $daysLeft < 0 => ['Pendaftaran ditutup', false],
        $daysLeft === 0 => ['Hari terakhir', true],
        $daysLeft <= 7 => [$daysLeft . ' hari lagi', true],
        default => ['Tutup ' . $lowongan->deadline->locale('id')->translatedFormat('j M Y'), false],
    };
@endphp
{{-- text-left: kartu sempit, teks yang terbungkus jadi renggang kalau ikut justify dari body --}}
<a
    href="{{ route('bkk.lowongan.detail', $lowongan->slug ?: $lowongan->id) }}"
    {{ $attributes->class('group relative h-full flex flex-col rounded-card bg-white p-5 md:p-6 text-left shadow-softpill ring-1 ring-brand-ink/5 transition-all duration-300 hover:-translate-y-1 hover:ring-brand-darkred/25 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-darkred') }}
>
    <div class="flex items-start justify-between gap-4">
        <x-landing.company-logo :mitra="$mitra" class="w-12 h-12 rounded-xl text-base" />
        <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $isPkl ? 'bg-brand-darkred/10 text-brand-darkred' : 'bg-brand-ink text-white' }}">
            {{ $isPkl ? 'PKL Siswa' : 'Kerja Alumni' }}
        </span>
    </div>

    <h3 class="mt-5 text-lg font-semibold leading-snug line-clamp-2 transition-colors group-hover:text-brand-darkred">{{ $lowongan->judul }}</h3>
    <p class="mt-1 text-sm text-brand-ink/60 line-clamp-1">{{ $mitra->nama_perusahaan ?? 'Mitra Industri BKK' }}</p>

    <ul class="mt-5 space-y-2.5 text-sm text-brand-ink/75">
        <li class="flex items-start gap-2.5">
            <x-landing.icon name="mapPin" class="w-4 h-4 mt-0.5 shrink-0 text-brand-darkred" />
            <span class="line-clamp-1"><span class="sr-only">Lokasi: </span>{{ $lowongan->lokasi }}</span>
        </li>
        <li class="flex items-start gap-2.5">
            <x-landing.icon name="school" class="w-4 h-4 mt-0.5 shrink-0 text-brand-darkred" />
            <span class="line-clamp-1"><span class="sr-only">Jurusan: </span>{{ $lowongan->target_jurusan }}</span>
        </li>
        @if ($lowongan->gaji_kompensasi)
            <li class="flex items-start gap-2.5">
                <x-landing.icon name="wallet" class="w-4 h-4 mt-0.5 shrink-0 text-brand-darkred" />
                <span class="line-clamp-1 font-medium text-brand-ink"><span class="sr-only">Gaji: </span>{{ $lowongan->gaji_kompensasi }}</span>
            </li>
        @endif
    </ul>

    {{-- mt-auto: di baris yang tingginya disamakan, bagian bawah menempel ke dasar kartu --}}
    <div class="mt-auto pt-6">
        <div class="flex items-center justify-between gap-3 border-t border-dashed border-brand-ink/15 pt-4">
            <span class="flex items-center gap-1.5 text-xs font-semibold {{ $urgent ? 'text-brand-signal' : 'text-brand-ink/50' }}">
                <x-landing.icon name="clock" class="w-3.5 h-3.5" />
                {{ $deadlineLabel }}
            </span>
            <span class="flex items-center gap-1.5 text-sm font-semibold text-brand-darkred">
                Lihat Detail
                <x-sketch.arrow class="w-6 h-3 transition-transform group-hover:translate-x-1" />
            </span>
        </div>
    </div>
</a>
