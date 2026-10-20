<?php

test('missing API routes return 404 JSON without a trace even in debug mode', function (string $path) {
    config(['app.debug' => true]);

    $response = $this->get($path, ['Accept' => 'text/html']);

    $response
        ->assertNotFound()
        ->assertHeader('Content-Type', 'application/json')
        ->assertExactJson(['message' => 'The route or resource was not found.']);
})->with(['/api', '/api/v1/missing-endpoint']);

test('missing non API routes return the custom 404 JSON when JSON is requested', function () {
    config(['app.debug' => true]);

    $response = $this->getJson('/missing-page');

    $response
        ->assertNotFound()
        ->assertExactJson(['message' => 'The route or resource was not found.']);
});

test('missing web pages keep their HTML 404 response', function () {
    config(['app.debug' => false]);

    $response = $this->get('/missing-page', ['Accept' => 'text/html']);

    $response
        ->assertNotFound()
        ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
        ->assertSee('Not Found');
});
