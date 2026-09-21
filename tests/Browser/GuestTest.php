<?php

it('renders the guest pages without errors', function (string $path, string $marker) {
    visit($path)
        ->assertSee($marker)
        ->assertNoJavaScriptErrors()
        ->assertNoConsoleLogs();
})->with([
    'home' => ['/', 'Sign in or create an account'],
    'login' => ['/login', 'Login'],
    'register' => ['/register', 'Register'],
    'password recovery' => ['/recover-password', 'Email'],
]);

it('may register and is gated behind email verification', function () {
    visit('/register')
        ->fill('name', 'Browser User')
        ->fill('email', 'browser@example.com')
        ->fill('password', 'password123')
        ->fill('password_confirmation', 'password123')
        ->submit()
        ->assertNoJavaScriptErrors()
        ->assertPathIs('/email/verify');

    $this->assertDatabaseHas('user_profile', ['email' => 'browser@example.com']);

    // Freshly registered users are unverified: protected pages bounce to the
    // verification notice. (Logout is asserted at the HTTP level in
    // Feature/Auth/AuthTest — the in-process server's session singleton
    // makes browser-level logout unobservable.)
    visit('/notifications')->assertPathIs('/email/verify');
});

it('may log in', function () {
    makeUser(['email' => 'known@example.com']);

    visit('/login')
        ->fill('email', 'known@example.com')
        ->fill('password', 'password123')
        ->submit()
        ->assertNoJavaScriptErrors()
        ->assertPathIs('/project');
});

it('rejects invalid credentials', function () {
    makeUser(['email' => 'known@example.com']);

    visit('/login')
        ->fill('email', 'known@example.com')
        ->fill('password', 'wrong-password')
        ->submit()
        ->assertPathIs('/login')
        ->assertSee('These credentials do not match our records');
});
