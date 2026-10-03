{{-- Satu siku coretan (garis atas + garis kiri). class wajib berisi posisi & ukuran; arah lain didapat dengan memutar:
     rotate-90 = kanan atas, rotate-180 = kanan bawah, -rotate-90 = kiri bawah. --}}
@props(['delay' => 0])
<span data-sketch="corner" data-delay="{{ $delay }}" aria-hidden="true" {{ $attributes->class('pointer-events-none absolute text-brand-darkred') }}></span>