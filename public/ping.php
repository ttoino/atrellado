<?php

// The container health endpoint must only pass once boot has finished:
// migrations can take tens of seconds over HTTP, and traffic before the
// schema exists would fail. The port itself opens immediately.
if (!file_exists('/tmp/atrellado-ready')) {
    http_response_code(503);
    echo 'booting';
    exit;
}

header('Content-Type: text/plain');
echo 'pong';
