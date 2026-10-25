<?php

declare(strict_types=1);

namespace App\Modules\Tenancy\Presentation\Http\Controller;

use App\Modules\Identity\Infrastructure\Eloquent\User;
use App\Modules\Tenancy\Application\Contracts\Repository\WorkspaceMembershipRepositoryInterface;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use App\Modules\Tenancy\Infrastructure\Eloquent\WorkspaceMembership;
use App\Modules\Tenancy\Presentation\Http\Requests\StoreWorkspaceMembershipRequest;
use App\Modules\Tenancy\Presentation\Http\Requests\UpdateWorkspaceMembershipRequest;
use App\Modules\Tenancy\Presentation\Http\Resources\WorkspaceMembershipDetailResource;
use App\Modules\Tenancy\Presentation\Http\Resources\WorkspaceMembershipResource;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class WorkspaceMembershipController extends Controller
{
    public function index(
        #[CurrentUser] User $user,
        Workspace $workspace,
        WorkspaceMembershipRepositoryInterface $repository
    ): AnonymousResourceCollection {
        return WorkspaceMembershipResource::collection($repository->findByWorkspaceForUser($workspace->id, $user->id));
    }

    public function store(
        StoreWorkspaceMembershipRequest $request,
        Workspace $workspace,
        WorkspaceMembershipRepositoryInterface $repository
    ): JsonResponse {
        $membership = $repository->createForWorkspace($request->toData(), $workspace->id);

        return (new WorkspaceMembershipDetailResource($membership))->response()->setStatusCode(201);
    }

    public function show(
        Workspace $workspace,
        WorkspaceMembership $membership
    ): WorkspaceMembershipDetailResource {
        return new WorkspaceMembershipDetailResource($membership);
    }

    public function update(
        UpdateWorkspaceMembershipRequest $request,
        Workspace $workspace,
        WorkspaceMembership $membership,
        WorkspaceMembershipRepositoryInterface $repository
    ): WorkspaceMembershipDetailResource {
        return new WorkspaceMembershipDetailResource($repository->update($membership, $request->toData()));
    }

    public function destroy(
        Workspace $workspace,
        WorkspaceMembership $membership,
        WorkspaceMembershipRepositoryInterface $repository
    ): Response {
        $repository->delete($membership);

        return response()->noContent();
    }
}
