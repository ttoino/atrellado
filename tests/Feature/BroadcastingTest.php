<?php

use App\Livewire\ProjectForumPage;
use App\Livewire\ThreadOverlay;
use App\Models\Thread;
use Illuminate\Broadcasting\Broadcasters\Broadcaster;
use Illuminate\Support\Facades\Broadcast;
use Livewire\Livewire;

// Laravel 13 removed Broadcast::fake(); this driver records what the sync
// queue hands to the broadcaster so tests can assert channels and payloads.
class RecordingBroadcaster extends Broadcaster
{
    /** @var list<array{channels: list<string>, event: string, payload: array<string, mixed>}> */
    public array $broadcasts = [];

    public function auth($request) {}

    public function validAuthenticationResponse($request, $result)
    {
        return $result;
    }

    public function broadcast(array $channels, $event, array $payload = [])
    {
        $this->broadcasts[] = [
            'channels' => array_map(fn ($channel) => $channel->name, $channels),
            'event' => $event,
            'payload' => $payload,
        ];
    }
}

function fakeBroadcaster(): RecordingBroadcaster
{
    $broadcaster = new RecordingBroadcaster;

    Broadcast::extend('recording', fn () => $broadcaster);
    config([
        'broadcasting.connections.recording' => ['driver' => 'recording'],
        'broadcasting.default' => 'recording',
    ]);

    return $broadcaster;
}

function useReverbDriver(): void
{
    config([
        'broadcasting.connections.reverb.app_id' => 'app-id',
        'broadcasting.connections.reverb.key' => 'app-key',
        'broadcasting.connections.reverb.secret' => 'app-secret',
        'broadcasting.default' => 'reverb',
    ]);

    // channels.php registered its channels on the boot-time default driver
    // (log); re-register them on the driver under test.
    require base_path('routes/channels.php');
}

it('broadcasts thread creation on the project channel', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);

    $broadcaster = fakeBroadcaster();

    Livewire::actingAs($coordinator)
        ->test(ProjectForumPage::class, ['project' => $project])
        ->set('title', 'Broadcast test thread')
        ->set('content', 'Thread content')
        ->call('createThread');

    $thread = $project->threads()->sole();

    expect($broadcaster->broadcasts)->toHaveCount(1)
        ->and($broadcaster->broadcasts[0]['channels'])->toBe(['private-project.'.$project->id])
        ->and($broadcaster->broadcasts[0]['event'])->toBe('thread.created')
        ->and($broadcaster->broadcasts[0]['payload'])->toMatchArray([
            'id' => $thread->id,
            'project_id' => $project->id,
        ]);
});

it('broadcasts thread comment creation on the project channel', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $thread = Thread::factory()->create([
        'author_id' => $coordinator->id,
        'project_id' => $project->id,
    ]);

    $broadcaster = fakeBroadcaster();

    Livewire::actingAs($coordinator)
        ->test(ThreadOverlay::class, ['thread' => $thread])
        ->set('newComment', 'A live comment')
        ->call('addComment');

    expect($broadcaster->broadcasts)->toHaveCount(1)
        ->and($broadcaster->broadcasts[0]['channels'])->toBe(['private-project.'.$project->id])
        ->and($broadcaster->broadcasts[0]['event'])->toBe('thread-comment.created')
        ->and($broadcaster->broadcasts[0]['payload'])->toMatchArray([
            'project_id' => $project->id,
            'thread_id' => $thread->id,
        ]);
});

it('broadcasts task comment creation on the project channel', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $task = makeTask(makeGroup($project, 0), 0);

    $broadcaster = fakeBroadcaster();

    $response = $this->actingAs($coordinator)->postJson('/api/task-comment', [
        'content' => 'A live task comment',
        'task_id' => $task->id,
    ]);

    $response->assertCreated();

    expect($broadcaster->broadcasts)->toHaveCount(1)
        ->and($broadcaster->broadcasts[0]['channels'])->toBe(['private-project.'.$project->id])
        ->and($broadcaster->broadcasts[0]['event'])->toBe('task-comment.created')
        ->and($broadcaster->broadcasts[0]['payload'])->toMatchArray([
            'id' => $response->json('id'),
            'project_id' => $project->id,
            'task_id' => $task->id,
        ]);
});

it('authorizes project members on the project channel', function () {
    useReverbDriver();

    $member = makeUser();
    $project = makeProject($member);

    $this->actingAs($member)->postJson('/broadcasting/auth', [
        'channel_name' => 'private-project.'.$project->id,
        'socket_id' => '123.456',
    ])->assertOk();
});

it('rejects non-members from the project channel', function () {
    useReverbDriver();

    $outsider = makeUser();
    $project = makeProject(makeUser());

    $this->actingAs($outsider)->postJson('/broadcasting/auth', [
        'channel_name' => 'private-project.'.$project->id,
        'socket_id' => '123.456',
    ])->assertForbidden();
});
