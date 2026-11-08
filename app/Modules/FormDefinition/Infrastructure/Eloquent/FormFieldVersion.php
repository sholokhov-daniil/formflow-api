<?php

namespace App\Modules\FormDefinition\Infrastructure\Eloquent;

use App\Modules\FormDefinition\Domain\Form\FieldType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['form_field_id', 'form_id', 'code', 'label', 'position', 'type', 'active', 'required'])]
final class FormFieldVersion extends Model
{
    use HasUuids;

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'form_id', 'id');
    }

    protected function casts(): array
    {
        return [
            'type' => FieldType::class,
            'active' => 'boolean',
            'required' => 'boolean',
            'position' => 'integer',
        ];
    }
}
