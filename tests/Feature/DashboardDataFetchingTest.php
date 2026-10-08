<?php

namespace Tests\Feature;

use App\Models\Berita;
use App\Models\KategoriBerita;
use App\Models\Lamaran;
use App\Models\Lowongan;
use App\Models\Mitra;
use App\Models\PenempatanPkl;
use App\Models\ProfilSiswa;
use App\Models\TracerRespon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DashboardDataFetchingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    protected function fakeAuth(string $role = 'ADMIN', string $id = 'usr-admin-001'): void
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
     * 1. Test Admin Dashboard fetches and renders live BKK ecosystem data.
     */
    public function test_admin_dashboard_fetches_and_renders_live_ecosystem_data(): void
    {
        $this->fakeAuth('ADMIN', 'usr-admin-001');

        $response = $this->withHeader('Authorization', 'Bearer valid_token')
            ->get('/bkk/admin');

        $response->assertStatus(200);
        $response->assertSee('Pusat Kendali Admin');
        $response->assertSee('Perlu Tindakan');
        $response->assertSee('Lowongan Aktif');
        $response->assertSee('Total Pelamar');
        $response->assertSee('Siswa PKL Berjalan');
        $response->assertSee('Mitra IDUKA');
        $response->assertSee('Corong Lamaran');
        $response->assertSee('Keterserapan Alumni');
        $response->assertSee('Publikasi Berita BKK');

        // Status tracer & identitas siswa PKL tampil dari kolom yang benar (bukan user_id mentah)
        $pkl = \App\Models\PenempatanPkl::with('siswa')->where('status', 'BERJALAN')->first();
        if ($pkl?->siswa) {
            $response->assertSee('NIS ' . $pkl->siswa->nis);
        }

        // Test JSON endpoint
        $jsonResponse = $this->withHeader('Authorization', 'Bearer valid_token')
            ->getJson('/bkk/admin');

        $jsonResponse->assertStatus(200);
        $jsonResponse->assertJsonStructure([
            'success',
            'data' => [
                'user',
                'stats' => [
                    'total_berita',
                    'total_published',
                    'total_draft',
                    'total_views',
                    'total_kategori',
                    'total_lowongan',
                    'total_lowongan_aktif',
                    'total_mitra',
                    'total_mitra_verified',
                    'total_siswa_pkl',
                    'total_pelamar',
                    'total_tracer_respon',
                    'total_permohonan_pending',
                    'total_jurnal_menunggu',
                    'tracer_terserap',
                ],
                'lamaran_funnel',
                'tracer_breakdown',
                'recent_lamarans',
                'kategori',
                'recent_lowongans',
                'recent_mitras',
                'recent_pkl',
                'recent_tracer',
            ],
        ]);
    }

    /**
     * 2. Test Mitra Dashboard fetches and renders live database data.
     */
    public function test_mitra_dashboard_fetches_and_renders_live_database_data(): void
    {
        $mitra = Mitra::first();

        $response = $this->withSession(['mitra_id' => $mitra->id])
            ->get('/bkk/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Portal Resmi Rekrutmen Mitra IDUKA');
        $response->assertSee($mitra->nama_perusahaan);
        $response->assertSee('Lowongan Aktif');
        $response->assertSee('Total Pelamar');
        $response->assertSee('Review CV');
    }

    /**
     * 3. Test Siswa Dashboard fetches and renders live database profile, applications, and vacancies.
     */
    public function test_siswa_dashboard_fetches_and_renders_live_database_data(): void
    {
        $this->fakeAuth('SISWA', 'usr-siswa-001');

        $response = $this->withHeader('Authorization', 'Bearer valid_token')
            ->get('/bkk/me');

        $response->assertStatus(200);
        $response->assertSee('Dashboard Siswa & Alumni');
        $response->assertSee('Kelengkapan Profil');
        $response->assertSee('Tracker Lamaran');
        $response->assertSee('Rekomendasi PKL & Lowongan');

        // Test JSON endpoint
        $jsonResponse = $this->withHeader('Authorization', 'Bearer valid_token')
            ->getJson('/bkk/me');

        $jsonResponse->assertStatus(200);
        $jsonResponse->assertJsonStructure([
            'success',
            'data' => [
                'profile',
                'applications',
                'vacancies',
                'cv_score',
                'active_count',
                'interview_count',
            ],
        ]);
    }

    /**
     * 4. Test Siswa Lamaran page renders live applications list.
     */
    public function test_siswa_lamaran_page_fetches_live_applications(): void
    {
        $this->fakeAuth('SISWA', 'usr-siswa-001');

        $response = $this->withHeader('Authorization', 'Bearer valid_token')
            ->get('/bkk/me/lamaran');

        $response->assertStatus(200);
        $response->assertSee('Riwayat Lamaran');
        $response->assertSee('Detail Seleksi');
    }

    public function test_admin_logout_clears_access_token_cookie(): void
    {
        $response = $this->post('/bkk/logout');

        $response->assertRedirect(route('bkk.index'));
        $response->assertCookieExpired('access_token');
    }

    public function test_admin_header_shows_live_pending_work_counts(): void
    {
        $this->fakeAuth('ADMIN', 'usr-admin-001');

        $pending = \App\Models\PermohonanKerjasama::where('status', 'MENUNGGU_REVIEW')->count();

        $response = $this->withHeader('Authorization', 'Bearer valid_token')->get('/bkk/admin/mitra');

        $response->assertStatus(200);
        if ($pending > 0) {
            $response->assertSee('Permohonan kerja sama baru');
        } else {
            $response->assertSee('Tidak ada antrean yang menunggu tindakan.');
        }
    }
}
