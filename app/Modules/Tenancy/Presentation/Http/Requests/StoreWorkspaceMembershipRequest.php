<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Presentation\Http\Requests;

use App\Modules\Identity\Infrastructure\Eloquent\User;
use App\Modules\Tenancy\Application\DTO\CreateWorkspaceMembershipData;
use App\Modules\Tenancy\Infrastructure\Eloquent\WorkspaceMembership;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreWorkspaceMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $workspace = $this->route('workspace');

        return [
            'user_id' => ['required', 'integer', Rule::exists(User::class, 'id'), Rule::unique(WorkspaceMembership::class, 'user_id')->where('workspace_id', $workspace->id)],
        ];
    }

    /** @return array<array-key, mixed> */
    public function validationData(): array
    {
        return $this->isJson() ? $this->json()->all() : [];
    }

    public function toData(): CreateWorkspaceMembershipData
    {
        $validated = $this->validated();

        return new CreateWorkspaceMembershipData(
            userId: (int) $validated['user_id'],
        );
    }
}
