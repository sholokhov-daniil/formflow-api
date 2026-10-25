<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Presentation\Http\Controller;

use App\Modules\FormDefinition\Application\Contracts\Repository\FormRepositoryInterface;
use App\Modules\FormDefinition\Infrastructure\Eloquent\Form;
use App\Modules\FormDefinition\Infrastructure\Eloquent\Project;
use App\Modules\FormDefinition\Presentation\Http\Requests\StoreFormRequest;
use App\Modules\FormDefinition\Presentation\Http\Requests\UpdateFormRequest;
use App\Modules\FormDefinition\Presentation\Http\Resources\FormDetailResource;
use App\Modules\FormDefinition\Presentation\Http\Resources\FormResource;
use App\Modules\Identity\Infrastructure\Eloquent\User;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class FormController extends Controller
{
    public function index(
        #[CurrentUser] User $user,
        Workspace $workspace,
        Project $project,
        FormRepositoryInterface $repository
    ): AnonymousResourceCollection {
        return FormResource::collection($repository->findByProjectForUser($project->id, $user->id));
    }

    public function store(
        StoreFormRequest $request,
        #[CurrentUser] User $user,
        Workspace $workspace,
        Project $project,
        FormRepositoryInterface $repository
    ): JsonResponse {
        $form = $repository->createForProject($request->toData(), $project->id, $user->id);

        return (new FormDetailResource($form))->response()->setStatusCode(201);
    }

    public function show(Workspace $workspace, Project $project, Form $form): FormDetailResource
    {
        return new FormDetailResource($form);
    }

    public function update(
        UpdateFormRequest $request,
        Workspace $workspace,
        Project $project,
        Form $form,
        FormRepositoryInterface $repository
    ): FormDetailResource {
        return new FormDetailResource($repository->update($form, $request->toData()));
    }

    public function destroy(
        Workspace $workspace,
        Project $project,
        Form $form,
        FormRepositoryInterface $repository
    ): Response {
        $repository->delete($form);

        return response()->noContent();
    }
}
