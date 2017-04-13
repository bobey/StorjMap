<?php

use Symfony\Component\HttpFoundation\JsonResponse;

$app->get('/api/nodes', function () use ($app) {
    return new JsonResponse(file_get_contents(__DIR__.'/../var/contacts/contacts.json'), 200, [], true);
})
->bind('nodes');
