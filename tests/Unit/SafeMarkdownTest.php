<?php

namespace Tests\Unit;

use App\Support\SafeMarkdown;
use PHPUnit\Framework\TestCase;

class SafeMarkdownTest extends TestCase
{
    public function test_markdown_formatting_is_kept(): void
    {
        $html = SafeMarkdown::render("## Pengalaman\n\n- **Laravel** [portofolio](https://contoh.id)");

        $this->assertStringContainsString('<h2>Pengalaman</h2>', $html);
        $this->assertStringContainsString('<strong>Laravel</strong>', $html);
        $this->assertStringContainsString('href="https://contoh.id"', $html);
    }

    public function test_script_handlers_and_unsafe_links_are_removed(): void
    {
        $html = SafeMarkdown::render(
            "<script>alert(1)</script>\n\n<img src=x onerror=alert(1)>\n\n[klik](javascript:alert(1))\n\n![x](data:text/html;base64,PHNjcmlwdD4=)"
        );

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringNotContainsString('data:text/html', $html);
    }

    public function test_null_input_renders_empty_string(): void
    {
        $this->assertSame('', trim(SafeMarkdown::render(null)));
    }
}
