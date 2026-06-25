<?php

declare(strict_types=1);

namespace App\Enums;

enum MetaType: string
{
    case String    = 'string';
    case Int       = 'int';
    case Boolean   = 'boolean';
    case Json      = 'json';
    case Timestamp = 'timestamp';

    public function label(): string
    {
        return match ($this) {
            self::String    => 'String',
            self::Int       => 'Integer',
            self::Boolean   => 'Boolean',
            self::Json      => 'JSON',
            self::Timestamp => 'Timestamp',
        };
    }
}
