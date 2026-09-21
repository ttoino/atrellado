<?php

use App\Http\Middleware\WithOtherProjects;
use App\Http\Middleware\WorkersRequestContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance;
use Illuminate\Foundation\Http\Middleware\TrimStrings;
use Illuminate\Foundation\Http\Middleware\ValidatePostSize;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Http\Middleware\TrustHosts;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Session\Middleware\AuthenticateSession;
use WorkersPhp\Laravel\Middleware\WaitForBoot;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Old Http\Kernel stack. WaitForBoot holds traffic while the
        // container boots, WorkersRequestContext derives URL config from
        // the request host; both only exist in the container (WORKERS_PHP),
        // tests and local dev skip them. The rest are framework classes.
        $middleware->use([
            ...(getenv('WORKERS_PHP') ? [WaitForBoot::class, WorkersRequestContext::class] : []),
            TrustHosts::class,
            TrustProxies::class,
            HandleCors::class,
            PreventRequestsDuringMaintenance::class,
            ValidatePostSize::class,
            TrimStrings::class,
            ConvertEmptyStringsToNull::class,
        ]);

        $middleware->trustProxies(at: '*');

        // Local container dev arrives with a localhost Host; production
        // keeps the default (the app URL host and its subdomains).
        $middleware->trustHosts(at: ['atrellado.toino.workers.dev', 'atrellado.toino.pt', '127.0.0.1', 'localhost'], subdomains: false);

        // The old web group ran AuthenticateSession; the framework default
        // does not.
        $middleware->web(append: [
            AuthenticateSession::class,
        ]);

        $middleware->alias([
            'withOtherProjects' => WithOtherProjects::class,
        ]);

        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
