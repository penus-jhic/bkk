<?php

namespace Tests\Feature;

use App\Models\CvResume;
use App\Models\Lowongan;
use App\Models\ProfilSiswa;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CvVersioningAndMarkdownTest extends TestCase
{
    use RefreshDatabase;

    protected function fakeAuth(string $role = 'SISWA', string $id = 'usr-siswa-001', string $name = 'Ahmad Rizky Pratama'): void
    {
        Http::fake([
            '*/api/user/verify' => Http::response([
                'success' => true,
                'message' => 'Token terverifikasi',
                'data' => [
                    'id' => $id,
                    'username' => 'siswa_user',
                    'nama_lengkap' => $name,
                    'nomor_induk' => 'SISWA-001',
                    'role' => $role,
                    'status_aktif' => true,
                    'email' => 'siswa@smkpenus.sch.id',
                ],
            ], 200),
        ]);
    }

    /**
     * 1. Menyimpan CV menghasilkan record baru dengan is_main = true dan SHA-256 checksum otomatis.
     */
    public function test_cv_save_generates_sha256_checksum_and_sets_is_main(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->fakeAuth('SISWA', 'usr-siswa-001');

        $content = "# Ahmad Rizky Pratama\n\n## Ringkasan\nSiswa kompeten di bidang Laravel dan Postgres.";
        $expectedChecksum = hash('sha256', trim($content));

        $res = $this->withHeader('Authorization', 'Bearer valid')
            ->postJson(route('bkk.me.cv.update'), [
                'raw_markdown' => $content,
            ]);

        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'user_id' => 'usr-siswa-001',
                    'checksum' => $expectedChecksum,
                    'is_main' => true,
                ],
            ]);

        $this->assertDatabaseHas('cv_resumes', [
            'user_id' => 'usr-siswa-001',
            'checksum' => $expectedChecksum,
            'is_main' => true,
        ]);
    }

    /**
     * 2. Menyimpan konten identik tidak membuat duplikasi record baru (reusable checksum).
     */
    public function test_cv_save_identical_content_does_not_duplicate_record(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->fakeAuth('SISWA', 'usr-siswa-001');

        $activeCv = CvResume::where('user_id', 'usr-siswa-001')->where('is_main', true)->firstOrFail();
        $initialCount = CvResume::where('user_id', 'usr-siswa-001')->count();

        $res = $this->withHeader('Authorization', 'Bearer valid')
            ->postJson(route('bkk.me.cv.update'), [
                'raw_markdown' => $activeCv->raw_markdown,
            ]);

        $res->assertStatus(200);
        $this->assertEquals($initialCount, CvResume::where('user_id', 'usr-siswa-001')->count());
    }

    /**
     * 3. Menyimpan konten berbeda menonaktifkan is_main lama dan membuat versi baru sebagai is_main.
     */
    public function test_cv_save_modified_content_creates_new_version_and_deactivates_previous(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->fakeAuth('SISWA', 'usr-siswa-001');

        $oldMain = CvResume::where('user_id', 'usr-siswa-001')->where('is_main', true)->firstOrFail();
        $newContent = "# Versi Terbaru 2026\n\n## Pengalaman\nFullstack Engineer di PT Solusi.";

        $res = $this->withHeader('Authorization', 'Bearer valid')
            ->postJson(route('bkk.me.cv.update'), [
                'raw_markdown' => $newContent,
            ]);

        $res->assertStatus(200);

        // Record lama berubah jadi is_main = false
        $this->assertFalse($oldMain->fresh()->is_main);

        // Record baru ada dan is_main = true
        $newMain = CvResume::where('user_id', 'usr-siswa-001')->where('is_main', true)->firstOrFail();
        $this->assertNotEquals($oldMain->id, $newMain->id);
        $this->assertEquals(hash('sha256', trim($newContent)), $newMain->checksum);
    }

    /**
     * 4. Memulihkan versi lama menjadi CV Utama (is_main = true) via endpoint cv.setMain.
     */
    public function test_cv_set_main_activates_selected_version(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->fakeAuth('SISWA', 'usr-siswa-001');

        $inactiveVersion = CvResume::where('user_id', 'usr-siswa-001')->where('is_main', false)->firstOrFail();
        $currentMain = CvResume::where('user_id', 'usr-siswa-001')->where('is_main', true)->firstOrFail();

        $res = $this->withHeader('Authorization', 'Bearer valid')
            ->postJson(route('bkk.me.cv.setMain', $inactiveVersion->id));

        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $inactiveVersion->id,
                    'is_main' => true,
                ],
            ]);

        $this->assertTrue($inactiveVersion->fresh()->is_main);
        $this->assertFalse($currentMain->fresh()->is_main);
    }

    /**
     * 5. Melamar pekerjaan otomatis melampirkan versi CV Utama yang aktif.
     */
    public function test_application_submission_attaches_active_main_cv(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->fakeAuth('SISWA', 'usr-siswa-001');

        $mainCv = CvResume::where('user_id', 'usr-siswa-001')->where('is_main', true)->firstOrFail();
        $lowongan = Lowongan::terbuka()
            ->whereDoesntHave('lamarans', fn ($q) => $q->where('siswa_id', 'usr-siswa-001'))
            ->firstOrFail();

        $res = $this->withHeader('Authorization', 'Bearer valid')
            ->postJson(route('bkk.me.daftar', $lowongan->slug));

        $res->assertStatus(201);

        $this->assertDatabaseHas('lamaran', [
            'siswa_id' => 'usr-siswa-001',
            'lowongan_id' => $lowongan->id,
            'cv_id' => $mainCv->id,
        ]);
    }

    /**
     * 6. Endpoint /bkk/me/cv/{id}/edit membuka versi spesifik dengan konten terkait.
     */
    public function test_cv_edit_version_endpoint_loads_specific_archive_version(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->fakeAuth('SISWA', 'usr-siswa-001');

        $inactiveVersion = CvResume::where('user_id', 'usr-siswa-001')->where('is_main', false)->firstOrFail();

        $res = $this->withHeader('Authorization', 'Bearer valid')
            ->getJson(route('bkk.me.cv.editVersion', $inactiveVersion->id));

        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $inactiveVersion->id,
                    'is_main' => false,
                    'checksum' => $inactiveVersion->checksum,
                ],
            ]);
    }

    /**
     * 7. Endpoint /bkk/me/cv/{id}/edit mengembalikan 404 jika ID bukan milik siswa bersangkutan.
     */
    public function test_cv_edit_version_endpoint_rejects_unowned_or_nonexistent_version(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->fakeAuth('SISWA', 'usr-siswa-001');

        $res = $this->withHeader('Authorization', 'Bearer valid')
            ->getJson(route('bkk.me.cv.editVersion', 999999));

        $res->assertStatus(404);
    }
}
