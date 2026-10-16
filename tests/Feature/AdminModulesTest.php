<?php

namespace Tests\Feature;

use App\Models\Lowongan;
use App\Models\Mitra;
use App\Models\PenempatanPkl;
use App\Models\PklJurnalHarian;
use App\Models\PklLaporanAkhir;
use App\Models\TracerKuesioner;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdminModulesTest extends TestCase
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
                    'nomor_induk' => 'ADM-001',
                    'role' => $role,
                    'status_aktif' => true,
                    'email' => strtolower($role) . '@smkpenus.sch.id',
                ],
            ], 200),
        ]);
    }

    /**
     * 1. Test /bkk/admin/lowongan HTML renders successfully without $recentBeritas error.
     */
    public function test_admin_lowongan_page_renders_html_without_recent_beritas_error(): void
    {
        $this->fakeAuth('ADMIN', 'usr-admin-001');

        $response = $this->withHeader('Authorization', 'Bearer valid_token')
            ->get('/bkk/admin/lowongan');

        $response->assertStatus(200);
        $response->assertSeeText('Katalog & Moderasi Lowongan');
        $response->assertSee('Total Lowongan');
        $response->assertSee('Tayang (Aktif)');
        $response->assertDontSee('$recentBeritas');
    }

    /**
     * 2. Test /bkk/admin/lowongan/{id} edit form loads.
     */
    public function test_admin_lowongan_edit_page_renders_successfully(): void
    {
        $this->fakeAuth('ADMIN', 'usr-admin-001');

        $lowongan = Lowongan::first();

        $response = $this->withHeader('Authorization', 'Bearer valid_token')
            ->get("/bkk/admin/lowongan/{$lowongan->id}");

        $response->assertStatus(200);
        $response->assertSeeText('Edit Informasi Lowongan');
        $response->assertSee($lowongan->judul);
    }

    /**
     * 3. Test /bkk/admin/lowongan/{id} update handler.
     */
    public function test_admin_lowongan_update(): void
    {
        $this->fakeAuth('ADMIN', 'usr-admin-001');

        $lowongan = Lowongan::first();

        $response = $this->withHeader('Authorization', 'Bearer valid_token')
            ->put("/bkk/admin/lowongan/{$lowongan->id}", [
                'judul' => 'Senior Frontend Specialist',
                'tipe' => 'Kerja',
                'target_jurusan' => 'RPL',
                'lokasi' => 'Bogor',
                'kuota' => 5,
                'deadline' => now()->addDays(30)->toDateString(),
                'status' => 'Aktif',
                'deskripsi' => 'Deskripsi tanggung jawab teknis.',
                'persyaratan' => "Keahlian Vue / React\nPengalaman Git",
            ]);

        $response->assertRedirect(route('bkk.admin.lowongan.index'));
        $this->assertDatabaseHas('lowongan', [
            'id' => $lowongan->id,
            'judul' => 'Senior Frontend Specialist',
        ]);
    }

    /**
     * 4. Test /bkk/admin/lowongan/{id}/siswa loads accepted applicants.
     */
    public function test_admin_lowongan_siswa_accepted_page_renders(): void
    {
        $this->fakeAuth('ADMIN', 'usr-admin-001');

        $lowongan = Lowongan::first();

        $response = $this->withHeader('Authorization', 'Bearer valid_token')
            ->get("/bkk/admin/lowongan/{$lowongan->id}/siswa");

        $response->assertStatus(200);
        $response->assertSeeText('Siswa Diterima & Terkonfirmasi PKL');
    }

    /**
     * 5. Test /bkk/admin/pkl/monitoring HTML renders successfully without $recentBeritas error.
     */
    public function test_admin_pkl_monitoring_page_renders_html_without_recent_beritas_error(): void
    {
        $this->fakeAuth('ADMIN', 'usr-admin-001');

        $response = $this->withHeader('Authorization', 'Bearer valid_token')
            ->get('/bkk/admin/pkl/monitoring');

        $response->assertStatus(200);
        $response->assertSeeText('Monitoring PKL & Validasi Laporan');
        $response->assertSee('Total Penempatan');
        $response->assertSee('Penempatan Siswa Aktif');
        $response->assertDontSee('$recentBeritas');
    }

    /**
     * 6. Test /bkk/admin/pkl/laporan-review alias route renders.
     */
    public function test_admin_pkl_laporan_review_page_renders_successfully(): void
    {
        $this->fakeAuth('ADMIN', 'usr-admin-001');

        $response = $this->withHeader('Authorization', 'Bearer valid_token')
            ->get('/bkk/admin/pkl/laporan-review');

        $response->assertStatus(200);
        $response->assertSee('Validasi Log Jurnal');
    }

    /**
     * 7. Test /bkk/admin/tracer-study HTML renders successfully without $recentBeritas error.
     */
    public function test_admin_tracer_study_page_renders_html_without_recent_beritas_error(): void
    {
        $this->fakeAuth('ADMIN', 'usr-admin-001');

        $response = $this->withHeader('Authorization', 'Bearer valid_token')
            ->get('/bkk/admin/tracer-study');

        $response->assertStatus(200);
        $response->assertSeeText('Rekapitulasi Tracer Study Alumni');
        $response->assertSee('Distribusi Pilar BMW Kemendikbud');
        $response->assertSee('Instrumen Kuesioner Tracer Study');
        $response->assertDontSee('$recentBeritas');
    }

    /**
     * 8. Test /bkk/admin/tracer-study/kuesioner stores new survey instrument.
     */
    public function test_admin_tracer_kuesioner_store(): void
    {
        $this->fakeAuth('ADMIN', 'usr-admin-001');

        $payload = [
            'judul' => 'Tracer Study Angkatan 2025',
            'tahun_sasaran_lulusan' => '2025',
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addMonths(6)->toDateString(),
            'deskripsi' => 'Survei berkala lulusan 2025.',
        ];

        $response = $this->withHeader('Authorization', 'Bearer valid_token')
            ->post('/bkk/admin/tracer-study/kuesioner', $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('tracer_kuesioner', [
            'judul' => 'Tracer Study Angkatan 2025',
            'tahun_sasaran_lulusan' => '2025',
        ]);
    }
}
