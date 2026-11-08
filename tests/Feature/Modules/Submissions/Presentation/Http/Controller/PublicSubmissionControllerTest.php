<?php

use App\Modules\Submissions\Infrastructure\Eloquent\SubmissionValue;
use Database\Factories\FormFactory;
use Database\Factories\FormFieldFactory;
use Database\Factories\ProjectFactory;
use Database\Factories\SubmissionFactory;
use Database\Factories\WorkspaceMembershipFactory;

test('public token creates a submission with values addressed by field code', function (): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    $project = ProjectFactory::new()->for($membership->workspace)->create();
    $form = FormFactory::new()->for($project)->create();
    $name = FormFieldFactory::new()->for($form)->create(['code' => 'name', 'active' => true]);
    $message = FormFieldFactory::new()->for($form)->create(['code' => 'message', 'active' => true]);
    $token = bin2hex(random_bytes(32));
    $form->forceFill(['public_token_hash' => hash('sha256', $token)])->save();

    $response = $this->withToken($token)->postJson('/api/v1/public/forms/submissions', [
        'values' => ['name' => 'Ada', 'message' => 'Hello'],
        'form_id' => '019a0000-0000-7000-8000-000000000099',
        'created_by' => 999,
    ]);

    $response->assertCreated();
    $submissionId = $response->json('data.id');
    $this->assertDatabaseHas('submissions', ['id' => $submissionId, 'form_id' => $form->id]);
    $this->assertDatabaseHas('submission_values', [
        'submission_id' => $submissionId,
        'form_id' => $form->id,
        'form_field_id' => $name->id,
        'value' => '"Ada"',
    ]);
    $this->assertDatabaseHas('submission_values', [
        'submission_id' => $submissionId,
        'form_id' => $form->id,
        'form_field_id' => $message->id,
        'value' => '"Hello"',
    ]);
    expect(array_keys($response->json('data')))->toEqualCanonicalizing(['id', 'created_at']);
});

test('public submission rejects inactive or unknown fields without creating records', function (): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    $project = ProjectFactory::new()->for($membership->workspace)->create();
    $form = FormFactory::new()->for($project)->create();
    FormFieldFactory::new()->for($form)->create(['code' => 'inactive', 'active' => false]);
    $token = bin2hex(random_bytes(32));
    $form->forceFill(['public_token_hash' => hash('sha256', $token)])->save();
    $before = SubmissionFactory::new()->for($form)->create();
    $submissionCount = $before->newQuery()->count();
    $valueCount = SubmissionValue::query()->count();

    $response = $this->withToken($token)->postJson('/api/v1/public/forms/submissions', [
        'values' => ['inactive' => 'value'],
    ]);

    $response->assertUnprocessable()->assertInvalid('values');
    $this->assertDatabaseCount('submissions', $submissionCount);
    $this->assertDatabaseHas('submissions', ['id' => $before->id]);
    $this->assertDatabaseCount('submission_values', $valueCount);
});

test('public token cannot list or read submissions', function (): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    $project = ProjectFactory::new()->for($membership->workspace)->create();
    $form = FormFactory::new()->for($project)->create();
    $submission = SubmissionFactory::new()->for($form)->create();
    $token = bin2hex(random_bytes(32));
    $form->forceFill(['public_token_hash' => hash('sha256', $token)])->save();

    $this->withToken($token)->getJson('/api/v1/workspaces/'.$membership->workspace_id.'/projects/'.$project->id.'/forms/'.$form->id.'/submissions')->assertUnauthorized();
    $this->withToken($token)->getJson('/api/v1/public/forms/submissions/'.$submission->id)->assertNotFound();
});
