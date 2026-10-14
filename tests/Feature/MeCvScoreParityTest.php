<?php

namespace Tests\Feature;

use App\Models\CvResume;
use App\Models\ProfilSiswa;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MeCvScoreParityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    protected function fakeAuth(string $role = 'SISWA', string $id = 'usr-siswa-001', string $name = 'Siswa Test'): void
    {
        Http::fake([
            '*/api/user/verify' => Http::response([
                'success' => true,
                'message' => 'Token terverifikasi',
                'data' => [
                    'id' => $id,
                    'username' => 'user_' . strtolower($role),
                    'nama_lengkap' => $name,
                    'nomor_induk' => 'TEST-NISN-999',
                    'role' => $role,
                    'status_aktif' => true,
                    'email' => strtolower($role) . '@smkpenus.sch.id',
                ],
            ], 200),
        ]);
    }

    /**
     * D1: Siswa dengan CV yang belum dievaluasi AI menghasilkan skor heuristik
     * yang identik antara Dashboard (/bkk/me) dan CV Hub (/bkk/me/cv).
     */
    public function test_unevaluated_cv_produces_identical_heuristic_score_on_dashboard_and_cv_hub(): void
    {
        $userId = 'usr-siswa-parity-01';
        $this->fakeAuth('SISWA', $userId, 'Rian Hidayat');

        // Request dashboard JSON
        $dashRes = $this->withHeader('Authorization', 'Bearer token_rian')
            ->getJson('/bkk/me');
        $dashRes->assertStatus(200);

        // Request CV hub JSON
        $cvRes = $this->withHeader('Authorization', 'Bearer token_rian')
            ->getJson('/bkk/me/cv');
        $cvRes->assertStatus(200);

        $dashScore = $dashRes->json('data.cv_score');
        $cvScore = $cvRes->json('data.score');

        $this->assertNotNull($dashScore);
        $this->assertNotNull($cvScore);

        // Skor total harus persis sama (heuristik terpadu)
        $this->assertSame($dashScore['total'], $cvScore['total']);

        // Parameter ATS harus persis sama
        $this->assertNotEmpty($dashScore['params']);
        $this->assertSame($dashScore['params'], $cvScore['params']);
    }

    /**
     * D1: Siswa dengan CV yang memiliki skor AI riil (cv_resumes.score & cv_resumes.feedback)
     * membaca kolom modern tersebut pada Dashboard (/bkk/me) dan bernilai sama dengan (/bkk/me/cv).
     */
    public function test_evaluated_cv_reads_modern_columns_and_matches_between_dashboard_and_cv_hub(): void
    {
        $userId = 'usr-siswa-parity-02';
        $this->fakeAuth('SISWA', $userId, 'Siti Nurhaliza');

        // Pastikan profil siswa tersedia
        ProfilSiswa::firstOrCreate(
            ['user_id' => $userId],
            [
                'nis' => '1234567890',
                'jurusan' => 'Rekayasa Perangkat Lunak',
                'kelas' => 'XII RPL 1',
                'angkatan' => '2026',
                'status_kelulusan' => 'SISWA_AKTIF',
            ]
        );

        $feedbackPayload = [
            'total_score' => 93,
            'parameters' => [
                [
                    'id' => 'ats_format',
                    'label' => 'Format ATS',
                    'score' => 95,
                    'value' => 95,
                    'max_score' => 100,
                    'status' => 'EXCELLENT',
                    'weight' => 0.25,
                    'feedback' => 'Format ATS sangat terstruktur.',
                ],
                [
                    'id' => 'industry_fit',
                    'label' => 'Kesesuaian Industri',
                    'score' => 92,
                    'value' => 92,
                    'max_score' => 100,
                    'status' => 'EXCELLENT',
                    'weight' => 0.35,
                    'feedback' => 'Keahlian sesuai kebutuhan industri IT.',
                ],
            ],
        ];

        CvResume::create([
            'user_id' => $userId,
            'raw_markdown' => "# Siti Nurhaliza\n\n## Ringkasan\nBackend Engineer berpengalaman.",
            'checksum' => hash('sha256', "# Siti Nurhaliza\n\n## Ringkasan\nBackend Engineer berpengalaman."),
            'is_main' => true,
            'temp' => false,
            'score' => 93,
            'feedback' => $feedbackPayload,
        ]);

        // Request dashboard JSON
        $dashRes = $this->withHeader('Authorization', 'Bearer token_siti')
            ->getJson('/bkk/me');
        $dashRes->assertStatus(200);

        // Request CV hub JSON
        $cvRes = $this->withHeader('Authorization', 'Bearer token_siti')
            ->getJson('/bkk/me/cv');
        $cvRes->assertStatus(200);

        $dashScore = $dashRes->json('data.cv_score');
        $cvScore = $cvRes->json('data.score');

        // Skor harus 93, bukan fallback dummy 84 / 88
        $this->assertSame(93, $dashScore['total']);
        $this->assertSame(93, $cvScore['total']);

        // Parameter ATS harus cocok dengan data feedback riil
        $this->assertCount(2, $dashScore['params']);
        $this->assertSame('ats_format', $dashScore['params'][0]['id']);
        $this->assertSame(95, $dashScore['params'][0]['value']);
        $this->assertSame($dashScore['params'], $cvScore['params']);

        // Verifikasi HTML Dashboard merender skor 93
        $dashHtml = $this->withHeader('Authorization', 'Bearer token_siti')
            ->get('/bkk/me');
        $dashHtml->assertStatus(200);
        $dashHtml->assertSee('93');
        $dashHtml->assertDontSee('82/100');
    }
}
