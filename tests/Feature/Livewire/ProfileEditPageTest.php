<?php

use App\Livewire\ProfileEditPage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('requires authentication', function () {
    $this->get(route('user.edit', makeUser()))->assertRedirect(route('login'));
});

it('forbids editing another user\'s profile', function () {
    Livewire::actingAs(makeUser())
        ->test(ProfileEditPage::class, ['user' => makeUser()])
        ->assertForbidden();
});

it('updates the user name', function () {
    $user = makeUser();

    Livewire::actingAs($user)
        ->test(ProfileEditPage::class, ['user' => $user])
        ->set('name', 'A brand new name')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('user.profile', $user));

    expect($user->fresh()->name)->toBe('A brand new name');
});

it('validates the name', function () {
    $user = makeUser();

    Livewire::actingAs($user)
        ->test(ProfileEditPage::class, ['user' => $user])
        ->set('name', 'no')
        ->call('save')
        ->assertHasErrors(['name']);
});

it('stores an uploaded profile picture as a 512px webp', function () {
    Storage::fake('local');

    $user = makeUser();

    Livewire::actingAs($user)
        ->test(ProfileEditPage::class, ['user' => $user])
        ->set('profile_picture', UploadedFile::fake()->image('avatar.png', 900, 600))
        ->call('save')
        ->assertHasNoErrors();

    Storage::assertExists("public/users/{$user->id}.webp");

    $image = imagecreatefromstring(Storage::get("public/users/{$user->id}.webp"));
    expect(imagesx($image))->toBe(512)
        ->and(imagesy($image))->toBe(512);
});

it('rejects oversized profile pictures', function () {
    Storage::fake('local');

    $user = makeUser();

    Livewire::actingAs($user)
        ->test(ProfileEditPage::class, ['user' => $user])
        ->set('profile_picture', UploadedFile::fake()->image('avatar.png', 9000, 600))
        ->call('save')
        ->assertHasErrors(['profile_picture']);
});
