<?php

it('creates a user as admin', function () {
    $admin = makeUser(['is_admin' => true]);

    visit('/login')
        ->fill('email', $admin->email)
        ->fill('password', 'password123')
        ->submit()
        ->navigate('/admin/create/user')
        ->fill('name', 'Admin Made')
        ->fill('email', 'adminmade@example.com')
        ->fill('password', 'password123')
        ->fill('password_confirmation', 'password123')
        ->submit()
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas('user_profile', ['email' => 'adminmade@example.com']);
});
