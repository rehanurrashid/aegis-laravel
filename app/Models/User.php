<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\BpType;
use App\Enums\CsAccountType;
use App\Enums\UserRole;
use App\Enums\UserTier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $table        = 'users';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'id', 'role', 'display_name', 'credentials', 'email', 'phone', 'location',
        'organization', 'avatar_initials', 'avatar_path', 'title', 'specialty', 'bio',
        'slug', 'slug_locked_at',
        'practitioner_public', 'cs_public', 'business_partner_public',
        'tier', 'services_mode', 'maat_addon', 'payment_model',
        'cs_account_type', 'cs_path', 'linked_provider_id',
        'stripe_connected', 'stripe_account_id', 'stripe_id', 'stripe_payment_method_id', 'verified',
        'invited_by_id', 'about_me',
        'bp_type', 'bp_business_name', 'bp_team_size', 'bp_hourly_rate_cents', 'bp_categories',
        'two_factor_enabled',
        'password', 'remember_token',
        'locked_at', 'locked_reason', 'failed_login_count',
        'deactivated_at', 'last_login_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $appends = ['portal', 'avatar_url'];

    protected $casts = [
        'role'                    => UserRole::class,
        'tier'                    => UserTier::class,
        'cs_account_type'         => CsAccountType::class,
        'bp_type'                 => BpType::class,
        'bp_categories'           => 'array',
        'bp_team_size'            => 'integer',
        'bp_hourly_rate_cents'    => 'integer',
        'failed_login_count'      => 'integer',
        'practitioner_public'     => 'boolean',
        'cs_public'               => 'boolean',
        'business_partner_public' => 'boolean',
        'services_mode'           => 'boolean',
        'maat_addon'              => 'boolean',
        'stripe_connected'        => 'boolean',
        'verified'                => 'boolean',
        'two_factor_enabled'      => 'boolean',
        'slug_locked_at'          => 'datetime',
        'locked_at'               => 'datetime',
        'deactivated_at'          => 'datetime',
        'last_login_at'           => 'datetime',
    ];

    // ── Accessor: portal derived from role ─────────────────────────────
    public function getPortalAttribute(): string
    {
        return $this->role?->portal() ?? 'provider';
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->avatar_path) : null;
    }

    // ── Self-referential ───────────────────────────────────────────────
    public function linkedProvider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'linked_provider_id');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    }

    public function invitees(): HasMany
    {
        return $this->hasMany(User::class, 'invited_by_id');
    }

    // ── Auth + identity ────────────────────────────────────────────────
    public function meta(): HasMany           { return $this->hasMany(UserMeta::class); }
    public function roleAssignments(): HasMany { return $this->hasMany(UserRoleAssignment::class); }
    public function roles(): HasMany           { return $this->hasMany(UserRoleAssignment::class); }
    public function sessions(): HasMany       { return $this->hasMany(UserSession::class); }
    public function preferences(): HasOne     { return $this->hasOne(UserPreference::class); }
    public function passwordResetTokens(): HasMany { return $this->hasMany(PasswordResetToken::class); }
    public function mfaToken(): HasOne        { return $this->hasOne(MfaToken::class); }

    // ── Plans ──────────────────────────────────────────────────────────
    public function continuityPlans(): HasMany
    {
        return $this->hasMany(ContinuityPlan::class, 'practitioner_id');
    }

    public function stewardships(): HasMany
    {
        return $this->hasMany(PlanSteward::class, 'steward_id');
    }

    public function assignedPlanTasks(): HasMany
    {
        return $this->hasMany(PlanTask::class, 'steward_id');
    }

    // ── Incidents ──────────────────────────────────────────────────────
    public function incidentsAsPractitioner(): HasMany
    {
        return $this->hasMany(CriticalIncident::class, 'practitioner_id');
    }

    public function incidentsReported(): HasMany
    {
        return $this->hasMany(CriticalIncident::class, 'reported_by_id');
    }

    public function incidentsVerified(): HasMany
    {
        return $this->hasMany(CriticalIncident::class, 'verified_by_id');
    }

    public function assignedIncidentTasks(): HasMany
    {
        return $this->hasMany(IncidentTask::class, 'assigned_to_id');
    }

    public function incidentUpdates(): HasMany
    {
        return $this->hasMany(IncidentUpdate::class, 'actor_id');
    }

    // ── Vault ──────────────────────────────────────────────────────────
    public function vaultItems(): HasMany
    {
        return $this->hasMany(VaultItem::class, 'practitioner_id');
    }

    public function vaultAccessAsPractitioner(): HasMany
    {
        return $this->hasMany(VaultAccessLog::class, 'practitioner_id');
    }

    public function vaultAccessAsActor(): HasMany
    {
        return $this->hasMany(VaultAccessLog::class, 'actor_id');
    }

    public function vaultAccessAsRecipient(): HasMany
    {
        return $this->hasMany(VaultAccessLog::class, 'recipient_id');
    }

    // ── Documents ──────────────────────────────────────────────────────
    public function continuityDocuments(): HasMany
    {
        return $this->hasMany(ContinuityDocument::class, 'practitioner_id');
    }

    public function heldDocuments(): HasMany
    {
        return $this->hasMany(ContinuityDocument::class, 'holder_steward_id');
    }

    public function documentSignatures(): HasMany
    {
        return $this->hasMany(DocumentSignature::class, 'signer_id');
    }

    // ── Network ────────────────────────────────────────────────────────
    public function networkConnections(): HasMany
    {
        return $this->hasMany(NetworkConnection::class, 'user_id');
    }

    public function networkConnectionsIncoming(): HasMany
    {
        return $this->hasMany(NetworkConnection::class, 'connected_user_id');
    }

    public function networkRequestsSent(): HasMany
    {
        return $this->hasMany(NetworkRequest::class, 'requester_id');
    }

    public function networkRequestsReceived(): HasMany
    {
        return $this->hasMany(NetworkRequest::class, 'recipient_id');
    }

    public function shadowConnections(): HasMany
    {
        return $this->hasMany(ShadowConnection::class, 'user_id');
    }

    public function shadowOf(): HasMany
    {
        return $this->hasMany(ShadowConnection::class, 'shadow_user_id');
    }

    // ── Referrals ──────────────────────────────────────────────────────
    public function referralsSent(): HasMany
    {
        return $this->hasMany(Referral::class, 'sender_id');
    }

    public function referralsReceived(): HasMany
    {
        return $this->hasMany(Referral::class, 'recipient_id');
    }

    // ── Messages ───────────────────────────────────────────────────────
    public function messageThreadsCreated(): HasMany
    {
        return $this->hasMany(MessageThread::class, 'created_by_id');
    }

    public function messagesSent(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function messagesReceived(): HasMany
    {
        return $this->hasMany(Message::class, 'recipient_id');
    }

    // ── Activity ───────────────────────────────────────────────────────
    public function activityEvents(): HasMany
    {
        return $this->hasMany(ActivityEvent::class, 'user_id');
    }

    public function scopedActivityEvents(): HasMany
    {
        return $this->hasMany(ActivityEvent::class, 'scoped_provider_id');
    }

    public function activityReads(): HasMany
    {
        return $this->hasMany(ActivityEventRead::class, 'user_id');
    }

    // ── Services + CEU ─────────────────────────────────────────────────
    public function services(): HasMany
    {
        return $this->hasMany(Service::class, 'practitioner_id');
    }

    public function serviceRequestsAsPractitioner(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'practitioner_id');
    }

    public function serviceRequestsAsInquirer(): HasMany
    {
        return $this->hasMany(ServiceRequest::class, 'inquirer_id');
    }

    public function serviceSessionsAsPractitioner(): HasMany
    {
        return $this->hasMany(ServiceSession::class, 'practitioner_id');
    }

    public function serviceSessionsAsClient(): HasMany
    {
        return $this->hasMany(ServiceSession::class, 'client_id');
    }

    public function ceuEntries(): HasMany
    {
        return $this->hasMany(CeuEntry::class, 'practitioner_id');
    }

    // ── BP ─────────────────────────────────────────────────────────────
    public function bpJobs(): HasMany
    {
        return $this->hasMany(BpJob::class, 'practitioner_id');
    }

    public function bpProposals(): HasMany
    {
        return $this->hasMany(BpProposal::class, 'bp_id');
    }

    public function bpContractsAsPractitioner(): HasMany
    {
        return $this->hasMany(BpContract::class, 'practitioner_id');
    }

    public function bpContractsAsBp(): HasMany
    {
        return $this->hasMany(BpContract::class, 'bp_id');
    }

    public function bpMilestonesAssigned(): HasMany
    {
        return $this->hasMany(BpMilestone::class, 'assigned_member_id');
    }

    public function bpInvoicesAsBp(): HasMany
    {
        return $this->hasMany(BpInvoice::class, 'bp_id');
    }

    public function bpInvoicesAsPractitioner(): HasMany
    {
        return $this->hasMany(BpInvoice::class, 'practitioner_id');
    }

    public function bpInvoicePayments(): HasMany
    {
        return $this->hasMany(BpInvoicePayment::class, 'payer_id');
    }

    public function bpPayouts(): HasMany
    {
        return $this->hasMany(BpPayout::class, 'bp_id');
    }

    public function bpTaxDocuments(): HasMany
    {
        return $this->hasMany(BpTaxDocument::class, 'bp_id');
    }

    public function bpSavedJobs(): HasMany
    {
        return $this->hasMany(BpSavedJob::class, 'bp_id');
    }

    public function bpTeamMembershipsAsAgency(): HasMany
    {
        return $this->hasMany(BpTeamMember::class, 'agency_id');
    }

    public function bpTeamMembershipsAsMember(): HasMany
    {
        return $this->hasMany(BpTeamMember::class, 'member_id');
    }

    public function bpTeamInvitations(): HasMany
    {
        return $this->hasMany(BpTeamInvitation::class, 'agency_id');
    }

    // ── CS finances ────────────────────────────────────────────────────
    public function csInvoicesIssued(): HasMany
    {
        return $this->hasMany(CsInvoice::class, 'cs_id');
    }

    public function csInvoicesReceived(): HasMany
    {
        return $this->hasMany(CsInvoice::class, 'practitioner_id');
    }

    public function csPayouts(): HasMany
    {
        return $this->hasMany(CsPayout::class, 'cs_id');
    }

    // ── Payments ───────────────────────────────────────────────────────
    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PractitionerPaymentMethod::class, 'practitioner_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(PractitionerPayment::class, 'practitioner_id');
    }

    // ── Steward check-ins (provider_checkins post-rename) ──────────────
    public function checkinsAsSteward(): HasMany
    {
        return $this->hasMany(ProviderCheckin::class, 'steward_id');
    }

    public function checkinsReceived(): HasMany
    {
        return $this->hasMany(ProviderCheckin::class, 'practitioner_id');
    }

    public function ssNotesAuthored(): HasMany
    {
        return $this->hasMany(SsProviderNote::class, 'ss_id');
    }

    public function ssNotesReceived(): HasMany
    {
        return $this->hasMany(SsProviderNote::class, 'practitioner_id');
    }

    // ── News ───────────────────────────────────────────────────────────
    public function newsPosts(): HasMany
    {
        return $this->hasMany(NewsPost::class, 'author_id');
    }

    public function newsComments(): HasMany
    {
        return $this->hasMany(NewsComment::class, 'author_id');
    }

    public function newsReactions(): HasMany
    {
        return $this->hasMany(NewsReaction::class, 'user_id');
    }

    public function newsPollVotes(): HasMany
    {
        return $this->hasMany(NewsPollVote::class, 'user_id');
    }

    // ── Support ────────────────────────────────────────────────────────
    public function complaintsSubmitted(): HasMany
    {
        return $this->hasMany(Complaint::class, 'submitter_id');
    }

    public function complaintsAssigned(): HasMany
    {
        return $this->hasMany(Complaint::class, 'assigned_to');
    }

    public function complaintReplies(): HasMany
    {
        return $this->hasMany(ComplaintReply::class, 'author_id');
    }

    // ── Admin ──────────────────────────────────────────────────────────
    public function auditLogsCreated(): HasMany
    {
        return $this->hasMany(AdminAuditLog::class, 'admin_id');
    }

    public function auditLogsTargeting(): HasMany
    {
        return $this->hasMany(AdminAuditLog::class, 'target_user_id');
    }

    public function profileEditAuthorizationsGranted(): HasMany
    {
        return $this->hasMany(ProfileEditAuthorization::class, 'practitioner_id');
    }

    public function profileEditAuthorizationsHeld(): HasMany
    {
        return $this->hasMany(ProfileEditAuthorization::class, 'authorized_user_id');
    }

    // ── Scopes ─────────────────────────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->whereNull('locked_at')->whereNull('deactivated_at');
    }

    public function scopeLocked($query)
    {
        return $query->whereNotNull('locked_at');
    }

    public function scopeByRole($query, UserRole|string $role)
    {
        return $query->where('role', $role instanceof UserRole ? $role->value : $role);
    }

    public function scopePublic($query)
    {
        return $query->where(function ($q) {
            $q->where('practitioner_public', 1)
              ->orWhere('cs_public', 1)
              ->orWhere('business_partner_public', 1);
        });
    }

    // ── Helpers ────────────────────────────────────────────────────────
    public function isPractitioner(): bool      { return $this->role === UserRole::Practitioner; }
    public function isContinuitySteward(): bool { return $this->role === UserRole::ContinuitySteward; }
    public function isSupportSteward(): bool    { return $this->role === UserRole::SupportSteward; }
    public function isBusinessPartner(): bool   { return $this->role === UserRole::BusinessPartner; }
    public function isAdmin(): bool             { return $this->role === UserRole::Admin; }
    public function isLocked(): bool            { return $this->locked_at !== null; }
    public function isDeactivated(): bool       { return $this->deactivated_at !== null; }
}
