<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\ContinuityPlan;
use App\Models\PlanSteward;
use App\Models\VaultItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OverviewController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $role = $user?->role?->value ?? 'guest';

        return Inertia::render('Shared/Overview', [
            'role'        => $role,
            'firstName'   => $this->firstNameOf($user?->display_name ?? ''),
            'heroEyebrow' => $this->heroEyebrowFor($role),
            'heroSub'     => $this->heroSubFor($role),
            'notice'      => $this->noticeFor($role),
            'checklist'   => $this->checklistFor($user, $role),
            'keyTerms'    => $this->keyTermsFor($role),
            'whyCards'    => $this->whyCardsFor($role),
            'howSteps'    => $this->howStepsFor($role),
            'faqs'        => $this->faqsFor($role),
        ]);
    }

    private function firstNameOf(string $displayName): string
    {
        $clean = (string) preg_replace('/^(Dr\.|Prof\.|Mr\.|Ms\.|Mrs\.)\s*/i', '', $displayName);
        $parts = explode(' ', trim($clean), 2);
        return $parts[0] !== '' ? $parts[0] : 'there';
    }

    private function heroEyebrowFor(string $role): string
    {
        return match ($role) {
            'practitioner'       => 'Welcome to Aegis · Provider Portal',
            'continuity_steward' => 'Welcome to Aegis · Continuity Steward Portal',
            'support_steward'    => 'Welcome to Aegis · Support Steward Portal',
            'business_partner'   => 'Welcome to Aegis · Business Partner Portal',
            'admin'              => 'Welcome to Aegis · Admin',
            default              => 'Welcome to Aegis',
        };
    }

    private function heroSubFor(string $role): string
    {
        return match ($role) {
            'practitioner'       => 'Aegis keeps your practice prepared for the unexpected. Build your continuity plan, designate stewards, and let your clients be cared for if life interrupts.',
            'continuity_steward' => 'You are a trusted colleague — ready to step in if a practitioner cannot. Aegis gives you the structure to do that with clarity and care.',
            'support_steward'    => 'A trusted person who can report a critical incident if needed. Aegis keeps the path clear when seconds matter.',
            'business_partner'   => 'Connect your services to the practitioners who need them. Aegis is your storefront and your settlement layer.',
            default              => 'A short orientation to Aegis for your role.',
        };
    }

    private function noticeFor(string $role): ?string
    {
        return match ($role) {
            'practitioner'       => 'Your continuity plan is your most important deliverable. <strong>Finish your setup path</strong> to ensure your clients are cared for.',
            'continuity_steward' => 'You have been entrusted with another practitioner\'s continuity. <strong>Review your roster</strong> regularly.',
            default              => null,
        };
    }

    /**
     * Setup checklist — role-aware. Each tile carries {label, desc, href, done}.
     * For practitioners we look at live plan state to mark items done.
     */
    private function checklistFor($user, string $role): array
    {
        if ($role === 'practitioner' && $user) {
            $plan = ContinuityPlan::where('practitioner_id', $user->id)->first();
            $hasPlan      = $plan !== null;
            $hasStewards  = PlanSteward::where('plan_id', $plan?->id)->exists();
            $hasVault     = VaultItem::where('practitioner_id', $user->id)->exists();
            $hasSigned    = (bool) ($plan?->signed_at);
            $hasAttested  = (bool) ($plan?->vault_attested_at);

            return [
                ['label' => 'Build your plan',      'desc' => 'Configure incident types',     'href' => '/provider/plan',     'done' => $hasPlan],
                ['label' => 'Designate stewards',   'desc' => 'Invite trusted colleagues',    'href' => '/provider/stewards', 'done' => $hasStewards],
                ['label' => 'Add to your Vault',    'desc' => 'Encrypted supplemental docs',  'href' => '/provider/vault',    'done' => $hasVault],
                ['label' => 'Sign your plan',       'desc' => 'Formally activate continuity', 'href' => '/provider/plan',     'done' => $hasSigned],
                ['label' => 'Attest vault contents','desc' => 'Annual confirmation',          'href' => '/provider/vault',    'done' => $hasAttested],
            ];
        }

        if ($role === 'continuity_steward') {
            return [
                ['label' => 'Review your roster',    'desc' => 'See who has chosen you',       'href' => '/continuity-steward/dashboard', 'done' => false],
                ['label' => 'Read each plan',        'desc' => 'Understand incident wishes',   'href' => '/continuity-steward/dashboard', 'done' => false],
                ['label' => 'Confirm contact info',  'desc' => 'Stay reachable',               'href' => '/continuity-steward/account',   'done' => false],
            ];
        }

        if ($role === 'support_steward') {
            return [
                ['label' => 'Confirm your roster',   'desc' => 'See who has designated you',   'href' => '/support-steward/dashboard', 'done' => false],
                ['label' => 'Know the report path',  'desc' => 'How to file an incident',      'href' => '/support-steward/dashboard', 'done' => false],
                ['label' => 'Stay reachable',        'desc' => 'Confirm contact info',         'href' => '/support-steward/account',   'done' => false],
            ];
        }

        if ($role === 'business_partner') {
            return [
                ['label' => 'Build your profile',    'desc' => 'Add services & credentials',   'href' => '/business-partner/profile',  'done' => false],
                ['label' => 'Connect Stripe',        'desc' => 'Onboard for payments',         'href' => '/business-partner/payments', 'done' => false],
                ['label' => 'Browse opportunities',  'desc' => 'Reply to practitioner posts',  'href' => '/business-partner/opportunities', 'done' => false],
            ];
        }

        return [];
    }

    private function keyTermsFor(string $role): array
    {
        return [
            ['term' => 'Continuity Plan',     'def' => 'Your documented preparation for a critical incident — the wishes, tasks, and contacts that activate if you cannot.'],
            ['term' => 'Continuity Steward',  'def' => 'The colleague who will care for your practice if you can\'t. They follow the plan you documented.'],
            ['term' => 'Support Steward',     'def' => 'A trusted person who reports a critical incident if needed. They are not asked to take clinical action.'],
            ['term' => 'Vault',               'def' => 'Encrypted storage of supplemental documents, opened only during a verified incident.'],
            ['term' => 'Business Partner',    'def' => 'A non-clinical service provider — billing, accounting, legal, IT — whom practitioners hire through Aegis.'],
            ['term' => 'Critical Incident',   'def' => 'A verified event (illness, injury, death) that activates a practitioner\'s continuity plan.'],
        ];
    }

    private function whyCardsFor(string $role): array
    {
        return match ($role) {
            'continuity_steward' => [
                ['title' => 'Clear expectations', 'body' => 'You know exactly what is asked of you, and when.',          'icon' => 'check-circle'],
                ['title' => 'Structured handoff', 'body' => 'When the time comes, the path is laid out — not improvised.', 'icon' => 'compass'],
                ['title' => 'Trust documented',   'body' => 'Your colleague chose you. Aegis records that choice formally.', 'icon' => 'shield'],
            ],
            'support_steward'    => [
                ['title' => 'Quiet readiness',  'body' => 'Aegis stays out of the way until you are needed.',         'icon' => 'shield'],
                ['title' => 'One clear action', 'body' => 'When you report, the right people are notified at once.', 'icon' => 'bell'],
                ['title' => 'Verified pathway', 'body' => 'No guessing, no confusion — incidents follow a known flow.', 'icon' => 'check-circle'],
            ],
            'business_partner'   => [
                ['title' => 'Reach the right clients', 'body' => 'Practitioners discover your services through Aegis.', 'icon' => 'users'],
                ['title' => 'Settlement, simplified',  'body' => 'Stripe Connect handles payments — Aegis never holds funds.', 'icon' => 'credit-card'],
                ['title' => 'Visible reputation',      'body' => 'Reviews and references are part of every relationship.', 'icon' => 'star'],
            ],
            default              => [
                ['title' => 'Continuity of care',    'body' => 'Your clients are looked after if life interrupts.',           'icon' => 'shield'],
                ['title' => 'Clarity for loved ones','body' => 'A signed plan removes guesswork during a crisis.',           'icon' => 'compass'],
                ['title' => 'Ethical responsibility','body' => 'A documented plan satisfies professional duty of care.',     'icon' => 'check-circle'],
            ],
        };
    }

    private function howStepsFor(string $role): array
    {
        return match ($role) {
            'continuity_steward' => [
                ['title' => 'Accept your role',          'body' => 'Review the practitioners who have invited you and accept the ones you are willing to serve.'],
                ['title' => 'Read each plan',            'body' => 'Familiarize yourself with each practitioner\'s incident wishes and client care preferences.'],
                ['title' => 'Stay reachable',            'body' => 'Keep your contact details current so you can be notified the moment you are needed.'],
            ],
            'support_steward'    => [
                ['title' => 'Confirm your roster',       'body' => 'See which practitioners have designated you as their Support Steward.'],
                ['title' => 'Know the report path',      'body' => 'Understand how to file a critical incident report when the time comes.'],
                ['title' => 'Stay reachable',            'body' => 'Keep your contact details current so the right people can reach you.'],
            ],
            'business_partner'   => [
                ['title' => 'Build your profile',        'body' => 'Add your services, credentials, and references.'],
                ['title' => 'Connect Stripe',            'body' => 'Onboard with Stripe Connect to receive client payments directly.'],
                ['title' => 'Respond to opportunities',  'body' => 'Reply to job postings and proposals from practitioners.'],
            ],
            default              => [
                ['title' => 'Build your plan',           'body' => 'Configure incident types and document your wishes.'],
                ['title' => 'Designate stewards',        'body' => 'Invite trusted colleagues to your roster.'],
                ['title' => 'Sign and attest',           'body' => 'Activate your plan and confirm vault contents annually.'],
            ],
        };
    }

    private function faqsFor(string $role): array
    {
        $shared = [
            ['q' => 'What if I never activate my plan?', 'a' => 'That\'s the goal. Your plan is insurance — quietly ready, rarely used.'],
            ['q' => 'How is the Vault secured?',          'a' => 'AES-256-GCM encryption; access is gated by a verified critical incident.'],
            ['q' => 'Who sees my information?',           'a' => 'Only the stewards and partners you explicitly designate, and only in the contexts you authorize.'],
        ];

        return match ($role) {
            'continuity_steward' => [
                ['q' => 'Can I decline an invitation?',           'a' => 'Yes. Accepting the role of Continuity Steward is always voluntary, and you can step down at any time before an incident.'],
                ['q' => 'What is asked of me during an incident?', 'a' => 'You follow the plan the practitioner documented. Aegis surfaces it the moment a critical incident is verified.'],
                ...$shared,
            ],
            'business_partner'   => [
                ['q' => 'How are payments handled?', 'a' => 'Stripe Connect Express. Funds flow directly between client and you — Aegis never holds money.'],
                ['q' => 'What are the fees?',         'a' => 'A standard platform fee plus Stripe\'s processing fee. See your billing page for current rates.'],
                ...$shared,
            ],
            default              => $shared,
        };
    }
}
