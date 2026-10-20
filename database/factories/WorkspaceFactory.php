<?php

namespace Database\Factories;

use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика пространства проектов
 *
 * @extends Factory<Workspace>
 */
#[UseModel(Workspace::class)]
class WorkspaceFactory extends Factory
{
    /**
     * Возвращает данные пространства проектов
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'kind' => 'team',
        ];
    }

    /**
     * Задаёт личное пространство
     */
    public function personal(): static
    {
        return $this->state(['kind' => 'personal']);
    }
}
