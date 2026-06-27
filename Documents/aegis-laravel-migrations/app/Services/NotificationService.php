<?php

declare(strict_types=1);

namespace App\Services;

use App\Jobs\SendEmailJob;
use App\Models\UserMeta;

class NotificationService
{
    /**
     * notify_* preference gate. Returns false if either the master 'notify_email'
     * or the per-module gate key is disabled.
     */
    public function shouldSend(string $userId, string $gateKey): bool
    {
        $master = $this->metaBool($userId, 'notify_email', true);
        if (!$master) return false;

        return $this->metaBool($userId, $gateKey, true);
    }

    public function send(string $userId, string $template, array $data, string $gateKey = 'notify_email'): bool
    {
        if (!$this->shouldSend($userId, $gateKey)) {
            return false;
        }
        SendEmailJob::dispatch($template, $data, $userId);
        return true;
    }

    /**
     * Map module → notify_* meta key.
     */
    public function getGateKey(string $module): string
    {
        return match ($module) {
            'plan', 'document'   => 'notify_plan',
            'vault'              => 'notify_vault',
            'incident'           => 'notify_incident',
            'steward'            => 'notify_steward',
            'payment'            => 'notify_payment',
            'message'            => 'notify_message',
            'referral'           => 'notify_referral',
            'account', 'system'  => 'notify_account',
            'summary'            => 'notify_summary',
            default              => 'notify_email',
        };
    }

    private function metaBool(string $userId, string $key, bool $default): bool
    {
        $row = UserMeta::where('user_id', $userId)
            ->where('meta_key', $key)
            ->first();
        if (!$row) return $default;
        return in_array((string) $row->meta_value, ['1', 'true', 'on', 'yes'], true);
    }
}
