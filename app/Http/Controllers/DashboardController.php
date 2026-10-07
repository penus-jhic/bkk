<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\KategoriBerita;
use App\Models\Lamaran;
use App\Models\Lowongan;
use App\Models\Mitra;
use App\Models\PenempatanPkl;
use App\Models\TracerRespon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Dashboard utama admin BKK.
     */
    public function index(Request $request): View|JsonResponse
    {
        $authUser = $request->auth_user ?? $request->input('auth_user');

        $stats = [
            'total_berita' => Schema::hasTable('berita') ? Berita::count() : 0,
            'total_published' => Schema::hasTable('berita') ? Berita::where('status', 'PUBLISHED')->count() : 0,
            'total_draft' => Schema::hasTable('berita') ? Berita::where('status', 'DRAFT')->count() : 0,
            'total_views' => Schema::hasTable('berita') ? (int) Berita::sum('views_count') : 0,
            'total_kategori' => Schema::hasTable('kategori_berita') ? KategoriBerita::count() : 0,
            'total_lowongan' => Schema::hasTable('lowongan') ? Lowongan::count() : 0,
            'total_lowongan_aktif' => Schema::hasTable('lowongan') ? Lowongan::where('status', 'Aktif')->count() : 0,
            'total_mitra' => Schema::hasTable('mitra') ? Mitra::count() : 0,
            'total_mitra_verified' => Schema::hasTable('mitra') ? Mitra::where('is_verified', true)->count() : 0,
            'total_siswa_pkl' => Schema::hasTable('penempatan_pkl') ? PenempatanPkl::where('status', 'BERJALAN')->count() : 0,
            'total_pelamar' => Schema::hasTable('lamaran') ? Lamaran::count() : 0,
            'total_tracer_respon' => Schema::hasTable('tracer_respon') ? TracerRespon::count() : 0,
        ];

        $recentBeritas = Schema::hasTable('berita')
            ? Berita::with('kategori')->latest('created_at')->take(5)->get()
            : collect();

        $kategoriList = Schema::hasTable('kategori_berita')
            ? KategoriBerita::withCount('beritas')->get()
            : collect();

        $recentLowongans = Schema::hasTable('lowongan')
            ? Lowongan::with('mitra')->latest('created_at')->take(4)->get()
            : collect();

        $recentMitras = Schema::hasTable('mitra')
            ? Mitra::latest('created_at')->take(4)->get()
            : collect();

        $recentPkl = Schema::hasTable('penempatan_pkl')
            ? PenempatanPkl::with(['siswa', 'mitra'])->latest('created_at')->take(4)->get()
            : collect();

        $recentTracer = Schema::hasTable('tracer_respon')
            ? TracerRespon::with('profilSiswa')->latest('created_at')->take(4)->get()
            : collect();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $authUser,
                    'stats' => $stats,
                    'kategori' => $kategoriList,
                    'recent_lowongans' => $recentLowongans,
                    'recent_mitras' => $recentMitras,
                    'recent_pkl' => $recentPkl,
                    'recent_tracer' => $recentTracer,
                ],
            ]);
        }

        return view('admin.pages.dashboard', compact(
            'authUser',
            'stats',
            'recentBeritas',
            'kategoriList',
            'recentLowongans',
            'recentMitras',
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
