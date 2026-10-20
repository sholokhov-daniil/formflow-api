<?php

use App\Modules\FormDefinition\Infrastructure\Eloquent\Project;
use App\Modules\Identity\Infrastructure\Eloquent\User;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use Database\Factories\SubmissionValueFactory;
use Database\Factories\WorkspaceMembershipFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

test('workspaces persist their name and supported kind with a generated UUID', function (string $kind) {
    $workspace = Workspace::query()->create(['name' => 'Product team', 'kind' => $kind]);
    $workspace->refresh();

    $this->assertModelExists($workspace);
    expect($workspace->id)->toBeUuid();
    expect($workspace->name)->toBe('Product team');
    expect($workspace->kind)->toBe($kind);
})->with(['personal' => 'personal', 'team' => 'team']);

test('workspaces default to the team kind when it is omitted', function () {
    $workspace = Workspace::query()->create(['name' => 'Product team']);
    $workspace->refresh();

    expect($workspace->kind)->toBe('team');
});

test('workspaces reject unsupported kinds', function () {
    expect(fn () => DB::transaction(fn () => Workspace::query()->create([
        'name' => 'Product team',
        'kind' => 'organization',
    ])))->toThrow(QueryException::class, 'workspaces_kind_check');

    $this->assertDatabaseCount('workspaces', 0);
});

test('deleting a workspace removes its memberships and form data while preserving users and other workspaces', function () {
    $value = SubmissionValueFactory::new()->create();
    $submission = $value->submission;
    $field = $value->field;
    $form = $value->form;
    $project = Project::query()->findOrFail($form->project_id);
    $workspace = $project->workspace;
    $creator = User::query()->findOrFail($form->created_by);
    $membership = WorkspaceMembershipFactory::new()->for($workspace)->for($creator)->create();
    $unrelatedValue = SubmissionValueFactory::new()->create();

    $workspace->delete();

    $this->assertModelMissing($workspace);
    $this->assertModelMissing($membership);
    $this->assertModelMissing($project);
    $this->assertModelMissing($form);
    $this->assertModelMissing($field);
    $this->assertModelMissing($submission);
    $this->assertModelMissing($value);
    $this->assertModelExists($creator);
    $this->assertModelExists($unrelatedValue);
});
