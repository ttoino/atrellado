<?php

header('Content-Type: text/plain');

$readyFlag = getenv('WORKERS_PHP_READY_FLAG') ?: '/tmp/workers-php-ready';

if (! file_exists($readyFlag)) {
    http_response_code(503);
    header('Retry-After: 3');
    exit;
}

echo 'pong';
