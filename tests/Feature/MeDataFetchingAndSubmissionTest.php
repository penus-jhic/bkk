<?php

namespace Tests\Feature;

use App\Models\CvResume;
use App\Models\Lamaran;
use App\Models\Lowongan;
use App\Models\Mitra;
use App\Models\PenempatanPkl;
use App\Models\PklJurnalHarian;
use App\Models\PklLaporanAkhir;
use App\Models\ProfilSiswa;
use App\Models\TracerRespon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MeDataFetchingAndSubmissionTest extends TestCase
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
     * 1. Siswa tanpa data riil di DB tidak mereturn data tiruan (dummy fallback).
     */
    public function test_siswa_with_no_data_returns_empty_arrays_without_dummy_fallback(): void
    {
        // Login sebagai siswa baru tanpa record lamaran / pkl di DB
        $newStudentId = 'usr-siswa-new-999';
        $this->fakeAuth('SISWA', $newStudentId, 'Budi Santoso');

        $response = $this->withHeader('Authorization', 'Bearer token_budi')
            ->get('/bkk/me');

        $response->assertStatus(200);
        // Memastikan nama siswa riil terpasang dari auth user, bukan mock Ahmad Rizky Pratama
        $response->assertSee('Budi Santoso');
        $response->assertDontSee('Ahmad Rizky Pratama');

        // Memastikan empty state dirender jika tidak memiliki lamaran
        $response->assertSee('Belum Ada Lamaran Aktif');

        // Uji endpoint JSON
        $json = $this->withHeader('Authorization', 'Bearer token_budi')
            ->getJson('/bkk/me');

        $json->assertStatus(200);
        $this->assertEmpty($json->json('data.applications'));
    }

    /**
     * 2. Siswa tanpa jurnal menampilkan Empty State dan tidak memunculkan mock Telkom Akses.
     */
    public function test_siswa_jurnal_empty_state_without_mock_telkom(): void
    {
        $newStudentId = 'usr-siswa-new-888';
        $this->fakeAuth('SISWA', $newStudentId, 'Dewi Lestari');

        $response = $this->withHeader('Authorization', 'Bearer token_dewi')
            ->get('/bkk/me/jurnal');

        $response->assertStatus(200);
        $response->assertSee('Belum Ada Catatan Jurnal');
        $response->assertSee('Belum Ada Penempatan');
        // Tidak lagi memunculkan nama PT Telkom Akses tiruan
        $response->assertDontSee('PT Telkom Akses');
    }

    /**
     * 3. Pengiriman Log Jurnal via AJAX POST /bkk/me/jurnal tersimpan ke basis data riil.
     */
    public function test_siswa_jurnal_ajax_submission_persists_to_database(): void
    {
        $this->fakeAuth('SISWA', 'usr-siswa-001', 'Ahmad Rizky Pratama');

        $penempatan = PenempatanPkl::where('siswa_id', 'usr-siswa-001')->first();
        $this->assertNotNull($penempatan);

        $payload = [
            'tanggal' => now()->toDateString(),
            'aktivitas' => 'Konfigurasi switch manage dan pembuatan VLAN 10 & 20 untuk lab komputer.',
            'durasi_jam' => 8,
        ];

        $response = $this->withHeader('Authorization', 'Bearer valid_token')
            ->postJson('/bkk/me/jurnal', $payload);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Log aktivitas jurnal PKL harian berhasil disimpan!',
        ]);

        $this->assertDatabaseHas('pkl_jurnal_harian', [
            'siswa_id' => 'usr-siswa-001',
            'aktivitas' => 'Konfigurasi switch manage dan pembuatan VLAN 10 & 20 untuk lab komputer.',
            'durasi_jam' => 8,
        ]);
    }

    /**
     * 4. Pengunggahan draf bab laporan via AJAX POST /bkk/me/laporan tersimpan ke database.
     */
    public function test_siswa_laporan_ajax_submission_persists_to_database(): void
    {
        $this->fakeAuth('SISWA', 'usr-siswa-001', 'Ahmad Rizky Pratama');

        $payload = [
            'nomor_bab' => 3,
            'judul_bab' => 'BAB III — Pelaksanaan Praktik Kerja Lapangan di Industri IT',
        ];

        $response = $this->withHeader('Authorization', 'Bearer valid_token')
            ->postJson('/bkk/me/laporan', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Draf bab laporan PKL berhasil diperbarui untuk ditinjau guru pembimbing.',
        ]);

        $this->assertDatabaseHas('pkl_laporan_akhir', [
            'siswa_id' => 'usr-siswa-001',
            'nomor_bab' => 3,
            'judul_bab' => 'BAB III — Pelaksanaan Praktik Kerja Lapangan di Industri IT',
            'status' => 'Ditinjau',
        ]);
    }

    /**
     * 5. Pembaruan CV Markdown via AJAX POST /bkk/me/cv tersimpan ke database.
     */
    public function test_siswa_cv_update_persists_markdown_to_database(): void
    {
        $this->fakeAuth('SISWA', 'usr-siswa-001', 'Ahmad Rizky Pratama');

        $payload = [
            'markdown' => "# Ahmad Rizky Pratama\n\n## Ringkasan Profil\nJunior Web Developer berpengalaman dengan Laravel.",
        ];

        $response = $this->withHeader('Authorization', 'Bearer valid_token')
            ->postJson('/bkk/me/cv', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Konten CV ATS berhasil diperbarui!',
        ]);

        $this->assertDatabaseHas('cv_resumes', [
            'siswa_id' => 'usr-siswa-001',
            'konten_markdown' => "# Ahmad Rizky Pratama\n\n## Ringkasan Profil\nJunior Web Developer berpengalaman dengan Laravel.",
        ]);
    }

    /**
     * 6. Admin Dashboard menampilkan status modul 'Aktif' dan kartu ringkasan ekosistem riil.
     */
    public function test_admin_dashboard_renders_active_badges_and_recent_ecosystem_data(): void
    {
        Http::fake([
            '*/api/user/verify' => Http::response([
                'success' => true,
                'message' => 'Token terverifikasi',
                'data' => [
                    'id' => 'usr-admin-001',
                    'username' => 'admin_user',
                    'nama_lengkap' => 'Administrator BKK',
                    'nomor_induk' => 'ADM-001',
                    'role' => 'ADMIN',
                    'status_aktif' => true,
                    'email' => 'admin@smkpenus.sch.id',
                ],
            ], 200),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer valid_token')
            ->get('/bkk/admin');

        $response->assertStatus(200);

        // Memastikan status Kesiapan Modul BKK dilabeli Aktif dan tidak ada label 'Segera'
        $response->assertSee('Tracer Study Alumni');
        $response->assertSee('Mitra IDUKA');
        $response->assertSee('Lowongan Kerja BKK');
        $response->assertSee('Monitoring PKL Siswa');
        $response->assertDontSee('Segera');

        // Memastikan section aktivitas ekosistem BKK dirender
        $response->assertSee('Aktivitas Terkini Ekosistem BKK');
        $response->assertSee('Lowongan Baru');
        $response->assertSee('Mitra Industri');
        $response->assertSee('Monitoring PKL');
        $response->assertSee('Tracer Alumni');
    }

    /**
     * 7. Halaman Kemitraan Publik (/bkk/kerja-sama) menampilkan hitungan riil mitra terverifikasi.
     */
    public function test_public_kerjasama_renders_dynamic_mitra_counter(): void
    {
        $verifiedCount = Mitra::verified()->count();

        $response = $this->get('/bkk/kerja-sama');
        $response->assertStatus(200);

        // Memastikan counter membaca database riil
        $response->assertSee("{$verifiedCount}+");
        $response->assertSee('Mitra DUDI Aktif');
    }
}
