<?php

namespace Tests\Feature;

use App\Models\Berita;
use App\Models\KategoriBerita;
use App\Models\Lamaran;
use App\Models\Lowongan;
use App\Models\Mitra;
use App\Models\ProfilSiswa;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicDataFetchingTest extends TestCase
{
    use RefreshDatabase;

    protected function createTestMitra(string $nama, string $npwp): Mitra
    {
        return Mitra::create([
            'nama_perusahaan' => $nama,
            'npwp' => $npwp,
            'password' => bcrypt('password123'),
            'sektor_industri' => 'Teknologi Informasi',
            'alamat_kantor' => 'Jl. Tegar Beriman No. 1',
            'kota' => 'Cibinong, Bogor',
            'email_perusahaan' => 'kontak@' . strtolower(preg_replace('/[^a-z0-9]/i', '', $nama)) . '.com',
            'no_telp_perusahaan' => '021-87654321',
            'is_verified' => true,
            'pic_name' => 'Koordinator Industri',
            'pic_role' => 'HRD',
            'pic_email' => 'hrd@' . strtolower(preg_replace('/[^a-z0-9]/i', '', $nama)) . '.com',
            'pic_phone' => '081234567890',
        ]);
    }

    protected function createTestLowongan(Mitra $mitra, array $attributes = []): Lowongan
    {
        $tipe = $attributes['tipe'] ?? 'PKL';
        return Lowongan::create(array_merge([
            'mitra_id' => $mitra->id,
            'judul' => 'Lowongan Test ' . uniqid(),
            'slug' => 'lowongan-test-' . uniqid(),
            'tipe' => $tipe,
            'tipe_badge' => $tipe === 'PKL' ? 'PKL Siswa' : 'Full-Time Lulusan',
            'target_jurusan' => 'Rekayasa Perangkat Lunak (RPL)',
            'lokasi' => 'Bogor',
            'kuota' => 2,
            'deadline' => now()->addDays(30)->toDateString(),
            'deskripsi' => 'Deskripsi lowongan test untuk verifikasi fetching data.',
            'persyaratan_json' => ['Siswa/Alumni SMK', 'Kompeten di bidangnya'],
            'benefit_json' => ['Uang saku', 'Sertifikat'],
            'status' => 'Aktif',
        ], $attributes));
    }

    /**
     * 1. Beranda (/bkk) mem-fetch dan me-render 3 berita terbaru & 4 lowongan aktif dari database.
     */
    public function test_landpage_fetches_and_renders_database_berita_and_lowongan(): void
    {
        $kategori = KategoriBerita::create([
            'nama' => 'Agenda & Event',
            'slug' => 'agenda-event',
        ]);

        // Buat 4 berita terbit
        for ($i = 1; $i <= 4; $i++) {
            Berita::create([
                'judul' => "Berita DB Publik Ke-{$i}",
                'slug' => "berita-db-publik-ke-{$i}",
                'kategori_id' => $kategori->id,
                'ringkasan' => "Ringkasan berita ke-{$i} langsung dari DB.",
                'konten' => "## Konten Berita {$i}",
                'penulis_nama' => 'Humas BKK Penus',
                'status' => 'PUBLISHED',
                'published_at' => now()->addMinutes($i),
            ]);
        }

        $mitra = $this->createTestMitra('PT Teknologi Unggul Mitra', '12.345.678.9-001.000');

        // Buat 5 lowongan aktif
        for ($j = 1; $j <= 5; $j++) {
            $this->createTestLowongan($mitra, [
                'judul' => "Lowongan Posisi {$j} DB",
                'slug' => "lowongan-posisi-{$j}-db",
                'tipe' => $j % 2 === 0 ? 'PKL' : 'Kerja',
                'target_jurusan' => 'Rekayasa Perangkat Lunak (RPL)',
                'lokasi' => 'Bogor',
                'gaji_kompensasi' => 'Rp 5.000.000',
                'created_at' => now()->addMinutes($j),
            ]);
        }

        $response = $this->get('/bkk');
        $response->assertStatus(200);

        // Hanya 3 berita terbaru (Ke-4, Ke-3, Ke-2) yang tampil, berita ke-1 tidak tampil di preview
        $response->assertSee('Berita DB Publik Ke-4');
        $response->assertSee('Berita DB Publik Ke-3');
        $response->assertSee('Berita DB Publik Ke-2');
        $response->assertDontSee('Berita DB Publik Ke-1');

        // Hanya 4 lowongan terbaru (Posisi 5, 4, 3, 2) yang tampil, posisi 1 tidak tampil di preview
        $response->assertSee('Lowongan Posisi 5 DB');
        $response->assertSee('Lowongan Posisi 4 DB');
        $response->assertSee('Lowongan Posisi 3 DB');
        $response->assertSee('Lowongan Posisi 2 DB');
        $response->assertDontSee('Lowongan Posisi 1 DB');

        // Memastikan nama mitra dari relasi Eloquent ter-render
        $response->assertSee('PT Teknologi Unggul Mitra');
    }

    /**
     * 2. Beranda (/bkk) menampilkan Empty State saat DB kosong tanpa data dummy fallback.
     */
    public function test_landpage_renders_empty_state_when_no_data(): void
    {
        $response = $this->get('/bkk');
        $response->assertStatus(200);

        // Menampilkan pesan Empty State
        $response->assertSee('Belum Ada Lowongan Aktif');
        $response->assertSee('Belum Ada Berita Terbaru');

        // Memastikan data mock/dummy statis lama tidak lagi muncul
        $response->assertDontSee('Junior Web Developer (Laravel & React)');
        $response->assertDontSee('PT Solusi Teknologi Nusantara');
        $response->assertDontSee('Job Fair Akbar SMK Plus Pelita Nusantara 2025');
    }

    /**
     * 3. Halaman katalog berita (/bkk/berita) dengan kategori, pencarian, dan paginasi.
     */
    public function test_public_berita_catalog_with_categories_and_search(): void
    {
        $katAgenda = KategoriBerita::create(['nama' => 'Agenda BKK', 'slug' => 'agenda-bkk']);
        $katTips = KategoriBerita::create(['nama' => 'Tips Karir', 'slug' => 'tips-karir']);

        Berita::create([
            'judul' => 'Job Fair Akbar Tahunan 2026',
            'slug' => 'job-fair-akbar-tahunan-2026',
            'kategori_id' => $katAgenda->id,
            'ringkasan' => 'Pelaksanaan bursa kerja.',
            'konten' => 'Detail agenda job fair.',
            'penulis_nama' => 'Ketua BKK',
            'status' => 'PUBLISHED',
            'is_featured' => true,
            'published_at' => now(),
        ]);

        Berita::create([
            'judul' => 'Tips Wawancara HRD Industri',
            'slug' => 'tips-wawancara-hrd-industri',
            'kategori_id' => $katTips->id,
            'ringkasan' => 'Teknik menjawab pertanyaan psikotes.',
            'konten' => 'Detail tips interview.',
            'penulis_nama' => 'Guru BK',
            'status' => 'PUBLISHED',
            'published_at' => now(),
        ]);

        // Akses katalog umum
        $resAll = $this->get('/bkk/berita');
        $resAll->assertStatus(200);
        $resAll->assertSee('Job Fair Akbar Tahunan 2026');
        $resAll->assertSee('Tips Wawancara HRD Industri');
        $resAll->assertSee('Agenda BKK');
        $resAll->assertSee('Tips Karir');

        // Filter per kategori
        $resKat = $this->get('/bkk/berita?kategori=tips-karir');
        $resKat->assertStatus(200);
        $resKat->assertSee('Tips Wawancara HRD Industri');
        $resKat->assertDontSee('Job Fair Akbar Tahunan 2026');

        // Filter pencarian kata kunci
        $resSearch = $this->get('/bkk/berita?q=Psikotes');
        $resSearch->assertStatus(200);
        $resSearch->assertSee('Tips Wawancara HRD Industri');
    }

    /**
     * 4. Detail berita (/bkk/berita/{id_berita}) me-render data via slug dan numeric id.
     */
    public function test_public_berita_detail_by_slug_and_id(): void
    {
        $kategori = KategoriBerita::create(['nama' => 'Kemitraan DUDI', 'slug' => 'kemitraan-dudi']);

        $berita = Berita::create([
            'judul' => 'MoU Baru dengan Industri Otomasi',
            'slug' => 'mou-baru-dengan-industri-otomasi',
            'kategori_id' => $kategori->id,
            'ringkasan' => 'Penandatanganan kerja sama magang.',
            'konten' => "## Ruang Lingkup Kerjasama\n\n- Prakerin 6 Bulan\n- Sertifikasi Teknis",
            'penulis_nama' => 'Kepala Hubin',
            'penulis_jabatan' => 'Koordinator Kemitraan',
            'status' => 'PUBLISHED',
            'published_at' => now(),
        ]);

        // Akses via slug
        $resSlug = $this->get("/bkk/berita/{$berita->slug}");
        $resSlug->assertStatus(200);
        $resSlug->assertSee('MoU Baru dengan Industri Otomasi');
        $resSlug->assertSee('Ruang Lingkup Kerjasama');
        $resSlug->assertSee('Prakerin 6 Bulan');

        $this->assertEquals(1, $berita->fresh()->views_count);

        // Akses via ID
        $resId = $this->get("/bkk/berita/{$berita->id}");
        $resId->assertStatus(200);
        $resId->assertSee('MoU Baru dengan Industri Otomasi');

        $this->assertEquals(2, $berita->fresh()->views_count);
    }

    /**
     * 5. Katalog lowongan (/bkk/lowongan) dengan filter tipe, jurusan, dan lokasi.
     */
    public function test_public_lowongan_catalog_with_filters(): void
    {
        $mitra = $this->createTestMitra('PT Mitra Sejahtera Bersama', '99.888.777.6-000.000');

        $this->createTestLowongan($mitra, [
            'judul' => 'Teknisi Jaringan Fiber Optik',
            'slug' => 'teknisi-jaringan-fiber-optik',
            'tipe' => 'PKL',
            'target_jurusan' => 'Teknik Komputer & Jaringan (TKJ)',
            'lokasi' => 'Cibinong, Bogor',
        ]);

        $this->createTestLowongan($mitra, [
            'judul' => 'Fullstack Web Developer Lulusan',
            'slug' => 'fullstack-web-developer-lulusan',
            'tipe' => 'Kerja',
            'target_jurusan' => 'Rekayasa Perangkat Lunak (RPL)',
            'lokasi' => 'Jakarta Selatan',
        ]);

        // Katalog umum
        $resAll = $this->get('/bkk/lowongan');
        $resAll->assertStatus(200);
        $resAll->assertSee('Teknisi Jaringan Fiber Optik');
        $resAll->assertSee('Fullstack Web Developer Lulusan');

        // Filter tipe PKL
        $resPkl = $this->get('/bkk/lowongan?tipe=pkl');
        $resPkl->assertStatus(200);
        $resPkl->assertSee('Teknisi Jaringan Fiber Optik');
        $resPkl->assertDontSee('Fullstack Web Developer Lulusan');

        // Filter jurusan RPL
        $resJurusan = $this->get('/bkk/lowongan?jurusan=RPL');
        $resJurusan->assertStatus(200);
        $resJurusan->assertSee('Fullstack Web Developer Lulusan');
        $resJurusan->assertDontSee('Teknisi Jaringan Fiber Optik');

        // Filter lokasi Bogor
        $resLokasi = $this->get('/bkk/lowongan?lokasi=bogor');
        $resLokasi->assertStatus(200);
        $resLokasi->assertSee('Teknisi Jaringan Fiber Optik');
        $resLokasi->assertDontSee('Fullstack Web Developer Lulusan');
    }

    /**
     * 6. Detail lowongan (/bkk/lowongan/{id_lowongan}) via slug, id, serta penanganan 404 / tidak ditemukan.
     */
    public function test_public_lowongan_detail_by_slug_and_id(): void
    {
        $mitra = $this->createTestMitra('PT Solusi Otomasi Prima', '44.333.222.1-111.000');

        $lowongan = $this->createTestLowongan($mitra, [
            'judul' => 'Operator PLC dan Robotika Industri',
            'slug' => 'operator-plc-dan-robotika-industri',
            'tipe' => 'PKL',
            'target_jurusan' => 'Teknik Otomasi Industri (TOI)',
            'lokasi' => 'Kawasan Industri Sentul, Bogor',
            'gaji_kompensasi' => 'Uang Saku Harian & Transport',
            'kuota' => 4,
            'persyaratan_json' => ['Siswa aktif kelas XI atau XII TOI', 'Menguasai dasar ladder diagram PLC'],
            'benefit_json' => ['Sertifikat industri', 'Akomodasi mes'],
        ]);

        // Akses via slug
        $resSlug = $this->get("/bkk/lowongan/{$lowongan->slug}");
        $resSlug->assertStatus(200);
        $resSlug->assertSee('Operator PLC dan Robotika Industri');
        $resSlug->assertSee('PT Solusi Otomasi Prima');
        $resSlug->assertSee('Menguasai dasar ladder diagram PLC');
        $resSlug->assertSee('Sertifikat industri');

        $this->assertEquals(1, $lowongan->fresh()->views_count);

        // Akses via numeric ID
        $resId = $this->get("/bkk/lowongan/{$lowongan->id}");
        $resId->assertStatus(200);
        $resId->assertSee('Operator PLC dan Robotika Industri');

        $this->assertEquals(2, $lowongan->fresh()->views_count);

        // Akses slug tidak ditemukan
        $resNotFound = $this->get('/bkk/lowongan/lowongan-palsu-tidak-ada');
        $resNotFound->assertStatus(200);
        $resNotFound->assertSee('Tidak Ditemukan');
        $resNotFound->assertSee('Lowongan yang Anda cari sudah ditutup');
    }
}
