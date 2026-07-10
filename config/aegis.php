<?php

declare(strict_types=1);

/**
 * Aegis — Platform Configuration
 *
 * Single source of truth for pricing, tier limits, stripe price IDs, and role rules.
 * All prices in CENTS. Frontend reads via Inertia props; never inject directly into JS.
 *
 * To update a price: edit here + update Stripe Dashboard + update STRIPE_PRICE_* env vars.
 *
 * Pricing confirmed: June 2026 sign-off (AEGIS-PROJECT-CONTEXT.md §8)
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Practitioner Subscription Pricing (cents)
    | Access $29/mo · $23/mo annual ($276/yr) — save 20%
    | Practice $49/mo · $39/mo annual ($468/yr) — save 20%
    |--------------------------------------------------------------------------
    */
    'pricing' => [

        'practitioner' => [
            'access' => [
                'name'              => 'Continuity Access',
                'tagline'           => 'Essential continuity for solo practitioners.',
                'monthly_cents'     => 2900,    // $29/mo
                'annual_cents'      => 2300,    // $23/mo billed annually
                'annual_total_cents'=> 27600,   // $276/yr
                'save_label'        => 'save 20%',
                'is_popular'        => false,
                'features'          => [
                    'Continuity Plan Builder',
                    'All 7 critical incident types',
                    'Document Vault (4 zones)',
                    '1 Continuity Steward',
                    '1 Support Steward',
                    'Shadow Network (limited)',
                    'Secure messaging',
                    'Activity log',
                ],
                'locked'            => [
                    'Referrals — locked',
                    'My Services — locked',
                ],
                'hidden'            => [
                    'Job Postings — hidden',
                ],
            ],
            'practice' => [
                'name'              => 'Continuity Practice',
                'tagline'           => 'Full toolkit. The standard for active practices.',
                'monthly_cents'     => 4900,    // $49/mo
                'annual_cents'      => 3900,    // $39/mo billed annually
                'annual_total_cents'=> 46800,   // $468/yr
                'save_label'        => 'save 20%',
                'is_popular'        => true,
                'features'          => [
                    'Everything in Continuity Access, plus:',
                    'Up to 2 Continuity Stewards (Primary + Alternate)',
                    'Up to 4 Support Stewards',
                    'Referrals — send & receive',
                    'Full Integrative Network search',
                    'Integrative Services Mode (offer services to peers)',
                    'Business Partner directory & Job Postings',
                    'Message templates + pinning',
                    'Activity log export',
                    'Priority support & onboarding call',
                ],
                'locked'            => [],
                'hidden'            => [],
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | MAAT Professional CS Add-on (Practitioner only, requires Practice)
        | +$29/mo · +$23/mo annual ($276/yr) — save 20%
        |----------------------------------------------------------------------
        */
        'maat_addon' => [
            'name'              => 'MAAT Professional Continuity Steward Service',
            'short_name'        => 'MAAT Service',
            'tagline'           => 'A MAAT-certified, licensed, insured CS designated to your practice.',
            'requires_tier'     => 'practice',
            'monthly_cents'     => 2900,    // +$29/mo
            'annual_cents'      => 2300,    // +$23/mo annual
            'annual_total_cents'=> 27600,   // +$276/yr
            'save_label'        => 'save 20%',
            'features'          => [
                'Licensed & insured Continuity Steward, certified by MAAT',
                'Emergency response within 4 hours of incident trigger',
                'Annual CS recertification included',
                'Practice succession planning support',
                'Legal defensibility for clients and estate',
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Combo pricing (for display only — Stripe bills base + addon separately)
        | Practice + MAAT: $78/mo · $62/mo annual
        |----------------------------------------------------------------------
        */
        'practitioner_combos' => [
            'practice_maat' => [
                'label'              => 'Continuity Practice + MAAT',
                'monthly_cents'      => 7800,    // $78/mo
                'annual_cents'       => 6200,    // $62/mo annual
                'annual_total_cents' => 74400,   // $744/yr
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Business Partner Subscription
        | $69/mo · $58/mo equivalent ($690/yr — save 2 months ≈ 17%)
        |----------------------------------------------------------------------
        */
        'business_partner' => [
            'name'              => 'Business Partner',
            'tagline'           => 'For billing, legal, IT, marketing, compliance, and other professionals.',
            'monthly_cents'     => 6900,    // $69/mo
            'annual_cents'      => 5750,    // $57.50/mo equivalent
            'annual_total_cents'=> 69000,   // $690/yr
            'save_label'        => 'save 2 months',
            'note'              => 'Covers both Agency and Freelancer account types.',
            'features'          => [
                'Public directory listing at /business/<you>',
                'Browse and bid on practitioner job postings',
                'Send proposals · auto-generated contracts',
                'Milestone-based delivery with practitioner approval',
                'Stripe Connect direct payouts — Aegis never holds funds',
                'Agency or Freelancer account types',
                'Invoices, contract history, performance metrics',
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Continuity Steward — Business Account
        | $49/mo · $429/yr (≈ $35.75/mo — save ~27%)
        | Covers 2–40 practitioners. Enterprise (41+) is custom quote.
        |----------------------------------------------------------------------
        */
        'continuity_steward_business' => [
            'name'              => 'Business CS',
            'tagline'           => 'Independent paid CS account. Serve 2–40 practitioners.',
            'monthly_cents'     => 4900,    // $49/mo
            'annual_cents'      => 3575,    // $429/yr ÷ 12 ≈ $35.75
            'annual_total_cents'=> 42900,   // $429/yr
            'save_label'        => 'save ~27%',
            'scope'             => '2–40 practitioners',
            'features'          => [
                'Independent paid CS account',
                'Serve 2–40 practitioners',
                'Public profile at /steward/<you>',
                'Proactively invite practitioners',
                'Full multi-practitioner dashboard',
                'Stripe Connect for CS payouts',
                'Aegis Verified badge available',
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Continuity Steward — Invited (Free)
        |----------------------------------------------------------------------
        */
        'continuity_steward_invited' => [
            'name'       => 'Invited CS',
            'tagline'    => 'Designated by a practitioner. No public profile.',
            'price'      => 'Free',
            'scope'      => '1 practitioner',
            'note'       => 'Covered by your linked practitioner\'s subscription.',
            'features'   => [
                'Linked to one practitioner',
                'Full continuity steward tools',
                'No public profile (upgrade to Business CS to get one)',
                'Upgrade anytime to serve more practitioners',
            ],
        ],

        /*
        |----------------------------------------------------------------------
        | Support Steward — Always free, invitation-only
        |----------------------------------------------------------------------
        */
        'support_steward' => [
            'name'    => 'Support Steward',
            'price'   => 'Free',
            'note'    => 'Invitation only — covered by the practitioner\'s subscription.',
        ],

        /*
        |----------------------------------------------------------------------
        | Founding Member Perks — CONFIRMED by Dr. Chapman 2026-07-09
        |
        | Slots: first 5,000 practitioners (Access + Practice combined),
        |        first 100 Business CS, first 100 Business Partners.
        |
        | Duration: for life of active Aegis membership.
        | Cancel + re-subscribe status: PENDING Chapman confirmation.
        |
        | Spotlight placement: News page only. MAAT selects featured
        | business internally and posts — no member self-serve redemption.
        | Scheduling is rotating/availability basis.
        |
        | Pricing lock: founders retain introductory pricing through
        | Aegis' first platform-wide pricing adjustment.
        |
        | Implementation note: users.founding_tier column + assignment
        | logic NOT YET BUILT — awaiting cancel/re-subscribe answer
        | before building the DB schema.
        |----------------------------------------------------------------------
        */
        'founding_member' => [
            'access' => [
                'label'       => '2 additional CS for life of membership + badge + early access + pricing lock + spotlight',
                'max_slot'    => 5000,
                'cs_bonus'    => 2,        // extra CS slots on top of tier cap
                'pricing_lock'=> true,     // retain intro price through first platform adjustment
                'badge'       => true,
                'early_access'=> true,
                'spotlight'   => 1,        // one spotlight inclusion; MAAT-driven, News page
            ],
            'practice' => [
                'label'       => '2 additional CS for life of membership + badge + early access + pricing lock + spotlight',
                'max_slot'    => 5000,
                'cs_bonus'    => 2,
                'pricing_lock'=> true,
                'badge'       => true,
                'early_access'=> true,
                'spotlight'   => 1,
            ],
            'cs' => [
                'label'       => '50% off Continuity Steward Training + badge + early access + spotlight',
                'max_slot'    => 100,
                'training_discount_pct' => 50,
                'training_discounted_slots' => 25,   // up to 25 discounted training placements
                'badge'       => true,
                'early_access'=> true,
                'spotlight'   => 1,
            ],
            'bp' => [
                'label'       => 'Badge + early access + pricing lock + spotlight',
                'max_slot'    => 100,
                'pricing_lock'=> true,
                'badge'       => true,
                'early_access'=> true,
                'spotlight'   => 1,
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Stripe Price ID → Tier Mapping
    |--------------------------------------------------------------------------
    | Used by StripeEventListener::handleSubscriptionUpdated() to sync
    | users.tier on every subscription webhook. Also used by
    | OnboardingController::subscribe() to resolve the correct Stripe price.
    |
    | Populate STRIPE_PRICE_* env vars after creating products in Stripe Dashboard.
    | (Stripe Dashboard → Products → Create, then copy price_xxx IDs)
    |--------------------------------------------------------------------------
    */
    'stripe_price_to_tier' => [
        env('STRIPE_PRICE_ACCESS_MONTHLY')      => 'access',
        env('STRIPE_PRICE_ACCESS_ANNUAL')       => 'access',
        env('STRIPE_PRICE_PRACTICE_MONTHLY')    => 'practice',
        env('STRIPE_PRICE_PRACTICE_ANNUAL')     => 'practice',
        env('STRIPE_PRICE_BP_MONTHLY')          => 'business_partner',
        env('STRIPE_PRICE_BP_ANNUAL')           => 'business_partner',
        env('STRIPE_PRICE_CS_BUSINESS_MONTHLY') => 'cs_business',
        env('STRIPE_PRICE_CS_BUSINESS_ANNUAL')  => 'cs_business',
        env('STRIPE_PRICE_MAAT_MONTHLY')        => 'maat_addon',
        env('STRIPE_PRICE_MAAT_ANNUAL')         => 'maat_addon',
    ],

    /*
    |--------------------------------------------------------------------------
    | Roles That Require a Paid Subscription at Onboarding
    |--------------------------------------------------------------------------
    */
    'paid_roles' => [
        'practitioner',
        'business_partner',
        'continuity_steward_business',  // cs_account_type = 'business'
    ],

    /*
    |--------------------------------------------------------------------------
    | Tier Feature Flags (mirrors §8.7 in AEGIS-PROJECT-CONTEXT.md)
    |--------------------------------------------------------------------------
    */
    'tier_limits' => [
        'access' => [
            'max_continuity_stewards' => (int) env('TIER_ACCESS_MAX_CS', 1),
            'max_support_stewards'    => (int) env('TIER_ACCESS_MAX_SS', 1),
            'referrals'               => false,
            'services_mode'           => false,
            'network_search'          => 'limited',
            'job_postings'            => false,
        ],
        'practice' => [
            'max_continuity_stewards' => (int) env('TIER_PRACTICE_MAX_CS', 2),
            'max_support_stewards'    => (int) env('TIER_PRACTICE_MAX_SS', 4),
            'referrals'               => true,
            'services_mode'           => true,
            'network_search'          => 'full',
            'job_postings'            => true,
        ],
    ],

];
