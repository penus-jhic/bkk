<?php

namespace App\Support;

use HTMLPurifier;
use HTMLPurifier_Config;
use Illuminate\Support\Str;

/**
 * Render Markdown ke HTML yang aman untuk dicetak dengan {!! !!}.
 *
 * Dua lapis: CommonMark membuang HTML mentah & link tidak aman, lalu HTMLPurifier
 * membersihkan ulang hasilnya, jadi perubahan opsi markdown di masa depan tidak langsung jadi stored XSS.
 */
class SafeMarkdown
{
    private static ?HTMLPurifier $purifier = null;

    public static function render(?string $markdown): string
    {
        $html = Str::markdown($markdown ?? '', [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        return self::purifier()->purify($html);
    }

    private static function purifier(): HTMLPurifier
    {
        if (self::$purifier === null) {
            $config = HTMLPurifier_Config::createDefault();
            // Tanpa cache di disk, supaya tetap jalan di container dengan filesystem read-only
            $config->set('Cache.DefinitionImpl', null);
            $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
            $config->set('HTML.TargetBlank', false);

            self::$purifier = new HTMLPurifier($config);
        }

        return self::$purifier;
    }
}
