<?php

namespace Database\Factories;

use App\Modules\Submissions\Infrastructure\Eloquent\Submission;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика заполнения формы
 *
 * @extends Factory<Submission>
 */
#[UseModel(Submission::class)]
class SubmissionFactory extends Factory
{
    /**
     * Возвращает данные заполнения формы
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
