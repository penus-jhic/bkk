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
use App\View\Composers\AdminAlertsComposer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        // S2: Konsolidasikan metrik berita menjadi 1 query
        $beritaStats = Berita::selectRaw("
            COUNT(*) AS total,
            COUNT(CASE WHEN status = 'PUBLISHED' THEN 1 END) AS published,
            COUNT(CASE WHEN status = 'DRAFT' THEN 1 END) AS draft,
            COALESCE(SUM(views_count), 0) AS views
        ")->first();

        // S2: Konsolidasikan metrik lowongan menjadi 1 query
        $lowonganStats = Lowongan::selectRaw("
            COUNT(*) AS total,
            COUNT(CASE WHEN status = 'Aktif' THEN 1 END) AS aktif,
            COUNT(CASE WHEN status = 'Aktif' AND tipe = 'PKL' THEN 1 END) AS pkl,
            COUNT(CASE WHEN status = 'Aktif' AND tipe = 'Kerja' THEN 1 END) AS kerja
        ")->first();

        // S2: Konsolidasikan metrik mitra menjadi 1 query
        $mitraStats = Mitra::selectRaw("
            COUNT(*) AS total,
            COUNT(CASE WHEN is_verified IS TRUE THEN 1 END) AS verified
        ")->first();

        // S2: Konsolidasikan penempatan PKL berjalan & selesai menjadi 1 query
        $pklStats = PenempatanPkl::selectRaw("
            COUNT(CASE WHEN status = 'BERJALAN' THEN 1 END) AS berjalan,
            COUNT(CASE WHEN status = 'SELESAI' THEN 1 END) AS selesai
        ")->first();

        $kategoriList = KategoriBerita::withCount('beritas')->get();

        $permohonanPendingCount = PermohonanKerjasama::where('status', 'MENUNGGU_REVIEW')->count();
        $jurnalMenungguCount = PklJurnalHarian::where('status', 'Menunggu')->count();
        $laporanDitinjauCount = PklLaporanAkhir::where('status', 'Ditinjau')->count();

        // Corong lamaran: urutan sama dengan enum status di tabel lamaran (1 query)
        $lamaranCounts = Lamaran::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $lamaranFunnel = collect(self::LAMARAN_STATUSES)
            ->map(fn (string $status) => ['status' => $status, 'total' => (int) ($lamaranCounts[$status] ?? 0)])
            ->all();

        // Keterserapan alumni (BMW: Bekerja, Melanjutkan, Wirausaha) dari tracer study (1 query)
        $tracerCounts = TracerRespon::query()
            ->selectRaw('status_keterserapan, count(*) as total')
            ->groupBy('status_keterserapan')
            ->pluck('total', 'status_keterserapan');
        $tracerBreakdown = collect(self::TRACER_STATUSES)
            ->map(fn (string $status) => ['status' => $status, 'total' => (int) ($tracerCounts[$status] ?? 0)])
            ->all();

        $stats = [
            'total_berita' => (int) ($beritaStats->total ?? 0),
            'total_published' => (int) ($beritaStats->published ?? 0),
            'total_draft' => (int) ($beritaStats->draft ?? 0),
            'total_views' => (int) ($beritaStats->views ?? 0),
            'total_kategori' => $kategoriList->count(),
            'total_lowongan' => (int) ($lowonganStats->total ?? 0),
            'total_lowongan_aktif' => (int) ($lowonganStats->aktif ?? 0),
            'total_lowongan_pkl' => (int) ($lowonganStats->pkl ?? 0),
            'total_lowongan_kerja' => (int) ($lowonganStats->kerja ?? 0),
            'total_mitra' => (int) ($mitraStats->total ?? 0),
            'total_mitra_verified' => (int) ($mitraStats->verified ?? 0),
            'total_permohonan_pending' => $permohonanPendingCount,
            'total_siswa_pkl' => (int) ($pklStats->berjalan ?? 0),
            'total_pkl_selesai' => (int) ($pklStats->selesai ?? 0),
            'total_jurnal_menunggu' => $jurnalMenungguCount,
            'total_laporan_ditinjau' => $laporanDitinjauCount,
            'total_pelamar' => (int) $lamaranCounts->sum(),
            'total_tracer_respon' => (int) $tracerCounts->sum(),
            'tracer_terserap' => (int) $tracerCounts->only(['Bekerja', 'Melanjutkan Pendidikan', 'Wirausaha'])->sum(),
        ];

        // S3: Bagikan hitungan metrik yang sudah dihitung ke AdminAlertsComposer agar tidak query ulang
        app(AdminAlertsComposer::class)->provideCounts([
            'permohonan' => $permohonanPendingCount,
            'jurnal' => $jurnalMenungguCount,
            'laporan' => $laporanDitinjauCount,
            'lamaran' => (int) ($lamaranCounts['Terkirim'] ?? 0),
        ]);

        $recentBeritas = Berita::with('kategori')->latest('created_at')->take(4)->get();
        $recentLowongans = Lowongan::with('mitra')->withCount('lamarans')->latest('created_at')->take(5)->get();
        $recentLamarans = Lamaran::with(['lowongan.mitra', 'siswa'])->latest('tanggal_melamar')->latest('id')->take(5)->get();
        $recentMitras = Mitra::withCount('lowongans')->latest('created_at')->take(4)->get();
        $pendingPermohonans = PermohonanKerjasama::where('status', 'MENUNGGU_REVIEW')->latest('created_at')->take(3)->get();
        $recentPkl = PenempatanPkl::with(['siswa', 'mitra'])->where('status', 'BERJALAN')->latest('tanggal_mulai')->take(4)->get();
        $recentTracer = TracerRespon::with('profilSiswa')->latest('tanggal_pengisian')->take(4)->get();

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
