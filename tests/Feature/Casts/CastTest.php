<?php

use App\Models\Notification;
use App\Models\Project;
use App\Models\TaskComment;
use Illuminate\Validation\ValidationException;

it('exposes every datetime format and round-trips through iso', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $group = makeGroup($project, 0);
    $task = makeTask($group, 0, ['creation_date' => '2026-01-15 10:30:00']);

    $cast = $task->fresh()->creation_date;

    expect($cast)->toBeArray()
        ->toHaveKeys(['iso', 'long_diff', 'diff', 'datetime', 'date', 'time'])
        ->and($cast['iso'])->toStartWith('2026-01-15T10:30:00')
        ->and($cast['date'])->toBe('Jan 15 2026')
        ->and($cast['time'])->toBe('10:30');

    $task->edit_date = $cast['iso'];
    $task->save();
    expect($task->fresh()->getRawOriginal('edit_date'))->toStartWith('2026-01-15 10:30:00');
});

it('keeps raw markdown next to the rendered html', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator, ['description' => '**bold** text']);

    $cast = $project->fresh()->description;

    expect($cast)->toBeArray()
        ->and($cast['raw'])->toBe('**bold** text')
        ->and($cast['formatted'])->toContain('<strong>bold</strong>');
});

it('round-trips models through notification json as model:class:id', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);

    $notification = new Notification;
    $notification->type = 'Test';
    $notification->notifiable_id = $coordinator->id;
    $notification->json = ['project' => $project, 'note' => 'plain'];
    $notification->save();

    $stored = $notification->fresh();
    expect($stored->getRawOriginal('json'))->toContain('model:'.addslashes(Project::class).':'.$project->id);

    $cast = $stored->json;
    expect($cast['project'])->toBeInstanceOf(Project::class)
        ->and($cast['project']->id)->toBe($project->id)
        ->and($cast['note'])->toBe('plain');
});

it('validates comment and thread authors are project members', function () {
    $coordinator = makeUser();
    $outsider = makeUser();
    $project = makeProject($coordinator);
    $group = makeGroup($project, 0);
    $task = makeTask($group, 0);

    $comment = new TaskComment(['content' => 'hello']);
    $comment->task_id = $task->id;
    $comment->author_id = $coordinator->id;
    $comment->save();
    expect($comment->exists)->toBeTrue();

    $bad = new TaskComment(['content' => 'intruder']);
    $bad->task_id = $task->id;
    $bad->author_id = $outsider->id;

    expect(fn () => $bad->save())->toThrow(ValidationException::class);
});
