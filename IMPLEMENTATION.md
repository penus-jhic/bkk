# Panduan Implementasi Slicing UI & Master Layout BKK Penus

Dokumen ini menjelaskan arsitektur, pemisahan komponen (slicing), serta implementasi routing untuk halaman publik BKK (Bursa Kerja Khusus) SMK Plus Pelita Nusantara.

---

## 1. Ringkasan & Tujuan

Tujuan implementasi ini adalah merapikan dan memecah halaman-halaman monolitik di `resources/views/index/pages/*` menjadi arsitektur Blade yang modular, DRY (*Don't Repeat Yourself*), dan mudah dikelola:
1. **Master Layout**: Menyediakan satu kerangka HTML dasar (`<head>`, meta tags, fonts, Tailwind CSS CDN & config, `<main>`, `@yield('content')`, scripts) di `resources/views/index/master.blade.php`.
2. **Pemisahan Partials**:
   - `resources/views/index/partials/header.blade.php` (Brand logo, mobile toggle, auth/profile CTA).
   - `resources/views/index/partials/navbar.blade.php` (Navigasi desktop & drawer mobile dengan deteksi active state dinamis).
   - `resources/views/index/partials/footer.blade.php` (Navigasi footer lengkap, info IDUKA, kontak sekretariat BKK).
3. **Penyelarasan dengan Sitemap (`SITEMAP.md`)**:
   - Menghubungkan seluruh menu navigasi ke endpoint Laravel nyata menggunakan helper `url()` atau `route()`.
   - Melengkapi seluruh controller method di `app/Http/Controllers/PublicController.php` dan `routes/web.php`.
   - Menyediakan halaman `tentang.blade.php` agar tidak ada tautan buntu (404).

---

## 2. Struktur Direktori Target

```
resources/views/index/
├── master.blade.php                      # Master page layout (HTML5 skeleton, meta, Tailwind config, slots)
├── partials/
│   ├── header.blade.php                  # Header bar utama (sticky blur, logo, CTA, include navbar)
│   ├── navbar.blade.php                  # Daftar menu & link aktif (Desktop + Mobile Drawer)
│   └── footer.blade.php                  # Footer global (Akses cepat, kontak, kemitraan, hak cipta)
└── pages/
    ├── index.blade.php                   # Landpage BKK (extends master)
    ├── berita.blade.php                  # List Berita & Agenda (extends master)
    ├── berita-detail.blade.php           # Detail Berita & Agenda (extends master)
    ├── lowongan.blade.php                # List Lowongan PKL & Kerja (extends master)
    ├── lowongan-detail.blade.php         # Detail Lowongan PKL & Kerja (extends master)
    ├── kerjasama.blade.php               # Portal Kerja Sama Mitra (extends master)
    └── tentang.blade.php                 # Profil & Struktur BKK Penus (extends master)
```

---

## 3. Pemetaan Routing & Sitemap (`routes/web.php`)

Sesuai dengan `SITEMAP.md`, seluruh endpoint publik wajib berada di bawah prefix `/bkk`:

| Endpoint URL | Route Name | Controller Method | View Blade | Keterangan |
| :--- | :--- | :--- | :--- | :--- |
| `/bkk` | `bkk.index` | `PublicController@index` | `index.pages.index` | Halaman utama / Landpage BKK |
| `/bkk/berita` | `bkk.berita` | `PublicController@berita` | `index.pages.berita` | Katalog warta, event, & agenda |
| `/bkk/berita/{id_berita}` | `bkk.berita.detail` | `PublicController@beritaDetail` | `index.pages.berita-detail` | Detail baca berita |
| `/bkk/lowongan` | `bkk.lowongan` | `PublicController@lowongan` | `index.pages.lowongan` | Portal lowongan PKL & Kerja |
| `/bkk/lowongan/{id_lowongan}` | `bkk.lowongan.detail` | `PublicController@lowonganDetail` | `index.pages.lowongan-detail` | Detail persyaratan lowongan |
| `/bkk/tentang` | `bkk.tentang` | `PublicController@tentang` | `index.pages.tentang` | Profil & struktur BKK Penus |
| `/bkk/kerja-sama` | `bkk.kerjasama` | `PublicController@kerjasama` | `index.pages.kerjasama` | Formulir & informasi MoU DUDI |

---

## 4. Desain Komponen & Logika Navigasi

### A. Deteksi Active State Dinamis
Menu navigasi di `navbar.blade.php` mendeteksi URL aktif menggunakan helper Laravel `request()->is(...)`:

```blade
@php
    $navItems = [
        ['label' => 'Beranda', 'url' => url('/'), 'active' => request()->is('bkk')],
        ['label' => 'Lowongan PKL & Kerja', 'url' => url('/bkk/lowongan*'), 'active' => request()->is('bkk/lowongan*')],
        ['label' => 'Berita & Agenda', 'url' => url('/bkk/berita*'), 'active' => request()->is('bkk/berita*')],
        ['label' => 'Kerja Sama Mitra', 'url' => url('/bkk/kerja-sama*'), 'active' => request()->is('bkk/kerja-sama*')],
        ['label' => 'Tentang BKK', 'url' => url('/bkk/tentang*'), 'active' => request()->is('bkk/tentang*')],
    ];
@endphp
```

- **Active Class**: `bg-primary-container text-on-primary-container font-semibold rounded-full px-4 py-2`
- **Inactive Class**: `px-3.5 py-2 font-label-md text-label-md text-on-surface-variant hover:bg-surface-container hover:text-on-surface rounded-full transition-colors`

### B. Mobile Navigation Drawer
- Menyediakan tombol ikon *menu* (hamburger) di perangkat mobile / tablet (`xl:hidden`).
- Menampilkan drawer/overlay responsif yang memuat seluruh menu navigasi dan tombol CTA login siswa.
- Menggunakan JavaScript vanilla yang ringan tanpa ketergantungan library luar.

---

## 5. Rencana Tahapan Eksekusi (Implementation Steps)

1. **Tahap 1: Pembuatan Master Layout**
   - Buat file `resources/views/index/master.blade.php`.
   - Pindahkan blok `<head>` (Google Fonts, Material Symbols, script Tailwind CDN + konfigurasi warna/tipografi tema), struktur `<main>`, serta `@yield('content')`.
2. **Tahap 2: Pembuatan Partials (Header, Navbar, Footer)**
   - Buat `resources/views/index/partials/navbar.blade.php` (Desktop + Mobile drawer dengan loop navigasi dan active link).
   - Buat `resources/views/index/partials/header.blade.php` (Brand logo, tombol hamburger, include navbar, notifikasi & CTA).
   - Buat `resources/views/index/partials/footer.blade.php` (Footer lengkap dengan link dinamis ke masing-masing rute).
3. **Tahap 3: Pembuatan Halaman Baru `tentang.blade.php`**
   - Buat `resources/views/index/pages/tentang.blade.php` dengan visual dan konten seputar profil BKK, visi misi, dan tim kerja.
4. **Tahap 4: Refactoring Halaman-Halaman di `resources/views/index/pages/*`**
   - Refactor `index.blade.php`
   - Refactor `berita.blade.php`
   - Refactor `berita-detail.blade.php`
   - Refactor `lowongan.blade.php`
   - Refactor `lowongan-detail.blade.php`
   - Refactor `kerjasama.blade.php`
   - Tiap file dihapus `<head>`, `<header>`, `<footer>` nya, cukup menggunakan `@extends('index.master')` dan `@section('content') ... @endsection`.
5. **Tahap 5: Pembaruan Routing & Controller**
   - Perbarui `app/Http/Controllers/PublicController.php` dengan method `index`, `berita`, `beritaDetail`, `lowongan`, `lowonganDetail`, `tentang`, `kerjasama` yang merender view di `index.pages.*`.
   - Perbarui `routes/web.php` untuk mendaftarkan seluruh endpoint publik tersebut.
6. **Tahap 6: Verifikasi & Uji Coba**
   - Pastikan sintaks PHP/Blade valid (jalankan `php artisan view:cache` atau uji rendering).
   - Uji respon HTTP endpoint via artisan atau curl/test.
