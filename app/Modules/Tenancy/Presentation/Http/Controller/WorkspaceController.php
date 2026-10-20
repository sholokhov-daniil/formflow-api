<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Presentation\Http\Controller;

use App\Modules\Identity\Infrastructure\Eloquent\User;
use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceRepositoryInterface;
use App\Modules\Tenancy\Presentation\Http\Resources\WorkspaceResource;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class WorkspaceController extends Controller
{
    /**
     * Возвращает все доступные пространства проектов текущего пользователя
     */
    public function index(
        #[CurrentUser] User $user,
        WorkspaceRepositoryInterface $workspaces
    ): AnonymousResourceCollection {
        return WorkspaceResource::collection(
            $workspaces->accessibleToUser($user->id)
        );
    }

    public function store() {}

    public function show() {}

    public function update() {}

    public function destroy() {}
}
