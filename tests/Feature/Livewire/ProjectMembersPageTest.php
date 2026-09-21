<?php

use App\Livewire\ProjectMembersPage;
use App\Notifications\ProjectInvite;
use App\Notifications\ProjectRemoved;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

it('requires authentication', function () {
    $project = makeProject(makeUser());

    $this->get(route('project.members', $project))->assertRedirect(route('login'));
});

it('lists the project members', function () {
    $coordinator = makeUser(['name' => 'The Coordinator']);
    $project = makeProject($coordinator);
    $member = makeUser(['name' => 'The Member']);
    $project->users()->attach($member);

    Livewire::actingAs($coordinator)
        ->test(ProjectMembersPage::class, ['project' => $project])
        ->assertSee('The Coordinator')
        ->assertSee('The Member');
});

it('lets the coordinator remove a member', function () {
    Notification::fake();

    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $member = makeUser();
    $project->users()->attach($member);

    Livewire::actingAs($coordinator)
        ->test(ProjectMembersPage::class, ['project' => $project])
        ->call('removeUser', $member->id);

    expect($project->users()->whereKey($member->id)->exists())->toBeFalse();

    Notification::assertSentTo($member, ProjectRemoved::class);
});

it('forbids members from removing members', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $member = makeUser();
    $other = makeUser();
    $project->users()->attach([$member->id, $other->id]);

    Livewire::actingAs($member)
        ->test(ProjectMembersPage::class, ['project' => $project])
        ->call('removeUser', $other->id)
        ->assertForbidden();
});

it('lets the coordinator hand over coordination', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $member = makeUser();
    $project->users()->attach($member);

    Livewire::actingAs($coordinator)
        ->test(ProjectMembersPage::class, ['project' => $project])
        ->call('setCoordinator', $member->id);

    expect($project->fresh()->coordinator_id)->toBe($member->id);
});

it('invites a user by email', function () {
    Notification::fake();

    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $invitee = makeUser();

    Livewire::actingAs($coordinator)
        ->test(ProjectMembersPage::class, ['project' => $project])
        ->set('email', $invitee->email)
        ->call('invite')
        ->assertHasNoErrors()
        ->assertSet('email', '');

    Notification::assertSentTo($invitee, ProjectInvite::class);
});

it('rejects an invite for an unknown email', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);

    Livewire::actingAs($coordinator)
        ->test(ProjectMembersPage::class, ['project' => $project])
        ->set('email', 'nobody@example.com')
        ->call('invite')
        ->assertHasErrors(['email']);
});

it('rejects an invite for an existing member', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $member = makeUser();
    $project->users()->attach($member);

    Livewire::actingAs($coordinator)
        ->test(ProjectMembersPage::class, ['project' => $project])
        ->set('email', $member->email)
        ->call('invite')
        ->assertHasErrors(['email']);
});

it('forbids non-coordinators from inviting', function () {
    $coordinator = makeUser();
    $project = makeProject($coordinator);
    $member = makeUser();
    $project->users()->attach($member);

    Livewire::actingAs($member)
        ->test(ProjectMembersPage::class, ['project' => $project])
        ->set('email', makeUser()->email)
        ->call('invite')
        ->assertHasErrors(['email']);
});
