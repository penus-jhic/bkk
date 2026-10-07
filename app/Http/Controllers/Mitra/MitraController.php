<?php

namespace App\Http\Controllers\Mitra;

use App\Http\Controllers\Controller;
use App\Models\Lamaran;
use App\Models\LamaranRiwayatStatus;
use App\Models\Lowongan;
use App\Models\Mitra;
use App\Models\PenempatanPkl;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class MitraController extends Controller
{
    /**
     * Dapatkan instance Mitra yang sedang login dari request / session
     */
    protected function getAuthenticatedMitra(Request $request): Mitra
    {
        $mitra = $request->attributes->get('mitra')
            ?? ($request->session()->has('mitra_id') ? Mitra::find($request->session()->get('mitra_id')) : null);

        if (!$mitra) {
            abort(401, 'Autentikasi Mitra diperlukan.');
        }

        return $mitra;
    }

    /**
     * Dashboard Overview Mitra (/bkk/dashboard)
     */
    public function dashboard(Request $request): View
    {
        $mitra = $this->getAuthenticatedMitra($request);

        $lowonganIds = $mitra->lowongans()->pluck('id');

        $metrics = [
            'total_lowongan' => $mitra->lowongans()->count(),
            'lowongan_aktif' => $mitra->lowongans()->where('status', 'Aktif')->count(),
            'total_pelamar' => Lamaran::whereIn('lowongan_id', $lowonganIds)->count(),
            'pelamar_interview' => Lamaran::whereIn('lowongan_id', $lowonganIds)->where('status', 'Dipanggil Interview')->count(),
            'pelamar_diterima' => Lamaran::whereIn('lowongan_id', $lowonganIds)->where('status', 'Diterima')->count(),
            'siswa_aktif_pkl' => PenempatanPkl::where('mitra_id', $mitra->id)->where('status', 'BERJALAN')->count(),
        ];

        // 4 Lowongan aktif / terbaru
        $vacancies = $mitra->lowongans()
            ->withCount('lamaran')
            ->latest('created_at')
            ->limit(4)
            ->get();

        // 5 Pelamar terbaru yang masuk ke lowongan mitra ini
        $recentApplicants = Lamaran::with(['profilSiswa', 'lowongan'])
            ->whereIn('lowongan_id', $lowonganIds)
            ->latest('created_at')
            ->limit(5)
            ->get();

        return view('mitra.pages.dashboard', compact(
            'mitra',
            'metrics',
            'vacancies',
            'recentApplicants'
        ));
    }

    /**
     * Daftar Seluruh Lowongan Mitra (/bkk/dashboard/lowongan)
     */
    public function lowonganIndex(Request $request): View
    {
        $mitra = $this->getAuthenticatedMitra($request);

        $query = $mitra->lowongans()->withCount('lamaran');

        $search = trim((string) $request->query('q', ''));
        $statusFilter = $request->query('status');
        $tipeFilter = $request->query('tipe');

        if (!empty($search)) {
            $like = config('database.default') === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('judul', $like, "%{$search}%")
                  ->orWhere('target_jurusan', $like, "%{$search}%")
                  ->orWhere('lokasi', $like, "%{$search}%");
            });
        }

        if (!empty($statusFilter) && $statusFilter !== 'Semua') {
            $query->where('status', $statusFilter);
        }

        if (!empty($tipeFilter) && $tipeFilter !== 'Semua') {
            $query->where('tipe', $tipeFilter);
        }

        $vacancies = $query->latest('created_at')->paginate(10)->withQueryString();

        return view('mitra.pages.lowongan.index', compact(
            'mitra',
            'vacancies',
            'search',
            'statusFilter',
            'tipeFilter'
        ));
    }

    /**
     * Form Tambah Lowongan Baru (/bkk/dashboard/lowongan/new)
     */
    public function lowonganCreate(Request $request): View
    {
        $mitra = $this->getAuthenticatedMitra($request);
        return view('mitra.pages.lowongan.create', compact('mitra'));
    }

    /**
     * Handler Simpan Lowongan Baru (POST /bkk/dashboard/lowongan)
     */
    public function lowonganStore(Request $request): RedirectResponse
    {
        $mitra = $this->getAuthenticatedMitra($request);

        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'tipe' => ['required', 'in:PKL,Kerja'],
            'jurusan' => ['required', 'string', 'max:150'],
            'lokasi' => ['required', 'string', 'max:150'],
            'kategori' => ['nullable', 'string', 'max:100'],
            'gaji' => ['nullable', 'string', 'max:150'],
            'kuota' => ['required', 'integer', 'min:1'],
            'deadline' => ['required', 'date'],
            'deskripsi' => ['required', 'string'],
            'persyaratan' => ['nullable', 'string'],
            'benefit' => ['nullable', 'string'],
        ], [
            'title.required' => 'Judul posisi lowongan wajib diisi.',
            'tipe.required' => 'Tipe lowongan wajib dipilih (PKL atau Kerja).',
            'jurusan.required' => 'Target jurusan kejuruan wajib diisi.',
            'lokasi.required' => 'Lokasi penempatan kerja wajib diisi.',
            'kuota.required' => 'Kuota pelamar minimal 1 orang.',
            'deadline.required' => 'Batas akhir pendaftaran wajib ditentukan.',
            'deskripsi.required' => 'Deskripsi pekerjaan wajib diisi.',
        ]);

        // Parsing persyaratan & benefit (dipisahkan baris baru)
        $persyaratanArr = array_values(array_filter(array_map('trim', explode("\n", (string) $request->input('persyaratan')))));
        if (empty($persyaratanArr)) {
            $persyaratanArr = ['Siswa/Alumni SMK Plus Pelita Nusantara', 'Komitmen dan disiplin kerja tinggi'];
        }

        $benefitArr = array_values(array_filter(array_map('trim', explode("\n", (string) $request->input('benefit')))));
        if (empty($benefitArr)) {
            $benefitArr = ['Sertifikat & Pengalaman Industri'];
        }

        $title = $request->input('title');
        $tipe = $request->input('tipe');

        $lowongan = Lowongan::create([
            'mitra_id' => $mitra->id,
            'judul' => $title,
            'slug' => Str::slug($title) . '-' . Str::lower(Str::random(5)),
            'tipe' => $tipe,
            'tipe_badge' => ($tipe === 'PKL') ? 'Magang / PKL Siswa' : 'Full-Time Lulusan',
            'target_jurusan' => $request->input('jurusan'),
            'lokasi' => $request->input('lokasi'),
            'kategori_posisi' => $request->input('kategori') ?: 'Teknologi & Operasional',
            'gaji_kompensasi' => $request->input('gaji') ?: 'Kompetitif UMK',
            'kuota' => (int) $request->input('kuota', 1),
            'deadline' => $request->input('deadline'),
            'deskripsi' => $request->input('deskripsi'),
            'persyaratan_json' => $persyaratanArr,
            'benefit_json' => $benefitArr,
            'status' => 'Aktif',
        ]);

        return redirect()
            ->route('bkk.mitra.lowongan.index')
            ->with('success', "Lowongan '{$title}' berhasil dipublikasikan dan langsung dapat dilamar oleh siswa/alumni BKK!");
    }

    /**
     * Form Edit Lowongan (/bkk/dashboard/lowongan/{id_lowongan})
     */
    public function lowonganEdit(Request $request, string $id_lowongan): View
    {
        $mitra = $this->getAuthenticatedMitra($request);

        $vacancy = $mitra->lowongans()
            ->where(function ($q) use ($id_lowongan) {
                if (is_numeric($id_lowongan)) {
                    $q->where('id', (int) $id_lowongan);
                }
                $q->orWhere('slug', $id_lowongan);
            })
            ->firstOrFail();

        return view('mitra.pages.lowongan.edit', compact('mitra', 'vacancy'));
    }

    /**
     * Handler Update Lowongan (PUT /bkk/dashboard/lowongan/{id_lowongan})
     */
    public function lowonganUpdate(Request $request, string $id_lowongan): RedirectResponse
    {
        $mitra = $this->getAuthenticatedMitra($request);

        $vacancy = $mitra->lowongans()
            ->where(function ($q) use ($id_lowongan) {
                if (is_numeric($id_lowongan)) {
                    $q->where('id', (int) $id_lowongan);
                }
                $q->orWhere('slug', $id_lowongan);
            })
            ->firstOrFail();

        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'tipe' => ['required', 'in:PKL,Kerja'],
            'jurusan' => ['required', 'string', 'max:150'],
            'lokasi' => ['required', 'string', 'max:150'],
            'kategori' => ['nullable', 'string', 'max:100'],
            'gaji' => ['nullable', 'string', 'max:150'],
            'kuota' => ['required', 'integer', 'min:1'],
            'deadline' => ['required', 'date'],
            'status' => ['required', 'in:Aktif,Ditutup,Draft'],
            'deskripsi' => ['required', 'string'],
        ]);

        $persyaratanArr = array_values(array_filter(array_map('trim', explode("\n", (string) $request->input('persyaratan')))));
        if (empty($persyaratanArr)) {
            $persyaratanArr = $vacancy->persyaratan_json ?: ['Siswa/Alumni SMK Plus Pelita Nusantara'];
        }

        $benefitArr = array_values(array_filter(array_map('trim', explode("\n", (string) $request->input('benefit')))));
        if (empty($benefitArr)) {
            $benefitArr = $vacancy->benefit_json ?: ['Sertifikat & Pengalaman Industri'];
        }

        $tipe = $request->input('tipe');

        $vacancy->update([
            'judul' => $request->input('title'),
            'tipe' => $tipe,
            'tipe_badge' => ($tipe === 'PKL') ? 'Magang / PKL Siswa' : 'Full-Time Lulusan',
            'target_jurusan' => $request->input('jurusan'),
            'lokasi' => $request->input('lokasi'),
            'kategori_posisi' => $request->input('kategori') ?: $vacancy->kategori_posisi,
            'gaji_kompensasi' => $request->input('gaji') ?: $vacancy->gaji_kompensasi,
            'kuota' => (int) $request->input('kuota', 1),
            'deadline' => $request->input('deadline'),
            'status' => $request->input('status', 'Aktif'),
            'deskripsi' => $request->input('deskripsi'),
            'persyaratan_json' => $persyaratanArr,
            'benefit_json' => $benefitArr,
        ]);

        return redirect()
            ->route('bkk.mitra.lowongan.index')
            ->with('success', "Perubahan lowongan '{$vacancy->judul}' berhasil disimpan!");
    }

    /**
     * Handler Hapus Lowongan (DELETE /bkk/dashboard/lowongan/{id_lowongan})
     */
    public function lowonganDestroy(Request $request, string $id_lowongan): RedirectResponse
    {
        $mitra = $this->getAuthenticatedMitra($request);

        $vacancy = $mitra->lowongans()
            ->where(function ($q) use ($id_lowongan) {
                if (is_numeric($id_lowongan)) {
                    $q->where('id', (int) $id_lowongan);
                }
                $q->orWhere('slug', $id_lowongan);
            })
            ->firstOrFail();

        $title = $vacancy->judul;
        $vacancy->delete();

        return redirect()
            ->route('bkk.mitra.lowongan.index')
            ->with('success', "Lowongan '{$title}' berhasil dihapus dari sistem.");
    }

    /**
     * Daftar CV Pelamar pada Lowongan Tertentu (/bkk/dashboard/lowongan/{id_lowongan}/pelamar)
     */
    public function pelamarIndex(Request $request, string $id_lowongan): View
    {
        $mitra = $this->getAuthenticatedMitra($request);

        $vacancy = $mitra->lowongans()
            ->where(function ($q) use ($id_lowongan) {
                if (is_numeric($id_lowongan)) {
                    $q->where('id', (int) $id_lowongan);
                }
                $q->orWhere('slug', $id_lowongan);
            })
            ->firstOrFail();

        $query = Lamaran::with(['profilSiswa', 'lowongan'])
            ->where('lowongan_id', $vacancy->id);

        $search = trim((string) $request->query('q', ''));
        $statusFilter = $request->query('status');

        if (!empty($search)) {
            $like = config('database.default') === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('kode_lamaran', $like, "%{$search}%")
                  ->orWhereHas('profilSiswa', function ($sq) use ($search, $like) {
                      $sq->where('nis', $like, "%{$search}%")
                         ->orWhere('nisn', $like, "%{$search}%")
                         ->orWhere('jurusan', $like, "%{$search}%")
                         ->orWhere('bio_singkat', $like, "%{$search}%");
                  });
            });
        }

        if (!empty($statusFilter) && $statusFilter !== 'Semua') {
            $query->where('status', $statusFilter);
        }

        $applicants = $query->latest('created_at')->paginate(10)->withQueryString();

        return view('mitra.pages.pelamar.index', compact(
            'mitra',
            'vacancy',
            'applicants',
            'search',
            'statusFilter'
        ));
    }

    /**
     * Detail Pelamar, Review CV, dan Form Status Seleksi (/bkk/dashboard/lowongan/{id_lowongan}/pelamar/{id_pelamar})
     */
    public function pelamarShow(Request $request, string $id_lowongan, string $id_pelamar): View
    {
        $mitra = $this->getAuthenticatedMitra($request);

        $vacancy = $mitra->lowongans()
            ->where(function ($q) use ($id_lowongan) {
                if (is_numeric($id_lowongan)) {
                    $q->where('id', (int) $id_lowongan);
                }
                $q->orWhere('slug', $id_lowongan);
            })
            ->firstOrFail();

        $applicant = Lamaran::with(['profilSiswa', 'cvResume', 'riwayatStatus'])
            ->where('lowongan_id', $vacancy->id)
            ->where(function ($q) use ($id_pelamar) {
                if (is_numeric($id_pelamar)) {
                    $q->where('id', (int) $id_pelamar);
                }
                $q->orWhere('kode_lamaran', $id_pelamar);
            })
            ->firstOrFail();

        // Siapkan CV Markdown dari snapshot cv_resumes atau profil_siswa
        $cvMarkdown = '';
        if ($applicant->cvResume && !empty($applicant->cvResume->konten_markdown)) {
            $cvMarkdown = $applicant->cvResume->konten_markdown;
        } else {
            $siswa = $applicant->profilSiswa;
            $nama = $siswa?->nama_lengkap ?? 'Pelamar BKK';
            $jurusan = $siswa?->jurusan ?? $vacancy->target_jurusan;
            $bio = $applicant->pesan_pelamar ?: ($siswa?->bio_singkat ?? 'Siswa/Alumni berkomitmen tinggi.');

            $cvMarkdown = "# {$nama}\n"
                . "**Jurusan:** {$jurusan} | **Domisili:** " . ($siswa?->kota ?? 'Bogor') . "\n\n"
                . "## Ringkasan Profil\n{$bio}\n\n"
                . "## Pendidikan\n- **SMK Plus Pelita Nusantara** — {$jurusan} (" . ($siswa?->status_kelulusan === 'ALUMNI' ? 'Alumni' : 'Siswa Aktif') . ")\n\n"
                . "## Kualifikasi & Portofolio\n- Nilai Kecocokan AI: {$applicant->skor_match_ai}%\n"
                . "- Portofolio: " . ($siswa?->link_portfolio ?: 'Tersedia pada verifikasi offline') . "\n";
        }

        return view('mitra.pages.pelamar.detail', compact(
            'mitra',
            'vacancy',
            'applicant',
            'cvMarkdown'
        ));
    }

    /**
     * Handler Update Status Seleksi Pelamar
     */
    public function pelamarUpdateStatus(Request $request, string $id_lowongan, string $id_pelamar): RedirectResponse
    {
        $mitra = $this->getAuthenticatedMitra($request);

        $vacancy = $mitra->lowongans()
            ->where(function ($q) use ($id_lowongan) {
                if (is_numeric($id_lowongan)) {
                    $q->where('id', (int) $id_lowongan);
                }
                $q->orWhere('slug', $id_lowongan);
            })
            ->firstOrFail();

        $applicant = Lamaran::where('lowongan_id', $vacancy->id)
            ->where(function ($q) use ($id_pelamar) {
                if (is_numeric($id_pelamar)) {
                    $q->where('id', (int) $id_pelamar);
                }
                $q->orWhere('kode_lamaran', $id_pelamar);
            })
            ->firstOrFail();

        $request->validate([
            'status' => ['required', 'in:Sedang Ditinjau,Dipanggil Interview,Diterima,Ditolak'],
            'catatan_seleksi' => ['nullable', 'string', 'max:1000'],
        ]);

        $newStatus = $request->input('status');
        $notes = $request->input('catatan_seleksi');

        $applicant->update([
            'status' => $newStatus,
            'catatan_seleksi' => $notes,
        ]);

        // Catat riwayat audit status seleksi
        LamaranRiwayatStatus::create([
            'lamaran_id' => $applicant->id,
            'judul_tahapan' => "Tahapan Seleksi: {$newStatus}",
            'deskripsi' => $notes ?: "Status pelamar diperbarui menjadi {$newStatus} oleh {$mitra->nama_perusahaan}.",
            'diubah_oleh_id' => (string) $mitra->id,
            'diubah_oleh_role' => 'MITRA',
        ]);

        return redirect()
            ->route('bkk.mitra.pelamar.show', [$vacancy->id, $applicant->kode_lamaran ?: $applicant->id])
            ->with('success', "Status seleksi pelamar berhasil diperbarui menjadi '{$newStatus}'!");
    }
}
