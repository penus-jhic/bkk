<?php

namespace Tests\Feature;

use App\Models\CvResume;
use App\Models\ProfilSiswa;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class XssRemediationX4X5X6Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    protected function fakeAuth(string $role = 'SISWA', string $id = 'usr-siswa-001', string $name = 'Siswa Test'): void
    {
        Http::fake([
            '*/api/user/verify' => Http::response([
                'success' => true,
                'message' => 'Token terverifikasi',
                'data' => [
                    'id' => $id,
                    'username' => 'user_' . strtolower($role),
                    'nama_lengkap' => $name,
                    'nomor_induk' => 'TEST-NISN-999',
                    'role' => $role,
                    'status_aktif' => true,
                    'email' => strtolower($role) . '@smkpenus.sch.id',
                ],
            ], 200),
        ]);
    }

    protected function fakeMitraAuth(): void
    {
        // Set mitra session to access mitra dashboard
        session([
            'mitra_id' => 1,
            'mitra_nama' => 'PT Mitra Test',
            'mitra_email' => 'mitra@test.com',
            'mitra_logged_in' => true,
        ]);
    }

    /**
     * X4: Test that CV preview and CV editor include DOMPurify script and sanitize marked.parse.
     */
    public function test_x4_cv_pages_include_dompurify_and_sanitize_marked_parse(): void
    {
        $this->fakeAuth('SISWA', 'usr-siswa-001', 'Test Siswa');

        // Test CV hub page (/bkk/me/cv)
        $resCv = $this->withHeader('Authorization', 'Bearer token_siswa')
            ->get('/bkk/me/cv');
        $resCv->assertStatus(200);
        $contentCv = $resCv->getContent();

        $this->assertStringContainsString('cdn.jsdelivr.net/npm/dompurify', $contentCv);
        $this->assertStringContainsString('DOMPurify.sanitize(parsed)', $contentCv);
        $this->assertStringNotContainsString('container.innerHTML = marked.parse(md)', $contentCv);

        // Test CV edit page (/bkk/me/cv/edit)
        $resEdit = $this->withHeader('Authorization', 'Bearer token_siswa')
            ->get('/bkk/me/cv/edit');
        $resEdit->assertStatus(200);
        $contentEdit = $resEdit->getContent();

        $this->assertStringContainsString('cdn.jsdelivr.net/npm/dompurify', $contentEdit);
        $this->assertStringContainsString('DOMPurify.sanitize(parsed)', $contentEdit);
        $this->assertStringContainsString('DOMPurify.sanitize(parsedReply)', $contentEdit);
        $this->assertStringNotContainsString('container.innerHTML = marked.parse(markdownText)', $contentEdit);
        $this->assertStringNotContainsString('${marked.parse(reply ||', $contentEdit);
    }

    /**
     * X5: Test that AI quick chips do not use vulnerable onclick handler with quote breakout
     * and instead use data-chat-prompt with delegated event listener.
     */
    public function test_x5_ai_quick_chips_use_data_prompt_and_delegated_listener(): void
    {
        $this->fakeAuth('SISWA', 'usr-siswa-001', 'Test Siswa');

        $response = $this->withHeader('Authorization', 'Bearer token_siswa')
            ->get('/bkk/me/cv/edit');
        $response->assertStatus(200);

        $content = $response->getContent();

        // 1. Must not contain the vulnerable onclick regex replace pattern
        $this->assertStringNotContainsString("onclick=\"sendChatPrompt('\${escapeHtml(chip).replace(/'/g", $content);

        // 2. Dynamic chips must use data-chat-prompt
        $this->assertStringContainsString('data-chat-prompt="${escapeHtml(chip)}"', $content);

        // 3. Static chips must use data-chat-prompt instead of inline onclick="sendChatPrompt(...)"
        $this->assertStringContainsString('data-chat-prompt="Perbaiki kalimat pengalaman agar lebih formal"', $content);
        $this->assertStringContainsString('data-chat-prompt="Bagaimana membuat ringkasan profil lebih singkat?"', $content);
        $this->assertStringContainsString('data-chat-prompt="Berikan kata kunci ATS terbaik untuk jurusan', $content);

        // 4. Delegated click listener must be present
        $this->assertStringContainsString("closest('[data-chat-prompt]')", $content);
        $this->assertStringContainsString("chipBtn.getAttribute('data-chat-prompt')", $content);
    }

    /**
     * X6: Test that showToast in me/master layout sets text via textContent.
     */
    public function test_x6_show_toast_uses_text_content_in_me_layout(): void
    {
        $this->fakeAuth('SISWA', 'usr-siswa-001', 'Test Siswa');
        $resMe = $this->withHeader('Authorization', 'Bearer token_siswa')->get('/bkk/me');
        $resMe->assertStatus(200);
        $contentMe = $resMe->getContent();

        $this->assertStringContainsString("toast.querySelector('.toast-msg').textContent = String(msg ?? '')", $contentMe);
        $this->assertStringNotContainsString('<span class="flex-1">${msg}</span>', $contentMe);
    }

    /**
     * X6: Test that showToast in admin/master layout sets text via textContent.
     */
    public function test_x6_show_toast_uses_text_content_in_admin_layout(): void
    {
        $this->fakeAuth('ADMIN', 'usr-admin-001', 'Admin BKK');
        $resAdmin = $this->withHeader('Authorization', 'Bearer token_admin')->get('/bkk/admin');
        $resAdmin->assertStatus(200);
        $contentAdmin = $resAdmin->getContent();

        $this->assertStringContainsString("toast.querySelector('.toast-msg').textContent = String(msg ?? '')", $contentAdmin);
        $this->assertStringNotContainsString('<span class="flex-1">${msg}</span>', $contentAdmin);
    }

    /**
     * X6: Test that showToast in mitra/master layout sets text via textContent.
     */
    public function test_x6_show_toast_uses_text_content_in_mitra_layout(): void
    {
        $mitra = \App\Models\Mitra::first() ?? \App\Models\Mitra::create([
            'nama_perusahaan' => 'PT Mitra Test',
            'password' => bcrypt('password'),
            'npwp' => '123456789',
            'is_verified' => true,
        ]);

        $resMitra = $this->withSession([
            'mitra_id' => $mitra->id,
            'mitra_nama' => $mitra->nama_perusahaan,
            'mitra_logged_in' => true,
        ])->get('/bkk/dashboard');

        $resMitra->assertStatus(200);
        $contentMitra = $resMitra->getContent();

        $this->assertStringContainsString("toast.querySelector('.toast-msg').textContent = String(message ?? '')", $contentMitra);
        $this->assertStringNotContainsString('<span class="flex-1">${message}</span>', $contentMitra);
    }

    /**
     * X6: Test that jurnal entry dynamic card sets activity via textContent.
     */
    public function test_x6_jurnal_entry_card_uses_text_content_for_activity(): void
    {
        $this->fakeAuth('SISWA', 'usr-siswa-001', 'Test Siswa');

        $response = $this->withHeader('Authorization', 'Bearer token_siswa')->get('/bkk/me/jurnal');
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString("card.querySelector('.jurnal-entry-activity').textContent = activity", $content);
        $this->assertStringNotContainsString('<div class="text-xs sm:text-sm text-navy mt-1.5 leading-relaxed font-medium">${activity}</div>', $content);
    }
}
