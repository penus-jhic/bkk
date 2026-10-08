<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Login uji coba KHUSUS development lokal, supaya halaman admin/siswa bisa dites tanpa menjalankan auth service.
 *
 * Hanya aktif kalau ketiga syarat ini terpenuhi sekaligus:
 *  1. APP_ENV=local
 *  2. AUTH_DEV_BYPASS=true
 *  3. Request datang langsung dari mesin sendiri (REMOTE_ADDR 127.0.0.1 / ::1, bukan lewat reverse proxy)
 *
 * Peran dipilih lewat halaman /bkk/dev/masuk (disimpan di cookie), bawaannya AUTH_DEV_ROLE.
 */
class DevAuth
{
    public const COOKIE = 'dev_auth_role';

    /**
     * Persona uji coba. Id siswa/alumni sama dengan data seeder (ProfilSiswaSeeder) supaya halaman /bkk/me berisi data.
     */
    public const PERSONAS = [
        'ADMIN' => [
            'id' => 'usr-admin-001', 'username' => 'admin', 'nama_lengkap' => 'Admin Uji Coba',
            'email' => 'admin@smkpenus.sch.id', 'nomor_induk' => 'ADM-DEV-001',
            'label' => 'Admin BKK', 'desc' => 'Dashboard admin, berita, mitra, lowongan, PKL, tracer study', 'home' => '/bkk/admin',
        ],
        'KEPALA_SEKOLAH' => [
            'id' => 'usr-kepsek-001', 'username' => 'kepsek', 'nama_lengkap' => 'Kepala Sekolah Uji Coba',
            'email' => 'kepsek@smkpenus.sch.id', 'nomor_induk' => 'KS-DEV-001',
            'label' => 'Kepala Sekolah', 'desc' => 'Akses admin yang sama dengan peran Admin', 'home' => '/bkk/admin',
        ],
        'SISWA' => [
            'id' => 'usr-siswa-001', 'username' => 'siswa', 'nama_lengkap' => 'Siswa Uji Coba',
            'email' => 'siswa@smkpenus.sch.id', 'nomor_induk' => '0061234567',
            'label' => 'Siswa', 'desc' => 'Portal siswa: CV, lamaran, jurnal & laporan PKL', 'home' => '/bkk/me',
        ],
        'ALUMNI' => [
            'id' => 'usr-alumni-001', 'username' => 'alumni', 'nama_lengkap' => 'Alumni Uji Coba',
            'email' => 'alumni@smkpenus.sch.id', 'nomor_induk' => '1920.08.112',
            'label' => 'Alumni', 'desc' => 'Portal alumni: CV & lamaran kerja', 'home' => '/bkk/me',
        ],
    ];

    /**
     * Rute /dev/masuk didaftarkan? (Dicek saat boot, jadi tanpa syarat alamat IP.)
     */
    public static function configured(): bool
    {
        return app()->environment('local') && (bool) config('services.auth_service.dev_bypass', false);
    }

    public static function enabled(Request $request): bool
    {
        // REMOTE_ADDR langsung, bukan $request->ip(), supaya tidak bisa dipalsukan lewat header X-Forwarded-For
        return self::configured() && in_array($request->server('REMOTE_ADDR'), ['127.0.0.1', '::1'], true);
    }

    public static function currentRole(Request $request): ?string
    {
        if (! self::enabled($request)) {
            return null;
        }

        $role = strtoupper((string) ($request->cookie(self::COOKIE) ?: config('services.auth_service.dev_role', 'ADMIN')));

        return array_key_exists($role, self::PERSONAS) ? $role : null;
    }

    /**
     * Data user palsu dengan bentuk yang sama seperti respon auth service (/api/user/verify).
     */
    public static function user(Request $request): ?array
    {
        $role = self::currentRole($request);

        if ($role === null) {
            return null;
        }

        $persona = self::PERSONAS[$role];

        return [
            'id' => $persona['id'],
            'username' => $persona['username'],
            'nama_lengkap' => $persona['nama_lengkap'],
            'email' => $persona['email'],
            'nomor_induk' => $persona['nomor_induk'],
            'role' => $role,
            'status_aktif' => true,
            'is_dev_login' => true,
        ];
    }
}
