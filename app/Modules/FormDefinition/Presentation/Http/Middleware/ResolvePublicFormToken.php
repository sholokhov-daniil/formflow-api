<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Presentation\Http\Middleware;

use App\Modules\FormDefinition\Infrastructure\Eloquent\Form;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Разрешает Bearer-токен в активную форму для публичных запросов.
 *
 * @author Daniil S.
 */
final class ResolvePublicFormToken
{
    /** Отклоняет невалидный токен без раскрытия состояния формы.
     *
     * @param  Request  $request  Входящий HTTP-запрос.
     * @param  Closure  $next  Следующий обработчик middleware.
     * @return Response HTTP-ответ следующего обработчика или 404.
     *
     * @author Daniil S.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $authorization = $request->header('Authorization', '');

        if (! preg_match('/^Bearer ([a-f0-9]{64})$/D', $authorization, $matches)) {
            return response()->json(['message' => 'The public form is unavailable.'], 404);
        }

        $form = Form::query()
            ->where('public_token_hash', hash('sha256', $matches[1]))
            ->where('active', true)
            ->first();

        if ($form === null) {
            return response()->json(['message' => 'The public form is unavailable.'], 404);
        }

        $request->attributes->set('publicForm', $form);

        return $next($request);
    }
}
