<?php

namespace Tests\Feature;

use App\Models\Berita;
use App\Models\Lamaran;
use App\Models\Lowongan;
use App\Models\Mitra;
use App\Models\PenempatanPkl;
use App\Models\PklJurnalHarian;
use App\Models\PklLaporanAkhir;
use App\Models\TracerRespon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SqlEfficiencyS1S2S3Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    protected function fakeAuth(string $role = 'ADMIN', string $id = 'usr-admin-001'): void
    {
        Http::fake([
            '*/api/user/verify' => Http::response([
                'success' => true,
                'message' => 'Token terverifikasi',
                'data' => [
                    'id' => $id,
                    'username' => strtolower($role) . '_user',
                    'nama_lengkap' => "User {$role}",
                    'nomor_induk' => 'ADM-001',
                    'role' => $role,
                    'status_aktif' => true,
                    'email' => strtolower($role) . '@smkpenus.sch.id',
                ],
            ], 200),
        ]);
    }

    /**
     * S1 & S2 & S3: Admin Dashboard (/bkk/admin).
     * Verify zero schema table/column checks, consolidated stats queries,
     * and query count well under legacy baseline (previously 74 queries).
     */
    public function test_admin_dashboard_has_zero_schema_queries_and_reduced_query_count(): void
    {
        $this->fakeAuth('ADMIN', 'usr-admin-001');

        $executedQueries = [];
        DB::listen(function ($query) use (&$executedQueries) {
            $executedQueries[] = $query->sql;
        });

        $response = $this->withHeader('Authorization', 'Bearer valid_token')
            ->get('/bkk/admin');

        $response->assertStatus(200);

        // S1: Assert ZERO information_schema, pg_tables, or table_constraints queries
        $schemaQueries = array_filter($executedQueries, function ($sql) {
            $lower = strtolower($sql);
            return str_contains($lower, 'information_schema')
                || str_contains($lower, 'sqlite_master')
                || str_contains($lower, 'pg_tables')
                || str_contains($lower, 'table_constraints');
        });
        $this->assertEmpty($schemaQueries, 'S1 Failed: Detected schema check queries on /bkk/admin: ' . json_encode($schemaQueries));

        // S2 & S3: Total query count should be substantially lower than legacy 74 queries
        $this->assertLessThanOrEqual(30, count($executedQueries), 'S2/S3 Failed: Query count exceeded threshold on /bkk/admin (got ' . count($executedQueries) . ')');

        // Verify consolidated query patterns are executed
        $hasConsolidatedBerita = false;
        $hasConsolidatedLowongan = false;
        $hasConsolidatedPkl = false;
        $hasConsolidatedMitra = false;

        foreach ($executedQueries as $sql) {
            $lower = strtolower($sql);
            if (str_contains($lower, 'berita') && str_contains($lower, 'published') && str_contains($lower, 'views_count')) {
                $hasConsolidatedBerita = true;
            }
            if (str_contains($lower, 'lowongan') && str_contains($lower, 'aktif') && str_contains($lower, 'pkl') && str_contains($lower, 'kerja')) {
                $hasConsolidatedLowongan = true;
            }
            if (str_contains($lower, 'penempatan_pkl') && str_contains($lower, 'berjalan') && str_contains($lower, 'selesai')) {
                $hasConsolidatedPkl = true;
            }
            if (str_contains($lower, 'mitra') && str_contains($lower, 'is_verified is true')) {
                $hasConsolidatedMitra = true;
            }
        }

        $this->assertTrue($hasConsolidatedBerita, 'S2: Berita metrics were not consolidated into 1 query');
        $this->assertTrue($hasConsolidatedLowongan, 'S2: Lowongan metrics were not consolidated into 1 query');
        $this->assertTrue($hasConsolidatedPkl, 'S2: Penempatan PKL metrics were not consolidated into 1 query');
        $this->assertTrue($hasConsolidatedMitra, 'S2: Mitra metrics were not consolidated into 1 query');
    }

    /**
     * S1: Siswa Dashboard & CV pages have zero schema queries.
     */
    public function test_student_pages_have_zero_schema_queries(): void
    {
        $this->fakeAuth('SISWA', 'usr-siswa-001');

        $executedQueries = [];
        DB::listen(function ($query) use (&$executedQueries) {
            $executedQueries[] = $query->sql;
        });

        $resDashboard = $this->withHeader('Authorization', 'Bearer valid_token')
            ->get('/bkk/me');
        $resDashboard->assertStatus(200);

        $resCv = $this->withHeader('Authorization', 'Bearer valid_token')
            ->get('/bkk/me/cv');
        $resCv->assertStatus(200);

        $schemaQueries = array_filter($executedQueries, function ($sql) {
            $lower = strtolower($sql);
            return str_contains($lower, 'information_schema')
                || str_contains($lower, 'sqlite_master')
                || str_contains($lower, 'pg_tables');
        });

        $this->assertEmpty($schemaQueries, 'S1 Failed: Found schema queries in student pages: ' . json_encode($schemaQueries));
    }

    /**
     * S2: Admin Tracer Study stats consolidated into a single SQL query.
     */
    public function test_admin_tracer_study_stats_consolidated_into_single_query(): void
    {
        $this->fakeAuth('ADMIN', 'usr-admin-001');

        $executedQueries = [];
        DB::listen(function ($query) use (&$executedQueries) {
            $executedQueries[] = $query->sql;
        });

        $response = $this->withHeader('Authorization', 'Bearer valid_token')
            ->get('/bkk/admin/tracer-study');

        $response->assertStatus(200);

        // 1. Verify unified consolidated stats query is present
        $consolidatedTracerStats = array_filter($executedQueries, function ($sql) {
            $lower = strtolower($sql);
            return str_contains($lower, 'tracer_respon')
                && str_contains($lower, 'bmw_bekerja')
                && str_contains($lower, 'avg_waktu_tunggu');
        });
        $this->assertCount(1, $consolidatedTracerStats, 'S2: Missing unified tracer stats query');

        // 2. Verify legacy individual count queries are eliminated (where "status_keterserapan" = '...')
        $individualCountQueries = array_filter($executedQueries, function ($sql) {
            $lower = strtolower($sql);
            return (str_contains($lower, 'where "status_keterserapan" =') || str_contains($lower, "where status_keterserapan ="))
                || (str_contains($lower, 'where "keselarasan_jurusan" =') || str_contains($lower, "where keselarasan_jurusan ="));
        });
        $this->assertEmpty($individualCountQueries, 'S2: Detected unconsolidated individual tracer queries: ' . json_encode($individualCountQueries));
    }

    /**
     * S2 & S3: Admin PKL Monitoring combines penempatan counts and avoids duplicate alert queries.
     */
    public function test_admin_pkl_monitoring_penempatan_consolidated_and_alerts_shared(): void
    {
        $this->fakeAuth('ADMIN', 'usr-admin-001');

        $executedQueries = [];
        DB::listen(function ($query) use (&$executedQueries) {
            $executedQueries[] = $query->sql;
        });

        $response = $this->withHeader('Authorization', 'Bearer valid_token')
            ->get('/bkk/admin/pkl/monitoring');

        $response->assertStatus(200);

        // S2: Penempatan PKL metrics consolidated into 1 query
        $consolidatedPenempatan = array_filter($executedQueries, function ($sql) {
            $lower = strtolower($sql);
            return str_contains($lower, 'penempatan_pkl')
                && str_contains($lower, 'berjalan')
                && str_contains($lower, 'selesai');
        });
        $this->assertCount(1, $consolidatedPenempatan, 'S2: Penempatan PKL stats were not consolidated into 1 query');

        // S2: Ensure separate status counts are eliminated (where "status" = 'BERJALAN')
        $legacyPenempatanCounts = array_filter($executedQueries, function ($sql) {
            $lower = strtolower($sql);
            return (str_contains($lower, "where \"status\" = 'berjalan'") || str_contains($lower, "where status = 'berjalan'"))
                || (str_contains($lower, "where \"status\" = 'selesai'") || str_contains($lower, "where status = 'selesai'"));
        });
        $this->assertEmpty($legacyPenempatanCounts, 'S2: Detected redundant penempatan_pkl where status counts: ' . json_encode($legacyPenempatanCounts));

        // S3: Jurnal pending and Laporan pending counts are only queried once (provided to AdminAlertsComposer)
        $jurnalCounts = array_filter($executedQueries, function ($sql) {
            $lower = strtolower($sql);
            return str_contains($lower, 'pkl_jurnal_harian') && str_contains($lower, 'menunggu');
        });
        $this->assertLessThanOrEqual(1, count($jurnalCounts), 'S3: Jurnal pending count queried redundantly by composer');

        $laporanCounts = array_filter($executedQueries, function ($sql) {
            $lower = strtolower($sql);
            return str_contains($lower, 'pkl_laporan_akhir') && str_contains($lower, 'ditinjau');
        });
        $this->assertLessThanOrEqual(1, count($laporanCounts), 'S3: Laporan pending count queried redundantly by composer');
    }

    /**
     * S2 & S3: Mitra Dashboard consolidates lowongan stats, reuses statusCounts, and shares alert counts.
     */
    public function test_mitra_dashboard_query_consolidation_and_alert_sharing(): void
    {
        $mitra = Mitra::first() ?? Mitra::create([
            'nama_perusahaan' => 'PT Mitra Efisiensi SQL',
            'singkatan' => 'MES',
            'password' => bcrypt('password'),
            'npwp' => '99.888.777.6-555.000',
            'is_verified' => true,
        ]);

        $executedQueries = [];
        DB::listen(function ($query) use (&$executedQueries) {
            $executedQueries[] = $query->sql;
        });

        $response = $this->withSession(['mitra_id' => $mitra->id])
            ->get('/bkk/dashboard');

        $response->assertStatus(200);

        // S1: Zero schema queries
        $schemaQueries = array_filter($executedQueries, function ($sql) {
            $lower = strtolower($sql);
            return str_contains($lower, 'information_schema') || str_contains($lower, 'sqlite_master');
        });
        $this->assertEmpty($schemaQueries, 'S1 Failed: Found schema queries on mitra dashboard: ' . json_encode($schemaQueries));

        // S2: Lowongan stats consolidated into 1 query with conditional aggregation
        $consolidatedLowongan = array_filter($executedQueries, function ($sql) {
            $lower = strtolower($sql);
            return str_contains($lower, 'lowongan')
                && str_contains($lower, "status = 'aktif'")
                && str_contains($lower, 'total');
        });
        $this->assertCount(1, $consolidatedLowongan, 'S2: Lowongan metrics not consolidated into single aggregation');

        // S2 & S3: Lamaran status counts runs 1 group by query which is reused for pelamar metrics and alerts
        $lamaranGroupBy = array_filter($executedQueries, function ($sql) {
            $lower = strtolower($sql);
            return str_contains($lower, 'lamaran') && str_contains($lower, 'group by "status"');
        });
        $this->assertLessThanOrEqual(1, count($lamaranGroupBy), 'S2/S3: Lamaran status grouping queried more than once');

        // S2 & S3: No separate individual queries for pelamar_interview or pelamar_diterima
        $legacyLamaranCounts = array_filter($executedQueries, function ($sql) {
            $lower = strtolower($sql);
            return str_contains($lower, 'lamaran')
                && (str_contains($lower, 'dipanggil interview') || str_contains($lower, 'diterima'))
                && str_contains($lower, 'count(');
        });
        $this->assertEmpty($legacyLamaranCounts, 'S2: Detected redundant separate lamaran status counts');
    }

    /**
     * S2: Accuracy check - verify consolidated stats produce correct values.
     */
    public function test_consolidated_stats_accuracy(): void
    {
        $this->fakeAuth('ADMIN', 'usr-admin-001');

        $response = $this->withHeader('Authorization', 'Bearer valid_token')
            ->getJson('/bkk/admin');

        $response->assertStatus(200);
        $data = $response->json('data.stats');

        $this->assertSame(Berita::count(), $data['total_berita']);
        $this->assertSame(Berita::where('status', 'PUBLISHED')->count(), $data['total_published']);
        $this->assertSame(Berita::where('status', 'DRAFT')->count(), $data['total_draft']);
        $this->assertSame(Lowongan::count(), $data['total_lowongan']);
        $this->assertSame(Lowongan::where('status', 'Aktif')->count(), $data['total_lowongan_aktif']);
        $this->assertSame(Mitra::count(), $data['total_mitra']);
        $this->assertSame(PenempatanPkl::where('status', 'BERJALAN')->count(), $data['total_siswa_pkl']);
        $this->assertSame(PenempatanPkl::where('status', 'SELESAI')->count(), $data['total_pkl_selesai']);
        $this->assertSame(Lamaran::count(), $data['total_pelamar']);
        $this->assertSame(TracerRespon::count(), $data['total_tracer_respon']);
    }
}
