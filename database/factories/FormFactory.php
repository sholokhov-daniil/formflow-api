<?php

namespace Database\Factories;

use App\Modules\FormDefinition\Infrastructure\Eloquent\Form;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика формы
 *
 * @extends Factory<Form>
 */
#[UseModel(Form::class)]
class FormFactory extends Factory
{
    /**
     * Возвращает данные формы
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => ProjectFactory::new(),
            'created_by' => UserFactory::new(),
            'name' => fake()->sentence(3),
            'settings' => null,
            'active' => true,
        ];
    }
}
