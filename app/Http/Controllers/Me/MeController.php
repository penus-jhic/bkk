<?php

namespace App\Http\Controllers\Me;

use App\Http\Controllers\Controller;
use App\Models\CvResume;
use App\Models\Lamaran;
use App\Models\LamaranRiwayatStatus;
use App\Models\Lowongan;
use App\Models\Notifikasi;
use App\Models\PenempatanPkl;
use App\Models\PklJurnalHarian;
use App\Models\PklLaporanAkhir;
use App\Models\ProfilSiswa;
use App\Services\MeDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class MeController extends Controller
{
    public function __construct(
        protected MeDataService $dataService
    ) {}

    /**
     * Dashboard Siswa & Alumni (/bkk/me)
     */
    public function dashboard(Request $request): View|JsonResponse
    {
        $authUser = $this->resolveAuthUser($request);
        $userId = $authUser['id'];
        $role = $authUser['role'];

        $profile = $this->dataService->getProfile($authUser);
        $applications = $this->getMappedApplications($userId);
        $vacancies = $this->getMappedVacancies($role);
        $cvScore = $this->dataService->getCvScore($role, $userId);
        $notifications = $this->getMappedNotifications($userId, $role);

        // Sinkronisasi data riil profil dari database jika record siswa/alumni ada
        try {
            if (Schema::hasTable('profil_siswa')) {
                $dbSiswa = ProfilSiswa::with(['primaryResume', 'penempatanPkl.mitra'])->find($userId);
                if ($dbSiswa) {
                    $profile['nis'] = $dbSiswa->nis ?: $profile['nis'];
                    $profile['jurusan'] = $dbSiswa->jurusan ?: $profile['jurusan'];
                    $profile['kelas'] = $dbSiswa->kelas ?? $profile['kelas'];
                    $profile['completion'] = $dbSiswa->kelengkapan_profil ?: $profile['completion'];
                    $profile['status_aktivitas'] = $dbSiswa->status_aktivitas ?? $profile['status'];

                    $primaryCv = $dbSiswa->primaryResume;
                    if ($primaryCv) {
                        $cvScore['total'] = $primaryCv->skor_total_ai;
                        if (!empty($primaryCv->skor_parameter_json)) {
                            $cvScore['params'] = $primaryCv->skor_parameter_json;
                        }
                    }
                }
            }
        } catch (\Throwable) {}

        $activeApplications = array_values(array_filter($applications, fn ($a) => !in_array($a['status'], ['Diterima', 'Ditolak'])));
        $interviews = array_values(array_filter($applications, fn ($a) => $a['status'] === 'Dipanggil Interview'));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'profile' => $profile,
                    'applications' => $applications,
                    'vacancies' => $vacancies,
                    'cv_score' => $cvScore,
                    'active_count' => count($activeApplications),
                    'interview_count' => count($interviews),
                ],
            ]);
        }

        return view('me.pages.dashboard', compact(
            'authUser',
            'profile',
            'applications',
            'vacancies',
            'cvScore',
            'notifications',
            'activeApplications',
            'interviews'
        ));
    }

    /**
     * Riwayat Lamaran (/bkk/me/lamaran)
     */
    public function lamaran(Request $request): View|JsonResponse
    {
        $authUser = $this->resolveAuthUser($request);
        $userId = $authUser['id'];
        $role = $authUser['role'];

        $profile = $this->dataService->getProfile($authUser);
        $applications = $this->getMappedApplications($userId);
        $notifications = $this->getMappedNotifications($userId, $role);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'applications' => $applications,
                ],
            ]);
        }

        return view('me.pages.lamaran', compact(
            'authUser',
            'profile',
            'applications',
            'notifications'
        ));
    }

    /**
     * CV Preview & AI Scoring Hub (/bkk/me/cv)
     */
    public function cv(Request $request): View|JsonResponse
    {
        $authUser = $this->resolveAuthUser($request);
        $userId = $authUser['id'];
        $role = $authUser['role'];

        $profile = $this->dataService->getProfile($authUser);
        $cvScore = $this->dataService->getCvScore($role, $userId);
        $suggestions = $this->dataService->getCvSuggestions($role, $userId);
        $cvMarkdown = $this->dataService->getCvMarkdown($role, $profile, $userId);
        $notifications = $this->getMappedNotifications($userId, $role) ?: $this->dataService->getNotifications($userId);

        try {
            if (Schema::hasTable('cv_resumes')) {
                $dbCv = CvResume::where('siswa_id', $userId)->where('is_primary', true)->first();
                if ($dbCv && !empty($dbCv->konten_markdown)) {
                    $cvMarkdown = $dbCv->konten_markdown;
                    $cvScore['total'] = $dbCv->skor_total_ai;
                    if (!empty($dbCv->skor_parameter_json)) {
                        $cvScore['params'] = $dbCv->skor_parameter_json;
                    }
                    if (!empty($dbCv->saran_perbaikan_ai_json)) {
                        $suggestions = $dbCv->saran_perbaikan_ai_json;
                    }
                }
            }
        } catch (\Throwable) {}

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'score' => $cvScore,
                    'suggestions' => $suggestions,
                    'markdown' => $cvMarkdown,
                ],
            ]);
        }

        return view('me.pages.cv', compact(
            'authUser',
            'profile',
            'cvScore',
            'suggestions',
            'cvMarkdown',
            'notifications'
        ));
    }

    /**
     * Editor CV (/bkk/me/cv/edit)
     */
    public function cvEdit(Request $request): View|JsonResponse
    {
        $authUser = $this->resolveAuthUser($request);
        $userId = $authUser['id'];
        $role = $authUser['role'];

        $profile = $this->dataService->getProfile($authUser);
        $cvScore = $this->dataService->getCvScore($role, $userId);
        $cvMarkdown = $this->dataService->getCvMarkdown($role, $profile, $userId);
        $notifications = $this->getMappedNotifications($userId, $role) ?: $this->dataService->getNotifications($userId);

        try {
            if (Schema::hasTable('cv_resumes')) {
                $dbCv = CvResume::where('siswa_id', $userId)->where('is_primary', true)->first();
                if ($dbCv && !empty($dbCv->konten_markdown)) {
                    $cvMarkdown = $dbCv->konten_markdown;
                }
            }
        } catch (\Throwable) {}

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'markdown' => $cvMarkdown,
                ],
            ]);
        }

        return view('me.pages.cv-edit', compact(
            'authUser',
            'profile',
            'cvScore',
            'cvMarkdown',
            'notifications'
        ));
    }

    /**
     * Update CV Markdown (POST /bkk/me/cv)
     */
    public function cvUpdate(Request $request): RedirectResponse|JsonResponse
    {
        $authUser = $this->resolveAuthUser($request);
        $userId = $authUser['id'];

        $validated = $request->validate([
            'markdown' => ['required', 'string'],
        ]);
        $markdown = $validated['markdown'];

        try {
            if (Schema::hasTable('cv_resumes') && $markdown) {
                CvResume::updateOrCreate(
                    [
                        'siswa_id' => $userId,
                        'is_primary' => true,
                    ],
                    [
                        'judul_cv' => 'CV ATS - ' . ($authUser['nama_lengkap'] ?? 'Siswa'),
                        'konten_markdown' => $markdown,
                        'terakhir_dianalisis_ai' => now(),
                    ]
                );
            }
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Konten CV ATS berhasil diperbarui!',
            ]);
        }

        return redirect()->route('bkk.me.cv')->with('success', 'CV berhasil disimpan!');
    }

    /**
     * Jurnal PKL Harian (/bkk/me/jurnal)
     */
    public function jurnal(Request $request): View|JsonResponse
    {
        $authUser = $this->resolveAuthUser($request);
        $userId = $authUser['id'];
        $role = $authUser['role'];

        $profile = $this->dataService->getProfile($authUser);
        $isSiswa = ($role === 'SISWA');
        $jurnalEntries = $this->getMappedJurnalEntries($userId);
        $notifications = $this->getMappedNotifications($userId, $role);

        $penempatan = null;
        try {
            if (Schema::hasTable('penempatan_pkl')) {
                $penempatan = PenempatanPkl::with('mitra')
                    ->where('siswa_id', $userId)
                    ->where('status', 'BERJALAN')
                    ->first();
            }
        } catch (\Throwable) {}

        $totalHours = (int) array_sum(array_column(array_filter($jurnalEntries, fn($j) => $j['status'] === 'Disetujui'), 'hours'));
        if ($totalHours === 0 && $penempatan && $penempatan->total_jam_tercapai > 0) {
            $totalHours = (int) $penempatan->total_jam_tercapai;
        }
        $targetHours = $penempatan?->target_jam ?? 640;
        $approvedCount = count(array_filter($jurnalEntries, fn($j) => $j['status'] === 'Disetujui'));
        $revisionCount = count(array_filter($jurnalEntries, fn($j) => $j['status'] === 'Revisi'));
        $mitraNama = $penempatan?->mitra?->nama_perusahaan ?? 'Belum Ada Penempatan';
        $pembimbingNama = $penempatan
            ? (($penempatan->unit_kerja_divisi ?: 'Divisi PKL') . ' · Pembimbing: ' . ($penempatan->pembimbing_industri_nama ?: 'Belum ditentukan'))
            : 'Belum terdaftar penempatan PKL aktif';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_siswa' => $isSiswa,
                'data' => $isSiswa ? $jurnalEntries : [],
                'stats' => [
                    'total_hours' => $totalHours,
                    'target_hours' => $targetHours,
                    'approved_count' => $approvedCount,
                    'revision_count' => $revisionCount,
                    'mitra_nama' => $mitraNama,
                    'pembimbing_nama' => $pembimbingNama,
                ],
            ]);
        }

        return view('me.pages.jurnal', compact(
            'authUser',
            'profile',
            'isSiswa',
            'jurnalEntries',
            'notifications',
            'penempatan',
            'totalHours',
            'targetHours',
            'approvedCount',
            'revisionCount',
            'mitraNama',
            'pembimbingNama'
        ));
    }

    /**
     * Input Log Jurnal Harian Baru (POST /bkk/me/jurnal)
     */
    public function storeJurnal(Request $request): RedirectResponse|JsonResponse
    {
        $authUser = $this->resolveAuthUser($request);
        $userId = $authUser['id'];

        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'aktivitas' => ['required', 'string'],
            'durasi_jam' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $createdEntry = null;
        try {
            if (Schema::hasTable('penempatan_pkl') && Schema::hasTable('pkl_jurnal_harian')) {
                $penempatan = PenempatanPkl::where('siswa_id', $userId)->where('status', 'BERJALAN')->first();

                if (!$penempatan) {
                    if ($request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Anda belum terdaftar dalam penempatan PKL aktif.',
                        ], 422);
                    }
                    return redirect()->route('bkk.me.jurnal')->with('error', 'Anda belum terdaftar dalam penempatan PKL aktif.');
                }

                $createdEntry = PklJurnalHarian::create([
                    'penempatan_pkl_id' => $penempatan->id,
                    'siswa_id' => $userId,
                    'tanggal' => $validated['tanggal'],
                    'aktivitas' => $validated['aktivitas'],
                    'durasi_jam' => $validated['durasi_jam'] ?? 8,
                    'status' => 'Menunggu',
                ]);
            }
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return redirect()->route('bkk.me.jurnal')->with('error', 'Gagal mencatat jurnal: ' . $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Log aktivitas jurnal PKL harian berhasil disimpan!',
                'data' => $createdEntry ? [
                    'id' => $createdEntry->id,
                    'date' => $createdEntry->tanggal ? $createdEntry->tanggal->format('d M Y') : date('d M Y'),
                    'hours' => $createdEntry->durasi_jam,
                    'activity' => $createdEntry->aktivitas,
                    'status' => $createdEntry->status,
                ] : null,
            ], 201);
        }

        return redirect()->route('bkk.me.jurnal')->with('success', 'Aktivitas harian berhasil dicatat dan menunggu validasi guru pembimbing.');
    }

    /**
     * Laporan Akhir PKL (/bkk/me/laporan)
     */
    public function laporan(Request $request): View|JsonResponse
    {
        $authUser = $this->resolveAuthUser($request);
        $userId = $authUser['id'];
        $role = $authUser['role'];

        $profile = $this->dataService->getProfile($authUser);
        $isSiswa = ($role === 'SISWA');
        $laporanSections = $this->getMappedLaporanSections($userId);
        $notifications = $this->getMappedNotifications($userId, $role);

        $penempatan = null;
        try {
            if (Schema::hasTable('penempatan_pkl')) {
                $penempatan = PenempatanPkl::with('mitra')
                    ->where('siswa_id', $userId)
                    ->where('status', 'BERJALAN')
                    ->first();
            }
        } catch (\Throwable) {}

        $deadlineStr = $penempatan?->tanggal_selesai ? $penempatan->tanggal_selesai->format('d F Y') : '30 April 2025';

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_siswa' => $isSiswa,
                'data' => $isSiswa ? $laporanSections : [],
                'deadline' => $deadlineStr,
            ]);
        }

        return view('me.pages.laporan', compact(
            'authUser',
            'profile',
            'isSiswa',
            'laporanSections',
            'notifications',
            'penempatan',
            'deadlineStr'
        ));
    }

    /**
     * Unggah / Update Draf Bab Laporan Akhir (POST /bkk/me/laporan)
     */
    public function storeLaporan(Request $request): RedirectResponse|JsonResponse
    {
        $authUser = $this->resolveAuthUser($request);
        $userId = $authUser['id'];

        $validated = $request->validate([
            'nomor_bab' => ['required', 'integer', 'between:1,5'],
            'judul_bab' => ['required', 'string'],
        ]);

        $laporan = null;
        try {
            if (Schema::hasTable('penempatan_pkl') && Schema::hasTable('pkl_laporan_akhir')) {
                $penempatan = PenempatanPkl::where('siswa_id', $userId)->where('status', 'BERJALAN')->first();

                if (!$penempatan) {
                    if ($request->wantsJson()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Anda belum memiliki penempatan PKL aktif.',
                        ], 422);
                    }
                    return redirect()->route('bkk.me.laporan')->with('error', 'Anda belum memiliki penempatan PKL aktif.');
                }

                $laporan = PklLaporanAkhir::updateOrCreate(
                    [
                        'penempatan_pkl_id' => $penempatan->id,
                        'nomor_bab' => $validated['nomor_bab'],
                    ],
                    [
                        'siswa_id' => $userId,
                        'judul_bab' => $validated['judul_bab'],
                        'status' => 'Ditinjau',
                        'terakhir_diperbarui' => now()->toDateString(),
                    ]
                );
            }
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return redirect()->route('bkk.me.laporan')->with('error', 'Gagal memperbarui draf laporan: ' . $e->getMessage());
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Draf bab laporan PKL berhasil diperbarui untuk ditinjau guru pembimbing.',
                'data' => $laporan ? [
                    'id' => $laporan->nomor_bab,
                    'title' => $laporan->judul_bab,
                    'status' => $laporan->status,
                    'note' => $laporan->catatan_pembimbing ?? 'Belum ada catatan pembimbing',
                    'updated' => $laporan->terakhir_diperbarui ? $laporan->terakhir_diperbarui->format('d M Y') : date('d M Y'),
                ] : null,
            ]);
        }

        return redirect()->route('bkk.me.laporan')->with('success', 'Draf bab laporan berhasil diserahkan ke guru pembimbing.');
    }

    /**
     * Handler Pendaftaran Lowongan PKL / Kerja (/bkk/{id_lowongan}/daftar)
     */
    public function storeLamaran(Request $request, string $id_lowongan): RedirectResponse|JsonResponse
    {
        $authUser = $this->resolveAuthUser($request);
        $userId = $authUser['id'];

        $lowongan = Lowongan::where('slug', $id_lowongan)
            ->orWhere('id', is_numeric($id_lowongan) ? (int)$id_lowongan : 0)
            ->firstOrFail();

        $cv = CvResume::where('siswa_id', $userId)->where('is_primary', true)->first();

        $existing = Lamaran::where('siswa_id', $userId)
            ->where('lowongan_id', $lowongan->id)
            ->first();

        if ($existing) {
            $msg = 'Anda sudah pernah mengajukan lamaran pada lowongan ini.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->route('bkk.me.lamaran')->with('warning', $msg);
        }

        $kodeLamaran = 'LMR-' . date('Y') . '-' . strtoupper(substr(uniqid(), -5));

        $lamaran = Lamaran::create([
            'kode_lamaran' => $kodeLamaran,
            'lowongan_id' => $lowongan->id,
            'siswa_id' => $userId,
            'cv_id' => $cv?->id ?? 1,
            'tanggal_melamar' => now()->toDateString(),
            'skor_match_ai' => 90,
            'status' => 'Terkirim',
            'step_tahapan' => 1,
            'catatan_seleksi' => 'Berkas pendaftaran otomatis diverifikasi sistem.',
        ]);

        if (Schema::hasTable('lamaran_riwayat_status')) {
            LamaranRiwayatStatus::create([
                'lamaran_id' => $lamaran->id,
                'judul_tahapan' => 'Lamaran Terkirim',
                'deskripsi' => 'Pendaftaran diajukan melalui portal BKK Penus.',
                'diubah_oleh_id' => $userId,
                'diubah_oleh_role' => $authUser['role'],
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Lamaran berhasil diajukan!',
                'data' => $lamaran,
            ], 201);
        }

        return redirect()->route('bkk.me.lamaran')->with('success', "Lamaran berhasil diajukan dengan Kode {$kodeLamaran}!");
    }

    /**
     * Mappings Helper dari Eloquent ke Array View
     */
    protected function getMappedApplications(string $userId): array
    {
        try {
            if (Schema::hasTable('lamaran')) {
                $dbLamarans = Lamaran::with(['lowongan.mitra', 'interview'])
                    ->where('siswa_id', $userId)
                    ->latest('tanggal_melamar')
                    ->get();

                if ($dbLamarans->isNotEmpty()) {
                    return $dbLamarans->map(function ($l) {
                        $lowongan = $l->lowongan;
                        $mitra = $lowongan?->mitra;
                        $interview = $l->interview;
                        $tgl = $l->tanggal_melamar ? $l->tanggal_melamar->format('d M Y') : ($l->created_at ? $l->created_at->format('d M Y') : date('d M Y'));

                        return [
                            'id' => $l->kode_lamaran,
                            'code' => $l->kode_lamaran,
                            'position' => $lowongan?->judul ?? 'Posisi Terpilih',
                            'role' => $lowongan?->judul ?? 'Posisi Terpilih',
                            'company' => $mitra?->nama_perusahaan ?? 'Mitra Industri',
                            'mitra' => $mitra?->sektor_industri ?? 'Teknologi & Operasional',
                            'type' => $lowongan?->tipe === 'PKL' ? 'PKL / Magang' : 'Full-time',
                            'location' => $lowongan?->lokasi ?? 'Bogor',
                            'date' => $tgl,
                            'applied_at' => $tgl,
                            'status' => $l->status,
                            'color' => '#1b283b',
                            'step' => $l->step_tahapan ?: 1,
                            'match_score' => $l->skor_match_ai ?: 92,
                            'timeline' => [
                                ['date' => $tgl, 'title' => 'Lamaran Dikirim', 'desc' => 'Berkas dan CV diajukan ke sistem BKK.', 'done' => true],
                                ['date' => '', 'title' => 'Verifikasi Berkas BKK', 'desc' => 'BKK Penus memvalidasi kualifikasi.', 'done' => in_array($l->status, ['Sedang Ditinjau', 'Dipanggil Interview', 'Diterima', 'Ditolak'])],
                                ['date' => '', 'title' => 'Tahap Seleksi & Interview', 'desc' => 'Ulasan oleh mitra industri atau pemanggilan interview.', 'done' => in_array($l->status, ['Dipanggil Interview', 'Diterima'])],
                                ['date' => '', 'title' => 'Pengumuman Hasil', 'desc' => 'Status akhir penerimaan.', 'done' => in_array($l->status, ['Diterima', 'Ditolak'])],
                            ],
                            'interview' => $interview ? [
                                'date' => $interview->tanggal_interview ? $interview->tanggal_interview->format('l, d M Y') : '',
                                'time' => $interview->waktu_interview ?? '09:00 WIB',
                                'mode' => $interview->mode ?? 'Tatap Muka',
                                'place' => $interview->lokasi_atau_url ?? 'Kampus BKK / Kantor Mitra',
                                'location' => $interview->lokasi_atau_url ?? 'Kampus BKK / Kantor Mitra',
                                'pic' => $interview->pic_pewawancara ?? 'Tim HRD Mitra',
                                'instructions' => $interview->instruksi_khusus ?? 'Membawa berkas cetak dan mengenakan pakaian rapi.',
                                'notes' => $interview->instruksi_khusus ?? '',
                            ] : null,
                        ];
                    })->all();
                }
            }
        } catch (\Throwable) {}

        return [];
    }

    protected function getMappedVacancies(string $role): array
    {
        try {
            if (Schema::hasTable('lowongan')) {
                $tipeTarget = ($role === 'SISWA') ? 'PKL' : 'Kerja';
                $dbLowongans = Lowongan::with('mitra')
                    ->where('status', 'Aktif')
                    ->where('tipe', $tipeTarget)
                    ->latest('created_at')
                    ->take(6)
                    ->get();

                if ($dbLowongans->isEmpty()) {
                    $dbLowongans = Lowongan::with('mitra')
                        ->where('status', 'Aktif')
                        ->latest('created_at')
                        ->take(6)
                        ->get();
                }

                if ($dbLowongans->isNotEmpty()) {
                    return $dbLowongans->map(function ($l) {
                        $jurusanTags = array_values(array_filter(array_map('trim', explode(',', (string) $l->target_jurusan))));
                        if (empty($jurusanTags)) {
                            $jurusanTags = ['Kompetensi Kejuruan', 'Sertifikasi Industri'];
                        }

                        return [
                            'id' => (string) $l->id,
                            'title' => $l->judul,
                            'company' => $l->mitra?->nama_perusahaan ?? 'Mitra Industri',
                            'type' => $l->tipe === 'PKL' ? 'PKL / Magang' : 'Full-time',
                            'jurusan' => $l->target_jurusan,
                            'location' => $l->lokasi,
                            'salary' => $l->gaji_kompensasi ?? 'Kompetitif UMK',
                            'deadline' => $l->deadline ? $l->deadline->format('d M Y') : 'Open',
                            'match' => 95,
                            'tags' => $jurusanTags,
                            'color' => '#1b283b',
                        ];
                    })->all();
                }
            }
        } catch (\Throwable) {}

        return [];
    }

    protected function getMappedJurnalEntries(string $userId): array
    {
        try {
            if (Schema::hasTable('pkl_jurnal_harian')) {
                $dbJurnals = PklJurnalHarian::where('siswa_id', $userId)
                    ->latest('tanggal')
                    ->get();

                if ($dbJurnals->isNotEmpty()) {
                    return $dbJurnals->map(function ($j) {
                        return [
                            'id' => $j->id,
                            'date' => $j->tanggal ? $j->tanggal->format('d M Y') : $j->created_at->format('d M Y'),
                            'hours' => $j->durasi_jam,
                            'activity' => $j->aktivitas,
                            'status' => $j->status,
                            'catatan' => $j->catatan_revisi,
                            'note' => $j->catatan_revisi,
                        ];
                    })->all();
                }
            }
        } catch (\Throwable) {}

        return [];
    }

    protected function getMappedLaporanSections(string $userId): array
    {
        $defaultBabs = [
            1 => 'BAB I — Pendahuluan (Latar Belakang & Tujuan PKL)',
            2 => 'BAB II — Gambaran Umum Perusahaan & Unit Kerja',
            3 => 'BAB III — Pelaksanaan Praktik Kerja Lapangan & Kegiatan',
            4 => 'BAB IV — Hasil, Pembahasan, & Analisis Pekerjaan',
            5 => 'BAB V — Penutup (Kesimpulan, Saran, & Lampiran)',
        ];

        try {
            if (Schema::hasTable('pkl_laporan_akhir')) {
                $dbLaporans = PklLaporanAkhir::where('siswa_id', $userId)
                    ->orderBy('nomor_bab')
                    ->get()
                    ->keyBy('nomor_bab');

                $sections = [];
                for ($i = 1; $i <= 5; $i++) {
                    if ($dbLaporans->has($i)) {
                        $l = $dbLaporans->get($i);
                        $sections[] = [
                            'id' => $l->nomor_bab,
                            'title' => $l->judul_bab ?: $defaultBabs[$i],
                            'status' => $l->status,
                            'note' => $l->catatan_pembimbing ?? 'Belum ada catatan pembimbing',
                            'updated' => $l->terakhir_diperbarui ? $l->terakhir_diperbarui->format('d M Y') : ($l->updated_at ? $l->updated_at->format('d M Y') : 'Baru saja'),
                        ];
                    } else {
                        $sections[] = [
                            'id' => $i,
                            'title' => $defaultBabs[$i],
                            'status' => 'Belum',
                            'note' => 'Draf bab belum diunggah',
                            'updated' => '-',
                        ];
                    }
                }
                return $sections;
            }
        } catch (\Throwable) {}

        $fallbackSections = [];
        for ($i = 1; $i <= 5; $i++) {
            $fallbackSections[] = [
                'id' => $i,
                'title' => $defaultBabs[$i],
                'status' => 'Belum',
                'note' => 'Draf bab belum diunggah',
                'updated' => '-',
            ];
        }
        return $fallbackSections;
    }

    protected function getMappedNotifications(string $userId, string $role): array
    {
        try {
            if (Schema::hasTable('notifikasi')) {
                $dbNotifs = Notifikasi::forRecipient($userId, $role)->latest('created_at')->take(8)->get();
                if ($dbNotifs->isNotEmpty()) {
                    return $dbNotifs->map(function ($n) {
                        return [
                            'id' => $n->id,
                            'title' => $n->judul,
                            'message' => $n->deskripsi,
                            'desc' => $n->deskripsi,
                            'type' => $n->tipe_notifikasi,
                            'time' => $n->created_at ? $n->created_at->diffForHumans() : 'Baru saja',
                            'is_read' => $n->is_read,
                            'unread' => !$n->is_read,
                            'accent' => $n->is_accent,
                            'url' => $n->url_action ?? '#',
                        ];
                    })->all();
                }
            }
        } catch (\Throwable) {}

        return [];
    }

    /**
     * Resolve data auth pengguna dengan fallback dummy data yang realistis.
     */
    protected function resolveAuthUser(Request $request): array
    {
        $authUser = $request->attributes->get('auth_user') ?? $request->auth_user ?? $request->input('auth_user') ?? [];

        $role = strtoupper($authUser['role'] ?? 'SISWA');
        if (!in_array($role, ['SISWA', 'ALUMNI'])) {
            $role = 'SISWA';
        }

        if (empty($authUser) || !isset($authUser['id'])) {
            return [
                'id' => ($role === 'SISWA') ? 'usr-siswa-001' : 'usr-alumni-001',
                'username' => ($role === 'SISWA') ? 'siswa.rizky' : 'nadia.salsabila',
                'nama_lengkap' => ($role === 'SISWA') ? 'Ahmad Rizky Pratama' : 'Nadia Salsabila',
                'email' => ($role === 'SISWA') ? 'siswa_rizky@smkpenus.sch.id' : 'nadia.salsabila@gmail.com',
                'no_hp' => ($role === 'SISWA') ? '0812-3456-7890' : '0857-1122-3344',
                'nomor_induk' => ($role === 'SISWA') ? '0061234567' : '1920.08.112',
                'role' => $role,
            ];
        }

        $authUser['role'] = $role;
        return $authUser;
    }
}
