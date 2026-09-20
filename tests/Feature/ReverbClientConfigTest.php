<?php

it('injects the reverb client config from the runtime config', function () {
    config()->set('broadcasting.default', 'reverb');
    config()->set('broadcasting.connections.reverb.key', 'test-app-key');
    config()->set('broadcasting.connections.reverb.secret', 'test-app-secret');
    config()->set('broadcasting.connections.reverb.options.host', 'ws.example.com');
    config()->set('broadcasting.connections.reverb.options.port', 8080);
    config()->set('broadcasting.connections.reverb.options.scheme', 'https');

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('window.reverbConfig', false)
        ->assertSee('test-app-key', false)
        ->assertSee('ws.example.com', false)
        ->assertDontSee('test-app-secret', false);
});

it('injects nothing when reverb is not the default broadcaster', function () {
    config()->set('broadcasting.default', 'log');
    config()->set('broadcasting.connections.reverb.key', 'test-app-key');

    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee('window.reverbConfig', false);
});
