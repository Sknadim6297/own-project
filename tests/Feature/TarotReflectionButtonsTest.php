<?php

namespace Tests\Feature;

use Tests\TestCase;

class TarotReflectionButtonsTest extends TestCase
{
    public function test_tarot_script_prevents_cancelled_submits_and_double_binding(): void
    {
        $path = public_path('frontend/assets/js/yuktione-tarot.js');
        $this->assertFileExists($path);
        $js = file_get_contents($path);

        $this->assertStringContainsString('markSubmitterBusy', $js);
        $this->assertStringContainsString('data-tarot-submitted', $js);
        $this->assertStringContainsString('pageshow', $js);
        $this->assertStringContainsString('data-tarot-select-form', $js);
        $this->assertStringContainsString('Never set disabled on the active submitter', $js);
        $this->assertStringContainsString("form.closest('.yk-tarot__fan')", $js);

        // Must not disable the active submitter during submit (cancels Chromium POSTs).
        $this->assertStringNotContainsString('btn.disabled = true', $js);

        $draw = file_get_contents(resource_path('views/frontend/sections/tarot/draw.blade.php'));
        $this->assertStringContainsString('data-tarot-draw-once', $draw);
        $this->assertStringNotContainsString('data-tarot-once', $draw);
    }
}
