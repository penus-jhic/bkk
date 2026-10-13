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
use App\Models\ProfilSiswa;
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

        $has = function (string $table): bool {
            try {
                return Schema::hasTable($table);
            } catch (\Throwable) {
                return false;
            }
        };

        $totalSiswaAktif = $has('profil_siswa') ? ProfilSiswa::where('status_kelulusan', 'SISWA_AKTIF')->count() : 0;
        $totalSiswaPkl = $has('penempatan_pkl') ? PenempatanPkl::where('status', 'BERJALAN')->count() : 0;
        $totalPklSelesai = $has('penempatan_pkl') ? PenempatanPkl::where('status', 'SELESAI')->count() : 0;
        $totalSiswaBelumPkl = ($has('profil_siswa') && $has('penempatan_pkl'))
            ? ProfilSiswa::where('status_kelulusan', 'SISWA_AKTIF')
                ->whereDoesntHave('penempatanPkl', fn ($q) => $q->whereIn('status', ['BERJALAN', 'SELESAI']))
                ->count()
            : max(0, $totalSiswaAktif - $totalSiswaPkl - $totalPklSelesai);

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
            'total_siswa_aktif' => $totalSiswaAktif,
            'total_siswa_belum_pkl' => $totalSiswaBelumPkl,
            'total_siswa_pkl' => $totalSiswaPkl,
            'total_pkl_selesai' => $totalPklSelesai,
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

        $totalPklDistributionBase = max(1, $totalSiswaBelumPkl + $totalSiswaPkl + $totalPklSelesai);
        $pklDistribution = [
            [
                'status' => 'Belum PKL',
                'total' => $totalSiswaBelumPkl,
                'percent' => (int) round(($totalSiswaBelumPkl / $totalPklDistributionBase) * 100),
                'color' => 'bg-amber-500',
                'badge' => 'bg-amber-50 text-amber-800 border border-amber-200',
            ],
            [
                'status' => 'Sedang PKL',
                'total' => $totalSiswaPkl,
                'percent' => (int) round(($totalSiswaPkl / $totalPklDistributionBase) * 100),
                'color' => 'bg-blue-600',
                'badge' => 'bg-blue-50 text-blue-800 border border-blue-200',
            ],
            [
                'status' => 'Selesai PKL',
                'total' => $totalPklSelesai,
                'percent' => (int) round(($totalPklSelesai / $totalPklDistributionBase) * 100),
                'color' => 'bg-emerald-600',
                'badge' => 'bg-emerald-50 text-emerald-800 border border-emerald-200',
            ],
        ];

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
                    'pkl_distribution' => $pklDistribution,
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
            'pklDistribution',
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
