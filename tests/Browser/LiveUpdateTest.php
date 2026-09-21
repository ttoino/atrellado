<?php

use App\Models\Thread;

// Requires BROADCAST_CONNECTION=reverb and a running reverb server (the CI
// job and the documented docker command both start one); the layout then
// injects window.reverbConfig and echo subscribes on page load.
it('appends new threads live', function () {
    $user = makeUser();
    $project = makeProject($user, ['name' => 'Live Project']);
    Thread::factory()->create([
        'project_id' => $project->id,
        'author_id' => $user->id,
        'title' => 'Existing Thread',
    ]);

    $page = visit('/login')
        ->fill('email', $user->email)
        ->fill('password', 'password123')
        ->submit()
        ->navigate("/project/{$project->id}/forum")
        ->waitForText('Existing Thread')
        // The broadcast fires once; gate it on the channel being subscribed.
        ->assertScript('Object.values(window.Echo?.connector?.channels ?? {}).some(c => c.subscription?.subscribed === true)');

    // The model's created event broadcasts ThreadCreated; the open page
    // should append the card without a reload.
    Thread::factory()->create([
        'project_id' => $project->id,
        'author_id' => $user->id,
        'title' => 'Live Thread',
    ]);

    $page->waitForText('Live Thread')->assertSee('Live Thread');
});
