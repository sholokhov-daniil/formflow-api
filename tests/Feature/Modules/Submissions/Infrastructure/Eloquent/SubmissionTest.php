<?php

use App\Modules\Submissions\Infrastructure\Eloquent\Submission;
use Database\Factories\FormFactory;
use Database\Factories\SubmissionFactory;
use Database\Factories\SubmissionValueFactory;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('submissions persist their form and resolve the form relationship with a generated UUID', function () {
    $form = FormFactory::new()->create();

    $submission = Submission::query()->create(['form_id' => $form->id])->refresh();

    $this->assertModelExists($submission);
    expect($submission->id)->toBeUuid();
    expect($submission->form->is($form))->toBeTrue();
});

test('a form can receive multiple submissions', function () {
    $form = FormFactory::new()->create();

    $submissions = SubmissionFactory::new()->count(2)->for($form)->create();

    $this->assertModelExists($submissions);
    expect($submissions->pluck('form_id')->all())->toBe([$form->id, $form->id]);
    expect($submissions->pluck('id')->unique())->toHaveCount(2);
});

test('deleting a submission removes its values while preserving the form fields and other submissions', function () {
    $value = SubmissionValueFactory::new()->create();
    $submission = $value->submission;
    $field = $value->field;
    $form = $value->form;
    $otherSubmission = SubmissionFactory::new()->for($form)->create();
    $otherValue = SubmissionValueFactory::new()->forSubmission($otherSubmission)->for($field, 'field')->create();

    $submission->delete();

    $this->assertModelMissing($submission);
    $this->assertModelMissing($value);
    $this->assertModelExists($form);
    $this->assertModelExists($field);
    $this->assertModelExists($otherSubmission);
    $this->assertModelExists($otherValue);
});
