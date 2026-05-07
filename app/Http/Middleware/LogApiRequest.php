<?php

namespace App\Http\Middleware;

use App\Services\AuditTrailService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogApiRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $token = $request->attributes->get('apiToken');
        if ($token === null) {
            return $response;
        }

        app(AuditTrailService::class)->log('api_request', null, [
            'method' => $request->method(),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'scope' => (string) $request->attributes->get('apiScope'),
            'api_token_id' => $token->id,
        ]);

        return $response;
    }
}
