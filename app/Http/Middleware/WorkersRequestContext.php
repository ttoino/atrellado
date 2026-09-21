<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WorkersRequestContext
{
    /**
     * Derive URL config from the request host. Registered only in the
     * container (WORKERS_PHP), so previews on their branch subdomain and
     * production on its custom domains both end up with correct URLs. The
     * edge always terminates TLS, so the scheme is fixed to https.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        config([
            'app.url' => "https://{$host}",
            'broadcasting.connections.reverb.options.host' => $host,
            'broadcasting.connections.reverb.options.port' => 443,
            'broadcasting.connections.reverb.options.scheme' => 'https',
        ]);

        return $next($request);
    }
}
