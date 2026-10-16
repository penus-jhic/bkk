{{-- Kepala dokumen portal mitra (dashboard & halaman login): meta, lalu tema dashboard bersama (font, ikon, Tailwind, token warna). --}}
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
@include('partials.dashboard-theme')
