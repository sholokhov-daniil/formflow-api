<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Presentation\Http\Requests;

use App\Modules\FormDefinition\Application\DTO\UpdateProjectData;
use App\Modules\FormDefinition\Infrastructure\Eloquent\Project;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Validator;

final class UpdateProjectRequest extends FormRequest
{
    /**
     * Право обновления проверяет middleware маршрута.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array{name: array<int, string|Unique>, description: array<int, string>}
     */
    public function rules(): array
    {
        /** @var Workspace $workspace */
        $workspace = $this->route('workspace');
        /** @var Project $project */
        $project = $this->route('project');
        $presenceRules = $this->isMethod('PUT') ? ['required'] : ['sometimes', 'required'];

        return [
            'name' => [...$presenceRules, 'string', 'max:255', Rule::unique(Project::class, 'name')->where('workspace_id', $workspace->id)->ignore($project)],
            'description' => [...$presenceRules, 'string', 'max:255'],
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    public function validationData(): array
    {
        return $this->isJson() ? $this->json()->all() : [];
    }

    /**
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $data = $this->validationData();

                if (
                    $this->isMethod('PATCH')
                    && $validator->errors()->count() === 0
                    && ! array_key_exists('name', $data)
                    && ! array_key_exists('description', $data)
                ) {
                    $validator->errors()->add('name', 'Provide at least one field to update.');
                }
            },
        ];
    }

    public function toData(): UpdateProjectData
    {
        $validated = $this->validated();

        return new UpdateProjectData(
            name: array_key_exists('name', $validated) ? (string) $validated['name'] : null,
            description: array_key_exists('description', $validated) ? (string) $validated['description'] : null,
        );
    }
}
