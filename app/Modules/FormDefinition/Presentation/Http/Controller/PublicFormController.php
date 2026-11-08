<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Presentation\Http\Controller;

use App\Modules\FormDefinition\Infrastructure\Eloquent\Form;
use App\Modules\FormDefinition\Presentation\Http\Resources\PublicFormSchemaResource;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/** Обслуживает публичное чтение схемы формы.
 *
 * @author Daniil S.
 */
final class PublicFormController extends Controller
{
    /** Возвращает схему формы, разрешённой публичным токеном.
     *
     * @param  Request  $request  Запрос с формой, установленной токен middleware.
     * @return PublicFormSchemaResource Публичная схема формы.
     *
     * @throws NotFoundHttpException Если middleware не установил форму.
     *
     * @author Daniil S.
     */
    public function schema(Request $request): PublicFormSchemaResource
    {
        $form = $request->attributes->get('publicForm');

        if (! $form instanceof Form) {
            throw new NotFoundHttpException('The public form is unavailable.');
        }

        return new PublicFormSchemaResource($form->load('fields'));
    }
}
