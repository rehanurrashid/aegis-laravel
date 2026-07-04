<?php
declare(strict_types=1);
namespace App\Events\Network;
use App\Events\AegisEvent;
use App\Models\NetworkRequest;
use App\Models\User;
class ConnectionRequestSent extends AegisEvent
{
    public function __construct(
        public readonly NetworkRequest $networkRequest,
        public readonly User $requester,
        public readonly User $recipient,
    ) {}
}
