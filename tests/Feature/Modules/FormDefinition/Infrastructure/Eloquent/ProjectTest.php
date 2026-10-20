<?php

use App\Modules\FormDefinition\Infrastructure\Eloquent\Project;
use Database\Factories\FormFactory;
use Database\Factories\ProjectFactory;
use Database\Factories\SubmissionFactory;
use Database\Factories\SubmissionValueFactory;
use Database\Factories\WorkspaceFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

test('projects persist their attributes and resolve their workspace', function () {
    $workspace = WorkspaceFactory::new()->create();

    $project = Project::query()->create([
        'workspace_id' => $workspace->id,
        'name' => 'Customer feedback',
        'description' => 'Forms for collecting customer feedback',
    ])->refresh();

    $this->assertModelExists($project);
    expect($project->id)->toBeUuid();
    expect($project->name)->toBe('Customer feedback');
    expect($project->description)->toBe('Forms for collecting customer feedback');
    expect($project->workspace->is($workspace))->toBeTrue();
});

test('project names must be unique within a workspace', function () {
    $project = ProjectFactory::new()->create(['name' => 'Customer feedback']);

    expect(fn () => DB::transaction(fn () => ProjectFactory::new()->for($project->workspace)->create([
        'name' => 'Customer feedback',
    ])))->toThrow(QueryException::class, 'projects_workspace_project_name_unique');

    $this->assertDatabaseCount('projects', 1);
});

test('different workspaces can use the same project name', function () {
    ProjectFactory::new()->create(['name' => 'Customer feedback']);

    $project = ProjectFactory::new()->create(['name' => 'Customer feedback']);

    $this->assertModelExists($project);
    $this->assertDatabaseCount('projects', 2);
    $this->assertDatabaseCount('workspaces', 2);
});

test('deleting a project removes its forms and answers while preserving its workspace and other projects', function () {
    $project = ProjectFactory::new()->create();
    $workspace = $project->workspace;
    $form = FormFactory::new()->create(['project_id' => $project->id]);
    $submission = SubmissionFactory::new()->for($form)->create();
    $value = SubmissionValueFactory::new()->forSubmission($submission)->create();
    $field = $value->field;
    $otherProject = ProjectFactory::new()->for($workspace)->create();
    $otherForm = FormFactory::new()->create(['project_id' => $otherProject->id]);

    $project->delete();

    $this->assertModelMissing($project);
    $this->assertModelMissing($form);
    $this->assertModelMissing($field);
    $this->assertModelMissing($submission);
    $this->assertModelMissing($value);
    $this->assertModelExists($workspace);
    $this->assertModelExists($otherForm);
});
