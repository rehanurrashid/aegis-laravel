<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivityEventType: string
{
    case Message                          = 'message';
    case Task                             = 'task';
    case Document                         = 'document';
    case Incident                         = 'incident';
    case Vault                            = 'vault';
    case Compliance                       = 'compliance';
    case Attestation                      = 'attestation';
    case Payment                          = 'payment';
    case Account                          = 'account';
    case System                           = 'system';
    case Referral                         = 'referral';
    case News                             = 'news';
    case Event                            = 'event';
    case PractitionerUnresponsiveFlagged  = 'practitioner_unresponsive_flagged';
    case JobPostings                      = 'job_postings';
    case Support                          = 'support';

    public function label(): string
    {
        return match ($this) {
            self::Message                         => 'Message',
            self::Task                            => 'Task',
            self::Document                        => 'Document',
            self::Incident                        => 'Incident',
            self::Vault                           => 'Vault',
            self::Compliance                      => 'Compliance',
            self::Attestation                     => 'Attestation',
            self::Payment                         => 'Payment',
            self::Account                         => 'Account',
            self::System                          => 'System',
            self::Referral                        => 'Referral',
            self::News                            => 'News',
            self::Event                           => 'Event',
            self::PractitionerUnresponsiveFlagged => 'Practitioner Flagged Unresponsive',
            self::JobPostings                     => 'Job Posting',
            self::Support                         => 'Support',
        };
    }
}
