<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('description');
            $table->timestamps();

            $table->foreignUuid('workspace_id')
                ->constrained('workspaces')
                ->cascadeOnDelete();

            $table->unique(['workspace_id', 'name'], 'projects_workspace_project_name_unique');
            $table->index('workspace_id', 'projects_workspace_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
