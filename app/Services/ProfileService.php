<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\NetworkConnection;
use App\Models\User;
use App\Models\Referral;
use Illuminate\Support\Str;

use App\Enums\ActivitySeverity;
use App\Services\ActivityService;

class ProfileService
{
    public function __construct(private ActivityService $activity) {}

    public function updateBasic(User $user, array $data): User
    {
        $allowed = ['display_name', 'phone', 'title', 'organization', 'location', 'bio', 'about_me', 'avatar_initials'];
        $user->update(array_intersect_key($data, array_flip($allowed)));

        $this->activity->log(
            $user->id, $user->role?->portal() ?? 'provider',
            'account', ActivitySeverity::Info,
            'profile_updated', 'Profile updated',
            'You updated your basic profile information.',
            null, null, null, 'log', $user->id,
        );

        return $user->fresh();
    }

    public function updateAvatar(User $user, \Illuminate\Http\UploadedFile $file): User
    {
        if ($user->avatar_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar_path);
        }
        $path = $file->store('avatars/' . $user->id, 'public');
        $user->update(['avatar_path' => $path]);
        return $user->fresh();
    }

    public function removeAvatar(User $user): User
    {
        if ($user->avatar_path) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($user->avatar_path);
        }
        $user->update(['avatar_path' => null]);
        return $user->fresh();
    }

    public function updateSpecialties(User $user, array $specialties): User
    {
        $this->setMeta($user, 'specialties', $specialties);
        return $user->fresh();
    }

    /**
     * Upsert a single user_meta row, typing the value correctly.
     */
    private function setMeta(User $user, string $key, mixed $value, string $type = 'json'): void
    {
        $encoded = $type === 'json' ? json_encode($value) : (string) $value;

        $existing = $user->meta()->where('meta_key', $key)->first();
        if ($existing) {
            $existing->update(['meta_value' => $encoded, 'meta_type' => $type]);
        } else {
            $user->meta()->create([
                'id'         => 'um_' . \Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(12)),
                'meta_key'   => $key,
                'meta_value' => $encoded,
                'meta_type'  => $type,
            ]);
        }
    }

    /**
     * Public alias for setMeta — used by NetworkController config save/reset.
     */
    public function setMetaPublic(User $user, string $key, mixed $value, string $type = 'json'): void
    {
        $this->setMeta($user, $key, $value, $type);
    }

    public function updateServices(User $user, array $services): User
    {
        $this->setMeta($user, 'services', $services);
        return $user->fresh();
    }

    public function updateApproaches(User $user, array $approaches): User
    {
        $this->setMeta($user, 'approaches', $approaches);
        return $user->fresh();
    }

    public function updateFees(User $user, array $fees): User
    {
        $this->setMeta($user, 'fees', $fees);
        return $user->fresh();
    }

    public function updateAvailability(User $user, array $availability): User
    {
        if (isset($availability['hours'])) {
            $this->setMeta($user, 'availability', $availability['hours']);
        }
        if (array_key_exists('accepting', $availability)) {
            $this->setMeta($user, 'network_accepting', (bool) $availability['accepting'], 'boolean');
        }
        if (array_key_exists('telehealth', $availability)) {
            $this->setMeta($user, 'network_telehealth', (bool) $availability['telehealth'], 'boolean');
        }
        return $user->fresh();
    }

    public function updatePrivacy(User $user, array $flags): User
    {
        $allowed = ['practitioner_public', 'cs_public', 'business_partner_public'];
        $user->update(array_intersect_key($flags, array_flip($allowed)));
        return $user->fresh();
    }

    public function updateNetwork(User $user, array $prefs): User
    {
        $this->setMeta($user, 'network_prefs', $prefs);
        return $user->fresh();
    }

    public function updateLanguagesAndWebsite(User $user, array $languages, ?string $website): User
    {
        $this->setMeta($user, 'languages', $languages);
        if ($website !== null) {
            $this->setMeta($user, 'website', $website, 'string');
        }
        return $user->fresh();
    }

    public function updateLicensedStates(User $user, array $states): User
    {
        $this->setMeta($user, 'licensed_states', $states);
        return $user->fresh();
    }

    public function updateEducation(User $user, array $education): User
    {
        $this->setMeta($user, 'education', $education);
        return $user->fresh();
    }

    public function updateNetworkPartners(User $user, array $partners): User
    {
        $this->setMeta($user, 'network_partners', $partners);
        return $user->fresh();
    }

    public function updateAiSettings(User $user, array $settings): User
    {
        $this->setMeta($user, 'ai_shadow_settings', $settings);
        return $user->fresh();
    }

    public function updateDemographics(User $user, array $demographics): User
    {
        $this->setMeta($user, 'demographics', $demographics);
        return $user->fresh();
    }

    /**
     * Resolve a public profile by slug and build structured profile_meta
     * from the user_meta key-value table.
     */
    public function getPublicProfile(string $slug): ?User
    {
        return User::where('slug', $slug)
            ->whereNull('deactivated_at')
            ->with('meta')
            ->first();
    }

    /**
     * Convert flat user_meta rows into a structured profile_meta array
     * matching the shape Vue expects: stats, schedule, identity, credentials,
     * fees, insurance_panels, connection, etc.
     *
     * Falls back to sane defaults for every missing key so templates never
     * have to deal with null chains.
     */
    public function buildProfileMeta(User $user, ?User $viewer = null): array
    {
        // Flatten meta into key → typed_value
        $raw = [];
        foreach ($user->meta as $m) {
            $raw[$m->meta_key] = $m->typed_value;
        }

        // Specialties / approaches / population
        $specialties = $raw['specialties']         ?? [];
        $approaches  = $raw['approaches']          ?? [];
        $population  = $raw['population_served']   ?? [];
        $conditions  = $raw['conditions_treated']  ?? [];
        $languages   = $raw['languages']           ?? ['English'];
        $insurance   = $raw['insurance_panels']    ?? ['BCBS', 'Aetna', 'Cigna', 'United', 'Medicare'];

        // Stats — computed from real data with meta fallbacks
        $sentCount      = $user->referralsSent()->count();
        $acceptedCount  = $user->referralsSent()->where('status', 'accepted')->count();
        $receivedCount  = $user->referralsReceived()->count();
        $totalReferrals = $sentCount + $receivedCount;

        $calcAcceptance = $sentCount > 0
            ? round(($acceptedCount / $sentCount) * 100) . '%'
            : ($raw['acceptance_rate'] ?? '—');

        // Mutual connections: network connections the viewer also shares (approximated as total active connections for the profile user)
        $activeConnections = NetworkConnection::where(function ($q) use ($user) {
            $q->where('user_id', $user->id)->orWhere('connected_user_id', $user->id);
        })->where('status', 'active')->count();

        $acceptingClients = $raw['accepting_clients'] ?? true;
        $clientSlots = $acceptingClients ? 'Open' : 'Closed';

        $stats = [
            'avg_response'        => $raw['avg_response']       ?? '—',
            'referrals_exchanged' => $totalReferrals > 0 ? $totalReferrals : (int) ($raw['referrals_exchanged'] ?? 0),
            'acceptance_rate'     => $sentCount > 0 ? $calcAcceptance : ($raw['acceptance_rate'] ?? '—'),
            'mutual_connections'  => $activeConnections > 0 ? $activeConnections : (int) ($raw['mutual_connections'] ?? 0),
            'client_slots'        => $clientSlots,
        ];

        // Schedule
        $schedule = [
            'session_format'    => $raw['session_format']    ?? 'In-Person & Telehealth',
            'session_length'    => $raw['session_length']    ?? '50 min standard',
            'next_available'    => $raw['next_available']    ?? 'Feb 24 · 10:00 AM',
            'waitlist_note'     => $raw['waitlist_note']     ?? '~1–2 wk waitlist for new clients',
            'new_client_wait'   => $raw['new_client_wait']   ?? '~1–2 weeks',
            'urgent_slots'      => $raw['urgent_slots']      ?? '2–3 day turnaround',
            'online_scheduling' => $raw['online_scheduling'] ?? true,
            'availability'      => $raw['availability']      ?? [
                'Mon' => '9–6', 'Tue' => '9–6', 'Wed' => '9–6',
                'Thu' => '9–6', 'Fri' => '9–3', 'Sat' => null, 'Sun' => null,
            ],
        ];

        // Identity
        $identity = [
            'pronouns'        => $raw['pronouns']        ?? 'He/Him',
            'ethnicity'       => $raw['ethnicity']       ?? null,
            'lgbtq_affirming' => $raw['lgbtq_affirming'] ?? true,
            'faith_sensitive' => $raw['faith_sensitive'] ?? true,
            'ada_accessible'  => $raw['ada_accessible']  ?? true,
            'gender_affirming'=> $raw['gender_affirming'] ?? true,
        ];

        // Credentials
        $credentials = [
            'state_licenses'    => $raw['state_licenses']    ?? 'CA · NY · WA',
            'telehealth_states' => $raw['telehealth_states'] ?? 'CA · NY · WA · OR · NV',
            'education'         => $raw['education']         ?? 'UCSF · Stanford',
            'license_status'    => $raw['license_status']    ?? 'Active · Expires Dec 2026',
            'malpractice'       => $raw['malpractice']       ?? 'Verified',
        ];

        // Fees
        $fees = [
            'self_pay_rate'  => $raw['self_pay_rate']  ?? '$200 / 50 min',
            'follow_up_rate' => $raw['follow_up_rate'] ?? '$160 / 30 min',
            'telehealth_rate'=> $raw['telehealth_rate']?? '$180 / 45 min',
            'sliding_scale'  => $raw['sliding_scale']  ?? true,
            'superbill'      => $raw['superbill']       ?? true,
        ];

        // Collaboration prefs
        $collab = [
            'referral_method'   => $raw['referral_method']   ?? 'Aegis · Phone · Fax',
            'preferred_handoff' => $raw['preferred_handoff']  ?? 'Warm handoff (call ahead)',
            'co_management'     => $raw['co_management']      ?? 'Yes — actively welcomes',
            'shared_care_plan'  => $raw['shared_care_plan']   ?? 'Shares clinical summaries',
            'crisis_coverage'   => $raw['crisis_coverage']    ?? 'Covered by on-call team',
            'best_contact_time' => $raw['best_contact_time']  ?? '10 AM – 1 PM PST',
            'best_contact_method' => $raw['best_contact_method'] ?? 'Aegis Message',
            'referral_tips'     => $raw['referral_tips']      ?? 'Include a brief summary when making referrals. Prefers warm handoffs when possible. Can receive shared records or documentation through secure methods.',
        ];

        // Affiliations
        $affiliations = [
            'hospital'     => $raw['hospital_affiliation'] ?? null,
            'academic_apt' => $raw['academic_appointment'] ?? null,
            'apa_member'   => $raw['apa_member']          ?? false,
            'publications' => (int) ($raw['publications'] ?? 0),
        ];

        // Ratings
        $rating       = $raw['rating']       ?? null;
        $review_count = (int) ($raw['review_count'] ?? 0);

        // Reviews
        $reviews = $raw['peer_reviews'] ?? [];

        // Visibility prefs
        $show_ratings    = $raw['show_ratings']      ?? true;
        $show_ref_stats  = $raw['show_referral_stats'] ?? true;
        $show_demographics = $raw['show_demographics'] ?? true;

        // Connection info (viewer-specific)
        $connection = null;
        $pendingRequestId = null;
        $inboundRequestId = null;
        if ($viewer && $viewer->id !== $user->id) {
            $conn = NetworkConnection::where(function ($q) use ($user, $viewer) {
                $q->where('user_id', $viewer->id)->where('connected_user_id', $user->id);
            })->orWhere(function ($q) use ($user, $viewer) {
                $q->where('user_id', $user->id)->where('connected_user_id', $viewer->id);
            })->where('status', 'active')->first();

            if ($conn) {
                $connection = [
                    'id'                    => $conn->id,
                    'connected_since'       => $conn->connected_at?->format('F Y'),
                    'connection_type'       => 'Mutual (Both Accepted)',
                    'last_interaction'      => $raw['last_interaction'] ?? null,
                    'mutual_connections'    => $stats['mutual_connections'],
                    'profile_completeness'  => ($raw['profile_completeness'] ?? 98) . '%',
                ];
            } else {
                // Outbound: viewer sent a request to this profile owner
                $outbound = \App\Models\NetworkRequest::where('requester_id', $viewer->id)
                    ->where('recipient_id', $user->id)
                    ->where('status', 'pending')
                    ->first();
                // Inbound: profile owner sent viewer a request (viewer should see Accept/Decline)
                $inbound  = \App\Models\NetworkRequest::where('requester_id', $user->id)
                    ->where('recipient_id', $viewer->id)
                    ->where('status', 'pending')
                    ->first();
                $pendingRequestId  = $outbound?->id;
                $inboundRequestId  = $inbound?->id;
            }
        }

        return [
            'rating'           => $rating,
            'review_count'     => $review_count,
            'stats'            => $stats,
            'schedule'         => $schedule,
            'identity'         => $identity,
            'credentials'      => $credentials,
            'fees'             => $fees,
            'collab'           => $collab,
            'affiliations'     => $affiliations,
            'specialties'      => is_array($specialties) ? $specialties : [],
            'approaches'       => is_array($approaches)  ? $approaches  : [],
            'population_served'=> is_array($population)  ? $population  : [],
            'conditions'       => is_array($conditions)  ? $conditions  : [],
            'languages'        => is_array($languages)   ? $languages   : [$languages],
            'insurance_panels' => is_array($insurance)   ? $insurance   : [],
            'reviews'          => is_array($reviews)     ? $reviews     : [],
            'connection'       => $connection,
            'pending_request_id'  => $pendingRequestId,
            'inbound_request_id'  => $inboundRequestId,
            'show_ratings'     => (bool) $show_ratings,
            'show_ref_stats'   => (bool) $show_ref_stats,
            'show_demographics'=> (bool) $show_demographics,
            'profile_completeness' => (int) ($raw['profile_completeness'] ?? 70),
            'years_in_practice'    => (int) ($raw['years_in_business'] ?? 5),
            'accepting_clients'    => (bool) ($raw['accepting_clients'] ?? true),
            'about_me_extended'    => $raw['about_me_extended'] ?? null,
            'private_notes'        => [],   // populated owner-side only — see ProfileController
        ];
    }

    /**
     * Append a private note to the owner's user_meta 'private_notes' JSON array.
     * Notes are stored newest-first; the list is capped at 50 entries.
     */
    public function savePrivateNote(User $user, string $body): void
    {
        $existing = [];
        $row = $user->meta()->where('meta_key', 'private_notes')->first();
        if ($row) {
            $existing = json_decode((string) $row->meta_value, true) ?? [];
        }

        array_unshift($existing, [
            'body'       => $body,
            'created_at' => now()->toDateTimeString(),
        ]);

        $existing = array_slice($existing, 0, 50);

        $this->setMeta($user, 'private_notes', $existing);
    }

    /**
     * Load private notes for the profile owner only.
     */
    public function getPrivateNotes(User $user): array
    {
        $row = $user->meta()->where('meta_key', 'private_notes')->first();
        return $row ? (json_decode((string) $row->meta_value, true) ?? []) : [];
    }
}
