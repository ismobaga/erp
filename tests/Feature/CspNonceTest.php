<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CspNonceTest extends TestCase
{
    use RefreshDatabase;

    public function test_csp_uses_nonce_and_disallows_unsafe_inline_scripts(): void
    {
        $response = $this->get('/');

        $csp = (string) $response->headers->get('Content-Security-Policy');

        $this->assertNotSame('', $csp);
        $this->assertStringContainsString("script-src 'self' 'nonce-", $csp);
        $this->assertStringNotContainsString("'unsafe-inline'", $csp);
    }

    public function test_inline_script_uses_same_nonce_as_csp_header(): void
    {
        $response = $this->get('/dms-presentation');

        $csp = (string) $response->headers->get('Content-Security-Policy');

        $this->assertSame(1, preg_match("/script-src[^;]*'nonce-([^']+)'/", $csp, $matches));

        $nonce = $matches[1];

        $response->assertSee('nonce="'.$nonce.'"', false);
    }
}
