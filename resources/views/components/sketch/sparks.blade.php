{{-- Tiga garis "kilau" kecil di pojok kanan atas kata, muncul satu per satu --}}
@props(['tone' => 'text-brand-darkred'])
<span {{ $attributes->class('relative inline-block') }}>{{ $slot }}<span data-sketch="sparks" data-duration="300" data-stagger="150" aria-hidden="true" class="pointer-events-none absolute {{ $tone }} -right-6 -top-3 w-7 h-7 md:-right-8 md:-top-4 md:w-9 md:h-9"></span></span>