<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('stores an uploaded profile picture as a 512px webp', function () {
    Storage::fake('local');

    $user = makeUser();

    $this->actingAs($user)->put("/api/user/{$user->id}", [
        'profile_picture' => UploadedFile::fake()->image('avatar.png', 900, 600),
    ])->assertRedirect();

    Storage::assertExists("public/users/{$user->id}.webp");

    $image = imagecreatefromstring(Storage::get("public/users/{$user->id}.webp"));
    expect(imagesx($image))->toBe(512)
        ->and(imagesy($image))->toBe(512);
});
