# Rencana Implementasi: Pengkondisian Status PKL Siswa pada Dashboard Siswa & Admin

## Deskripsi Tujuan
Menambahkan pengkondisian komprehensif terkait status pelaksanaan Praktik Kerja Lapangan (PKL) siswa pada **Dashboard Siswa (`/bkk/me`)** dan **Dashboard Admin (`/bkk/admin`)**. 

Berdasarkan kesepakatan pada sesi tanya-jawab:
1. **3 Kategori Status PKL Siswa**:
   - **Belum PKL**: Siswa aktif yang belum terdaftar dalam penempatan PKL atau belum memiliki penempatan aktif/selesai.
   - **Sedang PKL**: Siswa aktif yang sedang menjalani penempatan PKL (`status = 'BERJALAN'`).
   - **Sudah PKL**: Siswa yang telah menyelesaikan penempatan PKL (`status = 'SELESAI'`).
2. **Dashboard Siswa (`/bkk/me`)**:
   - Menampilkan Card/Widget Khusus Status PKL di area utama dashboard dengan tampilan kontekstual:
     - **Sedang PKL**: Menampilkan instansi mitra, divisi/unit kerja, pembimbing industri, progres jam PKL (target vs tercapai), dan tombol aksi cepat menuju Jurnal & Laporan.
     - **Sudah PKL**: Menampilkan instansi mitra tempat PKL selesai, rekap jam tercapai, nilai akhir (industri & sekolah), serta tombol akses riwayat dan rekomendasi lowongan kerja.
     - **Belum PKL**: Menampilkan alert/kartu panduan belum memiliki penempatan, langkah-langkah persiapan PKL, dan CTA eksplorasi lowongan bertipe PKL serta kelengkapan CV.
   - Memperbarui badge status pada banner sambutan (Greeting Banner) dan widget kartu status profil di Sidebar.
3. **Dashboard Admin (`/bkk/admin`)**:
   - Menghitung metrik `total_siswa_belum_pkl`, `total_siswa_pkl` (sedang berjalan), dan `total_pkl_selesai` dari data siswa aktif.
   - Menampilkan metrik statistik 'Siswa Belum PKL' pada KPI PKL admin.
   - Menambahkan ringkasan distribusi status PKL siswa aktif (Belum PKL vs Sedang PKL vs Selesai PKL) dalam bentuk breakdown visual interaktif pada section monitoring PKL.

---

## User Review Required
> [!IMPORTANT]
> - Data status PKL ditentukan dari tabel `penempatan_pkl` dengan foreign key `siswa_id` yang berelasi dengan `profil_siswa.user_id`.
> - Untuk siswa yang memiliki beberapa riwayat penempatan, prioritas pengecekan adalah status `BERJALAN` (Sedang PKL) terlebih dahulu, kemudian status `SELESAI` (Sudah PKL), dan jika tidak ada keduanya maka dikategorikan `BELUM_PKL`.
> - Pada environment development dengan mock/dev auth, sistem menyediakan fallback data yang aman agar tampilan tetap berfungsi baik dengan database kosong maupun database riil.

---

## Proposed Changes

### 1. Backend Controller & Data Layer (Siswa)

#### [MODIFY] `app/Http/Controllers/Me/MeController.php`
- Menambahkan logika helper `resolvePklStatus(string $userId, string $role)`:
  - Mengambil data dari `PenempatanPkl::with('mitra')` untuk siswa bersangkutan.
  - Menentukan status code (`BELUM_PKL`, `SEDANG_PKL`, `SUDAH_PKL`).
  - Menyusun metadata (nama mitra, unit kerja, pembimbing, tanggal, total jam, target jam, progress persen, nilai akhir).
- Menyematkan `$pklStatus` ke dalam data view dan respon JSON pada `MeController::dashboard()`.
- Memperbarui `$profile['status']` agar selaras dengan status PKL riil siswa jika role adalah `SISWA`.

```php
// Contoh penentuan status di MeController:
$activePkl = PenempatanPkl::with('mitra')->where('siswa_id', $userId)->where('status', 'BERJALAN')->latest()->first();
$finishedPkl = PenempatanPkl::with('mitra')->where('siswa_id', $userId)->where('status', 'SELESAI')->latest()->first();

if ($activePkl) {
    $pklStatus = [
        'code' => 'SEDANG_PKL',
        'label' => 'Sedang PKL',
        'badge' => 'bg-blue-100 text-blue-800 border-blue-200',
        'data' => $activePkl,
        // target & progress jam...
    ];
} elseif ($finishedPkl) {
    $pklStatus = [
        'code' => 'SUDAH_PKL',
        'label' => 'Sudah Selesai PKL',
        'badge' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
        'data' => $finishedPkl,
        // nilai industri & sekolah...
    ];
} else {
    $pklStatus = [
        'code' => 'BELUM_PKL',
        'label' => 'Belum PKL',
        'badge' => 'bg-amber-100 text-amber-800 border-amber-200',
        'data' => null,
    ];
}
```

---

### 2. Frontend Tampilan Siswa

#### [MODIFY] `resources/views/me/pages/dashboard.blade.php`
- Memperbarui Greeting Banner:
  - Mengubah pill status di atas nama siswa dari hardcoded `'Siswa PKL'` menjadi dinamis sesuai `$pklStatus['label']` dan badge styling.
- Menambahkan **Widget Khusus Status PKL** di bawah Greeting Banner / di atas Rekomendasi:
  - **Sedang PKL**: Card berdesain elegan dengan progress bar jam kerja, detail mitra penempatan, guru pembimbing & pembimbing industri, tombol "Tulis Jurnal Harian" & "Upload Laporan".
  - **Sudah PKL**: Card konfirmasi kelulusan PKL, nilai evaluasi industri & sekolah, ucapan selamat telah menyelesaikan masa PKL, tombol "Lihat Dokumen & Jurnal" & "Cari Lowongan Kerja".
  - **Belum PKL**: Card informatif dengan nuansa amber/navy, instruksi persiapan penempatan PKL, panduan koordinasi sekolah, serta tombol "Eksplorasi Lowongan PKL" & "Lengkapi CV".

#### [MODIFY] `resources/views/me/partials/sidebar.blade.php`
- Memperbarui kartu status di bagian bawah sidebar (`Pinned Bottom Status Card`):
  - Mengganti teks status hardcoded menjadi label dinamis dari `$profile['status']` / `$pklStatus['label']`.
  - Jika siswa Sedang PKL, tampilkan nama mitra secara ringkas.

---

### 3. Backend Controller & Data Layer (Admin)

#### [MODIFY] `app/Http/Controllers/DashboardController.php`
- Menghitung statistik baru pada method `index()`:
  - `total_siswa_aktif`: Jumlah siswa aktif (`profil_siswa` dengan `status_kelulusan = 'SISWA_AKTIF'`).
  - `total_siswa_belum_pkl`: Siswa aktif yang belum memiliki record penempatan PKL berstatus `BERJALAN` atau `SELESAI`.
  - `total_siswa_pkl`: Jumlah siswa dengan penempatan berstatus `BERJALAN`.
  - `total_pkl_selesai`: Jumlah siswa dengan penempatan berstatus `SELESAI`.
  - `pkl_distribution`: Array berisi data persentase dan total masing-masing status untuk chart/progress distribution bar.
- Memasukkan metrik baru ke dalam variabel `$stats` dan payload JSON.

---

### 4. Frontend Tampilan Admin

#### [MODIFY] `resources/views/admin/pages/dashboard.blade.php`
- Memperbarui KPI card "Siswa PKL Berjalan":
  - Memperkaya label catatan dengan informasi jumlah siswa belum PKL (`{{ $s('total_siswa_belum_pkl') }} belum penempatan · {{ $s('total_pkl_selesai') }} selesai`).
- Menambahkan **Distribution Breakdown Bar** pada Section Monitoring PKL:
  - Menampilkan ringkasan visual 3 status: Belum PKL (Kuning/Amber), Sedang PKL (Biru/Navy), dan Selesai PKL (Hijau/Emerald) lengkap dengan jumlah siswa dan persentasenya.
  - Memberikan indikator peringatan jika terdapat siswa aktif yang belum memiliki penempatan PKL agar admin BKK dapat segera menindaklanjuti.

---

## Verification Plan

### Automated / Syntax Check
1. Linting & syntax checking via PHP CLI:
   ```powershell
   php -l app/Http/Controllers/Me/MeController.php
   php -l app/Http/Controllers/DashboardController.php
   ```
2. Validasi compile blade template:
   ```powershell
   php artisan view:cache
   ```

### Manual Verification
1. **Verifikasi Dashboard Siswa (`/bkk/me`)**:
   - Buka `/bkk/me` saat siswa berstatus **Belum PKL**:
     - Pastikan badge di greeting banner menampilkan "Belum PKL".
     - Pastikan widget panduan penempatan PKL muncul dengan tombol aksi ke lowongan PKL dan edit CV.
   - Buka `/bkk/me` saat siswa berstatus **Sedang PKL**:
     - Pastikan widget progress jam dan detail mitra industri muncul lengkap dengan tombol menuju jurnal harian & laporan akhir.
   - Buka `/bkk/me` saat siswa berstatus **Sudah PKL**:
     - Pastikan widget kelulusan PKL dan ringkasan nilai muncul.
2. **Verifikasi Dashboard Admin (`/bkk/admin`)**:
   - Buka `/bkk/admin`:
     - Periksa KPI Card PKL: menampilkan counter siswa belum PKL dan selesai PKL.
     - Periksa Section Monitoring PKL: bar distribusi 3 status (Belum PKL, Sedang PKL, Selesai PKL) ter-render dengan proporsi angka yang akurat.
