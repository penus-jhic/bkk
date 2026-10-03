{{-- Logo perusahaan mitra, atau inisial nama di atas gradasi merah selama belum ada logo.
     class = ukuran, sudut & ukuran huruf inisial (mis. "w-12 h-12 rounded-xl text-base"). --}}
@props(['mitra' => null])
@php
    $name = $mitra->nama_perusahaan ?? 'Mitra BKK';
    // "PT Solusi Teknologi Nusantara" -> "ST": badan usaha di depan dilewati
    $initials = collect(preg_split('/\s+/', preg_replace('/^(PT|CV|UD|PD|KAP|Yayasan)\.?\s+/i', '', $name)))
        ->filter()
        ->take(2)
        ->map(fn ($word) => mb_substr($word, 0, 1))
        ->implode('');
@endphp
<span {{ $attributes->class('relative shrink-0 overflow-hidden bg-linear-to-br from-brand-signal to-brand-deepred flex items-center justify-center font-display font-bold uppercase tracking-wide text-white') }}>
    <span aria-hidden="true">{{ $initials }}</span>
    @if (!empty($mitra?->logo_url))
        <img src="{{ $mitra->logo_url }}" alt="" class="absolute inset-0 size-full object-cover bg-white" loading="lazy" onerror="this.remove()">
    @endif
</span>
