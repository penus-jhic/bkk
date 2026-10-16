<?php

namespace Tests\Feature;

use App\Models\Lowongan;
use App\Models\Mitra;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MitraDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected Mitra $mitra;

    protected function setUp(): void
    {
        parent::setUp();

        // Ambil atau buat mitra demo terverifikasi
        $this->mitra = Mitra::firstOrCreate(
            ['npwp' => '01.234.567.8-091.000'],
            [
                'nama_perusahaan' => 'PT Solusi Teknologi Nusantara',
                'singkatan' => 'STN',
                'password' => Hash::make('Password123!'),
                'sektor_industri' => 'Teknologi Informasi & Jaringan',
                'alamat_kantor' => 'Jl. Pajajaran No. 88, Kota Bogor',
                'kota' => 'Bogor',
                'email_perusahaan' => 'hrd@solusiteknologi.co.id',
                'no_telp_perusahaan' => '0251-8321900',
                'pic_name' => 'Cindy Claudia, S.Kom.',
                'pic_role' => 'Talent Acquisition Lead',
                'pic_email' => 'cindy.claudia@solusiteknologi.co.id',
                'pic_phone' => '0812-9988-7766',
                'is_verified' => true,
                'status_kemitraan' => 'Mitra IDUKA Terverifikasi',
            ]
        );
    }

    /**
     * 1. Halaman Login Mitra (/bkk/dashboard/login) dapat diakses publik.
     */
    public function test_mitra_login_page_loads_successfully(): void
    {
        $response = $this->get('/bkk/dashboard/login');

        $response->assertStatus(200)
            ->assertSee('Masuk Portal IDUKA')
            ->assertSee('Nama Perusahaan')
            ->assertSee('Kata Sandi');
    }

    /**
     * 2. Pengunjung tanpa login yang mengakses /bkk/dashboard dialihkan ke login.
     */
    public function test_unauthenticated_mitra_redirected_to_login(): void
    {
        $response = $this->get('/bkk/dashboard');

        $response->assertRedirect(route('bkk.mitra.login'));
    }

    /**
     * 3. Login Mitra dengan Nama Perusahaan (Sanitasi spasi/case-insensitive) & Password.
     */
    public function test_mitra_can_login_with_sanitized_company_name_and_password(): void
    {
        $response = $this->post('/bkk/dashboard/login', [
            'nama_perusahaan' => '   pt   solusi   teknologi   nusantara   ', // ekstra spasi & huruf kecil
            'password' => 'Password123!',
        ]);

        $response->assertRedirect(route('bkk.mitra.dashboard'))
            ->assertSessionHas('mitra_id', $this->mitra->id);
    }

    /**
     * 4. Login Mitra gagal dengan kata sandi salah.
     */
    public function test_mitra_login_fails_with_invalid_password(): void
    {
        $response = $this->from('/bkk/dashboard/login')->post('/bkk/dashboard/login', [
            'nama_perusahaan' => 'PT Solusi Teknologi Nusantara',
            'password' => 'SalahPassword!',
        ]);

        $response->assertRedirect('/bkk/dashboard/login')
            ->assertSessionHas('error');
    }

    /**
     * 5. Dashboard Mitra terautentikasi (/bkk/dashboard) menampilkan data database riil.
     */
    public function test_authenticated_mitra_dashboard_loads_db_data(): void
    {
        $response = $this->withSession(['mitra_id' => $this->mitra->id])
            ->get('/bkk/dashboard');

        $response->assertStatus(200)
            ->assertSee($this->mitra->nama_perusahaan)
            ->assertSee('Lowongan Aktif')
            ->assertSee('Total Pelamar');
    }

    /**
     * 6. Daftar Lowongan Mitra (/bkk/dashboard/lowongan) dengan fetching DB.
     */
    public function test_authenticated_mitra_lowongan_index_loads(): void
    {
        $response = $this->withSession(['mitra_id' => $this->mitra->id])
            ->get('/bkk/dashboard/lowongan');

        $response->assertStatus(200)
            ->assertSeeText('Kelola Lowongan IDUKA');
    }

    /**
     * 7. Buat Lowongan Baru (POST /bkk/dashboard/lowongan).
     */
    public function test_authenticated_mitra_can_create_lowongan(): void
    {
        $response = $this->withSession(['mitra_id' => $this->mitra->id])
            ->post('/bkk/dashboard/lowongan', [
                'title' => 'Quality Assurance Tester Junior',
                'tipe' => 'Kerja',
                'jurusan' => 'Rekayasa Perangkat Lunak (RPL)',
                'lokasi' => 'Kota Bogor (Hybrid)',
                'kuota' => 2,
                'deadline' => now()->addMonths(1)->toDateString(),
                'deskripsi' => 'Pengujian fungsionalitas aplikasi dan pembuatan skenario test case.',
            ]);

        $response->assertRedirect(route('bkk.mitra.lowongan.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('lowongan', [
            'mitra_id' => $this->mitra->id,
            'judul' => 'Quality Assurance Tester Junior',
            'tipe' => 'Kerja',
        ]);
    }

    /**
     * 8. Halaman Pengaturan Mitra (/bkk/dashboard/pengaturan).
     */
    public function test_mitra_pengaturan_page_loads(): void
    {
        $response = $this->withSession(['mitra_id' => $this->mitra->id])
            ->get('/bkk/dashboard/pengaturan');

        $response->assertStatus(200)
            ->assertSeeText('Pengaturan Akun Mitra')
            ->assertSee('Keamanan Kata Sandi')
            ->assertSee('Logo Perusahaan')
            ->assertSee('Keluar dari Akun (Logout)');
    }

    /**
     * 9. Update Password Mitra pada Pengaturan.
     */
    public function test_mitra_can_update_password(): void
    {
        $response = $this->withSession(['mitra_id' => $this->mitra->id])
            ->post('/bkk/dashboard/pengaturan/password', [
                'current_password' => 'Password123!',
                'password' => 'NewPassword123!',
                'password_confirmation' => 'NewPassword123!',
            ]);

        $response->assertSessionHas('password_success');

        $this->mitra->refresh();
        $this->assertTrue(Hash::check('NewPassword123!', $this->mitra->password));

        // Kembalikan ke password semula
        $this->mitra->update(['password' => Hash::make('Password123!')]);
    }

    /**
     * 10. Logout Mitra (POST /bkk/dashboard/logout) menghapus sesi & cookie.
     */
    public function test_mitra_logout_clears_session_and_cookie(): void
    {
        $response = $this->withSession(['mitra_id' => $this->mitra->id])
            ->post('/bkk/dashboard/logout');

        $response->assertRedirect(route('bkk.mitra.login'))
            ->assertSessionMissing('mitra_id');
    }

    protected function fakeAuth(string $role = 'ADMIN', string $id = 'adm-bkk-001'): void
    {
        \Illuminate\Support\Facades\Http::fake([
            '*/api/user/verify' => \Illuminate\Support\Facades\Http::response([
                'success' => true,
                'message' => 'Token terverifikasi',
                'data' => [
                    'id' => $id,
                    'username' => 'admin_bkk',
                    'nama_lengkap' => 'Admin BKK Penus',
                    'nomor_induk' => 'ADM-001',
                    'role' => $role,
                    'status_aktif' => true,
                    'email' => 'admin@smkpenus.sch.id',
                ],
            ], 200),
        ]);
    }

    /**
     * 11. Admin BKK: Daftar Mitra IDUKA (/bkk/admin/mitra).
     */
    public function test_admin_mitra_index_loads_successfully(): void
    {
        $this->fakeAuth('ADMIN');

        $response = $this->withCookies(['access_token' => 'dummy-admin-token'])
            ->get('/bkk/admin/mitra');

        $response->assertStatus(200)
            ->assertSeeText('Kelola Mitra Industri (IDUKA)')
            ->assertSee('Tambah Mitra Baru')
            ->assertSee($this->mitra->nama_perusahaan);
    }

    /**
     * 12. Admin BKK: Form Tambah Mitra Baru (/bkk/admin/mitra/new).
     */
    public function test_admin_mitra_create_page_loads_successfully(): void
    {
        $this->fakeAuth('ADMIN');

        $response = $this->withCookies(['access_token' => 'dummy-admin-token'])
            ->get('/bkk/admin/mitra/new');

        $response->assertStatus(200)
            ->assertSeeText('Tambah Mitra Industri (IDUKA) Baru')
            ->assertSee('Nama Resmi Perusahaan')
            ->assertSee('Nomor NPWP Perusahaan');
    }

    /**
     * 13. Admin BKK: Simpan Mitra Baru (POST /bkk/admin/mitra).
     */
    public function test_admin_can_store_new_mitra(): void
    {
        $this->fakeAuth('ADMIN');

        $response = $this->withCookies(['access_token' => 'dummy-admin-token'])
            ->post('/bkk/admin/mitra', [
                'nama_perusahaan' => '   PT  Inovasi  Kreatif  Nusantara   ',
                'singkatan' => 'IKN Tech',
                'npwp' => '99.888.777.6-555.000',
                'password' => 'PasswordBaru123!',
                'sektor_industri' => 'Desain Grafis & Animasi 3D',
                'alamat_kantor' => 'Jl. Sudirman No. 100, Bogor',
                'kota' => 'Bogor',
                'email_perusahaan' => 'contact@ikntech.co.id',
                'no_telp_perusahaan' => '0251-8765432',
                'pic_name' => 'Fajar Pratama, S.Ds.',
                'pic_role' => 'Studio Director',
                'pic_email' => 'fajar@ikntech.co.id',
                'pic_phone' => '0812-3456-7890',
                'is_verified' => 1,
            ]);

        $response->assertRedirect(route('bkk.admin.mitra.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('mitra', [
            'nama_perusahaan' => 'PT Inovasi Kreatif Nusantara', // disanitasi
            'npwp' => '99.888.777.6-555.000',
            'is_verified' => true,
        ]);
    }

    /**
     * 14. Admin BKK: Form Edit Mitra (/bkk/admin/mitra/{id}).
     */
    public function test_admin_can_view_edit_mitra_page(): void
    {
        $this->fakeAuth('ADMIN');

        $response = $this->withCookies(['access_token' => 'dummy-admin-token'])
            ->get("/bkk/admin/mitra/{$this->mitra->id}");

        $response->assertStatus(200)
            ->assertSeeText('Edit:')
            ->assertSee($this->mitra->nama_perusahaan);
    }

    /**
     * 15. Admin BKK: Update Data Mitra (PUT /bkk/admin/mitra/{id}).
     */
    public function test_admin_can_update_mitra(): void
    {
        $this->fakeAuth('ADMIN');

        $response = $this->withCookies(['access_token' => 'dummy-admin-token'])
            ->put("/bkk/admin/mitra/{$this->mitra->id}", [
                'nama_perusahaan' => 'PT Solusi Teknologi Nusantara Updated',
                'singkatan' => 'STN Global',
                'npwp' => $this->mitra->npwp,
                'sektor_industri' => 'Cloud & AI Engineering',
                'alamat_kantor' => 'Kawasan Industri Penus, Bogor',
                'kota' => 'Kota Bogor',
                'email_perusahaan' => 'update@solusiteknologi.co.id',
                'no_telp_perusahaan' => '0251-8392041',
                'pic_name' => 'Cindy Claudia, M.Kom.',
                'pic_role' => 'VP Talent Acquisition',
                'pic_email' => 'cindy@solusiteknologi.co.id',
                'pic_phone' => '0811-9876-5432',
                'is_verified' => 1,
            ]);

        $response->assertRedirect(route('bkk.admin.mitra.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('mitra', [
            'id' => $this->mitra->id,
            'nama_perusahaan' => 'PT Solusi Teknologi Nusantara Updated',
            'sektor_industri' => 'Cloud & AI Engineering',
        ]);
    }

    /**
     * 16. Admin BKK: Toggle Status Verifikasi Mitra (POST /bkk/admin/mitra/{id}/verify).
     */
    public function test_admin_can_toggle_verify_mitra(): void
    {
        $this->fakeAuth('ADMIN');

        $initialStatus = $this->mitra->is_verified;

        $response = $this->withCookies(['access_token' => 'dummy-admin-token'])
            ->post("/bkk/admin/mitra/{$this->mitra->id}/verify");

        $response->assertSessionHas('success');

        $this->mitra->refresh();
        $this->assertEquals(!$initialStatus, $this->mitra->is_verified);
    }

    /**
     * 17. Admin BKK: Hapus Mitra (DELETE /bkk/admin/mitra/{id}).
     */
    public function test_admin_can_delete_mitra(): void
    {
        $this->fakeAuth('ADMIN');

        $tempMitra = Mitra::create([
            'nama_perusahaan' => 'PT Mitra Hapus Tester',
            'npwp' => '11.222.333.4-555.000',
            'password' => Hash::make('Password123!'),
            'sektor_industri' => 'Otomotif',
            'alamat_kantor' => 'Bogor',
            'kota' => 'Bogor',
            'email_perusahaan' => 'hapus@tester.com',
            'no_telp_perusahaan' => '0251-123456',
            'pic_name' => 'Tester',
            'pic_role' => 'HR',
            'pic_email' => 'pic@tester.com',
            'pic_phone' => '0812345678',
        ]);

        $response = $this->withCookies(['access_token' => 'dummy-admin-token'])
            ->delete("/bkk/admin/mitra/{$tempMitra->id}");

        $response->assertRedirect(route('bkk.admin.mitra.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('mitra', ['id' => $tempMitra->id]);
    }
}
