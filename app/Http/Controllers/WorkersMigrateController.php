<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Artisan;

// workers-php: artisan has no direct access to D1 outside the worker, so
// migrations run in-place over HTTP against the D1 binding. Shared-secret
// gated (WORKERS_MIGRATE_KEY, shipped in the bundled .env like APP_KEY).
class WorkersMigrateController extends Controller
{
    public function __invoke()
    {
        // config(), not env(): the .env file is not loaded when the
        // configuration is cached (the production bundle caches it).
        $key = config('app.workers_migrate_key');
        abort_unless($key && hash_equals($key, (string) request('key', '')), 403);

        // Symfony Console reads PHP_SELF while booting; the wasm CGI
        // bridge does not populate it.
        $_SERVER['PHP_SELF'] ??= '/index.php';

        Artisan::call('migrate', ['--force' => true]);

        return response(Artisan::output(), 200, ['Content-Type' => 'text/plain']);
    }
}
