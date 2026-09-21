<?php

use App\Http\Middleware\WorkersRequestContext;
use Illuminate\Support\Facades\Route;

it('derives url and reverb config from the request host', function () {
    Route::middleware(WorkersRequestContext::class)->get(
        '/_test/request-context',
        fn () => response()->json([
            'app_url' => config('app.url'),
            'reverb_host' => config('broadcasting.connections.reverb.options.host'),
            'reverb_port' => config('broadcasting.connections.reverb.options.port'),
            'reverb_scheme' => config('broadcasting.connections.reverb.options.scheme'),
        ]),
    );

    $this->get('https://feature-x--atrellado.workers.dev/_test/request-context')
        ->assertOk()
        ->assertExactJson([
            'app_url' => 'https://feature-x--atrellado.workers.dev',
            'reverb_host' => 'feature-x--atrellado.workers.dev',
            'reverb_port' => 443,
            'reverb_scheme' => 'https',
        ]);
});
