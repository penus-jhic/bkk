<?php

namespace Tests\Feature;

use App\Models\CvResume;
use App\Models\Lamaran;
use App\Models\Lowongan;
use App\Models\Mitra;
use App\Models\Notifikasi;
use App\Models\PenempatanPkl;
use App\Models\PermohonanKerjasama;
use App\Models\PklJurnalHarian;
use App\Models\PklLaporanAkhir;
use App\Models\ProfilSiswa;
use App\Models\TracerKuesioner;
use App\Models\TracerRespon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BkkDatabaseAndModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function fakeAuth(string $role = 'ADMIN', string $id = 'test-user-id'): void
    {
        Http::fake([
            '*/api/user/verify' => Http::response([
                'success' => true,
                'message' => 'Token terverifikasi',
                'data' => [
                    'id' => $id,
                    'username' => strtolower($role) . '_user',
                    'nama_lengkap' => "User {$role}",
                    'nomor_induk' => 'TEST-001',
                    'role' => $role,
                    'status_aktif' => true,
                    'email' => strtolower($role) . '@smkpenus.sch.id',
                ],
            ], 200),
        ]);
    }

    /**
     * 1. Verifikasi seluruh 20 tabel database dari DATABASE_SCHEMA.md berhasil dimigrasi.
     */
    public function test_all_20_tables_from_schema_exist(): void
    {
        $expectedTables = [
            'profil_siswa',
            'cv_resumes',
            'cv_pendidikan',
            'cv_pengalaman',
            'cv_keahlian',
            'cv_sertifikat',
            'mitra',
            'permohonan_kerjasama',
            'lowongan',
            'lamaran',
            'jadwal_interview',
            'lamaran_riwayat_status',
            'penempatan_pkl',
            'pkl_jurnal_harian',
            'pkl_laporan_akhir',
            'tracer_kuesioner',
            'tracer_pertanyaan',
            'tracer_respon',
            'tracer_jawaban_detail',
            'notifikasi',
            'kategori_berita',
            'berita',
        ];

        foreach ($expectedTables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Tabel [{$table}] harus ada di database.");
        }
    }

    /**
     * 2. Verifikasi Seeder berhasil mengisi data relasional yang saling terhubung.
     */
    public function test_seeders_populate_interconnected_data(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Profil Siswa & Alumni
        $siswa = ProfilSiswa::find('usr-siswa-001');
        $this->assertNotNull($siswa);
        $this->assertEquals('SISWA_AKTIF', $siswa->status_kelulusan);

        $alumni = ProfilSiswa::find('usr-alumni-001');
        $this->assertNotNull($alumni);
        $this->assertEquals('ALUMNI', $alumni->status_kelulusan);

        // Mitra IDUKA
        $this->assertGreaterThanOrEqual(5, Mitra::count());
        $this->assertDatabaseHas('mitra', ['npwp' => '01.234.567.8-091.000']);

        // Lowongan
        $this->assertGreaterThanOrEqual(4, Lowongan::count());

        // Resume CV & AI Score
        $resume = CvResume::where('siswa_id', 'usr-siswa-001')->first();
        $this->assertNotNull($resume);
        $this->assertEquals(94, $resume->skor_total_ai);
        $this->assertGreaterThan(0, $resume->pendidikan()->count());
        $this->assertGreaterThan(0, $resume->pengalaman()->count());
        $this->assertGreaterThan(0, $resume->keahlian()->count());
        $this->assertGreaterThan(0, $resume->sertifikat()->count());

        // Lamaran & Interview
        $lamaran = Lamaran::with(['interview', 'riwayatStatus'])->where('siswa_id', 'usr-siswa-001')->first();
        $this->assertNotNull($lamaran);

        // Penempatan PKL, Jurnal, dan Laporan
        $penempatan = PenempatanPkl::with(['jurnals', 'laporans'])->where('siswa_id', 'usr-siswa-001')->first();
        $this->assertNotNull($penempatan);
        $this->assertEquals('BERJALAN', $penempatan->status);
        $this->assertGreaterThan(0, $penempatan->jurnals()->count());
        $this->assertEquals(5, $penempatan->laporans()->count());

        // Tracer Study
        $tracer = TracerKuesioner::with(['pertanyaans', 'respons.jawabanDetails'])->first();
        $this->assertNotNull($tracer);
        $this->assertGreaterThan(0, $tracer->pertanyaans()->count());
        $this->assertGreaterThan(0, $tracer->respons()->count());

        // Notifikasi
        $this->assertGreaterThan(0, Notifikasi::count());
    }

    /**
     * 3. Verifikasi Katalog Lowongan Publik dan Detail Lowongan.
     */
    public function test_public_lowongan_endpoints(): void
    {
        $this->seed(DatabaseSeeder::class);

        $resIndex = $this->get('/bkk/lowongan');
        $resIndex->assertStatus(200);

        $resJson = $this->getJson('/bkk/lowongan');
        $resJson->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'total',
                    'lowongan' => ['data'],
                ],
            ]);

        $lowongan = Lowongan::first();
        $initialViews = $lowongan->views_count;

        $resDetail = $this->get("/bkk/lowongan/{$lowongan->slug}");
        $resDetail->assertStatus(200);

        $this->assertEquals($initialViews + 1, $lowongan->fresh()->views_count);
    }

    /**
     * 4. Verifikasi Form Pengajuan Kerja Sama Publik (/bkk/kerja-sama).
     */
    public function test_public_permohonan_kerjasama_submission(): void
    {
        $payload = [
            'nama_perusahaan' => 'PT Mitra Baru Indonesia',
            'bidang_usaha' => 'Hardware & IT Infrastructure',
            'alamat_perusahaan' => 'Jl. Sudirman Kav 20, Jakarta',
            'email_resmi' => 'contact@mitrabaru.co.id',
            'no_telepon' => '021-5551234',
            'nama_pic' => 'Iwan Susanto',
            'jabatan_pic' => 'General Manager',
            'jenis_kerjasama' => ['PKL / Magang Siswa', 'Perekrutan Lulusan'],
            'pesan_tambahan' => 'Pengajuan MoU kemitraan industri.',
        ];

        $response = $this->post('/bkk/kerja-sama', $payload);
        $response->assertRedirect(route('bkk.kerjasama') . '#form-kemitraan')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('permohonan_kerjasama', [
            'email_resmi' => 'contact@mitrabaru.co.id',
            'status' => 'MENUNGGU_REVIEW',
        ]);
    }

    /**
     * 5. Verifikasi Interaksi Siswa di /bkk/me (Jurnal, Laporan, CV, Lamaran).
     */
    public function test_me_student_interaction_endpoints(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->fakeAuth('SISWA', 'usr-siswa-001');

        // Submit log jurnal PKL
        $jurnalRes = $this->withHeader('Authorization', 'Bearer valid_token')
            ->post('/bkk/me/jurnal', [
                'tanggal' => now()->toDateString(),
                'aktivitas' => 'Testing modul penelusuran tamatan dan validasi jurnal PKL.',
                'durasi_jam' => 8,
            ]);
        $jurnalRes->assertRedirect(route('bkk.me.jurnal'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pkl_jurnal_harian', [
            'siswa_id' => 'usr-siswa-001',
            'aktivitas' => 'Testing modul penelusuran tamatan dan validasi jurnal PKL.',
        ]);

        // Submit draft laporan PKL Bab 5
        $laporanRes = $this->withHeader('Authorization', 'Bearer valid_token')
            ->post('/bkk/me/laporan', [
                'nomor_bab' => 5,
                'judul_bab' => 'BAB V — Kesimpulan dan Rekomendasi Industri',
            ]);
        $laporanRes->assertRedirect(route('bkk.me.laporan'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('pkl_laporan_akhir', [
            'siswa_id' => 'usr-siswa-001',
            'nomor_bab' => 5,
            'status' => 'Ditinjau',
        ]);

        // Update CV Markdown
        $cvRes = $this->withHeader('Authorization', 'Bearer valid_token')
            ->post('/bkk/me/cv', [
                'markdown' => '# Ahmad Rizky Pratama - Updated ATS CV',
            ]);
        $cvRes->assertRedirect(route('bkk.me.cv'))
            ->assertSessionHas('success');

        // Apply lamaran baru
        $lowongan = Lowongan::where('slug', 'junior-web-developer-laravel-react-stn')->first();
        $daftarRes = $this->withHeader('Authorization', 'Bearer valid_token')
            ->post("/bkk/me/daftar/{$lowongan->id}");
        $daftarRes->assertRedirect(route('bkk.me.lamaran'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('lamaran', [
            'siswa_id' => 'usr-siswa-001',
            'lowongan_id' => $lowongan->id,
        ]);
    }

    /**
     * 6. Verifikasi Admin BKK: Moderasi Lowongan.
     */
    public function test_admin_lowongan_moderation(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->fakeAuth('ADMIN', 'adm-test-001');

        $resIndex = $this->withHeader('Authorization', 'Bearer valid_token')
            ->getJson('/bkk/admin/lowongan');
        $resIndex->assertStatus(200)
            ->assertJson(['success' => true]);

        $lowongan = Lowongan::first();
        $resUpdate = $this->withHeader('Authorization', 'Bearer valid_token')
            ->postJson("/bkk/admin/lowongan/{$lowongan->id}/status", [
                'status' => 'Ditutup',
            ]);
        $resUpdate->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals('Ditutup', $lowongan->fresh()->status);
    }

    /**
     * 7. Verifikasi Admin BKK: Verifikasi Mitra & Review Permohonan Kerja Sama.
     */
    public function test_admin_mitra_verification(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->fakeAuth('ADMIN', 'adm-test-001');

        $resIndex = $this->withHeader('Authorization', 'Bearer valid_token')
            ->getJson('/bkk/admin/mitra');
        $resIndex->assertStatus(200)
            ->assertJson(['success' => true]);

        $mitra = Mitra::first();
        $currentVerified = $mitra->is_verified;

        $resVerify = $this->withHeader('Authorization', 'Bearer valid_token')
            ->postJson("/bkk/admin/mitra/{$mitra->id}/verify");
        $resVerify->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals(!$currentVerified, $mitra->fresh()->is_verified);

        // Review permohonan kerjasama
        $permohonan = PermohonanKerjasama::first();
        $resReview = $this->withHeader('Authorization', 'Bearer valid_token')
            ->postJson("/bkk/admin/mitra/permohonan/{$permohonan->id}", [
                'status' => 'DISETUJUI',
                'catatan_admin' => 'Perusahaan memenuhi kualifikasi mitra.',
            ]);
        $resReview->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals('DISETUJUI', $permohonan->fresh()->status);
    }

    /**
     * 8. Verifikasi Admin BKK: Monitoring PKL & Validasi Jurnal / Laporan.
     */
    public function test_admin_pkl_monitoring_and_validation(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->fakeAuth('ADMIN', 'adm-test-001');

        $resMonitoring = $this->withHeader('Authorization', 'Bearer valid_token')
            ->getJson('/bkk/admin/pkl/monitoring');
        $resMonitoring->assertStatus(200)
            ->assertJson(['success' => true]);

        $jurnal = PklJurnalHarian::first();
        $resValJurnal = $this->withHeader('Authorization', 'Bearer valid_token')
            ->postJson("/bkk/admin/pkl/jurnal/{$jurnal->id}/validate", [
                'status' => 'Disetujui',
                'catatan_revisi' => 'Validasi tuntas.',
            ]);
        $resValJurnal->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals('Disetujui', $jurnal->fresh()->status);

        $laporan = PklLaporanAkhir::first();
        $resValLaporan = $this->withHeader('Authorization', 'Bearer valid_token')
            ->postJson("/bkk/admin/pkl/laporan/{$laporan->id}/validate", [
                'status' => 'Disetujui',
                'catatan_pembimbing' => 'Naskah bab disetujui.',
            ]);
        $resValLaporan->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertEquals('Disetujui', $laporan->fresh()->status);
    }

    /**
     * 9. Verifikasi Admin BKK: Tracer Study Aggregation & Pembuatan Kuesioner Baru.
     */
    public function test_admin_tracer_study_and_kuesioner(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->fakeAuth('ADMIN', 'adm-test-001');

        $resIndex = $this->withHeader('Authorization', 'Bearer valid_token')
            ->getJson('/bkk/admin/tracer-study');
        $resIndex->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'stats' => [
                    'total_respon',
                    'bmw',
                    'keselarasan',
                    'rata_rata_waktu_tunggu_bulan',
                ],
            ]);

        $resStore = $this->withHeader('Authorization', 'Bearer valid_token')
            ->postJson('/bkk/admin/tracer-study/kuesioner', [
                'judul' => 'Tracer Study Lulusan 2025 (6 Bulan Pasca Lulus)',
                'tahun_sasaran_lulusan' => '2025',
                'tanggal_mulai' => now()->toDateString(),
                'tanggal_selesai' => now()->addMonths(6)->toDateString(),
                'deskripsi' => 'Survei monitoring tahap awal pasca kelulusan.',
            ]);
        $resStore->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('tracer_kuesioner', [
            'tahun_sasaran_lulusan' => '2025',
        ]);
    }
}
