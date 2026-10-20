<?php

namespace Database\Factories;

use App\Modules\Tenancy\Infrastructure\Eloquent\WorkspaceMembership;
use Illuminate\Database\Eloquent\Factories\Attributes\UseModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Фабрика участника пространства
 *
 * @extends Factory<WorkspaceMembership>
 */
#[UseModel(WorkspaceMembership::class)]
class WorkspaceMembershipFactory extends Factory
{
    /**
     * Возвращает данные участника пространства
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
