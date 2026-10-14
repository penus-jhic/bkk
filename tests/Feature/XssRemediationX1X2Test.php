<?php

namespace Tests\Feature;

use App\Models\Lowongan;
use App\Models\Mitra;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class XssRemediationX1X2Test extends TestCase
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
     * X1: Test that lowongan title containing quotes is properly escaped with @js
     * and does not allow breaking out of the inline onclick event handler.
     */
    public function test_x1_job_title_with_quotes_does_not_break_out_of_apply_vacancy_onclick(): void
    {
        $this->fakeAuth('SISWA', 'usr-siswa-001', 'Test Siswa');

        $mitra = Mitra::first() ?? Mitra::create([
            'nama_perusahaan' => 'PT Mitra Test',
            'password' => bcrypt('password'),
            'npwp' => '123456789',
            'is_verified' => true,
        ]);

        $xssTitle = "PoC');alert(document.domain);//";
        $lowongan = Lowongan::create([
            'mitra_id' => $mitra->id,
            'judul' => $xssTitle,
            'slug' => 'poc-xss-test-' . uniqid(),
            'tipe' => 'PKL',
            'tipe_badge' => 'Magang PKL',
            'target_jurusan' => 'Rekayasa Perangkat Lunak (RPL)',
            'lokasi' => 'Jakarta',
            'kuota' => 2,
            'deadline' => now()->addMonth(),
            'deskripsi' => 'Deskripsi lowongan uji keamanan',
            'persyaratan_json' => ['Siswa aktif kelas XII SMK RPL'],
            'status' => 'Aktif',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer token_siswa')
            ->get('/bkk/me');

        $response->assertStatus(200);

        $content = $response->getContent();

        // Must not contain the vulnerable unescaped single quote breakout
        $vulnerablePattern = "onclick=\"applyVacancy('" . $lowongan->id . "', '" . $xssTitle . "')\"";
        $vulnerableDecodedPattern = "applyVacancy('" . $lowongan->id . "', 'PoC');alert(document.domain);//')";
        $this->assertStringNotContainsString($vulnerablePattern, $content);
        $this->assertStringNotContainsString($vulnerableDecodedPattern, $content);

        // Must contain @js encoded safe attribute
        $this->assertStringContainsString('applyVacancy(', $content);
        $this->assertStringContainsString('PoC\u0027);alert(document.domain);\/\/', $content);
    }

    /**
     * X2: Test that me/master layout includes global escapeHtml helper.
     */
    public function test_x2_master_layout_defines_global_escape_html_helper(): void
    {
        $this->fakeAuth('SISWA', 'usr-siswa-001', 'Test Siswa');

        $resDashboard = $this->withHeader('Authorization', 'Bearer token_siswa')
            ->get('/bkk/me');
        $resDashboard->assertStatus(200);
        $resDashboard->assertSee('function escapeHtml(str)', false);

        $resLamaran = $this->withHeader('Authorization', 'Bearer token_siswa')
            ->get('/bkk/me/lamaran');
        $resLamaran->assertStatus(200);
        $resLamaran->assertSee('function escapeHtml(str)', false);
    }

    /**
     * X2: Test that openDetailModal drawer applies escapeHtml and color validation
     * in both dashboard and lamaran pages.
     */
    public function test_x2_open_detail_modal_escapes_interpolated_values_and_validates_color(): void
    {
        $this->fakeAuth('SISWA', 'usr-siswa-001', 'Test Siswa');

        foreach (['/bkk/me', '/bkk/me/lamaran'] as $url) {
            $response = $this->withHeader('Authorization', 'Bearer token_siswa')
                ->get($url);
            $response->assertStatus(200);

            $content = $response->getContent();

            // Check that openDetailModal sanitizes dangerous interpolated fields
            $this->assertStringContainsString('${escapeHtml(app.position)}', $content);
            $this->assertStringContainsString('${escapeHtml(app.company)}', $content);
            $this->assertStringContainsString('${escapeHtml(app.location)}', $content);
            $this->assertStringContainsString('${escapeHtml(app.status)}', $content);
            $this->assertStringContainsString('${escapeHtml(app.type)}', $content);
            $this->assertStringContainsString('${escapeHtml(app.interview.date)}', $content);
            $this->assertStringContainsString('${escapeHtml(app.interview.time)}', $content);
            $this->assertStringContainsString('${escapeHtml(app.interview.mode)}', $content);
            $this->assertStringContainsString('${escapeHtml(app.interview.place)}', $content);
            $this->assertStringContainsString('${escapeHtml(app.interview.pic)}', $content);
            $this->assertStringContainsString('${escapeHtml(t.title)}', $content);
            $this->assertStringContainsString('${escapeHtml(t.desc)}', $content);
            $this->assertStringContainsString('${escapeHtml(t.date)}', $content);

            // Check that background color is validated against hex regex
            $this->assertStringContainsString('/^#[0-9a-fA-F]{6}$/', $content);
        }
    }
}
