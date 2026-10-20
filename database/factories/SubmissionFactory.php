<?php

namespace Database\Factories;

use App\Modules\Submissions\Infrastructure\Eloquent\Submission;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Submission>
 */
#[UseModel(Submission::class)]
class SubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'form_id' => FormFactory::new(),
        ];
    }
}
