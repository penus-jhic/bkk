<?php

namespace Database\Seeders;

use App\Models\CvResume;
use App\Models\JadwalInterview;
use App\Models\Lamaran;
use App\Models\LamaranRiwayatStatus;
use App\Models\Lowongan;
use App\Models\ProfilSiswa;
use Illuminate\Database\Seeder;

class LamaranSeeder extends Seeder
{
    public function run(): void
    {
        $siswa = ProfilSiswa::find('usr-siswa-001');
        $alumni = ProfilSiswa::find('usr-alumni-001');

        $lowonganPklStn = Lowongan::where('slug', 'pkl-web-application-tester-support-stn')->first();
        $lowonganPklTelkom = Lowongan::where('slug', 'pkl-field-technician-fiber-optic-telkom-akses')->first();
        $lowonganBca = Lowongan::where('slug', 'junior-operations-it-support-bca-digital')->first();

        // 1. Lamaran Siswa ke PT Solusi Teknologi Nusantara (Diterima)
        if ($siswa && $lowonganPklStn) {
            $cvSiswa = CvResume::where('user_id', $siswa->user_id)->where('is_main', true)->first();

            $lamaran1 = Lamaran::updateOrCreate(
                ['kode_lamaran' => 'LMR-2025-001'],
                [
                    'lowongan_id' => $lowonganPklStn->id,
                    'siswa_id' => $siswa->user_id,
                    'cv_id' => $cvSiswa?->id,
                    'tanggal_melamar' => '2025-06-10',
                    'skor_match_ai' => 96,
                    'status' => 'Diterima',
                    'step_tahapan' => 4,
                    'catatan_seleksi' => 'Kandidat memiliki pemahaman teknis web yang kuat dan dinilai siap untuk penempatan PKL.',
                ]
            );

            LamaranRiwayatStatus::firstOrCreate([
                'lamaran_id' => $lamaran1->id,
                'judul_tahapan' => 'Lamaran Terkirim',
            ], [
                'deskripsi' => 'Berkas dan CV ATS berhasil didaftarkan ke sistem PT Solusi Teknologi Nusantara.',
                'diubah_oleh_id' => $siswa->user_id,
                'diubah_oleh_role' => 'SISWA',
            ]);

            LamaranRiwayatStatus::firstOrCreate([
                'lamaran_id' => $lamaran1->id,
                'judul_tahapan' => 'Panggilan Interview & Asesmen',
            ], [
                'deskripsi' => 'Undangan wawancara teknis dan uji coding dasar.',
                'diubah_oleh_id' => 'cindy-stn',
                'diubah_oleh_role' => 'MITRA',
            ]);

            LamaranRiwayatStatus::firstOrCreate([
                'lamaran_id' => $lamaran1->id,
                'judul_tahapan' => 'Diterima Magang / PKL',
            ], [
                'deskripsi' => 'Selamat! Anda diterima untuk program PKL periode Juli - Desember 2025.',
                'diubah_oleh_id' => 'cindy-stn',
                'diubah_oleh_role' => 'MITRA',
            ]);
        }

        // 2. Lamaran Siswa ke PT Telkom Akses (Dipanggil Interview)
        if ($siswa && $lowonganPklTelkom) {
            $cvSiswa = CvResume::where('user_id', $siswa->user_id)->where('is_main', true)->first();

            $lamaran2 = Lamaran::updateOrCreate(
                ['kode_lamaran' => 'LMR-2025-002'],
                [
                    'lowongan_id' => $lowonganPklTelkom->id,
                    'siswa_id' => $siswa->user_id,
                    'cv_id' => $cvSiswa?->id,
                    'tanggal_melamar' => '2025-06-15',
                    'skor_match_ai' => 88,
                    'status' => 'Dipanggil Interview',
                    'step_tahapan' => 3,
                    'catatan_seleksi' => 'Lolos seleksi berkas administrasi. Dijadwalkan wawancara kompetensi daring.',
                ]
            );

            JadwalInterview::updateOrCreate(
                ['lamaran_id' => $lamaran2->id],
                [
                    'tanggal_interview' => now()->addDays(5)->toDateString(),
                    'waktu_interview' => '09:30 WIB',
                    'mode' => 'Online (Google Meet)',
                    'lokasi_atau_url' => 'https://meet.google.com/abc-defg-hij',
                    'pic_pewawancara' => 'Budi Hartono, M.T. (HR Operations Telkom Akses)',
                    'instruksi_khusus' => 'Harap bergabung 10 menit sebelum jadwal, mengenakan seragam sekolah rapi, dan menyiapkan portofolio.',
                    'status_kehadiran' => 'TERJADWAL',
                ]
            );

            LamaranRiwayatStatus::firstOrCreate([
                'lamaran_id' => $lamaran2->id,
                'judul_tahapan' => 'Lamaran Terkirim',
            ], [
                'deskripsi' => 'Pendaftaran online berhasil diterima server.',
                'diubah_oleh_id' => $siswa->user_id,
                'diubah_oleh_role' => 'SISWA',
            ]);

            LamaranRiwayatStatus::firstOrCreate([
                'lamaran_id' => $lamaran2->id,
                'judul_tahapan' => 'Dipanggil Interview',
            ], [
                'deskripsi' => 'Jadwal sesi interview daring telah diterbitkan.',
                'diubah_oleh_id' => 'budi-telkom',
                'diubah_oleh_role' => 'MITRA',
            ]);
        }

        // 3. Lamaran Alumni ke PT BCA Digital (Diterima)
        if ($alumni && $lowonganBca) {
            $cvAlumni = CvResume::where('user_id', $alumni->user_id)->where('is_main', true)->first();

            $lamaran3 = Lamaran::updateOrCreate(
                ['kode_lamaran' => 'LMR-2025-003'],
                [
                    'lowongan_id' => $lowonganBca->id,
                    'siswa_id' => $alumni->user_id,
                    'cv_id' => $cvAlumni?->id,
                    'tanggal_melamar' => '2025-05-20',
                    'skor_match_ai' => 95,
                    'status' => 'Diterima',
                    'step_tahapan' => 4,
                    'catatan_seleksi' => 'Kandidat memiliki sertifikasi MTCNA aktif dan pengalaman teknis yang relevan.',
                ]
            );
        }
    }
}
