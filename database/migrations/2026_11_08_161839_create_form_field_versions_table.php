<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_field_versions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('form_field_id');
            $table->uuid('form_id');
            $table->string('code', 100);
            $table->string('label');
            $table->integer('position');
            $table->string('type', 32);
            $table->boolean('active');
            $table->boolean('required');
            $table->timestamps();

            $table->foreign('form_id')->references('id')->on('forms')->cascadeOnDelete();
            $table->unique(['id', 'form_id']);
            $table->index(['form_field_id', 'form_id']);
        });

        Schema::table('form_fields', function (Blueprint $table): void {
            $table->uuid('current_version_id')->nullable();
        });

        foreach (DB::table('form_fields')->get() as $field) {
            $versionId = (string) Str::uuid();
            DB::table('form_field_versions')->insert([
                'id' => $versionId,
                'form_field_id' => $field->id,
                'form_id' => $field->form_id,
                'code' => $field->code,
                'label' => $field->label,
                'position' => $field->position,
                'type' => $field->type,
                'active' => $field->active,
                'required' => $field->required,
                'created_at' => $field->created_at,
                'updated_at' => $field->updated_at,
            ]);
            DB::table('form_fields')->where('id', $field->id)->update(['current_version_id' => $versionId]);
        }

        Schema::table('form_fields', function (Blueprint $table): void {
            $table->foreign('current_version_id')->references('id')->on('form_field_versions')->nullOnDelete();
        });

        Schema::table('submission_values', function (Blueprint $table): void {
            $table->uuid('form_field_version_id')->nullable();
        });

        DB::statement('UPDATE submission_values SET form_field_version_id = form_fields.current_version_id FROM form_fields WHERE submission_values.form_field_id = form_fields.id AND submission_values.form_id = form_fields.form_id');

        Schema::table('submission_values', function (Blueprint $table): void {
            $table->uuid('form_field_version_id')->nullable(false)->change();
            $table->foreign(['form_field_version_id', 'form_id'])
                ->references(['id', 'form_id'])
                ->on('form_field_versions')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('submission_values', function (Blueprint $table): void {
            $table->dropForeign(['form_field_version_id', 'form_id']);
            $table->dropColumn('form_field_version_id');
        });

        Schema::table('form_fields', function (Blueprint $table): void {
            $table->dropForeign(['current_version_id']);
            $table->dropColumn('current_version_id');
        });

        Schema::dropIfExists('form_field_versions');
    }
};
