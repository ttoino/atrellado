<?php

use App\Models\TaskGroup;

it('compacts sibling groups when a group moves within its project', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $groups = collect(range(0, 3))->map(fn ($p) => makeGroup($project, $p));

    $moved = $groups[0]->fresh();
    $moved->position = 2;
    $moved->save();

    expect(groupPositions($project))->toBe([
        $groups[1]->id => 0,
        $groups[2]->id => 1,
        $groups[0]->id => 2,
        $groups[3]->id => 3,
    ]);
});

it('closes the gap when a group is deleted', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $groups = collect(range(0, 2))->map(fn ($p) => makeGroup($project, $p));

    $groups[1]->fresh()->delete();

    expect(groupPositions($project))->toBe([
        $groups[0]->id => 0,
        $groups[2]->id => 1,
    ]);
    expect(TaskGroup::find($groups[1]->id))->toBeNull();
});
