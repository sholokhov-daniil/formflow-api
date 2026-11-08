<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Presentation\Http\Controller;

use App\Modules\FormDefinition\Application\Actions\IssuePublicFormTokenAction;
use App\Modules\FormDefinition\Infrastructure\Eloquent\Form;
use App\Modules\FormDefinition\Infrastructure\Eloquent\Project;
use App\Modules\Tenancy\Infrastructure\Eloquent\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

/** Управляет публичным токеном формы от имени участника workspace.
 *
 * @author Daniil S.
 */
final class PublicFormTokenController extends Controller
{
    /** Выпускает новый токен и возвращает его без кеширования.
     *
     * @param  Workspace  $workspace  Рабочее пространство из маршрута.
     * @param  Project  $project  Проект из маршрута.
     * @param  Form  $form  Форма из маршрута.
     * @param  IssuePublicFormTokenAction  $issueToken  Операция выпуска токена.
     * @return JsonResponse Ответ с новым токеном.
     *
     * @author Daniil S.
     */
    public function store(
        Workspace $workspace,
        Project $project,
        Form $form,
        IssuePublicFormTokenAction $issueToken,
    ): JsonResponse {
        $response = response()->json([
            'data' => [
                'form_id' => $form->id,
                'token' => $issueToken->handle($form),
            ],
        ], 201);

        $response->headers->set('Cache-Control', 'no-store');

        return $response;
    }

    /** Отзывает действующий токен формы, если он существует.
     *
     * @param  Workspace  $workspace  Рабочее пространство из маршрута.
     * @param  Project  $project  Проект из маршрута.
     * @param  Form  $form  Форма из маршрута.
     * @return Response Ответ без тела.
     *
     * @author Daniil S.
     */
    public function destroy(Workspace $workspace, Project $project, Form $form): Response
    {
        $form->forceFill(['public_token_hash' => null])->save();

        return response()->noContent();
    }
}
