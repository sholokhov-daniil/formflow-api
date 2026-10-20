<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 100);
            $table->boolean('active')->default(false);
            $table->integer('position');
            $table->string('label');
            $table->string('type', 32);
            $table->boolean('required')->default(false);
            $table->timestamps();

            $table->foreignUuid('form_id')
                ->constrained('forms')
                ->cascadeOnDelete();

            $table->unique(['id', 'form_id']);
            $table->unique(['form_id', 'code'], 'form_fields_code_unique');
            $table->index(['code'], 'form_fields_code_index');
        });

        DB::statement("
            ALTER TABLE form_fields
            ADD CONSTRAINT form_fields_type_check
            CHECK (type IN (
                'text',
                'textarea',
                'number',
                'boolean',
                'file',
                'email',
                'phone',
                'date',
                'datetime',
                'select'
                )
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};
