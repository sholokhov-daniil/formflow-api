<?php

namespace Database\Factories;

use App\Modules\Tenancy\Infrastructure\Eloquent\WorkspaceMembership;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WorkspaceMembership>
 */
#[UseModel(WorkspaceMembership::class)]
class WorkspaceMembershipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => UserFactory::new(),
            'workspace_id' => WorkspaceFactory::new(),
        ];
    }
}
