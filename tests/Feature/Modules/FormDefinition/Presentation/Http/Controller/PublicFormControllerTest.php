<?php

use Database\Factories\FormFactory;
use Database\Factories\FormFieldFactory;
use Database\Factories\ProjectFactory;
use Database\Factories\UserFactory;
use Database\Factories\WorkspaceMembershipFactory;

test('form token is issued only to a member and only its hash is stored', function (): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    $project = ProjectFactory::new()->for($membership->workspace)->create();
    $form = FormFactory::new()->for($project)->create();
    $url = '/api/v1/workspaces/'.$membership->workspace_id.'/projects/'.$project->id.'/forms/'.$form->id.'/public-token';

    $response = $this->withBasicAuth($membership->user->email, 'password')->postJson($url);

    $response->assertCreated()->assertJsonPath('data.form_id', $form->id);
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
    $token = $response->json('data.token');

    expect($token)->toMatch('/^[a-f0-9]{64}$/')
        ->and($form->fresh()->public_token_hash)->toBe(hash('sha256', $token))
        ->and($form->fresh()->public_token_hash)->not->toBe($token)
        ->and($form->fresh()->toArray())->not->toHaveKey('public_token_hash');

    $replacement = $this->withBasicAuth($membership->user->email, 'password')->postJson($url);
    $replacement->assertCreated();
    expect($form->fresh()->public_token_hash)->toBe(hash('sha256', $replacement->json('data.token')))
        ->and($replacement->json('data.token'))->not->toBe($token);
    $this->withToken($token)->getJson('/api/v1/public/forms/schema')->assertNotFound();
});

test('form token issuance is protected by basic authentication and membership', function (): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    $project = ProjectFactory::new()->for($membership->workspace)->create();
    $form = FormFactory::new()->for($project)->create();
    $url = '/api/v1/workspaces/'.$membership->workspace_id.'/projects/'.$project->id.'/forms/'.$form->id.'/public-token';

    $this->postJson($url)->assertUnauthorized();

    $outsider = UserFactory::new()->create();
    $this->withBasicAuth($outsider->email, 'password')->postJson($url)->assertForbidden();

    expect($form->fresh()->public_token_hash)->toBeNull();
});

test('public schema exposes only active public field data and token can be revoked', function (): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    $project = ProjectFactory::new()->for($membership->workspace)->create();
    $form = FormFactory::new()->for($project)->create(['name' => 'Contact us']);
    $visible = FormFieldFactory::new()->for($form)->create([
        'code' => 'email',
        'label' => 'Email address',
        'position' => 1,
        'active' => true,
        'required' => true,
    ]);
    FormFieldFactory::new()->for($form)->create(['code' => 'internal', 'active' => false]);
    $token = bin2hex(random_bytes(32));
    $form->forceFill(['public_token_hash' => hash('sha256', $token)])->save();

    $response = $this->withToken($token)->getJson('/api/v1/public/forms/schema');

    $response->assertOk()->assertJsonPath('data.name', 'Contact us')
        ->assertJsonPath('data.fields.0.code', 'email')
        ->assertJsonPath('data.fields.0.required', true);
    expect(array_keys($response->json('data.fields.0')))->toEqualCanonicalizing(['code', 'label', 'position', 'type', 'required'])
        ->and(array_column($response->json('data.fields'), 'code'))->toBe(['email']);

    $url = '/api/v1/workspaces/'.$membership->workspace_id.'/projects/'.$project->id.'/forms/'.$form->id.'/public-token';
    $this->withBasicAuth($membership->user->email, 'password')->deleteJson($url)->assertNoContent();
    $this->withToken($token)->getJson('/api/v1/public/forms/schema')->assertNotFound();
    expect($form->fresh()->public_token_hash)->toBeNull();
});

test('public schema hides inactive forms and invalid tokens with the same response', function (): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    $project = ProjectFactory::new()->for($membership->workspace)->create();
    $form = FormFactory::new()->for($project)->create(['active' => false]);
    $token = bin2hex(random_bytes(32));
    $form->forceFill(['public_token_hash' => hash('sha256', $token)])->save();

    $this->withToken($token)->getJson('/api/v1/public/forms/schema')
        ->assertNotFound()
        ->assertExactJson(['message' => 'The public form is unavailable.']);
    $this->withToken(str_repeat('0', 64))->getJson('/api/v1/public/forms/schema')
        ->assertNotFound()
        ->assertExactJson(['message' => 'The public form is unavailable.']);
});

test('public API CORS preflight allows only configured origins without credentials', function (): void {
    config(['cors.allowed_origins' => ['https://forms.example.test']]);

    $response = $this->withHeaders([
        'Origin' => 'https://forms.example.test',
        'Access-Control-Request-Method' => 'GET',
        'Access-Control-Request-Headers' => 'Authorization',
    ])->options('/api/v1/public/forms/schema');

    $response->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', 'https://forms.example.test')
        ->assertHeaderMissing('Access-Control-Allow-Credentials');

    $disallowed = $this->withHeaders([
        'Origin' => 'https://other.example.test',
        'Access-Control-Request-Method' => 'GET',
    ])->options('/api/v1/public/forms/schema');

    $disallowed->assertNoContent()
        ->assertHeader('Access-Control-Allow-Origin', 'https://forms.example.test');
});
