<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Presentation\Http\Controller;

use App\Modules\FormDefinition\Application\Contracts\Repository\FormFieldRepositoryInterface;
use App\Modules\FormDefinition\Infrastructure\Eloquent\Form;
use App\Modules\FormDefinition\Infrastructure\Eloquent\FormField;
use App\Modules\FormDefinition\Infrastructure\Eloquent\Project;
use App\Modules\FormDefinition\Presentation\Http\Requests\StoreFormFieldRequest;
use App\Modules\FormDefinition\Presentation\Http\Requests\UpdateFormFieldRequest;
use App\Modules\FormDefinition\Presentation\Http\Resources\FormFieldDetailResource;
use App\Modules\FormDefinition\Presentation\Http\Resources\FormFieldResource;
use App\Modules\Identity\Infrastructure\Eloquent\User;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class FormFieldController extends Controller
{
    public function index(
        #[CurrentUser] User $user,
        Workspace $workspace,
        Project $project,
        Form $form,
        FormFieldRepositoryInterface $repository
    ): AnonymousResourceCollection {
        return FormFieldResource::collection($repository->findByFormForUser($form->id, $user->id));
    }

    public function store(
        StoreFormFieldRequest $request,
        Workspace $workspace,
        Project $project,
        Form $form,
        FormFieldRepositoryInterface $repository
    ): JsonResponse {
        $field = $repository->createForForm($request->toData(), $form->id);

        return (new FormFieldDetailResource($field))->response()->setStatusCode(201);
    }

    public function show(
        Workspace $workspace,
        Project $project,
        Form $form,
        FormField $field
    ): FormFieldDetailResource {
        return new FormFieldDetailResource($field);
    }

    public function update(
        UpdateFormFieldRequest $request,
        Workspace $workspace,
        Project $project,
        Form $form,
        FormField $field,
        FormFieldRepositoryInterface $repository
    ): FormFieldDetailResource {
        return new FormFieldDetailResource($repository->update($field, $request->toData()));
    }

    public function destroy(
        Workspace $workspace,
        Project $project,
        Form $form,
        FormField $field,
        FormFieldRepositoryInterface $repository
    ): Response {
        $repository->delete($field);

        return response()->noContent();
    }
}
