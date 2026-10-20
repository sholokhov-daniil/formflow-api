<?php

namespace Database\Factories;

use App\Modules\FormDefinition\Domain\Form\FieldType;
use App\Modules\FormDefinition\Infrastructure\Eloquent\FormField;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FormField>
 */
#[UseModel(FormField::class)]
class FormFieldFactory extends Factory
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
            'code' => fake()->unique()->lexify('field_????????'),
            'label' => fake()->words(3, true),
            'position' => 1,
            'type' => FieldType::Text,
            'active' => false,
            'required' => false,
        ];
    }
}
