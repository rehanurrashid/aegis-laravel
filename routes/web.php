<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\MfaController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Public\PublicPageController;
use App\Http\Controllers\Public\PublicInteractionController;
use App\Http\Controllers\Provider\ContinuityPlanController;
use App\Http\Controllers\Provider\DocumentsController;
use App\Http\Controllers\Provider\DashboardController as ProviderDashboardController;
use App\Http\Controllers\Provider\FinancesController as ProviderFinancesController;
use App\Http\Controllers\Provider\IncidentController as ProviderIncidentController;
use App\Http\Controllers\Provider\JobPostingsController as ProviderJobPostingsController;
use App\Http\Controllers\Provider\NetworkController;
use App\Http\Controllers\Provider\ProfileController as ProviderProfileController;
use App\Http\Controllers\Provider\ProviderCredentialController;
use App\Http\Controllers\Provider\CeuRequirementController;
use App\Http\Controllers\Provider\ReferralsController;
use App\Http\Controllers\Provider\ServicesController;
use App\Http\Controllers\Provider\SettingsController as ProviderSettingsController;
use App\Http\Controllers\Provider\ContinuityStewardController;
use App\Http\Controllers\Provider\VaultController;
use App\Http\Controllers\ContinuitySteward\DashboardController as CsDashboardController;
use App\Http\Controllers\ContinuitySteward\ContinuityManagementController;
use App\Http\Controllers\ContinuitySteward\DocumentsController as CsDocumentsController;
use App\Http\Controllers\ContinuitySteward\FinancesController as CsFinancesController;
use App\Http\Controllers\ContinuitySteward\ProfileController as CsProfileController;
use App\Http\Controllers\ContinuitySteward\ProvidersController as CsProvidersController;
use App\Http\Controllers\ContinuitySteward\SettingsController as CsSettingsController;
use App\Http\Controllers\ContinuitySteward\TasksController as CsTasksController;
use App\Http\Controllers\ContinuitySteward\VaultController as CsVaultController;
use App\Http\Controllers\SupportSteward\DashboardController as SsDashboardController;
use App\Http\Controllers\SupportSteward\DocumentsController as SsDocumentsController;
use App\Http\Controllers\SupportSteward\CriticalIncidentController as SsCriticalIncidentController;
use App\Http\Controllers\SupportSteward\ProfileController as SsProfileController;
use App\Http\Controllers\SupportSteward\ProvidersController as SsProvidersController;
use App\Http\Controllers\SupportSteward\SettingsController as SsSettingsController;
use App\Http\Controllers\SupportSteward\TasksController as SsTasksController;
use App\Http\Controllers\BusinessPartner\ContractsController;
use App\Http\Controllers\BusinessPartner\DashboardController as BpDashboardController;
use App\Http\Controllers\BusinessPartner\FinancesController as BpFinancesController;
use App\Http\Controllers\BusinessPartner\InvoicesController;
use App\Http\Controllers\BusinessPartner\JobsController;
use App\Http\Controllers\BusinessPartner\MilestonesController;
use App\Http\Controllers\BusinessPartner\PaymentSetupController as BpPaymentController;
use App\Http\Controllers\BusinessPartner\ProfileController as BpProfileController;
use App\Http\Controllers\BusinessPartner\ProposalsController;
use App\Http\Controllers\BusinessPartner\SettingsController as BpSettingsController;
use App\Http\Controllers\BusinessPartner\TeamController;
use App\Http\Controllers\Admin\ComplaintsController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\HelpArticlesController;
use App\Http\Controllers\Admin\PackagesController;
use App\Http\Controllers\Admin\PaymentsController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Shared\ActivityController;
use App\Http\Controllers\Shared\MessagesController;
use App\Http\Controllers\Shared\MessageAttachmentController;
use App\Http\Controllers\Shared\OverviewController;
use App\Http\Controllers\Shared\SupportController;
use App\Http\Controllers\Public\ProfileController as PublicProfileController;

// Newly wired controllers (previously built but unrouted) + RosterController import fix
use App\Http\Controllers\Provider\RosterController;
use App\Http\Controllers\Provider\NewsController;
use App\Http\Controllers\ContinuitySteward\IncidentsController as CsIncidentsController;
use App\Http\Controllers\SupportSteward\ContinuityStewardsController as SsContinuityStewardsController;
use App\Http\Controllers\BusinessPartner\TaxDocumentsController;
use App\Http\Controllers\Admin\IncidentsController as AdminIncidentsController;
use App\Http\Controllers\Admin\PayoutsController as AdminPayoutsController;
use App\Http\Controllers\Auth\OnboardingController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

// ── Root redirect ─────────────────────────────────────────────────────────────
Route::get('/', function () {
    if (!auth()->check()) {
        return redirect()->route('login');
    }

    $role = auth()->user()->role;
    $role = $role instanceof \App\Enums\UserRole
        ? $role
        : \App\Enums\UserRole::tryFrom((string) $role);

    return match ($role) {
        \App\Enums\UserRole::Practitioner      => redirect()->route('provider.dashboard'),
        \App\Enums\UserRole::ContinuitySteward => redirect()->route('cs.dashboard'),
        \App\Enums\UserRole::SupportSteward    => redirect()->route('ss.dashboard'),
        \App\Enums\UserRole::BusinessPartner   => redirect()->route('bp.dashboard'),
        \App\Enums\UserRole::Admin             => redirect()->route('admin.dashboard'),
        default                                => redirect()->route('login'),
    };
})->name('home');

// ── Marketing pages (no auth) ───────────────────────────────────────────────────
Route::get('/about', [PublicPageController::class, 'about'])->name('about');
Route::get('/pricing', [PublicPageController::class, 'pricing'])->name('pricing');
Route::get('/contact', [PublicPageController::class, 'contact'])->name('contact');
Route::post('/contact', [PublicPageController::class, 'sendContact'])->name('contact.send');

// ── Auth (guest only) ─────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
    Route::get('/forgot-password', [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');

    // Onboarding (post-registration intent + role selection)
    Route::get('/onboarding', [OnboardingController::class, 'showIntent'])->name('onboarding.intent');
    Route::post('/onboarding', [OnboardingController::class, 'submitIntent'])->name('onboarding.intent.store');
    Route::get('/onboarding/role', [OnboardingController::class, 'showRole'])->name('onboarding.role');
    Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// ── Email verification (authenticated; verify link is signed) ─────────────────
Route::middleware('auth')->group(function () {
    Route::get('/email/verify', [VerifyEmailController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])
        ->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [VerifyEmailController::class, 'resend'])
        ->middleware('throttle:6,1')->name('verification.resend');
});

// ── MFA challenge (session-gated, no auth middleware yet) ─────────────────────
Route::get('/mfa/challenge', [MfaController::class, 'showChallenge'])->name('mfa.challenge');
Route::post('/mfa/challenge', [MfaController::class, 'challenge'])->name('mfa.challenge.store');

// ── Provider Portal ───────────────────────────────────────────────────────────
Route::middleware(['auth', 'role:practitioner', 'check.locked'])
    ->prefix('provider')
    ->name('provider.')
    ->group(function () {

        // Dashboard
        Route::get('/dashboard', [ProviderDashboardController::class, 'index'])->name('dashboard');

        // Continuity Plan
        Route::get('/continuity-plan', [ContinuityPlanController::class, 'index'])->name('plan.index');
        Route::post('/continuity-plan', [ContinuityPlanController::class, 'store'])->name('plan.store');
        Route::post('/continuity-plan/sign', [ContinuityPlanController::class, 'sign'])->name('plan.sign');
        Route::post('/continuity-plan/attest', [ContinuityPlanController::class, 'attest'])->name('plan.attest');
        Route::post('/continuity-plan/annual-review/start', [ContinuityPlanController::class, 'reviewStart'])->name('plan.review.start');
        Route::post('/continuity-plan/annual-review/complete', [ContinuityPlanController::class, 'reviewComplete'])->name('plan.review.complete');

        // Continuity Stewards
        Route::get('/continuity-stewards', [ContinuityStewardController::class, 'csIndex'])->name('stewards.index');
        Route::post('/continuity-stewards/invite', [ContinuityStewardController::class, 'csInvite'])->name('stewards.invite');
        Route::delete('/continuity-stewards/{steward}', [ContinuityStewardController::class, 'csRemove'])->name('stewards.remove');
        Route::post('/continuity-stewards/{steward}/authorize', [ContinuityStewardController::class, 'csAuthorize'])->name('stewards.authorize');

        // Support Stewards
        Route::get('/support-stewards', [ContinuityStewardController::class, 'ssIndex'])->name('ss.index');
        Route::post('/support-stewards/invite', [ContinuityStewardController::class, 'ssInvite'])->name('ss.invite');
        Route::delete('/support-stewards/{steward}', [ContinuityStewardController::class, 'ssRemove'])->name('ss.remove');

        // Vault
        Route::get('/vault', [VaultController::class, 'index'])->name('vault.index');
        Route::post('/vault/upload', [VaultController::class, 'upload'])->name('vault.upload');
        Route::post('/vault/attest', [VaultController::class, 'attest'])->name('vault.attest');
        Route::get('/vault/{item}/download', [VaultController::class, 'download'])->name('vault.download');
        Route::post('/vault/{item}/permissions', [VaultController::class, 'permissions'])->name('vault.permissions');
        Route::delete('/vault/{item}', [VaultController::class, 'destroy'])->name('vault.destroy');

        // Network
        Route::get('/network', [NetworkController::class, 'index'])->name('network.index');
        Route::post('/network/connect', [NetworkController::class, 'connect'])->name('network.connect');
        Route::post('/network/requests/{networkRequest}/accept', [NetworkController::class, 'accept'])->name('network.accept');
        Route::post('/network/requests/{networkRequest}/decline', [NetworkController::class, 'decline'])->name('network.decline');
        Route::delete('/network/{connection}', [NetworkController::class, 'disconnect'])->name('network.disconnect');
        Route::post('/network/invite', [NetworkController::class, 'invite'])->name('network.invite');
        Route::post('/network/shadow/add', [NetworkController::class, 'addShadow'])->name('network.shadow.add');

        // Referrals
        Route::get('/referrals', [ReferralsController::class, 'index'])->name('referrals.index');
        Route::post('/referrals', [ReferralsController::class, 'store'])->name('referrals.store');
        Route::post('/referrals/{referral}/accept', [ReferralsController::class, 'accept'])->name('referrals.accept');
        Route::post('/referrals/{referral}/decline', [ReferralsController::class, 'decline'])->name('referrals.decline');
        Route::post('/referrals/{referral}/cancel', [ReferralsController::class, 'cancel'])->name('referrals.cancel');
        Route::post('/referrals/{referral}/complete', [ReferralsController::class, 'complete'])->name('referrals.complete');

        // Services (requires Practice tier + services mode)
        Route::middleware('services.mode')->group(function () {
            Route::get('/services', [ServicesController::class, 'index'])->name('services.index');
            Route::post('/services', [ServicesController::class, 'store'])->name('services.store');
            Route::put('/services/{service}', [ServicesController::class, 'update'])->name('services.update');
            Route::delete('/services/{service}', [ServicesController::class, 'destroy'])->name('services.destroy');
            Route::post('/services/{service}/requests/{serviceRequest}/accept', [ServicesController::class, 'acceptRequest'])->name('services.request.accept');
            Route::post('/services/{service}/requests/{serviceRequest}/decline', [ServicesController::class, 'declineRequest'])->name('services.request.decline');
        });

        // Support Requests (Job Postings)
        Route::get('/support-services', [ProviderJobPostingsController::class, 'index'])->name('jobs.index');
        Route::post('/support-services', [ProviderJobPostingsController::class, 'store'])->name('jobs.store');
        Route::put('/support-services/{job}', [ProviderJobPostingsController::class, 'update'])->name('jobs.update');
        Route::post('/support-services/{job}/status', [ProviderJobPostingsController::class, 'setStatus'])->name('jobs.status');
        Route::delete('/support-services/{job}', [ProviderJobPostingsController::class, 'destroy'])->name('jobs.destroy');
        Route::post('/support-services/{job}/proposals/{proposal}/accept', [ProviderJobPostingsController::class, 'acceptProposal'])->name('jobs.proposal.accept');
        Route::post('/support-services/{job}/proposals/{proposal}/decline', [ProviderJobPostingsController::class, 'declineProposal'])->name('jobs.proposal.decline');
        Route::post('/support-services/{job}/proposals/{proposal}/stage', [ProviderJobPostingsController::class, 'setProposalStage'])->name('jobs.proposal.stage');
        Route::post('/support-services/{job}/proposals/{proposal}/notes', [ProviderJobPostingsController::class, 'setProposalNotes'])->name('jobs.proposal.notes');
        Route::post('/support-services/contracts/{contract}/cancel', [ProviderJobPostingsController::class, 'cancelContract'])->name('jobs.contract.cancel');

        // Continuity Documents
        Route::get('/important-documents', [DocumentsController::class, 'index'])->name('documents.index');
        Route::post('/important-documents/request', [DocumentsController::class, 'request'])->name('documents.request');
        Route::post('/important-documents/{document}/sign', [DocumentsController::class, 'sign'])->name('documents.sign');

        // Critical Incident Activation
        Route::post('/activate', [ProviderIncidentController::class, 'activate'])->name('incident.activate');

        // CEUs
        Route::post('/ceus', [RosterController::class, "upsert"])->name('ceus.store');
        Route::put('/ceus/{ceu}', [RosterController::class, "upsert"])->name('ceus.update');
        Route::delete('/ceus/{ceu}', [RosterController::class, "deleteCeu"])->name('ceus.destroy');

        // CEU Requirements (jurisdictional CEU obligations)
        Route::post('/ceu-requirements', [CeuRequirementController::class, 'store'])->name('ceu_requirements.store');
        Route::put('/ceu-requirements/{requirement}', [CeuRequirementController::class, 'update'])->name('ceu_requirements.update');
        Route::delete('/ceu-requirements/{requirement}', [CeuRequirementController::class, 'destroy'])->name('ceu_requirements.destroy');

        // Provider Credentials (licenses, certifications, insurance)
        Route::post('/credentials', [ProviderCredentialController::class, 'store'])->name('credentials.store');
        Route::put('/credentials/{credential}', [ProviderCredentialController::class, 'update'])->name('credentials.update');
        Route::delete('/credentials/{credential}', [ProviderCredentialController::class, 'destroy'])->name('credentials.destroy');
        Route::get('/credentials/{credential}/download', [ProviderCredentialController::class, 'download'])->name('credentials.download');
        Route::delete('/credentials/{credential}/document', [ProviderCredentialController::class, 'destroyDocument'])->name('credentials.document.destroy');

        // News & Events
        Route::get('/news', [NewsController::class, 'index'])->name('news.index');
        Route::get('/news/library', [NewsController::class, 'library'])->name('news.library');
        Route::get('/news/events', [NewsController::class, 'events'])->name('news.events');
        Route::post('/news', [NewsController::class, 'storePost'])->name('news.store');
        Route::post('/news/{post}/comment', [NewsController::class, 'comment'])->name('news.comment');
        Route::post('/news/{post}/react', [NewsController::class, 'react'])->name('news.react');
        Route::post('/news/{post}/vote', [NewsController::class, 'votePoll'])->name('news.vote');
        Route::post('/news/events/{event}/rsvp', [NewsController::class, 'rsvp'])->name('news.rsvp');

        // Profile
        Route::get('/profile', [ProviderProfileController::class, 'index'])->name('profile.index');
        Route::put('/profile/basic', [ProviderProfileController::class, 'updateBasic'])->name('profile.basic');
        Route::post('/profile/avatar', [ProviderProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
        Route::delete('/profile/avatar', [ProviderProfileController::class, 'removeAvatar'])->name('profile.avatar.destroy');
        Route::put('/profile/specialties', [ProviderProfileController::class, 'updateSpecialties'])->name('profile.specialties');
        Route::put('/profile/services', [ProviderProfileController::class, 'updateServices'])->name('profile.services');
        Route::put('/profile/approaches', [ProviderProfileController::class, 'updateApproaches'])->name('profile.approaches');
        Route::put('/profile/fees', [ProviderProfileController::class, 'updateFees'])->name('profile.fees');
        Route::put('/profile/availability', [ProviderProfileController::class, 'updateAvailability'])->name('profile.availability');
        Route::put('/profile/privacy', [ProviderProfileController::class, 'updatePrivacy'])->name('profile.privacy');
        Route::put('/profile/network-preferences', [ProviderProfileController::class, 'updateNetwork'])->name('profile.network');
        Route::put('/profile/languages', [ProviderProfileController::class, 'updateLanguages'])->name('profile.languages');
        Route::put('/profile/licensed-states', [ProviderProfileController::class, 'updateLicensedStates'])->name('profile.licensed-states');
        Route::put('/profile/education', [ProviderProfileController::class, 'updateEducation'])->name('profile.education');
        Route::put('/profile/network-partners', [ProviderProfileController::class, 'updateNetworkPartners'])->name('profile.network-partners');
        Route::put('/profile/ai-settings', [ProviderProfileController::class, 'updateAiSettings'])->name('profile.ai-settings');
        Route::put('/profile/demographics', [ProviderProfileController::class, 'updateDemographics'])->name('profile.demographics');
        Route::post('/profile/private-note', [ProviderProfileController::class, 'savePrivateNote'])->name('profile.private-note');

        // Finances
        Route::get('/finances', [ProviderFinancesController::class, 'index'])->name('finances.index');
        Route::post('/finances/payment-method', [ProviderFinancesController::class, 'storePaymentMethod'])->name('finances.payment.store');

        // Settings
        Route::get('/settings', [ProviderSettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings/notifications', [ProviderSettingsController::class, 'updateNotifications'])->name('settings.notifications');
        Route::put('/settings/password', [PasswordResetController::class, 'change'])->name('settings.password');
        Route::delete('/settings/account', [ProviderSettingsController::class, 'deleteAccount'])->name('settings.account.delete');
        Route::post('/settings/mfa/enable', [MfaController::class, 'enable'])->name('settings.mfa.enable');
        Route::post('/settings/mfa/verify', [MfaController::class, 'verify'])->name('settings.mfa.verify');
        Route::post('/settings/mfa/disable', [MfaController::class, 'disable'])->name('settings.mfa.disable');

        // Shared pages — portal-prefixed URLs
        Route::get('/overview',  [OverviewController::class,  'index'])->name('overview');
        Route::get('/messages',  [MessagesController::class,  'index'])->name('messages');
        Route::get('/activity',  [ActivityController::class,  'index'])->name('activity');
        Route::get('/support',   [SupportController::class,   'index'])->name('support');
    });

// ── Continuity Steward Portal ─────────────────────────────────────────────────
Route::middleware(['auth', 'role:continuity_steward', 'check.locked'])
    ->prefix('continuity-steward')
    ->name('cs.')
    ->group(function () {

        Route::get('/dashboard', [CsDashboardController::class, 'index'])->name('dashboard');

        // Providers (practitioners this CS is assigned to)
        Route::get('/providers', [CsProvidersController::class, 'index'])->name('providers.index');
        Route::get('/providers/{provider}/plan', [CsProvidersController::class, 'plan'])->name('providers.plan');
        Route::post('/providers/{provider}/plan/countersign', [CsProvidersController::class, 'countersign'])->name('providers.plan.countersign');

        // Tasks
        Route::get('/my-tasks', [CsTasksController::class, 'index'])->name('tasks.index');
        Route::post('/my-tasks/{task}/complete', [CsTasksController::class, 'complete'])->name('tasks.complete');
        Route::post('/my-tasks/{task}/flag', [CsTasksController::class, 'flag'])->name('tasks.flag');
        Route::post('/my-tasks/{task}/note', [CsTasksController::class, 'addNote'])->name('tasks.note');

        // Continuity Management (incidents)
        Route::get('/continuity-management', [ContinuityManagementController::class, 'index'])->name('continuity.index');
        Route::post('/continuity-management/{incident}/verify', [ContinuityManagementController::class, 'verify'])->name('continuity.verify');
        Route::post('/continuity-management/{incident}/activate', [ContinuityManagementController::class, 'activate'])->name('continuity.activate');
        Route::post('/continuity-management/{incident}/close', [ContinuityManagementController::class, 'close'])->name('continuity.close');
        Route::post('/continuity-management/{incident}/update', [ContinuityManagementController::class, 'update'])->name('continuity.update');

        // Incidents (dedicated read view + steward update; complements continuity-management actions)
        Route::get('/incidents', [CsIncidentsController::class, 'index'])->name('incidents.index');
        Route::get('/incidents/{incident}', [CsIncidentsController::class, 'show'])->name('incidents.show');
        Route::post('/incidents/{incident}/update', [CsIncidentsController::class, 'postUpdate'])->name('incidents.update');

        // Vault (emergency access — incident.active gate applied per-action in controller)
        Route::get('/vault/{plan}', [CsVaultController::class, 'index'])->name('vault.index');
        Route::get('/vault/{plan}/{item}/download', [CsVaultController::class, 'download'])->name('vault.download');

        // Documents
        Route::get('/important-documents', [CsDocumentsController::class, 'index'])->name('documents.index');

        // Finances
        Route::get('/finances', [CsFinancesController::class, 'index'])->name('finances.index');
        Route::post('/finances/invoice', [CsFinancesController::class, 'storeInvoice'])->name('finances.invoice.store');
        Route::post('/finances/fee-amendment', [CsFinancesController::class, 'feeAmendment'])->name('finances.amend');

        // Profile
        Route::get('/profile', [CsProfileController::class, 'index'])->name('profile.index');
        Route::put('/profile', [CsProfileController::class, 'update'])->name('profile.update');

        // Settings
        Route::get('/settings', [CsSettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings/notifications', [CsSettingsController::class, 'updateNotifications'])->name('settings.notifications');
        Route::put('/settings/password', [PasswordResetController::class, 'change'])->name('settings.password');
        Route::post('/settings/mfa/enable', [MfaController::class, 'enable'])->name('settings.mfa.enable');
        Route::post('/settings/mfa/verify', [MfaController::class, 'verify'])->name('settings.mfa.verify');
        Route::post('/settings/mfa/disable', [MfaController::class, 'disable'])->name('settings.mfa.disable');

        // Shared pages — portal-prefixed URLs
        Route::get('/overview',  [OverviewController::class,  'index'])->name('overview');
        Route::get('/messages',  [MessagesController::class,  'index'])->name('messages');
        Route::get('/activity',  [ActivityController::class,  'index'])->name('activity');
        Route::get('/support',   [SupportController::class,   'index'])->name('support');
    });

// ── Support Steward Portal ────────────────────────────────────────────────────
Route::middleware(['auth', 'role:support_steward', 'check.locked'])
    ->prefix('support-steward')
    ->name('ss.')
    ->group(function () {

        Route::get('/dashboard', [SsDashboardController::class, 'index'])->name('dashboard');

        // Providers
        Route::get('/providers', [SsProvidersController::class, 'index'])->name('providers.index');
        Route::get('/providers/{provider}', [SsProvidersController::class, 'show'])->name('providers.show');

        // Continuity Stewards visible to SS
        Route::get('/continuity-stewards', [SsProvidersController::class, 'continuityStewarded'])->name('cs.index');
        // Dedicated CS directory (standalone controller)
        Route::get('/continuity-stewards/directory', [SsContinuityStewardsController::class, 'index'])->name('cs.directory');

        // Tasks
        Route::get('/my-tasks', [SsTasksController::class, 'index'])->name('tasks.index');
        Route::post('/my-tasks/{task}/complete', [SsTasksController::class, 'complete'])->name('tasks.complete');

        // Critical Incident Log
        Route::get('/critical-incident-log', [SsCriticalIncidentController::class, 'index'])->name('incidents.index');
        Route::post('/critical-incident-log', [SsCriticalIncidentController::class, 'store'])->name('incidents.store');
        Route::post('/critical-incident-log/{incident}/update', [SsCriticalIncidentController::class, 'update'])->name('incidents.update');

        // Documents
        Route::get('/important-documents', [SsDocumentsController::class, 'index'])->name('documents.index');

        // Profile
        Route::get('/profile', [SsProfileController::class, 'index'])->name('profile.index');
        Route::put('/profile', [SsProfileController::class, 'update'])->name('profile.update');

        // Settings
        Route::get('/settings', [SsSettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings/notifications', [SsSettingsController::class, 'updateNotifications'])->name('settings.notifications');
        Route::put('/settings/password', [PasswordResetController::class, 'change'])->name('settings.password');
        Route::post('/settings/mfa/enable', [MfaController::class, 'enable'])->name('settings.mfa.enable');
        Route::post('/settings/mfa/verify', [MfaController::class, 'verify'])->name('settings.mfa.verify');
        Route::post('/settings/mfa/disable', [MfaController::class, 'disable'])->name('settings.mfa.disable');

        // Shared pages — portal-prefixed URLs
        Route::get('/overview',  [OverviewController::class,  'index'])->name('overview');
        Route::get('/messages',  [MessagesController::class,  'index'])->name('messages');
        Route::get('/activity',  [ActivityController::class,  'index'])->name('activity');
        Route::get('/support',   [SupportController::class,   'index'])->name('support');
    });

// ── Business Partner Portal ───────────────────────────────────────────────────
Route::middleware(['auth', 'role:business_partner', 'check.locked'])
    ->prefix('business')
    ->name('bp.')
    ->group(function () {

        Route::get('/dashboard', [BpDashboardController::class, 'index'])->name('dashboard');

        // Job Board
        Route::get('/find-jobs', [JobsController::class, 'index'])->name('jobs.index');
        Route::post('/find-jobs/{job}/save', [JobsController::class, 'save'])->name('jobs.save');
        Route::post('/find-jobs/{job}/propose', [JobsController::class, 'propose'])->name('jobs.propose');

        // Proposals
        Route::get('/proposals', [ProposalsController::class, 'index'])->name('proposals.index');
        Route::delete('/proposals/{proposal}', [ProposalsController::class, 'withdraw'])->name('proposals.withdraw');

        // Contracts
        Route::get('/contracts', [ContractsController::class, 'index'])->name('contracts.index');
        Route::post('/contracts/{contract}/sign', [ContractsController::class, 'sign'])->name('contracts.sign');
        Route::post('/contracts/{contract}/cancel', [ContractsController::class, 'cancel'])->name('contracts.cancel');

        // Milestones
        Route::get('/milestones', [MilestonesController::class, 'index'])->name('milestones.index');
        Route::post('/milestones', [MilestonesController::class, 'store'])->name('milestones.store');
        Route::post('/milestones/{milestone}/submit', [MilestonesController::class, 'submit'])->name('milestones.submit');

        // Invoices
        Route::get('/invoices', [InvoicesController::class, 'index'])->name('invoices.index');
        Route::post('/invoices', [InvoicesController::class, 'store'])->name('invoices.store');
        Route::post('/invoices/{invoice}/send', [InvoicesController::class, 'send'])->name('invoices.send');
        Route::post('/invoices/{invoice}/void', [InvoicesController::class, 'void'])->name('invoices.void');

        // Finances
        Route::get('/finances', [BpFinancesController::class, 'index'])->name('finances.index');

        // Tax Documents (W-9 / 1099)
        Route::get('/tax-documents', [TaxDocumentsController::class, 'index'])->name('tax.index');
        Route::post('/tax-documents/upload', [TaxDocumentsController::class, 'upload'])->name('tax.upload');
        Route::post('/tax-documents/w9', [TaxDocumentsController::class, 'submitW9'])->name('tax.w9');
        Route::delete('/tax-documents/{document}', [TaxDocumentsController::class, 'destroy'])->name('tax.destroy');

        // Stripe Connect setup
        Route::get('/payment-setup', [BpPaymentController::class, 'index'])->name('payment.index');
        Route::post('/payment-setup/connect', [BpPaymentController::class, 'connect'])->name('payment.connect');

        // Team (agency only)
        Route::get('/team', [TeamController::class, 'index'])->name('team.index');
        Route::post('/team/invite', [TeamController::class, 'invite'])->name('team.invite');
        Route::delete('/team/{member}', [TeamController::class, 'remove'])->name('team.remove');
        Route::put('/team/{member}/role', [TeamController::class, 'updateRole'])->name('team.role');

        // Profile
        Route::get('/profile', [BpProfileController::class, 'index'])->name('profile.index');
        Route::put('/profile', [BpProfileController::class, 'update'])->name('profile.update');

        // Settings
        Route::get('/settings', [BpSettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings/notifications', [BpSettingsController::class, 'updateNotifications'])->name('settings.notifications');
        Route::put('/settings/password', [PasswordResetController::class, 'change'])->name('settings.password');
        Route::post('/settings/mfa/enable', [MfaController::class, 'enable'])->name('settings.mfa.enable');
        Route::post('/settings/mfa/verify', [MfaController::class, 'verify'])->name('settings.mfa.verify');
        Route::post('/settings/mfa/disable', [MfaController::class, 'disable'])->name('settings.mfa.disable');

        // Shared pages — portal-prefixed URLs
        Route::get('/overview',  [OverviewController::class,  'index'])->name('overview');
        Route::get('/messages',  [MessagesController::class,  'index'])->name('messages');
        Route::get('/activity',  [ActivityController::class,  'index'])->name('activity');
        Route::get('/support',   [SupportController::class,   'index'])->name('support');
    });

// ── Admin Portal ──────────────────────────────────────────────────────────────
Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Packages / Pricing
        Route::get('/packages', [PackagesController::class, 'index'])->name('packages.index');
        Route::post('/packages/{tier}/price', [PackagesController::class, 'updatePrice'])->name('packages.price');
        Route::post('/packages/{tier}/feature', [PackagesController::class, 'updateFeature'])->name('packages.feature');
        Route::post('/packages/{tier}/limits', [PackagesController::class, 'updateLimits'])->name('packages.limits');

        // Users
        Route::get('/users', [UsersController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [UsersController::class, 'show'])->name('users.show');
        Route::post('/users/{user}/lock', [UsersController::class, 'lock'])->name('users.lock');
        Route::post('/users/{user}/unlock', [UsersController::class, 'unlock'])->name('users.unlock');
        Route::post('/users/{user}/reset-password', [UsersController::class, 'resetPassword'])->name('users.password.reset');
        Route::post('/users/{user}/role', [UsersController::class, 'updateRole'])->name('users.role');
        Route::post('/users/{user}/deactivate', [UsersController::class, 'deactivate'])->name('users.deactivate');
        Route::post('/users/{user}/restore', [UsersController::class, 'restore'])->name('users.restore');

        // Roles & Permissions
        Route::get('/roles', [RolesController::class, 'index'])->name('roles.index');
        Route::post('/roles', [RolesController::class, 'store'])->name('roles.store');
        Route::put('/roles/{role}/permissions', [RolesController::class, 'updatePermissions'])->name('roles.permissions');
        Route::delete('/roles/{role}', [RolesController::class, 'destroy'])->name('roles.destroy');

        // Payments & Payouts
        Route::get('/payments', [PaymentsController::class, 'index'])->name('payments.index');
        Route::post('/payments/{payment}/refund', [PaymentsController::class, 'refund'])->name('payments.refund');
        Route::post('/payments/{payment}/retry', [PaymentsController::class, 'retry'])->name('payments.retry');
        Route::post('/payouts/{payout}/release', [PaymentsController::class, 'releasePayout'])->name('payouts.release');

        // Payout lifecycle (dedicated controller — index/cancel/retry; release above is the single
        // BP+CS-aware endpoint, kept canonical because AdminPayoutService is BP-only)
        Route::get('/payouts', [AdminPayoutsController::class, 'index'])->name('payouts.index');
        Route::post('/payouts/{payout}/cancel', [AdminPayoutsController::class, 'cancel'])->name('payouts.cancel');
        Route::post('/payouts/{payout}/retry', [AdminPayoutsController::class, 'retry'])->name('payouts.retry');

        // Critical Incident oversight
        Route::get('/incidents', [AdminIncidentsController::class, 'index'])->name('incidents.index');
        Route::get('/incidents/{incident}', [AdminIncidentsController::class, 'show'])->name('incidents.show');
        Route::post('/incidents/{incident}/escalate', [AdminIncidentsController::class, 'escalate'])->name('incidents.escalate');
        Route::post('/incidents/{incident}/close', [AdminIncidentsController::class, 'close'])->name('incidents.close');

        // Complaints
        Route::get('/complaints', [ComplaintsController::class, 'index'])->name('complaints.index');
        Route::get('/complaints/{complaint}', [ComplaintsController::class, 'show'])->name('complaints.show');
        Route::post('/complaints/{complaint}/assign', [ComplaintsController::class, 'assign'])->name('complaints.assign');
        Route::post('/complaints/{complaint}/reply', [ComplaintsController::class, 'reply'])->name('complaints.reply');
        Route::post('/complaints/{complaint}/status', [ComplaintsController::class, 'updateStatus'])->name('complaints.status');
        Route::post('/complaints/{complaint}/escalate', [ComplaintsController::class, 'escalate'])->name('complaints.escalate');

        // Help Articles
        Route::get('/help-articles', [HelpArticlesController::class, 'index'])->name('help.index');
        Route::post('/help-articles', [HelpArticlesController::class, 'store'])->name('help.store');
        Route::put('/help-articles/{article}', [HelpArticlesController::class, 'update'])->name('help.update');
        Route::post('/help-articles/{article}/publish', [HelpArticlesController::class, 'publish'])->name('help.publish');
        Route::delete('/help-articles/{article}', [HelpArticlesController::class, 'destroy'])->name('help.destroy');
    });

// ── Shared routes (any authenticated role) ────────────────────────────────────
Route::middleware(['auth', 'check.locked'])->group(function () {
    Route::get('/overview', [OverviewController::class, 'index'])->name('overview');

    // Messages
    Route::get('/messages', [MessagesController::class, 'index'])->name('messages.index');
    Route::post('/messages', [MessagesController::class, 'store'])->name('messages.store');
    Route::post('/messages/{thread}/reply', [MessagesController::class, 'reply'])->name('messages.reply');
    Route::post('/messages/{thread}/read', [MessagesController::class, 'markRead'])->name('messages.read');
    Route::get('/messages/{message}/attachments/{index}', [MessageAttachmentController::class, 'download'])->name('messages.attachment.download')->where('index', '[0-9]+');
    Route::post('/messages/{thread}/mute',         [MessagesController::class, 'mute'])->name('messages.mute');
    Route::post('/messages/{thread}/unmute',       [MessagesController::class, 'unmute'])->name('messages.unmute');
    Route::get('/messages/{thread}/export',        [MessagesController::class, 'export'])->name('messages.export');
    Route::post('/messages/{thread}/block',        [MessagesController::class, 'block'])->name('messages.block');
    Route::post('/messages/{thread}/unblock',      [MessagesController::class, 'unblock'])->name('messages.unblock');
    Route::post('/messages/availability',          [MessagesController::class, 'setAvailability'])->name('messages.availability');
    Route::post('/messages/find-or-create',        [MessagesController::class, 'findOrCreate'])->name('messages.find-or-create');

    // Activity Feed
    Route::get('/activity', [ActivityController::class, 'index'])->name('activity.index');
    Route::post('/activity/mark-all-read', [ActivityController::class, 'markAllRead'])->name('activity.mark-all-read');
    Route::get('/activity/export', [ActivityController::class, 'export'])->name('activity.export');
    Route::post('/activity/{event}/read', [ActivityController::class, 'markRead'])->name('activity.read');

    // Support / Help Desk
    Route::get('/support', [SupportController::class, 'index'])->name('support.index');
    Route::post('/support/ticket', [SupportController::class, 'storeTicket'])->name('support.ticket.store');
    Route::post('/support/ticket/{ticket}/reply', [SupportController::class, 'replyToTicket'])->name('support.ticket.reply');
    Route::post('/support/ticket/{ticket}/close', [SupportController::class, 'closeTicket'])->name('support.ticket.close');
    Route::post('/support/feedback', [SupportController::class, 'storeFeedback'])->name('support.feedback');
});

// ── Public Profiles (no auth) ─────────────────────────────────────────────────
Route::prefix('public')->name('public.')->group(function () {
    Route::get('/provider/{slug}', [PublicProfileController::class, 'provider'])->name('provider');
    Route::get('/continuity-steward/{slug}', [PublicProfileController::class, 'continuityStewarded'])->name('cs');
    Route::get('/support-steward/{slug}', [PublicProfileController::class, 'supportSteward'])->name('ss');
    Route::get('/business/{slug}', [PublicProfileController::class, 'business'])->name('bp');
});

// ── Public Profile Interactions (auth required) ───────────────────────────────
Route::middleware('auth')->prefix('public/profiles')->name('public.profile.')->group(function () {
    Route::post('/{user}/endorse',         [PublicInteractionController::class, 'endorse'])->name('endorse');
    Route::post('/{user}/service-request', [PublicInteractionController::class, 'serviceRequest'])->name('service-request');
    Route::post('/{user}/connect',              [PublicInteractionController::class, 'connect'])->name('connect');
    Route::delete('/{networkRequest}/cancel-connect', [PublicInteractionController::class, 'cancelConnect'])->name('cancel-connect');
    Route::delete('/{connection}/disconnect',   [PublicInteractionController::class, 'disconnect'])->name('disconnect');
    // BP-specific engagement actions
    Route::post('/{user}/hire-request',     [PublicInteractionController::class, 'hireRequest'])->name('hire-request');
    Route::post('/{user}/quote-request',    [PublicInteractionController::class, 'quoteRequest'])->name('quote-request');
    Route::post('/{user}/consultation',     [PublicInteractionController::class, 'consultation'])->name('consultation');
    Route::post('/{user}/bp-review',        [PublicInteractionController::class, 'bpReview'])->name('bp-review');
});
