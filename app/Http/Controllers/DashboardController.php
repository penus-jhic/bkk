<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\KategoriBerita;
use App\Models\Lamaran;
use App\Models\Lowongan;
use App\Models\Mitra;
use App\Models\PenempatanPkl;
use App\Models\PermohonanKerjasama;
use App\Models\PklJurnalHarian;
use App\Models\PklLaporanAkhir;
use App\Models\TracerRespon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    private const LAMARAN_STATUSES = ['Terkirim', 'Sedang Ditinjau', 'Dipanggil Interview', 'Diterima', 'Ditolak'];

    private const TRACER_STATUSES = ['Bekerja', 'Melanjutkan Pendidikan', 'Wirausaha', 'Mencari Kerja'];

    /**
     * Dashboard utama admin BKK: ringkasan lowongan, lamaran, mitra, PKL, tracer study, dan berita.
     */
    public function index(Request $request): View|JsonResponse
    {
        $authUser = $request->auth_user ?? $request->input('auth_user');

        $has = fn (string $table) => Schema::hasTable($table);

        $stats = [
            'total_berita' => $has('berita') ? Berita::count() : 0,
            'total_published' => $has('berita') ? Berita::where('status', 'PUBLISHED')->count() : 0,
            'total_draft' => $has('berita') ? Berita::where('status', 'DRAFT')->count() : 0,
            'total_views' => $has('berita') ? (int) Berita::sum('views_count') : 0,
            'total_kategori' => $has('kategori_berita') ? KategoriBerita::count() : 0,
            'total_lowongan' => $has('lowongan') ? Lowongan::count() : 0,
            'total_lowongan_aktif' => $has('lowongan') ? Lowongan::where('status', 'Aktif')->count() : 0,
            'total_lowongan_pkl' => $has('lowongan') ? Lowongan::where('status', 'Aktif')->where('tipe', 'PKL')->count() : 0,
            'total_lowongan_kerja' => $has('lowongan') ? Lowongan::where('status', 'Aktif')->where('tipe', 'Kerja')->count() : 0,
            'total_mitra' => $has('mitra') ? Mitra::count() : 0,
            'total_mitra_verified' => $has('mitra') ? Mitra::where('is_verified', true)->count() : 0,
            'total_permohonan_pending' => $has('permohonan_kerjasama') ? PermohonanKerjasama::where('status', 'MENUNGGU_REVIEW')->count() : 0,
            'total_siswa_pkl' => $has('penempatan_pkl') ? PenempatanPkl::where('status', 'BERJALAN')->count() : 0,
            'total_pkl_selesai' => $has('penempatan_pkl') ? PenempatanPkl::where('status', 'SELESAI')->count() : 0,
            'total_jurnal_menunggu' => $has('pkl_jurnal_harian') ? PklJurnalHarian::where('status', 'Menunggu')->count() : 0,
            'total_laporan_ditinjau' => $has('pkl_laporan_akhir') ? PklLaporanAkhir::where('status', 'Ditinjau')->count() : 0,
            'total_pelamar' => $has('lamaran') ? Lamaran::count() : 0,
            'total_tracer_respon' => $has('tracer_respon') ? TracerRespon::count() : 0,
        ];

        // Corong lamaran: urutan sama dengan enum status di tabel lamaran
        $lamaranCounts = $has('lamaran')
            ? Lamaran::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')
            : collect();
        $lamaranFunnel = collect(self::LAMARAN_STATUSES)
            ->map(fn (string $status) => ['status' => $status, 'total' => (int) ($lamaranCounts[$status] ?? 0)])
            ->all();

        // Keterserapan alumni (BMW: Bekerja, Melanjutkan, Wirausaha) dari tracer study
        $tracerCounts = $has('tracer_respon')
            ? TracerRespon::query()->selectRaw('status_keterserapan, count(*) as total')->groupBy('status_keterserapan')->pluck('total', 'status_keterserapan')
            : collect();
        $tracerBreakdown = collect(self::TRACER_STATUSES)
            ->map(fn (string $status) => ['status' => $status, 'total' => (int) ($tracerCounts[$status] ?? 0)])
            ->all();
        $stats['tracer_terserap'] = (int) $tracerCounts->only(['Bekerja', 'Melanjutkan Pendidikan', 'Wirausaha'])->sum();

        $recentBeritas = $has('berita')
            ? Berita::with('kategori')->latest('created_at')->take(4)->get()
            : collect();

        $kategoriList = $has('kategori_berita')
            ? KategoriBerita::withCount('beritas')->get()
            : collect();

        $recentLowongans = $has('lowongan')
            ? Lowongan::with('mitra')->withCount('lamarans')->latest('created_at')->take(5)->get()
            : collect();

        $recentLamarans = $has('lamaran')
            ? Lamaran::with(['lowongan.mitra', 'siswa'])->latest('tanggal_melamar')->latest('id')->take(5)->get()
            : collect();

        $recentMitras = $has('mitra')
            ? Mitra::withCount('lowongans')->latest('created_at')->take(4)->get()
            : collect();

        $pendingPermohonans = $has('permohonan_kerjasama')
            ? PermohonanKerjasama::where('status', 'MENUNGGU_REVIEW')->latest('created_at')->take(3)->get()
            : collect();

        $recentPkl = $has('penempatan_pkl')
            ? PenempatanPkl::with(['siswa', 'mitra'])->where('status', 'BERJALAN')->latest('tanggal_mulai')->take(4)->get()
            : collect();

        $recentTracer = $has('tracer_respon')
            ? TracerRespon::with('profilSiswa')->latest('tanggal_pengisian')->take(4)->get()
            : collect();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $authUser,
                    'stats' => $stats,
                    'lamaran_funnel' => $lamaranFunnel,
                    'tracer_breakdown' => $tracerBreakdown,
                    'kategori' => $kategoriList,
                    'recent_lowongans' => $recentLowongans,
                    'recent_lamarans' => $recentLamarans,
                    'recent_mitras' => $recentMitras,
                    'recent_pkl' => $recentPkl,
                    'recent_tracer' => $recentTracer,
                ],
            ]);
        }

        return view('admin.pages.dashboard', compact(
            'authUser',
            'stats',
            'lamaranFunnel',
            'tracerBreakdown',
            'recentBeritas',
            'kategoriList',
            'recentLowongans',
            'recentLamarans',
            'recentMitras',
            'pendingPermohonans',
            'recentPkl',
            'recentTracer'
        ));
    }

    /**
     * Profil pengguna terautentikasi di dashboard bkk.
     */
    public function profile(Request $request): View|JsonResponse
    {
        $authUser = $request->auth_user ?? $request->input('auth_user');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $authUser,
            ]);
        }

        return view('admin.pages.profile', compact('authUser'));
    }
}
