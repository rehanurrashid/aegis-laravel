<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ActivitySeverity;
use App\Models\Service;
use App\Models\ServiceRequest;
use App\Models\ServiceSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use RuntimeException;

class ServiceService
{
    public function __construct(private ActivityService $activity) {}

    public function create(User $practitioner, array $data): Service
    {
        if ($practitioner->tier !== 'practice' || !$practitioner->services_mode) {
            throw new RuntimeException('Services Mode requires the Practice tier.');
        }

        return Service::create([
            'id'              => 'ps_' . Str::lower(Str::random(12)),
            'practitioner_id' => $practitioner->id,
            'title'           => $data['title'],
            'description'     => $data['description'] ?? null,
            'category'        => $data['category'] ?? null,
            'price_cents'     => $data['price_cents'] ?? 0,
            
            'status'          => 'active',
            'created_at'      => now(),
        ]);
    }

    public function update(Service $service, array $data): Service
    {
        $allowed = ['title', 'description', 'category', 'price_cents', 'price_type', 'status'];
        $service->update(array_intersect_key($data, array_flip($allowed)));
        return $service->fresh();
    }

    public function activate(Service $service): Service
    {
        $service->update(['status' => 'active']);
        return $service->fresh();
    }

    public function deactivate(Service $service): Service
    {
        $service->update(['status' => 'inactive']);
        return $service->fresh();
    }

    public function archive(Service $service): Service
    {
        $service->update(['status' => 'archived']);
        return $service->fresh();
    }

    public function submitRequest(Service $service, User $requester, array $data): ServiceRequest
    {
        if ($service->status !== 'active') {
            throw new RuntimeException('This service is not currently available.');
        }
        if ($service->practitioner_id === $requester->id) {
            throw new RuntimeException('Cannot request your own service.');
        }

        $req = ServiceRequest::create([
            'id'              => 'sr_' . Str::lower(Str::random(12)),
            'service_id'      => $service->id,
            'inquirer_id'     => $requester->id,
            'practitioner_id' => $service->practitioner_id,
            'message'         => $data['message'] ?? null,
            'status'          => 'new',
            'created_at'      => now(),
        ]);

        $this->activity->log(
            $service->practitioner_id, 'provider', 'referral', ActivitySeverity::Info,
            'service_request_received',
            "{$requester->display_name} requested your service: {$service->title}",
            $data['message'] ?? 'Tap to review.',
            'service_request', $req->id, $requester->id
        );

        return $req;
    }

    public function acceptRequest(ServiceRequest $req): ServiceRequest
    {
        $req->update(['status' => 'accepted', 'responded_at' => now()]);
        $requester = User::find($req->inquirer_id);
        $service = Service::find($req->service_id);

        $this->activity->log(
            $req->inquirer_id, 'provider', 'referral', ActivitySeverity::Info,
            'service_request_accepted',
            "Your service request was accepted",
            "{$service->title}",
            'service_request', $req->id, $req->practitioner_id
        );

        return $req->fresh();
    }

    public function declineRequest(ServiceRequest $req, ?string $reason = null): ServiceRequest
    {
        $req->update([
            'status' => 'declined',
            'responded_at' => now(),
            'response_note' => $reason,
        ]);

        $this->activity->log(
            $req->inquirer_id, 'provider', 'referral', ActivitySeverity::Info,
            'service_request_declined',
            "Your service request was declined",
            $reason ?? 'No reason given.',
            'service_request', $req->id, $req->practitioner_id
        );

        return $req->fresh();
    }

    public function bookSession(ServiceRequest $req, array $data): ServiceSession
    {
        return ServiceSession::create([
            'id'                 => 'ss_' . Str::lower(Str::random(12)),
            'service_request_id' => $req->id,
            'service_id'         => $req->service_id,
            'practitioner_id'    => $req->practitioner_id,
            'client_id'          => $req->inquirer_id,
            'scheduled_at'       => $data['session_at'] ?? $data['scheduled_at'] ?? null,
            'status'             => 'scheduled',
            'created_at'         => now(),
        ]);
    }

    public function getForPractitioner(string $practitionerId): Collection
    {
        return Service::where('practitioner_id', $practitionerId)
            ->whereIn('status', ['active', 'inactive'])
            ->get();
    }

    public function findProviders(array $filters = []): Collection
    {
        $query = Service::where('status', 'active');
        if (!empty($filters['category'])) $query->where('category', $filters['category']);
        if (!empty($filters['max_price_cents'])) $query->where('price_cents', '<=', $filters['max_price_cents']);

        return $query->limit($filters['limit'] ?? 50)->get();
    }
}
