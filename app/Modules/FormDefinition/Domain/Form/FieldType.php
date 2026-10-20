<?php

declare(strict_types=1);

namespace App\Modules\FormDefinition\Domain\Form;

/**
 * Доступные типы полей формы
 */
enum FieldType: string
{
    case Text = 'text';
    case Textarea = 'textarea';
    case Number = 'number';
    case Email = 'email';
    case Phone = 'phone';
    case Boolean = 'boolean';
    case Date = 'date';
    case DateTime = 'datetime';
    case Select = 'select';
    case File = 'file';
}
