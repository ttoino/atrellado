<?php

it('lets users view and update their own profile but not others', function () {
    $user = makeUser();
    $other = makeUser();

    expect($user->can('view', $user))->toBeTrue()
        ->and($user->can('update', $user))->toBeTrue()
        ->and($user->can('update', $other))->toBeFalse();
});

it('lets admins update, block and unblock users, but not regular users', function () {
    $admin = makeUser(['is_admin' => true]);
    $user = makeUser();
    $target = makeUser();

    expect($admin->can('update', $target))->toBeTrue()
        ->and($admin->can('block', $target))->toBeTrue()
        ->and($user->can('update', $target))->toBeFalse()
        ->and($user->can('block', $target))->toBeFalse();
});

it('does not block admins or already blocked users, nor unblock active ones', function () {
    $admin = makeUser(['is_admin' => true]);
    $otherAdmin = makeUser(['is_admin' => true]);
    $blocked = makeUser(['blocked' => true]);
    $active = makeUser();

    expect($admin->can('block', $otherAdmin))->toBeFalse()
        ->and($admin->can('block', $blocked))->toBeFalse()
        ->and($admin->can('unblock', $active))->toBeFalse()
        ->and($admin->can('unblock', $blocked))->toBeTrue();
});
