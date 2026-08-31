<?php

declare(strict_types=1);

namespace Silao\Domain\Model\Enum;

enum FieldType: string
{
    case Text = 'text';
    case Email = 'email';
    case Phone = 'phone';
    case Number = 'number';
    case Date = 'date';
    case Time = 'time';
    case DateTime = 'datetime';
    case Select = 'select';
    case Radio = 'radio';
    case Checkbox = 'checkbox';
    case Textarea = 'textarea';
    case Location = 'location';
    case Resource = 'resource';
}