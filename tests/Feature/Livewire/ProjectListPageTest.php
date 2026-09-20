<?php

use App\Livewire\ProjectListPage;
use Livewire\Livewire;

it('requires authentication', function () {
    $this->get(route('project.list'))->assertRedirect(route('login'));
});

it('lists the user\'s projects', function () {
    $user = makeUser();
    $mine = makeProject($user, ['name' => 'My project']);
    makeProject(makeUser(), ['name' => 'Not mine']);

    Livewire::actingAs($user)
        ->test(ProjectListPage::class)
        ->assertSee('My project')
        ->assertDontSee('Not mine');
});

it('shows an empty state without projects', function () {
    Livewire::actingAs(makeUser())
        ->test(ProjectListPage::class)
        ->assertSee("You don't have any projects yet!", false);
});

it('toggles the favorite flag', function () {
    $user = makeUser();
    $project = makeProject($user);

    expect($project->users()->first()->pivot->is_favorite)->toBeFalsy();

    Livewire::actingAs($user)
        ->test(ProjectListPage::class)
        ->call('toggleFavorite', $project->id);

    expect($project->users()->first()->pivot->is_favorite)->toBeTruthy();

    Livewire::actingAs($user)
        ->test(ProjectListPage::class)
        ->call('toggleFavorite', $project->id);

    expect($project->users()->first()->pivot->is_favorite)->toBeFalsy();
});

it('lets the coordinator delete a project from the list', function () {
    $user = makeUser();
    $project = makeProject($user);

    Livewire::actingAs($user)
        ->test(ProjectListPage::class)
        ->call('deleteProject', $project->id);

    expect($project->fresh())->toBeNull();
});

it('forbids members from deleting a project', function () {
    $member = makeUser();
    $project = makeProject(makeUser());
    $project->users()->attach($member);

    Livewire::actingAs($member)
        ->test(ProjectListPage::class)
        ->call('deleteProject', $project->id)
        ->assertForbidden();
});
