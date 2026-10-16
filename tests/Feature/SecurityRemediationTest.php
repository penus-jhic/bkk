<?php

namespace Tests\Feature;

use App\Http\Controllers\Mitra\MitraController;
use App\Models\CvResume;
use App\Models\Lamaran;
use App\Models\Lowongan;
use App\Models\Mitra;
use App\Models\ProfilSiswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SecurityRemediationTest extends TestCase
{
    use RefreshDatabase;

    protected Mitra $mitra;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mitra = Mitra::create([
            'nama_perusahaan' => 'PT Mitra Pengujian Keamanan',
            'singkatan' => 'MPK',
            'password' => Hash::make('PasswordAman123!'),
            'npwp' => '01.999.888.7-001.000',
            'sektor_industri' => 'Teknologi Informasi',
            'alamat_kantor' => 'Jl. Sudirman No. 1, Jakarta',
            'kota' => 'Jakarta',
            'email_perusahaan' => 'security@mitrapengujian.com',
            'no_telp_perusahaan' => '021-5551234',
            'pic_name' => 'Budi Santoso',
            'pic_role' => 'HR Manager',
            'pic_email' => 'budi@mitrapengujian.com',
            'pic_phone' => '081234567890',
            'is_verified' => true,
            'status_kemitraan' => 'Mitra IDUKA Terverifikasi',
        ]);
    }

    /**
     * Finding 1 Test: Unauthenticated request with X-Mitra-ID header cannot access dashboard.
     */
    public function test_mitra_auth_bypass_via_header_is_rejected(): void
    {
        $response = $this->withHeaders([
            'X-Mitra-ID' => (string) $this->mitra->id,
        ])->get('/bkk/dashboard');

        $response->assertRedirect(route('bkk.mitra.login'));
    }

    /**
     * Finding 1 Test: Unauthenticated JSON request with X-Mitra-ID header returns 401.
     */
    public function test_mitra_auth_bypass_via_header_returns_401_for_json(): void
    {
        $response = $this->withHeaders([
            'X-Mitra-ID' => (string) $this->mitra->id,
            'Accept' => 'application/json',
        ])->getJson('/bkk/dashboard');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Autentikasi Mitra diperlukan untuk mengakses halaman ini.',
            ]);
    }

    /**
     * Finding 1 Test: Request with unencrypted cookie mitra_token without valid session is rejected.
     */
    public function test_mitra_auth_bypass_via_cookie_is_rejected(): void
    {
        $response = $this->withUnencryptedCookie('mitra_token', (string) $this->mitra->id)
            ->get('/bkk/dashboard');

        $response->assertRedirect(route('bkk.mitra.login'));
    }

    /**
     * Finding 1 Test: Legitimate session authentication succeeds.
     */
    public function test_mitra_authenticated_session_succeeds(): void
    {
        $response = $this->withSession(['mitra_id' => $this->mitra->id])
            ->get('/bkk/dashboard');

        $response->assertStatus(200)
            ->assertSee($this->mitra->nama_perusahaan);
    }

    /**
     * Finding 2 Test: Stored XSS payload in applicant CV Markdown is neutralized.
     */
    public function test_stored_xss_in_cv_markdown_is_stripped_and_neutralized(): void
    {
        $lowongan = Lowongan::create([
            'mitra_id' => $this->mitra->id,
            'judul' => 'Software Engineer Intern',
            'slug' => 'software-engineer-intern-' . uniqid(),
            'tipe' => 'PKL',
            'tipe_badge' => 'Magang PKL',
            'target_jurusan' => 'Rekayasa Perangkat Lunak (RPL)',
            'lokasi' => 'Jakarta',
            'kuota' => 2,
            'deadline' => now()->addMonth(),
            'deskripsi' => 'Deskripsi lowongan magang software engineering.',
            'persyaratan_json' => ['Siswa aktif kelas XII SMK RPL'],
            'status' => 'Aktif',
        ]);

        $profil = ProfilSiswa::create([
            'user_id' => 'usr-attacker-001',
            'nis' => '12345678',
            'jurusan' => 'Rekayasa Perangkat Lunak (RPL)',
            'angkatan' => '2026',
            'status_kelulusan' => 'SISWA_AKTIF',
        ]);

        // Malicious XSS payload containing <script>, <img onerror>, and javascript: links
        $xssPayload = <<<EOT
## Curriculum Vitae

<script>alert('XSS-ATTACK-SCRIPT')</script>
<img src="invalid_image.jpg" onerror="alert('XSS-ONERROR')">
[Malicious Link](javascript:alert('XSS-LINK'))

### Keahlian Asli
- PHP & Laravel
- TypeScript
EOT;

        $cv = CvResume::create([
            'user_id' => $profil->user_id,
            'raw_markdown' => $xssPayload,
            'is_main' => true,
        ]);

        $lamaran = Lamaran::create([
            'kode_lamaran' => 'LAM-' . uniqid(),
            'lowongan_id' => $lowongan->id,
            'siswa_id' => $profil->user_id,
            'cv_id' => $cv->id,
            'status' => 'Terkirim',
            'tanggal_melamar' => now(),
            'skor_match_ai' => 85,
        ]);

        // Access candidate detail page as the authenticated Mitra recruiter
        $response = $this->withSession(['mitra_id' => $this->mitra->id])
            ->get("/bkk/dashboard/lowongan/{$lowongan->id}/pelamar/{$lamaran->id}");

        $response->assertStatus(200);

        // Verify that raw script tags and onerror handlers are completely stripped
        $response->assertDontSee("<script>alert('XSS-ATTACK-SCRIPT')</script>", false);
        $response->assertDontSee("onerror=\"alert('XSS-ONERROR')\"", false);
        $response->assertDontSee("href=\"javascript:alert('XSS-LINK')\"", false);

        // Verify that legitimate Markdown formatting is preserved
        $response->assertSee('Curriculum Vitae');
        $response->assertSee('Keahlian Asli');
        $response->assertSee('PHP &amp; Laravel', false);
    }

    /**
     * Finding 3 Test: getAuthenticatedMitra fails closed with 401 when no session or mitra exists.
     */
    public function test_get_authenticated_mitra_fails_closed_when_unauthenticated(): void
    {
        $controller = app(MitraController::class);
        $request = Request::create('/bkk/dashboard', 'GET');
        $request->setLaravelSession(app('session.store'));

        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('Autentikasi Mitra diperlukan.');

        $refMethod = new \ReflectionMethod($controller, 'getAuthenticatedMitra');
        $refMethod->setAccessible(true);
        $refMethod->invoke($controller, $request);
    }

    /**
     * Finding 4 Test: Authenticated student role cannot be spoofed to ALUMNI via ?role= parameter.
     */
    public function test_student_role_cannot_be_spoofed_via_query_parameter(): void
    {
        Http::fake([
            '*/api/user/verify' => Http::response([
                'success' => true,
                'message' => 'Token terverifikasi',
                'data' => [
                    'id' => 'usr-siswa-001',
                    'username' => 'siswa_rizky',
                    'nama_lengkap' => 'Ahmad Rizky Pratama',
                    'email' => 'siswa_rizky@smkpenus.sch.id',
                    'no_hp' => '0812-3456-7890',
                    'nomor_induk' => '0061234567',
                    'role' => 'SISWA',
                    'status_aktif' => true,
                ],
            ], 200),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer valid_student_token')
            ->getJson('/bkk/me?role=ALUMNI');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'profile' => [
                    'role' => 'SISWA',
                ],
            ],
        ]);
    }

    /**
     * Finding 5 Test: Public lowongan JSON endpoint masks corporate NPWP and HR representative PII.
     */
    public function test_public_lowongan_json_does_not_expose_mitra_pii(): void
    {
        Lowongan::create([
            'mitra_id' => $this->mitra->id,
            'judul' => 'Network Administrator',
            'slug' => 'network-administrator-' . uniqid(),
            'tipe' => 'Kerja',
            'tipe_badge' => 'Lowongan Kerja',
            'target_jurusan' => 'Teknik Komputer dan Jaringan (TKJ)',
            'lokasi' => 'Jakarta',
            'kuota' => 1,
            'deadline' => now()->addMonth(),
            'deskripsi' => 'Lowongan kerja staff network engineer.',
            'persyaratan_json' => ['Alumni SMK TKJ'],
            'status' => 'Aktif',
        ]);

        $response = $this->getJson('/bkk/lowongan');
        $response->assertStatus(200);

        $json = $response->getContent();
        $this->assertStringNotContainsString($this->mitra->npwp, $json);
        $this->assertStringNotContainsString($this->mitra->pic_name, $json);
        $this->assertStringNotContainsString($this->mitra->pic_email, $json);
        $this->assertStringNotContainsString($this->mitra->pic_phone, $json);
    }

    /**
     * Finding 6 Test: Mitra login regenerates session ID to prevent session fixation.
     */
    public function test_mitra_login_regenerates_session_id(): void
    {
        $this->withSession(['test_prev' => 'val']);
        $oldSessionId = session()->getId();

        $response = $this->post('/bkk/dashboard/login', [
            'nama_perusahaan' => $this->mitra->nama_perusahaan,
            'password' => 'PasswordAman123!',
        ]);

        $response->assertRedirect(route('bkk.mitra.dashboard'));
        $newSessionId = session()->getId();

        $this->assertNotEquals($oldSessionId, $newSessionId);
        $this->assertEquals($this->mitra->id, session('mitra_id'));
    }

    /**
     * Finding 7 Test: Internal infrastructure connection errors do not leak raw host/port messages.
     */
    public function test_auth_microservice_failure_does_not_leak_internal_exception(): void
    {
        Http::fake([
            '*/api/user/verify' => function () {
                throw new ConnectionException('cURL error 7: Failed to connect to localhost port 3002');
            },
        ]);

        $response = $this->withHeader('Authorization', 'Bearer valid_token')
            ->getJson('/bkk/admin');

        $response->assertStatus(503)
            ->assertJson([
                'success' => false,
                'message' => 'Layanan otentikasi sedang tidak tersedia. Silakan coba beberapa saat lagi.',
            ]);

        $this->assertStringNotContainsString('cURL error', $response->getContent());
        $this->assertStringNotContainsString('localhost port 3002', $response->getContent());
    }

    /**
     * Finding 8 Test: VerifyAuthToken rejects access_token supplied via query string.
     */
    public function test_verify_auth_token_rejects_query_parameter_access_token(): void
    {
        Http::fake([
            '*/api/user/verify' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 'admin-id',
                    'role' => 'ADMIN',
                    'status_aktif' => true,
                ],
            ], 200),
        ]);

        $response = $this->getJson('/bkk/admin?access_token=leaked_query_token');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Token otentikasi tidak ditemukan',
            ]);
    }

    /**
     * Finding 9 Test: Mitra logo upload derives safe server extension and randomized filename.
     */
    public function test_mitra_logo_upload_derives_safe_extension_and_random_name(): void
    {
        $file = UploadedFile::fake()->image('test_logo.png');

        $response = $this->withSession(['mitra_id' => $this->mitra->id])
            ->post('/bkk/dashboard/pengaturan/logo', [
                'logo' => $file,
            ]);

        $response->assertSessionHas('logo_success');
        $this->mitra->refresh();

        $this->assertNotNull($this->mitra->logo_url);
        $this->assertStringEndsWith('.png', $this->mitra->logo_url);
        $this->assertStringNotContainsString('test_logo', $this->mitra->logo_url);

        // Bersihkan berkas fisik yang dihasilkan tes
        $filePath = public_path(ltrim($this->mitra->logo_url, '/'));
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    /**
     * Finding 9 Test: Upload directory contains execution blocking configurations.
     */
    public function test_uploads_directory_has_script_execution_protections(): void
    {
        $this->assertFileExists(public_path('uploads/.htaccess'));
        $this->assertFileExists(public_path('uploads/web.config'));
    }
}
