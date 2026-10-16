<?php

namespace Tests\Feature;

use App\Models\CvResume;
use App\Models\Mitra;
use App\Models\PenempatanPkl;
use App\Models\PklJurnalHarian;
use App\Models\PklLaporanAkhir;
use App\Models\ProfilSiswa;
use App\Support\DevAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuruPortalAndCvUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->app['env'] = 'local';
        config([
            'services.auth_service.dev_bypass' => true,
            'services.auth_service.dev_role' => 'ADMIN',
        ]);
        Http::fake();
    }

    protected function asRole(string $role)
    {
        return $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withCookie(DevAuth::COOKIE, $role);
    }

    // Tiap test menyiapkan datanya sendiri (RefreshDatabase mengosongkan database di antara test)
    protected function makeSiswa(string $userId = 'usr-siswa-001'): ProfilSiswa
    {
        return ProfilSiswa::updateOrCreate(
            ['user_id' => $userId],
            [
                'nis' => '0061234567',
                'jurusan' => 'Rekayasa Perangkat Lunak',
                'angkatan' => '2023',
                'status_kelulusan' => 'SISWA_AKTIF',
            ]
        );
    }

    protected function makeCv(string $userId = 'usr-siswa-001', array $attributes = []): CvResume
    {
        $this->makeSiswa($userId);
        $markdown = "# CV Siswa\n\nPengalaman proyek web.";

        return CvResume::create(array_merge([
            'user_id' => $userId,
            'raw_markdown' => $markdown,
            'checksum' => hash('sha256', $markdown),
            'is_main' => true,
            'temp' => false,
        ], $attributes));
    }

    protected function makeMitra(): Mitra
    {
        return Mitra::firstOrCreate(
            ['npwp' => 'TEST-MITRA-001'],
            [
                'nama_perusahaan' => 'PT Solusi Bangsa Industri',
                'password' => bcrypt('secret123'),
                'sektor_industri' => 'Teknologi Informasi',
                'alamat_kantor' => 'Jl. Merdeka No. 10',
                'kota' => 'Bogor',
                'email_perusahaan' => 'hrd@solusibangsa.id',
                'no_telp_perusahaan' => '0251123456',
                'pic_name' => 'Bpk. Hendra PIC',
                'pic_role' => 'HR Manager',
                'pic_phone' => '081234567890',
                'pic_email' => 'hendra@solusibangsa.id',
                'is_verified' => true,
            ]
        );
    }

    protected function makePenempatan(string $status, string $userId = 'usr-siswa-001'): PenempatanPkl
    {
        $this->makeSiswa($userId);

        return PenempatanPkl::create([
            'siswa_id' => $userId,
            'mitra_id' => $this->makeMitra()->id,
            'guru_pembimbing_id' => 'usr-guru-001',
            'pembimbing_industri_nama' => 'Mentor Budi',
            'unit_kerja_divisi' => 'Software Dev',
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addMonths(6)->toDateString(),
            'target_jam' => 640,
            'total_jam_tercapai' => 0,
            'status' => $status,
        ]);
    }

    public function test_student_can_upload_cv_document_file(): void
    {
        $userId = 'usr-siswa-001';
        $this->makeSiswa($userId);

        $fakePdf = UploadedFile::fake()->create('curriculum_vitae.pdf', 500, 'application/pdf');

        $response = $this->asRole('SISWA')
            ->post('/bkk/me/cv/upload', [
                'cv_file' => $fakePdf,
            ]);

        $response->assertRedirect(route('bkk.me.cv'));
        $response->assertSessionHas('success');

        $cv = CvResume::where('user_id', $userId)->where('temp', false)->first();
        $this->assertNotNull($cv);
        $this->assertEquals('curriculum_vitae.pdf', $cv->file_name);
        $this->assertTrue($cv->has_document_file);
        $this->assertEquals('Belum Direview', $cv->status_review_guru);
    }

    public function test_student_can_delete_uploaded_cv_document(): void
    {
        $cv = $this->makeCv('usr-siswa-001', [
            'file_path' => 'storage/cv_uploads/fake.pdf',
            'file_name' => 'fake.pdf',
            'file_size' => 12345,
        ]);

        $response = $this->asRole('SISWA')
            ->post('/bkk/me/cv/delete-file');

        $response->assertRedirect(route('bkk.me.cv'));
        $cv->refresh();
        $this->assertNull($cv->file_path);
        $this->assertNull($cv->file_name);
    }

    public function test_guru_can_access_dashboard_and_directory(): void
    {
        $response = $this->asRole('GURU')
            ->get('/bkk/guru');

        $response->assertStatus(200);
        $response->assertSee('Portal Guru Pembimbing PKL');

        $siswaRes = $this->asRole('GURU')
            ->get('/bkk/guru/siswa');
        $siswaRes->assertStatus(200);
        $siswaRes->assertSee('Direktori Siswa & CV');
    }

    public function test_guru_can_review_cv_and_provide_feedback(): void
    {
        $userId = 'usr-siswa-001';
        $this->makeCv($userId);

        $cvRes = $this->asRole('GURU')
            ->get("/bkk/guru/siswa/{$userId}/cv");
        $cvRes->assertStatus(200);
        $cvRes->assertSee('Tinjauan CV Siswa');

        $feedbackRes = $this->asRole('GURU')
            ->from("/bkk/guru/siswa/{$userId}/cv")
            ->post("/bkk/guru/siswa/{$userId}/cv/feedback", [
                'status_review_guru' => 'Disetujui',
                'catatan_guru' => 'CV sangat terstruktur dan siap diajukan ke industri mitra.',
            ]);

        $feedbackRes->assertSessionHas('success');

        $cv = CvResume::where('user_id', $userId)->first();
        $this->assertEquals('Disetujui', $cv->status_review_guru);
        $this->assertEquals('CV sangat terstruktur dan siap diajukan ke industri mitra.', $cv->catatan_guru);
    }

    public function test_guru_can_accept_pkl_application(): void
    {
        $userId = 'usr-siswa-001';
        $penempatan = $this->makePenempatan('MENUNGGU_PERSETUJUAN', $userId);

        $acceptRes = $this->asRole('GURU')
            ->from('/bkk/guru/pengajuan')
            ->post("/bkk/guru/pengajuan/{$penempatan->id}/accept", [
                'catatan_guru' => 'Disetujui oleh Guru Pembimbing.',
            ]);

        $acceptRes->assertSessionHas('success');

        $penempatan->refresh();
        $this->assertEquals('BERJALAN', $penempatan->status);
        $this->assertNotNull($penempatan->disetujui_pada);
    }

    public function test_guru_can_input_final_grade_and_complete_pkl(): void
    {
        $userId = 'usr-siswa-001';
        $penempatan = $this->makePenempatan('BERJALAN', $userId);

        $gradeRes = $this->asRole('GURU')
            ->from("/bkk/guru/penempatan/{$penempatan->id}")
            ->post("/bkk/guru/penempatan/{$penempatan->id}/nilai", [
                'nilai_akhir_sekolah' => 92.5,
                'catatan_guru' => 'Kerja keras dan pemahaman sangat memuaskan.',
            ]);

        $gradeRes->assertSessionHas('success');
        $penempatan->refresh();
        $this->assertEquals(92.5, $penempatan->nilai_akhir_sekolah);

        $finishRes = $this->asRole('GURU')
            ->from("/bkk/guru/penempatan/{$penempatan->id}")
            ->post("/bkk/guru/penempatan/{$penempatan->id}/selesaikan");

        $finishRes->assertSessionHas('success');
        $penempatan->refresh();
        $this->assertEquals('SELESAI', $penempatan->status);
    }

    public function test_student_can_view_pkl_status_and_submit_mandiri_application(): void
    {
        $userId = 'usr-siswa-001';
        ProfilSiswa::updateOrCreate(
            ['user_id' => $userId],
            [
                'nis' => '0061234567',
                'jurusan' => 'Teknik Komputer dan Jaringan',
                'angkatan' => '2023',
                'status_kelulusan' => 'SISWA_AKTIF',
            ]
        );

        $viewRes = $this->asRole('SISWA')
            ->get('/bkk/me/pkl');
        $viewRes->assertStatus(200);
        $viewRes->assertSee('Status & Pengajuan PKL Siswa');

        $submitRes = $this->asRole('SISWA')
            ->post('/bkk/me/pkl/pengajuan', [
                'tipe_mitra' => 'MANDIRI',
                'nama_perusahaan_mandiri' => 'PT Jaringan Solusi Global',
                'kota_mandiri' => 'Bogor',
                'alamat_mandiri' => 'Jl. Pajajaran No. 45',
                'unit_kerja_divisi' => 'Network Operations Center',
                'pembimbing_industri_nama' => 'Bpk. Ahmad NOC',
                'tanggal_mulai' => now()->toDateString(),
                'tanggal_selesai' => now()->addMonths(6)->toDateString(),
                'catatan_siswa' => 'Mohon persetujuan dari Guru Pembimbing.',
            ]);

        $submitRes->assertRedirect(route('bkk.me.pkl'));
        $submitRes->assertSessionHas('success');

        $penempatan = PenempatanPkl::where('siswa_id', $userId)->where('status', 'MENUNGGU_PERSETUJUAN')->first();
        $this->assertNotNull($penempatan);
        $this->assertTrue((bool) $penempatan->pengajuan_mandiri);
        $this->assertEquals('Network Operations Center', $penempatan->unit_kerja_divisi);
    }

    public function test_student_can_submit_pkl_application_without_optional_fields(): void
    {
        $userId = 'usr-siswa-001';
        $this->makeSiswa($userId);

        $this->asRole('SISWA')
            ->post('/bkk/me/pkl/pengajuan', [
                'tipe_mitra' => 'MANDIRI',
                'nama_perusahaan_mandiri' => 'PT Jaringan Solusi Global',
                'unit_kerja_divisi' => 'Network Operations Center',
                'tanggal_mulai' => now()->toDateString(),
                'tanggal_selesai' => now()->addMonths(6)->toDateString(),
            ])
            ->assertRedirect(route('bkk.me.pkl'))
            ->assertSessionHas('success');

        $penempatan = PenempatanPkl::where('siswa_id', $userId)->first();
        $this->assertEquals('Mentor Industri', $penempatan->pembimbing_industri_nama);
        $this->assertNull($penempatan->catatan_guru);
    }

    public function test_guru_can_validate_journal_entry(): void
    {
        $penempatan = $this->makePenempatan('BERJALAN');
        $jurnal = PklJurnalHarian::create([
            'penempatan_pkl_id' => $penempatan->id,
            'siswa_id' => $penempatan->siswa_id,
            'tanggal' => now()->toDateString(),
            'aktivitas' => 'Pemasangan kabel UTP dan konfigurasi router mikrotik di gedung A.',
            'durasi_jam' => 8,
            'status' => 'Menunggu',
        ]);

        $validateRes = $this->asRole('GURU')
            ->from('/bkk/guru/jurnal')
            ->post("/bkk/guru/jurnal/{$jurnal->id}/validate", [
                'status' => 'Disetujui',
                'catatan_revisi' => 'Dokumentasi kegiatan sangat lengkap.',
            ]);

        $validateRes->assertSessionHas('success');
        $jurnal->refresh();
        $this->assertEquals('Disetujui', $jurnal->status);
        $this->assertEquals('Dokumentasi kegiatan sangat lengkap.', $jurnal->catatan_revisi);
    }

    public function test_guru_can_validate_final_report_chapter(): void
    {
        $penempatan = $this->makePenempatan('BERJALAN');
        $laporan = PklLaporanAkhir::create([
            'penempatan_pkl_id' => $penempatan->id,
            'siswa_id' => $penempatan->siswa_id,
            'nomor_bab' => 1,
            'judul_bab' => 'BAB I - Pendahuluan & Profil Tempat PKL',
            'status' => 'Ditinjau',
        ]);

        $validateRes = $this->asRole('GURU')
            ->from('/bkk/guru/laporan')
            ->post("/bkk/guru/laporan/{$laporan->id}/validate", [
                'status' => 'Disetujui',
                'catatan_pembimbing' => 'Bab I telah disetujui, lanjutkan penyusunan Bab II.',
            ]);

        $validateRes->assertSessionHas('success');
        $laporan->refresh();
        $this->assertEquals('Disetujui', $laporan->status);
    }
}
