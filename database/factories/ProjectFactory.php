<?php

namespace Database\Factories;

use App\Modules\FormDefinition\Infrastructure\Eloquent\Project;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
#[UseModel(Project::class)]
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'workspace_id' => WorkspaceFactory::new(),
            'name' => fake()->unique()->bothify('Project ????####'),
            'description' => fake()->sentence(),
        ];
    }
}
