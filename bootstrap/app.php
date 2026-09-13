<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Old Http\Kernel stack, framework classes only. TrustHosts keeps
        // its default (all subdomains of the app URL).
        $middleware->use([
            \Illuminate\Http\Middleware\TrustHosts::class,
            \Illuminate\Http\Middleware\TrustProxies::class,
            \Illuminate\Http\Middleware\HandleCors::class,
            \Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance::class,
            \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
            \Illuminate\Foundation\Http\Middleware\TrimStrings::class,
            \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        ]);

        $middleware->trustProxies(at: '*');

        // workers-php: wrangler dev serves localhost; the default trusts
        // only subdomains of the app URL.
        $middleware->trustHosts(fn () => [
            '^(.+\.)?'.preg_quote(parse_url(config('app.url'), PHP_URL_HOST)).'$',
            '^localhost(:\d+)?$',
        ]);

        // The old web group ran AuthenticateSession; the framework default
        // does not.
        $middleware->web(append: [
            AuthenticateSession::class,
        ]);

        $middleware->api(prepend: [
            'throttle:api',
        ]);

        $middleware->alias([
            'isAdmin' => \App\Http\Middleware\IsAdmin::class,
            'withOtherProjects' => \App\Http\Middleware\WithOtherProjects::class,
        ]);

        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
