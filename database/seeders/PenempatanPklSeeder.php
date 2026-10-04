<?php

namespace Database\Seeders;

use App\Models\Mitra;
use App\Models\PenempatanPkl;
use App\Models\PklJurnalHarian;
use App\Models\PklLaporanAkhir;
use App\Models\ProfilSiswa;
use Illuminate\Database\Seeder;

class PenempatanPklSeeder extends Seeder
{
    public function run(): void
    {
        $siswa = ProfilSiswa::find('usr-siswa-001');
        $mitra = Mitra::where('npwp', '01.234.567.8-091.000')->first();

        if ($siswa && $mitra) {
            $penempatan = PenempatanPkl::updateOrCreate(
                ['siswa_id' => $siswa->user_id, 'mitra_id' => $mitra->id],
                [
                    'guru_pembimbing_id' => 'guru-siti-001',
                    'pembimbing_industri_nama' => 'Cindy Claudia, S.Kom.',
                    'unit_kerja_divisi' => 'Web & Application Development Unit',
                    'tanggal_mulai' => '2025-07-01',
                    'tanggal_selesai' => '2025-12-31',
                    'target_jam' => 640,
                    'total_jam_tercapai' => 380,
                    'status' => 'BERJALAN',
                    'nilai_akhir_industri' => null,
                    'nilai_akhir_sekolah' => null,
                ]
            );

            // Seed 4 entri jurnal harian
            $jurnals = [
                [
                    'tanggal' => now()->subDays(4)->toDateString(),
                    'aktivitas' => 'Melakukan pemetaan skema database dan normalisasi tabel BKK SMK Pelita Nusantara.',
                    'durasi' => 8,
                    'status' => 'Disetujui',
                    'catatan' => 'Analisis ERD sudah sesuai standar.',
                ],
                [
                    'tanggal' => now()->subDays(3)->toDateString(),
                    'aktivitas' => 'Membuat komponen Blade template untuk dashboard siswa/alumni berbasis Tailwind CSS.',
                    'durasi' => 8,
                    'status' => 'Disetujui',
                    'catatan' => 'Tampilan responsif di mobile dan desktop.',
                ],
                [
                    'tanggal' => now()->subDays(2)->toDateString(),
                    'aktivitas' => 'Implementasi sistem upload berkas sertifikasi dan CV preview ATS generator.',
                    'durasi' => 7,
                    'status' => 'Revisi',
                    'catatan' => 'Tolong lengkapi validasi file size maksimal 2MB untuk upload PDF.',
                ],
                [
                    'tanggal' => now()->subDays(1)->toDateString(),
                    'aktivitas' => 'Menyempurnakan integrasi REST API endpoint /api/user/verify ke middleware aplikasi Laravel.',
                    'durasi' => 8,
                    'status' => 'Menunggu',
                    'catatan' => null,
                ],
            ];

            foreach ($jurnals as $j) {
                PklJurnalHarian::firstOrCreate(
                    [
                        'penempatan_pkl_id' => $penempatan->id,
                        'tanggal' => $j['tanggal'],
                    ],
                    [
                        'siswa_id' => $siswa->user_id,
                        'aktivitas' => $j['aktivitas'],
                        'durasi_jam' => $j['durasi'],
                        'status' => $j['status'],
                        'catatan_revisi' => $j['catatan'],
                        'divalidasi_oleh' => ($j['status'] === 'Disetujui') ? 'guru-siti-001' : null,
                        'divalidasi_pada' => ($j['status'] === 'Disetujui') ? now() : null,
                    ]
                );
            }

            // Seed 5 Bab Laporan Akhir PKL
            $babs = [
                ['no' => 1, 'judul' => 'BAB I — Pendahuluan (Latar Belakang, Tujuan, & Profil Perusahaan)', 'status' => 'Disetujui', 'catatan' => 'Sistematika penulisan rapi dan latar belakang kontekstual.'],
                ['no' => 2, 'judul' => 'BAB II — Tinjauan Pustaka & Landasan Teori', 'status' => 'Disetujui', 'catatan' => 'Daftar rujukan pustaka memadai.'],
                ['no' => 3, 'judul' => 'BAB III — Pelaksanaan Praktik Kerja Lapangan', 'status' => 'Ditinjau', 'catatan' => 'Sedang dalam review oleh guru pembimbing.'],
                ['no' => 4, 'judul' => 'BAB IV — Hasil, Pembahasan, & Analisis Kendala', 'status' => 'Revisi', 'catatan' => 'Mohon sertakan screenshot diagram use case dan capture kode sistem.'],
                ['no' => 5, 'judul' => 'BAB V — Kesimpulan, Saran, & Lampiran Dokumentasi', 'status' => 'Belum', 'catatan' => null],
            ];

            foreach ($babs as $b) {
                PklLaporanAkhir::updateOrCreate(
                    [
                        'penempatan_pkl_id' => $penempatan->id,
                        'nomor_bab' => $b['no'],
                    ],
                    [
                        'siswa_id' => $siswa->user_id,
                        'judul_bab' => $b['judul'],
                        'file_draft_url' => ($b['status'] !== 'Belum') ? "/bkk/uploads/laporan/bab-{$b['no']}-usr-siswa-001.pdf" : null,
                        'status' => $b['status'],
                        'catatan_pembimbing' => $b['catatan'],
                        'terakhir_diperbarui' => now()->toDateString(),
                        'divalidasi_oleh' => ($b['status'] === 'Disetujui') ? 'guru-siti-001' : null,
                    ]
                );
            }
        }
    }
}
