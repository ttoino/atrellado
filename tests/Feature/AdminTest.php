<?php

it('forbids the admin area to non-admins', function () {
    $this->actingAs(makeUser())->get('/admin/users')->assertForbidden();
});

it('shows the admin area to admins', function () {
    $this->actingAs(makeUser(['is_admin' => true]))->get('/admin/users')->assertOk();
});
