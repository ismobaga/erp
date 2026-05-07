<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiScope
{
    public function handle(Request $request, Closure $next, string $requiredScope): Response
    {
        $scope = (string) $request->attributes->get('apiScope', '');
        $requiredScope = strtolower($requiredScope);

        $allowed = match ($requiredScope) {
            'public' => in_array($scope, ['public', 'private'], true),
            'private' => $scope === 'private',
            default => false,
        };

        if (! $allowed) {
            return response()->json(['message' => 'This token scope is not allowed for this endpoint.'], 403);
        }

        return $next($request);
    }
}
