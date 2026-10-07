<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lamaran;
use App\Models\Lowongan;
use App\Models\PenempatanPkl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminLowonganController extends Controller
{
    /**
     * Moderasi dan Katalog Seluruh Lowongan Industri (/bkk/admin/lowongan)
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = Lowongan::with('mitra')->withCount('lamarans');

        $search = $request->input('q');
        if (!empty($search)) {
            $like = config('database.default') === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('judul', $like, "%{$search}%")
                  ->orWhere('target_jurusan', $like, "%{$search}%")
                  ->orWhereHas('mitra', fn ($m) => $m->where('nama_perusahaan', $like, "%{$search}%"));
            });
        }

        $tipe = $request->input('tipe');
        if (!empty($tipe) && $tipe !== 'Semua') {
            $query->where('tipe', $tipe);
        }

        $status = $request->input('status');
        if (!empty($status) && $status !== 'Semua') {
            $query->where('status', $status);
        }

        $lowongans = $query->latest('created_at')->paginate(10)->withQueryString();

        $stats = [
            'total' => Lowongan::count(),
            'aktif' => Lowongan::where('status', 'Aktif')->count(),
            'ditutup' => Lowongan::where('status', 'Ditutup')->count(),
            'draft' => Lowongan::where('status', 'Draft')->count(),
            'total_pelamar' => Lamaran::count(),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'data' => $lowongans,
            ]);
        }

        $authUser = $request->auth_user ?? $request->input('auth_user') ?? [];

        return view('admin.pages.lowongan.index', compact('authUser', 'lowongans', 'stats', 'search', 'tipe', 'status'));
    }

    /**
     * Form Edit Data Lowongan (/bkk/admin/lowongan/{id})
     */
    public function edit(Request $request, string $id): View
    {
        $lowongan = Lowongan::with('mitra')->withCount('lamarans')->findOrFail($id);
        $authUser = $request->auth_user ?? $request->input('auth_user') ?? [];

        return view('admin.pages.lowongan.edit', compact('authUser', 'lowongan'));
    }

    /**
     * Simpan Perubahan Data Lowongan (PUT /bkk/admin/lowongan/{id})
     */
    public function update(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $lowongan = Lowongan::findOrFail($id);

        $validated = $request->validate([
            'judul' => ['required', 'string', 'max:255'],
            'tipe' => ['required', 'in:PKL,Kerja'],
            'target_jurusan' => ['required', 'string', 'max:150'],
            'lokasi' => ['required', 'string', 'max:150'],
            'gaji_kompensasi' => ['nullable', 'string', 'max:150'],
            'kuota' => ['required', 'integer', 'min:1'],
            'deadline' => ['required', 'date'],
            'status' => ['required', 'in:Aktif,Ditutup,Draft'],
            'deskripsi' => ['required', 'string'],
            'persyaratan' => ['nullable', 'string'],
            'benefit' => ['nullable', 'string'],
        ]);

        if (isset($validated['persyaratan'])) {
            $validated['persyaratan_json'] = array_values(array_filter(array_map('trim', explode("\n", str_replace("\r", "", $validated['persyaratan'])))));
            unset($validated['persyaratan']);
        }

        if (isset($validated['benefit'])) {
            $validated['benefit_json'] = array_values(array_filter(array_map('trim', explode("\n", str_replace("\r", "", $validated['benefit'])))));
            unset($validated['benefit']);
        }

        $lowongan->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Lowongan '{$lowongan->judul}' berhasil diperbarui.",
                'data' => $lowongan,
            ]);
        }

        return redirect()
            ->route('bkk.admin.lowongan.index')
            ->with('success', "Informasi lowongan '{$lowongan->judul}' berhasil diperbarui.");
    }

    /**
     * Hapus Lowongan (/bkk/admin/lowongan/{id})
     */
    public function destroy(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $lowongan = Lowongan::findOrFail($id);
        $judul = $lowongan->judul;
        $lowongan->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Lowongan '{$judul}' berhasil dihapus.",
            ]);
        }

        return redirect()
            ->route('bkk.admin.lowongan.index')
            ->with('success', "Lowongan '{$judul}' berhasil dihapus dari sistem.");
    }

    /**
     * Update status publikasi lowongan oleh Admin BKK.
     */
    public function updateStatus(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $lowongan = Lowongan::findOrFail($id);
        $validated = $request->validate([
            'status' => ['required', 'in:Aktif,Ditutup,Draft'],
        ]);
        $newStatus = $validated['status'];

        $lowongan->update(['status' => $newStatus]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Status lowongan berhasil diperbarui menjadi {$newStatus}.",
                'data' => $lowongan,
            ]);
        }

        return back()->with('success', "Status lowongan berhasil diubah menjadi {$newStatus}.");
    }

    /**
     * Daftar Siswa yang Diterima / Terdaftar PKL pada Lowongan (/bkk/admin/lowongan/{id}/siswa)
     */
    public function siswa(Request $request, string $id): View|JsonResponse
    {
        $lowongan = Lowongan::with('mitra')->findOrFail($id);
        $authUser = $request->auth_user ?? $request->input('auth_user') ?? [];

        // Siswa yang berstatus diterima pada lowongan ini
        $lamaransDiterima = Lamaran::with(['siswa', 'cv'])
            ->where('lowongan_id', $id)
            ->where('status', 'Diterima')
            ->latest('updated_at')
            ->paginate(15);

        // Penempatan PKL terkait jika lowongan bertipe PKL
        $penempatan = PenempatanPkl::with('siswa')
            ->where('lowongan_id', $id)
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'lowongan' => $lowongan,
                'siswa_diterima' => $lamaransDiterima,
                'penempatan' => $penempatan,
            ]);
        }

        return view('admin.pages.lowongan.siswa', compact('authUser', 'lowongan', 'lamaransDiterima', 'penempatan'));
    }
}
