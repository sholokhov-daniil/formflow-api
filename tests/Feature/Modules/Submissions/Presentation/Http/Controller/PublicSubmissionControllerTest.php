<?php

use App\Modules\FormDefinition\Domain\Form\FieldType;
use App\Modules\Submissions\Application\Contracts\Repository\SubmissionRepositoryInterface;
use App\Modules\Submissions\Infrastructure\Eloquent\SubmissionValue;
use Database\Factories\FormFactory;
use Database\Factories\FormFieldFactory;
use Database\Factories\ProjectFactory;
use Database\Factories\SubmissionFactory;
use Database\Factories\WorkspaceMembershipFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(LazilyRefreshDatabase::class);

test('public token creates a submission with values addressed by field code', function (): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    $project = ProjectFactory::new()->for($membership->workspace)->create();
    $form = FormFactory::new()->for($project)->create();
    $name = FormFieldFactory::new()->for($form)->create(['code' => 'name', 'active' => true]);
    $email = FormFieldFactory::new()->for($form)->create(['code' => 'email', 'type' => FieldType::Email, 'active' => true]);
    $message = FormFieldFactory::new()->for($form)->create(['code' => 'message', 'active' => true]);
    $token = bin2hex(random_bytes(32));
    $form->forceFill(['public_token_hash' => hash('sha256', $token)])->save();

    $response = $this->withToken($token)->postJson('/api/v1/public/forms/submissions', [
        'values' => ['name' => 'Ada', 'email' => 'ada@example.test', 'message' => 'Hello'],
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
        'form_field_version_id' => $name->current_version_id,
        'value' => '"Ada"',
    ]);
    $this->assertDatabaseHas('submission_values', [
        'submission_id' => $submissionId,
        'form_id' => $form->id,
        'form_field_id' => $email->id,
        'value' => '"ada@example.test"',
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
    $otherForm = FormFactory::new()->for($project)->create();
    FormFieldFactory::new()->for($otherForm)->create(['code' => 'foreign', 'active' => true]);
    $token = bin2hex(random_bytes(32));
    $form->forceFill(['public_token_hash' => hash('sha256', $token)])->save();
    $before = SubmissionFactory::new()->for($form)->create();
    $submissionCount = $before->newQuery()->count();
    $valueCount = SubmissionValue::query()->count();

    $response = $this->withToken($token)->postJson('/api/v1/public/forms/submissions', [
        'values' => ['inactive' => 'value', 'foreign' => 'value'],
    ]);

    $response->assertUnprocessable()->assertInvalid(['values.inactive', 'values.foreign']);
    $this->assertDatabaseCount('submissions', $submissionCount);
    $this->assertDatabaseHas('submissions', ['id' => $before->id]);
    $this->assertDatabaseCount('submission_values', $valueCount);
});

test('public submission requires every active required field and writes nothing on failure', function (): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    $project = ProjectFactory::new()->for($membership->workspace)->create();
    $form = FormFactory::new()->for($project)->create();
    FormFieldFactory::new()->for($form)->create(['code' => 'name', 'active' => true, 'required' => true]);
    FormFieldFactory::new()->for($form)->create(['code' => 'message', 'active' => true]);
    $token = bin2hex(random_bytes(32));
    $form->forceFill(['public_token_hash' => hash('sha256', $token)])->save();

    $response = $this->withToken($token)->postJson('/api/v1/public/forms/submissions', [
        'values' => ['message' => 'Hello'],
    ]);

    $response->assertUnprocessable()->assertInvalid('values.name');
    $this->assertDatabaseCount('submissions', 0);
    $this->assertDatabaseCount('submission_values', 0);
});

test('public submission skips inactive required fields but rejects them when submitted', function (): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    $project = ProjectFactory::new()->for($membership->workspace)->create();
    $form = FormFactory::new()->for($project)->create();
    FormFieldFactory::new()->for($form)->create(['code' => 'retired', 'active' => false, 'required' => true]);
    FormFieldFactory::new()->for($form)->create(['code' => 'message', 'active' => true]);
    $token = bin2hex(random_bytes(32));
    $form->forceFill(['public_token_hash' => hash('sha256', $token)])->save();

    $this->withToken($token)->postJson('/api/v1/public/forms/submissions', [
        'values' => ['message' => 'Hello'],
    ])->assertCreated();

    $response = $this->withToken($token)->postJson('/api/v1/public/forms/submissions', [
        'values' => ['message' => 'Hello', 'retired' => 'value'],
    ]);

    $response->assertUnprocessable()->assertInvalid('values.retired');
    $this->assertDatabaseCount('submissions', 1);
});

test('disabling a form after schema retrieval prevents a new public submission', function (): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    $project = ProjectFactory::new()->for($membership->workspace)->create();
    $form = FormFactory::new()->for($project)->create();
    FormFieldFactory::new()->for($form)->create(['code' => 'message', 'active' => true]);
    $token = bin2hex(random_bytes(32));
    $form->forceFill(['public_token_hash' => hash('sha256', $token)])->save();

    $this->withToken($token)->getJson('/api/v1/public/forms/schema')->assertOk();
    $form->update(['active' => false]);

    $this->withToken($token)->postJson('/api/v1/public/forms/submissions', [
        'values' => ['message' => 'Hello'],
    ])->assertNotFound();
    $this->assertDatabaseCount('submissions', 0);
    $this->assertDatabaseCount('submission_values', 0);
});

test('field changes and removal preserve the accepted definition and administrative history', function (): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    $project = ProjectFactory::new()->for($membership->workspace)->create();
    $form = FormFactory::new()->for($project)->create();
    $field = FormFieldFactory::new()->for($form)->create([
        'code' => 'message',
        'label' => 'Message',
        'active' => true,
    ]);
    $acceptedVersionId = $field->current_version_id;
    $token = bin2hex(random_bytes(32));
    $form->forceFill(['public_token_hash' => hash('sha256', $token)])->save();

    $created = $this->withToken($token)->postJson('/api/v1/public/forms/submissions', [
        'values' => ['message' => 'Hello'],
    ])->assertCreated();
    $submissionId = $created->json('data.id');
    $value = SubmissionValue::query()->where('submission_id', $submissionId)->firstOrFail();

    $this->withBasicAuth($membership->user->email, 'password')->patchJson(
        '/api/v1/workspaces/'.$membership->workspace_id.'/projects/'.$project->id.'/forms/'.$form->id.'/fields/'.$field->id,
        ['code' => 'body', 'label' => 'Request body'],
    )->assertOk();
    $this->withBasicAuth($membership->user->email, 'password')->deleteJson(
        '/api/v1/workspaces/'.$membership->workspace_id.'/projects/'.$project->id.'/forms/'.$form->id.'/fields/'.$field->id,
    )->assertNoContent();

    $this->assertModelExists($value->fresh());
    $this->assertDatabaseHas('submission_values', [
        'id' => $value->id,
        'form_field_version_id' => $acceptedVersionId,
        'value' => '"Hello"',
    ]);
    $this->assertDatabaseHas('form_field_versions', [
        'id' => $acceptedVersionId,
        'code' => 'message',
        'label' => 'Message',
    ]);
    $form->update(['active' => false]);

    $this->withBasicAuth($membership->user->email, 'password')
        ->getJson('/api/v1/workspaces/'.$membership->workspace_id.'/projects/'.$project->id.'/forms/'.$form->id.'/submissions')
        ->assertOk()
        ->assertJsonPath('data.0.id', $submissionId);
});

test('public submission persistence rolls back when a value fails after submission creation', function (): void {
    Notification::fake();

    $membership = WorkspaceMembershipFactory::new()->create();
    $project = ProjectFactory::new()->for($membership->workspace)->create();
    $form = FormFactory::new()->for($project)->create();
    $field = FormFieldFactory::new()->for($form)->create(['code' => 'first']);

    expect(fn () => app(SubmissionRepositoryInterface::class)->createPublicForForm(
        $form->id,
        ['first' => 'saved before failure', 'second' => 'invalid foreign key'],
        [
            'first' => $field->id,
            'second' => '00000000-0000-7000-8000-000000000000',
        ],
        [
            'first' => $field->current_version_id,
            'second' => '00000000-0000-7000-8000-000000000001',
        ],
    ))->toThrow(QueryException::class);

    $this->assertDatabaseCount('submissions', 0);
    $this->assertDatabaseCount('submission_values', 0);
    Notification::assertNothingSent();
});

test('public submission rejects values that do not match the active field type without writes', function (FieldType $type, mixed $value): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    $project = ProjectFactory::new()->for($membership->workspace)->create();
    $form = FormFactory::new()->for($project)->create();
    FormFieldFactory::new()->for($form)->create([
        'code' => 'answer',
        'type' => $type,
        'active' => true,
    ]);
    $token = bin2hex(random_bytes(32));
    $form->forceFill(['public_token_hash' => hash('sha256', $token)])->save();

    $response = $this->withToken($token)->postJson('/api/v1/public/forms/submissions', [
        'values' => ['answer' => $value],
    ]);

    $response->assertUnprocessable()->assertInvalid('values.answer');
    $this->assertDatabaseCount('submissions', 0);
    $this->assertDatabaseCount('submission_values', 0);
})->with([
    'null text' => [FieldType::Text, null],
    'blank text' => [FieldType::Textarea, '   '],
    'invalid email' => [FieldType::Email, 'not-an-email'],
    'long text' => [FieldType::Text, str_repeat('a', 10001)],
    'number as string' => [FieldType::Number, '0'],
    'boolean as number' => [FieldType::Boolean, 0],
    'unsupported date type' => [FieldType::Date, '2026-11-08'],
]);

test('public submission preserves zero and false as valid values', function (): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    $project = ProjectFactory::new()->for($membership->workspace)->create();
    $form = FormFactory::new()->for($project)->create();
    $number = FormFieldFactory::new()->for($form)->create(['code' => 'score', 'type' => FieldType::Number, 'active' => true]);
    $boolean = FormFieldFactory::new()->for($form)->create(['code' => 'consent', 'type' => FieldType::Boolean, 'active' => true]);
    $token = bin2hex(random_bytes(32));
    $form->forceFill(['public_token_hash' => hash('sha256', $token)])->save();

    $response = $this->withToken($token)->postJson('/api/v1/public/forms/submissions', [
        'values' => ['score' => 0, 'consent' => false],
    ]);

    $response->assertCreated();
    $submissionId = $response->json('data.id');
    $this->assertDatabaseHas('submission_values', [
        'submission_id' => $submissionId,
        'form_field_id' => $number->id,
        'value' => '0',
    ]);
    $this->assertDatabaseHas('submission_values', [
        'submission_id' => $submissionId,
        'form_field_id' => $boolean->id,
        'value' => 'false',
    ]);
});

test('public submission limits the number of addressed fields', function (): void {
    $membership = WorkspaceMembershipFactory::new()->create();
    $project = ProjectFactory::new()->for($membership->workspace)->create();
    $form = FormFactory::new()->for($project)->create();
    $token = bin2hex(random_bytes(32));
    $form->forceFill(['public_token_hash' => hash('sha256', $token)])->save();

    $response = $this->withToken($token)->postJson('/api/v1/public/forms/submissions', [
        'values' => array_fill_keys(array_map(static fn (int $index): string => 'field_'.$index, range(1, 101)), 'value'),
    ]);

    $response->assertUnprocessable()->assertInvalid('values');
    $this->assertDatabaseCount('submissions', 0);
    $this->assertDatabaseCount('submission_values', 0);
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
