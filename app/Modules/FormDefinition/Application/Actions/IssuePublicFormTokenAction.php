<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Application\Actions;

use App\Modules\FormDefinition\Infrastructure\Eloquent\Form;

/** Выдаёт новый публичный bearer-токен формы и заменяет предыдущий.
 *
 * @author Daniil S.
 */
final class IssuePublicFormTokenAction
{
    /** Сохраняет хэш токена и возвращает исходное значение для однократной выдачи.
     *
     * @param  Form  $form  Форма, для которой выдаётся токен.
     * @return string Случайный 256-битный токен в шестнадцатеричном формате.
     *
     * @author Daniil S.
     */
    public function handle(Form $form): string
    {
        $token = bin2hex(random_bytes(32));

        $form->forceFill([
            'public_token_hash' => hash('sha256', $token),
        ])->save();

        return $token;
    }
}
