<?php

use App\Livewire\ProjectInfoPage;
use Livewire\Livewire;

it('requires authentication', function () {
    $project = makeProject(makeUser());

    $this->get(route('project.info', $project))->assertRedirect(route('login'));
});

it('shows the project info', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator, ['name' => 'Info project']);

    Livewire::actingAs($coordinator)
        ->test(ProjectInfoPage::class, ['project' => $project])
        ->assertSee('Info project')
        ->assertSee('Archive');
});

it('lets the coordinator edit name and description', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);

    Livewire::actingAs($coordinator)
        ->test(ProjectInfoPage::class, ['project' => $project])
        ->call('edit')
        ->set('name', 'Renamed project')
        ->set('description', 'A fresh description')
        ->call('save')
        ->assertSet('editing', false);

    $fresh = $project->fresh();

    expect($fresh->name)->toBe('Renamed project')
        ->and($fresh->description['raw'])->toBe('A fresh description');
});

it('validates the edit form', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);

    Livewire::actingAs($coordinator)
        ->test(ProjectInfoPage::class, ['project' => $project])
        ->call('edit')
        ->set('name', 'no')
        ->call('save')
        ->assertHasErrors(['name']);
});

it('archives and unarchives the project', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);

    Livewire::actingAs($coordinator)
        ->test(ProjectInfoPage::class, ['project' => $project])
        ->call('archive');

    expect($project->fresh()->archived)->toBeTruthy();

    Livewire::actingAs($coordinator)
        ->test(ProjectInfoPage::class, ['project' => $project])
        ->call('unarchive');

    expect($project->fresh()->archived)->toBeFalsy();
});

it('forbids members from updating the project', function () {
    $member = makeUser();
    $project = makeProject(makeUser());
    $project->users()->attach($member);

    Livewire::actingAs($member)
        ->test(ProjectInfoPage::class, ['project' => $project])
        ->call('edit')
        ->assertForbidden();
});

it('lets a member leave the project', function () {
    $member = makeUser();
    $project = makeProject(makeUser());
    $project->users()->attach($member);

    Livewire::actingAs($member)
        ->test(ProjectInfoPage::class, ['project' => $project])
        ->call('leave')
        ->assertRedirect(route('project.list'));

    expect($project->users()->whereKey($member->id)->exists())->toBeFalse();
});

it('lets only the coordinator delete the project', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);

    Livewire::actingAs($coordinator)
        ->test(ProjectInfoPage::class, ['project' => $project])
        ->call('deleteProject')
        ->assertRedirect(route('project.list'));

    expect($project->fresh())->toBeNull();
});
