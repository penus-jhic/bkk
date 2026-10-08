<?php

namespace App\Http\Middleware;

use App\Support\DevAuth;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyAuthToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1-2. Ambil data user: login uji coba lokal (lihat App\Support\DevAuth) atau verifikasi token ke Auth Microservice
        $userData = DevAuth::user($request) ?? $this->fetchUserFromAuthService($request);

        if ($userData instanceof Response) {
            return $userData;
        }

        // 3. Validasi status keaktifan akun
        if (isset($userData['status_aktif']) && $userData['status_aktif'] === false) {
            return $this->errorResponse($request, 'Akun pengguna sedang dinonaktifkan', Response::HTTP_FORBIDDEN);
        }

        // 4. Validasi Role (RBAC) jika parameter role disertakan pada route middleware
        if (!empty($roles)) {
            $userRole = strtoupper((string) ($userData['role'] ?? ''));
            $allowedRoles = array_map('strtoupper', $roles);

            if (!in_array($userRole, $allowedRoles, true)) {
                return $this->errorResponse($request, 'Akses ditolak: role ' . ($userData['role'] ?? 'UNKNOWN') . ' tidak memiliki izin untuk mengakses resource ini', Response::HTTP_FORBIDDEN);
            }
        }

        // 5. Merge metadata user ke dalam Request agar dapat diakses via $request->auth_user atau $request->input('auth_user')
        $request->merge([
            'auth_user' => $userData,
        ]);

        // Simpan juga ke request attributes & user resolver bawaan Laravel
        $request->attributes->set('auth_user', $userData);
        $request->setUserResolver(fn () => (object) $userData);

        return $next($request);
    }

    /**
     * Verifikasi access_token ke Auth Microservice. Mengembalikan data user, atau Response error.
     */
    protected function fetchUserFromAuthService(Request $request): array|Response
    {
        // 1. Ekstraksi access_token dari Request Cookie atau Authorization Bearer
        $token = $this->extractToken($request);

        if (empty($token)) {
            return $this->unauthenticated($request, 'Token otentikasi tidak ditemukan');
        }

        // 2. Forward access_token ke Auth Microservice dengan format JSON Payload
        $baseUrl = config('services.auth_service.base_url', 'http://localhost:3002');
        $verifyUrl = rtrim($baseUrl, '/') . '/api/user/verify';

        try {
            $response = Http::timeout(5)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($verifyUrl, [
                    'access_token' => $token,
                ]);
        } catch (\Throwable $e) {
            Log::error('Auth service connection failed: ' . $e->getMessage());
            return $this->errorResponse($request, 'Layanan otentikasi sedang tidak tersedia. Silakan coba beberapa saat lagi.', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        if (!$response->successful() || !$response->json('success')) {
            $errorMessage = $response->json('message') ?? 'Token otentikasi tidak valid atau telah kedaluwarsa';
            return $this->unauthenticated($request, $errorMessage);
        }

        $userData = $response->json('data');

        if (!$userData) {
            return $this->unauthenticated($request, 'Data pengguna tidak ditemukan dalam respon otentikasi');
        }

        return $userData;
    }

    /**
     * Request API dapat respon JSON 401, request halaman (browser) diarahkan ke halaman login.
     */
    protected function unauthenticated(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], Response::HTTP_UNAUTHORIZED);
        }

        return redirect()->away(config('services.auth_service.login_url', '/login'));
    }

    /**
     * Request API dapat respon JSON, request halaman (browser) ditampilkan halaman error Laravel.
     */
    protected function errorResponse(Request $request, string $message, int $status): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }

        abort($status, $message);
    }

    /**
     * Ekstraksi access_token dari:
     * - Authorization Bearer: Authorization: Bearer <token>
     * - Request Cookie: access_token
     */
    protected function extractToken(Request $request): ?string
    {
        // 1. Authorization: Bearer <token>
        $bearerToken = $request->bearerToken();
        if (!empty($bearerToken)) {
            return trim($bearerToken);
        }

        // 2. Request Cookie: access_token
        $cookieToken = $request->cookie('access_token') ?? $request->cookies->get('access_token');
        if (!empty($cookieToken) && is_string($cookieToken)) {
            return trim($cookieToken);
        }

        // Fallback Header Cookie mentah jika ada
        $rawCookie = $request->header('cookie');
        if (!empty($rawCookie) && is_string($rawCookie)) {
            if (preg_match('/(?:^|;\s*)access_token=([^;]+)/', $rawCookie, $matches)) {
                return trim(urldecode($matches[1]));
            }
        }

        return null;
    }
}
