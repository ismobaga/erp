<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetSecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // ── Content Security Policy ────────────────────────────────────
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; script-src 'self' 'unsafe-inline' cdn.jsdelivr.net; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'"
        );

        // ── Prevent Clickjacking ───────────────────────────────────────
        $response->headers->set('X-Frame-Options', 'DENY');

        // ── Prevent MIME Type Sniffing ─────────────────────────────────
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // ── Enable XSS Protection ──────────────────────────────────────
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // ── Referrer Policy ────────────────────────────────────────────
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // ── Permissions Policy (formerly Feature Policy) ───────────────
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), magnetometer=(), gyroscope=(), usb=()'
        );

        // ── Strict Transport Security (HTTPS only) ─────────────────────
        if ($request->isSecure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        return $response;
    }
}
