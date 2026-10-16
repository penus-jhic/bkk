<?php

namespace Tests\Feature;

use App\Models\CvResume;
use App\Models\GuruPembimbing;
use App\Models\Lamaran;
use App\Models\Lowongan;
use App\Models\Mitra;
use App\Models\PenempatanPkl;
use App\Models\ProfilSiswa;
use App\Services\JobMatchService;
use App\Support\DevAuth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Audit P0: identitas pelamar, skor kecocokan yang dihitung (bukan angka tetap), dan pembatasan guru ke siswa bimbingannya.
 */
class PklPembimbingAndMatchScoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app['env'] = 'local';
        config([
            'services.auth_service.dev_bypass' => true,
            'services.auth_service.dev_role' => 'ADMIN',
        ]);
        Http::fake();
    }

    protected function asRole(string $role)
    {
        return $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withCookie(DevAuth::COOKIE, $role);
    }

    protected function makeSiswa(string $userId, ?string $guruId, array $attributes = []): ProfilSiswa
    {
        return ProfilSiswa::create(array_merge([
            'user_id' => $userId,
            'nama_lengkap' => 'Siswa ' . $userId,
            'nis' => (string) random_int(1000000, 9999999),
            'jurusan' => 'Rekayasa Perangkat Lunak (RPL)',
            'angkatan' => '2024',
            'status_kelulusan' => 'SISWA_AKTIF',
            'guru_pembimbing_id' => $guruId,
        ], $attributes));
    }

    protected function makeMitra(): Mitra
    {
        return Mitra::create([
            'nama_perusahaan' => 'PT Uji Kecocokan',
            'npwp' => 'NPWP-' . Str::random(6),
            'password' => bcrypt('secret123'),
            'sektor_industri' => 'Teknologi Informasi',
            'alamat_kantor' => 'Jl. Uji No. 1',
            'kota' => 'Bogor',
            'email_perusahaan' => 'hr@uji.test',
            'no_telp_perusahaan' => '0251000000',
            'pic_name' => 'PIC Uji',
            'pic_role' => 'HR',
            'pic_phone' => '0812000000',
            'pic_email' => 'pic@uji.test',
            'is_verified' => true,
        ]);
    }

    protected function makeLowongan(Mitra $mitra, string $targetJurusan, array $persyaratan, string $tipe = 'PKL'): Lowongan
    {
        return Lowongan::create([
            'mitra_id' => $mitra->id,
            'judul' => 'Backend Developer Laravel',
            'slug' => 'lowongan-' . Str::random(8),
            'tipe' => $tipe,
            'tipe_badge' => $tipe,
            'target_jurusan' => $targetJurusan,
            'lokasi' => 'Bogor',
            'kuota' => 2,
            'deadline' => now()->addMonth()->toDateString(),
            'deskripsi' => 'Membangun API.',
            'persyaratan_json' => $persyaratan,
            'status' => 'Aktif',
        ]);
    }

    protected function makePenempatan(ProfilSiswa $siswa, Mitra $mitra, string $status = 'BERJALAN'): PenempatanPkl
    {
        return PenempatanPkl::create([
            'siswa_id' => $siswa->user_id,
            'mitra_id' => $mitra->id,
            'guru_pembimbing_id' => $siswa->guru_pembimbing_id,
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addMonths(3)->toDateString(),
            'status' => $status,
        ]);
    }

    public function test_guru_only_sees_and_acts_on_own_students(): void
    {
        $mitra = $this->makeMitra();
        $milikSendiri = $this->makeSiswa('usr-siswa-a', 'usr-guru-001', ['nama_lengkap' => 'Ani Bimbingan Sendiri']);
        $milikLain = $this->makeSiswa('usr-siswa-b', 'usr-guru-002', ['nama_lengkap' => 'Budi Bimbingan Lain']);
        $this->makePenempatan($milikSendiri, $mitra);
        $penempatanLain = $this->makePenempatan($milikLain, $mitra);

        $this->asRole('GURU')->get('/bkk/guru/siswa')
            ->assertOk()
            ->assertSee('Ani Bimbingan Sendiri')
            ->assertDontSee('Budi Bimbingan Lain');

        $this->asRole('GURU')->get("/bkk/guru/penempatan/{$penempatanLain->id}")->assertNotFound();
        $this->asRole('GURU')->get("/bkk/guru/siswa/{$milikLain->user_id}/cv")->assertNotFound();
        $this->asRole('GURU')
            ->post("/bkk/guru/penempatan/{$penempatanLain->id}/nilai", ['nilai_akhir_sekolah' => 90])
            ->assertNotFound();
        $this->assertNull($penempatanLain->fresh()->nilai_akhir_sekolah);

        // Admin tetap melihat semua siswa
        $this->asRole('ADMIN')->get('/bkk/guru/siswa')
            ->assertOk()
            ->assertSee('Ani Bimbingan Sendiri')
            ->assertSee('Budi Bimbingan Lain');
    }

    public function test_guru_is_registered_when_opening_portal(): void
    {
        $this->asRole('GURU')->get('/bkk/guru')->assertOk();

        $guru = GuruPembimbing::find('usr-guru-001');
        $this->assertNotNull($guru);
        $this->assertSame(DevAuth::PERSONAS['GURU']['nama_lengkap'], $guru->nama_lengkap);
    }

    public function test_admin_can_assign_pembimbing_and_open_pkl_follows(): void
    {
        GuruPembimbing::create(['user_id' => 'usr-guru-002', 'nama_lengkap' => 'Ibu Guru Baru']);
        $siswa = $this->makeSiswa('usr-siswa-c', null);
        $penempatan = $this->makePenempatan($siswa, $this->makeMitra(), 'MENUNGGU_PERSETUJUAN');

        $this->asRole('ADMIN')
            ->from('/bkk/guru/siswa')
            ->post("/bkk/guru/siswa/{$siswa->user_id}/pembimbing", ['guru_pembimbing_id' => 'usr-guru-002'])
            ->assertRedirect('/bkk/guru/siswa')
            ->assertSessionHas('success');

        $this->assertSame('usr-guru-002', $siswa->fresh()->guru_pembimbing_id);
        $this->assertSame('usr-guru-002', $penempatan->fresh()->guru_pembimbing_id);

        $this->asRole('ADMIN')
            ->post("/bkk/guru/siswa/{$siswa->user_id}/pembimbing", ['guru_pembimbing_id' => 'usr-tidak-ada'])
            ->assertSessionHasErrors('guru_pembimbing_id');

        // Guru tidak boleh mengatur pembimbing
        $this->asRole('GURU')
            ->post("/bkk/guru/siswa/{$siswa->user_id}/pembimbing", ['guru_pembimbing_id' => null])
            ->assertForbidden();
        $this->assertSame('usr-guru-002', $siswa->fresh()->guru_pembimbing_id);
    }

    public function test_pengajuan_from_student_without_pembimbing_waits_for_admin(): void
    {
        $this->asRole('SISWA')->post('/bkk/me/pkl/pengajuan', [
            'tipe_mitra' => 'MANDIRI',
            'nama_perusahaan_mandiri' => 'PT Tanpa Pembimbing',
            'unit_kerja_divisi' => 'IT Support',
            'tanggal_mulai' => now()->toDateString(),
            'tanggal_selesai' => now()->addMonths(3)->toDateString(),
        ])->assertSessionHas('success');

        $penempatan = PenempatanPkl::where('siswa_id', 'usr-siswa-001')->firstOrFail();
        $this->assertNull($penempatan->guru_pembimbing_id);

        $this->asRole('GURU')->get('/bkk/guru/pengajuan')->assertOk()->assertDontSee('PT Tanpa Pembimbing');
        $this->asRole('ADMIN')->get('/bkk/guru/pengajuan')->assertOk()->assertSee('PT Tanpa Pembimbing');
    }

    public function test_student_identity_is_synced_and_shown_to_mitra(): void
    {
        $this->asRole('SISWA')->get('/bkk/me')->assertOk();

        $profil = ProfilSiswa::findOrFail('usr-siswa-001');
        $this->assertSame(DevAuth::PERSONAS['SISWA']['nama_lengkap'], $profil->nama_lengkap);
        $this->assertSame(DevAuth::PERSONAS['SISWA']['email'], $profil->email);

        $mitra = $this->makeMitra();
        $lowongan = $this->makeLowongan($mitra, 'Rekayasa Perangkat Lunak (RPL)', ['Laravel']);
        $cv = CvResume::create(['user_id' => $profil->user_id, 'raw_markdown' => "# CV\n\nLaravel", 'is_main' => true]);
        $lamaran = Lamaran::create([
            'kode_lamaran' => 'LMR-' . Str::random(5),
            'lowongan_id' => $lowongan->id,
            'siswa_id' => $profil->user_id,
            'cv_id' => $cv->id,
            'status' => 'Terkirim',
            'tanggal_melamar' => now(),
            'skor_match_ai' => 77,
        ]);

        $this->withSession(['mitra_id' => $mitra->id])
            ->get("/bkk/dashboard/lowongan/{$lowongan->id}/pelamar")
            ->assertOk()
            ->assertSee(DevAuth::PERSONAS['SISWA']['nama_lengkap']);

        $this->withSession(['mitra_id' => $mitra->id])
            ->get("/bkk/dashboard/lowongan/{$lowongan->id}/pelamar/{$lamaran->id}")
            ->assertOk()
            ->assertSee(DevAuth::PERSONAS['SISWA']['email'])
            ->assertSee('Kecocokan: 77%');
    }

    public function test_lamaran_match_score_is_computed_from_jurusan_and_cv(): void
    {
        $siswa = $this->makeSiswa('usr-siswa-001', null);
        $markdown = "# Siswa RPL\n\nBackend developer.\n\n## Keahlian\n- Laravel\n- Git\n- PostgreSQL\n";
        CvResume::create(['user_id' => $siswa->user_id, 'raw_markdown' => $markdown, 'is_main' => true, 'temp' => false, 'score' => 80]);

        $mitra = $this->makeMitra();
        $cocok = $this->makeLowongan($mitra, 'Rekayasa Perangkat Lunak (RPL)', ['Menguasai Laravel', 'Terbiasa Git', 'PostgreSQL']);
        $beda = $this->makeLowongan($mitra, 'Desain Komunikasi Visual (DKV)', ['Adobe Illustrator', 'Figma', 'Photoshop']);

        $this->asRole('SISWA')->post("/bkk/me/daftar/{$cocok->id}")->assertSessionHas('success');
        $this->asRole('SISWA')->post("/bkk/me/daftar/{$beda->id}")->assertSessionHas('success');

        $skorCocok = Lamaran::where('lowongan_id', $cocok->id)->value('skor_match_ai');
        $skorBeda = Lamaran::where('lowongan_id', $beda->id)->value('skor_match_ai');

        // 50% skor CV (80) + 30% jurusan cocok (100) + 20% kata kunci (semua ada di CV) = 90
        $this->assertSame(90, $skorCocok);
        // DKV: jurusan tidak cocok, hanya kata kunci dari judul yang ada di CV
        $this->assertLessThan(50, $skorBeda);
        $this->assertGreaterThan($skorBeda, $skorCocok);
    }

    public function test_jurusan_codes_are_parsed_from_free_text(): void
    {
        $match = app(JobMatchService::class);

        $this->assertEqualsCanonicalizing(['TKJ', 'RPL'], $match->jurusanCodes('TKJ & RPL'));
        $this->assertSame(['MM'], $match->jurusanCodes('Desain Komunikasi Visual (DKV) / Multimedia'));
        $this->assertCount(5, $match->jurusanCodes('Semua Jurusan SMK Pelita Nusantara'));
        $this->assertSame([], $match->jurusanCodes('Belum diisi'));
    }
}
