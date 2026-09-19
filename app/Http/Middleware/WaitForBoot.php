<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WaitForBoot
{
    /**
     * The entrypoint touches the ready flag once it has migrated and primed
     * caches; until then short-circuit with a retryable response so early
     * traffic never hits a half-booted app. The worker holds requests through
     * this window; browsers and API clients get a self-retrying answer.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! file_exists('/tmp/atrellado-ready')) {
            $body = $request->expectsJson()
                ? response()->json(['message' => 'Atrellado is starting, retry shortly.', 'retry_after' => 3], 503)
                : response()->view('errors.booting', [], 503);

            return $body->header('Retry-After', 3);
        }

        return $next($request);
    }
}
