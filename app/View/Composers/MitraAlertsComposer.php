<?php

namespace App\View\Composers;

use App\Models\Lamaran;
use App\Models\Mitra;
use Illuminate\View\View;

/**
 * Antrean pekerjaan mitra IDUKA untuk lonceng notifikasi (header) dan badge menu (sidebar).
 * Mitra diambil dari $currentMitra yang dibagikan middleware mitra.auth. Dihitung sekali per request.
 */
class MitraAlertsComposer
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
        $mitra = $view->getData()['currentMitra'] ?? null;

        $view->with('mitraAlerts', $this->alerts ??= $this->build($mitra instanceof Mitra ? $mitra : null));
    }

    private function build(?Mitra $mitra): array
    {
        if (! $mitra) {
            return ['items' => [], 'total' => 0, 'by_key' => []];
        }

        $pelamarCount = isset($this->providedCounts['pelamar'])
            ? (int) $this->providedCounts['pelamar']
            : Lamaran::whereIn('lowongan_id', $mitra->lowongans()->pluck('id'))->where('status', 'Terkirim')->count();

        $items = [
            [
                'key' => 'pelamar',
                'count' => $pelamarCount,
                'title' => 'Lamaran baru belum ditinjau',
                'desc' => 'Berkas CV siswa & alumni menunggu review.',
                'href' => route('bkk.mitra.lowongan.index'),
            ],
            [
                'key' => 'tutup',
                'count' => $mitra->lowongans()->where('status', 'Aktif')->whereBetween('deadline', [now()->toDateString(), now()->addDays(7)->toDateString()])->count(),
                'title' => 'Lowongan segera ditutup',
                'desc' => 'Batas pendaftaran tinggal 7 hari lagi.',
                'href' => route('bkk.mitra.lowongan.index', ['status' => 'Aktif']),
            ],
        ];

        return [
            'items' => array_values(array_filter($items, fn ($item) => $item['count'] > 0)),
            'total' => array_sum(array_column($items, 'count')),
            'by_key' => array_column($items, 'count', 'key'),
        ];
    }
}
