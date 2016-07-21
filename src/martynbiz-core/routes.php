<?php
// Routes

$app->get('/hello', function ($request, $response, $args) {
    
    $response->getBody()->write('Hello world!');
    return $response;
});
