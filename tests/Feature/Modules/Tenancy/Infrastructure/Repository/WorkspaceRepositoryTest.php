<?php

use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceRepositoryInterface;
use Database\Factories\UserFactory;
use Database\Factories\WorkspaceFactory;
use Database\Factories\WorkspaceMembershipFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('accessible workspaces are ordered by name and then UUID across personal and team kinds', function () {
    $user = UserFactory::new()->create();
    $beta = WorkspaceFactory::new()->create([
        'id' => '019a0000-0000-7000-8000-000000000003',
        'name' => 'Beta',
    ]);
    $secondAlpha = WorkspaceFactory::new()->create([
        'id' => '019a0000-0000-7000-8000-000000000002',
        'name' => 'Alpha',
    ]);
    $firstAlpha = WorkspaceFactory::new()->personal()->create([
        'id' => '019a0000-0000-7000-8000-000000000001',
        'name' => 'Alpha',
    ]);
    foreach ([$beta, $secondAlpha, $firstAlpha] as $workspace) {
        WorkspaceMembershipFactory::new()->for($user)->for($workspace)->create();
    }

    $result = app(WorkspaceRepositoryInterface::class)->accessibleToUser($user->id);

    expect($result->pluck('id')->all())->toBe([
        '019a0000-0000-7000-8000-000000000001',
        '019a0000-0000-7000-8000-000000000002',
        '019a0000-0000-7000-8000-000000000003',
    ]);
});

test('a workspace stops being accessible after its membership is removed', function () {
    $membership = WorkspaceMembershipFactory::new()->create();
    $userId = $membership->user_id;
    $repository = app(WorkspaceRepositoryInterface::class);
    expect($repository->accessibleToUser($userId)->pluck('id')->all())->toBe([$membership->workspace_id]);

    $membership->delete();

    expect($repository->accessibleToUser($userId))->toBeEmpty();
});
