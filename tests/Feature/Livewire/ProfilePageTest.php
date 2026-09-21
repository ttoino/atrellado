<?php

use App\Livewire\ProfilePage;
use Livewire\Livewire;

it('requires authentication', function () {
    $this->get(route('user.profile', makeUser()))->assertRedirect(route('login'));
});

it('shows the user profile', function () {
    $user = makeUser(['name' => 'Profile Owner']);

    Livewire::actingAs($user)
        ->test(ProfilePage::class, ['user' => $user])
        ->assertSee('Profile Owner')
        ->assertSee('Delete account');
});

it('lets a user delete their own account', function () {
    $user = makeUser();

    Livewire::actingAs($user)
        ->test(ProfilePage::class, ['user' => $user])
        ->call('deleteUser')
        ->assertRedirect(route('home'));

    expect($user->fresh())->toBeNull();
});

it('forbids strangers from viewing the profile', function () {
    Livewire::actingAs(makeUser())
        ->test(ProfilePage::class, ['user' => makeUser()])
        ->assertForbidden();
});

it('lets an admin delete another user\'s account', function () {
    $other = makeUser();

    Livewire::actingAs(makeUser(['is_admin' => true]))
        ->test(ProfilePage::class, ['user' => $other])
        ->call('deleteUser')
        ->assertRedirect(route('home'));

    expect($other->fresh())->toBeNull();
});
