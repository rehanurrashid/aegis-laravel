<?php

declare(strict_types=1);

namespace App\Enums;

enum ComplaintCategory: string
{
    case SupportTicket = 'support_ticket';
    case Feedback      = 'feedback';
    case Complaint     = 'complaint';

    public function label(): string
    {
        return match ($this) {
            self::SupportTicket => 'Support Ticket',
            self::Feedback      => 'Feedback',
            self::Complaint     => 'Complaint',
        };
    }
}
