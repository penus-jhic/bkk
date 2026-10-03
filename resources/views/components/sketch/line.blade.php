{{-- Garis panjang coretan, mis. pemisah section. class wajib berisi posisi, lebar & tinggi (h-3). --}}
@props(['delay' => 0])
<span data-sketch="line" data-duration="900" data-delay="{{ $delay }}" aria-hidden="true" {{ $attributes->class('pointer-events-none absolute text-brand-darkred') }}></span>