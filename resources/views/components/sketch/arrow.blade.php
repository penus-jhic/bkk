{{-- Panah coretan menghadap kanan (balik dengan "-scale-x-100") atau ke bawah (direction="down").
     class = ukuran 2:1 (mis. w-7 h-3.5) atau 1:2 untuk panah bawah. Warna mengikuti currentColor induknya. --}}
@props(['delay' => 0, 'direction' => 'right'])
<span {{ $attributes->class('relative block') }}><span data-sketch="{{ $direction === 'down' ? 'arrowDown' : 'arrow' }}" data-duration="350" data-stagger="150" data-delay="{{ $delay }}" aria-hidden="true" class="pointer-events-none absolute inset-0"></span></span>