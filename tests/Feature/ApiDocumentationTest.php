<?php

use App\Modules\Identity\Infrastructure\Eloquent\User;
use App\Modules\Tenancy\Presentation\Http\Resources\WorkspaceResource;
use Database\Factories\UserFactory;
use Database\Factories\WorkspaceFactory;
use Database\Factories\WorkspaceMembershipFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Symfony\Component\Yaml\Yaml;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['app.debug' => false]);
});

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

test('the documented workspace example matches the authenticated API response', function () {
    $specification = Yaml::parseFile(base_path('docs/api/openapi.yaml'));
    $path = '/'.Route::getRoutes()->getByName('workspaces.index')->uri();
    $example = $specification['paths'][$path]['get']['responses']['200']['content']['application/json']['examples']['workspaces']['value'];
    $user = UserFactory::new()->create(['password' => 'correct-password']);
    foreach (array_reverse($example['data']) as $attributes) {
        $workspace = WorkspaceFactory::new()->create($attributes);
        WorkspaceMembershipFactory::new()->for($user)->for($workspace)->create();
    }

    $response = $this->withBasicAuth($user->email, 'correct-password')->getJson($path);

    $response->assertOk()->assertExactJson($example);
});

test('the documented empty workspace example matches the authenticated API response', function () {
    $specification = Yaml::parseFile(base_path('docs/api/openapi.yaml'));
    $path = '/'.Route::getRoutes()->getByName('workspaces.index')->uri();
    $example = $specification['paths'][$path]['get']['responses']['200']['content']['application/json']['examples']['empty']['value'];
    $user = UserFactory::new()->create(['password' => 'correct-password']);

    $response = $this->withBasicAuth($user->email, 'correct-password')->getJson($path);

    $response->assertOk()->assertExactJson($example);
});

test('the documented authentication scheme and 401 response match the API', function () {
    $specification = Yaml::parseFile(base_path('docs/api/openapi.yaml'));
    $path = '/'.Route::getRoutes()->getByName('workspaces.index')->uri();
    $operation = $specification['paths'][$path]['get'];
    $securityName = array_key_first($operation['security'][0]);
    $scheme = $specification['components']['securitySchemes'][$securityName];
    $responseContract = $operation['responses']['401'];

    $response = $this->getJson($path);

    expect($scheme['type'])->toBe('http');
    expect($scheme['scheme'])->toBe('basic');
    $response
        ->assertUnauthorized()
        ->assertHeader('WWW-Authenticate', $responseContract['headers']['WWW-Authenticate']['example'])
        ->assertExactJson($responseContract['content']['application/json']['example']);
});

test('the documented 403 response matches a denied viewAny ability', function () {
    $specification = Yaml::parseFile(base_path('docs/api/openapi.yaml'));
    $path = '/'.Route::getRoutes()->getByName('workspaces.index')->uri();
    $example = $specification['paths'][$path]['get']['responses']['403']['content']['application/json']['example'];
    $user = UserFactory::new()->create(['password' => 'correct-password']);
    Gate::before(fn (User $user, string $ability): ?bool => $ability === 'viewAny' ? false : null);

    $response = $this->withBasicAuth($user->email, 'correct-password')->getJson($path);

    $response->assertForbidden()->assertExactJson($example);
});

test('the documented 429 response matches the authentication block body and Retry-After header', function () {
    $this->freezeTime();
    $specification = Yaml::parseFile(base_path('docs/api/openapi.yaml'));
    $path = '/'.Route::getRoutes()->getByName('workspaces.index')->uri();
    $contract = $specification['paths'][$path]['get']['responses']['429'];
    $schemaName = basename($contract['content']['application/json']['schema']['$ref']);
    $schema = $specification['components']['schemas'][$schemaName];
    $this->withBasicAuth('unknown@example.test', 'incorrect-password');
    $this->getJson($path)->assertUnauthorized();
    $this->getJson($path)->assertUnauthorized();

    $response = $this->getJson($path);

    $response->assertTooManyRequests()
        ->assertHeader('Retry-After', (string) $contract['headers']['Retry-After']['example'])
        ->assertExactJson($contract['content']['application/json']['example']);
    expect(array_keys($response->json()))->toEqualCanonicalizing($schema['required']);
    expect(array_keys($response->json()))->toEqualCanonicalizing(array_keys($schema['properties']));
    expect($schema['additionalProperties'])->toBeFalse();
    expect($response->json('message'))->toBeString();
    expect($response->json('retry_after'))->toBeInt()->toBeGreaterThanOrEqual($schema['properties']['retry_after']['minimum']);
    expect((int) $response->headers->get('Retry-After'))->toBe($response->json('retry_after'));
});

test('the documented workspace schema matches the public resource fields and supported kinds', function (string $kind) {
    $specification = Yaml::parseFile(base_path('docs/api/openapi.yaml'));
    $schema = $specification['components']['schemas']['Workspace'];
    $workspace = WorkspaceFactory::new()->make([
        'id' => '019a0000-0000-7000-8000-000000000001',
        'name' => 'Product workspace',
        'kind' => $kind,
    ]);

    $serialized = (new WorkspaceResource($workspace))->resolve();

    expect(array_keys($serialized))->toEqualCanonicalizing($schema['required']);
    expect(array_keys($schema['properties']))->toEqualCanonicalizing(array_keys($serialized));
    expect($schema['additionalProperties'])->toBeFalse();
    foreach ($schema['properties'] as $property) {
        expect($property['type'])->toBe('string');
    }
    expect($schema['properties']['id']['format'])->toBe('uuid');
    expect($serialized['id'])->toBeUuid();
    expect($serialized['kind'])->toBeIn($schema['properties']['kind']['enum']);
})->with(['personal', 'team']);

test('the OpenAPI response references resolve to the documented workspace and error schemas', function () {
    $specification = Yaml::parseFile(base_path('docs/api/openapi.yaml'));
    $operation = $specification['paths']['/api/v1/workspaces']['get'];
    $responses = $operation['responses'];

    expect($specification['openapi'])->toBe('3.1.0');
    expect($responses['200']['content']['application/json']['schema']['$ref'])->toBe('#/components/schemas/WorkspaceList');
    expect($responses['401']['content']['application/json']['schema']['$ref'])->toBe('#/components/schemas/Error');
    expect($responses['403']['content']['application/json']['schema']['$ref'])->toBe('#/components/schemas/Error');
    expect($responses['429']['content']['application/json']['schema']['$ref'])->toBe('#/components/schemas/AuthenticationRateLimitError');
    expect($specification['components']['schemas']['WorkspaceList'])->toMatchArray([
        'type' => 'object',
        'additionalProperties' => false,
        'required' => ['data'],
        'properties' => [
            'data' => [
                'type' => 'array',
                'items' => ['$ref' => '#/components/schemas/Workspace'],
            ],
        ],
    ]);
    expect($specification['components']['schemas']['Error'])->toMatchArray([
        'type' => 'object',
        'additionalProperties' => false,
        'required' => ['message'],
        'properties' => ['message' => ['type' => 'string']],
    ]);
});
