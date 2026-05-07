<?php

namespace App\Http\Middleware;

use App\Models\ApiToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = (string) $request->bearerToken();

        if ($plainToken === '') {
            return response()->json(['message' => 'API token is required.'], 401);
        }

        $token = ApiToken::query()
            ->with(['company', 'user'])
            ->where('token_hash', hash('sha256', $plainToken))
            ->whereNull('revoked_at')
            ->first();

        if ($token === null || $token->expires_at?->isPast()) {
            return response()->json(['message' => 'Invalid API token.'], 401);
        }

        $token->forceFill(['last_used_at' => now()])->saveQuietly();

        Auth::setUser($token->user);
        app()->instance('currentCompany', $token->company);

        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('apiToken', $token);
        $request->attributes->set('apiScope', (string) $token->scope);

        return $next($request);
    }
}
