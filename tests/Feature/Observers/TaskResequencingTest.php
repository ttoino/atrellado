<?php

use App\Models\Task;

it('compacts siblings when a task moves down within its group', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $group = makeGroup($project, 0);
    $tasks = collect(range(0, 3))->map(fn ($p) => makeTask($group, $p));

    $moved = $tasks[0]->fresh();
    $moved->position = 2;
    $moved->save();

    expect(taskPositions($group))->toBe([
        $tasks[1]->id => 0,
        $tasks[2]->id => 1,
        $tasks[0]->id => 2,
        $tasks[3]->id => 3,
    ]);
});

it('compacts siblings when a task moves up within its group', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $group = makeGroup($project, 0);
    $tasks = collect(range(0, 3))->map(fn ($p) => makeTask($group, $p));

    $moved = $tasks[3]->fresh();
    $moved->position = 0;
    $moved->save();

    expect(taskPositions($group))->toBe([
        $tasks[3]->id => 0,
        $tasks[0]->id => 1,
        $tasks[1]->id => 2,
        $tasks[2]->id => 3,
    ]);
});

it('rebalances both groups on a cross-group move', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $from = makeGroup($project, 0);
    $to = makeGroup($project, 1);
    $fromTasks = collect(range(0, 2))->map(fn ($p) => makeTask($from, $p));
    $toTasks = collect(range(0, 1))->map(fn ($p) => makeTask($to, $p));

    $moved = $fromTasks[1]->fresh();
    $moved->task_group_id = $to->id;
    $moved->position = 0;
    $moved->save();

    expect(taskPositions($from))->toBe([
        $fromTasks[0]->id => 0,
        $fromTasks[2]->id => 1,
    ]);
    expect(taskPositions($to))->toBe([
        $fromTasks[1]->id => 0,
        $toTasks[0]->id => 1,
        $toTasks[1]->id => 2,
    ]);
});

it('closes the gap when a task is deleted', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $group = makeGroup($project, 0);
    $tasks = collect(range(0, 3))->map(fn ($p) => makeTask($group, $p));

    $tasks[1]->fresh()->delete();

    expect(taskPositions($group))->toBe([
        $tasks[0]->id => 0,
        $tasks[2]->id => 1,
        $tasks[3]->id => 2,
    ]);
    expect(Task::find($tasks[1]->id))->toBeNull();
});
