{{-- Garis bawah coretan. sm = menu/kategori aktif, md = judul section, lg = judul hero. tone = warna goresan. --}}
@props(['size' => 'md', 'tone' => 'text-brand-darkred', 'delay' => 0])
@php
    [$position, $scale] = match ($size) {
        'sm' => ['-left-1 -right-1 -bottom-2 h-2', 0.55],
        'lg' => ['-left-3 -right-8 -bottom-4 h-4 sm:-bottom-5 sm:h-5 md:-bottom-6 md:h-6 lg:h-7', 1.5],
        default => ['-left-3 -right-5 -bottom-3 h-3.5 md:-bottom-4 md:h-4', 1],
    };
@endphp
<span {{ $attributes->class('relative inline-block') }}>{{ $slot }}<span data-sketch="underline" data-scale="{{ $scale }}" data-duration="450" data-stagger="130" data-delay="{{ $delay }}" aria-hidden="true" class="pointer-events-none absolute {{ $tone }} {{ $position }}"></span></span>