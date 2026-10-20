<?php

use App\Modules\Submissions\Infrastructure\Eloquent\SubmissionValue;
use Database\Factories\FormFieldFactory;
use Database\Factories\SubmissionFactory;
use Database\Factories\SubmissionValueFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

test('submission values round-trip JSON data and resolve the submission form and field', function (mixed $input) {
    $submission = SubmissionFactory::new()->create();
    $field = FormFieldFactory::new()->for($submission->form)->create();

    $value = SubmissionValue::query()->create([
        'submission_id' => $submission->id,
        'form_id' => $submission->form_id,
        'form_field_id' => $field->id,
        'value' => $input,
    ])->refresh();

    $this->assertModelExists($value);
    expect($value->id)->toBeUuid();
    expect($value->value)->toEqual($input);
    expect(get_debug_type($value->value))->toBe(get_debug_type($input));
    expect($value->submission->is($submission))->toBeTrue();
    expect($value->form->is($submission->form))->toBeTrue();
    expect($value->field->is($field))->toBeTrue();
})->with([
    'text' => ['Ответ на форму'],
    'empty string' => [''],
    'integer' => [42],
    'zero' => [0],
    'decimal' => [12.5],
    'true' => [true],
    'false' => [false],
    'null' => [null],
    'empty array' => [[]],
    'list' => [['first', 'second']],
    'object' => [['name' => 'Report', 'count' => 2, 'enabled' => false, 'metadata' => ['source' => 'web']]],
]);

test('a submission cannot contain duplicate values for the same field', function () {
    $value = SubmissionValueFactory::new()->create();

    expect(fn () => DB::transaction(fn () => SubmissionValue::query()->create([
        'submission_id' => $value->submission_id,
        'form_id' => $value->form_id,
        'form_field_id' => $value->form_field_id,
        'value' => 'Duplicate answer',
    ])))->toThrow(QueryException::class, 'submission_values_submission_id_form_field_id_unique');

    $this->assertDatabaseCount('submission_values', 1);
});

test('different submissions can contain values for the same field', function () {
    $value = SubmissionValueFactory::new()->create();
    $submission = SubmissionFactory::new()->for($value->form)->create();

    $otherValue = SubmissionValueFactory::new()->forSubmission($submission)->for($value->field, 'field')->create();

    $this->assertModelExists($otherValue);
    $this->assertDatabaseCount('submission_values', 2);
    expect($otherValue->form_field_id)->toBe($value->form_field_id);
});

test('one submission can contain values for multiple fields', function () {
    $submission = SubmissionFactory::new()->create();

    $values = SubmissionValueFactory::new()->count(2)->forSubmission($submission)->create();

    $this->assertModelExists($values);
    expect($values->pluck('form_field_id')->unique())->toHaveCount(2);
});

test('a submission rejects a field belonging to a different form', function () {
    $submission = SubmissionFactory::new()->create();
    $foreignField = FormFieldFactory::new()->create();

    expect(fn () => DB::transaction(fn () => SubmissionValue::query()->create([
        'submission_id' => $submission->id,
        'form_id' => $submission->form_id,
        'form_field_id' => $foreignField->id,
        'value' => 'Invalid answer',
    ])))->toThrow(QueryException::class, 'submission_values_form_field_id_form_id_foreign');

    $this->assertDatabaseCount('submission_values', 0);
});

test('a value cannot use a submission belonging to a different form', function () {
    $submission = SubmissionFactory::new()->create();
    $foreignField = FormFieldFactory::new()->create();

    expect(fn () => DB::transaction(fn () => SubmissionValue::query()->create([
        'submission_id' => $submission->id,
        'form_id' => $foreignField->form_id,
        'form_field_id' => $foreignField->id,
        'value' => 'Invalid answer',
    ])))->toThrow(QueryException::class, 'submission_values_submission_id_form_id_foreign');

    $this->assertDatabaseCount('submission_values', 0);
});

test('existing values cannot be moved to records from another form', function (string $attribute) {
    $value = SubmissionValueFactory::new()->create();
    $foreignValue = SubmissionValueFactory::new()->create();
    $originalId = $value->getAttribute($attribute);

    expect(fn () => DB::transaction(fn () => $value->update([
        $attribute => $foreignValue->getAttribute($attribute),
    ])))->toThrow(QueryException::class, 'foreign key constraint');

    expect($value->fresh()->getAttribute($attribute))->toBe($originalId);
    $this->assertDatabaseCount('submission_values', 2);
})->with([
    'form' => 'form_id',
    'submission' => 'submission_id',
    'field' => 'form_field_id',
]);

test('an existing field value can be replaced without creating a duplicate record', function () {
    $value = SubmissionValueFactory::new()->create(['value' => 'Original answer']);

    $value->update(['value' => false]);
    $value->refresh();

    expect($value->value)->toBeFalse();
    $this->assertDatabaseCount('submission_values', 1);
});
