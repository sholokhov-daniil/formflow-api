<?php

declare(strict_types=1);

namespace App\Modules\Submissions\Infrastructure\Eloquent;

use App\Modules\FormDefinition\Infrastructure\Eloquent\Form;
use DateTime;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Заполнение формы
 *
 * @property string $id
 * @property string $form_id
 * @property DateTime|null $create_at
 * @property DateTime|null $updated_at
 */
#[Fillable('form_id', 'created_at', 'updated_at')]
final class Submission extends Model
{
    use HasUuids;

    /**
     * Возвращает форму
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'form_id', 'id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(SubmissionValue::class, 'submission_id', 'id');
    }
}
