<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mitra;
use App\Models\PermohonanKerjasama;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminMitraController extends Controller
{
    /**
     * Daftar Mitra IDUKA (/bkk/admin/mitra)
     */
    public function index(Request $request): View|JsonResponse
    {
        $query = Mitra::withCount(['lowongans', 'penempatanPkl']);

        $search = trim((string) $request->input('q', ''));
        if (!empty($search)) {
            $like = config('database.default') === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('nama_perusahaan', $like, "%{$search}%")
                  ->orWhere('kota', $like, "%{$search}%")
                  ->orWhere('npwp', $like, "%{$search}%")
                  ->orWhere('sektor_industri', $like, "%{$search}%")
                  ->orWhere('pic_name', $like, "%{$search}%");
            });
        }

        $filterVerifikasi = $request->input('status');
        if ($filterVerifikasi === 'verified') {
            $query->where('is_verified', true);
        } elseif ($filterVerifikasi === 'pending') {
            $query->where('is_verified', false);
        }

        $mitras = $query->latest('created_at')->paginate(10)->withQueryString();

        $stats = [
            'total_mitra' => Mitra::count(),
            'verified' => Mitra::where('is_verified', true)->count(),
            'pending_verifikasi' => Mitra::where('is_verified', false)->count(),
            'permohonan_masuk' => PermohonanKerjasama::where('status', 'MENUNGGU_REVIEW')->count(),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'data' => $mitras,
            ]);
        }

        $authUser = $request->auth_user ?? $request->input('auth_user') ?? [];

        return view('admin.pages.mitra.index', compact('authUser', 'mitras', 'stats', 'search', 'filterVerifikasi'));
    }

    /**
     * Form Tambah Mitra Baru (/bkk/admin/mitra/new)
     */
    public function create(Request $request): View
    {
        $authUser = $request->auth_user ?? $request->input('auth_user') ?? [];
        return view('admin.pages.mitra.create', compact('authUser'));
    }

    /**
     * Handler Simpan Mitra Baru (POST /bkk/admin/mitra)
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'nama_perusahaan' => ['required', 'string', 'max:200'],
            'singkatan' => ['nullable', 'string', 'max:50'],
            'npwp' => ['required', 'string', 'max:50', 'unique:mitra,npwp'],
            'password' => ['required', 'string', 'min:8'],
            'sektor_industri' => ['required', 'string', 'max:150'],
            'alamat_kantor' => ['required', 'string'],
            'kota' => ['required', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'email_perusahaan' => ['required', 'email', 'max:150'],
            'no_telp_perusahaan' => ['required', 'string', 'max:50'],
            'pic_name' => ['required', 'string', 'max:150'],
            'pic_role' => ['required', 'string', 'max:150'],
            'pic_email' => ['required', 'email', 'max:150'],
            'pic_phone' => ['required', 'string', 'max:50'],
            'is_verified' => ['nullable'],
        ], [
            'nama_perusahaan.required' => 'Nama Perusahaan wajib diisi.',
            'npwp.required' => 'Nomor NPWP perusahaan wajib diisi.',
            'npwp.unique' => 'Nomor NPWP sudah terdaftar dalam sistem.',
            'password.required' => 'Kata sandi login mitra wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'sektor_industri.required' => 'Sektor industri wajib diisi.',
            'alamat_kantor.required' => 'Alamat kantor wajib diisi.',
            'kota.required' => 'Kota domisili perusahaan wajib diisi.',
            'email_perusahaan.required' => 'Email resmi perusahaan wajib diisi.',
            'no_telp_perusahaan.required' => 'Nomor telepon kantor wajib diisi.',
            'pic_name.required' => 'Nama Person-in-Charge (PIC) wajib diisi.',
            'pic_role.required' => 'Jabatan PIC wajib diisi.',
            'pic_email.required' => 'Email PIC wajib diisi.',
            'pic_phone.required' => 'Nomor telepon/WhatsApp PIC wajib diisi.',
        ]);

        // Sanitasi Nama Perusahaan
        $validated['nama_perusahaan'] = trim(preg_replace('/\s+/', ' ', $validated['nama_perusahaan']));
        $validated['password'] = Hash::make($validated['password']);
        $validated['is_verified'] = $request->boolean('is_verified');
        $validated['status_kemitraan'] = $validated['is_verified'] ? 'Mitra IDUKA Terverifikasi' : 'Menunggu Verifikasi Admin';

        $mitra = Mitra::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Mitra '{$mitra->nama_perusahaan}' berhasil ditambahkan!",
                'data' => $mitra,
            ], 201);
        }

        return redirect()
            ->route('bkk.admin.mitra.index')
            ->with('success', "Mitra '{$mitra->nama_perusahaan}' berhasil ditambahkan dan siap digunakan untuk login!");
    }

    /**
     * Form Edit Mitra (/bkk/admin/mitra/{id_mitra})
     */
    public function edit(Request $request, string $id): View
    {
        $mitra = Mitra::withCount(['lowongans', 'penempatanPkl'])->findOrFail($id);
        $authUser = $request->auth_user ?? $request->input('auth_user') ?? [];

        return view('admin.pages.mitra.edit', compact('authUser', 'mitra'));
    }

    /**
     * Handler Update Mitra (PUT /bkk/admin/mitra/{id_mitra})
     */
    public function update(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $mitra = Mitra::findOrFail($id);

        $validated = $request->validate([
            'nama_perusahaan' => ['required', 'string', 'max:200'],
            'singkatan' => ['nullable', 'string', 'max:50'],
            'npwp' => ['required', 'string', 'max:50', Rule::unique('mitra')->ignore($mitra->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'sektor_industri' => ['required', 'string', 'max:150'],
            'alamat_kantor' => ['required', 'string'],
            'kota' => ['required', 'string', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'email_perusahaan' => ['required', 'email', 'max:150'],
            'no_telp_perusahaan' => ['required', 'string', 'max:50'],
            'pic_name' => ['required', 'string', 'max:150'],
            'pic_role' => ['required', 'string', 'max:150'],
            'pic_email' => ['required', 'email', 'max:150'],
            'pic_phone' => ['required', 'string', 'max:50'],
            'is_verified' => ['nullable'],
        ]);

        $validated['nama_perusahaan'] = trim(preg_replace('/\s+/', ' ', $validated['nama_perusahaan']));

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['is_verified'] = $request->boolean('is_verified');
        $validated['status_kemitraan'] = $validated['is_verified'] ? 'Mitra IDUKA Terverifikasi' : 'Nonaktif / Menunggu Verifikasi';

        $mitra->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Data mitra '{$mitra->nama_perusahaan}' berhasil diperbarui.",
                'data' => $mitra,
            ]);
        }

        return redirect()
            ->route('bkk.admin.mitra.index')
            ->with('success', "Data mitra '{$mitra->nama_perusahaan}' berhasil diperbarui.");
    }

    /**
     * Handler Hapus Mitra (DELETE /bkk/admin/mitra/{id_mitra})
     */
    public function destroy(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $mitra = Mitra::findOrFail($id);
        $nama = $mitra->nama_perusahaan;
        $mitra->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Mitra '{$nama}' berhasil dihapus dari sistem.",
            ]);
        }

        return redirect()
            ->route('bkk.admin.mitra.index')
            ->with('success', "Mitra '{$nama}' berhasil dihapus beserta data keterkaitannya.");
    }

    /**
     * Verifikasi atau Nonaktifkan Akun Mitra IDUKA.
     */
    public function toggleVerify(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $mitra = Mitra::findOrFail($id);
        $mitra->update([
            'is_verified' => !$mitra->is_verified,
            'status_kemitraan' => !$mitra->is_verified ? 'Mitra IDUKA Terverifikasi' : 'Nonaktif / Ditinjau',
        ]);

        $statusText = $mitra->is_verified ? 'Terverifikasi' : 'Dinonaktifkan';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Mitra {$mitra->nama_perusahaan} status diperbarui: {$statusText}.",
                'is_verified' => $mitra->is_verified,
            ]);
        }

        return back()->with('success', "Status kemitraan {$mitra->nama_perusahaan} berhasil diubah menjadi {$statusText}.");
    }

    /**
     * Daftar Permohonan Kerja Sama Baru dari Publik (/bkk/admin/mitra/permohonan)
     */
    public function permohonanIndex(Request $request): View|JsonResponse
    {
        $permohonan = PermohonanKerjasama::latest('created_at')->paginate(10);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $permohonan,
            ]);
        }

        $authUser = $request->auth_user ?? $request->input('auth_user') ?? [];

        return view('admin.pages.mitra.permohonan', compact('authUser', 'permohonan'));
    }

    /**
     * Approve / Tolak Permohonan Kerja Sama.
     */
    public function permohonanUpdateStatus(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $permohonan = PermohonanKerjasama::findOrFail($id);
        $validated = $request->validate([
            'status' => ['required', 'in:MENUNGGU_REVIEW,DISETUJUI,DITOLAK'],
            'catatan_admin' => ['nullable', 'string', 'max:1000'],
        ]);
        $newStatus = $validated['status'];
        $catatan = $validated['catatan_admin'] ?? null;

        $permohonan->update([
            'status' => $newStatus,
            'catatan_admin' => $catatan,
            'diproses_oleh' => $request->auth_user['id'] ?? 'adm-bkk-001',
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Permohonan kerja sama berhasil diupdate menjadi {$newStatus}.",
                'data' => $permohonan,
            ]);
        }

        return back()->with('success', "Permohonan berhasil diupdate.");
    }
}
