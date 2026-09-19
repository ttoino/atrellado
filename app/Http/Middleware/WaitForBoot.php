<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

// Holds traffic while the container boots: the runtime health check stops
// containers whose /ping does not pass quickly, so /ping answers as soon
// as nginx+fpm are up and this middleware 503s the app until the
// entrypoint finishes migrating and flags readiness.
class WaitForBoot
{
    public function handle(Request $request, Closure $next)
    {
        if (!file_exists('/tmp/atrellado-ready')) {
            return response('booting', 503)->header('Retry-After', '5');
        }

        return $next($request);
    }
}
