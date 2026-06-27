<?php

declare(strict_types=1);

namespace App\Enums;

enum TaskStatus: string
{
    case Pending    = 'pending';
    case InProgress = 'in_progress';
    case Complete   = 'complete';
    case Exception  = 'exception';

    public function label(): string
    {
        return match ($this) {
            self::Pending    => 'Pending',
            self::InProgress => 'In Progress',
            self::Complete   => 'Complete',
            self::Exception  => 'Exception',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending    => 'gray',
            self::InProgress => 'blue',
            self::Complete   => 'green',
            self::Exception  => 'red',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::InProgress], true);
    }
}
