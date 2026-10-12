<?php

namespace Database\Seeders;

use App\Models\Mitra;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MitraSeeder extends Seeder
{
    public function run(): void
    {
        // Akun mitra contoh hanya untuk development; jangan pernah di-seed ke database server
        if (! app()->environment('local', 'testing')) {
            throw new \RuntimeException('MitraSeeder hanya boleh dijalankan saat APP_ENV=local atau testing.');
        }

        // Placeholder yang tidak bisa dipakai login; kata sandi asli per akun dibuat di bawah
        $defaultPassword = Hash::make(Str::random(40));

        Mitra::updateOrCreate(
            ['npwp' => '01.234.567.8-091.000'],
            [
                'nama_perusahaan' => 'PT Solusi Teknologi Nusantara',
                'singkatan' => 'STN',
                'password' => $defaultPassword,
                'sektor_industri' => 'Teknologi Informasi, Rekayasa Perangkat Lunak & Jaringan',
                'alamat_kantor' => 'Jl. Pajajaran No. 88, Baranangsiang, Kota Bogor',
                'kota' => 'Bogor',
                'website' => 'https://solusiteknologi.co.id',
                'email_perusahaan' => 'hrd@solusiteknologi.co.id',
                'no_telp_perusahaan' => '0251-8321900',
                'logo_url' => 'https://images.unsplash.com/photo-1549923746-c502d488b3ea?w=150&auto=format&fit=crop&q=80',
                'status_kemitraan' => 'Mitra IDUKA Utama (MoU Terverifikasi)',
                'tanggal_mou_mulai' => '2025-01-01',
                'tanggal_mou_selesai' => '2028-01-01',
                'is_verified' => true,
                'pic_name' => 'Cindy Claudia, S.Kom.',
                'pic_role' => 'Talent Acquisition & Partnership Lead',
                'pic_email' => 'cindy.claudia@solusiteknologi.co.id',
                'pic_phone' => '0812-9988-7766',
            ]
        );

        Mitra::updateOrCreate(
            ['npwp' => '02.345.678.9-501.000'],
            [
                'nama_perusahaan' => 'PT Telkom Akses Semarang',
                'singkatan' => 'Telkom Akses',
                'password' => $defaultPassword,
                'sektor_industri' => 'Telekomunikasi & Jaringan Fiber Optic',
                'alamat_kantor' => 'Jl. Pahlawan No. 10, Pleburan, Kota Semarang',
                'kota' => 'Semarang',
                'website' => 'https://telkomakses.co.id',
                'email_perusahaan' => 'recruitment.smg@telkomakses.co.id',
                'no_telp_perusahaan' => '024-8412345',
                'logo_url' => 'https://images.unsplash.com/photo-1516321318423-f06f85e504b3?w=150&auto=format&fit=crop&q=80',
                'status_kemitraan' => 'Mitra IDUKA Strategis PKL & Rekrutmen',
                'tanggal_mou_mulai' => '2024-06-01',
                'tanggal_mou_selesai' => '2027-06-01',
                'is_verified' => true,
                'pic_name' => 'Budi Hartono, M.T.',
                'pic_role' => 'HR Operations Manager',
                'pic_email' => 'budi.hartono@telkomakses.co.id',
                'pic_phone' => '0813-1122-3344',
            ]
        );

        Mitra::updateOrCreate(
            ['npwp' => '03.456.789.0-021.000'],
            [
                'nama_perusahaan' => 'PT BCA Digital',
                'singkatan' => 'blu by BCA Digital',
                'password' => $defaultPassword,
                'sektor_industri' => 'Fintech & Perbankan Digital',
                'alamat_kantor' => 'The Breeze BSD City, Tangerang / Sudirman, Jakarta Selatan',
                'kota' => 'Jakarta Selatan',
                'website' => 'https://bcadigital.co.id',
                'email_perusahaan' => 'careers@bcadigital.co.id',
                'no_telp_perusahaan' => '021-50882200',
                'logo_url' => 'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?w=150&auto=format&fit=crop&q=80',
                'status_kemitraan' => 'Mitra Program Penyerapan Kerja Lulusan',
                'tanggal_mou_mulai' => '2025-02-15',
                'tanggal_mou_selesai' => '2027-02-15',
                'is_verified' => true,
                'pic_name' => 'Maya Amanda, S.Psi.',
                'pic_role' => 'People Experience Specialist',
                'pic_email' => 'maya.amanda@bcadigital.co.id',
                'pic_phone' => '0811-2233-4455',
            ]
        );

        Mitra::updateOrCreate(
            ['npwp' => '04.567.890.1-401.000'],
            [
                'nama_perusahaan' => 'PT Cyber Network Indonesia',
                'singkatan' => 'CNI Net',
                'password' => $defaultPassword,
                'sektor_industri' => 'Cloud Infrastructure & ISP Solution',
                'alamat_kantor' => 'Jl. Ir. H. Juanda No. 120, Dago, Kota Bandung',
                'kota' => 'Bandung',
                'website' => 'https://cybernetwork.id',
                'email_perusahaan' => 'hr@cybernetwork.id',
                'no_telp_perusahaan' => '022-2501234',
                'logo_url' => null,
                'status_kemitraan' => 'Mitra Terverifikasi',
                'tanggal_mou_mulai' => '2024-08-01',
                'tanggal_mou_selesai' => '2026-08-01',
                'is_verified' => true,
                'pic_name' => 'Rian Permana',
                'pic_role' => 'Head of Engineering & Talent',
                'pic_email' => 'rian@cybernetwork.id',
                'pic_phone' => '0878-3344-5566',
            ]
        );

        Mitra::updateOrCreate(
            ['npwp' => '05.678.901.2-123.000'],
            [
                'nama_perusahaan' => 'CV Lentera Media Kreasi',
                'singkatan' => 'Lentera Media',
                'password' => $defaultPassword,
                'sektor_industri' => 'Multimedia, Animasi & Desain Komunikasi Visual',
                'alamat_kantor' => 'Jl. Pandu Raya No. 45, Bantarjati, Kota Bogor',
                'kota' => 'Bogor',
                'website' => 'https://lenteramedia.studio',
                'email_perusahaan' => 'halo@lenteramedia.studio',
                'no_telp_perusahaan' => '0251-8356789',
                'logo_url' => null,
                'status_kemitraan' => 'Mitra IDUKA DKV & Multimedia',
                'tanggal_mou_mulai' => '2025-01-10',
                'tanggal_mou_selesai' => '2026-01-10',
                'is_verified' => true,
                'pic_name' => 'Sinta Dewi, S.Sn.',
                'pic_role' => 'Creative Director',
                'pic_email' => 'sinta@lenteramedia.studio',
                'pic_phone' => '0856-7788-9900',
            ]
        );

        // Kata sandi acak per akun, ditampilkan sekali di konsol saat seeding
        $credentials = [];
        foreach (Mitra::whereIn('npwp', [
            '01.234.567.8-091.000', '02.345.678.9-501.000', '03.456.789.0-021.000',
            '04.567.890.1-401.000', '05.678.901.2-123.000',
        ])->get() as $mitra) {
            $password = Str::password(16);
            $mitra->update(['password' => Hash::make($password)]);
            $credentials[] = [$mitra->singkatan, $mitra->npwp, $password];
        }

        $this->command?->table(['Mitra', 'NPWP', 'Kata sandi'], $credentials);
    }
}
