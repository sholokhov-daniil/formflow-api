<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Infrastructure\Eloquent;

use App\Modules\FormDefinition\Domain\Form\FieldType;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Поле формы
 *
 * @property string $id
 * @property string $form_id
 * @property int $position
 * @property string $code
 * @property string $label
 * @property bool $active
 * @property FieldType $type
 * @property bool $required
 */
#[Fillable(['form_id', 'position', 'active', 'code', 'label', 'type', 'required'])]
final class FormField extends Model
{
    use HasUuids;

    /**
     * Возвращает форму
     *
     * @return BelongsTo
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'form_id', 'id');
    }

    /**
     * @return string[]
     */
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
