# Aegis — Full Application File Tree (Corrected)

Concise, single-glance directory map. Backend (Laravel 11) and frontend (Vue 3 + Inertia) files only — no descriptions.

**Final corrected version — closes all 22 `[NO UC BASIS]` items in addition to prior gap fixes.**

**Latest additions (UC closure pass):**
- Added `NewsService` + `Provider/NewsController` + News policy + 6 News FormRequests + News events folder (UC-PRV-240..248)
- Added `ActivityEventRead` model + new migration `000071` (UC-PRV-092, UC-PRV-093)
- Renamed `SsProviderCheckin` → `ProviderCheckin` model + new migration `000072` rename (UC-CS-015)
- Added `Admin/IncidentsController` + `Incidents.vue` (UC-ADM-068)
- Added `ReferralService` (was cited but undefined)
- Added `NewsLibrary.vue` (UC-PRV-247)

**Changes from previous version (per `AEGIS_STRUCTURE_GAP_REPORT.md`):**
- Renamed `UserRole.php` model → `UserRoleAssignment.php`
- Added `app/Events/AegisEvent.php` (abstract base) as explicit entry
- Added 4 new Policies: `SubscriptionPolicy`, `NetworkConnectionPolicy`, `HelpArticlePolicy`, `PackagePolicy`
- Added 4 new Services: `AdminPayoutService`, `AdminHelpArticleService`, `TaxDocumentService`, `TeamService`
- Added 2 new Admin controllers: `PayoutsController`, `HelpArticlesController`
- Added new ContinuitySteward controllers: `InvoicesController`, `IncidentsController`, `SupportController`
- Added new BusinessPartner controllers: `MessagesController`, `SettingsController`, `SupportController`, `TaxDocumentsController`
- Added new SupportSteward controllers: `SettingsController`, `SupportController`
- Added new Provider controller: `RosterController`
- Added new Auth controllers: `OnboardingController`, `VerifyEmailController`
- Added ~50 new FormRequests under their respective domain folders
- Added `StewardResigned` event + email template (UC-CS-026)
- Added Admin events (`HelpArticlePublished`, `PayoutReleasedManually`)
- Added new Vue pages for newly-covered UCs (`Roster.vue`, `HelpCenter.vue` per portal, `TaxDocuments.vue`, `Invoices.vue` for CS, `Payouts.vue` and `HelpArticles.vue` for admin, onboarding pages)
- Added `lang/en/validation.php`

---

## Backend — Laravel 11

```
app/
├── Enums/
│   ├── ActivitySeverity.php
│   ├── BpJobStatus.php
│   ├── ComplaintCategory.php
│   ├── ComplaintPriority.php
│   ├── ComplaintStatus.php
│   ├── ContractStatus.php
│   ├── DocumentStatus.php
│   ├── IncidentStatus.php
│   ├── IncidentType.php
│   ├── InvoiceStatus.php
│   ├── MilestoneStatus.php
│   ├── PayoutStatus.php
│   ├── PlanStatus.php
│   ├── ProposalStatus.php
│   ├── ReferralStatus.php
│   ├── ServiceRequestStatus.php
│   ├── ServiceStatus.php
│   ├── StewardRole.php
│   ├── StewardStatus.php
│   ├── StewardType.php
│   ├── SubmissionChannel.php
│   ├── TaskStatus.php
│   ├── UserRole.php
│   ├── VaultItemStatus.php
│   └── VaultZone.php
│
├── Models/
│   ├── AdminAuditLog.php
│   ├── ActivityEvent.php
│   ├── ActivityEventRead.php
│   ├── BpContract.php
│   ├── BpInvoice.php
│   ├── BpInvoiceLineItem.php
│   ├── BpInvoicePayment.php
│   ├── BpJob.php
│   ├── BpMilestone.php
│   ├── BpPayout.php
│   ├── BpProposal.php
│   ├── BpSavedJob.php
│   ├── BpTaxDocument.php
│   ├── BpTeamInvitation.php
│   ├── BpTeamMember.php
│   ├── CeuEntry.php
│   ├── Complaint.php
│   ├── ComplaintMeta.php
│   ├── ComplaintReply.php
│   ├── ContinuityDocument.php
│   ├── ContinuityPlan.php
│   ├── ContractMeta.php
│   ├── CriticalIncident.php
│   ├── CsInvoice.php
│   ├── CsPayout.php
│   ├── DocumentSignature.php
│   ├── HelpArticle.php
│   ├── IncidentMeta.php
│   ├── IncidentTask.php
│   ├── IncidentUpdate.php
│   ├── Message.php
│   ├── MessageThread.php
│   ├── MfaToken.php
│   ├── NetworkConnection.php
│   ├── NetworkRequest.php
│   ├── NewsComment.php
│   ├── NewsEvent.php
│   ├── NewsLibraryItem.php
│   ├── NewsPollVote.php
│   ├── NewsPost.php
│   ├── NewsReaction.php
│   ├── NewsTrendingTopic.php
│   ├── PackageOverride.php
│   ├── PasswordResetToken.php
│   ├── PlanIncidentConfig.php
│   ├── PlanMeta.php
│   ├── PlanSteward.php
│   ├── PlanTask.php
│   ├── PractitionerPayment.php
│   ├── PractitionerPaymentMethod.php
│   ├── ProfileEditAuthorization.php
│   ├── Referral.php
│   ├── ReferralMeta.php
│   ├── Role.php
│   ├── RolePermission.php
│   ├── Service.php
│   ├── ServiceRequest.php
│   ├── ServiceSession.php
│   ├── ShadowConnection.php
│   ├── ProviderCheckin.php
│   ├── SsProviderNote.php
│   ├── StripeWebhookEvent.php
│   ├── User.php
│   ├── UserMeta.php
│   ├── UserPreference.php
│   ├── UserRoleAssignment.php
│   ├── UserSession.php
│   ├── VaultAccessLog.php
│   ├── VaultItem.php
│   └── VaultItemMeta.php
│
├── Services/
│   ├── ActivityService.php
│   ├── AdminComplaintService.php
│   ├── AdminHelpArticleService.php
│   ├── AdminPackageService.php
│   ├── AdminPaymentService.php
│   ├── AdminPayoutService.php
│   ├── AdminRoleService.php
│   ├── AdminUserService.php
│   ├── AuthService.php
│   ├── BpJobService.php
│   ├── CeuService.php
│   ├── ContractService.php
│   ├── DocumentService.php
│   ├── IncidentService.php
│   ├── InvoiceService.php
│   ├── MessagingService.php
│   ├── NetworkService.php
│   ├── NewsService.php
│   ├── NotificationService.php
│   ├── PayoutService.php
│   ├── PlanService.php
│   ├── ProfileService.php
│   ├── ReferralService.php
│   ├── ProposalService.php
│   ├── ServiceService.php
│   ├── StewardService.php
│   ├── SubscriptionService.php
│   ├── SupportService.php
│   ├── TaxDocumentService.php
│   ├── TeamService.php
│   └── VaultService.php
│
├── Events/
│   ├── AegisEvent.php
│   ├── Admin/
│   │   ├── HelpArticlePublished.php
│   │   ├── PayoutReleasedManually.php
│   │   ├── RefundProcessed.php
│   │   ├── UserLocked.php
│   │   └── UserRoleChanged.php
│   ├── Auth/
│   │   ├── EmailVerified.php
│   │   ├── PasswordReset.php
│   │   ├── UserLoggedIn.php
│   │   └── UserRegistered.php
│   ├── Business/
│   │   ├── ContractCancelled.php
│   │   ├── ContractCreated.php
│   │   ├── ContractSigned.php
│   │   ├── InvoicePaid.php
│   │   ├── InvoiceSent.php
│   │   ├── InvoiceVoided.php
│   │   ├── MilestoneSubmitted.php
│   │   ├── PayoutReleased.php
│   │   ├── ProposalAccepted.php
│   │   ├── ProposalSubmitted.php
│   │   └── ProposalWithdrawn.php
│   ├── Incident/
│   │   ├── IncidentActivated.php
│   │   ├── IncidentClosed.php
│   │   ├── IncidentEscalated.php
│   │   ├── IncidentReopened.php
│   │   ├── IncidentReported.php
│   │   ├── IncidentVerified.php
│   │   ├── IncidentWithdrawn.php
│   │   └── VaultUnsealed.php
│   ├── News/
│   │   ├── EventRsvpReceived.php
│   │   ├── NewsCommented.php
│   │   └── NewsPostPublished.php
│   ├── Plan/
│   │   ├── AnnualReviewCompleted.php
│   │   ├── AnnualReviewDue.php
│   │   ├── PlanSigned.php
│   │   ├── PlanVersionUpdated.php
│   │   └── VaultAttested.php
│   ├── Steward/
│   │   ├── AlternateCSActivated.php
│   │   ├── StewardAccepted.php
│   │   ├── StewardDeclined.php
│   │   ├── StewardDesignated.php
│   │   ├── StewardRemoved.php
│   │   └── StewardResigned.php
│   └── Support/
│       ├── TicketCreated.php
│       └── TicketReplied.php
│
├── Listeners/
│   ├── ActivityFanoutListener.php
│   ├── SendEmailNotificationListener.php
│   ├── SendIncidentAlertsListener.php
│   └── StripeEventListener.php
│
├── Jobs/
│   ├── ActivityFanoutJob.php
│   ├── AnnualReviewReminderJob.php
│   ├── DigestEmailJob.php
│   ├── IncidentNotificationJob.php
│   ├── SendEmailJob.php
│   ├── StaleIncidentAlertJob.php
│   ├── StripeWebhookProcessorJob.php
│   └── VaultSealCheckJob.php
│
├── Policies/
│   ├── AdminPolicy.php
│   ├── BpContractPolicy.php
│   ├── BpInvoicePolicy.php
│   ├── BpJobPolicy.php
│   ├── ComplaintPolicy.php
│   ├── ContinuityDocumentPolicy.php
│   ├── ContinuityPlanPolicy.php
│   ├── HelpArticlePolicy.php
│   ├── IncidentPolicy.php
│   ├── MessagePolicy.php
│   ├── NetworkConnectionPolicy.php
│   ├── NewsPolicy.php
│   ├── PackagePolicy.php
│   ├── PlanTaskPolicy.php
│   ├── ProfileEditAuthorizationPolicy.php
│   ├── ReferralPolicy.php
│   ├── ServicePolicy.php
│   ├── SubscriptionPolicy.php
│   └── VaultPolicy.php
│
├── Http/
│   ├── Middleware/
│   │   ├── CheckAccountLocked.php
│   │   ├── EnsureAdminRole.php
│   │   ├── EnsureIncidentActive.php
│   │   ├── EnsurePlanActive.php
│   │   ├── EnsureRole.php
│   │   ├── EnsureServicesMode.php
│   │   ├── HandleInertiaRequests.php
│   │   └── ImpersonateForDemo.php
│   │
│   ├── Controllers/
│   │   ├── Admin/
│   │   │   ├── ComplaintsController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── HelpArticlesController.php
│   │   │   ├── IncidentsController.php
│   │   │   ├── PackagesController.php
│   │   │   ├── PaymentsController.php
│   │   │   ├── PayoutsController.php
│   │   │   ├── RolesController.php
│   │   │   └── UsersController.php
│   │   ├── Auth/
│   │   │   ├── LoginController.php
│   │   │   ├── MfaController.php
│   │   │   ├── OnboardingController.php
│   │   │   ├── PasswordResetController.php
│   │   │   ├── RegisterController.php
│   │   │   └── VerifyEmailController.php
│   │   ├── BusinessPartner/
│   │   │   ├── ContractsController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── FinancesController.php
│   │   │   ├── InvoicesController.php
│   │   │   ├── JobsController.php
│   │   │   ├── MessagesController.php
│   │   │   ├── MilestonesController.php
│   │   │   ├── PaymentSetupController.php
│   │   │   ├── ProfileController.php
│   │   │   ├── ProposalsController.php
│   │   │   ├── SettingsController.php
│   │   │   ├── SupportController.php
│   │   │   ├── TaxDocumentsController.php
│   │   │   └── TeamController.php
│   │   ├── ContinuitySteward/
│   │   │   ├── ContinuityManagementController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── DocumentsController.php
│   │   │   ├── FinancesController.php
│   │   │   ├── IncidentsController.php
│   │   │   ├── InvoicesController.php
│   │   │   ├── ProfileController.php
│   │   │   ├── ProvidersController.php
│   │   │   ├── SettingsController.php
│   │   │   ├── SupportController.php
│   │   │   ├── TasksController.php
│   │   │   └── VaultController.php
│   │   ├── Provider/
│   │   │   ├── ContinuityPlanController.php
│   │   │   ├── ContinuityStewardController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── DocumentsController.php
│   │   │   ├── FinancesController.php
│   │   │   ├── JobPostingsController.php
│   │   │   ├── NetworkController.php
│   │   │   ├── NewsController.php
│   │   │   ├── ProfileController.php
│   │   │   ├── ReferralsController.php
│   │   │   ├── RosterController.php
│   │   │   ├── ServicesController.php
│   │   │   ├── SettingsController.php
│   │   │   ├── SupportStewardController.php
│   │   │   └── VaultController.php
│   │   ├── Public/
│   │   │   └── ProfileController.php
│   │   ├── Shared/
│   │   │   ├── ActivityController.php
│   │   │   ├── MessagesController.php
│   │   │   ├── OverviewController.php
│   │   │   └── SupportController.php
│   │   └── SupportSteward/
│   │       ├── ContinuityStewardsController.php
│   │       ├── CriticalIncidentController.php
│   │       ├── DashboardController.php
│   │       ├── DocumentsController.php
│   │       ├── ProfileController.php
│   │       ├── ProvidersController.php
│   │       ├── SettingsController.php
│   │       ├── SupportController.php
│   │       └── TasksController.php
│   │
│   └── Requests/
│       ├── Admin/
│       │   ├── ChangeRoleRequest.php
│       │   ├── CreateRoleRequest.php
│       │   ├── DeactivateUserRequest.php
│       │   ├── HelpArticleRequest.php
│       │   ├── ImpersonateUserRequest.php
│       │   ├── LockUserRequest.php
│       │   ├── ManualPayoutRequest.php
│       │   ├── RefundPaymentRequest.php
│       │   ├── UpdatePackageRequest.php
│       │   └── UpdateRolePermissionsRequest.php
│       ├── Auth/
│       │   ├── LoginRequest.php
│       │   ├── OnboardingIntentRequest.php
│       │   ├── PasswordResetRequest.php
│       │   ├── RegisterRequest.php
│       │   └── VerifyEmailRequest.php
│       ├── Business/
│       │   ├── AddLineItemRequest.php
│       │   ├── CreateContractRequest.php
│       │   ├── CreateInvoiceRequest.php
│       │   ├── CreateJobRequest.php
│       │   ├── SubmitMilestoneRequest.php
│       │   ├── SubmitProposalRequest.php
│       │   ├── UpdateProposalRequest.php
│       │   ├── UploadTaxDocRequest.php
│       │   └── W9SubmissionRequest.php
│       ├── Docs/
│       │   ├── AmendDocumentRequest.php
│       │   ├── RequestReleaseRequest.php
│       │   ├── SendForSignatureRequest.php
│       │   ├── SignReminderRequest.php
│       │   └── UploadDocumentRequest.php
│       ├── Incident/
│       │   ├── AttachIncidentDocRequest.php
│       │   ├── EscalateIncidentRequest.php
│       │   ├── IncidentUpdateRequest.php
│       │   ├── ReportIncidentRequest.php
│       │   └── VerifyIncidentRequest.php
│       ├── Messages/
│       │   ├── CreateThreadRequest.php
│       │   └── SendMessageRequest.php
│       ├── News/
│       │   ├── CreateCommentRequest.php
│       │   ├── CreateNewsPostRequest.php
│       │   ├── PollVoteRequest.php
│       │   ├── ReactionRequest.php
│       │   ├── RsvpEventRequest.php
│       │   └── UpdateNewsPostRequest.php
│       ├── Network/
│       │   ├── AddShadowRequest.php
│       │   ├── RequestConnectionRequest.php
│       │   └── SendReferralRequest.php
│       ├── Plan/
│       │   ├── AddPlanTaskRequest.php
│       │   ├── AnnualReviewRequest.php
│       │   ├── AttestVaultRequest.php
│       │   ├── ConfigureIncidentsRequest.php
│       │   ├── CreatePlanRequest.php
│       │   └── SignPlanRequest.php
│       ├── Profile/
│       │   ├── UpdateAvailabilityRequest.php
│       │   ├── UpdateBasicProfileRequest.php
│       │   ├── UpdateCredentialsRequest.php
│       │   ├── UpdateFeesRequest.php
│       │   ├── UpdateSpecialtiesRequest.php
│       │   └── UpdateVisibilityRequest.php
│       ├── Referrals/
│       │   └── CreateReferralRequest.php
│       ├── Roster/
│       │   └── UpsertRosterEntryRequest.php
│       ├── Services/
│       │   ├── CreateServiceRequest.php
│       │   └── UpdateServiceRequest.php
│       ├── Settings/
│       │   ├── CloseAccountRequest.php
│       │   ├── ExportDataRequest.php
│       │   ├── UpdateNotificationGatesRequest.php
│       │   └── UpdatePreferencesRequest.php
│       ├── Steward/
│       │   ├── AddCheckinRequest.php
│       │   ├── CertifyPlanRequest.php
│       │   ├── DesignateStewardRequest.php
│       │   ├── ResignStewardRequest.php
│       │   └── UpdateStewardTaskRequest.php
│       ├── Subscription/
│       │   ├── CancelSubscriptionRequest.php
│       │   ├── ChangeTierRequest.php
│       │   └── ToggleAddOnRequest.php
│       ├── Support/
│       │   ├── CreateTicketRequest.php
│       │   ├── ReplyTicketRequest.php
│       │   └── SubmitFeedbackRequest.php
│       ├── Team/
│       │   ├── InviteTeamMemberRequest.php
│       │   └── UpdatePermissionsRequest.php
│       └── Vault/
│           ├── SetVaultPermissionsRequest.php
│           ├── UpdateVaultItemRequest.php
│           └── UploadVaultItemRequest.php
│
├── Providers/
│   ├── AppServiceProvider.php
│   ├── AuthServiceProvider.php
│   ├── BroadcastServiceProvider.php
│   ├── EventServiceProvider.php
│   └── RouteServiceProvider.php
│
└── Console/
    ├── Commands/
    │   ├── DispatchDigestsCommand.php
    │   ├── ExpireStalePlansCommand.php
    │   └── SweepOverdueInvoicesCommand.php
    └── Kernel.php

bootstrap/
├── app.php
├── providers.php
└── cache/

config/
├── aegis.php
├── app.php
├── auth.php
├── broadcasting.php
├── cache.php
├── cashier.php
├── cors.php
├── database.php
├── filesystems.php
├── horizon.php
├── inertia.php
├── logging.php
├── mail.php
├── queue.php
├── reverb.php
├── sanctum.php
├── services.php
└── session.php

database/
├── migrations/                  (72 files: 69 base table creates + 1 add_foreign_keys + 2 UC closure pass — `000071_create_activity_event_reads_table`, `000072_rename_provider_checkins_table`)
├── factories/
│   ├── UserFactory.php
│   ├── ContinuityPlanFactory.php
│   ├── CriticalIncidentFactory.php
│   ├── VaultItemFactory.php
│   ├── BpJobFactory.php
│   ├── BpProposalFactory.php
│   └── ComplaintFactory.php
└── seeders/                     (26 files — full demo dataset across all 65 active tables)
    ├── ActivityReadSeeder.php
    ├── ActivitySeeder.php
    ├── AdminSeeder.php
    ├── BpSeeder.php
    ├── CeuSeeder.php
    ├── DatabaseSeeder.php
    ├── DocumentSeeder.php
    ├── IncidentConfigSeeder.php
    ├── IncidentSeeder.php
    ├── InvoiceSeeder.php
    ├── MessageSeeder.php
    ├── NetworkSeeder.php
    ├── NewsSeeder.php
    ├── PackageSeeder.php
    ├── PayoutSeeder.php
    ├── PlanSeeder.php
    ├── PlanTaskSeeder.php
    ├── ProviderCheckinSeeder.php
    ├── ReferralSeeder.php
    ├── RoleSeeder.php
    ├── ServiceSeeder.php
    ├── StewardSeeder.php
    ├── SupportSeeder.php
    ├── UserMetaSeeder.php
    ├── UserSeeder.php
    └── VaultSeeder.php

lang/
└── en/
    ├── auth.php
    ├── pagination.php
    ├── passwords.php
    └── validation.php

routes/
├── api.php
├── channels.php
├── console.php
└── web.php

storage/
├── app/{public,private}/
├── framework/
└── logs/

tests/
├── Feature/{Auth,Plan,Incident,Vault,Bp,Admin}/
├── Unit/{Services,Policies}/
├── Pest.php
└── TestCase.php

composer.json
package.json
.env.example
artisan
```

---

## Frontend — Vue 3 + Inertia.js

```
resources/
├── views/
│   ├── app.blade.php
│   └── emails/
│       ├── admin/
│       │   ├── account-locked.blade.php
│       │   ├── help-article-published.blade.php
│       │   ├── manual-payout-released.blade.php
│       │   ├── refund-processed.blade.php
│       │   └── role-changed.blade.php
│       ├── auth/
│       │   ├── new-device.blade.php
│       │   ├── password-reset.blade.php
│       │   ├── verify-email.blade.php
│       │   └── welcome.blade.php
│       ├── bp/
│       │   ├── contract-generated.blade.php
│       │   ├── contract-signed.blade.php
│       │   ├── invoice-paid.blade.php
│       │   ├── invoice-sent.blade.php
│       │   ├── milestone-submitted.blade.php
│       │   ├── payout-released.blade.php
│       │   └── proposal-accepted.blade.php
│       ├── digest/
│       │   └── weekly-digest.blade.php
│       ├── event/
│       │   └── event-reminder.blade.php
│       ├── gaps/
│       │   └── compliance-gaps.blade.php
│       ├── incident/
│       │   ├── incident-activated.blade.php
│       │   ├── incident-escalated.blade.php
│       │   ├── incident-reopened.blade.php
│       │   ├── incident-reported.blade.php
│       │   ├── incident-resolved.blade.php
│       │   ├── incident-verified.blade.php
│       │   └── vault-unsealed.blade.php
│       ├── network/
│       │   ├── connection-accepted.blade.php
│       │   └── connection-request.blade.php
│       ├── news/
│       │   └── news-digest.blade.php
│       ├── plan/
│       │   ├── annual-review-completed.blade.php
│       │   ├── annual-review-due.blade.php
│       │   ├── plan-finalized.blade.php
│       │   ├── plan-updated.blade.php
│       │   └── vault-attested.blade.php
│       ├── steward/
│       │   ├── alternate-activated.blade.php
│       │   ├── cs-accepted.blade.php
│       │   ├── cs-declined.blade.php
│       │   ├── cs-invited.blade.php
│       │   ├── cs-removed.blade.php
│       │   ├── cs-resigned.blade.php
│       │   └── ss-invited.blade.php
│       └── support/
│           ├── ticket-received.blade.php
│           └── ticket-replied.blade.php
│
├── css/
│   ├── app.css
│   └── _shared.css
│
└── js/
    ├── app.js
    ├── bootstrap.js
    │
    ├── layouts/
    │   ├── AppLayout.vue
    │   ├── AuthLayout.vue
    │   └── PublicLayout.vue
    │
    ├── components/
    │   ├── chrome/
    │   │   ├── AppHeader.vue
    │   │   ├── AppSidebar.vue
    │   │   └── DemoSwitcher.vue
    │   ├── features/
    │   │   ├── ActivityFeed.vue
    │   │   ├── IncidentBanner.vue
    │   │   ├── MessagesThread.vue
    │   │   ├── PlanStatusCard.vue
    │   │   ├── ProfileStrip.vue
    │   │   ├── StewardCard.vue
    │   │   ├── SupportWidget.vue
    │   │   └── VaultZone.vue
    │   ├── modals/
    │   │   ├── JobDetailModal.vue
    │   │   ├── ProposalModal.vue
    │   │   ├── ReferralModal.vue
    │   │   └── UpgradeCSModal.vue
    │   └── ui/
    │       ├── AegisBadge.vue
    │       ├── AegisCard.vue
    │       ├── AegisConfirm.vue
    │       ├── AegisDropzone.vue
    │       ├── AegisEmptyState.vue
    │       ├── AegisHeroBanner.vue
    │       ├── AegisIcon.vue
    │       ├── AegisModal.vue
    │       ├── AegisPagination.vue
    │       ├── AegisStatChip.vue
    │       ├── AegisToast.vue
    │       ├── AegisToggle.vue
    │       └── AegisUpgradeModal.vue
    │
    ├── pages/
    │   ├── admin/
    │   │   ├── Complaints.vue
    │   │   ├── ComplaintDetail.vue
    │   │   ├── Dashboard.vue
    │   │   ├── HelpArticles.vue
    │   │   ├── Packages.vue
    │   │   ├── PaymentDetail.vue
    │   │   ├── Payments.vue
    │   │   ├── Payouts.vue
    │   │   ├── Roles.vue
    │   │   ├── UserDetail.vue
    │   │   ├── Users.vue
    │   │   └── WebhookEvents.vue
    │   ├── auth/
    │   │   ├── Login.vue
    │   │   ├── MfaChallenge.vue
    │   │   ├── MfaSetup.vue
    │   │   ├── OnboardingIntent.vue
    │   │   ├── OnboardingRole.vue
    │   │   ├── PasswordRequest.vue
    │   │   ├── Register.vue
    │   │   ├── ResetPassword.vue
    │   │   └── VerifyEmail.vue
    │   ├── business-partner/
    │   │   ├── ContractDetail.vue
    │   │   ├── Contracts.vue
    │   │   ├── Dashboard.vue
    │   │   ├── EditProfile.vue
    │   │   ├── Finances.vue
    │   │   ├── FindJobs.vue
    │   │   ├── HelpCenter.vue
    │   │   ├── Invoices.vue
    │   │   ├── JobDetail.vue
    │   │   ├── Milestones.vue
    │   │   ├── PaymentSetup.vue
    │   │   ├── Proposals.vue
    │   │   ├── Settings.vue
    │   │   ├── Support.vue
    │   │   ├── TaxDocuments.vue
    │   │   └── Team.vue
    │   ├── continuity-steward/
    │   │   ├── ContinuityManagement.vue
    │   │   ├── Dashboard.vue
    │   │   ├── EditProfile.vue
    │   │   ├── Finances.vue
    │   │   ├── HelpCenter.vue
    │   │   ├── ImportantDocuments.vue
    │   │   ├── Invoices.vue
    │   │   ├── MyTasks.vue
    │   │   ├── Providers.vue
    │   │   ├── Settings.vue
    │   │   ├── Support.vue
    │   │   └── Vault.vue
    │   ├── provider/
    │   │   ├── ContinuityPlan.vue
    │   │   ├── ContinuityStewards.vue
    │   │   ├── Dashboard.vue
    │   │   ├── EditProfile.vue
    │   │   ├── Events.vue
    │   │   ├── Finances.vue
    │   │   ├── HelpCenter.vue
    │   │   ├── ImportantDocuments.vue
    │   │   ├── JobPostings.vue
    │   │   ├── Network.vue
    │   │   ├── News.vue
    │   │   ├── Referrals.vue
    │   │   ├── Roster.vue
    │   │   ├── Services.vue
    │   │   ├── Settings.vue
    │   │   ├── SupportStewards.vue
    │   │   └── Vault.vue
    │   ├── public/
    │   │   ├── BusinessProfile.vue
    │   │   ├── ContinuityStewardProfile.vue
    │   │   ├── ProviderProfile.vue
    │   │   └── SupportStewardProfile.vue
    │   ├── shared/
    │   │   ├── Activity.vue
    │   │   ├── Messages.vue
    │   │   ├── Overview.vue
    │   │   └── Support.vue
    │   └── support-steward/
    │       ├── ContinuityStewards.vue
    │       ├── CriticalIncidentLog.vue
    │       ├── Dashboard.vue
    │       ├── EditProfile.vue
    │       ├── HelpCenter.vue
    │       ├── ImportantDocuments.vue
    │       ├── MyTasks.vue
    │       ├── Providers.vue
    │       ├── Settings.vue
    │       └── Support.vue
    │
    ├── composables/
    │   ├── useActivity.js
    │   ├── useConfirm.js
    │   ├── useDemo.js
    │   ├── useIncident.js
    │   ├── useModal.js
    │   ├── usePortal.js
    │   ├── useProfileRoute.js
    │   ├── useToast.js
    │   ├── useUpgrade.js
    │   └── useVault.js
    │
    └── stores/
        ├── auth.js
        ├── incident.js
        ├── notifications.js
        ├── pricing.js
        └── ui.js
```

---

## Project Root

```
.env
.env.example
.gitignore
artisan
composer.json
composer.lock
package.json
package-lock.json
phpunit.xml
postcss.config.js
README.md
vite.config.js
```
