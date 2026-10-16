<?php

namespace Database\Seeders;

use App\Models\ProfilSiswa;
use Illuminate\Database\Seeder;

class ProfilSiswaSeeder extends Seeder
{
    public function run(): void
    {
        ProfilSiswa::updateOrCreate(
            ['user_id' => 'usr-siswa-001'],
            [
                'nama_lengkap' => 'Ahmad Rizky Pratama',
                // Persona GURU di /bkk/dev/masuk, supaya siswa demo tampil di Portal Guru
                'guru_pembimbing_id' => 'usr-guru-001',
                'nis' => '0061234567',
                'nisn' => '0061234567',
                'jurusan' => 'Rekayasa Perangkat Lunak (RPL)',
                'kelas' => 'XII RPL 1',
                'angkatan' => '2023',
                'tahun_lulus' => null,
                'status_kelulusan' => 'SISWA_AKTIF',
                'status_aktivitas' => 'PKL di PT Solusi Teknologi Nusantara',
                'kelengkapan_profil' => 92,
                'headline_profesi' => 'Junior Full-Stack Web Developer | SMK Penus',
                'bio_singkat' => 'Siswa kelas XII RPL SMK Plus Pelita Nusantara yang memiliki antusiasme tinggi pada pengembangan aplikasi web Laravel, basis data relasional, dan integrasi API modern.',
                'link_linkedin' => 'https://linkedin.com/in/ahmad-rizky-pratama',
                'link_github' => 'https://github.com/rizkypratama-dev',
                'link_portfolio' => 'https://rizkypratama.me',
            ]
        );

        ProfilSiswa::updateOrCreate(
            ['user_id' => 'usr-alumni-001'],
            [
                'nama_lengkap' => 'Nadia Salsabila',
                'nis' => '1920.08.112',
                'nisn' => '0049876543',
                'jurusan' => 'Teknik Komputer & Jaringan (TKJ)',
                'kelas' => 'XII TKJ 2',
                'angkatan' => '2021',
                'tahun_lulus' => '2024',
                'status_kelulusan' => 'ALUMNI',
                'status_aktivitas' => 'Network Engineer di PT Telkom Akses',
                'kelengkapan_profil' => 96,
                'headline_profesi' => 'MikroTik Certified Network Associate (MTCNA) | Alumni TKJ Penus',
                'bio_singkat' => 'Alumni SMK Plus Pelita Nusantara angkatan 2024 dengan keahlian jaringan komputer, konfigurasi MikroTik RouterOS, fiber optic cabling, dan network monitoring.',
                'link_linkedin' => 'https://linkedin.com/in/nadiasalsabila',
                'link_github' => 'https://github.com/nadiasalsabila',
                'link_portfolio' => 'https://nadiasalsabila.net',
            ]
        );

        ProfilSiswa::updateOrCreate(
            ['user_id' => 'usr-siswa-002'],
            [
                'nama_lengkap' => 'Fajar Nugraha',
                // Persona GURU di /bkk/dev/masuk, supaya siswa demo tampil di Portal Guru
                'guru_pembimbing_id' => 'usr-guru-001',
                'nis' => '0061234588',
                'nisn' => '0061234588',
                'jurusan' => 'Teknik Komputer & Jaringan (TKJ)',
                'kelas' => 'XII TKJ 1',
                'angkatan' => '2023',
                'tahun_lulus' => null,
                'status_kelulusan' => 'SISWA_AKTIF',
                'status_aktivitas' => 'Persiapan PKL Semester Genap',
                'kelengkapan_profil' => 84,
                'headline_profesi' => 'Network Technician Trainee | SMK Penus',
                'bio_singkat' => 'Siswa TKJ dengan ketertarikan pada routing, switching, dan instalasi perangkat nirkabel serta pemeliharaan server lokal.',
                'link_linkedin' => null,
                'link_github' => 'https://github.com/fajarnugraha',
                'link_portfolio' => null,
            ]
        );

        ProfilSiswa::updateOrCreate(
            ['user_id' => 'usr-alumni-002'],
            [
                'nis' => '1920.08.204',
                'nisn' => '0049876599',
                'jurusan' => 'Desain Komunikasi Visual (DKV)',
                'kelas' => 'XII DKV 1',
                'angkatan' => '2021',
                'tahun_lulus' => '2024',
                'status_kelulusan' => 'ALUMNI',
                'status_aktivitas' => 'UI/UX Designer di CV Lentera Media Kreasi',
                'kelengkapan_profil' => 90,
                'headline_profesi' => 'Graphic & UI/UX Designer | Alumni DKV Penus',
                'bio_singkat' => 'Alumni DKV berfokus pada desain produk digital, branding visual perusahaan, dan ilustrasi digital berbasis Figma dan Adobe Creative Suite.',
                'link_linkedin' => 'https://linkedin.com/in/dimassetiawan',
                'link_github' => null,
                'link_portfolio' => 'https://behance.net/dimassetiawan',
            ]
        );
    }
}
