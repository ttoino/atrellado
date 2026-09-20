<?php

use App\Livewire\ProjectTagsPage;
use App\Models\Tag;
use Livewire\Livewire;

it('requires authentication', function () {
    $project = makeProject(makeUser());

    $this->get(route('project.tags', $project))->assertRedirect(route('login'));
});

it('lists the project tags', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $project->tags()->save(new Tag(['title' => 'First tag', 'color' => 0xFF0000]));

    Livewire::actingAs($coordinator)
        ->test(ProjectTagsPage::class, ['project' => $project])
        ->assertSee('First tag');
});

it('creates a tag', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);

    Livewire::actingAs($coordinator)
        ->test(ProjectTagsPage::class, ['project' => $project])
        ->set('title', 'Brand new tag')
        ->set('color', '#00ff00')
        ->call('createTag')
        ->assertHasNoErrors()
        ->assertSee('Brand new tag');

    expect($project->tags()->where('title', 'Brand new tag')->first()->color)->toBe('#00ff00');
});

it('validates the create form', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);

    Livewire::actingAs($coordinator)
        ->test(ProjectTagsPage::class, ['project' => $project])
        ->set('title', 'no')
        ->call('createTag')
        ->assertHasErrors(['title']);
});

it('edits a tag', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $tag = $project->tags()->save(new Tag(['title' => 'Before edit', 'color' => 0xFF0000]));

    Livewire::actingAs($coordinator)
        ->test(ProjectTagsPage::class, ['project' => $project])
        ->call('editTag', $tag->id)
        ->set('editTitle', 'After edit')
        ->set('editColor', '#0000ff')
        ->call('saveTag')
        ->assertHasNoErrors()
        ->assertSee('After edit');

    expect($tag->fresh()->title)->toBe('After edit');
});

it('deletes a tag', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $tag = $project->tags()->save(new Tag(['title' => 'Doomed tag', 'color' => 0xFF0000]));

    Livewire::actingAs($coordinator)
        ->test(ProjectTagsPage::class, ['project' => $project])
        ->call('deleteTag', $tag->id)
        ->assertDontSee('Doomed tag');

    expect($tag->fresh())->toBeNull();
});

it('forbids non-members from creating tags', function () {
    $project = makeProject(makeUser());

    Livewire::actingAs(makeUser())
        ->test(ProjectTagsPage::class, ['project' => $project])
        ->assertForbidden();
});
