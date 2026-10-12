# Security Audit Report — BKK (penus-jhic/bkk)

- **Date:** 2026-10-12
- **Scope:** Laravel 12 application (`app/`, `routes/`, `config/`, `resources/views/`, `database/`), deployment files (`Dockerfile`, `compose.yml`, `.env.example`), and npm + Composer dependencies.
- **Method:** Static review of tracked source, configuration and dependency manifests. Dependency checks used `npm audit --json`, the security-auditor skill script, and `composer audit`. The app was **not** run or tested dynamically, and the existing PHPUnit suite was **not** executed.

## Summary

| Severity | Count | Area |
|---|---|---|
| High | 2 | Docker image contents / dev-login gate |
| Medium | 3 | Container hardening, cookies, default credentials, dependency pinning |
| Low | 4 | Hardening and hygiene |
| Dependency (critical, dev-only) | 2 | `concurrently` / `shell-quote` |

Composer reports **no** known advisories. The application-layer controls reviewed (RBAC, ownership scoping, path traversal, login throttling, upload handling) are generally sound. The most serious risks are in the deployment setup, not in the PHP code.

---

## High

### H-1. Secrets and dev configuration are baked into the Docker image
**Where:** `Dockerfile` — `COPY . .` with no `.dockerignore` file.

- `.env` (APP_KEY, DB_PASSWORD, `AUTH_DEV_BYPASS`), `.git/`, `node_modules/`, and `storage/` are all copied into the image layers. Anyone with image pull access can read them, even if they are removed later in the build.
- The local `.env` currently has `APP_ENV=local`, `APP_DEBUG=true`, and `AUTH_DEV_BYPASS=true`. If this file is used for a deployed container, the dev-login gate in H-2 and verbose error pages are active.

**Remediation:** Add a `.dockerignore` that excludes `.env*`, `.git`, `node_modules`, `vendor` (or install inside the image), `storage/logs`, `*.log`, and `.phpunit.result.cache`. Inject secrets at runtime through `env_file`/secrets, and never bake them into the image.

### H-2. Dev-login bypass depends on the source IP alone
**Where:** `app/Support/DevAuth.php`, `app/Http/Controllers/DevAuthController.php`, `config/services.php`.

- `/bkk/dev/masuk/{role}` lets anyone log in as ADMIN, KEPALA_SEKOLAH, SISWA, or ALUMNI without the auth service. It is only enabled when `APP_ENV=local`, `AUTH_DEV_BYPASS=true`, and `REMOTE_ADDR` is `127.0.0.1` or `::1`.
- The code correctly uses `REMOTE_ADDR` instead of the spoofable `X-Forwarded-For`. However, if a reverse proxy runs on the same host, or the container uses host networking, all proxied traffic arrives with `REMOTE_ADDR` = `127.0.0.1`. Any internet visitor would then get admin access.
- The `.env` contains `AUTH_DEV_BYPASS=true`, and the route is registered at boot whenever the flag is true, so one misconfigured environment is enough.

**Remediation:** Make the bypass fail closed. Also refuse to boot when `AUTH_DEV_BYPASS=true` and `APP_ENV` is not `local`, and do not rely on IP alone. Ensure production `.env` sets `AUTH_DEV_BYPASS=false`, `APP_ENV=production`, and `APP_DEBUG=false`.

---

## Medium

### M-1. Container runs the PHP development server as root, with dev dependencies
**Where:** `Dockerfile`

- `CMD php artisan serve` is the PHP built-in server, which is not intended for production.
- `composer install` runs without `--no-dev --optimize-autoloader`, and `npm install` runs inside the image.
- No `USER` directive, so the process runs as root.
- `FROM composer:latest` is unpinned, so builds are not reproducible and a compromised upstream tag would flow straight into the build.

**Remediation:** Use a multi-stage build. Pin the base image by version or digest. Install `--no-dev`, run `php-fpm` or Octane behind nginx, run as a non-root user, and make the filesystem read-only except `storage/` and `bootstrap/cache/`.

### M-2. Default seeded password is shared by all partner accounts
**Where:** `database/seeders/MitraSeeder.php` (all accounts use `Password123!`). The dev page `resources/views/dev/masuk.blade.php` also shows it.

If the seeder runs against a non-local database, every Mitra account has a publicly known password.

**Remediation:** Generate random passwords per account, or require a password reset on first login. Do not run seeders outside local development.

### M-3. Unescaped rendering of admin-authored Markdown
**Where:** `resources/views/index/pages/berita-detail.blade.php:228` (`{!! $berita->rendered_konten !!}`), `app/Models/Berita.php:88`.

The output is produced by `Str::markdown` with `html_input => strip` and `allow_unsafe_links => false`, which is a sound configuration. The risk is that a future change to these options, or to the sanitizer, would produce stored XSS. The applicant CV preview (`mitra/pages/pelamar/detail.blade.php:97`) uses the same pattern with applicant-supplied content, which makes it more exposed. A test exists (`SecurityRemediationTest::test_stored_xss_in_cv_markdown_is_stripped_and_neutralized`).

**Remediation:** Keep the sanitizer options locked, and add an HTML purifier (for example `ezyang/htmlpurifier`) as a second layer for applicant-supplied content. Confirm the test passes in CI.

---

## Low

### L-1. `resolveAuthUser` falls back to request input
**Where:** `app/Http/Controllers/Me/MeController.php:968`

`$request->input('auth_user')` is a fallback after the request attribute. Because `VerifyAuthToken` always sets the attribute, this is not exploitable on the current routes. It is still a trust boundary that a client can write to, so a future route without the middleware could accept forged identity.

**Remediation:** Remove the `input()` fallback and read only from `$request->attributes`.

### L-2. Logout is reachable via GET
**Where:** `routes/web.php` (`Route::match(['GET', 'POST'], '/logout', ...)`)

A cross-site `<img src="/bkk/logout">` can log users out. This is low impact (no data change), but it is a CSRF-style action.

**Remediation:** Accept only `POST` for logout.

### L-3. Tooling file committed to `storage/`
**Where:** `storage/framework/lsp-21184b397484a4a6.php`

An editor helper that bootstraps the app and calls `getcwd()` is committed. It is not under `public/`, but it is executable PHP that does not belong in version control.

**Remediation:** Remove it from git and add it to `.gitignore`.

### L-4. Unresolved submodule pointer
**Where:** `FeLandingPageJhic` is tracked as a gitlink (mode 160000) with no `.gitmodules`.

Clones will not fetch it, and it is unclear what it contains.

**Remediation:** Add `.gitmodules` or remove the gitlink.

---

## Dependencies

### npm (`package.json`, `package-lock.json`)
Reported by `npm audit --json` and by the security-auditor skill script. Both report the same two findings.

| Package | Severity | Advisory | Affected range | Notes |
|---|---|---|---|---|
| `shell-quote` 1.8.4–1.10.0 | Critical (upstream CVSS 8.1) | GHSA-pqg4-j6r4-53mv — `quote()` command injection via a line terminator after a `{ comment }` token (CWE-78) | `>=1.8.4 <1.11.0` | Transitive, via `concurrently` |
| `concurrently` 9.2.3–9.2.4, 10.0.0–10.0.5 | Critical (as reported by npm) | Inherits the `shell-quote` advisory | — | Direct devDependency |

**Exposure:** Both are **devDependencies** used only by `composer run dev` (`composer.json:45`). They do not ship in a production runtime, so the risk applies to developer machines. Running untrusted input through the `concurrently` command line could still trigger it.

**Remediation:** `npm audit fix` (or upgrade `concurrently` to a version outside the affected ranges, which pulls a fixed `shell-quote`). Re-run `npm audit` to confirm 0 vulnerabilities.

### Composer (`composer.json`, `composer.lock`)
`composer audit` reports **no** security advisories.

---

## Controls reviewed and found sound

- **Path traversal:** `StaticAssetController::deliverFile` rejects `..` and null bytes, resolves `realpath`, and checks the result is inside the base directory. Symlink escapes are covered.
- **Authorization (IDOR):** Mitra pages scope every lookup through `$mitra->lowongans()`, and Me pages filter by `user_id` (for example `cvEditVersion`, `cvSetMain`). Test coverage exists in `SecurityRemediationTest`.
- **RBAC:** `verify.auth:...` checks roles case-insensitively against a fixed allow-list and rejects deactivated accounts.
- **Token handling:** Bearer and cookie tokens are validated by the auth service. Query-string tokens are rejected (test `test_verify_auth_token_rejects_query_parameter_access_token`), and auth errors do not leak internal exceptions.
- **Mitra login:** Passwords use `Hash::check`, sessions are regenerated on login, login is throttled to 5/min, and `password` is hidden from serialization.
- **Uploads:** Logo uploads are validated by MIME type and size, renamed to random names, and the directory has an `.htaccess` that disables PHP execution.
- **Public form:** `storeKerjasama` is throttled and validated.
- **SQL:** All raw SQL uses bindings, and no user input reaches `DB::raw` unbound.
- **Debug/exec functions:** No `eval`, `exec`, `shell_exec`, `unserialize`, or `dd()` calls were found in `app/`, `routes/`, or `config/`.

---

## Remediation plan (priority order)

1. **Now:** Fix or disable PostgREST (C-1). Remove its superuser connection and anon grants, and rotate `DB_PASSWORD`.
2. **Before any deploy:** Add `.dockerignore` and inject secrets at runtime (H-1). Confirm the production `.env` has `APP_ENV=production`, `APP_DEBUG=false`, `AUTH_DEV_BYPASS=false` (H-2).
3. **Next release:** Replace the PHP dev server with php-fpm behind nginx, run as non-root, install `--no-dev`, and pin the base image (M-1).
4. **Next release:** Set `SESSION_SECURE_COOKIE=true` and use HTTPS for the auth service (M-3). Remove shared seeded passwords (M-2).
5. **Housekeeping:** `npm audit fix` for the `concurrently`/`shell-quote` chain. Apply the low-severity items L-1 to L-4.

## Limitations

- Static review only. No dynamic testing, fuzzing, or penetration test was performed.
- The PHPUnit suite (including `SecurityRemediationTest`) was not run, so the claims about existing tests are based on reading the test names.
- The auth microservice (`AUTH_SERVICE_URL`) is outside this repository and was not reviewed.
- Runtime headers (CSP, HSTS, X-Frame-Options) were not verified, because they depend on the reverse proxy configuration, which is not in the repo.
