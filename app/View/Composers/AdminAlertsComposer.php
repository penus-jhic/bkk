<?php

namespace App\View\Composers;

use App\Models\Lamaran;
use App\Models\PermohonanKerjasama;
use App\Models\PklJurnalHarian;
use App\Models\PklLaporanAkhir;
use Illuminate\View\View;

/**
 * Antrean pekerjaan admin BKK untuk lonceng notifikasi (header) dan badge menu (sidebar).
 * Dihitung sekali per request walau dipakai di dua partial.
 */
class AdminAlertsComposer
{
    private ?array $alerts = null;

    /**
     * Cache hitungan yang sudah dihitung controller dalam request yang sama (S3).
     */
    private array $providedCounts = [];

    /**
     * Terima hitungan metrik yang sudah dihitung controller agar tidak query ulang (S3).
     */
    public function provideCounts(array $counts): void
    {
        $this->providedCounts = array_merge($this->providedCounts, $counts);
        $this->alerts = null;
    }

    public function compose(View $view): void
    {
        $view->with('adminAlerts', $this->alerts ??= $this->build());
    }

    private function build(): array
    {
        $items = [
            [
                'key' => 'permohonan',
                'count' => (int) ($this->providedCounts['permohonan'] ?? PermohonanKerjasama::where('status', 'MENUNGGU_REVIEW')->count()),
                'title' => 'Permohonan kerja sama baru',
                'desc' => 'Calon mitra IDUKA menunggu review.',
                'route' => 'bkk.admin.mitra.permohonan.index',
            ],
            [
                'key' => 'jurnal',
                'count' => (int) ($this->providedCounts['jurnal'] ?? PklJurnalHarian::where('status', 'Menunggu')->count()),
                'title' => 'Jurnal PKL menunggu validasi',
                'desc' => 'Log harian siswa belum diperiksa.',
                'route' => 'bkk.admin.pkl.monitoring',
            ],
            [
                'key' => 'laporan',
                'count' => (int) ($this->providedCounts['laporan'] ?? PklLaporanAkhir::where('status', 'Ditinjau')->count()),
                'title' => 'Bab laporan PKL perlu ditinjau',
                'desc' => 'Draf laporan akhir siswa sudah dikirim.',
                'route' => 'bkk.admin.pkl.monitoring',
            ],
            [
                'key' => 'lamaran',
                'count' => (int) ($this->providedCounts['lamaran'] ?? Lamaran::where('status', 'Terkirim')->count()),
                'title' => 'Lamaran baru masuk',
                'desc' => 'Pelamar belum ditinjau mitra.',
                'route' => 'bkk.admin.lowongan.index',
            ],
        ];

        return [
            'items' => array_values(array_filter($items, fn ($item) => $item['count'] > 0)),
            'total' => array_sum(array_column($items, 'count')),
            'by_key' => array_column($items, 'count', 'key'),
        ];
    }
}
