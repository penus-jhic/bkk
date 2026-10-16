<?php

namespace Tests\Feature;

use App\Models\Berita;
use App\Models\KategoriBerita;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BeritaModuleTest extends TestCase
{
    use RefreshDatabase;

    protected function fakeAuthAdmin(): void
    {
        Http::fake([
            '*/api/user/verify' => Http::response([
                'success' => true,
                'message' => 'Token terverifikasi',
                'data' => [
                    'id' => 'admin-test-id',
                    'username' => 'admin',
                    'nama_lengkap' => 'Administrator IT',
                    'role' => 'ADMIN',
                    'status_aktif' => true,
                ],
            ], 200),
        ]);
    }

    /**
     * Test landpage /bkk fetches and displays the latest 3 published articles.
     */
    public function test_landpage_fetches_latest_3_berita(): void
    {
        $kategori = KategoriBerita::create([
            'nama' => 'Agenda & Event',
            'slug' => 'agenda-event',
        ]);

        for ($i = 1; $i <= 5; $i++) {
            Berita::create([
                'judul' => "Berita Ke-{$i} Terbaru",
                'slug' => "berita-ke-{$i}-terbaru",
                'kategori_id' => $kategori->id,
                'ringkasan' => "Ringkasan berita ke-{$i}.",
                'konten' => "## Konten Berita {$i}",
                'penulis_nama' => 'Humas BKK',
                'status' => 'PUBLISHED',
                'published_at' => now()->addMinutes($i),
            ]);
        }

        $response = $this->get('/bkk');
        $response->assertStatus(200);

        // Hanya 3 berita terbaru (Ke-5, Ke-4, Ke-3) yang tampil di Section 4
        $response->assertSee('Berita Ke-5 Terbaru');
        $response->assertSee('Berita Ke-4 Terbaru');
        $response->assertSee('Berita Ke-3 Terbaru');
        $response->assertDontSee('Berita Ke-1 Terbaru');
    }

    /**
     * Test public catalog renders categories and published news articles.
     */
    public function test_public_berita_catalog_renders_successfully(): void
    {
        $kategori = KategoriBerita::create([
            'nama' => 'Tips Karier',
            'slug' => 'tips-karier',
        ]);

        Berita::create([
            'judul' => 'Tips Sukses Interview Kerja',
            'slug' => 'tips-sukses-interview-kerja',
            'kategori_id' => $kategori->id,
            'ringkasan' => 'Panduan menghadapi wawancara.',
            'konten' => "## Langkah Awal\nPersiapkan diri dengan baik.",
            'penulis_nama' => 'Konselor Karier',
            'status' => 'PUBLISHED',
        ]);

        $response = $this->get('/bkk/berita');
        $response->assertStatus(200);
        $response->assertSee('Tips Sukses Interview Kerja');
        $response->assertSee('Tips Karier');
    }

    /**
     * Test public detail renders markdown content into HTML.
     */
    public function test_public_berita_detail_renders_with_markdown_content(): void
    {
        $kategori = KategoriBerita::create([
            'nama' => 'Tips Karier',
            'slug' => 'tips-karier',
        ]);

        $berita = Berita::create([
            'judul' => 'Panduan Markdown BKK',
            'slug' => 'panduan-markdown-bkk',
            'kategori_id' => $kategori->id,
            'ringkasan' => 'Contoh artikel markdown.',
            'konten' => "## Subjudul Penting\n\n* Poin Pertama\n* Poin Kedua\n\n> Kutipan motivasi kerja.",
            'penulis_nama' => 'Ibu Sri Rahayu',
            'status' => 'PUBLISHED',
        ]);

        $response = $this->get('/bkk/berita/' . $berita->slug);
        $response->assertStatus(200);
        $response->assertSee('Panduan Markdown BKK');
        // Pastikan markdown di-render ke HTML tag (h2, li, blockquote)
        $response->assertSee('Subjudul Penting');
        $response->assertSee('Poin Pertama');
        $response->assertSee('Kutipan motivasi kerja.');
        
        // Verifikasi views_count naik
        $this->assertEquals(1, $berita->fresh()->views_count);
    }

    /**
     * Test unauthenticated access to /bkk/admin/berita returns 401.
     */
    public function test_admin_berita_routes_require_authentication(): void
    {
        $response = $this->getJson('/bkk/admin/berita');
        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Token otentikasi tidak ditemukan',
            ]);
    }

    /**
     * Test authenticated admin can view the berita index and dashboard.
     */
    public function test_authenticated_admin_can_view_dashboard_and_berita_index(): void
    {
        $this->fakeAuthAdmin();

        $kategori = KategoriBerita::create([
            'nama' => 'Agenda & Event',
            'slug' => 'agenda-event',
        ]);

        Berita::create([
            'judul' => 'Job Fair Akbar 2025',
            'slug' => 'job-fair-akbar-2025',
            'kategori_id' => $kategori->id,
            'ringkasan' => 'Pameran kerja tahunan.',
            'konten' => '## Info Job Fair',
            'penulis_nama' => 'Tim Humas',
            'status' => 'PUBLISHED',
        ]);

        // Dashboard
        $resDashboard = $this->withHeader('Authorization', 'Bearer valid_token')
            ->get('/bkk/admin');
        $resDashboard->assertStatus(200);
        $resDashboard->assertSee('Dashboard Admin BKK');
        $resDashboard->assertSee('Job Fair Akbar 2025');

        // Berita Index
        $resIndex = $this->withHeader('Authorization', 'Bearer valid_token')
            ->get('/bkk/admin/berita');
        $resIndex->assertStatus(200);
        $resIndex->assertSeeText('Kelola Berita & Agenda BKK');
        $resIndex->assertSee('Job Fair Akbar 2025');
    }

    /**
     * Test admin can view create form.
     */
    public function test_admin_can_view_create_form(): void
    {
        $this->fakeAuthAdmin();

        $response = $this->withHeader('Authorization', 'Bearer valid_token')
            ->get('/bkk/admin/berita/new');

        $response->assertStatus(200);
        $response->assertSee('Tulis Berita Baru');
        $response->assertSee('Konten Utama (Markdown Editor)');
    }

    /**
     * Test admin can store new berita article with Markdown content.
     */
    public function test_admin_can_create_new_berita(): void
    {
        $this->fakeAuthAdmin();

        $kategori = KategoriBerita::create([
            'nama' => 'Prestasi Siswa',
            'slug' => 'prestasi-siswa',
        ]);

        $payload = [
            'judul' => 'Juara LKS Tingkat Provinsi',
            'slug' => 'juara-lks-tingkat-provinsi',
            'kategori_id' => $kategori->id,
            'ringkasan' => 'Siswa RPL meraih medali emas.',
            'konten' => "## Prestasi Membanggakan\n\nSelamat kepada tim yang berhasil meraih juara 1.",
            'penulis_nama' => 'Kepala Kompetensi Keahlian',
            'penulis_jabatan' => 'Guru RPL',
            'status' => 'PUBLISHED',
            'is_featured' => '1',
        ];

        $response = $this->withHeader('Authorization', 'Bearer valid_token')
            ->post('/bkk/admin/berita', $payload);

        $response->assertRedirect('/bkk/admin/berita');
        $this->assertDatabaseHas('berita', [
            'judul' => 'Juara LKS Tingkat Provinsi',
            'slug' => 'juara-lks-tingkat-provinsi',
            'is_featured' => true,
            'status' => 'PUBLISHED',
        ]);
    }

    /**
     * Test admin can update an existing berita article.
     */
    public function test_admin_can_update_berita(): void
    {
        $this->fakeAuthAdmin();

        $kategori = KategoriBerita::create([
            'nama' => 'Kemitraan',
            'slug' => 'kemitraan',
        ]);

        $berita = Berita::create([
            'judul' => 'Judul Lama',
            'slug' => 'judul-lama',
            'kategori_id' => $kategori->id,
            'ringkasan' => 'Ringkasan lama.',
            'konten' => '## Konten Lama',
            'penulis_nama' => 'Penulis Lama',
            'status' => 'DRAFT',
        ]);

        // Edit view
        $resEdit = $this->withHeader('Authorization', 'Bearer valid_token')
            ->get('/bkk/admin/berita/' . $berita->id);
        $resEdit->assertStatus(200);
        $resEdit->assertSee('Judul Lama');

        // Update action
        $updatePayload = [
            'judul' => 'Judul Baru Diperbarui',
            'slug' => 'judul-baru-diperbarui',
            'kategori_id' => $kategori->id,
            'ringkasan' => 'Ringkasan baru.',
            'konten' => '## Konten Baru yang Diperbarui',
            'penulis_nama' => 'Penulis Baru',
            'status' => 'PUBLISHED',
        ];

        $resUpdate = $this->withHeader('Authorization', 'Bearer valid_token')
            ->put('/bkk/admin/berita/' . $berita->id, $updatePayload);

        $resUpdate->assertRedirect('/bkk/admin/berita');
        $this->assertDatabaseHas('berita', [
            'id' => $berita->id,
            'judul' => 'Judul Baru Diperbarui',
            'status' => 'PUBLISHED',
        ]);
    }

    /**
     * Test admin can delete an existing berita article.
     */
    public function test_admin_can_delete_berita(): void
    {
        $this->fakeAuthAdmin();

        $kategori = KategoriBerita::create([
            'nama' => 'Peluang Kerja',
            'slug' => 'peluang-kerja',
        ]);

        $berita = Berita::create([
            'judul' => 'Berita yang akan dihapus',
            'slug' => 'berita-akan-dihapus',
            'kategori_id' => $kategori->id,
            'ringkasan' => 'Ringkasan.',
            'konten' => '## Konten.',
            'penulis_nama' => 'Penulis',
            'status' => 'PUBLISHED',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer valid_token')
            ->delete('/bkk/admin/berita/' . $berita->id);

        $response->assertRedirect('/bkk/admin/berita');
        $this->assertDatabaseMissing('berita', [
            'id' => $berita->id,
        ]);
    }
}
