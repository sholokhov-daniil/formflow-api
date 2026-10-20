<?php

namespace Database\Factories;

use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Workspace>
 */
#[UseModel(Workspace::class)]
class WorkspaceFactory extends Factory
{
    /**
     * Define the model's default state.
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

    public function personal(): static
    {
        return $this->state(['kind' => 'personal']);
    }
}
