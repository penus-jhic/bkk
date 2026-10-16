<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MeRoutingAndAuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Memastikan rute /bkk/me memerlukan autentikasi (401 bila tanpa token).
     */
    public function test_me_routes_require_authentication(): void
    {
        $response = $this->getJson('/bkk/me');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Token otentikasi tidak ditemukan',
            ]);
    }

    /**
     * Memastikan respon 401 bila token tidak valid.
     */
    public function test_me_routes_return_401_on_invalid_token(): void
    {
        Http::fake([
            '*/api/user/verify' => Http::response([
                'success' => false,
                'message' => 'access_token tidak valid',
            ], 401),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer invalid_token')
            ->getJson('/bkk/me');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Memastikan respon 403 bila akun pengguna berstatus nonaktif.
     */
    public function test_me_routes_return_403_when_account_inactive(): void
    {
        Http::fake([
            '*/api/user/verify' => Http::response([
                'success' => true,
                'message' => 'Token terverifikasi',
                'data' => [
                    'id' => 'siswa-inactive-id',
                    'username' => 'siswa_nonaktif',
                    'role' => 'SISWA',
                    'status_aktif' => false,
                ],
            ], 200),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer valid_token')
            ->getJson('/bkk/me');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'Akun pengguna sedang dinonaktifkan',
            ]);
    }

    /**
     * Memastikan role selain SISWA dan ALUMNI (misal GURU, TU, ADMIN) ditolak dengan 403 Forbidden.
     */
    public function test_me_routes_return_403_for_unauthorized_roles(): void
    {
        Http::fake([
            '*/api/user/verify' => Http::response([
                'success' => true,
                'message' => 'Token terverifikasi',
                'data' => [
                    'id' => 'guru-id',
                    'username' => 'guru_siti',
                    'role' => 'GURU',
                    'status_aktif' => true,
                ],
            ], 200),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer token_guru')
            ->getJson('/bkk/me');

        $response->assertStatus(403);
    }

    /**
     * Memastikan role SISWA dapat mengakses /bkk/me (Dashboard).
     */
    public function test_authenticated_siswa_can_access_dashboard(): void
    {
        Http::fake([
            '*/api/user/verify' => Http::response([
                'success' => true,
                'message' => 'Token terverifikasi',
                'data' => [
                    'id' => 'siswa-rizky-id',
                    'username' => 'siswa_rizky',
                    'nama_lengkap' => 'Ahmad Rizky Pratama',
                    'nomor_induk' => '0061234567',
                    'role' => 'SISWA',
                    'status_aktif' => true,
                    'email' => 'siswa_rizky@smkpenus.sch.id',
                ],
            ], 200),
        ]);

        // Akses via Bearer Header
        $resHtml = $this->withHeader('Authorization', 'Bearer token_siswa')
            ->get('/bkk/me');
        $resHtml->assertStatus(200)
            ->assertSee('Ahmad Rizky Pratama')
            ->assertSee('Tracker Lamaran');

        // Akses via JSON
        $resJson = $this->withHeader('Authorization', 'Bearer token_siswa')
            ->getJson('/bkk/me');
        $resJson->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'profile' => [
                        'name' => 'Ahmad Rizky Pratama',
                        'nis' => '0061234567',
                        'role' => 'SISWA',
                    ],
                ],
            ]);
    }

    /**
     * Memastikan role SISWA dapat mengakses /bkk/me/lamaran.
     */
    public function test_authenticated_siswa_can_access_lamaran(): void
    {
        Http::fake([
            '*/api/user/verify' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 'siswa-rizky-id',
                    'username' => 'siswa_rizky',
                    'role' => 'SISWA',
                    'status_aktif' => true,
                ],
            ], 200),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer token_siswa')
            ->get('/bkk/me/lamaran');

        $response->assertStatus(200)
            ->assertSee('Riwayat Lamaran');
    }

    /**
     * Memastikan role SISWA dapat mengakses /bkk/me/cv.
     */
    public function test_authenticated_siswa_can_access_cv(): void
    {
        Http::fake([
            '*/api/user/verify' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 'siswa-rizky-id',
                    'username' => 'siswa_rizky',
                    'role' => 'SISWA',
                    'status_aktif' => true,
                ],
            ], 200),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer token_siswa')
            ->get('/bkk/me/cv');

        $response->assertStatus(200)
            ->assertSeeText('CV & AI Scoring Hub');
    }

    /**
     * Memastikan role SISWA dapat mengakses /bkk/me/cv/edit.
     */
    public function test_authenticated_siswa_can_access_cv_edit(): void
    {
        Http::fake([
            '*/api/user/verify' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 'siswa-rizky-id',
                    'username' => 'siswa_rizky',
                    'role' => 'SISWA',
                    'status_aktif' => true,
                ],
            ], 200),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer token_siswa')
            ->get('/bkk/me/cv/edit');

        $response->assertStatus(200)
            ->assertSeeText('Editor CV ATS');
    }

    /**
     * Memastikan role SISWA dapat mengakses /bkk/me/jurnal.
     */
    public function test_authenticated_siswa_can_access_jurnal(): void
    {
        Http::fake([
            '*/api/user/verify' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 'siswa-rizky-id',
                    'username' => 'siswa_rizky',
                    'role' => 'SISWA',
                    'status_aktif' => true,
                ],
            ], 200),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer token_siswa')
            ->get('/bkk/me/jurnal');

        $response->assertStatus(200)
            ->assertSeeText('Jurnal PKL Harian')
            ->assertSee('Log Cepat Hari Ini');
    }

    /**
     * Memastikan role SISWA dapat mengakses /bkk/me/laporan.
     */
    public function test_authenticated_siswa_can_access_laporan(): void
    {
        Http::fake([
            '*/api/user/verify' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 'siswa-rizky-id',
                    'username' => 'siswa_rizky',
                    'role' => 'SISWA',
                    'status_aktif' => true,
                ],
            ], 200),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer token_siswa')
            ->get('/bkk/me/laporan');

        $response->assertStatus(200)
            ->assertSeeText('Laporan Akhir PKL')
            ->assertSee('Progres Persetujuan Laporan');
    }
}
