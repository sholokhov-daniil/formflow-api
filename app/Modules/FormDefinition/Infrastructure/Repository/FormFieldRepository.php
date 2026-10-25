<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Infrastructure\Repository;

use App\Modules\FormDefinition\Application\Contracts\Repository\FormFieldRepositoryInterface;
use App\Modules\FormDefinition\Application\DTO\CreateFormFieldData;
use App\Modules\FormDefinition\Application\DTO\UpdateFormFieldData;
use App\Modules\FormDefinition\Infrastructure\Eloquent\FormField;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class FormFieldRepository implements FormFieldRepositoryInterface
{
    public function findByFormForUser(string $formId, int $userId): Collection
    {
        return FormField::query()
            ->where('form_id', $formId)
            ->whereHas('form.project.workspace.memberships', static fn (Builder $query): Builder => $query->where('user_id', $userId))
            ->orderBy('position')
            ->orderBy('id')
            ->get();
    }

    public function createForForm(CreateFormFieldData $data, string $formId): FormField
    {
        return FormField::create([
            'form_id' => $formId,
            'code' => $data->code,
            'label' => $data->label,
            'position' => $data->position,
            'type' => $data->type,
            'active' => $data->active,
            'required' => $data->required,
        ]);
    }

    public function update(FormField $field, UpdateFormFieldData $data): FormField
    {
        if ($data->code !== null) {
            $field->code = $data->code;
        }

        if ($data->label !== null) {
            $field->label = $data->label;
        }

        if ($data->position !== null) {
            $field->position = $data->position;
        }

        if ($data->type !== null) {
            $field->type = $data->type;
        }

        if ($data->active !== null) {
            $field->active = $data->active;
        }

        if ($data->required !== null) {
            $field->required = $data->required;
        }

        $field->save();

        return $field;
    }

    public function delete(FormField $field): void
    {
        $field->delete();
    }
}
