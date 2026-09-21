<?php

use App\Livewire\AdminProjectsPage;
use Livewire\Livewire;

it('forbids the page to non-admins', function () {
    $this->actingAs(makeUser())->get(route('admin.projects'))->assertForbidden();
});

it('lists projects to admins', function () {
    makeProject(makeUser(), ['name' => 'Admin visible project']);

    Livewire::actingAs(makeUser(['is_admin' => true]))
        ->test(AdminProjectsPage::class)
        ->assertSee('Admin visible project');
});

it('deletes a project', function () {
    $admin = makeUser(['is_admin' => true]);
    $project = makeProject(makeUser());

    Livewire::actingAs($admin)
        ->test(AdminProjectsPage::class)
        ->call('deleteProject', $project->id);

    expect($project->fresh())->toBeNull();
});
