<?php

namespace Tests\Feature;

use App\Models\CvResume;
use App\Models\Lamaran;
use App\Models\Lowongan;
use App\Models\Mitra;
use App\Models\PenempatanPkl;
use App\Models\PklJurnalHarian;
use App\Models\ProfilSiswa;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Regresi untuk bug yang ditemukan pada audit (status lamaran, lamaran tanpa CV,
 * validasi status admin, verifikasi mitra, jam PKL, lowongan kedaluwarsa, dll).
 */
class BugFixRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    protected function fakeAuth(string $role = 'SISWA', string $id = 'usr-siswa-001'): void
    {
        Http::fake([
            '*/api/user/verify' => Http::response([
                'success' => true,
                'data' => [
                    'id' => $id,
                    'nama_lengkap' => 'User Test',
                    'nomor_induk' => 'TEST-999',
                    'role' => $role,
                    'status_aktif' => true,
                ],
            ], 200),
        ]);
    }

    public function test_mitra_can_set_enum_status_and_invalid_status_is_rejected_without_500(): void
    {
        $lamaran = Lamaran::with('lowongan')->firstOrFail();
        $url = route('bkk.mitra.pelamar.updateStatus', [$lamaran->lowongan_id, $lamaran->id]);
        $session = ['mitra_id' => $lamaran->lowongan->mitra_id];

        $this->withSession($session)->post($url, ['status' => 'Dipanggil Interview'])
            ->assertRedirect();
        $this->assertSame('Dipanggil Interview', $lamaran->fresh()->status);

        // Nilai lama yang tidak ada di enum lamaran.status sebelumnya memicu error 500
        $this->withSession($session)->post($url, ['status' => 'Interview'])
            ->assertSessionHasErrors('status');
        $this->assertSame('Dipanggil Interview', $lamaran->fresh()->status);
    }

    public function test_student_without_cv_cannot_apply(): void
    {
        $this->fakeAuth('SISWA', 'usr-siswa-002');
        CvResume::where('siswa_id', 'usr-siswa-002')->delete();
        $lowongan = Lowongan::terbuka()->firstOrFail();

        $this->withHeader('Authorization', 'Bearer t')
            ->post(route('bkk.me.daftar', $lowongan->slug))
            ->assertRedirect(route('bkk.me.cv.edit'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('lamaran', ['siswa_id' => 'usr-siswa-002', 'lowongan_id' => $lowongan->id]);
    }

    public function test_student_cannot_apply_to_closed_lowongan(): void
    {
        $this->fakeAuth('SISWA', 'usr-siswa-001');
        $lowongan = Lowongan::whereDoesntHave('lamarans', fn ($q) => $q->where('siswa_id', 'usr-siswa-001'))->firstOrFail();
        $lowongan->update(['status' => 'Ditutup']);

        $this->withHeader('Authorization', 'Bearer t')
            ->postJson(route('bkk.me.daftar', $lowongan->slug))
            ->assertStatus(422);

        $this->assertDatabaseMissing('lamaran', ['siswa_id' => 'usr-siswa-001', 'lowongan_id' => $lowongan->id]);
    }

    public function test_new_student_gets_profile_and_can_save_cv(): void
    {
        $this->fakeAuth('SISWA', 'usr-siswa-baru-777');

        $this->withHeader('Authorization', 'Bearer t')
            ->post(route('bkk.me.cv.update'), ['markdown' => '# CV Baru'])
            ->assertRedirect(route('bkk.me.cv'))
            ->assertSessionHas('success');

        $this->assertNotNull(ProfilSiswa::find('usr-siswa-baru-777'));
        $this->assertDatabaseHas('cv_resumes', ['siswa_id' => 'usr-siswa-baru-777', 'konten_markdown' => '# CV Baru']);
    }

    public function test_admin_status_endpoints_reject_unknown_values(): void
    {
        $this->fakeAuth('ADMIN', 'adm-001');
        $lowongan = Lowongan::firstOrFail();

        $this->withHeader('Authorization', 'Bearer t')
            ->postJson(route('bkk.admin.lowongan.updateStatus', $lowongan->id), ['status' => 'Ngawur'])
            ->assertStatus(422);

        $this->assertSame('Aktif', $lowongan->fresh()->status);
    }

    public function test_admin_can_create_unverified_mitra(): void
    {
        $this->fakeAuth('ADMIN', 'adm-001');

        $this->withHeader('Authorization', 'Bearer t')->post(route('bkk.admin.mitra.store'), [
            'nama_perusahaan' => 'PT Uji Regresi',
            'npwp' => '99.999.999.9-999.999',
            'password' => 'rahasia123',
            'sektor_industri' => 'Teknologi',
            'alamat_kantor' => 'Jl. Uji',
            'kota' => 'Bogor',
            'email_perusahaan' => 'hr@uji.test',
            'no_telp_perusahaan' => '0251',
            'pic_name' => 'PIC',
            'pic_role' => 'HRD',
            'pic_email' => 'pic@uji.test',
            'pic_phone' => '0812',
            // is_verified tidak dikirim = checkbox tidak dicentang
        ])->assertRedirect(route('bkk.admin.mitra.index'));

        $this->assertFalse(Mitra::where('npwp', '99.999.999.9-999.999')->firstOrFail()->is_verified);
    }

    public function test_total_jam_is_recalculated_when_jurnal_is_revised(): void
    {
        $this->fakeAuth('ADMIN', 'adm-001');
        $penempatan = PenempatanPkl::firstOrFail();
        PklJurnalHarian::where('penempatan_pkl_id', $penempatan->id)->delete();
        $jurnal = PklJurnalHarian::create([
            'penempatan_pkl_id' => $penempatan->id,
            'siswa_id' => $penempatan->siswa_id,
            'tanggal' => today()->toDateString(),
            'aktivitas' => 'Uji',
            'durasi_jam' => 8,
            'status' => 'Menunggu',
        ]);
        $url = route('bkk.admin.pkl.validateJurnal', $jurnal->id);

        $this->withHeader('Authorization', 'Bearer t')->post($url, ['status' => 'Disetujui']);
        $this->assertSame(8, $penempatan->fresh()->total_jam_tercapai);

        $this->withHeader('Authorization', 'Bearer t')->post($url, ['status' => 'Revisi']);
        $this->assertSame(0, $penempatan->fresh()->total_jam_tercapai);
    }

    public function test_expired_lowongan_is_hidden_from_public_listing(): void
    {
        $lowongan = Lowongan::terbuka()->firstOrFail();
        $lowongan->update(['deadline' => today()->subDay()->toDateString()]);

        $this->get(route('bkk.lowongan'))->assertOk()->assertDontSee($lowongan->judul);
        $this->get(route('bkk.lowongan.detail', $lowongan->slug))->assertNotFound();
    }

    public function test_array_query_params_do_not_crash_public_pages(): void
    {
        $this->get('/bkk/lowongan?tipe[]=pkl&lokasi[]=bogor&q[]=x&jurusan[]=a')->assertOk();
        $this->get('/bkk/berita?q[]=x&kategori[]=a')->assertOk();
    }

    public function test_jurnal_rejects_future_date(): void
    {
        $this->fakeAuth('SISWA', 'usr-siswa-001');
        $this->withHeader('Authorization', 'Bearer t')
            ->postJson(route('bkk.me.jurnal.store'), [
                'tanggal' => today()->addDay()->toDateString(),
                'aktivitas' => 'Masa depan',
            ])
            ->assertStatus(422);
    }

    public function test_jurnal_rejects_alumni(): void
    {
        $this->fakeAuth('ALUMNI', 'usr-alumni-001');
        $this->withHeader('Authorization', 'Bearer t')
            ->postJson(route('bkk.me.jurnal.store'), [
                'tanggal' => today()->toDateString(),
                'aktivitas' => 'Alumni',
            ])
            ->assertStatus(403);
    }
}
