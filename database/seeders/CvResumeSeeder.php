<?php

namespace Database\Seeders;

use App\Models\CvResume;
use App\Models\ProfilSiswa;
use Illuminate\Database\Seeder;

class CvResumeSeeder extends Seeder
{
    public function run(): void
    {
        $siswa = ProfilSiswa::find('usr-siswa-001');
        $alumni = ProfilSiswa::find('usr-alumni-001');

        if ($siswa) {
            // Versi 1 (Arsip terdahulu, is_main = false)
            $oldMarkdownSiswa = <<<MARKDOWN
# Ahmad Rizky Pratama
**Siswa SMK Plus Pelita Nusantara · Rekayasa Perangkat Lunak**
Email: siswa_rizky@smkpenus.sch.id | Telepon: 0812-3456-7890 | Bogor, Jawa Barat

---

## Ringkasan Profil
Siswa SMK Plus Pelita Nusantara jurusan Rekayasa Perangkat Lunak dengan minat tinggi dalam web development menggunakan PHP dan MySQL.

## Pendidikan
### SMK Plus Pelita Nusantara — Rekayasa Perangkat Lunak
*2023 - 2026* | Nilai Rata-rata: 88.0

## Keahlian Teknis
- HTML5, CSS3, JavaScript Dasar
- PHP Dasar & MySQL
MARKDOWN;

            CvResume::updateOrCreate(
                ['user_id' => $siswa->user_id, 'checksum' => CvResume::generateChecksum($oldMarkdownSiswa)],
                [
                    'raw_markdown' => $oldMarkdownSiswa,
                    'is_main' => false,
                    'temp' => false,
                    'feedback' => null,
                    'created_at' => now()->subDays(14),
                    'updated_at' => now()->subDays(14),
                ]
            );

            // Versi 2 (CV Utama Aktif, is_main = true)
            $markdownSiswa = <<<MARKDOWN
# Ahmad Rizky Pratama
**Junior Full-Stack Web Developer**
Email: siswa_rizky@smkpenus.sch.id | Telepon: 0812-3456-7890 | Bogor, Jawa Barat
LinkedIn: linkedin.com/in/ahmad-rizky-pratama | GitHub: github.com/rizkypratama-dev

---

## Ringkasan Profesional
Siswa kelas XII jurusan Rekayasa Perangkat Lunak SMK Plus Pelita Nusantara dengan pengalaman praktis dalam perancangan web responsif dan backend menggunakan PHP Laravel, PostgreSQL, dan Tailwind CSS. Memiliki portofolio proyek manajemen sekolah dan sistem inventaris barang.

## Pendidikan
**SMK Plus Pelita Nusantara** — Rekayasa Perangkat Lunak (RPL)  
*2023 - 2026 (Sekarang)* | Nilai Rata-rata: 89.5 / 100  
- Juara 2 Lomba Kompetensi Siswa (LKS) Web Technologies Tingkat Kota Bogor (2025)

## Pengalaman PKL & Proyek
**PT Solusi Teknologi Nusantara** — Web Developer Intern  
*Juli 2025 - Sekarang | Bogor*  
- Mengembangkan 12 antarmuka dashboard admin menggunakan Tailwind CSS & Blade template.
- Mengintegrasikan modul otentikasi JWT microservice dengan waktu respon API di bawah 200ms.
- Mengoptimalkan query database PostgreSQL untuk pelaporan transaksi bulanan.

## Keahlian Teknis
- **Bahasa Pemrograman**: PHP, JavaScript, SQL, HTML5/CSS3
- **Framework & Libs**: Laravel 12, Bootstrap 5, Tailwind CSS, Alpine.js
- **Tools**: Git, GitHub, Postman, VS Code, Figma

## Portofolio & Sertifikasi
- **LSP-P1 Junior Web Developer** — Badan Nasional Sertifikasi Profesi (BNSP), 2025 (No: BNSP-SMK-PENUS-2025-089)
MARKDOWN;

            $checksumSiswa = CvResume::generateChecksum($markdownSiswa);
            CvResume::updateOrCreate(
                ['user_id' => $siswa->user_id, 'checksum' => $checksumSiswa],
                [
                    'raw_markdown' => $markdownSiswa,
                    'is_main' => true,
                    'temp' => false,
                    'score' => 88,
                    'feedback' => [
                        'checksum' => $checksumSiswa,
                        'total_score' => 88,
                        'grade_label' => 'Baik',
                        'percentile' => 82,
                        'summary_verdict' => 'CV memiliki struktur ATS yang sangat rapi dan mudah di-parse mesin. Kualitas dapat ditingkatkan hingga skor 95+ dengan menambahkan metrik numerik pada bagian sertifikasi dan proyek kejuruan.',
                        'parameters' => [
                            [
                                'id' => 'param_industry_fit',
                                'label' => 'Kesesuaian Industri',
                                'score' => 90,
                                'max_score' => 100,
                                'status' => 'EXCELLENT',
                                'weight' => 0.35,
                                'feedback' => 'Terminologi teknis (Laravel, PostgreSQL, JWT, Tailwind CSS) selaras dengan kebutuhan industri software engineering.',
                            ],
                            [
                                'id' => 'param_completeness',
                                'label' => 'Kelengkapan Data',
                                'score' => 84,
                                'max_score' => 100,
                                'status' => 'GOOD',
                                'weight' => 0.25,
                                'feedback' => 'Semua bagian utama telah tersedia lengkap dengan kontak LinkedIn dan GitHub.',
                            ],
                            [
                                'id' => 'param_ats_format',
                                'label' => 'Format & Parsing ATS',
                                'score' => 95,
                                'max_score' => 100,
                                'status' => 'EXCELLENT',
                                'weight' => 0.25,
                                'feedback' => 'Hirarki heading (H1, H2), bullet points, dan tipografi Markdown sepenuhnya bebas dari karakter pemecah parser.',
                            ],
                            [
                                'id' => 'param_portfolio_impact',
                                'label' => 'Daya Tarik Portofolio',
                                'score' => 85,
                                'max_score' => 100,
                                'status' => 'GOOD',
                                'weight' => 0.15,
                                'feedback' => 'Sebagian besar butir telah mencantumkan hasil terukur, tambahkan 1 tautan proyek mandiri unggulan.',
                            ],
                        ],
                        'detected_sections' => [
                            'HEADER_IDENTITY',
                            'SUMMARY',
                            'EDUCATION',
                            'EXPERIENCE',
                            'SKILLS',
                            'CERTIFICATIONS',
                        ],
                        'missing_sections' => [
                            'PROJECTS',
                        ],
                    ],
                    'created_at' => now()->subDays(2),
                    'updated_at' => now()->subDays(2),
                ]
            );
        }

        if ($alumni) {
            $markdownAlumni = <<<MARKDOWN
# Nadia Salsabila
**MikroTik Certified Network Associate (MTCNA)**
Email: nadia.salsabila@gmail.com | Telepon: 0857-1122-3344 | Semarang, Jawa Tengah
LinkedIn: linkedin.com/in/nadiasalsabila

---

## Ringkasan Profesional
Alumni SMK Plus Pelita Nusantara 2024 jurusan Teknik Komputer & Jaringan dengan sertifikasi resmi MTCNA. Berpengalaman 1 tahun dalam konfigurasi perangkat jaringan MikroTik, troubleshooting fiber optic, dan pemeliharaan infrastruktur jaringan ISP.

## Pengalaman Kerja
**PT Telkom Akses Semarang** — Junior Network Engineer  
*Agustus 2024 - Sekarang | Semarang*  
- Melakukan maintenance preventif pada 50+ ODP (Optical Distribution Point) per bulan.
- Mengonfigurasi VLAN dan bandwidth management RouterOS MikroTik untuk pelanggan korporasi.

## Pendidikan
**SMK Plus Pelita Nusantara** — Teknik Komputer & Jaringan (TKJ)  
*2021 - 2024* | Nilai Akhir: 91.0 / 100

## Keahlian & Sertifikasi
- MikroTik Certified Network Associate (MTCNA-2405-ID-8819)
- Fiber Optic Splicing & OTDR Testing
- Bandwidth Management & VLAN Trunking
MARKDOWN;

            $checksumAlumni = CvResume::generateChecksum($markdownAlumni);
            CvResume::updateOrCreate(
                ['user_id' => $alumni->user_id, 'checksum' => $checksumAlumni],
                [
                    'raw_markdown' => $markdownAlumni,
                    'is_main' => true,
                    'temp' => false,
                    'score' => 91,
                    'feedback' => [
                        'checksum' => $checksumAlumni,
                        'total_score' => 91,
                        'grade_label' => 'Sangat Baik',
                        'percentile' => 89,
                        'summary_verdict' => 'Dokumen CV berstandar profesional dengan kredensial MTCNA dan metrik pemeliharaan jaringan yang kuat.',
                        'parameters' => [
                            [
                                'id' => 'param_industry_fit',
                                'label' => 'Kesesuaian Industri',
                                'score' => 93,
                                'max_score' => 100,
                                'status' => 'EXCELLENT',
                                'weight' => 0.35,
                                'feedback' => 'Kata kunci teknis jaringan ISP (MTCNA, ODP, VLAN, OTDR) sangat relevan dengan target industri.',
                            ],
                            [
                                'id' => 'param_completeness',
                                'label' => 'Kelengkapan Data',
                                'score' => 88,
                                'max_score' => 100,
                                'status' => 'GOOD',
                                'weight' => 0.25,
                                'feedback' => 'Identitas, pengalaman kerja, pendidikan, dan nomor lisensi sertifikasi sudah lengkap.',
                            ],
                            [
                                'id' => 'param_ats_format',
                                'label' => 'Format & Parsing ATS',
                                'score' => 94,
                                'max_score' => 100,
                                'status' => 'EXCELLENT',
                                'weight' => 0.25,
                                'feedback' => 'Struktur bagian kronologis terbalik memudahkan pembacaan sistem ATS perusahaan.',
                            ],
                            [
                                'id' => 'param_portfolio_impact',
                                'label' => 'Daya Tarik Portofolio',
                                'score' => 87,
                                'max_score' => 100,
                                'status' => 'GOOD',
                                'weight' => 0.15,
                                'feedback' => 'Tambahkan persentase SLA uptime jaringan yang berhasil dipertahankan.',
                            ],
                        ],
                        'detected_sections' => [
                            'HEADER_IDENTITY',
                            'SUMMARY',
                            'EXPERIENCE',
                            'EDUCATION',
                            'SKILLS',
                            'CERTIFICATIONS',
                        ],
                        'missing_sections' => [
                            'PROJECTS',
                        ],
                    ],
                    'created_at' => now()->subDay(),
                    'updated_at' => now()->subDay(),
                ]
            );
        }
    }
}
