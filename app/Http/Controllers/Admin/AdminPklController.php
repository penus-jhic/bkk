<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PenempatanPkl;
use App\Models\PklJurnalHarian;
use App\Models\PklLaporanAkhir;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPklController extends Controller
{
    /**
     * Monitoring Siswa PKL Aktif di Industri (/bkk/admin/pkl/monitoring)
     */
    public function monitoring(Request $request): View|JsonResponse
    {
        $query = PenempatanPkl::with(['siswa', 'mitra', 'lowongan']);

        $search = $request->input('q');
        if (!empty($search)) {
            $like = config('database.default') === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('pembimbing_industri_nama', $like, "%{$search}%")
                  ->orWhere('unit_kerja_divisi', $like, "%{$search}%")
                  ->orWhereHas('siswa', fn ($s) => $s->where('nis', $like, "%{$search}%")->orWhere('jurusan', $like, "%{$search}%"))
                  ->orWhereHas('mitra', fn ($m) => $m->where('nama_perusahaan', $like, "%{$search}%"));
            });
        }

        $status = $request->input('status');
        if (!empty($status) && $status !== 'Semua') {
            $query->where('status', $status);
        }

        $penempatan = $query->latest('created_at')->paginate(10, ['*'], 'penempatan_page')->withQueryString();

        // Antrean Jurnal Harian yang Menunggu Validasi
        $jurnalsPending = PklJurnalHarian::with(['penempatan.mitra', 'penempatan.siswa'])
            ->where('status', 'Menunggu')
            ->latest('tanggal')
            ->paginate(10, ['*'], 'jurnal_page');

        // Antrean Naskah Laporan Akhir yang Ditinjau
        $laporansPending = PklLaporanAkhir::with(['penempatan.mitra', 'penempatan.siswa'])
            ->where('status', 'Ditinjau')
            ->latest('updated_at')
            ->paginate(10, ['*'], 'laporan_page');

        $stats = [
            'total_penempatan' => PenempatanPkl::count(),
            'berjalan' => PenempatanPkl::where('status', 'BERJALAN')->count(),
            'selesai' => PenempatanPkl::where('status', 'SELESAI')->count(),
            'jurnal_menunggu' => PklJurnalHarian::where('status', 'Menunggu')->count(),
            'laporan_ditinjau' => PklLaporanAkhir::where('status', 'Ditinjau')->count(),
        ];

        $activeTab = $request->input('tab', 'penempatan');

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'data' => [
                    'penempatan' => $penempatan,
                    'jurnals_pending' => $jurnalsPending,
                    'laporans_pending' => $laporansPending,
                ],
            ]);
        }

        $authUser = $request->auth_user ?? $request->input('auth_user') ?? [];

        return view('admin.pages.pkl.monitoring', compact(
            'authUser',
            'penempatan',
            'jurnalsPending',
            'laporansPending',
            'stats',
            'activeTab',
            'status',
            'search'
        ));
    }

    /**
     * Alias Rute Review dan Validasi Laporan & Jurnal PKL (/bkk/admin/pkl/laporan-review)
     */
    public function laporanReview(Request $request): View|JsonResponse
    {
        $request->merge(['tab' => $request->input('tab', 'jurnal')]);
        return $this->monitoring($request);
    }

    /**
     * Validasi Log Jurnal Harian PKL
     */
    public function validateJurnal(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $jurnal = PklJurnalHarian::findOrFail($id);
        $validated = $request->validate([
            'status' => ['required', 'in:Menunggu,Disetujui,Revisi'],
            'catatan_revisi' => ['nullable', 'string', 'max:1000'],
        ]);
        $status = $validated['status'];
        $catatan = $validated['catatan_revisi'] ?? null;

        $jurnal->update([
            'status' => $status,
            'catatan_revisi' => $catatan,
            'divalidasi_oleh' => $request->auth_user['id'] ?? 'adm-bkk-001',
            'divalidasi_pada' => now(),
        ]);

        // Hitung ulang total jam tercapai setiap kali status berubah (termasuk Disetujui -> Revisi)
        if ($jurnal->penempatan) {
            $penempatan = $jurnal->penempatan;
            $totalDisetujui = PklJurnalHarian::where('penempatan_pkl_id', $penempatan->id)
                ->where('status', 'Disetujui')
                ->sum('durasi_jam');
            $penempatan->update(['total_jam_tercapai' => $totalDisetujui]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Jurnal harian berhasil divalidasi ({$status}).",
                'data' => $jurnal,
            ]);
        }

        return back()->with('success', "Log jurnal harian tanggal {$jurnal->tanggal->format('d M Y')} berhasil divalidasi ({$status}).");
    }

    /**
     * Validasi Bab Naskah Laporan Akhir PKL
     */
    public function validateLaporan(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $laporan = PklLaporanAkhir::findOrFail($id);
        $validated = $request->validate([
            'status' => ['required', 'in:Belum,Ditinjau,Revisi,Disetujui'],
            'catatan_pembimbing' => ['nullable', 'string', 'max:1000'],
        ]);
        $status = $validated['status'];
        $catatan = $validated['catatan_pembimbing'] ?? null;

        $laporan->update([
            'status' => $status,
            'catatan_pembimbing' => $catatan,
            'divalidasi_oleh' => $request->auth_user['id'] ?? 'adm-bkk-001',
            'terakhir_diperbarui' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Bab {$laporan->nomor_bab} laporan PKL berhasil diperbarui statusnya ({$status}).",
                'data' => $laporan,
            ]);
        }

        return back()->with('success', "Naskah Bab {$laporan->nomor_bab} berhasil divalidasi dengan status {$status}.");
    }
}
