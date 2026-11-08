<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Infrastructure\Observers;

use App\Modules\FormDefinition\Infrastructure\Eloquent\FormField;
use App\Modules\FormDefinition\Infrastructure\Eloquent\FormFieldVersion;
use Illuminate\Support\Facades\DB;

final class FormFieldObserver
{
    public function created(FormField $field): void
    {
        $this->createVersion($field);
    }

    public function updated(FormField $field): void
    {
        if ($field->wasChanged(['code', 'label', 'position', 'type', 'active', 'required'])) {
            $this->createVersion($field);
        }
    }

    private function createVersion(FormField $field): void
    {
        DB::transaction(function () use ($field): void {
            $version = FormFieldVersion::query()->create([
                'form_field_id' => $field->id,
                'form_id' => $field->form_id,
                'code' => $field->code,
                'label' => $field->label,
                'position' => $field->position,
                'type' => $field->type,
                'active' => $field->active,
                'required' => $field->required,
            ]);

            FormField::query()->whereKey($field->id)->update(['current_version_id' => $version->id]);
            $field->current_version_id = $version->id;
        });
    }
}
