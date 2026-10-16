<?php

namespace Tests\Feature;

use App\Models\CvResume;
use App\Models\Lamaran;
use App\Models\LamaranRiwayatStatus;
use App\Models\Lowongan;
use App\Models\Mitra;
use App\Models\ProfilSiswa;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MeAuditD2D3RemediationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    protected function fakeAuth(string $role = 'SISWA', string $id = 'usr-siswa-audit-01', string $name = 'Budi Santoso'): void
    {
        Http::fake([
            '*/api/user/verify' => Http::response([
                'success' => true,
                'message' => 'Token terverifikasi',
                'data' => [
                    'id' => $id,
                    'username' => 'user_' . strtolower($role),
                    'nama_lengkap' => $name,
                    'nomor_induk' => 'TEST-NISN-888',
                    'role' => $role,
                    'status_aktif' => true,
                    'email' => strtolower($role) . '@smkpenus.sch.id',
                ],
            ], 200),
        ]);
    }

    /**
     * D2: Editor CV (/bkk/me/cv/edit & /bkk/me/cv/{id}/edit) menghasilkan skor & parameter
     * yang identik dengan CV Hub (/bkk/me/cv) pada CV yang belum dievaluasi AI (tanpa mock dummy 84/89).
     */
    public function test_cv_edit_and_cv_edit_version_parity_with_cv_hub_unevaluated(): void
    {
        $userId = 'usr-siswa-d2-01';
        $this->fakeAuth('SISWA', $userId, 'Budi Santoso');

        ProfilSiswa::firstOrCreate(
            ['user_id' => $userId],
            [
                'nis' => '1122334455',
                'jurusan' => 'Teknik Komputer & Jaringan',
                'kelas' => 'XII TKJ 1',
                'angkatan' => '2026',
                'status_kelulusan' => 'SISWA_AKTIF',
            ]
        );

        $cv = CvResume::create([
            'user_id' => $userId,
            'raw_markdown' => "# Budi Santoso\n\n## Ringkasan\nSiswa TKJ yang tekun.\n\n## Pendidikan\nSMK Penus\n\n## Keahlian\nMikroTik, Jaringan",
            'checksum' => hash('sha256', "# Budi Santoso\n\n## Ringkasan\nSiswa TKJ yang tekun.\n\n## Pendidikan\nSMK Penus\n\n## Keahlian\nMikroTik, Jaringan"),
            'is_main' => true,
            'temp' => false,
            'score' => null,
            'feedback' => null,
        ]);

        // Request CV Hub
        $cvRes = $this->withHeader('Authorization', 'Bearer token_budi')
            ->getJson('/bkk/me/cv');
        $cvRes->assertStatus(200);
        $hubScore = $cvRes->json('data.score');

        // Request CV Edit
        $editRes = $this->withHeader('Authorization', 'Bearer token_budi')
            ->getJson('/bkk/me/cv/edit');
        $editRes->assertStatus(200);
        $editScore = $editRes->json('data.score');

        // Request CV Edit Version spesifik
        $editVerRes = $this->withHeader('Authorization', 'Bearer token_budi')
            ->getJson("/bkk/me/cv/{$cv->id}/edit");
        $editVerRes->assertStatus(200);
        $editVerScore = $editVerRes->json('data.score');

        $this->assertNotNull($hubScore);
        $this->assertNotNull($editScore);
        $this->assertNotNull($editVerScore);

        // Skor total dan parameter ATS harus identik 100%
        $this->assertSame($hubScore['total'], $editScore['total']);
        $this->assertSame($hubScore['total'], $editVerScore['total']);
        $this->assertSame($hubScore['params'], $editScore['params']);
        $this->assertSame($hubScore['params'], $editVerScore['params']);
    }

    /**
     * D2: CV Hub (/bkk/me/cv) mengembalikan saran kontekstual nyata dari teks CV Markdown
     * dan tidak lagi mengembalikan saran mock dummy statis dari MeDataService (misal id 's1').
     */
    public function test_cv_hub_returns_contextual_suggestions_instead_of_dummy(): void
    {
        $userId = 'usr-siswa-d2-02';
        $this->fakeAuth('SISWA', $userId, 'Ahmad Fauzi');

        ProfilSiswa::firstOrCreate(
            ['user_id' => $userId],
            [
                'nis' => '9988776655',
                'jurusan' => 'Rekayasa Perangkat Lunak',
                'kelas' => 'XII RPL 2',
                'angkatan' => '2026',
                'status_kelulusan' => 'SISWA_AKTIF',
            ]
        );

        $markdownContent = "# Ahmad Fauzi\n\n## Ringkasan\nCalon Software Engineer berdedikasi tinggi.\n\n## Pendidikan\nSMK Plus Pelita Nusantara\n\n## Pengalaman\n- Melaksanakan penugasan kerja industri di PT Software Kreasi";

        CvResume::create([
            'user_id' => $userId,
            'raw_markdown' => $markdownContent,
            'checksum' => hash('sha256', $markdownContent),
            'is_main' => true,
            'temp' => false,
            'score' => null,
            'feedback' => null,
        ]);

        $res = $this->withHeader('Authorization', 'Bearer token_ahmad')
            ->getJson('/bkk/me/cv');
        $res->assertStatus(200);

        $suggestions = $res->json('data.suggestions');
        $this->assertIsArray($suggestions);
        $this->assertNotEmpty($suggestions);

        // Harus menggunakan format saran kontekstual (sug-01, sug-02, ...)
        $this->assertStringStartsWith('sug-', $suggestions[0]['id']);

        // Pastikan tidak ada id dummy lama seperti 's1', 's2', 's3', 's4' dari MeDataService
        $ids = array_column($suggestions, 'id');
        $this->assertNotContains('s1', $ids);
        $this->assertNotContains('s2', $ids);
    }

    /**
     * D3: Timeline lamaran siswa (/bkk/me/lamaran dan /bkk/me) memetakan log riil dari
     * lamaran_riwayat_status (termasuk judul, catatan mitra, dan tanggal aktual).
     */
    public function test_application_timeline_reflects_real_riwayat_status(): void
    {
        $userId = 'usr-siswa-d3-01';
        $this->fakeAuth('SISWA', $userId, 'Dewi Sartika');

        $profil = ProfilSiswa::firstOrCreate(
            ['user_id' => $userId],
            [
                'nis' => '5544332211',
                'jurusan' => 'Teknik Komputer & Jaringan',
                'kelas' => 'XII TKJ 2',
                'angkatan' => '2026',
                'status_kelulusan' => 'SISWA_AKTIF',
            ]
        );

        $cv = CvResume::create([
            'user_id' => $userId,
            'raw_markdown' => "# Dewi Sartika\n\n## Ringkasan\nSiswa aktif.",
            'checksum' => hash('sha256', "# Dewi Sartika\n\n## Ringkasan\nSiswa aktif."),
            'is_main' => true,
            'temp' => false,
        ]);

        $lowongan = Lowongan::firstOrFail();

        $lamaran = Lamaran::create([
            'kode_lamaran' => 'LMR-TEST-001',
            'lowongan_id' => $lowongan->id,
            'siswa_id' => $userId,
            'cv_id' => $cv->id,
            'tanggal_melamar' => now()->toDateString(),
            'skor_match_ai' => 90,
            'status' => 'Sedang Ditinjau',
            'step_tahapan' => 2,
            'catatan_seleksi' => 'Sedang ditinjau oleh HRD mitra.',
        ]);

        // Tambah entri riwayat status 1 (oleh siswa saat submit)
        LamaranRiwayatStatus::create([
            'lamaran_id' => $lamaran->id,
            'judul_tahapan' => 'Lamaran Terkirim',
            'deskripsi' => 'Berkas pendaftaran diajukan via portal siswa.',
            'diubah_oleh_id' => $userId,
            'diubah_oleh_role' => 'SISWA',
            'created_at' => now()->subDay(),
        ]);

        // Tambah entri riwayat status 2 (oleh mitra saat review)
        LamaranRiwayatStatus::create([
            'lamaran_id' => $lamaran->id,
            'judul_tahapan' => 'Tahapan Seleksi: Sedang Ditinjau',
            'deskripsi' => 'CV memenuhi kualifikasi awal. Menunggu review tim teknis.',
            'diubah_oleh_id' => (string) $lowongan->mitra_id,
            'diubah_oleh_role' => 'MITRA',
            'created_at' => now(),
        ]);

        // Cek JSON lamaran di portal siswa
        $res = $this->withHeader('Authorization', 'Bearer token_dewi')
            ->getJson('/bkk/me/lamaran');
        $res->assertStatus(200);

        $apps = $res->json('data.applications');
        $this->assertNotEmpty($apps);

        $targetApp = collect($apps)->firstWhere('code', 'LMR-TEST-001');
        $this->assertNotNull($targetApp);

        $timeline = $targetApp['timeline'];
        $this->assertCount(2, $timeline);

        // Tahap 1
        $this->assertSame('Lamaran Terkirim', $timeline[0]['title']);
        $this->assertSame('Berkas pendaftaran diajukan via portal siswa.', $timeline[0]['desc']);
        $this->assertTrue($timeline[0]['done']);

        // Tahap 2
        $this->assertSame('Tahapan Seleksi: Sedang Ditinjau', $timeline[1]['title']);
        $this->assertSame('CV memenuhi kualifikasi awal. Menunggu review tim teknis.', $timeline[1]['desc']);
        $this->assertTrue($timeline[1]['done']);
    }

    /**
     * D3: Timeline lamaran dengan riwayat status kosong menggunakan fallback baseline dengan aman.
     */
    public function test_application_timeline_falls_back_gracefully_when_riwayat_empty(): void
    {
        $userId = 'usr-siswa-d3-02';
        $this->fakeAuth('SISWA', $userId, 'Eko Prasetyo');

        $profil = ProfilSiswa::firstOrCreate(
            ['user_id' => $userId],
            [
                'nis' => '1199228833',
                'jurusan' => 'Rekayasa Perangkat Lunak',
                'kelas' => 'XII RPL 1',
                'angkatan' => '2026',
                'status_kelulusan' => 'SISWA_AKTIF',
            ]
        );

        $cv = CvResume::create([
            'user_id' => $userId,
            'raw_markdown' => "# Eko Prasetyo\n\n## Ringkasan\nSiswa RPL.",
            'checksum' => hash('sha256', "# Eko Prasetyo\n\n## Ringkasan\nSiswa RPL."),
            'is_main' => true,
            'temp' => false,
        ]);

        $lowongan = Lowongan::firstOrFail();

        $lamaran = Lamaran::create([
            'kode_lamaran' => 'LMR-TEST-002',
            'lowongan_id' => $lowongan->id,
            'siswa_id' => $userId,
            'cv_id' => $cv->id,
            'tanggal_melamar' => now()->toDateString(),
            'skor_match_ai' => 90,
            'status' => 'Terkirim',
            'step_tahapan' => 1,
            'catatan_seleksi' => 'Menunggu verifikasi.',
        ]);

        // Tidak ada baris pada LamaranRiwayatStatus

        $res = $this->withHeader('Authorization', 'Bearer token_eko')
            ->getJson('/bkk/me/lamaran');
        $res->assertStatus(200);

        $apps = $res->json('data.applications');
        $targetApp = collect($apps)->firstWhere('code', 'LMR-TEST-002');
        $this->assertNotNull($targetApp);

        $timeline = $targetApp['timeline'];
        $this->assertCount(4, $timeline);
        $this->assertSame('Lamaran Dikirim', $timeline[0]['title']);
        $this->assertTrue($timeline[0]['done']);
    }
}
