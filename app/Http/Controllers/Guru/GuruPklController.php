<?php

namespace App\Http\Controllers\Guru;

use App\Http\Controllers\Controller;
use App\Models\CvResume;
use App\Models\Lamaran;
use App\Models\Lowongan;
use App\Models\Mitra;
use App\Models\Notifikasi;
use App\Models\PenempatanPkl;
use App\Models\PklJurnalHarian;
use App\Models\PklLaporanAkhir;
use App\Models\ProfilSiswa;
use App\Services\MeDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class GuruPklController extends Controller
{
    protected MeDataService $meDataService;

    public function __construct(MeDataService $meDataService)
    {
        $this->meDataService = $meDataService;
    }

    /**
     * Dapatkan metadata auth user
     */
    protected function getAuthUser(Request $request): array
    {
        return $request->auth_user ?? $request->input('auth_user') ?? [
            'id' => 'usr-guru-001',
            'username' => 'guru',
            'nama_lengkap' => 'Guru Pembimbing PKL',
            'role' => 'GURU',
        ];
    }

    /**
     * Dashboard Utama Portal Guru (/bkk/guru)
     */
    public function dashboard(Request $request): View|JsonResponse
    {
        $authUser = $this->getAuthUser($request);

        $stats = [
            'total_siswa' => ProfilSiswa::count(),
            'pkl_berjalan' => PenempatanPkl::where('status', 'BERJALAN')->count(),
            'pengajuan_menunggu' => PenempatanPkl::where('status', 'MENUNGGU_PERSETUJUAN')->count(),
            'lamaran_pkl_menunggu' => Lamaran::whereHas('lowongan', fn ($q) => $q->where('tipe', 'PKL'))
                ->whereIn('status', ['Terkirim', 'Sedang Ditinjau'])
                ->count(),
            'cv_perlu_review' => CvResume::where('temp', false)
                ->where(function ($q) {
                    $q->whereNull('status_review_guru')
                      ->orWhere('status_review_guru', 'Belum Direview');
                })->count(),
            'jurnal_menunggu' => PklJurnalHarian::where('status', 'Menunggu')->count(),
            'laporan_ditinjau' => PklLaporanAkhir::where('status', 'Ditinjau')->count(),
            'pkl_selesai' => PenempatanPkl::where('status', 'SELESAI')->count(),
        ];

        // Daftar pengajuan PKL yang menunggu persetujuan (Actionable)
        $pendingPengajuan = PenempatanPkl::with(['siswa', 'mitra', 'lowongan'])
            ->where('status', 'MENUNGGU_PERSETUJUAN')
            ->latest()
            ->take(5)
            ->get();

        // Daftar CV yang baru diunggah / diupdate siswa
        $recentCvs = CvResume::with('profilSiswa')
            ->where('temp', false)
            ->latest('updated_at')
            ->take(6)
            ->get();

        // Antrean jurnal harian menunggu validasi
        $pendingJurnals = PklJurnalHarian::with(['penempatan.siswa', 'penempatan.mitra'])
            ->where('status', 'Menunggu')
            ->latest('tanggal')
            ->take(5)
            ->get();

        // Antrean laporan akhir menunggu validasi
        $pendingLaporans = PklLaporanAkhir::with(['penempatan.siswa', 'penempatan.mitra'])
            ->where('status', 'Ditinjau')
            ->latest('updated_at')
            ->take(5)
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'data' => [
                    'pending_pengajuan' => $pendingPengajuan,
                    'recent_cvs' => $recentCvs,
                    'pending_jurnals' => $pendingJurnals,
                    'pending_laporans' => $pendingLaporans,
                ],
            ]);
        }

        return view('guru.pages.dashboard', compact(
            'authUser',
            'stats',
            'pendingPengajuan',
            'recentCvs',
            'pendingJurnals',
            'pendingLaporans'
        ));
    }

    /**
     * Direktori Siswa & Monitoring CV (/bkk/guru/siswa)
     */
    public function siswaIndex(Request $request): View|JsonResponse
    {
        $authUser = $this->getAuthUser($request);

        $query = ProfilSiswa::with(['mainResume', 'activePkl.mitra']);

        $search = trim((string) $request->input('q', ''));
        if (!empty($search)) {
            $like = config('database.default') === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('nis', $like, "%{$search}%")
                  ->orWhere('nisn', $like, "%{$search}%")
                  ->orWhere('jurusan', $like, "%{$search}%")
                  ->orWhere('kelas', $like, "%{$search}%")
                  ->orWhere('user_id', $like, "%{$search}%");
            });
        }

        $jurusan = $request->input('jurusan');
        if (!empty($jurusan) && $jurusan !== 'Semua') {
            $query->where('jurusan', $jurusan);
        }

        $statusCv = $request->input('status_cv');
        if ($statusCv === 'Ada Dokumen') {
            $query->whereHas('mainResume', fn ($r) => $r->whereNotNull('file_path'));
        } elseif ($statusCv === 'Belum Direview') {
            $query->whereHas('mainResume', fn ($r) => $r->where(function ($sq) {
                $sq->whereNull('status_review_guru')->orWhere('status_review_guru', 'Belum Direview');
            }));
        } elseif ($statusCv === 'Disetujui') {
            $query->whereHas('mainResume', fn ($r) => $r->where('status_review_guru', 'Disetujui'));
        }

        $statusPkl = $request->input('status_pkl');
        if ($statusPkl === 'BERJALAN') {
            $query->whereHas('penempatanPkl', fn ($p) => $p->where('status', 'BERJALAN'));
        } elseif ($statusPkl === 'MENUNGGU_PERSETUJUAN') {
            $query->whereHas('penempatanPkl', fn ($p) => $p->where('status', 'MENUNGGU_PERSETUJUAN'));
        } elseif ($statusPkl === 'SELESAI') {
            $query->whereHas('penempatanPkl', fn ($p) => $p->where('status', 'SELESAI'));
        } elseif ($statusPkl === 'BELUM_PKL') {
            $query->doesntHave('penempatanPkl');
        }

        $daftarJurusan = ProfilSiswa::select('jurusan')->distinct()->pluck('jurusan')->filter()->values();

        $siswaList = $query->latest('updated_at')->paginate(12)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $siswaList,
            ]);
        }

        return view('guru.pages.siswa.index', compact(
            'authUser',
            'siswaList',
            'search',
            'jurusan',
            'statusCv',
            'statusPkl',
            'daftarJurusan'
        ));
    }

    /**
     * Halaman Detail & Review CV Siswa (/bkk/guru/siswa/{user_id}/cv)
     */
    public function siswaCv(Request $request, string $user_id): View|JsonResponse
    {
        $authUser = $this->getAuthUser($request);

        $siswa = ProfilSiswa::with(['activePkl.mitra', 'penempatanPkl.mitra'])
            ->where('user_id', $user_id)
            ->firstOrFail();

        // Cari CV utama atau versi terbaru siswa
        $cv = CvResume::where('user_id', $user_id)
            ->where('temp', false)
            ->where('is_main', true)
            ->first();

        if (!$cv) {
            $cv = CvResume::where('user_id', $user_id)
                ->where('temp', false)
                ->latest('id')
                ->first();
        }

        // Daftar riwayat versi CV siswa
        $cvVersions = CvResume::where('user_id', $user_id)
            ->where('temp', false)
            ->latest('id')
            ->get();

        // Riwayat lamaran siswa
        $lamarans = Lamaran::with(['lowongan.mitra'])
            ->where('siswa_id', $user_id)
            ->latest()
            ->get();

        $activePenempatan = $siswa->penempatanPkl->firstWhere('status', 'BERJALAN')
            ?? $siswa->penempatanPkl->firstWhere('status', 'MENUNGGU_PERSETUJUAN')
            ?? $siswa->penempatanPkl->first();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'siswa' => $siswa,
                'cv' => $cv,
                'cv_versions' => $cvVersions,
                'active_penempatan' => $activePenempatan,
            ]);
        }

        return view('guru.pages.siswa.cv', compact(
            'authUser',
            'siswa',
            'cv',
            'cvVersions',
            'lamarans',
            'activePenempatan'
        ));
    }

    /**
     * Simpan Review & Catatan Guru pada CV Siswa (POST /bkk/guru/siswa/{user_id}/cv/feedback)
     */
    public function storeCvFeedback(Request $request, string $user_id): RedirectResponse|JsonResponse
    {
        $authUser = $this->getAuthUser($request);

        $validated = $request->validate([
            'status_review_guru' => ['required', 'in:Disetujui,Perlu Revisi,Belum Direview'],
            'catatan_guru' => ['nullable', 'string', 'max:2000'],
            'cv_id' => ['nullable', 'integer'],
        ], [
            'status_review_guru.required' => 'Pilih status review CV.',
            'status_review_guru.in' => 'Status review CV tidak valid.',
        ]);

        $cvId = $validated['cv_id'] ?? null;
        $cv = null;

        if ($cvId) {
            $cv = CvResume::where('user_id', $user_id)->find($cvId);
        }

        if (!$cv) {
            $cv = CvResume::where('user_id', $user_id)->where('is_main', true)->first()
                ?? CvResume::where('user_id', $user_id)->latest('id')->first();
        }

        if (!$cv) {
            return back()->with('error', 'Siswa belum memiliki CV tersimpan.');
        }

        $cv->update([
            'status_review_guru' => $validated['status_review_guru'],
            'catatan_guru' => $validated['catatan_guru'] ?? null,
            'reviewed_by_guru_id' => $authUser['id'] ?? 'usr-guru-001',
            'reviewed_at' => now(),
        ]);

        // Buat notifikasi untuk siswa
        try {
            Notifikasi::create([
                'recipient_id' => $user_id,
                'recipient_role' => 'SISWA',
                'judul' => 'Review CV dari Guru Pembimbing',
                'deskripsi' => "Guru pembimbing telah memberikan review pada CV Anda dengan status '{$validated['status_review_guru']}'." . ($validated['catatan_guru'] ? " Catatan: {$validated['catatan_guru']}" : ""),
                'tipe_notifikasi' => 'PKL',
                'url_action' => route('bkk.me.cv'),
                'is_read' => false,
                'is_accent' => ($validated['status_review_guru'] === 'Disetujui'),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Review CV berhasil disimpan dan diteruskan ke siswa.',
                'data' => $cv,
            ]);
        }

        return back()->with('success', "Review CV berhasil disimpan dengan status '{$validated['status_review_guru']}'.");
    }

    /**
     * Halaman Kelola & Persetujuan Pengajuan PKL (/bkk/guru/pengajuan)
     */
    public function pengajuanIndex(Request $request): View|JsonResponse
    {
        $authUser = $this->getAuthUser($request);
        $tab = $request->input('tab', 'menunggu');

        // 1. Pengajuan PKL Siswa yang Menunggu Persetujuan Guru
        $pengajuanMenunggu = PenempatanPkl::with(['siswa', 'mitra', 'lowongan'])
            ->where('status', 'MENUNGGU_PERSETUJUAN')
            ->latest()
            ->paginate(10, ['*'], 'menunggu_page');

        // 2. Lamaran Lowongan PKL Industri Siswa (Terkirim / Sedang Ditinjau)
        $lamaranPkl = Lamaran::with(['profilSiswa', 'cvResume', 'lowongan.mitra'])
            ->whereHas('lowongan', fn ($q) => $q->where('tipe', 'PKL'))
            ->whereIn('status', ['Terkirim', 'Sedang Ditinjau'])
            ->latest()
            ->paginate(10, ['*'], 'lamaran_page');

        // 3. Penempatan PKL Aktif (Berjalan)
        $penempatanBerjalan = PenempatanPkl::with(['siswa', 'mitra', 'lowongan'])
            ->where('status', 'BERJALAN')
            ->latest()
            ->paginate(10, ['*'], 'berjalan_page');

        // 4. Riwayat Selesai / Dibatalkan
        $penempatanSelesai = PenempatanPkl::with(['siswa', 'mitra', 'lowongan'])
            ->whereIn('status', ['SELESAI', 'DIBATALKAN'])
            ->latest()
            ->paginate(10, ['*'], 'riwayat_page');

        $counts = [
            'menunggu' => PenempatanPkl::where('status', 'MENUNGGU_PERSETUJUAN')->count(),
            'lamaran' => Lamaran::whereHas('lowongan', fn ($q) => $q->where('tipe', 'PKL'))->whereIn('status', ['Terkirim', 'Sedang Ditinjau'])->count(),
            'berjalan' => PenempatanPkl::where('status', 'BERJALAN')->count(),
            'selesai' => PenempatanPkl::whereIn('status', ['SELESAI', 'DIBATALKAN'])->count(),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'counts' => $counts,
                'data' => [
                    'menunggu' => $pengajuanMenunggu,
                    'lamaran' => $lamaranPkl,
                    'berjalan' => $penempatanBerjalan,
                    'selesai' => $penempatanSelesai,
                ],
            ]);
        }

        return view('guru.pages.pengajuan.index', compact(
            'authUser',
            'tab',
            'pengajuanMenunggu',
            'lamaranPkl',
            'penempatanBerjalan',
            'penempatanSelesai',
            'counts'
        ));
    }

    /**
     * Setujui / Terima (ACCEPT) Pengajuan PKL Siswa (POST /bkk/guru/pengajuan/{id}/accept)
     */
    public function acceptPengajuan(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $authUser = $this->getAuthUser($request);

        $penempatan = PenempatanPkl::with(['siswa', 'mitra'])->findOrFail($id);

        $validated = $request->validate([
            'catatan_guru' => ['nullable', 'string', 'max:1000'],
            'pembimbing_industri_nama' => ['nullable', 'string', 'max:150'],
            'unit_kerja_divisi' => ['nullable', 'string', 'max:150'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date'],
        ]);

        $updateData = [
            'status' => 'BERJALAN',
            'guru_pembimbing_id' => $authUser['id'] ?? 'usr-guru-001',
            'disetujui_oleh' => $authUser['id'] ?? 'usr-guru-001',
            'disetujui_pada' => now(),
            'catatan_guru' => $validated['catatan_guru'] ?? 'Pengajuan PKL telah disetujui oleh Guru Pembimbing.',
        ];

        if (!empty($validated['pembimbing_industri_nama'])) {
            $updateData['pembimbing_industri_nama'] = $validated['pembimbing_industri_nama'];
        }
        if (!empty($validated['unit_kerja_divisi'])) {
            $updateData['unit_kerja_divisi'] = $validated['unit_kerja_divisi'];
        }
        if (!empty($validated['tanggal_mulai'])) {
            $updateData['tanggal_mulai'] = $validated['tanggal_mulai'];
        }
        if (!empty($validated['tanggal_selesai'])) {
            $updateData['tanggal_selesai'] = $validated['tanggal_selesai'];
        }

        $penempatan->update($updateData);

        // Jika penempatan terhubung ke lowongan PKL, sinkronkan status lamaran siswa menjadi Diterima
        if ($penempatan->lowongan_id && $penempatan->siswa_id) {
            Lamaran::where('lowongan_id', $penempatan->lowongan_id)
                ->where('siswa_id', $penempatan->siswa_id)
                ->update([
                    'status' => 'Diterima',
                    'catatan_seleksi' => 'Diterima & disahkan sebagai penempatan PKL oleh Guru Pembimbing.',
                ]);
        }

        // Perbarui status aktivitas siswa
        if ($penempatan->siswa) {
            $namaMitra = $penempatan->mitra->nama_perusahaan ?? 'Mitra Industri';
            $penempatan->siswa->update([
                'status_aktivitas' => "PKL Aktif di {$namaMitra}",
            ]);
        }

        // Berikan notifikasi ke siswa
        try {
            $mitraNama = $penempatan->mitra->nama_perusahaan ?? 'Mitra Industri';
            Notifikasi::create([
                'recipient_id' => $penempatan->siswa_id,
                'recipient_role' => 'SISWA',
                'judul' => 'Pengajuan PKL Disetujui (Accepted)!',
                'deskripsi' => "Selamat! Pengajuan PKL Anda di {$mitraNama} telah disetujui (Accepted) oleh Guru Pembimbing. Anda sekarang dapat mulai mengisi Jurnal PKL harian.",
                'tipe_notifikasi' => 'PKL',
                'url_action' => route('bkk.me.jurnal'),
                'is_read' => false,
                'is_accent' => true,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengajuan PKL berhasil disetujui (Accepted) dan status penempatan kini BERJALAN.',
                'data' => $penempatan,
            ]);
        }

        return back()->with('success', "Pengajuan PKL siswa berhasil disetujui (Accepted)! Status siswa kini aktif PKL.");
    }

    /**
     * Tolak Pengajuan PKL Siswa (POST /bkk/guru/pengajuan/{id}/reject)
     */
    public function rejectPengajuan(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $authUser = $this->getAuthUser($request);

        $penempatan = PenempatanPkl::with(['siswa', 'mitra'])->findOrFail($id);

        $validated = $request->validate([
            'alasan' => ['required', 'string', 'max:1000'],
        ], [
            'alasan.required' => 'Wajib menyertakan alasan penolakan / catatan revisi pengajuan PKL.',
        ]);

        $penempatan->update([
            'status' => 'DIBATALKAN',
            'disetujui_oleh' => $authUser['id'] ?? 'usr-guru-001',
            'disetujui_pada' => now(),
            'catatan_guru' => $validated['alasan'],
        ]);

        // Berikan notifikasi ke siswa
        try {
            $mitraNama = $penempatan->mitra->nama_perusahaan ?? 'Mitra Industri';
            Notifikasi::create([
                'recipient_id' => $penempatan->siswa_id,
                'recipient_role' => 'SISWA',
                'judul' => 'Pengajuan PKL Ditolak / Dibatalkan',
                'deskripsi' => "Pengajuan PKL di {$mitraNama} belum dapat disetujui. Catatan Guru: {$validated['alasan']}",
                'tipe_notifikasi' => 'PKL',
                'url_action' => route('bkk.me.cv'),
                'is_read' => false,
                'is_accent' => false,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengajuan PKL telah ditolak / dibatalkan.',
                'data' => $penempatan,
            ]);
        }

        return back()->with('success', 'Pengajuan PKL telah ditolak dengan catatan yang dikirimkan ke siswa.');
    }

    /**
     * Konversi Lamaran Lowongan PKL Mitra Menjadi Penempatan Resmi (POST /bkk/guru/pengajuan/lamaran/{id}/accept)
     */
    public function acceptLamaranPkl(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $authUser = $this->getAuthUser($request);

        $lamaran = Lamaran::with(['lowongan.mitra', 'profilSiswa'])->findOrFail($id);

        // Cek apakah sudah ada penempatan untuk siswa ini
        $existing = PenempatanPkl::where('siswa_id', $lamaran->siswa_id)
            ->where('status', 'BERJALAN')
            ->first();

        if ($existing) {
            return back()->with('error', 'Siswa ini sudah memiliki penempatan PKL aktif yang sedang berjalan.');
        }

        $lowongan = $lamaran->lowongan;
        $mitraId = $lowongan->mitra_id;

        $penempatan = PenempatanPkl::create([
            'siswa_id' => $lamaran->siswa_id,
            'mitra_id' => $mitraId,
            'lowongan_id' => $lowongan->id,
            'guru_pembimbing_id' => $authUser['id'] ?? 'usr-guru-001',
            'pembimbing_industri_nama' => $lowongan->mitra->pic_name ?? 'Mentor Industri',
            'unit_kerja_divisi' => $lowongan->kategori_posisi ?? 'Operasional & Teknis',
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addMonths(6)->toDateString(),
            'target_jam' => 640,
            'total_jam_tercapai' => 0,
            'status' => 'BERJALAN',
            'disetujui_oleh' => $authUser['id'] ?? 'usr-guru-001',
            'disetujui_pada' => now(),
            'catatan_guru' => 'Disetujui langsung oleh Guru Pembimbing dari lamaran lowongan PKL.',
            'pengajuan_mandiri' => false,
        ]);

        $lamaran->update([
            'status' => 'Diterima',
            'catatan_seleksi' => 'Disetujui (Accepted) oleh Guru Pembimbing & Diterbitkan Penempatan PKL.',
        ]);

        if ($lamaran->profilSiswa) {
            $namaMitra = $lowongan->mitra->nama_perusahaan ?? 'Mitra Industri';
            $lamaran->profilSiswa->update([
                'status_aktivitas' => "PKL Aktif di {$namaMitra}",
            ]);
        }

        try {
            Notifikasi::create([
                'recipient_id' => $lamaran->siswa_id,
                'recipient_role' => 'SISWA',
                'judul' => 'Lamaran PKL Disetujui (Accepted) oleh Guru!',
                'deskripsi' => "Lamaran PKL Anda untuk posisi '{$lowongan->judul}' telah disetujui Guru Pembimbing dan diterbitkan Penempatan PKL resmi.",
                'tipe_notifikasi' => 'PKL',
                'url_action' => route('bkk.me.jurnal'),
                'is_read' => false,
                'is_accent' => true,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        return back()->with('success', "Lamaran PKL berhasil di-Accept dan penempatan siswa resmi aktif!");
    }

    /**
     * Validasi Log Jurnal Harian PKL (/bkk/guru/jurnal)
     */
    public function jurnalIndex(Request $request): View|JsonResponse
    {
        $authUser = $this->getAuthUser($request);

        $statusFilter = $request->input('status', 'Menunggu');

        $query = PklJurnalHarian::with(['penempatan.siswa', 'penempatan.mitra']);

        if (!empty($statusFilter) && $statusFilter !== 'Semua') {
            $query->where('status', $statusFilter);
        }

        $search = trim((string) $request->input('q', ''));
        if (!empty($search)) {
            $like = config('database.default') === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('aktivitas', $like, "%{$search}%")
                  ->orWhereHas('penempatan.siswa', fn ($s) => $s->where('nis', $like, "%{$search}%")->orWhere('jurusan', $like, "%{$search}%"))
                  ->orWhereHas('penempatan.mitra', fn ($m) => $m->where('nama_perusahaan', $like, "%{$search}%"));
            });
        }

        $jurnals = $query->latest('tanggal')->paginate(12)->withQueryString();

        $stats = [
            'menunggu' => PklJurnalHarian::where('status', 'Menunggu')->count(),
            'disetujui' => PklJurnalHarian::where('status', 'Disetujui')->count(),
            'revisi' => PklJurnalHarian::where('status', 'Revisi')->count(),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'data' => $jurnals,
            ]);
        }

        return view('guru.pages.jurnal.index', compact(
            'authUser',
            'jurnals',
            'statusFilter',
            'search',
            'stats'
        ));
    }

    /**
     * Action Validasi Jurnal Harian (POST /bkk/guru/jurnal/{id}/validate)
     */
    public function validateJurnal(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $authUser = $this->getAuthUser($request);

        $jurnal = PklJurnalHarian::with('penempatan.siswa')->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:Menunggu,Disetujui,Revisi'],
            'catatan_revisi' => ['nullable', 'string', 'max:1000'],
        ]);

        $status = $validated['status'];
        $catatan = $validated['catatan_revisi'] ?? null;

        $jurnal->update([
            'status' => $status,
            'catatan_revisi' => $catatan,
            'divalidasi_oleh' => $authUser['id'] ?? 'usr-guru-001',
            'divalidasi_pada' => now(),
        ]);

        // Hitung ulang akumulasi jam PKL siswa
        if ($jurnal->penempatan) {
            $penempatan = $jurnal->penempatan;
            $totalJam = (int) PklJurnalHarian::where('penempatan_pkl_id', $penempatan->id)
                ->where('status', 'Disetujui')
                ->sum('durasi_jam');
            $penempatan->update(['total_jam_tercapai' => $totalJam]);
        }

        // Notifikasi ke siswa jika diminta revisi
        if ($status === 'Revisi' && $jurnal->siswa_id) {
            try {
                Notifikasi::create([
                    'recipient_id' => $jurnal->siswa_id,
                    'recipient_role' => 'SISWA',
                    'judul' => 'Catatan Revisi Jurnal PKL',
                    'deskripsi' => "Jurnal harian tanggal {$jurnal->tanggal->format('d M Y')} memerlukan revisi: {$catatan}",
                    'tipe_notifikasi' => 'PKL',
                    'url_action' => route('bkk.me.jurnal'),
                    'is_read' => false,
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Jurnal tanggal {$jurnal->tanggal->format('d M Y')} berhasil divalidasi ({$status}).",
                'data' => $jurnal,
            ]);
        }

        return back()->with('success', "Jurnal tanggal {$jurnal->tanggal->format('d M Y')} berhasil divalidasi ({$status}).");
    }

    /**
     * Review Naskah Laporan Akhir PKL (/bkk/guru/laporan)
     */
    public function laporanIndex(Request $request): View|JsonResponse
    {
        $authUser = $this->getAuthUser($request);

        $statusFilter = $request->input('status', 'Ditinjau');

        $query = PklLaporanAkhir::with(['penempatan.siswa', 'penempatan.mitra']);

        if (!empty($statusFilter) && $statusFilter !== 'Semua') {
            $query->where('status', $statusFilter);
        }

        $search = trim((string) $request->input('q', ''));
        if (!empty($search)) {
            $like = config('database.default') === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('judul_bab', $like, "%{$search}%")
                  ->orWhereHas('penempatan.siswa', fn ($s) => $s->where('nis', $like, "%{$search}%")->orWhere('jurusan', $like, "%{$search}%"))
                  ->orWhereHas('penempatan.mitra', fn ($m) => $m->where('nama_perusahaan', $like, "%{$search}%"));
            });
        }

        $laporans = $query->latest('updated_at')->paginate(12)->withQueryString();

        $stats = [
            'ditinjau' => PklLaporanAkhir::where('status', 'Ditinjau')->count(),
            'disetujui' => PklLaporanAkhir::where('status', 'Disetujui')->count(),
            'revisi' => PklLaporanAkhir::where('status', 'Revisi')->count(),
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'stats' => $stats,
                'data' => $laporans,
            ]);
        }

        return view('guru.pages.laporan.index', compact(
            'authUser',
            'laporans',
            'statusFilter',
            'search',
            'stats'
        ));
    }

    /**
     * Action Validasi Laporan Akhir (POST /bkk/guru/laporan/{id}/validate)
     */
    public function validateLaporan(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $authUser = $this->getAuthUser($request);

        $laporan = PklLaporanAkhir::with('penempatan.siswa')->findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:Belum,Ditinjau,Revisi,Disetujui'],
            'catatan_pembimbing' => ['nullable', 'string', 'max:1000'],
        ]);

        $status = $validated['status'];
        $catatan = $validated['catatan_pembimbing'] ?? null;

        $laporan->update([
            'status' => $status,
            'catatan_pembimbing' => $catatan,
            'divalidasi_oleh' => $authUser['id'] ?? 'usr-guru-001',
            'terakhir_diperbarui' => now(),
        ]);

        // Notifikasi ke siswa
        if ($laporan->siswa_id) {
            try {
                Notifikasi::create([
                    'recipient_id' => $laporan->siswa_id,
                    'recipient_role' => 'SISWA',
                    'judul' => "Review Laporan PKL: {$laporan->judul_bab}",
                    'deskripsi' => "Status bab ini telah diperbarui menjadi '{$status}'." . ($catatan ? " Catatan: {$catatan}" : ""),
                    'tipe_notifikasi' => 'PKL',
                    'url_action' => route('bkk.me.laporan'),
                    'is_read' => false,
                    'is_accent' => ($status === 'Disetujui'),
                ]);
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Laporan {$laporan->judul_bab} berhasil divalidasi ({$status}).",
                'data' => $laporan,
            ]);
        }

        return back()->with('success', "Laporan {$laporan->judul_bab} berhasil divalidasi ({$status}).");
    }

    /**
     * Detail Berkas Penempatan PKL Siswa (/bkk/guru/penempatan/{id})
     */
    public function penempatanShow(Request $request, string $id): View|JsonResponse
    {
        $authUser = $this->getAuthUser($request);

        $penempatan = PenempatanPkl::with([
            'siswa.mainResume',
            'mitra',
            'lowongan',
            'jurnals' => fn ($q) => $q->latest('tanggal'),
            'laporans' => fn ($q) => $q->orderBy('nomor_bab'),
        ])->findOrFail($id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $penempatan,
            ]);
        }

        return view('guru.pages.penempatan.show', compact('authUser', 'penempatan'));
    }

    /**
     * Input Nilai Akhir Sekolah PKL (POST /bkk/guru/penempatan/{id}/nilai)
     */
    public function inputNilai(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $penempatan = PenempatanPkl::findOrFail($id);

        $validated = $request->validate([
            'nilai_akhir_sekolah' => ['required', 'numeric', 'min:0', 'max:100'],
            'catatan_guru' => ['nullable', 'string', 'max:1000'],
        ], [
            'nilai_akhir_sekolah.required' => 'Nilai akhir sekolah wajib diisi.',
            'nilai_akhir_sekolah.numeric' => 'Nilai harus berupa angka antara 0 - 100.',
            'nilai_akhir_sekolah.min' => 'Nilai minimal 0.',
            'nilai_akhir_sekolah.max' => 'Nilai maksimal 100.',
        ]);

        $updateData = [
            'nilai_akhir_sekolah' => $validated['nilai_akhir_sekolah'],
        ];

        if (isset($validated['catatan_guru'])) {
            $updateData['catatan_guru'] = $validated['catatan_guru'];
        }

        $penempatan->update($updateData);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Nilai akhir sekolah sebesar {$penempatan->nilai_akhir_sekolah} berhasil disimpan.",
                'data' => $penempatan,
            ]);
        }

        return back()->with('success', "Nilai akhir sekolah ({$penempatan->nilai_akhir_sekolah}) berhasil disimpan.");
    }

    /**
     * Finalisasi / Selesaikan Penempatan PKL Siswa (POST /bkk/guru/penempatan/{id}/selesaikan)
     */
    public function selesaikanPkl(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $penempatan = PenempatanPkl::with('siswa')->findOrFail($id);

        $penempatan->update([
            'status' => 'SELESAI',
        ]);

        if ($penempatan->siswa) {
            $penempatan->siswa->update([
                'status_aktivitas' => 'Tuntas PKL (Persiapan Kelulusan)',
            ]);
        }

        try {
            Notifikasi::create([
                'recipient_id' => $penempatan->siswa_id,
                'recipient_role' => 'SISWA',
                'judul' => 'Penuntasan PKL Berhasil!',
                'deskripsi' => 'Selamat! Masa PKL Anda telah dinyatakan resmi SELESAI oleh Guru Pembimbing. Terima kasih atas dedikasi dan kinerjanya!',
                'tipe_notifikasi' => 'PKL',
                'url_action' => route('bkk.me.laporan'),
                'is_read' => false,
                'is_accent' => true,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Status PKL siswa telah resmi dinyatakan SELESAI.',
                'data' => $penempatan,
            ]);
        }

        return back()->with('success', 'Status PKL siswa telah resmi dinyatakan SELESAI.');
    }
}
