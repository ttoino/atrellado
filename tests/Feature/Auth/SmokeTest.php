<?php

use App\Models\User;

it('renders all guest auth form pages', function () {
    $this->get('/login')->assertOk();
    $this->get('/register')->assertOk();
    $this->get('/recover-password')->assertOk();
    $this->get('/reset-password/some-token')->assertOk();
});

it('shows the verification notice to unverified users', function () {
    $this->actingAs(User::factory()->create())
        ->get('/email/verify')
        ->assertOk();
});

it('redirects verified users away from the verification notice', function () {
    $this->actingAs(makeUser())
        ->get('/email/verify')
        ->assertRedirect();
});
