<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблица участников пространств
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspace_memberships', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignUuid('workspace_id')
                ->constrained('workspaces')
                ->cascadeOnDelete();

            $table->unique(['user_id', 'workspace_id'], 'workspace_memberships_user_workspace_unique');

            $table->index('workspace_id', 'workspace_memberships_workspace_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workspace_memberships');
    }
};
