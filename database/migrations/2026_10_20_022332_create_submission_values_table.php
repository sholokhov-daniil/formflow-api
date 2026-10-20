<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Таблица ответов на поля форм
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_values', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('form_id');
            $table->uuid('submission_id');
            $table->uuid('form_field_id');
            $table->jsonb('value')->nullable();
            $table->timestamps();

            $table->foreign(['submission_id', 'form_id'])
                ->references(['id', 'form_id'])
                ->on('submissions')
                ->cascadeOnDelete();

            $table->foreign(['form_field_id', 'form_id'])
                ->references(['id', 'form_id'])
                ->on('form_fields')
                ->cascadeOnDelete();

            $table->unique(['id', 'form_id']);
            $table->unique(['submission_id', 'form_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_values');
    }
};
