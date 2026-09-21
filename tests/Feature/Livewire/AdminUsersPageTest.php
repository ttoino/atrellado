<?php

use App\Livewire\AdminUsersPage;
use Livewire\Livewire;

it('forbids the page to non-admins', function () {
    $this->actingAs(makeUser())->get(route('admin.users'))->assertForbidden();
});

it('lists users to admins', function () {
    makeUser(['name' => 'Somebody Else']);

    Livewire::actingAs(makeUser(['is_admin' => true]))
        ->test(AdminUsersPage::class)
        ->assertSee('Somebody Else');
});

it('blocks and unblocks a user', function () {
    $admin = makeUser(['is_admin' => true]);
    $user = makeUser();

    Livewire::actingAs($admin)
        ->test(AdminUsersPage::class)
        ->call('blockUser', $user->id);

    expect($user->fresh()->blocked)->toBeTruthy();

    Livewire::actingAs($admin)
        ->test(AdminUsersPage::class)
        ->call('unblockUser', $user->id);

    expect($user->fresh()->blocked)->toBeFalsy();
});

it('deletes a user', function () {
    $admin = makeUser(['is_admin' => true]);
    $user = makeUser();

    Livewire::actingAs($admin)
        ->test(AdminUsersPage::class)
        ->call('deleteUser', $user->id);

    expect($user->fresh())->toBeNull();
});

it('forbids blocking another admin', function () {
    $admin = makeUser(['is_admin' => true]);
    $other = makeUser(['is_admin' => true]);

    Livewire::actingAs($admin)
        ->test(AdminUsersPage::class)
        ->call('blockUser', $other->id)
        ->assertForbidden();
});
