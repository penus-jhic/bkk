<?php

namespace Tests\Feature;

use App\Models\CvResume;
use App\Models\Notifikasi;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiCvContractTest extends TestCase
{
    use RefreshDatabase;

    protected function fakeAuth(string $role = 'SISWA', string $id = 'usr-siswa-001', string $name = 'Ahmad Rizky Pratama'): void
    {
        Http::fake([
            '*/api/user/verify' => Http::response([
                'success' => true,
                'message' => 'Token terverifikasi',
                'data' => [
                    'id' => $id,
                    'username' => 'siswa_user',
                    'nama_lengkap' => $name,
                    'nomor_induk' => 'SISWA-001',
                    'role' => $role,
                    'status_aktif' => true,
                    'email' => 'siswa@smkpenus.sch.id',
                ],
            ], 200),
        ]);
    }

    /**
     * 1. Endpoint POST /api/ai/cv/score mengembalikan skema kontrak lengkap,
     *    menyimpan ke cv_resumes.feedback, dan mengirim notifikasi.
     */
    public function test_score_endpoint_returns_contract_schema_updates_feedback_and_creates_notification(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->fakeAuth('SISWA', 'usr-siswa-001');
        Cache::flush();

        $mainCv = CvResume::where('user_id', 'usr-siswa-001')
            ->where('temp', false)
            ->where('is_main', true)
            ->firstOrFail();

        $mainCv->update(['feedback' => null]);
        $initialNotifCount = Notifikasi::where('recipient_id', 'usr-siswa-001')
            ->where('tipe_notifikasi', 'AI_SCORE')
            ->count();

        $res = $this->withHeader('Authorization', 'Bearer valid')
            ->postJson('/api/ai/cv/score', [
                'cv_id' => $mainCv->id,
            ]);

        $res->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'status_code',
                'message',
                'data' => [
                    'checksum',
                    'total_score',
                    'grade_label',
                    'percentile',
                    'summary_verdict',
                    'parameters' => [
                        '*' => [
                            'id',
                            'label',
                            'score',
                            'max_score',
                            'status',
                            'weight',
                            'feedback',
                        ],
                    ],
                    'detected_sections',
                    'missing_sections',
                ],
                'meta' => [
                    'request_id',
                    'timestamp',
                    'execution_time_ms',
                    'cached',
                    'cache_key',
                ],
            ])
            ->assertJson([
                'success' => true,
                'status_code' => 200,
                'data' => [
                    'checksum' => $mainCv->checksum,
                ],
            ]);

        $freshCv = $mainCv->fresh();
        $this->assertNotNull($freshCv->feedback);
        $this->assertEquals($res->json('data.total_score'), $freshCv->feedback['total_score']);
        $this->assertEquals($res->json('data.total_score'), $freshCv->score);
        $this->assertCount(4, $freshCv->feedback['parameters']);

        $this->assertGreaterThan(
            $initialNotifCount,
            Notifikasi::where('recipient_id', 'usr-siswa-001')->where('tipe_notifikasi', 'AI_SCORE')->count()
        );

        // Pastikan alias /api/ai/cv/scores juga berfungsi
        $resAlias = $this->withHeader('Authorization', 'Bearer valid')
            ->postJson('/api/ai/cv/scores', [
                'cv_id' => $mainCv->id,
            ]);
        $resAlias->assertStatus(200)->assertJson(['success' => true]);
    }

    /**
     * 2. Menyimpan CV permanen (temp = false) otomatis memicu evaluasi /score di background,
     *    mengisi cv_resumes.feedback, dan mengirim notifikasi ke siswa.
     */
    public function test_cv_save_permanent_triggers_background_score_feedback_and_notification(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->fakeAuth('SISWA', 'usr-siswa-001');
        Cache::flush();

        $newMarkdown = <<<MARKDOWN
# Ahmad Rizky Pratama
Email: siswa_rizky@smkpenus.sch.id | Telp: 0812-3456-7890

## Ringkasan Profil
Siswa aktif Rekayasa Perangkat Lunak SMK Plus Pelita Nusantara dengan spesialisasi backend Laravel dan PostgreSQL.

## Pendidikan
### SMK Plus Pelita Nusantara — Rekayasa Perangkat Lunak
- Nilai rata-rata kejuruan 90.5 / 100

## Pengalaman PKL
- Mengembangkan 15 endpoint REST API inventaris sekolah dengan waktu respon < 150ms.
- Mengelola 200+ data aset laboratorium dengan tingkat akurasi 99%.

## Keahlian Teknis
- PHP, Laravel 11, PostgreSQL, Tailwind CSS, Git
MARKDOWN;

        $res = $this->withHeader('Authorization', 'Bearer valid')
            ->postJson(route('bkk.me.cv.update'), [
                'raw_markdown' => $newMarkdown,
                'temp' => false,
            ]);

        $res->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'is_main' => true,
                    'temp' => false,
                ],
            ]);

        $savedCv = CvResume::findOrFail($res->json('data.id'));
        $this->assertFalse($savedCv->temp);
        $this->assertTrue($savedCv->is_main);
        $this->assertIsArray($savedCv->feedback);
        $this->assertArrayHasKey('total_score', $savedCv->feedback);
        $this->assertEquals($savedCv->feedback['total_score'], $savedCv->score);

        $this->assertDatabaseHas('notifikasi', [
            'recipient_id' => 'usr-siswa-001',
            'tipe_notifikasi' => 'AI_SCORE',
        ]);
    }

    /**
     * 3. Menyimpan draf sementara (temp = true) melakukan upsert 1 baris per user tanpa mengotori
     *    riwayat versi, mendukung POST /api/ai/cv/suggestions, dan dipromosikan saat Simpan CV (temp = false).
     */
    public function test_temp_draft_upsert_and_suggestions_endpoint_without_polluting_version_history(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->fakeAuth('SISWA', 'usr-siswa-001');

        $savedVersionsCountBefore = CvResume::where('user_id', 'usr-siswa-001')->where('temp', false)->count();
        $originalMain = CvResume::where('user_id', 'usr-siswa-001')->where('temp', false)->where('is_main', true)->firstOrFail();

        // Simpan draf sementara ke-1 (temp = true)
        $draftMd1 = "# Ahmad Rizky\n\n## Pengalaman\n- Melaksanakan penugasan kerja industri sesuai kompetensi keahlian dan SOP perusahaan.\n\n## Pendidikan\nSMK Plus Pelita Nusantara";
        $draftRes1 = $this->withHeader('Authorization', 'Bearer valid')
            ->postJson(route('bkk.me.cv.update'), [
                'raw_markdown' => $draftMd1,
                'temp' => true,
            ]);

        $draftRes1->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'temp' => true,
                    'is_main' => false,
                ],
            ]);
        $draftId1 = $draftRes1->json('data.id');

        // Simpan draf sementara ke-2 (temp = true) -> harus menimpa baris temp yang sama (upsert 1 baris)
        $draftMd2 = $draftMd1 . "\n\n## Keahlian\n- Laravel & PostgreSQL";
        $draftRes2 = $this->withHeader('Authorization', 'Bearer valid')
            ->postJson(route('bkk.me.cv.update'), [
                'raw_markdown' => $draftMd2,
                'temp' => true,
            ]);

        $draftRes2->assertStatus(200);
        $draftId2 = $draftRes2->json('data.id');

        $this->assertEquals($draftId1, $draftId2, 'Draf temp=true harus di-upsert pada 1 baris yang sama.');
        $this->assertEquals(1, CvResume::where('user_id', 'usr-siswa-001')->where('temp', true)->count());
        $this->assertEquals($savedVersionsCountBefore, CvResume::where('user_id', 'usr-siswa-001')->where('temp', false)->count());
        $this->assertTrue($originalMain->fresh()->is_main, 'CV Utama tidak boleh tergantikan oleh draf temp=true.');

        // Panggil POST /api/ai/cv/suggestions dengan {"cv_id": $draftId2}
        $sugRes = $this->withHeader('Authorization', 'Bearer valid')
            ->postJson('/api/ai/cv/suggestions', [
                'cv_id' => $draftId2,
            ]);

        $sugRes->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'status_code',
                'message',
                'data' => [
                    'total_suggestions',
                    'suggestions' => [
                        '*' => [
                            'id',
                            'title',
                            'desc',
                            'impact',
                            'section',
                            'category',
                            'patch' => [
                                'target_pattern',
                                'replacement',
                            ],
                        ],
                    ],
                ],
                'meta' => [
                    'request_id',
                    'timestamp',
                    'execution_time_ms',
                ],
            ]);

        $this->assertGreaterThanOrEqual(1, $sugRes->json('data.total_suggestions'));

        // Sekarang user menekan Simpan CV (temp = false) -> baris temp dipromosikan menjadi versi utama resmi
        $saveRes = $this->withHeader('Authorization', 'Bearer valid')
            ->postJson(route('bkk.me.cv.update'), [
                'raw_markdown' => $draftMd2,
                'temp' => false,
            ]);

        $saveRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $draftId2,
                    'temp' => false,
                    'is_main' => true,
                ],
            ]);

        $this->assertEquals(0, CvResume::where('user_id', 'usr-siswa-001')->where('temp', true)->count());
        $this->assertEquals($savedVersionsCountBefore + 1, CvResume::where('user_id', 'usr-siswa-001')->where('temp', false)->count());
    }

    /**
     * 4. Endpoint POST /api/ai/cv/chat mendukung mode JSON (stream: false) dan SSE Streaming (stream: true).
     */
    public function test_chat_endpoint_supports_both_json_and_sse_streaming_contract(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->fakeAuth('SISWA', 'usr-siswa-001');

        $payload = [
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Bagaimana cara membuat butir pengalaman kasir saya terdengar lebih profesional untuk lulusan Akuntansi?',
                ],
            ],
            'context' => [
                'jurusan' => 'Akuntansi dan Keuangan Lembaga',
            ],
            'stream' => false,
        ];

        // Mode Non-Stream (JSON)
        $jsonRes = $this->withHeader('Authorization', 'Bearer valid')
            ->postJson('/api/ai/cv/chat', $payload);

        $jsonRes->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'status_code',
                'message',
                'data' => [
                    'reply',
                    'suggested_actions' => [
                        '*' => [
                            'label',
                            'action_type',
                            'payload',
                        ],
                    ],
                    'quick_chips',
                ],
                'meta' => [
                    'request_id',
                    'timestamp',
                    'execution_time_ms',
                ],
            ]);

        // Mode Stream (SSE text/event-stream)
        $payload['stream'] = true;
        $streamRes = $this->withHeader('Authorization', 'Bearer valid')
            ->post('/api/ai/cv/chat', $payload);

        $streamRes->assertStatus(200);
        $this->assertStringContainsString('text/event-stream', (string) $streamRes->headers->get('Content-Type'));

        $streamedContent = $streamRes->streamedContent();
        $this->assertStringContainsString('event: delta', $streamedContent);
        $this->assertStringContainsString('event: done', $streamedContent);
    }

    /**
     * 5. Validasi payload batas karakter dan struktur pada /api/ai/cv/* mengembalikan 422 VALIDATION_ERROR.
     */
    public function test_payload_boundary_and_validation_error_envelope(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->fakeAuth('SISWA', 'usr-siswa-001');

        // Chat tanpa context.jurusan
        $resChat = $this->withHeader('Authorization', 'Bearer valid')
            ->postJson('/api/ai/cv/chat', [
                'messages' => [['role' => 'user', 'content' => 'Halo AI']],
                'context' => [],
            ]);

        $resChat->assertStatus(422)
            ->assertJson([
                'success' => false,
                'status_code' => 422,
                'error_code' => 'VALIDATION_ERROR',
            ])
            ->assertJsonStructure(['errors' => ['context.jurusan']]);

        // CV dengan konten terlalu pendek (< 20 karakter) ditolak oleh /api/ai/cv/score
        $shortCv = CvResume::create([
            'user_id' => 'usr-siswa-001',
            'raw_markdown' => '# Pendek',
            'checksum' => CvResume::generateChecksum('# Pendek'),
            'is_main' => false,
            'temp' => true,
        ]);

        $resScore = $this->withHeader('Authorization', 'Bearer valid')
            ->postJson('/api/ai/cv/score', [
                'cv_id' => $shortCv->id,
            ]);

        $resScore->assertStatus(422)
            ->assertJson([
                'success' => false,
                'status_code' => 422,
                'error_code' => 'VALIDATION_ERROR',
            ]);
    }

    /**
     * 6. Checksum caching pada /api/ai/cv/score mengembalikan cached=true pada pemanggilan kedua,
     *    dan otomatis ter-invalidate saat konten CV berubah.
     */
    public function test_checksum_caching_and_invalidation_on_score_endpoint(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->fakeAuth('SISWA', 'usr-siswa-001');
        Cache::flush();

        $mainCv = CvResume::where('user_id', 'usr-siswa-001')
            ->where('temp', false)
            ->where('is_main', true)
            ->firstOrFail();

        // Pemanggilan pertama -> cached: false
        $res1 = $this->withHeader('Authorization', 'Bearer valid')
            ->postJson('/api/ai/cv/score', ['cv_id' => $mainCv->id]);
        $res1->assertStatus(200)->assertJsonPath('meta.cached', false);

        // Pemanggilan kedua dengan checksum sama -> cached: true
        $res2 = $this->withHeader('Authorization', 'Bearer valid')
            ->postJson('/api/ai/cv/score', ['cv_id' => $mainCv->id]);
        $res2->assertStatus(200)->assertJsonPath('meta.cached', true);

        // Ubah 1 karakter pada CV -> checksum berubah & cache lama diabaikan (cached: false)
        $modifiedMarkdown = $mainCv->raw_markdown . "\n- Tambahan 1 baris sertifikasi baru.";
        $mainCv->update([
            'raw_markdown' => $modifiedMarkdown,
            'checksum' => CvResume::generateChecksum($modifiedMarkdown),
        ]);

        $res3 = $this->withHeader('Authorization', 'Bearer valid')
            ->postJson('/api/ai/cv/score', ['cv_id' => $mainCv->id]);
        $res3->assertStatus(200)
            ->assertJsonPath('meta.cached', false)
            ->assertJsonPath('data.checksum', $mainCv->checksum);
    }

    /**
     * 7. Quick Fix: Setiap request ke upstream AI Service (http://localhost:6767/api/ai/cv/~)
     *    wajib mengirimkan access_token via Authorization Bearer header DAN Cookie access_token.
     */
    public function test_upstream_ai_request_receives_access_token_in_bearer_and_cookies(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->fakeAuth('SISWA', 'usr-siswa-001');
        Cache::flush();

        $mainCv = CvResume::where('user_id', 'usr-siswa-001')
            ->where('temp', false)
            ->where('is_main', true)
            ->firstOrFail();

        $tokenSeenInScore = false;
        $cookieSeenInScore = false;
        $tokenSeenInSug = false;
        $tokenSeenInChat = false;

        $secretToken = 'jwt-secure-access-token-xyz-123';

        Http::fake([
            '*/api/user/verify' => Http::response([
                'success' => true,
                'data' => [
                    'id' => 'usr-siswa-001',
                    'username' => 'siswa_user',
                    'nama_lengkap' => 'Ahmad Rizky Pratama',
                    'nomor_induk' => 'SISWA-001',
                    'role' => 'SISWA',
                    'status_aktif' => true,
                    'email' => 'siswa@smkpenus.sch.id',
                ],
            ], 200),
            '*/api/ai/cv/score' => function ($request) use (&$tokenSeenInScore, &$cookieSeenInScore, $secretToken) {
                $authHeader = $request->header('Authorization')[0] ?? '';
                $cookieHeader = $request->header('Cookie')[0] ?? '';
                if ($authHeader === 'Bearer ' . $secretToken) {
                    $tokenSeenInScore = true;
                }
                if (str_contains($cookieHeader, 'access_token=' . $secretToken)) {
                    $cookieSeenInScore = true;
                }

                return Http::response([
                    'success' => true,
                    'status_code' => 200,
                    'message' => 'Evaluasi berhasil',
                    'data' => [
                        'total_score' => 95,
                        'grade_label' => 'Sangat Baik',
                        'percentile' => 92,
                        'summary_verdict' => 'Sangat bagus',
                        'parameters' => [],
                    ],
                ], 200);
            },
            '*/api/ai/cv/suggestions' => function ($request) use (&$tokenSeenInSug, $secretToken) {
                $authHeader = $request->header('Authorization')[0] ?? '';
                $cookieHeader = $request->header('Cookie')[0] ?? '';
                if ($authHeader === 'Bearer ' . $secretToken && str_contains($cookieHeader, 'access_token=' . $secretToken)) {
                    $tokenSeenInSug = true;
                }

                return Http::response([
                    'success' => true,
                    'status_code' => 200,
                    'message' => 'Saran AI berhasil',
                    'data' => [
                        'total_suggestions' => 0,
                        'suggestions' => [],
                    ],
                ], 200);
            },
            '*/api/ai/cv/chat' => function ($request) use (&$tokenSeenInChat, $secretToken) {
                $authHeader = $request->header('Authorization')[0] ?? '';
                $cookieHeader = $request->header('Cookie')[0] ?? '';
                if ($authHeader === 'Bearer ' . $secretToken && str_contains($cookieHeader, 'access_token=' . $secretToken)) {
                    $tokenSeenInChat = true;
                }

                return Http::response([
                    'success' => true,
                    'status_code' => 200,
                    'message' => 'Chat berhasil',
                    'data' => [
                        'reply' => 'Halo dari upstream AI',
                        'suggested_actions' => [],
                        'quick_chips' => [],
                    ],
                ], 200);
            },
        ]);

        // 1. Test POST /api/ai/cv/score forwards token to upstream in both headers & cookies
        $resScore = $this->withHeader('Authorization', 'Bearer ' . $secretToken)
            ->withCookie('access_token', $secretToken)
            ->postJson('/api/ai/cv/score', ['cv_id' => $mainCv->id]);
        $resScore->assertStatus(200);
        $this->assertTrue($tokenSeenInScore, 'Bearer token was not passed to upstream /score');
        $this->assertTrue($cookieSeenInScore, 'Cookie access_token was not passed to upstream /score');

        // Pastikan cv_resumes.score terisi dari upstream
        $this->assertEquals(95, $mainCv->fresh()->score);

        // 2. Test POST /api/ai/cv/suggestions forwards token
        $resSug = $this->withHeader('Authorization', 'Bearer ' . $secretToken)
            ->withCookie('access_token', $secretToken)
            ->postJson('/api/ai/cv/suggestions', ['cv_id' => $mainCv->id]);
        $resSug->assertStatus(200);
        $this->assertTrue($tokenSeenInSug, 'Token was not passed to upstream /suggestions');

        // 3. Test POST /api/ai/cv/chat forwards token
        $resChat = $this->withHeader('Authorization', 'Bearer ' . $secretToken)
            ->withCookie('access_token', $secretToken)
            ->postJson('/api/ai/cv/chat', [
                'messages' => [['role' => 'user', 'content' => 'Review CV']],
                'context' => ['jurusan' => 'RPL'],
            ]);
        $resChat->assertStatus(200);
        $this->assertTrue($tokenSeenInChat, 'Token was not passed to upstream /chat');
    }
}

