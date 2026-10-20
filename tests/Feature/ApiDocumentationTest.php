<?php

test('the API documentation endpoint renders Swagger UI', function () {
    $response = $this->get('/doc/api');

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
        ->assertHeaderMissing('Content-Disposition')
        ->assertSeeHtml('id="swagger-ui"')
        ->assertSeeHtml('data-specification-url="/doc/api/openapi.yaml"')
        ->assertSee('swagger-ui.css', escape: false)
        ->assertSee('swagger-ui-bundle.js', escape: false)
        ->assertSee('SwaggerUIBundle(', escape: false);
});

test('the API specification endpoint serves the OpenAPI YAML file', function () {
    $response = $this->get('/doc/api/openapi.yaml');

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'application/yaml; charset=UTF-8');

    $this->expectOutputString(file_get_contents(base_path('docs/api/openapi.yaml')));

    $response->sendContent();
});
