<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Login uji coba lokal (App\Support\DevAuth) hanya boleh aktif di APP_ENV=local + AUTH_DEV_BYPASS=true
 * + request langsung dari localhost. Selain itu wajib kembali ke verifikasi token biasa.
 */
class DevAuthBypassTest extends TestCase
{
    use RefreshDatabase;

    private function enableBypass(string $env = 'local'): void
    {
        $this->app['env'] = $env;
        config(['services.auth_service.dev_bypass' => true, 'services.auth_service.dev_role' => 'ADMIN']);
        Http::fake(); // memastikan auth service tidak dipanggil saat bypass aktif
    }

    public function test_local_request_from_loopback_is_logged_in_without_token(): void
    {
        $this->enableBypass();

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->get('/bkk/admin')
            ->assertOk()
            ->assertSee('Login dev: ADMIN');

        Http::assertNothingSent();
    }

    public function test_dev_role_cookie_still_goes_through_rbac(): void
    {
        $this->enableBypass();

        $response = $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withCredentials()
            ->withCookie('dev_auth_role', 'SISWA')
            ->getJson('/bkk/admin');

        $response->assertForbidden();
    }

    public function test_request_from_outside_localhost_is_not_bypassed_even_with_forwarded_header(): void
    {
        $this->enableBypass();

        $response = $this->withServerVariables(['REMOTE_ADDR' => '172.18.0.5'])
            ->withHeader('X-Forwarded-For', '127.0.0.1')
            ->getJson('/bkk/admin');

        $response->assertUnauthorized();
    }

    public function test_bypass_is_ignored_outside_local_environment(): void
    {
        $this->enableBypass('production');

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->getJson('/bkk/admin')
            ->assertUnauthorized();
    }

    public function test_bypass_is_off_when_flag_is_not_set(): void
    {
        $this->app['env'] = 'local';
        config(['services.auth_service.dev_bypass' => false]);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->getJson('/bkk/admin')
            ->assertUnauthorized();
    }
}
