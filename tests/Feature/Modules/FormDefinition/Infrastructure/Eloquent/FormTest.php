<?php

use App\Modules\FormDefinition\Infrastructure\Eloquent\Form;
use App\Modules\FormDefinition\Infrastructure\Eloquent\Project;
use App\Modules\Identity\Infrastructure\Eloquent\User;
use Database\Factories\FormFactory;
use Database\Factories\ProjectFactory;
use Database\Factories\SubmissionFactory;
use Database\Factories\SubmissionValueFactory;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('forms persist their owner and project and round-trip JSON settings', function (?array $settings) {
    $project = ProjectFactory::new()->create();
    $creator = UserFactory::new()->create();

    $form = Form::query()->create([
        'project_id' => $project->id,
        'created_by' => $creator->id,
        'name' => 'Contact us',
        'settings' => $settings,
        'active' => true,
    ])->refresh();

    $this->assertModelExists($form);
    expect($form->id)->toBeUuid();
    expect($form->project_id)->toBe($project->id);
    expect($form->created_by)->toBe($creator->id);
    expect($form->name)->toBe('Contact us');
    expect($form->settings)->toEqual($settings);
    expect(get_debug_type($form->settings))->toBe(get_debug_type($settings));
})->with([
    'no settings' => [null],
    'empty settings' => [[]],
    'nested settings' => [['confirmation' => ['message' => 'Спасибо!', 'redirect' => null], 'limit' => 10, 'notify' => true]],
]);

test('form activity is returned as a boolean after saving and updating', function (bool $active) {
    $form = FormFactory::new()->create(['active' => $active])->refresh();

    expect($form->active)->toBe($active);

    $form->update(['active' => ! $active]);

    expect($form->fresh()->active)->toBe(! $active);
})->with(['active' => true, 'inactive' => false]);

test('deleting a form removes its fields and answers while preserving its project and creator', function () {
    $value = SubmissionValueFactory::new()->create();
    $form = $value->form;
    $field = $value->field;
    $submission = $value->submission;
    $project = Project::query()->findOrFail($form->project_id);
    $creator = User::query()->findOrFail($form->created_by);
    $otherForm = FormFactory::new()->create(['project_id' => $project->id, 'created_by' => $creator->id]);

    $form->delete();

    $this->assertModelMissing($form);
    $this->assertModelMissing($field);
    $this->assertModelMissing($submission);
    $this->assertModelMissing($value);
    $this->assertModelExists($project);
    $this->assertModelExists($creator);
    $this->assertModelExists($otherForm);
});

test('deleting the creator removes their forms and answers while preserving the project and other creators forms', function () {
    $creator = UserFactory::new()->create();
    $project = ProjectFactory::new()->create();
    $form = FormFactory::new()->create(['created_by' => $creator->id, 'project_id' => $project->id]);
    $submission = SubmissionFactory::new()->for($form)->create();
    $value = SubmissionValueFactory::new()->forSubmission($submission)->create();
    $field = $value->field;
    $otherForm = FormFactory::new()->create(['project_id' => $project->id]);

    $creator->delete();

    $this->assertModelMissing($form);
    $this->assertModelMissing($field);
    $this->assertModelMissing($submission);
    $this->assertModelMissing($value);
    $this->assertModelExists($project);
    $this->assertModelExists($otherForm);
});
