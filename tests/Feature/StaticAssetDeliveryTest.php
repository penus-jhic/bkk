<?php

namespace Tests\Feature;

use App\Models\Mitra;
use App\Models\PklLaporanAkhir;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaticAssetDeliveryTest extends TestCase
{
    /**
     * Pastikan gambar di public/images dapat diakses via endpoint /bkk/images/{path}
     */
    public function test_can_serve_image_asset_under_bkk_endpoint(): void
    {
        $response = $this->get('/bkk/images/logosmkpenus.png');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertStringContainsString('public', $response->headers->get('Cache-Control'));
    }

    /**
     * Pastikan upaya directory traversal pada endpoint gambar ditolak dengan 404
     */
    public function test_path_traversal_is_blocked_on_images(): void
    {
        $response = $this->get('/bkk/images/../../config/app.php');
        $response->assertStatus(404);

        $responseEnc = $this->get('/bkk/images/..%2F..%2Fconfig%2Fapp.php');
        $responseEnc->assertStatus(404);
    }

    /**
     * Pastikan request file yang tidak ada mengembalikan 404
     */
    public function test_non_existent_image_returns_404(): void
    {
        $response = $this->get('/bkk/images/tidak-ada-file-xyz-987.png');
        $response->assertStatus(404);
    }

    /**
     * Pastikan file di public/uploads dapat diakses via endpoint /bkk/uploads/{path}
     */
    public function test_can_serve_upload_asset(): void
    {
        $uploadDir = public_path('uploads/testing');
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $testFile = $uploadDir . '/dummy.pdf';
        file_put_contents($testFile, '%PDF-1.4 dummy content');

        try {
            $response = $this->get('/bkk/uploads/testing/dummy.pdf');
            $response->assertStatus(200);
            $response->assertHeader('Content-Type', 'application/pdf');
        } finally {
            if (file_exists($testFile)) {
                unlink($testFile);
            }
            if (file_exists($uploadDir)) {
                rmdir($uploadDir);
            }
        }
    }

    /**
     * Pastikan file di storage disk public dapat diakses via /bkk/storage/{path}
     */
    public function test_can_serve_storage_asset(): void
    {
        Storage::disk('public')->put('testing/test_doc.pdf', '%PDF-1.4 public storage content');

        try {
            $response = $this->get('/bkk/storage/testing/test_doc.pdf');
            $response->assertStatus(200);
            $response->assertHeader('Content-Type', 'application/pdf');
        } finally {
            Storage::disk('public')->delete('testing/test_doc.pdf');
            Storage::disk('public')->deleteDirectory('testing');
        }
    }

    /**
     * Pastikan helper asset() menghasilkan URL dengan prefix /bkk
     */
    public function test_asset_helper_generates_bkk_prefix(): void
    {
        $url = asset('images/logosmkpenus.png');

        $this->assertStringContainsString('/bkk/images/logosmkpenus.png', $url);
    }

    /**
     * Pastikan Storage::url() menghasilkan URL dengan prefix /bkk/storage
     */
    public function test_storage_url_generates_bkk_storage_prefix(): void
    {
        $url = Storage::disk('public')->url('berita/sample.jpg');

        $this->assertStringContainsString('/bkk/storage/berita/sample.jpg', $url);
    }

    /**
     * Pastikan accessor logoUrl pada model Mitra menormalisasi path uploads
     */
    public function test_mitra_logo_url_accessor_normalizes_uploads_prefix(): void
    {
        $mitraLegacy = new Mitra(['logo_url' => '/uploads/mitra_logos/pt_abc.png']);
        $this->assertEquals('/bkk/uploads/mitra_logos/pt_abc.png', $mitraLegacy->logo_url);

        $mitraRelative = new Mitra(['logo_url' => 'uploads/mitra_logos/pt_abc.png']);
        $this->assertEquals('/bkk/uploads/mitra_logos/pt_abc.png', $mitraRelative->logo_url);

        $mitraPrefixed = new Mitra(['logo_url' => '/bkk/uploads/mitra_logos/pt_abc.png']);
        $this->assertEquals('/bkk/uploads/mitra_logos/pt_abc.png', $mitraPrefixed->logo_url);

        $mitraExternal = new Mitra(['logo_url' => 'https://images.unsplash.com/logo.png']);
        $this->assertEquals('https://images.unsplash.com/logo.png', $mitraExternal->logo_url);
    }

    /**
     * Pastikan accessor fileDraftUrl pada model PklLaporanAkhir menormalisasi path uploads
     */
    public function test_pkl_laporan_file_draft_url_accessor_normalizes_uploads_prefix(): void
    {
        $laporanLegacy = new PklLaporanAkhir(['file_draft_url' => '/uploads/laporan/bab-1.pdf']);
        $this->assertEquals('/bkk/uploads/laporan/bab-1.pdf', $laporanLegacy->file_draft_url);

        $laporanPrefixed = new PklLaporanAkhir(['file_draft_url' => '/bkk/uploads/laporan/bab-1.pdf']);
        $this->assertEquals('/bkk/uploads/laporan/bab-1.pdf', $laporanPrefixed->file_draft_url);

        $laporanNull = new PklLaporanAkhir(['file_draft_url' => null]);
        $this->assertNull($laporanNull->file_draft_url);
    }
}
