<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблица пространств проектов
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->text('name');
            $table->text('kind')->default('team');
            $table->timestamps();
        });

        DB::statement("
            ALTER TABLE workspaces
            ADD CONSTRAINT workspaces_kind_check
            CHECK (kind IN ('personal', 'team'))
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('workspaces');
    }
};
