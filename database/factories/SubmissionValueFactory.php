<?php

namespace Database\Factories;

use App\Modules\Submissions\Infrastructure\Eloquent\Submission;
use App\Modules\Submissions\Infrastructure\Eloquent\SubmissionValue;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubmissionValue>
 */
#[UseModel(SubmissionValue::class)]
class SubmissionValueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'submission_id' => SubmissionFactory::new(),
            'form_id' => fn (array $attributes): string => Submission::query()->findOrFail($attributes['submission_id'])->form_id,
            'form_field_id' => fn (array $attributes): string => FormFieldFactory::new()->create([
                'form_id' => $attributes['form_id'],
            ])->id,
            'value' => fake()->sentence(),
        ];
    }

    public function forSubmission(Submission $submission): static
    {
        return $this->state([
            'submission_id' => $submission->id,
            'form_id' => $submission->form_id,
        ]);
    }
}
