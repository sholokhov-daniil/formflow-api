<?php

declare(strict_types=1);

namespace App\Modules\Submissions\Presentation\Http\Controller;

use App\Modules\FormDefinition\Infrastructure\Eloquent\Form;
use App\Modules\FormDefinition\Infrastructure\Eloquent\Project;
use App\Modules\Identity\Infrastructure\Eloquent\User;
use App\Modules\Submissions\Application\Contracts\Repository\SubmissionRepositoryInterface;
use App\Modules\Submissions\Infrastructure\Eloquent\Submission;
use App\Modules\Submissions\Presentation\Http\Requests\StoreSubmissionRequest;
use App\Modules\Submissions\Presentation\Http\Resources\SubmissionDetailResource;
use App\Modules\Submissions\Presentation\Http\Resources\SubmissionResource;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class SubmissionController extends Controller
{
    public function index(
        #[CurrentUser] User $user,
        Workspace $workspace,
        Project $project,
        Form $form,
        SubmissionRepositoryInterface $repository
    ): AnonymousResourceCollection {
        return SubmissionResource::collection($repository->findByFormForUser($form->id, $user->id));
    }

    public function store(
        StoreSubmissionRequest $request,
        Workspace $workspace,
        Project $project,
        Form $form,
        SubmissionRepositoryInterface $repository
    ): JsonResponse {
        $submission = $repository->createForForm($form->id);

        return (new SubmissionDetailResource($submission))->response()->setStatusCode(201);
    }

    public function show(
        Workspace $workspace,
        Project $project,
        Form $form,
        Submission $submission
    ): SubmissionDetailResource {
        return new SubmissionDetailResource($submission);
    }

    public function destroy(
        Workspace $workspace,
        Project $project,
        Form $form,
        Submission $submission,
        SubmissionRepositoryInterface $repository
    ): Response {
        $repository->delete($submission);

        return response()->noContent();
    }
}
