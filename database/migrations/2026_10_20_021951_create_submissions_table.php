<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();

            $table->foreignUuid('form_id')
                ->constrained('forms')
                ->onDelete('cascade');

            $table->unique(['id', 'form_id']);

            $table->index('form_id', 'submissions_form_id_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
