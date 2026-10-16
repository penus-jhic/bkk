<?php

namespace App\Services;

use App\Models\CvResume;
use App\Models\Lowongan;
use App\Models\ProfilSiswa;

/**
 * Skor kecocokan siswa dengan lowongan (0-100), dihitung secara deterministik (bukan panggilan AI):
 * - 50% kualitas CV: skor ATS yang tersimpan di cv_resumes, atau skor heuristik dari isi CV
 * - 30% kecocokan jurusan siswa dengan target jurusan lowongan
 * - 20% cakupan kata kunci judul & persyaratan lowongan di dalam CV
 */
class JobMatchService
{
    private const WEIGHT_CV = 0.5;
    private const WEIGHT_JURUSAN = 0.3;
    private const WEIGHT_KEYWORD = 0.2;

    // Kata kunci per kompetensi keahlian; urutan & kode sama dengan daftar jurusan di landing page
    private const JURUSAN_KEYWORDS = [
        'RPL' => ['rpl', 'rekayasa perangkat lunak', 'pengembangan perangkat lunak'],
        'TKJ' => ['tkj', 'teknik komputer', 'jaringan'],
        'MM' => ['multimedia', 'dkv', 'desain komunikasi visual'],
        'PKM' => ['pkm', 'perbankan', 'keuangan mikro'],
        'TOI' => ['toi', 'otomasi industri'],
    ];

    private const STOPWORDS = [
        'dan', 'atau', 'yang', 'untuk', 'dengan', 'dari', 'pada', 'dalam', 'serta', 'memiliki', 'mampu', 'siswa',
        'lulusan', 'jurusan', 'aktif', 'smk', 'plus', 'pelita', 'nusantara', 'penus', 'menggunakan', 'memahami',
        'terbiasa', 'nilai', 'jika', 'sesuai', 'bersedia', 'minimal', 'tahun', 'kelas', 'tinggi', 'baik',
        'menguasai', 'mengerti', 'paham', 'pengalaman', 'kemampuan', 'diutamakan',
    ];

    public function __construct(private AiCvService $aiCvService)
    {
    }

    public function score(?ProfilSiswa $siswa, ?CvResume $cv, Lowongan $lowongan): int
    {
        $markdown = (string) ($cv?->raw_markdown ?? '');

        $total = self::WEIGHT_CV * $this->cvQuality($cv, $markdown)
            + self::WEIGHT_JURUSAN * $this->jurusanScore($siswa?->jurusan, $lowongan->target_jurusan)
            + self::WEIGHT_KEYWORD * $this->keywordCoverage($markdown, $lowongan);

        return max(0, min(100, (int) round($total)));
    }

    /**
     * Kode kompetensi keahlian dari teks jurusan bebas (mis. "Teknik Komputer & Jaringan (TKJ)" => ['TKJ']).
     * Teks yang menyebut "semua" berarti terbuka untuk seluruh jurusan.
     */
    public function jurusanCodes(?string $text): array
    {
        $text = mb_strtolower((string) $text);
        if (str_contains($text, 'semua')) {
            return array_keys(self::JURUSAN_KEYWORDS);
        }

        $codes = [];
        foreach (self::JURUSAN_KEYWORDS as $code => $keywords) {
            foreach ($keywords as $keyword) {
                // Batas kata supaya "mm" tidak cocok dengan kata lain yang mengandung "mm"
                if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/u', $text)) {
                    $codes[] = $code;
                    break;
                }
            }
        }

        return $codes;
    }

    private function cvQuality(?CvResume $cv, string $markdown): float
    {
        if ($cv?->score !== null) {
            return (float) $cv->score;
        }
        if (trim($markdown) === '') {
            return 0;
        }

        $checksum = $cv?->checksum ?: CvResume::generateChecksum($markdown);

        return (float) ($this->aiCvService->computeHeuristicScore($markdown, $checksum)['total_score'] ?? 0);
    }

    private function jurusanScore(?string $jurusanSiswa, ?string $targetJurusan): float
    {
        $target = $this->jurusanCodes($targetJurusan);
        if ($target === []) {
            return 100; // lowongan tidak membatasi jurusan
        }

        $siswa = $this->jurusanCodes($jurusanSiswa);
        if ($siswa === []) {
            return 50; // jurusan siswa belum diisi / tidak dikenali
        }

        return array_intersect($siswa, $target) !== [] ? 100 : 0;
    }

    private function keywordCoverage(string $markdown, Lowongan $lowongan): float
    {
        $source = $lowongan->judul . ' ' . implode(' ', (array) ($lowongan->persyaratan_json ?? []));
        preg_match_all('/[\p{L}\p{N}+#.]{3,}/u', mb_strtolower($source), $matches);

        $keywords = array_values(array_unique(array_filter(
            array_map(fn ($word) => trim($word, '.'), $matches[0]),
            fn ($word) => mb_strlen($word) >= 3 && !in_array($word, self::STOPWORDS, true)
        )));

        if ($keywords === []) {
            return 100;
        }

        $cv = mb_strtolower($markdown);
        $found = count(array_filter($keywords, fn ($word) => str_contains($cv, $word)));

        return 100 * $found / count($keywords);
    }
}
