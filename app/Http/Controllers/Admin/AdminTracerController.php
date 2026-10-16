<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TracerKuesioner;
use App\Models\TracerRespon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminTracerController extends Controller
{
    /**
     * Dashboard dan Rekapitulasi Tracer Study Alumni (/bkk/admin/tracer-study)
     */
    public function index(Request $request): View|JsonResponse
    {
        $kuesioners = TracerKuesioner::withCount(['pertanyaans', 'respons'])->latest()->get();

        // S2: Konsolidasikan seluruh metrik tracer study (total, BMW, relevansi, avg tunggu) menjadi 1 query
        $statsRow = TracerRespon::selectRaw("
            COUNT(*) AS total_respon,
            COUNT(CASE WHEN status_keterserapan = 'Bekerja' THEN 1 END) AS bmw_bekerja,
            COUNT(CASE WHEN status_keterserapan = 'Melanjutkan Pendidikan' THEN 1 END) AS bmw_melanjutkan,
            COUNT(CASE WHEN status_keterserapan = 'Wirausaha' THEN 1 END) AS bmw_wirausaha,
            COUNT(CASE WHEN status_keterserapan = 'Mencari Kerja' THEN 1 END) AS bmw_mencari_kerja,
            COUNT(CASE WHEN keselarasan_jurusan = 'Sangat Selaras' THEN 1 END) AS rel_sangat_selaras,
            COUNT(CASE WHEN keselarasan_jurusan = 'Selaras' THEN 1 END) AS rel_selaras,
            COUNT(CASE WHEN keselarasan_jurusan = 'Kurang Selaras' THEN 1 END) AS rel_kurang_selaras,
            COUNT(CASE WHEN keselarasan_jurusan = 'Tidak Selaras' THEN 1 END) AS rel_tidak_selaras,
            AVG(waktu_tunggu_bulan) AS avg_waktu_tunggu
        ")->first();

        $stats = [
            'total_respon' => (int) ($statsRow->total_respon ?? 0),
            'bmw' => [
                'bekerja' => (int) ($statsRow->bmw_bekerja ?? 0),
                'melanjutkan' => (int) ($statsRow->bmw_melanjutkan ?? 0),
                'wirausaha' => (int) ($statsRow->bmw_wirausaha ?? 0),
                'mencari_kerja' => (int) ($statsRow->bmw_mencari_kerja ?? 0),
            ],
            'keselarasan' => [
                'sangat_selaras' => (int) ($statsRow->rel_sangat_selaras ?? 0),
                'selaras' => (int) ($statsRow->rel_selaras ?? 0),
                'kurang_selaras' => (int) ($statsRow->rel_kurang_selaras ?? 0),
                'tidak_selaras' => (int) ($statsRow->rel_tidak_selaras ?? 0),
            ],
            'rata_rata_waktu_tunggu_bulan' => round((float) ($statsRow->avg_waktu_tunggu ?? 0), 1),
        ];

        $recentResponses = TracerRespon::with(['profilSiswa', 'kuesioner'])
            ->latest('tanggal_pengisian')
            ->paginate(10);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'kuesioner' => $kuesioners,
                'stats' => $stats,
                'recent_responses' => $recentResponses,
            ]);
        }

        $authUser = $request->auth_user ?? $request->input('auth_user') ?? [];

        return view('admin.pages.tracer.index', compact('authUser', 'kuesioners', 'stats', 'recentResponses'));
    }

    /**
     * Buat Kuesioner Tracer Study Baru
     */
    public function kuesionerStore(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'tahun_sasaran_lulusan' => ['required', 'string', 'max:10'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after_or_equal:tanggal_mulai'],
            'deskripsi' => ['nullable', 'string'],
        ]);

        $kuesioner = TracerKuesioner::create([
            'judul' => $validated['judul'],
            'slug' => Str::slug($validated['judul']) . '-' . Str::random(5),
            'tahun_sasaran_lulusan' => $validated['tahun_sasaran_lulusan'],
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'deskripsi' => $validated['deskripsi'] ?? null,
            'is_aktif' => true,
            'created_by' => $request->auth_user['id'] ?? 'adm-bkk-001',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Instrumen kuesioner tracer study berhasil dibuat!',
                'data' => $kuesioner,
            ], 201);
        }

        return back()->with('success', "Instrumen kuesioner tracer study '{$kuesioner->judul}' berhasil diterbitkan.");
    }
}
