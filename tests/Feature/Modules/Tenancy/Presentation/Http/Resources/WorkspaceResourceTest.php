<?php

use App\Modules\Tenancy\Presentation\Http\Resources\WorkspaceResource;
use Database\Factories\UserFactory;
use Database\Factories\WorkspaceFactory;
use Illuminate\Http\Request;

test('workspace resources expose only public fields even with extra attributes and loaded relations', function (string $kind) {
    $workspace = WorkspaceFactory::new()->make([
        'id' => '019a0000-0000-7000-8000-000000000001',
        'name' => 'Product workspace',
        'kind' => $kind,
        'created_at' => '2026-10-20 00:00:00',
    ]);
    $workspace->setAttribute('internal_notes', 'Private workspace details');
    $workspace->setRelation('owner', UserFactory::new()->make());

    $result = (new WorkspaceResource($workspace))->resolve(Request::create('/api/v1/workspaces'));

    expect($result)->toBe([
        'id' => '019a0000-0000-7000-8000-000000000001',
        'name' => 'Product workspace',
        'kind' => $kind,
    ]);
})->with(['personal', 'team']);
