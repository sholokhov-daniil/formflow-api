<?php

namespace Database\Seeders;

use App\Modules\Identity\Infrastructure\Eloquent\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Начальные данные приложения
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Создаёт тестового пользователя
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
