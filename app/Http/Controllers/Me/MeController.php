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
use App\Services\AiCvService;
use App\Services\MeDataService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MeController extends Controller
{
    protected ?ProfilSiswa $currentProfilSiswa = null;

    public function __construct(
        protected MeDataService $dataService,
        protected AiCvService $aiCvService
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
        $primaryCv = null;
        try {
            $dbSiswa = ($this->currentProfilSiswa && (string) $this->currentProfilSiswa->user_id === (string) $userId)
                ? $this->currentProfilSiswa
                : ProfilSiswa::find($userId);

            if ($dbSiswa) {
                $this->currentProfilSiswa = $dbSiswa;
                $profile['nis'] = $dbSiswa->nis ?: $profile['nis'];
                $profile['jurusan'] = $dbSiswa->jurusan ?: $profile['jurusan'];
                $profile['kelas'] = $dbSiswa->kelas ?? $profile['kelas'];
                $profile['completion'] = $dbSiswa->kelengkapan_profil ?: $profile['completion'];
                $profile['status_aktivitas'] = $dbSiswa->status_aktivitas ?? $profile['status'];

                $dbSiswa->loadMissing('primaryResume');
                $primaryCv = $dbSiswa->primaryResume;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        $resolvedCv = $this->resolveActiveCvAndScore($userId, $role, $profile, $primaryCv);
        $cvScore = $resolvedCv['score'];

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

        $profile = $this->enrichProfileFromDatabase($this->dataService->getProfile($authUser), $userId);
        $notifications = $this->getMappedNotifications($userId, $role) ?: $this->dataService->getNotifications($userId);
        $activeCv = null;
        $cvVersions = collect();

        try {
            $cvVersions = CvResume::where('user_id', $userId)
                ->where('temp', false)
                ->select(['id', 'user_id', 'created_at', 'is_main', 'score', 'checksum'])
                ->latest('id')
                ->get();

            $activeHeader = $cvVersions->firstWhere('is_main', true) ?? $cvVersions->first();
            if ($activeHeader) {
                $activeCv = CvResume::find($activeHeader->id);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        $resolved = $this->resolveActiveCvAndScore($userId, $role, $profile, $activeCv);
        $cvScore = $resolved['score'];
        $cvFeedback = $resolved['feedback'];
        $cvMarkdown = $resolved['markdown'];
        $checksum = $resolved['checksum'];
        $activeCv = $resolved['activeCv'];

        $suggestions = (!empty($cvFeedback['suggestions']) && is_array($cvFeedback['suggestions']))
            ? $cvFeedback['suggestions']
            : $this->aiCvService->computeContextualSuggestions($cvMarkdown);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'score' => $cvScore,
                    'feedback' => $cvFeedback,
                    'suggestions' => $suggestions,
                    'markdown' => $cvMarkdown,
                    'raw_markdown' => $cvMarkdown,
                    'checksum' => $checksum,
                    'is_main' => (bool) ($activeCv?->is_main ?? true),
                    'active_cv' => $activeCv,
                    'versions' => $cvVersions,
                ],
            ]);
        }

        return view('me.pages.cv', compact(
            'authUser',
            'profile',
            'cvScore',
            'cvFeedback',
            'suggestions',
            'cvMarkdown',
            'notifications',
            'activeCv',
            'cvVersions',
            'checksum'
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

        $profile = $this->enrichProfileFromDatabase($this->dataService->getProfile($authUser), $userId);
        $notifications = $this->getMappedNotifications($userId, $role) ?: $this->dataService->getNotifications($userId);
        $activeCv = null;
        $cvVersions = collect();

        try {
            $cvVersions = CvResume::where('user_id', $userId)
                ->where('temp', false)
                ->select(['id', 'user_id', 'created_at', 'is_main', 'score', 'checksum'])
                ->latest('id')
                ->get();

            $activeHeader = $cvVersions->firstWhere('is_main', true) ?? $cvVersions->first();
            if ($activeHeader) {
                $activeCv = CvResume::find($activeHeader->id);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        $resolved = $this->resolveActiveCvAndScore($userId, $role, $profile, $activeCv);
        $cvScore = $resolved['score'];
        $cvFeedback = $resolved['feedback'];
        $cvMarkdown = $resolved['markdown'];
        $checksum = $resolved['checksum'];
        $activeCv = $resolved['activeCv'];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'score' => $cvScore,
                    'markdown' => $cvMarkdown,
                    'raw_markdown' => $cvMarkdown,
                    'checksum' => $checksum,
                    'is_main' => (bool) ($activeCv?->is_main ?? true),
                    'feedback' => $cvFeedback,
                    'active_cv' => $activeCv,
                    'versions' => $cvVersions,
                ],
            ]);
        }

        return view('me.pages.cv-edit', compact(
            'authUser',
            'profile',
            'cvScore',
            'cvFeedback',
            'cvMarkdown',
            'notifications',
            'activeCv',
            'cvVersions',
            'checksum'
        ));
    }

    /**
     * Editor CV Versi Spesifik (/bkk/me/cv/{id}/edit)
     */
    public function cvEditVersion(Request $request, int|string $id): View|RedirectResponse|JsonResponse
    {
        $authUser = $this->resolveAuthUser($request);
        $userId = $authUser['id'];
        $role = $authUser['role'];

        $targetCv = null;
        try {
            $targetCv = CvResume::where('user_id', $userId)
                ->where('temp', false)
                ->where('id', $id)
                ->first();
        } catch (\Throwable $e) {
            report($e);
        }

        if (!$targetCv) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Versi CV tidak ditemukan atau bukan milik Anda.'], 404);
            }
            return redirect()->route('bkk.me.cv.edit')->with('error', 'Versi CV arsip yang dipilih tidak ditemukan.');
        }

        $profile = $this->enrichProfileFromDatabase($this->dataService->getProfile($authUser), $userId);
        $notifications = $this->getMappedNotifications($userId, $role) ?: $this->dataService->getNotifications($userId);
        $cvVersions = collect();

        try {
            $cvVersions = CvResume::where('user_id', $userId)
                ->where('temp', false)
                ->select(['id', 'user_id', 'created_at', 'is_main', 'score', 'checksum'])
                ->latest('id')
                ->get();
        } catch (\Throwable $e) {
            report($e);
        }

        $resolved = $this->resolveActiveCvAndScore($userId, $role, $profile, $targetCv);
        $cvScore = $resolved['score'];
        $cvFeedback = $resolved['feedback'];
        $cvMarkdown = $resolved['markdown'];
        $checksum = $resolved['checksum'];
        $activeCv = $resolved['activeCv'] ?? $targetCv;

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $targetCv->id,
                    'score' => $cvScore,
                    'markdown' => $cvMarkdown,
                    'raw_markdown' => $cvMarkdown,
                    'checksum' => $checksum,
                    'is_main' => (bool) $targetCv->is_main,
                    'feedback' => $cvFeedback,
                    'active_cv' => $activeCv,
                    'versions' => $cvVersions,
                ],
            ]);
        }

        return view('me.pages.cv-edit', compact(
            'authUser',
            'profile',
            'cvScore',
            'cvFeedback',
            'cvMarkdown',
            'notifications',
            'activeCv',
            'cvVersions',
            'checksum'
        ));
    }

    /**
     * Update CV Markdown (POST /bkk/me/cv)
     * Mendukung penyimpanan draf sementara (temp = true) maupun versi resmi (temp = false).
     */
    public function cvUpdate(Request $request): RedirectResponse|JsonResponse
    {
        $authUser = $this->resolveAuthUser($request);
        $userId = $authUser['id'];
        $role = $authUser['role'] ?? 'SISWA';

        $validated = $request->validate([
            'raw_markdown' => ['nullable', 'string'],
            'markdown' => ['nullable', 'string'],
            'temp' => ['nullable', 'boolean'],
        ]);
        $markdown = $validated['raw_markdown'] ?? $validated['markdown'] ?? '';
        $isTemp = (bool) $request->boolean('temp', false);

        if (trim($markdown) === '') {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Konten Markdown CV tidak boleh kosong.'], 422);
            }
            return back()->withInput()->with('error', 'Konten Markdown CV tidak boleh kosong.');
        }

        $cv = null;
        try {
            if ($isTemp) {
                $cv = CvResume::saveDraft($userId, $markdown);
            } else {
                $cv = CvResume::saveVersion($userId, $markdown);
                $bearerToken = $request->attributes->get('auth_token')
                    ?: ($request->bearerToken()
                    ?: ($request->cookie('access_token')
                    ?: null));

                // Picu evaluasi /api/ai/cv/score di background, update cv_resumes.feedback & cv_resumes.score, dan kirim notifikasi
                if ($cv) {
                    $cvId = $cv->id;
                    dispatch(function () use ($cvId, $role, $bearerToken) {
                        $targetCv = CvResume::find($cvId);
                        if ($targetCv && !$targetCv->temp) {
                            app(AiCvService::class)->evaluateAndStoreScore(
                                cv: $targetCv,
                                recipientRole: $role,
                                token: is_string($bearerToken) ? $bearerToken : null,
                                sendNotification: true
                            );
                        }
                    })->afterResponse();
                }
            }
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
            }
            return back()->withInput()->with('error', 'Gagal menyimpan CV: ' . $e->getMessage());
        }

        if ($request->wantsJson()) {
            $freshCv = $cv?->fresh() ?? $cv;
            return response()->json([
                'success' => true,
                'message' => $isTemp
                    ? 'Draf sementara CV berhasil disiapkan untuk analisis AI.'
                    : 'Konten CV ATS berhasil diperbarui!',
                'data' => $freshCv ? [
                    'id' => $freshCv->id,
                    'user_id' => $freshCv->user_id,
                    'checksum' => $freshCv->checksum,
                    'is_main' => (bool) $freshCv->is_main,
                    'temp' => (bool) $freshCv->temp,
                    'score' => $freshCv->score,
                    'feedback' => $freshCv->feedback,
                    'raw_markdown' => $freshCv->raw_markdown,
                    'created_at' => $freshCv->created_at ? $freshCv->created_at->format('d M Y H:i') : null,
                ] : null,
            ]);
        }

        return redirect()->route('bkk.me.cv')->with('success', 'CV berhasil disimpan!');
    }

    /**
     * Aktifkan Versi CV Tertentu Sebagai CV Utama (POST /bkk/me/cv/{id}/main)
     */
    public function cvSetMain(Request $request, int|string $id): RedirectResponse|JsonResponse
    {
        $authUser = $this->resolveAuthUser($request);
        $userId = $authUser['id'];
        $role = $authUser['role'] ?? 'SISWA';

        $cv = CvResume::where('user_id', $userId)->where('temp', false)->findOrFail($id);
        $cv->setAsMain();

        if (empty($cv->feedback) || $cv->score === null) {
            $cvId = $cv->id;
            $bearerToken = $request->attributes->get('auth_token')
                ?: ($request->bearerToken()
                ?: ($request->cookie('access_token')
                ?: null));
            dispatch(function () use ($cvId, $role, $bearerToken) {
                $targetCv = CvResume::find($cvId);
                if ($targetCv) {
                    app(AiCvService::class)->evaluateAndStoreScore(
                        cv: $targetCv,
                        recipientRole: $role,
                        token: is_string($bearerToken) ? $bearerToken : null,
                        sendNotification: true
                    );
                }
            })->afterResponse();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Versi CV berhasil diaktifkan sebagai CV Utama!',
                'data' => [
                    'id' => $cv->id,
                    'checksum' => $cv->checksum,
                    'is_main' => $cv->is_main,
                    'temp' => $cv->temp,
                    'feedback' => $cv->feedback,
                ],
            ]);
        }

        return back()->with('success', 'Versi CV berhasil diaktifkan sebagai CV Utama!');
    }

    /**
     * Hapus versi CV arsip milik siswa (DELETE /bkk/me/cv/{id}).
     * CV Utama tidak boleh dihapus supaya siswa selalu punya CV aktif, dan versi yang sudah dipakai melamar juga
     * ditolak karena lamaran.cv_id bersifat cascadeOnDelete (lamarannya ikut terhapus).
     */
    public function cvDestroy(Request $request, int|string $id): RedirectResponse|JsonResponse
    {
        $authUser = $this->resolveAuthUser($request);
        $cv = CvResume::where('user_id', $authUser['id'])->where('temp', false)->findOrFail($id);

        $refusal = match (true) {
            (bool) $cv->is_main => 'CV Utama tidak bisa dihapus. Jadikan versi lain sebagai CV Utama terlebih dahulu.',
            $cv->lamarans()->exists() => 'Versi CV ini sudah dipakai untuk melamar, jadi tidak bisa dihapus.',
            default => null,
        };

        if ($refusal) {
            return $request->wantsJson()
                ? response()->json(['success' => false, 'message' => $refusal], 422)
                : back()->with('error', $refusal);
        }

        $cv->delete();
        $message = "Versi CV #{$id} berhasil dihapus.";

        return $request->wantsJson()
            ? response()->json(['success' => true, 'message' => $message])
            : redirect()->route('bkk.me.cv')->with('success', $message);
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
            $penempatan = PenempatanPkl::with('mitra')
                ->where('siswa_id', $userId)
                ->where('status', 'BERJALAN')
                ->first();
        } catch (\Throwable $e) {
            report($e);
        }

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

        if ($authUser['role'] !== 'SISWA') {
            $msg = 'Jurnal PKL hanya dapat diisi oleh siswa aktif.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 403);
            }
            return redirect()->route('bkk.me.jurnal')->with('error', $msg);
        }

        $validated = $request->validate([
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'aktivitas' => ['required', 'string'],
            'durasi_jam' => ['nullable', 'integer', 'min:1', 'max:12'],
        ], [
            'tanggal.before_or_equal' => 'Tanggal jurnal tidak boleh melebihi hari ini.',
        ]);

        $createdEntry = null;
        try {
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

            $sudahAda = PklJurnalHarian::where('penempatan_pkl_id', $penempatan->id)
                ->whereDate('tanggal', $validated['tanggal'])
                ->exists();

            if ($sudahAda) {
                $msg = 'Jurnal untuk tanggal tersebut sudah pernah dicatat.';
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return redirect()->route('bkk.me.jurnal')->with('error', $msg);
            }

            $createdEntry = PklJurnalHarian::create([
                'penempatan_pkl_id' => $penempatan->id,
                'siswa_id' => $userId,
                'tanggal' => $validated['tanggal'],
                'aktivitas' => $validated['aktivitas'],
                'durasi_jam' => $validated['durasi_jam'] ?? 8,
                'status' => 'Menunggu',
            ]);
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
            $penempatan = PenempatanPkl::with('mitra')
                ->where('siswa_id', $userId)
                ->where('status', 'BERJALAN')
                ->first();
        } catch (\Throwable $e) {
            report($e);
        }

        $deadlineStr = $penempatan?->tanggal_selesai ? $penempatan->tanggal_selesai->locale('id')->translatedFormat('d F Y') : 'Belum ditentukan';

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
            'judul_bab' => ['required', 'string', 'max:255'],
            'file_draft' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:10240'], // 10 MB
        ], [
            'file_draft.required' => 'Pilih berkas draf bab terlebih dahulu.',
            'file_draft.mimes' => 'Format berkas harus .pdf, .doc, atau .docx.',
            'file_draft.max' => 'Ukuran berkas maksimal 10 MB.',
        ]);

        $laporan = null;
        try {
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

            $existing = PklLaporanAkhir::where('penempatan_pkl_id', $penempatan->id)
                ->where('nomor_bab', $validated['nomor_bab'])
                ->first();

            if ($existing && $existing->status === 'Disetujui') {
                $msg = 'Bab ini sudah disetujui pembimbing dan tidak dapat diunggah ulang.';
                if ($request->wantsJson()) {
                    return response()->json(['success' => false, 'message' => $msg], 422);
                }
                return redirect()->route('bkk.me.laporan')->with('error', $msg);
            }

            $file = $request->file('file_draft');
            $fileName = 'bab' . $validated['nomor_bab'] . '_' . $penempatan->id . '_' . Str::random(24) . '.' . ($file->guessExtension() ?: $file->getClientOriginalExtension());
            $path = $file->storeAs('laporan_pkl', $fileName, 'public');

            $laporan = PklLaporanAkhir::updateOrCreate(
                [
                    'penempatan_pkl_id' => $penempatan->id,
                    'nomor_bab' => $validated['nomor_bab'],
                ],
                [
                    'siswa_id' => $userId,
                    'judul_bab' => $validated['judul_bab'],
                    'file_draft_url' => '/bkk/storage/' . $path,
                    'status' => 'Ditinjau',
                    'terakhir_diperbarui' => now()->toDateString(),
                ]
            );
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

        $lowongan = Lowongan::where(function ($q) use ($id_lowongan) {
                $q->where('slug', $id_lowongan);
                if (is_numeric($id_lowongan)) {
                    $q->orWhere('id', (int) $id_lowongan);
                }
            })
            ->firstOrFail();

        $tolak = null;
        if ($lowongan->status !== 'Aktif') {
            $tolak = 'Lowongan ini sudah tidak menerima lamaran.';
        } elseif ($lowongan->deadline && $lowongan->deadline->lt(today())) {
            $tolak = 'Batas akhir pendaftaran lowongan ini sudah lewat.';
        }

        $cv = CvResume::where('user_id', $userId)->where('temp', false)->where('is_main', true)->first();
        if (!$tolak && !$cv) {
            $tolak = 'Lengkapi CV Anda terlebih dahulu sebelum melamar.';
        }

        if ($tolak) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $tolak], 422);
            }
            // Form lamaran ada di halaman publik yang tidak menampilkan flash error, jadi arahkan ke portal siswa
            return redirect()->route($cv ? 'bkk.me.lamaran' : 'bkk.me.cv.edit')->with('error', $tolak);
        }

        $existing = Lamaran::where('siswa_id', $userId)
            ->where('lowongan_id', $lowongan->id)
            ->first();

        if ($existing) {
            $msg = 'Anda sudah pernah mengajukan lamaran pada lowongan ini.';
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return redirect()->route('bkk.me.lamaran')->with('error', $msg);
        }

        $kodeLamaran = 'LMR-' . date('Y') . '-' . strtoupper(substr(uniqid(), -5));

        $lamaran = Lamaran::create([
            'kode_lamaran' => $kodeLamaran,
            'lowongan_id' => $lowongan->id,
            'siswa_id' => $userId,
            'cv_id' => $cv->id,
            'tanggal_melamar' => now()->toDateString(),
            'skor_match_ai' => 90,
            'status' => 'Terkirim',
            'step_tahapan' => 1,
            'catatan_seleksi' => 'Berkas pendaftaran otomatis diverifikasi sistem.',
        ]);

        LamaranRiwayatStatus::create([
            'lamaran_id' => $lamaran->id,
            'judul_tahapan' => 'Lamaran Terkirim',
            'deskripsi' => 'Pendaftaran diajukan melalui portal BKK Penus.',
            'diubah_oleh_id' => $userId,
            'diubah_oleh_role' => $authUser['role'],
        ]);

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
            $dbLamarans = Lamaran::with(['lowongan.mitra', 'interview', 'riwayatStatus'])
                ->where('siswa_id', $userId)
                ->latest('tanggal_melamar')
                ->get();

            if ($dbLamarans->isNotEmpty()) {
                return $dbLamarans->map(function ($l) {
                    $lowongan = $l->lowongan;
                    $mitra = $lowongan?->mitra;
                    $interview = $l->interview;
                    $tgl = $l->tanggal_melamar ? $l->tanggal_melamar->format('d M Y') : ($l->created_at ? $l->created_at->format('d M Y') : date('d M Y'));

                    $timeline = [];
                    if ($l->relationLoaded('riwayatStatus') && $l->riwayatStatus->isNotEmpty()) {
                        $timeline = $l->riwayatStatus->sortBy('created_at')->map(function ($r) {
                            return [
                                'date' => $r->created_at ? $r->created_at->format('d M Y, H:i') : '',
                                'title' => $r->judul_tahapan,
                                'desc' => $r->deskripsi ?? '',
                                'done' => true,
                            ];
                        })->values()->all();
                    } else {
                        $timeline = [
                            ['date' => $tgl, 'title' => 'Lamaran Dikirim', 'desc' => 'Berkas dan CV diajukan ke sistem BKK.', 'done' => true],
                            ['date' => '', 'title' => 'Verifikasi Berkas BKK', 'desc' => 'BKK Penus memvalidasi kualifikasi.', 'done' => in_array($l->status, ['Sedang Ditinjau', 'Dipanggil Interview', 'Diterima', 'Ditolak'])],
                            ['date' => '', 'title' => 'Tahap Seleksi & Interview', 'desc' => 'Ulasan oleh mitra industri atau pemanggilan interview.', 'done' => in_array($l->status, ['Dipanggil Interview', 'Diterima'])],
                            ['date' => '', 'title' => 'Pengumuman Hasil', 'desc' => 'Status akhir penerimaan.', 'done' => in_array($l->status, ['Diterima', 'Ditolak'])],
                        ];
                    }

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
                        'timeline' => $timeline,
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
        } catch (\Throwable $e) {
            report($e);
        }

        return [];
    }

    protected function getMappedVacancies(string $role): array
    {
        try {
            $tipeTarget = ($role === 'SISWA') ? 'PKL' : 'Kerja';
            $dbLowongans = Lowongan::with('mitra')
                ->terbuka()
                ->where('tipe', $tipeTarget)
                ->latest('created_at')
                ->take(6)
                ->get();

            if ($dbLowongans->isEmpty()) {
                $dbLowongans = Lowongan::with('mitra')
                    ->terbuka()
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
        } catch (\Throwable $e) {
            report($e);
        }

        return [];
    }

    protected function getMappedJurnalEntries(string $userId): array
    {
        try {
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
        } catch (\Throwable $e) {
            report($e);
        }

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
        } catch (\Throwable $e) {
            report($e);
        }

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
        } catch (\Throwable $e) {
            report($e);
        }

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
        $this->currentProfilSiswa = $this->ensureProfilSiswa($authUser);
        $request->attributes->set('profil_siswa_model', $this->currentProfilSiswa);

        return $authUser;
    }

    protected function enrichProfileFromDatabase(array $profile, string $userId): array
    {
        try {
            $dbSiswa = ($this->currentProfilSiswa && (string) $this->currentProfilSiswa->user_id === (string) $userId)
                ? $this->currentProfilSiswa
                : ProfilSiswa::find($userId);

            if ($dbSiswa) {
                $this->currentProfilSiswa = $dbSiswa;
                $profile['nis'] = $dbSiswa->nis ?: $profile['nis'];
                if (!empty($dbSiswa->jurusan) && $dbSiswa->jurusan !== 'Belum diisi') {
                    $profile['jurusan'] = $dbSiswa->jurusan;
                }
                $profile['kelas'] = $dbSiswa->kelas ?? $profile['kelas'];
                $profile['completion'] = $dbSiswa->kelengkapan_profil ?: $profile['completion'];
                $profile['status_aktivitas'] = $dbSiswa->status_aktivitas ?? $profile['status'];
            }
        } catch (\Throwable $e) {
            report($e);
        }

        if (empty($profile['jurusan']) || $profile['jurusan'] === '-' || $profile['jurusan'] === 'Belum diisi') {
            $profile['jurusan'] = ($profile['role'] ?? 'SISWA') === 'SISWA'
                ? 'Rekayasa Perangkat Lunak'
                : 'Teknik Komputer dan Jaringan';
        }

        return $profile;
    }

    /**
     * Buat atau ambil record profil_siswa minimal untuk user dari auth service yang belum punya profil,
     * karena cv_resumes, lamaran, dan penempatan_pkl ber-FK ke profil_siswa.user_id.
     */
    protected function ensureProfilSiswa(array $authUser): ?ProfilSiswa
    {
        $userId = (string) ($authUser['id'] ?? '');
        if ($userId === '') {
            return null;
        }

        if ($this->currentProfilSiswa && (string) $this->currentProfilSiswa->user_id === $userId) {
            return $this->currentProfilSiswa;
        }

        try {
            return $this->currentProfilSiswa = ProfilSiswa::firstOrCreate(
                ['user_id' => $userId],
                [
                    'nis' => $authUser['nomor_induk'] ?? $authUser['nis'] ?? '-',
                    'jurusan' => $authUser['jurusan'] ?? 'Belum diisi',
                    'kelas' => $authUser['kelas'] ?? null,
                    'angkatan' => (string) ($authUser['angkatan'] ?? date('Y')),
                    'status_kelulusan' => ($authUser['role'] ?? '') === 'ALUMNI' ? 'ALUMNI' : 'SISWA_AKTIF',
                ]
            );
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
    }

    /**
     * Resolusi terpusat untuk CV Aktif, skor ATS, feedback, dan parameter.
     * Digunakan bersama oleh dashboard() dan cv() untuk menjamin konsistensi 100%.
     */
    protected function resolveActiveCvAndScore(string $userId, string $role, array $profile = [], ?CvResume $preferredCv = null): array
    {
        $cvScore = $this->dataService->getCvScore($role, $userId);
        $activeCv = $preferredCv;

        if (!$activeCv) {
            try {
                $cvVersions = CvResume::where('user_id', $userId)
                    ->where('temp', false)
                    ->select(['id', 'user_id', 'created_at', 'is_main', 'score', 'checksum'])
                    ->latest('id')
                    ->get();

                $activeHeader = $cvVersions->firstWhere('is_main', true) ?? $cvVersions->first();
                if ($activeHeader) {
                    $activeCv = CvResume::find($activeHeader->id);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $cvMarkdown = (!empty($activeCv?->raw_markdown))
            ? $activeCv->raw_markdown
            : $this->dataService->getCvMarkdown($role, $profile, $userId);

        $checksum = $activeCv?->checksum ?? CvResume::generateChecksum($cvMarkdown);

        $cvFeedback = ($activeCv && is_array($activeCv->feedback) && !empty($activeCv->feedback))
            ? $activeCv->feedback
            : $this->aiCvService->computeHeuristicScore($cvMarkdown, $checksum);

        $resolvedCvScore = $activeCv?->score !== null
            ? (int) $activeCv->score
            : (isset($cvFeedback['total_score']) ? (int) $cvFeedback['total_score'] : (int) ($cvScore['total'] ?? 85));

        $cvScore['total'] = $resolvedCvScore;

        if (!empty($cvFeedback['parameters']) && is_array($cvFeedback['parameters'])) {
            $cvScore['params'] = array_map(fn ($p) => [
                'id' => $p['id'] ?? '',
                'label' => $p['label'] ?? 'Parameter ATS',
                'value' => (int) ($p['score'] ?? $p['value'] ?? 85),
                'score' => (int) ($p['score'] ?? $p['value'] ?? 85),
                'max_score' => (int) ($p['max_score'] ?? 100),
                'status' => $p['status'] ?? 'GOOD',
                'weight' => $p['weight'] ?? 0.25,
                'feedback' => $p['feedback'] ?? '',
            ], $cvFeedback['parameters']);
        }

        return [
            'score' => $cvScore,
            'feedback' => $cvFeedback,
            'activeCv' => $activeCv,
            'markdown' => $cvMarkdown,
            'checksum' => $checksum,
        ];
    }

    /**
     * Upload Dokumen Berkas CV Siswa (PDF / DOCX) (POST /bkk/me/cv/upload)
     */
    public function cvUploadDocument(Request $request): RedirectResponse|JsonResponse
    {
        $authUser = $this->resolveAuthUser($request);
        $userId = $authUser['id'];

        $request->validate([
            'cv_file' => ['required', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ], [
            'cv_file.required' => 'Pilih berkas file CV yang akan diunggah.',
            'cv_file.file' => 'Berkas yang dipilih tidak valid.',
            'cv_file.mimes' => 'Format file CV harus bertipe PDF, DOC, atau DOCX.',
            'cv_file.max' => 'Ukuran maksimal berkas CV adalah 5 MB.',
        ]);

        $file = $request->file('cv_file');
        $fileName = $file->getClientOriginalName();
        $fileSize = $file->getSize();
        $fileMime = $file->getClientMimeType() ?: $file->getMimeType();

        // Simpan ke storage/app/public/cv_uploads
        $savedPath = $file->store('cv_uploads', 'public');

        CvResume::ensureProfilSiswaExists($userId);

        $activeCv = CvResume::where('user_id', $userId)
            ->where('temp', false)
            ->where('is_main', true)
            ->first();

        if (!$activeCv) {
            $activeCv = CvResume::where('user_id', $userId)
                ->where('temp', false)
                ->latest('id')
                ->first();
        }

        // Hapus file lama jika ada
        if ($activeCv && !empty($activeCv->file_path)) {
            $oldPath = str_replace(['storage/', 'public/'], '', $activeCv->file_path);
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($oldPath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
            }
        }

        if ($activeCv) {
            $activeCv->update([
                'file_path' => 'storage/' . $savedPath,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'file_mime' => $fileMime,
                'status_review_guru' => 'Belum Direview',
            ]);
        } else {
            $defaultMarkdown = $this->dataService->getCvMarkdown($authUser['role'] ?? 'SISWA', $this->dataService->getProfile($authUser), $userId);
            $activeCv = CvResume::create([
                'user_id' => $userId,
                'raw_markdown' => $defaultMarkdown,
                'is_main' => true,
                'temp' => false,
                'checksum' => CvResume::generateChecksum($defaultMarkdown),
                'file_path' => 'storage/' . $savedPath,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'file_mime' => $fileMime,
                'status_review_guru' => 'Belum Direview',
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Berkas CV berhasil diunggah dan siap ditinjau Guru Pembimbing.',
                'data' => [
                    'file_name' => $fileName,
                    'file_size_formatted' => $activeCv->file_size_formatted,
                    'file_url' => $activeCv->file_url,
                    'status_review_guru' => $activeCv->status_review_guru,
                ],
            ]);
        }

        return redirect()->route('bkk.me.cv')->with('success', "Berkas CV '{$fileName}' berhasil diunggah dan siap ditinjau Guru Pembimbing!");
    }

    /**
     * Hapus berkas CV yang diunggah (POST /bkk/me/cv/delete-file)
     */
    public function cvDeleteDocument(Request $request): RedirectResponse|JsonResponse
    {
        $authUser = $this->resolveAuthUser($request);
        $userId = $authUser['id'];

        $activeCv = CvResume::where('user_id', $userId)
            ->where('temp', false)
            ->whereNotNull('file_path')
            ->latest('id')
            ->first();

        if ($activeCv && !empty($activeCv->file_path)) {
            $oldPath = str_replace(['storage/', 'public/'], '', $activeCv->file_path);
            if (\Illuminate\Support\Facades\Storage::disk('public')->exists($oldPath)) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
            }
            $activeCv->update([
                'file_path' => null,
                'file_name' => null,
                'file_size' => null,
                'file_mime' => null,
            ]);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Berkas dokumen CV berhasil dihapus.',
            ]);
        }

        return redirect()->route('bkk.me.cv')->with('success', 'Berkas dokumen CV berhasil dihapus.');
    }

    /**
     * Halaman Status & Pengajuan PKL Siswa (/bkk/me/pkl)
     */
    public function pklStatus(Request $request): View|JsonResponse
    {
        $authUser = $this->resolveAuthUser($request);
        $userId = $authUser['id'];
        $role = $authUser['role'];

        $profile = $this->enrichProfileFromDatabase($this->dataService->getProfile($authUser), $userId);
        $notifications = $this->getMappedNotifications($userId, $role) ?: $this->dataService->getNotifications($userId);

        $penempatans = PenempatanPkl::with(['mitra', 'lowongan'])
            ->where('siswa_id', $userId)
            ->latest()
            ->get();

        $activePkl = $penempatans->firstWhere('status', 'BERJALAN')
            ?? $penempatans->firstWhere('status', 'MENUNGGU_PERSETUJUAN')
            ?? $penempatans->first();

        // Cari CV aktif siswa
        $activeCv = CvResume::where('user_id', $userId)
            ->where('temp', false)
            ->latest('id')
            ->first();

        // Ambil daftar mitra untuk pilihan pengajuan
        $mitras = \App\Models\Mitra::where('is_verified', true)->orderBy('nama_perusahaan')->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => [
                    'active_pkl' => $activePkl,
                    'penempatans' => $penempatans,
                    'cv' => $activeCv,
                ],
            ]);
        }

        return view('me.pages.pkl', compact(
            'authUser',
            'profile',
            'notifications',
            'penempatans',
            'activePkl',
            'activeCv',
            'mitras'
        ));
    }

    /**
     * Kirim Formulir Pengajuan PKL Mandiri Siswa (POST /bkk/me/pkl/pengajuan)
     */
    public function storePengajuanPkl(Request $request): RedirectResponse|JsonResponse
    {
        $authUser = $this->resolveAuthUser($request);
        $userId = $authUser['id'];

        // Cek apakah siswa sudah punya PKL aktif
        $existing = PenempatanPkl::where('siswa_id', $userId)
            ->whereIn('status', ['BERJALAN', 'MENUNGGU_PERSETUJUAN'])
            ->first();

        if ($existing) {
            $msg = ($existing->status === 'BERJALAN')
                ? 'Anda sudah memiliki penempatan PKL aktif yang sedang berjalan.'
                : 'Pengajuan PKL Anda sebelumnya masih menunggu persetujuan Guru Pembimbing.';

            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            return back()->with('error', $msg);
        }

        $validated = $request->validate([
            'tipe_mitra' => ['required', 'in:TERDAFTAR,MANDIRI'],
            'mitra_id' => ['nullable', 'required_if:tipe_mitra,TERDAFTAR', 'exists:mitra,id'],
            'nama_perusahaan_mandiri' => ['nullable', 'required_if:tipe_mitra,MANDIRI', 'string', 'max:200'],
            'alamat_mandiri' => ['nullable', 'string'],
            'kota_mandiri' => ['nullable', 'string', 'max:100'],
            'unit_kerja_divisi' => ['required', 'string', 'max:150'],
            'pembimbing_industri_nama' => ['nullable', 'string', 'max:150'],
            'tanggal_mulai' => ['required', 'date'],
            'tanggal_selesai' => ['required', 'date', 'after:tanggal_mulai'],
            'catatan_siswa' => ['nullable', 'string', 'max:1000'],
        ], [
            'tipe_mitra.required' => 'Pilih jenis mitra PKL (Perusahaan Terafiliasi atau Tempat Baru).',
            'mitra_id.required_if' => 'Pilih perusahaan mitra terafiliasi sekolah.',
            'nama_perusahaan_mandiri.required_if' => 'Tuliskan nama perusahaan tempat PKL mandiri.',
            'unit_kerja_divisi.required' => 'Divisi atau bidang penempatan kerja wajib diisi.',
            'tanggal_mulai.required' => 'Tanggal mulai PKL wajib ditentukan.',
            'tanggal_selesai.required' => 'Tanggal selesai PKL wajib ditentukan.',
            'tanggal_selesai.after' => 'Tanggal selesai harus setelah tanggal mulai.',
        ]);

        $mitraId = null;
        if ($validated['tipe_mitra'] === 'TERDAFTAR') {
            $mitraId = $validated['mitra_id'];
        } else {
            // Buat mitra baru kategori mandiri
            $createdMitra = \App\Models\Mitra::create([
                'nama_perusahaan' => $validated['nama_perusahaan_mandiri'],
                'npwp' => 'MDR-' . strtoupper(Str::random(8)),
                'password' => bcrypt(Str::random(16)),
                'sektor_industri' => 'Mitra Mandiri Rekomendasi Siswa',
                'alamat_kantor' => $validated['alamat_mandiri'] ?? 'Alamat Mitra Mandiri',
                'kota' => $validated['kota_mandiri'] ?? 'Kota',
                'email_perusahaan' => 'pic@' . Str::slug($validated['nama_perusahaan_mandiri']) . '.co.id',
                'no_telp_perusahaan' => '-',
                'pic_name' => $validated['pembimbing_industri_nama'] ?? 'Pembimbing Lapangan',
                'pic_role' => 'Mentor Industri',
                'pic_phone' => '-',
                'pic_email' => 'mentor@mitra.com',
                'is_verified' => false,
                'status_kemitraan' => 'Pengajuan PKL Mandiri Siswa',
            ]);
            $mitraId = $createdMitra->id;
        }

        CvResume::ensureProfilSiswaExists($userId);

        $penempatan = PenempatanPkl::create([
            'siswa_id' => $userId,
            'mitra_id' => $mitraId,
            'guru_pembimbing_id' => 'usr-guru-001',
            'pembimbing_industri_nama' => ($validated['pembimbing_industri_nama'] ?? null) ?: 'Mentor Industri',
            'unit_kerja_divisi' => $validated['unit_kerja_divisi'],
            'tanggal_mulai' => $validated['tanggal_mulai'],
            'tanggal_selesai' => $validated['tanggal_selesai'],
            'target_jam' => 640,
            'total_jam_tercapai' => 0,
            'status' => 'MENUNGGU_PERSETUJUAN',
            'catatan_guru' => !empty($validated['catatan_siswa']) ? "Catatan Siswa: {$validated['catatan_siswa']}" : null,
            'pengajuan_mandiri' => ($validated['tipe_mitra'] === 'MANDIRI'),
        ]);

        // Buat notifikasi untuk admin/guru
        try {
            Notifikasi::create([
                'recipient_id' => 'usr-guru-001',
                'recipient_role' => 'GURU',
                'judul' => 'Pengajuan PKL Siswa Baru',
                'deskripsi' => "Siswa NIS {$authUser['nomor_induk']} mengajukan permohonan PKL mandiri dan menunggu persetujuan (Accept).",
                'tipe_notifikasi' => 'PKL',
                'url_action' => route('bkk.guru.pengajuan.index'),
                'is_read' => false,
                'is_accent' => true,
            ]);
        } catch (\Throwable $e) {
            report($e);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Pengajuan PKL berhasil dikirimkan dan sedang menunggu persetujuan Guru Pembimbing.',
                'data' => $penempatan,
            ]);
        }

        return redirect()->route('bkk.me.pkl')->with('success', 'Pengajuan PKL Anda berhasil dikirimkan dan sedang menunggu persetujuan (Accept) dari Guru Pembimbing.');
    }
}

