<?php

declare(strict_types=1);

namespace App\Modules\Submissions\Presentation\Http\Controller;

use App\Modules\FormDefinition\Infrastructure\Eloquent\Form;
use App\Modules\FormDefinition\Infrastructure\Eloquent\Project;
use App\Modules\Identity\Infrastructure\Eloquent\User;
use App\Modules\Submissions\Application\Contracts\Repository\SubmissionValueRepositoryInterface;
use App\Modules\Submissions\Infrastructure\Eloquent\Submission;
use App\Modules\Submissions\Infrastructure\Eloquent\SubmissionValue;
use App\Modules\Submissions\Presentation\Http\Requests\StoreSubmissionValueRequest;
use App\Modules\Submissions\Presentation\Http\Requests\UpdateSubmissionValueRequest;
use App\Modules\Submissions\Presentation\Http\Resources\SubmissionValueDetailResource;
use App\Modules\Submissions\Presentation\Http\Resources\SubmissionValueResource;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

class SubmissionValueController extends Controller
{
    public function index(
        #[CurrentUser] User $user,
        Workspace $workspace,
        Project $project,
        Form $form,
        Submission $submission,
        SubmissionValueRepositoryInterface $repository
    ): AnonymousResourceCollection {
        return SubmissionValueResource::collection($repository->findBySubmissionForUser($submission->id, $user->id));
    }

    public function store(
        StoreSubmissionValueRequest $request,
        Workspace $workspace,
        Project $project,
        Form $form,
        Submission $submission,
        SubmissionValueRepositoryInterface $repository
    ): JsonResponse {
        $value = $repository->createForSubmission($request->toData(), $submission->id, $submission->form_id);

        return (new SubmissionValueDetailResource($value))->response()->setStatusCode(201);
    }

    public function show(
        Workspace $workspace,
        Project $project,
        Form $form,
        Submission $submission,
        SubmissionValue $value
    ): SubmissionValueDetailResource {
        return new SubmissionValueDetailResource($value);
    }

    public function update(
        UpdateSubmissionValueRequest $request,
        Workspace $workspace,
        Project $project,
        Form $form,
        Submission $submission,
        SubmissionValue $value,
        SubmissionValueRepositoryInterface $repository
    ): SubmissionValueDetailResource {
        return new SubmissionValueDetailResource($repository->update($value, $request->toData()));
    }

    public function destroy(
        Workspace $workspace,
        Project $project,
        Form $form,
        Submission $submission,
        SubmissionValue $value,
        SubmissionValueRepositoryInterface $repository
    ): Response {
        $repository->delete($value);

        return response()->noContent();
    }
}
