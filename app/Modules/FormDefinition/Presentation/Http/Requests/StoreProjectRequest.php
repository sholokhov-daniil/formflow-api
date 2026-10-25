<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Presentation\Http\Requests;

use App\Modules\FormDefinition\Application\DTO\CreateProjectData;
use App\Modules\FormDefinition\Infrastructure\Eloquent\Project;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

final class StoreProjectRequest extends FormRequest
{
    /**
     * Право создания проверяет middleware маршрута.
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

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique(Project::class, 'name')->where('workspace_id', $workspace->id)],
            'description' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<array-key, mixed>
     */
    public function validationData(): array
    {
        return $this->isJson() ? $this->json()->all() : [];
    }

    public function toData(): CreateProjectData
    {
        $validated = $this->validated();

        return new CreateProjectData(
            name: (string) $validated['name'],
            description: (string) $validated['description'],
        );
    }
}
