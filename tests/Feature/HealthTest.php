<?php

it('serves the health check endpoint', function () {
    $this->get('/up')->assertOk();
});
