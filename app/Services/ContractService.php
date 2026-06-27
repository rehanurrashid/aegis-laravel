<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\Business\ContractCancelled;

use App\Enums\ActivitySeverity;
use App\Events\Business\ContractSigned;
use App\Models\BpContract;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class ContractService
{
    public function __construct(private ActivityService $activity) {}

    public function create(string $jobId, string $proposalId, string $practitionerId, string $bpId, int $totalCents): BpContract
    {
        return BpContract::create([
            'id'              => 'bc_' . Str::lower(Str::random(12)),
            'job_id'          => $jobId,
            'proposal_id'     => $proposalId,
            'practitioner_id' => $practitionerId,
            'bp_id'           => $bpId,
            'total_cents'     => $totalCents,
            'status'          => 'active',
            'created_at'      => now(),
        ]);
    }

    public function sign(BpContract $contract, User $signer, array $signature): BpContract
    {
        $isPractitioner = $contract->practitioner_id === $signer->id;
        $isBp = $contract->bp_id === $signer->id;

        $update = [];
        if ($isPractitioner) {
            $update['practitioner_signed_at'] = now();
            $update['practitioner_signature_name'] = $signature['name'] ?? $signer->display_name;
        } elseif ($isBp) {
            $update['bp_signed_at'] = now();
            $update['bp_signature_name'] = $signature['name'] ?? $signer->display_name;
        }
        $contract->update($update);

        // Both signed → mark fully executed
        $fresh = $contract->fresh();
        if ($fresh->practitioner_signed_at && $fresh->bp_signed_at) {
            $fresh->update(['fully_executed_at' => now()]);
            event(new ContractSigned($fresh));
        }

        $otherId = $isPractitioner ? $contract->bp_id : $contract->practitioner_id;
        $otherPortal = $isPractitioner ? 'business_partner' : 'provider';
        $this->activity->log(
            $otherId, $otherPortal, 'payment', ActivitySeverity::Info,
            'contract_signed',
            "{$signer->display_name} signed the contract",
            $fresh->fully_executed_at ? 'Contract is now fully executed.' : 'Awaiting your signature.',
            'bp_contract', $contract->id, $signer->id
        );

        return $fresh;
    }

    public function cancel(BpContract $contract, User $actor, ?string $reason = null): BpContract
    {
        $contract->update([
            'status'      => 'cancelled',
            'cancelled_at'=> now(),
            'cancel_reason'=> $reason,
        ]);

        $otherId = $contract->practitioner_id === $actor->id
            ? $contract->bp_id
            : $contract->practitioner_id;
        $otherUser = User::find($otherId);

        $this->activity->log(
            $otherId,
            $otherUser?->role === 'business_partner' ? 'business_partner' : 'provider',
            'payment',
            ActivitySeverity::Warning,
            'contract_cancelled',
            "{$actor->display_name} cancelled the contract",
            $reason ?? 'No reason given.',
            'bp_contract', $contract->id, $actor->id
        );

        event(new ContractCancelled($contract->fresh(), $actor, $reason));

        return $contract->fresh();
    }

    public function pause(BpContract $contract): BpContract
    {
        $contract->update(['status' => 'paused', 'paused_at' => now()]);
        return $contract->fresh();
    }

    public function resume(BpContract $contract): BpContract
    {
        $contract->update(['status' => 'active', 'resumed_at' => now()]);
        return $contract->fresh();
    }

    public function complete(BpContract $contract): BpContract
    {
        $contract->update(['status' => 'completed', 'completed_at' => now()]);
        return $contract->fresh();
    }

    public function getForPractitioner(string $practitionerId): Collection
    {
        return BpContract::where('practitioner_id', $practitionerId)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getForBp(string $bpId): Collection
    {
        return BpContract::where('bp_id', $bpId)
            ->orderByDesc('created_at')
            ->get();
    }
}
