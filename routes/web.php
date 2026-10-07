<?php

use App\Http\Controllers\Admin\AdminBeritaController;
use App\Http\Controllers\Admin\AdminLowonganController;
use App\Http\Controllers\Admin\AdminMitraController;
use App\Http\Controllers\Admin\AdminPklController;
use App\Http\Controllers\Admin\AdminTracerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Me\MeController;
use App\Http\Controllers\Mitra\MitraAuthController;
use App\Http\Controllers\Mitra\MitraController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\StaticAssetController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - BKK System
|--------------------------------------------------------------------------
|
| Seluruh endpoint WAJIB memiliki prefix /bkk/.
| - /bkk/*           : Publik (Tanpa middleware)
| - /bkk/admin/*     : Terproteksi Sekolah (Middleware verify.auth + RBAC: ADMIN, KEPALA_SEKOLAH, TU, DEVELOPER)
| - /bkk/me/*        : Terproteksi Siswa & Alumni (Middleware verify.auth: SISWA, ALUMNI)
| - /bkk/dashboard/* : Khusus Mitra Industri / IDUKA (Bypass VerifyMiddleware / Auth NPWP & Password)
|
*/

// Redirect root ke /bkk
Route::redirect('/', '/bkk');

Route::prefix('bkk')->group(function () {
    // 0. Static Asset Handlers (Kompatibilitas Reverse Proxy Path-Based)
    Route::get('/images/{path}', [StaticAssetController::class, 'serveImage'])->where('path', '.*')->name('bkk.static.images');
    Route::get('/uploads/{path}', [StaticAssetController::class, 'serveUpload'])->where('path', '.*')->name('bkk.static.uploads');
    Route::get('/storage/{path}', [StaticAssetController::class, 'serveStorage'])->where('path', '.*')->name('bkk.static.storage');
    Route::get('/build/{path}', [StaticAssetController::class, 'serveBuild'])->where('path', '.*')->name('bkk.static.build');

    // 1. Router Group Publik: /bkk/*
    Route::get('/', [PublicController::class, 'index'])->name('bkk.index');
    Route::get('/info', [PublicController::class, 'info'])->name('bkk.info');
    Route::get('/berita', [PublicController::class, 'berita'])->name('bkk.berita');
    Route::get('/berita/{id_berita}', [PublicController::class, 'beritaDetail'])->name('bkk.berita.detail');
    Route::get('/lowongan', [PublicController::class, 'lowongan'])->name('bkk.lowongan');
    Route::get('/lowongan/{id_lowongan}', [PublicController::class, 'lowonganDetail'])->name('bkk.lowongan.detail');
    Route::get('/tentang', [PublicController::class, 'tentang'])->name('bkk.tentang');
    Route::get('/kerja-sama', [PublicController::class, 'kerjasama'])->name('bkk.kerjasama');
    Route::post('/kerja-sama', [PublicController::class, 'storeKerjasama'])->middleware('throttle:5,1')->name('bkk.kerjasama.store');

    // 2. Router Group Terproteksi Admin Sekolah: /bkk/admin/*
    Route::middleware('verify.auth:ADMIN,KEPALA_SEKOLAH,TU,DEVELOPER')->prefix('admin')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('bkk.admin.index');
        Route::get('/profile', [DashboardController::class, 'profile'])->name('bkk.admin.profile');

        // Modul Berita Admin
        Route::get('/berita', [AdminBeritaController::class, 'index'])->name('bkk.admin.berita.index');
        Route::get('/berita/new', [AdminBeritaController::class, 'create'])->name('bkk.admin.berita.create');
        Route::post('/berita', [AdminBeritaController::class, 'store'])->name('bkk.admin.berita.store');
        Route::get('/berita/{id_berita}', [AdminBeritaController::class, 'edit'])->name('bkk.admin.berita.edit');
        Route::put('/berita/{id_berita}', [AdminBeritaController::class, 'update'])->name('bkk.admin.berita.update');
        Route::delete('/berita/{id_berita}', [AdminBeritaController::class, 'destroy'])->name('bkk.admin.berita.destroy');

        // Modul Lowongan Admin (Moderasi & Detail)
        Route::get('/lowongan', [AdminLowonganController::class, 'index'])->name('bkk.admin.lowongan.index');
        Route::get('/lowongan/{id}', [AdminLowonganController::class, 'edit'])->name('bkk.admin.lowongan.edit');
        Route::put('/lowongan/{id}', [AdminLowonganController::class, 'update'])->name('bkk.admin.lowongan.update');
        Route::delete('/lowongan/{id}', [AdminLowonganController::class, 'destroy'])->name('bkk.admin.lowongan.destroy');
        Route::post('/lowongan/{id}/status', [AdminLowonganController::class, 'updateStatus'])->name('bkk.admin.lowongan.updateStatus');
        Route::get('/lowongan/{id}/siswa', [AdminLowonganController::class, 'siswa'])->name('bkk.admin.lowongan.siswa');

        // Modul Mitra IDUKA Admin
        Route::get('/mitra', [AdminMitraController::class, 'index'])->name('bkk.admin.mitra.index');
        Route::get('/mitra/new', [AdminMitraController::class, 'create'])->name('bkk.admin.mitra.create');
        Route::post('/mitra', [AdminMitraController::class, 'store'])->name('bkk.admin.mitra.store');
        Route::get('/mitra/permohonan', [AdminMitraController::class, 'permohonanIndex'])->name('bkk.admin.mitra.permohonan.index');
        Route::post('/mitra/permohonan/{id}', [AdminMitraController::class, 'permohonanUpdateStatus'])->name('bkk.admin.mitra.permohonan.updateStatus');
        Route::get('/mitra/{id}', [AdminMitraController::class, 'edit'])->name('bkk.admin.mitra.edit');
        Route::put('/mitra/{id}', [AdminMitraController::class, 'update'])->name('bkk.admin.mitra.update');
        Route::delete('/mitra/{id}', [AdminMitraController::class, 'destroy'])->name('bkk.admin.mitra.destroy');
        Route::post('/mitra/{id}/verify', [AdminMitraController::class, 'toggleVerify'])->name('bkk.admin.mitra.verify');

        // Modul Monitoring PKL & Review Laporan
        Route::get('/pkl/monitoring', [AdminPklController::class, 'monitoring'])->name('bkk.admin.pkl.monitoring');
        Route::get('/pkl/laporan-review', [AdminPklController::class, 'laporanReview'])->name('bkk.admin.pkl.review');
        Route::post('/pkl/jurnal/{id}/validate', [AdminPklController::class, 'validateJurnal'])->name('bkk.admin.pkl.validateJurnal');
        Route::post('/pkl/laporan/{id}/validate', [AdminPklController::class, 'validateLaporan'])->name('bkk.admin.pkl.validateLaporan');

        // Modul Tracer Study
        Route::get('/tracer-study', [AdminTracerController::class, 'index'])->name('bkk.admin.tracer.index');
        Route::post('/tracer-study/kuesioner', [AdminTracerController::class, 'kuesionerStore'])->name('bkk.admin.tracer.kuesioner.store');
    });

    // 3. Router Group Terproteksi Khusus Siswa & Alumni: /bkk/me/*
    Route::middleware('verify.auth:SISWA,ALUMNI')->prefix('me')->name('bkk.me.')->group(function () {
        Route::get('/', [MeController::class, 'dashboard'])->name('index');
        Route::get('/lamaran', [MeController::class, 'lamaran'])->name('lamaran');
        Route::get('/cv', [MeController::class, 'cv'])->name('cv');
        Route::post('/cv', [MeController::class, 'cvUpdate'])->name('cv.update');
        Route::get('/cv/edit', [MeController::class, 'cvEdit'])->name('cv.edit');
        Route::get('/jurnal', [MeController::class, 'jurnal'])->name('jurnal');
        Route::post('/jurnal', [MeController::class, 'storeJurnal'])->name('jurnal.store');
        Route::get('/laporan', [MeController::class, 'laporan'])->name('laporan');
        Route::post('/laporan', [MeController::class, 'storeLaporan'])->name('laporan.store');
        Route::post('/daftar/{id_lowongan}', [MeController::class, 'storeLamaran'])->name('daftar');
    });

    // 4. Autentikasi Publik Mitra: /bkk/dashboard/login
    Route::get('/dashboard/login', [MitraAuthController::class, 'showLogin'])->name('bkk.mitra.login');
    Route::post('/dashboard/login', [MitraAuthController::class, 'login'])->middleware('throttle:5,1')->name('bkk.mitra.login.post');

    // 5. Router Group Khusus Mitra (IDUKA): /bkk/dashboard/* (Terproteksi Middleware mitra.auth)
    Route::middleware('mitra.auth')->prefix('dashboard')->name('bkk.mitra.')->group(function () {
        Route::post('/logout', [MitraAuthController::class, 'logout'])->name('logout');
        Route::get('/', [MitraController::class, 'dashboard'])->name('dashboard');

        // Pengaturan Akun & Profil Mitra
        Route::get('/pengaturan', [MitraAuthController::class, 'pengaturan'])->name('pengaturan');
        Route::post('/pengaturan/password', [MitraAuthController::class, 'updatePassword'])->name('pengaturan.password');
        Route::post('/pengaturan/logo', [MitraAuthController::class, 'updateLogo'])->name('pengaturan.logo');
        Route::post('/pengaturan/profil', [MitraAuthController::class, 'updateProfil'])->name('pengaturan.profil');

        // Modul Lowongan Mitra
        Route::get('/lowongan', [MitraController::class, 'lowonganIndex'])->name('lowongan.index');
        Route::get('/lowongan/new', [MitraController::class, 'lowonganCreate'])->name('lowongan.create');
        Route::post('/lowongan', [MitraController::class, 'lowonganStore'])->name('lowongan.store');
        Route::get('/lowongan/{id_lowongan}', [MitraController::class, 'lowonganEdit'])->name('lowongan.edit');
        Route::put('/lowongan/{id_lowongan}', [MitraController::class, 'lowonganUpdate'])->name('lowongan.update');
        Route::delete('/lowongan/{id_lowongan}', [MitraController::class, 'lowonganDestroy'])->name('lowongan.destroy');

        // Modul Review CV & Pelamar
        Route::get('/lowongan/{id_lowongan}/pelamar', [MitraController::class, 'pelamarIndex'])->name('pelamar.index');
        Route::get('/lowongan/{id_lowongan}/pelamar/{id_pelamar}', [MitraController::class, 'pelamarShow'])->name('pelamar.show');
        Route::post('/lowongan/{id_lowongan}/pelamar/{id_pelamar}/status', [MitraController::class, 'pelamarUpdateStatus'])->name('pelamar.updateStatus');
    });
});
