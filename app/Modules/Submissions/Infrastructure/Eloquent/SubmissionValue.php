<?php

declare(strict_types=1);

namespace App\Modules\Submissions\Infrastructure\Eloquent;

use App\Modules\FormDefinition\Infrastructure\Eloquent\Form;
use App\Modules\FormDefinition\Infrastructure\Eloquent\FormField;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string               $id
 * @property string               $submission_id
 * @property string               $form_id
 * @property string               $form_field_id
 * @property string|int|bool|null $value
 */
#[Fillable('submission_id', 'form_field_id', 'value', 'form_id')]
final class SubmissionValue extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(Submission::class, 'submission_id', 'id');
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'form_id', 'id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(FormField::class, 'form_field_id', 'id');
    }
}
