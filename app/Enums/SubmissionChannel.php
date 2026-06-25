<?php

declare(strict_types=1);

namespace App\Enums;

/** complaints.submission_channel — stored as VARCHAR(40) default 'ticket'. */
enum SubmissionChannel: string
{
    case Ticket                  = 'ticket';
    case FeedbackButton          = 'feedback_button';
    case ContextualQuestionnaire = 'contextual_questionnaire';
    case FreeForm                = 'free_form';

    public function label(): string
    {
        return match ($this) {
            self::Ticket                  => 'Help Ticket',
            self::FeedbackButton          => 'Feedback Button',
            self::ContextualQuestionnaire => 'Contextual Questionnaire',
            self::FreeForm                => 'Free-form Submission',
        };
    }
}
