<?php

use App\Modules\FormDefinition\Domain\Form\FieldType;
use App\Modules\FormDefinition\Infrastructure\Eloquent\FormField;
use Database\Factories\FormFactory;
use Database\Factories\FormFieldFactory;
use Database\Factories\SubmissionFactory;
use Database\Factories\SubmissionValueFactory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(LazilyRefreshDatabase::class);

test('fields persist supported types and cast their attributes after reading from the database', function (FieldType $type) {
    $form = FormFactory::new()->create();

    $field = FormField::query()->create([
        'form_id' => $form->id,
        'code' => 'contact',
        'label' => 'Contact details',
        'position' => '7',
        'type' => $type->value,
        'active' => true,
        'required' => true,
    ])->refresh();

    $this->assertModelExists($field);
    expect($field->id)->toBeUuid();
    expect($field->code)->toBe('contact');
    expect($field->label)->toBe('Contact details');
    expect($field->position)->toBe(7);
    expect($field->type)->toBe($type);
    expect($field->active)->toBeTrue();
    expect($field->required)->toBeTrue();
    expect($field->form->is($form))->toBeTrue();
})->with(FieldType::cases());

test('fields default to inactive and optional when the flags are omitted', function () {
    $form = FormFactory::new()->create();

    $field = FormField::query()->create([
        'form_id' => $form->id,
        'code' => 'comment',
        'label' => 'Comment',
        'position' => 0,
        'type' => FieldType::Textarea,
    ])->refresh();

    expect($field->active)->toBeFalse();
    expect($field->required)->toBeFalse();
    expect($field->position)->toBe(0);
});

test('field types can be updated using enum values', function () {
    $field = FormFieldFactory::new()->create(['type' => FieldType::Text]);

    $field->update(['type' => FieldType::Email, 'active' => true, 'required' => true, 'position' => 2]);
    $field->refresh();

    expect($field->type)->toBe(FieldType::Email);
    expect($field->active)->toBeTrue();
    expect($field->required)->toBeTrue();
    expect($field->position)->toBe(2);
});

test('the database rejects unsupported field types even when model casts are bypassed', function () {
    $field = FormFieldFactory::new()->create();

    expect(fn () => DB::transaction(fn () => FormField::query()->whereKey($field->id)->update([
        'type' => 'unsupported',
    ])))->toThrow(QueryException::class, 'form_fields_type_check');

    expect($field->fresh()->type)->toBe(FieldType::Text);
});

test('field codes must be unique within a form', function () {
    $field = FormFieldFactory::new()->create(['code' => 'email']);

    expect(fn () => DB::transaction(fn () => FormFieldFactory::new()->for($field->form)->create([
        'code' => 'email',
    ])))->toThrow(QueryException::class, 'form_fields_code_unique');

    $this->assertDatabaseCount('form_fields', 1);
});

test('different forms can use the same field code', function () {
    FormFieldFactory::new()->create(['code' => 'email']);

    $field = FormFieldFactory::new()->create(['code' => 'email']);

    $this->assertModelExists($field);
    $this->assertDatabaseCount('form_fields', 2);
    $this->assertDatabaseCount('forms', 2);
});

test('deleting a field removes its values while preserving the submission and values of other fields', function () {
    $submission = SubmissionFactory::new()->create();
    $value = SubmissionValueFactory::new()->forSubmission($submission)->create();
    $otherValue = SubmissionValueFactory::new()->forSubmission($submission)->create();
    $field = $value->field;
    $form = $value->form;

    $field->delete();

    $this->assertModelMissing($field);
    $this->assertModelMissing($value);
    $this->assertModelExists($submission);
    $this->assertModelExists($form);
    $this->assertModelExists($otherValue);
});
