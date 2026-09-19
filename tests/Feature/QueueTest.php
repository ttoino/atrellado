<?php

use App\Notifications\TaskAssigned;

it('dispatches notifications through the database queue', function () {
    config()->set('queue.default', 'database');

    // Telescope pushes a housekeeping job on every queue operation.
    config()->set('telescope.enabled', false);

    $user = makeUser();
    $project = makeProject($user);
    $group = makeGroup($project, 1);
    $assignee = makeUser();
    $project->users()->attach($assignee);

    $task = makeTask($group, 1, ['creator_id' => $user->id]);
    $task->assignees()->attach($assignee);

    $assignee->notify(new TaskAssigned($task, $user));

    expect(DB::table('jobs')->count())->toBe(1);

    // The artisan subprocess re-reads env config; pin the connection.
    $this->artisan('queue:work', ['connection' => 'database', '--once' => true])->assertSuccessful();

    // Housekeeping jobs (Telescope) may remain; the notification's must not.
    expect(DB::table('jobs')->where('payload', 'like', '%TaskAssigned%')->count())->toBe(0)
        ->and($assignee->notifications()->where('type', TaskAssigned::class)->count())->toBe(1);
});
