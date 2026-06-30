<?php

declare(strict_types=1);

namespace App\Enums;

enum ComplaintCategory: string
{
    case SupportTicket  = 'support_ticket';
    case Feedback       = 'feedback';
    case Complaint      = 'complaint';
    case Bug            = 'bug';
    case FeatureRequest = 'feature_request';
    case Billing        = 'billing';
    case Other          = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SupportTicket  => 'Support Ticket',
            self::Feedback       => 'Feedback',
            self::Complaint      => 'Complaint',
            self::Bug            => 'Bug Report',
            self::FeatureRequest => 'Feature Request',
            self::Billing        => 'Billing',
            self::Other          => 'Other',
        };
    }
}
