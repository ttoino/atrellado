<?php

use App\Models\Project;

it('lets members and admins view a project but not outsiders', function () {
    $coordinator = makeUser();
    $member = makeUser();
    $outsider = makeUser();
    $admin = makeUser(['is_admin' => true]);
    $project = makeProject($coordinator);
    $project->users()->attach($member->id);

    expect($coordinator->can('view', $project))->toBeTrue()
        ->and($member->can('view', $project))->toBeTrue()
        ->and($admin->can('view', $project))->toBeTrue()
        ->and($outsider->can('view', $project))->toBeFalse();
});

it('denies everything to blocked users', function () {
    $coordinator = makeUser(['blocked' => true]);
    $project = makeProject($coordinator);

    expect($coordinator->can('view', $project))->toBeFalse()
        ->and($coordinator->can('viewAny', Project::class))->toBeFalse()
        ->and($coordinator->can('create', Project::class))->toBeFalse()
        ->and($coordinator->can('report', $project))->toBeFalse();
});

it('lets only non-admin unblocked users create projects', function () {
    expect(makeUser()->can('create', Project::class))->toBeTrue()
        ->and(makeUser(['is_admin' => true])->can('create', Project::class))->toBeFalse();
});

it('lets only the coordinator update a project, never admins, never when archived', function () {
    $coordinator = makeUser();
    $member = makeUser();
    $admin = makeUser(['is_admin' => true]);
    $project = makeProject($coordinator);
    $project->users()->attach($member->id);

    expect($coordinator->can('update', $project))->toBeTrue()
        ->and($member->can('update', $project))->toBeFalse()
        ->and($admin->can('update', $project))->toBeFalse();

    $project->archived = true;
    expect($coordinator->can('update', $project))->toBeFalse();
});

it('lets the coordinator and admins delete a project but not members', function () {
    $coordinator = makeUser();
    $member = makeUser();
    $admin = makeUser(['is_admin' => true]);
    $project = makeProject($coordinator);
    $project->users()->attach($member->id);

    expect($coordinator->can('delete', $project))->toBeTrue()
        ->and($admin->can('delete', $project))->toBeTrue()
        ->and($member->can('delete', $project))->toBeFalse();
});

it('lets only the coordinator hand over coordination, to someone else, while active', function () {
    $coordinator = makeUser();
    $member = makeUser();
    $outsider = makeUser();
    $project = makeProject($coordinator);
    $project->users()->attach($member->id);

    expect($coordinator->can('setCoordinator', [$project, $member]))->toBeTrue()
        ->and($member->can('setCoordinator', [$project, $member]))->toBeFalse()
        ->and($coordinator->can('setCoordinator', [$project, $coordinator]))->toBeFalse();

    $project->archived = true;
    expect($coordinator->can('setCoordinator', [$project, $member]))->toBeFalse();
});

it('lets non-admins report projects', function () {
    $project = makeProject(makeUser());

    expect(makeUser()->can('report', $project))->toBeTrue()
        ->and(makeUser(['is_admin' => true])->can('report', $project))->toBeFalse();
});
