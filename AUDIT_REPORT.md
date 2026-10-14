# Audit Report: XSS, SQL Efficiency, Stale Data

- **Date:** 2026-10-13
- **Branch / commit:** `CV-Scope` @ `09da6d4`
- **Scope:** three areas, (1) XSS vulnerabilities, (2) inefficient SQL queries, (3) frontend still using dummy data even though the backend already has the data.

## Method

- Reviewed the routes, controllers, services, models, view composers, and every Blade view that outputs raw HTML (`{!! !!}`, `innerHTML`, `marked.parse`, inline `onclick`, Alpine attributes).
- **Query counts** were measured against the local MariaDB by requesting each page through the HTTP kernel (dev-auth personas ADMIN/SISWA/ALUMNI plus a mitra session) and logging every query with `DB::listen`. All requests ran inside one transaction that was rolled back.
- **XSS** was confirmed by planting a malicious job title and company name, rendering `/bkk/me` and `/bkk/me/lamaran`, and inspecting the output. Also rolled back.
- No code was changed during the audit.

## Summary

| # | Area | Severity | Finding |
|---|---|---|---|
| X1 | XSS | **High** | Job title breaks out of the `onclick` handler on the student dashboard (confirmed) |
| X2 | XSS | **High** | Application detail drawer builds HTML from mitra data via `innerHTML` (confirmed) |
| X3 | XSS | Amplifier | `access_token` is written into page JavaScript, so any XSS can steal it |
| X4 | XSS | Medium | `marked.parse()` → `innerHTML` without sanitizing (CV preview, AI chat) |
| X5 | XSS | Medium | AI quick chips have the same `'` breakout as X1 |
| X6 | XSS | Low | Toasts and the jurnal card use `innerHTML` for messages |
| S1 | SQL | High | `Schema::hasTable()` on every request (32 schema queries on `/bkk/admin`) |
| S2 | SQL | Medium | One `COUNT(*)` query per statistic |
| S3 | SQL | Medium | View composers re-run counts the controller already ran |
| S4 | SQL | Low | **[FIXED]** Student profile row read twice per request; unused eager load |
| S5 | SQL | Low | **[FIXED]** CV version list loads full markdown/JSON for every version |
| S6 | SQL | Low | **[FIXED]** `LIKE '%q%'` search; `views_count` written on every GET |
| D1 | Stale data | **High** | Dashboard CV score reads old columns, falls back to dummy values |
| D2 | Stale data | Medium | CV editor shows dummy parameter scores; dummy suggestions in the API |
| D3 | Stale data | Medium | Application timeline hard-coded; `lamaran_riwayat_status` ignored |
| D4 | Stale data | Medium | Fixed match scores and skills shown to students and mitra |
| D5 | Stale data | Low | **[FIXED]** Public stats intentional marketing copy retained as verified with user |
| D6 | Stale data | Env | **[FIXED]** Pending `cv_resumes` migrations executed, error logging (`report($e)`) added to `catch` blocks |

---

## 1. XSS

### X1: Job title breaks out of `onclick` (High, confirmed)

**Location:** `resources/views/me/pages/dashboard.blade.php:301`

```blade
onclick="applyVacancy('{{ $v['id'] }}', '{{ $v['title'] }}')"
```

Blade escapes `'` as `&#039;`, but the browser decodes HTML entities in the attribute **before** running the JavaScript. Blade escaping therefore doesn't protect a JS string inside an event-handler attribute.

**Proof:** with the job title `PoC');alert(document.domain);//`, the rendered attribute was:

```html
onclick="applyVacancy('2', 'PoC&#039;);alert(document.domain);//')"
```

and the browser runs it as:

```js
applyVacancy('2', 'PoC');alert(document.domain);//')
```

**Who can exploit it:** any mitra account. `MitraController::lowonganStore` accepts any string as the title and publishes immediately (`MitraController.php:191`, `'status' => 'Aktif'`). The code runs in a student's session when they click "Lamar Sekarang".

**Fix:**

```blade
onclick="applyVacancy(@js($v['id']), @js($v['title']))"
```

Or better, put the values in `data-*` attributes and attach the listener in JavaScript.

### X2: Application detail drawer builds HTML via `innerHTML` (High, confirmed)

**Location:** `openDetailModal()` in `resources/views/me/pages/lamaran.blade.php` (~L330) and `resources/views/me/pages/dashboard.blade.php` (~L428).

The drawer is built with a template literal and assigned to `body.innerHTML`, inserting these fields without escaping:

- `app.position`, `app.company`, `app.location`, `app.status`, `app.type`
- `app.interview.date / time / mode / place / pic`
- `t.title`, `t.desc`, `t.date` in the timeline
- `app.color` (inside a `style` attribute)

Most of these come from data a mitra controls: `lowongan.judul`, `lowongan.lokasi`, `mitra.nama_perusahaan`, and `jadwal_interview.lokasi_atau_url` / `pic_pewawancara`.

**Proof:** with the company name `<img src=x onerror=alert(document.domain)>`, the payload reached the `openDetailModal({...})` JSON on `/bkk/me/lamaran`. `@json` makes it safe inside the attribute, but once the JSON is parsed the raw string goes into `innerHTML` and runs when the student opens the drawer.

**Fix:** move the `escapeHtml()` helper that already exists in `me/pages/cv.blade.php` into `me/master.blade.php` and wrap every interpolated value:

```js
`<h3 class="...">${escapeHtml(app.position)}</h3>`
```

For `app.color`, check it against `/^#[0-9a-f]{6}$/i` before use.

### X3: Access token exposed to JavaScript (amplifier)

**Location:** `resources/views/me/pages/cv.blade.php:372-378`, `resources/views/me/pages/cv-edit.blade.php:419-425`

```blade
const clientToken = @json($clientToken); // access_token from cookie / bearer
```

Any XSS on a `/bkk/me` page (X1, X2) can `fetch('/bkk/me/cv')` and read the token out of the HTML, even when the `access_token` cookie is HttpOnly. That turns a one-page XSS into theft of the SSO session.

**Fix:** remove `clientToken` and the `Authorization` header. The AI endpoints are same-origin, `credentials: 'include'` already sends the cookie, and `VerifyAuthToken::extractToken()` already reads it.

### X4: Markdown rendered into `innerHTML` without sanitizing (Medium)

**Locations:**

- `resources/views/me/pages/cv.blade.php:328`: `container.innerHTML = marked.parse(md)`
- `resources/views/me/pages/cv-edit.blade.php:469`: live preview
- `resources/views/me/pages/cv-edit.blade.php` (~L774, assigned at L860): AI chat reply `marked.parse(reply)`

`marked` doesn't sanitize HTML. The CV preview is mostly self-XSS (the student's own CV), but the AI reply is model output that text pasted into the CV can steer (prompt injection).

**Fix:** add DOMPurify and use `DOMPurify.sanitize(marked.parse(text))`.

### X5: AI quick-chip handler breakout (Medium)

**Location:** `resources/views/me/pages/cv-edit.blade.php` (quick chips in `renderAiChatResponseElement`)

```js
onclick="sendChatPrompt('${escapeHtml(chip).replace(/'/g, "\\'")}')"
```

`escapeHtml` converts `'` to `&#039;` first, so `.replace(/'/g, ...)` never matches. The browser then decodes `&#039;` back to `'`, which is the same breakout as X1.

**Fix:** store the chip text in `data-prompt="${escapeHtml(chip)}"` and use a delegated click listener that reads `el.dataset.prompt`.

### X6: Toast messages and jurnal card via `innerHTML` (Low)

**Locations:**

- `showToast` in `resources/views/{me,admin,mitra}/master.blade.php` (`${msg}` / `${message}`)
- `resources/views/me/pages/jurnal.blade.php:256` (`${activity}`, the student's own input)

Some toast messages come from the server and include exception text (`MeController.php:415, 622, 755` return `$e->getMessage()`).

**Fix:** build the element and set the message with `textContent`.

### Checked and safe

- `Berita::getRenderedKontenAttribute()` and the pelamar CV view use `Str::markdown()` with `html_input => 'strip'` and `allow_unsafe_links => false`.
- `@json(...)` uses Laravel's default flags (`JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT`), so it is safe inside attributes.
- `confirm('… {{ addslashes($x) }}')` handlers (berita, mitra, permohonan): `addslashes` runs before HTML escaping, so the decoded string stays escaped.
- Flash messages (`session('success'|'error')`) are rendered with `{{ }}`.
- Alpine attributes containing `{{ }}` only interpolate integers.

---

## 2. Inefficient SQL queries

### Measured queries per page load

| Page | Role | Queries | `information_schema` lookups | Identical repeated queries |
|---|---|---|---|---|
| `/bkk/admin` | ADMIN | **74** | **32** | 29 |
| `/bkk/admin/pkl/monitoring` | ADMIN | 21 | 0 | 9 |
| `/bkk/dashboard` | MITRA | 18 | 0 | 3 |
| `/bkk/admin/tracer-study` | ADMIN | 17 | 0 | 7 |
| `/bkk/me` | SISWA / ALUMNI | 16 | 5 | 1–2 |
| `/bkk/me/lamaran`, `/jurnal`, `/laporan` | SISWA | 11 | 3–4 | 0 |
| `/bkk/me/cv`, `/cv/edit` | SISWA | 9 | 4 | 1 |
| `/bkk/admin/lowongan` | ADMIN | 10 | 0 | 3 |
| Public pages (`/bkk`, berita, lowongan, kerja-sama) | public | 4–10 | 0 | 0–2 |

Counts include the database session read/write (`SESSION_DRIVER=database`).

### S1: `Schema::hasTable()` on every request (High)

**Locations:**

- `app/Http/Controllers/DashboardController.php:33`: `$has = fn ($t) => Schema::hasTable($t)` is called about 20 times; `berita` and `lowongan` are each checked 5 times.
- `app/View/Composers/AdminAlertsComposer.php:27`
- `app/Http/Controllers/Me/MeController.php`: every helper (L48, 145, 229, 292, 508, 586, 660, 710, 839, 866, 922, 970, 1005, 1052, 1109, 1139)
- `app/Models/CvResume.php:60` (`ensureProfilSiswaExists`)

Each call is an `information_schema.tables` query, accounting for 32 of the 74 queries on `/bkk/admin`. The tables are created by migrations, so these checks protect nothing in a migrated database.

**Fix:** remove the checks. If an optional table really is needed, check once and cache the result for the process (e.g. a `static` variable).

### S2: One `COUNT(*)` per statistic (Medium)

| Location | Now | Can be |
|---|---|---|
| `DashboardController.php:35-54` | ~18 counts | ~6 (one per table) |
| `AdminTracerController.php:23-39` | 9 counts + 1 AVG | 2 `GROUP BY` queries + 1 AVG |
| `AdminPklController.php:53-59` | 5 counts | 3 |
| `MitraController.php:42-49` | 6 counts + `pluck('id')` | 2–3 |

Example for berita:

```php
$berita = Berita::selectRaw("
    COUNT(*) AS total,
    SUM(status = 'PUBLISHED') AS published,
    SUM(status = 'DRAFT') AS draft,
    COALESCE(SUM(views_count), 0) AS views
")->first();
```

In `MitraController.php:45-47`, the three lamaran counts duplicate `$statusCounts` (L66), which already contains them:

```php
'total_pelamar'     => $statusCounts->sum(),
'pelamar_interview' => (int) ($statusCounts['Dipanggil Interview'] ?? 0),
'pelamar_diterima'  => (int) ($statusCounts['Diterima'] ?? 0),
```

### S3: View composers repeat controller counts (Medium)

- `AdminAlertsComposer` re-runs the counts for pending permohonan (`MENUNGGU_REVIEW`), jurnal (`Menunggu`) and laporan (`Ditinjau`) that `DashboardController` and `AdminPklController` have just run. These show up as `x2` in the query log.
- `MitraAlertsComposer` runs `$mitra->lowongans()->pluck('id')` again after `MitraController::dashboard` already did.

**Fix:** have the controller share its numbers (e.g. `View::share('adminAlerts', ...)`), or have both read from one per-request service.

### S4: Student profile read twice per request; unused eager load (Low) — **[FIXED]**

- `MeController::resolveAuthUser()` → `ensureProfilSiswa()` runs `firstOrCreate` on every `/bkk/me` request (L1101), then `ProfilSiswa::find($userId)` reads the same row again (L49, L1110).
- `cvUpdate()` calls `ensureProfilSiswa()` twice (via `resolveAuthUser` at L363 and directly at L367), and `CvResume::saveDraft/saveVersion` calls `ensureProfilSiswaExists()` a third time.
- `dashboard()` eager-loads `penempatanPkl.mitra` (L49), but the result is never used.

**Resolution:**
- `ensureProfilSiswa()` now caches and returns the `ProfilSiswa` instance into `$this->currentProfilSiswa` and sets `$request->attributes->set('profil_siswa_model', ...)`.
- `dashboard()` and `enrichProfileFromDatabase()` reuse `$this->currentProfilSiswa` without re-querying `ProfilSiswa::find($userId)`.
- Dropped unused eager load `penempatanPkl.mitra` in `dashboard()`.
- Removed redundant `$this->ensureProfilSiswa()` call in `cvUpdate()`.
- Added request-lifecycle static memoization in `CvResume::ensureProfilSiswaExists()` to avoid duplicate queries within the same request.

### S5: CV version list loads full rows (Low) — **[FIXED]**

`MeController.php:153, 237, 316`: `CvResume::where(...)->latest('id')->get()` loads `raw_markdown` (longText) and `feedback` (JSON) for **every** version just to show a list. The active CV is also fetched in a separate query, although it's part of the same list.

**Resolution:**
- CV version queries in `cv()`, `cvEdit()`, and `cvEditVersion()` now specify lightweight columns: `select(['id', 'user_id', 'created_at', 'is_main', 'score', 'checksum'])`.
- Active CV in `cv()` and `cvEdit()` is resolved from the collection (`firstWhere('is_main', true)`), loading full row contents (`raw_markdown` and `feedback`) only for that single active record via `CvResume::find($activeHeader->id)`.
- Updated `cv-edit.blade.php` and `cv.blade.php` score badges to display `$ver->score` directly without parsing the JSON `feedback`.
- Replaced inline `@js($v->raw_markdown)` in `cv-edit.blade.php` modal with asynchronous on-demand loading: `loadVersionContent(versionId)` fetches full markdown via `/bkk/me/cv/{id}/edit` only when the user clicks "Muat Teks".

### S6: Search and view counters (Low) — **[FIXED]**

- `LIKE '%q%'` / `ilike` searches (`PublicController.php:105-107, 175-178`, `MitraController.php:99-101, 329-334`, `AdminPklController.php:27-30`) can't use an index. Retained as current dataset sizes remain small and performant without extra overhead.
- `increment('views_count')` on every detail-page GET (`PublicController.php:142, 257`) writes to the database on every view.

**Resolution:**
- Added session-based view debounce for `PublicController::beritaDetail` (`viewed_berita_{id}`) and `PublicController::lowonganDetail` (`viewed_lowongan_{id}`).
- Views counter now only increments once per unique visitor session, eliminating redundant DB write operations on refreshes or rapid reloads.

**Expected result:** removing S1 and merging the counts in S2/S3 should take `/bkk/admin` from 74 queries to roughly 15 (an estimate, not measured).

---

## 3. Stale / dummy data

### D1: Dashboard CV score reads old columns (High)

**Location:** `app/Http/Controllers/Me/MeController.php:59-61`

```php
$cvScore['total'] = $primaryCv->skor_total_ai;
$cvScore['params'] = $primaryCv->skor_parameter_json;
```

AI scoring (`AiCvService::evaluateAndStoreScore`) now writes `cv_resumes.score` and `cv_resumes.feedback`, and `/bkk/me/cv` reads those (L165). The dashboard still reads the legacy `skor_total_ai` / `skor_parameter_json`, which nothing updates anymore. If those are empty, the dashboard falls back to `MeDataService::getCvScore()` (hard-coded 84 / 89, `MeDataService.php:60-84`) and then `?? 82` in `me/pages/dashboard.blade.php:116`.

**Result:** the dashboard score doesn't match the CV page score.

**Fix:** use `score` and `feedback['parameters']`, the same way `cv()` does (L161-179).

### D2: Dummy CV scores and suggestions (Medium)

- `cvEdit()` and `cvEditVersion()` start from `MeDataService::getCvScore()` (L222, L308) and only overwrite the total when feedback exists. A CV that has never been scored shows made-up parameter scores.
- `cv()` passes `MeDataService::getCvSuggestions()` (hard-coded, `MeDataService.php:89-155`) to the view and returns it in the JSON API as `suggestions` (L138, L187), although the real endpoint `/api/ai/cv/suggestions` exists.

**Fix:** with no feedback, show an empty "not analyzed yet" state; take suggestions from `feedback` or the AI endpoint.

### D3: Application timeline hard-coded (Medium)

**Location:** `MeController.php:894-899`

The four timeline steps and their descriptions are fixed strings, with only the `done` flag derived from status. Meanwhile:

- Mitra status changes and notes are written to `lamaran_riwayat_status` (`MitraController.php:447-453`).
- New applications also write a row (`MeController.php:840`).
- `Lamaran::riwayatStatus()` already exists.

Students never see the mitra's notes or the real dates.

**Fix:** eager-load `riwayatStatus` in `getMappedApplications()` and map it to `timeline`.

### D4: Fixed match scores and skills (Medium)

| Location | Hard-coded value | Seen by |
|---|---|---|
| `MeController.php:955` | `'match' => 95` for every vacancy | students |
| `MeController.php:833` | `'skor_match_ai' => 90` for every application | mitra, admin |
| `app/Models/Lamaran.php` `getCvScoreAttribute()` | `?: 85` fallback | mitra |
| `app/Models/Lamaran.php:104-107` `getSkillsAttribute()` | the same 3 skills for every applicant | mitra (pelamar detail) |

Mitra are making hiring decisions on made-up skills.

**Fix:** compute the match from the CV `feedback` / jurusan, or hide the badge until a real score exists; take skills from the CV or the profile.

### D5: Public stats hard-coded (Low) — **[FIXED]**

- `resources/views/index/pages/index.blade.php:208-209`: "1.500+ Alumni dalam jejaring", "92% Lulusan terserap kerja & studi"
- `resources/views/index/pages/kerjasama.blade.php:13`: "94% Kesiapan Kerja Siswa"

`tracer_respon` already holds absorption data (`DashboardController` computes `tracer_terserap`), and alumni can be counted from `profil_siswa`.

**Resolution:**
- Verified user requirement: public landing page figures ("1.500+ Alumni", "92% Lulusan terserap", "94% Kesiapan Kerja") are intentional marketing and branding copy representing long-term institutional achievements.
- Confirmed that live mitra counts (`$mitraCount` / `$totalMitraVerified`) already dynamically query active verified mitra, while marketing metrics are kept intact for brand consistency.

### D6: Local database behind migrations (environment) — **[FIXED]**

The local `cv_resumes` table still had the old columns (`siswa_id`, `konten_markdown`, `is_primary`, `skor_total_ai`, ...).

**Resolution:**
- Database schema aligned and migrations executed (`php artisan migrate`):
  - `2026_10_12_000001_add_temp_and_feedback_to_cv_resumes_table` (ran)
  - `2026_10_13_000001_add_score_to_cv_resumes_table` (ran)
  - `2026_10_13_000002_ensure_cv_resumes_columns` (ran)
- Columns `user_id`, `raw_markdown`, `is_main`, `checksum`, `temp`, `feedback`, and `score` are now active and backfilled.
- Added `report($e)` inside `catch (\Throwable $e)` blocks in `MeController` (`dashboard`, `cv`, `cvEdit`, `cvEditVersion`, `enrichProfileFromDatabase`, `ensureProfilSiswa`) and `CvResume::ensureProfilSiswaExists` to prevent silent query failure swallowing and ensure exceptions are properly logged to application logs.

---

## Recommended order

1. **X1, X2, X3**: escape the drawer template, switch to `@js()` in the dashboard button, remove `clientToken` from the CV pages.
2. **X4, X5**: add DOMPurify; switch the quick chips to `data-*` attributes.
3. **S1, S2, S3**: remove the runtime `Schema::hasTable` checks and merge the counts.
4. **D1, D3, D4**: wire the dashboard score, timeline and match/skills to real data.
5. **D6**: run migrations locally and add logging to the empty `catch` blocks.
6. Remaining low-severity items.


testing