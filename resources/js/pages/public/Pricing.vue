<!--
  pages/public/Pricing.vue — Pricing page for Aegis.
  Route: GET /pricing (pricing)
  Layout: PublicLayout (no auth required)

  Pricing source (pricing.php / AEGIS-PROJECT-CONTEXT.md §8):
    Continuity Access:   $39/mo · $35.75/mo annual ($429/yr · save ~8%)
    Continuity Practice: $79/mo · $65.83/mo annual ($790/yr · save ~13%) ← Most Popular
    MAAT add-on:        +$29/mo · +$23/mo annual ($276/yr · save 20%) (requires Practice)
    Business CS (2–40):  $49/mo · $490/yr (save ~16%)
    Enterprise CS (41+): Custom quote
    Invited CS / SS:     Free (covered by practitioner's subscription)
    Business Partner:    $69/mo · $690/yr (save 2 months)
-->
<template>
  <Head title="Pricing — Aegis" />
  <PublicLayout>

    <!-- Hero -->
    <section class="public-hero">
      <div class="public-hero-inner">
        <div class="public-hero-eyebrow">Simple, transparent pricing</div>
        <h1 class="public-hero-title">A backup plan that pays for itself the day you need it.</h1>
        <p class="public-hero-sub">
          Choose the plan that matches your practice. Your designated Continuity Steward and
          Support Steward access their portals at no extra cost — covered by your subscription.
        </p>

        <!-- Global segmented tabs pattern -->
        <div class="tabs-segmented" style="margin-bottom: 0; margin-top: 16px;">
          <button
            class="tab-pill"
            :class="{ active: billing === 'monthly' }"
            @click="billing = 'monthly'"
          >Monthly</button>
          <button
            class="tab-pill"
            :class="{ active: billing === 'annual' }"
            @click="billing = 'annual'"
          >Annual <span class="badge-pill badge-pill--green">Save 20%</span></button>
        </div>
      </div>
    </section>

    <!-- How it works -->
    <section class="public-section public-section--alt">
      <div class="public-section-inner">
        <h2 class="public-section-title" style="text-align: center; margin-bottom: 32px;">How Aegis works</h2>
        <div class="pricing-how-grid">
          <!-- Outer wrapper is overflow:visible so the step number badge clears the card -->
          <div v-for="step in howSteps" :key="step.num" class="pricing-how-outer">
            <div class="pricing-step-num">{{ step.num }}</div>
            <div class="card pricing-how-step">
              <div class="card-body">
                <div class="pricing-step-title">
                  <AegisIcon :name="step.icon" :size="16" />
                  {{ step.title }}
                </div>
                <p class="public-card-body">{{ step.body }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Practitioner plans -->
    <section class="public-section">
      <div class="public-section-inner">
        <h2 class="public-section-title" style="text-align: center; margin-bottom: 32px;">Practitioner plans</h2>
        <div class="pricing-cards-grid">
          <!-- Outer wrapper overflow:visible so popular flag clears the card -->
          <div v-for="plan in plans" :key="plan.key" class="pricing-card-outer">
            <div v-if="plan.featured" class="pricing-popular-flag">Most popular</div>
            <div class="card pricing-card" :class="{ 'pricing-card--featured': plan.featured }">
              <div class="card-body">
                <div class="pricing-card-name">{{ plan.name }}</div>
                <div class="pricing-card-tagline">{{ plan.tagline }}</div>
                <div class="pricing-card-price">
                  <span class="pricing-amount">${{ billing === 'annual' ? plan.annual : plan.monthly }}</span>
                  <span class="pricing-period">/mo</span>
                </div>
                <div class="pricing-billed-note">
                  <template v-if="billing === 'annual'">
                    Billed ${{ plan.billedAnnual }}/yr — save 20%
                  </template>
                  <template v-else>
                    Billed monthly
                  </template>
                </div>
                <ul class="pricing-features">
                  <li v-for="f in plan.features" :key="f" class="pricing-feature">
                    <AegisIcon name="check" :size="14" />
                    {{ f }}
                  </li>
                </ul>
                <a
                  :href="route('register')"
                  class="btn btn-block"
                  :class="plan.featured ? 'btn-primary' : 'btn-outline'"
                >Get started</a>
              </div>
            </div>
          </div>
        </div>

        <!-- MAAT Add-on -->
        <div class="pricing-addon">
          <div class="pricing-addon-mark">
            <AegisIcon name="shield-check" :size="22" />
          </div>
          <div class="pricing-addon-body">
            <div class="pricing-addon-eyebrow">Add-on</div>
            <div class="pricing-addon-title">MAAT Managed Continuity Steward</div>
            <p class="pricing-addon-tagline">
              Skip recruiting a Continuity Steward. A vetted, licensed, insured MAAT professional
              steps in as your CS — on standby and ready. Requires Continuity Practice.
            </p>
            <div class="pricing-addon-features">
              <span class="pricing-addon-feature"><AegisIcon name="check" :size="12" /> Licensed &amp; insured CS</span>
              <span class="pricing-addon-feature"><AegisIcon name="check" :size="12" /> Annual standby certification</span>
              <span class="pricing-addon-feature"><AegisIcon name="check" :size="12" /> Priority incident response</span>
              <span class="pricing-addon-feature"><AegisIcon name="check" :size="12" /> Full plan execution</span>
            </div>
          </div>
          <div class="pricing-addon-price">
            <div class="pricing-addon-amount">
              +$<span>{{ billing === 'annual' ? ((props.pricing?.maat_addon?.annual_cents ?? 2300) / 100) : ((props.pricing?.maat_addon?.monthly_cents ?? 2900) / 100) }}</span><span class="pricing-period">/mo</span>
            </div>
            <div class="pricing-addon-billed">
              {{ billing === 'annual' ? 'Billed $' + ((props.pricing?.maat_addon?.annual_total_cents ?? 27600) / 100) + '/yr' : 'Billed monthly' }}
            </div>
            <a :href="route('register')" class="btn btn-primary btn-sm">Add MAAT Service</a>
            <div class="pricing-addon-req">Requires Continuity Practice</div>
          </div>
        </div>

        <!-- Founding member banner -->
        <div class="pricing-founding">
          <AegisIcon name="star" :size="20" />
          <div>
            <strong>Founding member pricing is locked in for life.</strong>
            Sign up before launch and your rate never increases, even as we add features.
          </div>
        </div>
      </div>
    </section>

    <!-- For Stewards -->
    <section class="public-section public-section--alt">
      <div class="public-section-inner">
        <h2 class="public-section-title" style="text-align: center; margin-bottom: 8px;">For Continuity &amp; Support Stewards</h2>
        <p class="public-section-body" style="text-align: center; margin-bottom: 32px;">
          Stewards designated by a practitioner ride along on that practitioner's plan at no cost.
          Independent Continuity Stewards serving multiple practices subscribe directly.
        </p>
        <div class="pricing-steward-grid">
          <div v-for="cs in stewardPlans" :key="cs.key" class="card">
            <div class="card-body">
              <div class="pricing-card-name">{{ cs.name }}</div>
              <div class="pricing-card-price" style="margin-top: 8px;">
                <span class="pricing-amount" style="font-size: 28px;">{{ cs.price }}</span>
              </div>
              <div class="pricing-billed-note">{{ cs.billing }}</div>
              <p class="public-card-body" style="margin-top: 12px;">{{ cs.desc }}</p>
              <template v-if="cs.cta">
                <a :href="route('register')" class="btn btn-outline btn-sm" style="margin-top: 16px; display: inline-flex;">
                  {{ cs.cta }}
                </a>
              </template>
              <template v-else>
                <button type="button" class="btn btn-outline btn-sm" disabled style="margin-top: 16px; opacity: 0.5; cursor: default;">
                  Invitation only
                </button>
              </template>
            </div>
          </div>
        </div>
        <div class="pricing-enterprise-note">
          <div class="pricing-enterprise-inner">
            <AegisIcon name="briefcase" :size="16" />
            <span><strong>Serving 41+ practitioners?</strong> Business CS caps at 40. For larger continuity rosters we offer a custom enterprise quote.</span>
          </div>
          <a :href="route('contact')" class="btn btn-outline btn-sm">Get a quote</a>
        </div>
      </div>
    </section>

    <!-- Business Partners -->
    <section class="public-section">
      <div class="public-section-inner">
        <h2 class="public-section-title" style="text-align: center; margin-bottom: 8px;">For Business Partners</h2>
        <p class="public-section-body" style="text-align: center; margin-bottom: 32px;">
          Practice support professionals — billing, legal, IT, marketing, compliance, accounting.
          A separate marketplace alongside the continuity flow.
        </p>
        <div class="pricing-bp-wrap">
          <div class="card">
            <div class="card-body pricing-bp-inner">
              <div>
                <div class="pricing-card-name">Business Partner</div>
                <p class="pricing-card-tagline" style="margin-top: 6px;">
                  Find and accept contracts with practitioners. Proposals, milestones, and Stripe-Connect-routed payouts — all in one place.
                </p>
                <ul class="pricing-features" style="margin-top: 16px;">
                  <li v-for="f in bpFeatures" :key="f" class="pricing-feature">
                    <AegisIcon name="check" :size="14" />
                    {{ f }}
                  </li>
                </ul>
              </div>
              <div class="pricing-bp-side">
                <div class="pricing-card-price">
                  <span class="pricing-amount">${{ billing === 'annual' ? ((props.pricing?.business_partner?.annual_cents ?? 5750) / 100) : ((props.pricing?.business_partner?.monthly_cents ?? 6900) / 100) }}</span>
                  <span class="pricing-period">/mo</span>
                </div>
                <div class="pricing-billed-note">
                  {{ billing === 'annual' ? 'Billed $' + ((props.pricing?.business_partner?.annual_total_cents ?? 69000) / 100) + '/yr · save 2 months' : 'Billed monthly' }}
                </div>
                <a :href="route('register')" class="btn btn-primary" style="width: 100%; margin-top: 16px;">
                  Get started
                </a>
                <div class="pricing-bp-note">Agency or Freelancer</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- FAQ -->
    <section class="public-section public-section--alt">
      <div class="public-section-inner public-section--narrow">
        <h2 class="public-section-title" style="text-align: center;">Frequently asked</h2>
        <p class="public-section-body" style="text-align: center; margin-bottom: 32px;">
          If the answer's not here, write to
          <a href="mailto:support@maatpracticefirm.com" class="pricing-faq-email">support@maatpracticefirm.com</a>
          and a human will reply.
        </p>
        <div class="pricing-faqs">
          <div v-for="faq in faqs" :key="faq.q" class="pricing-faq">
            <button
              class="pricing-faq-q"
              @click="activeFaq = activeFaq === faq.q ? null : faq.q"
            >
              <span>{{ faq.q }}</span>
              <AegisIcon
                :name="activeFaq === faq.q ? 'chevron-up' : 'chevron-down'"
                :size="16"
              />
            </button>
            <div v-show="activeFaq === faq.q" class="pricing-faq-a">
              {{ faq.a }}
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="public-section">
      <div class="public-section-inner" style="text-align: center;">
        <h2 class="public-section-title">Ready when you are.</h2>
        <p class="public-section-body">
          Set up your Continuity Plan in about 20 minutes. Designate your Stewards. Cancel anytime — no contract.
        </p>
        <div class="public-hero-actions">
          <a :href="route('register')" class="btn btn-primary">Start with Practice</a>
          <a :href="route('contact')" class="btn btn-outline">Talk to us first</a>
        </div>
      </div>
    </section>

  </PublicLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { Head } from '@inertiajs/vue3'
import PublicLayout from '@/layouts/PublicLayout.vue'

const props = defineProps({
  pricing: { type: Object, default: () => ({}) },
})

const billing   = ref('monthly')
const activeFaq = ref(null)

const howSteps = [
  {
    num:   '1',
    icon:  'shield-check',
    title: 'Build your Continuity Plan',
    body:  'In about 20 minutes, document what should happen across all seven critical-incident types — incapacitation, death, missing, detainment, natural disaster, geopolitical conflict, and voluntary leave. Upload important documents and seal credentials into your Vault.',
  },
  {
    num:   '2',
    icon:  'users',
    title: 'Designate your Stewards',
    body:  'Invite a Continuity Steward (verifies and executes the plan) and a Support Steward (family or staff who monitors and triggers the alert). Both get their own portal access — covered by your subscription.',
  },
  {
    num:   '3',
    icon:  'clock',
    title: 'Standby — the plan rests',
    body:  'The Vault stays sealed. Your Stewards complete standby preparation tasks and certify readiness annually. Nothing about the plan is visible to your patients or competitors.',
  },
  {
    num:   '4',
    icon:  'alert-triangle',
    title: 'Activation — only if needed',
    body:  'A verified critical incident unlocks the relevant Vault zones. Your CS executes the plan you wrote, with a full activity audit trail. Every view, download, and decision is logged for legal defensibility.',
  },
]

const plans = computed(() => {
  const p = props.pricing?.practitioner ?? {}
  return [
    {
      key:          'access',
      name:         p.access?.name ?? 'Continuity Access',
      tagline:      p.access?.tagline ?? 'Everything you need to protect your practice and your clients.',
      monthly:      (p.access?.monthly_cents      ?? 3900) / 100,
      annual:       (p.access?.annual_cents        ?? 3575) / 100,
      billedAnnual: (p.access?.annual_total_cents  ?? 42900) / 100,
      featured:     false,
      features:     p.access?.features ?? [
        '1 Continuity Steward invitation',
        '2 Support Steward invitations',
        'Serve as CS for 1 practitioner',
        'Continuity Plan (all 7 incident types)',
        'Document Vault (4 zones)',
        'Shadow Network (limited)',
        'Secure messaging · Activity log',
      ],
    },
    {
      key:          'practice',
      name:         p.practice?.name ?? 'Continuity Practice',
      tagline:      p.practice?.tagline ?? 'Full continuity + integrative business services for growing practices.',
      monthly:      (p.practice?.monthly_cents      ?? 7900) / 100,
      annual:       (p.practice?.annual_cents        ?? 6583) / 100,
      billedAnnual: (p.practice?.annual_total_cents  ?? 79000) / 100,
      featured:     true,
      features:     p.practice?.features ?? [
        'Up to 2 Continuity Steward invitations',
        'Up to 2 Support Steward invitations',
        'Serve as CS for up to 3 practitioners',
        'Everything in Access, plus:',
        'Referrals — send & receive',
        'Full Integrative Network',
        'Integrative Services Mode',
        'Business Partner directory & Job Postings',
        'Priority support & onboarding call',
      ],
    },
    {
      key:          'practice_business',
      name:         p.practice_business?.name ?? 'Continuity Practice Business',
      tagline:      p.practice_business?.tagline ?? 'Practice + Business Partner access in one account.',
      monthly:      (p.practice_business?.monthly_cents      ?? 10400) / 100,
      annual:       (p.practice_business?.annual_cents        ?? 8667) / 100,
      billedAnnual: (p.practice_business?.annual_total_cents  ?? 104000) / 100,
      featured:     false,
      features:     p.practice_business?.features ?? [
        'Everything in Practice, plus:',
        'Business Partner profile & service listing',
        'Serve as CS for up to 43 practitioners',
        'Respond to practitioner service requests',
        'Service agreements, contracts & payment tools',
      ],
    },
  ]
})

const stewardPlans = computed(() => [
  {
    key:     'business-cs',
    name:    'Business Continuity Steward',
    price:   '$' + ((props.pricing?.continuity_steward_business?.monthly_cents ?? 4900) / 100),
    billing: 'per month · or $' + ((props.pricing?.continuity_steward_business?.annual_total_cents ?? 49000) / 100) + '/yr (save ~16%)',
    desc:    'Independent licensed CS serving 2–40 practitioners. Your own portal, your own roster. Subscription covers your practitioner relationships.',
    cta:     'Register as CS',
  },
  {
    key:     'invited-cs',
    name:    'Invited Continuity Steward',
    price:   'Free',
    billing: 'Covered by practitioner subscription',
    desc:    'Designated by a specific practitioner. Portal access, tasks, and incident response are included at no cost — the practitioner\'s plan covers you.',
    cta:     null,
  },
  {
    key:     'invited-ss',
    name:    'Support Steward',
    price:   'Free',
    billing: 'Covered by practitioner subscription',
    desc:    'Family member or staff monitoring the practitioner. Monitors check-in status, triggers incident alerts, and coordinates with the CS. Always free.',
    cta:     null,
  },
])

const bpFeatures = [
  'Browse and apply to practitioner job postings',
  'Proposal and contract management',
  'Milestone tracking and approvals',
  'Invoice generation and submission',
  'Stripe Connect direct payouts',
  'Agency team management',
  'W-9 and 1099 document handling',
]

const faqs = [
  {
    q: 'What does the practitioner subscription actually cover?',
    a: 'Your Continuity Plan, Document Vault, both your Stewards\' portal access, all 7 incident types, secure messaging, and your activity log. Stewards designated by you do not pay — their access is covered by your subscription.',
  },
  {
    q: 'What happens to my plan during a critical incident?',
    a: 'Nothing on the billing side. The plan stays active and your designated Continuity Steward gains the access defined in your Continuity Plan. Vault zones unlock only after the incident is verified.',
  },
  {
    q: 'Can I switch between Continuity Access and Continuity Practice later?',
    a: 'Yes. Upgrades take effect immediately. Downgrades take effect at the next billing cycle and may require you to remove a Steward or unlist services to fit the lower-tier limits.',
  },
  {
    q: 'Do I need the MAAT add-on?',
    a: 'No — you can designate your own Continuity Steward at no extra cost. MAAT is for practitioners who would rather have a vetted, licensed, insured professional CS on standby instead of recruiting one. Requires Continuity Practice.',
  },
  {
    q: 'Is there a contract or long-term commitment?',
    a: 'No. Monthly plans are month-to-month. Annual plans save 20% and renew yearly. Cancel anytime — your access continues through the end of the paid period.',
  },
  {
    q: 'What\'s a Business Partner — and how is that different from a Steward?',
    a: 'Business Partners are independent professionals (accountants, attorneys, IT, marketing, billing, compliance) practitioners hire for non-continuity work. The Aegis Business Partner marketplace handles proposals, contracts, milestones, and Stripe-Connect-routed payouts — separate from the continuity flow. Stewards, by contrast, are the two designated humans who execute your Continuity Plan during a critical incident.',
  },
  {
    q: 'How are payments handled?',
    a: 'Subscriptions are processed via Stripe Billing. Peer-to-peer payments inside Aegis (referrals, services, Business Partner contracts) flow directly between parties through Stripe Connect — Aegis never holds funds in transit.',
  },
]
</script>

<style scoped>
/* ── Badge pill for save label ─────────────────────────────── */
.badge-pill--green {
  font-size: 10px;
  background: var(--green-light);
  color: var(--green-dark);
  padding: 2px 7px;
  border-radius: var(--radius-full);
  font-weight: 700;
  letter-spacing: 0.2px;
}

/* ── How it works ──────────────────────────────────────────── */
.pricing-how-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 24px;
  margin-top: 16px;
}

/* Outer wrapper: overflow visible so step number badge floats above card */
.pricing-how-outer {
  position: relative;
  padding-top: 16px; /* space for the badge */
}

.pricing-how-step {
  height: 100%;
}

.pricing-step-num {
  position: absolute;
  top: 0;
  left: 18px;
  z-index: 2;
  width: 28px;
  height: 28px;
  border-radius: var(--radius-full);
  background: var(--gold-dark);
  color: var(--text-inverted);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-family: var(--font-serif);
  font-size: 14px;
  font-weight: 700;
  box-shadow: 0 2px 6px rgba(160,129,62,0.35);
}

.pricing-step-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-family: var(--font-serif);
  font-size: 14px;
  font-weight: 700;
  color: var(--gold-dark);
  margin-top: 6px;
  margin-bottom: 10px;
}

/* ── Plan cards ────────────────────────────────────────────── */
.pricing-cards-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 24px;
  max-width: 800px;
  margin: 0 auto;
}

/* Outer wrapper: overflow visible for the popular flag badge */
.pricing-card-outer {
  position: relative;
  padding-top: 16px;
}

.pricing-card { height: 100%; }

.pricing-card--featured {
  border: 1px solid var(--gold-dark);
  box-shadow: var(--shadow);
}

.pricing-popular-flag {
  position: absolute;
  top: 0;
  left: 50%;
  transform: translateX(-50%);
  z-index: 2;
  background: var(--gold-dark);
  color: var(--text-inverted);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  padding: 4px 14px;
  border-radius: var(--radius-full);
  white-space: nowrap;
  box-shadow: 0 2px 6px rgba(160,129,62,0.35);
}

.pricing-card-name {
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.6px;
  color: var(--text-4);
  margin-bottom: 4px;
}

.pricing-card-tagline {
  font-size: 13px;
  color: var(--text-3);
  line-height: 1.55;
  margin-bottom: 20px;
  min-height: 38px;
}

.pricing-card-price {
  display: flex;
  align-items: baseline;
  gap: 4px;
  margin-bottom: 4px;
}

.pricing-amount {
  font-family: var(--font-serif);
  font-size: 42px;
  color: var(--text);
  font-weight: 700;
  line-height: 1;
  letter-spacing: -1px;
}

.pricing-period {
  font-size: 14px;
  color: var(--text-4);
  font-weight: 600;
}

.pricing-billed-note {
  font-size: 12px;
  color: var(--text-4);
  min-height: 18px;
  margin-bottom: 20px;
}

.pricing-features {
  list-style: none;
  margin: 0 0 24px;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.pricing-feature {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13.5px;
  color: var(--text-2);
  line-height: 1.4;
}

/* ── Add-on band ───────────────────────────────────────────── */
.pricing-addon {
  display: flex;
  align-items: flex-start;
  gap: 20px;
  margin-top: 32px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 24px 28px;
  box-shadow: var(--shadow-sm);
}

.pricing-addon-mark {
  width: 48px;
  height: 48px;
  border-radius: var(--radius);
  background: var(--icon-bg-gold);
  color: var(--gold-dark);
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.pricing-addon-body { flex: 1; min-width: 0; }

.pricing-addon-eyebrow {
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.5px;
  text-transform: uppercase;
  color: var(--gold-dark);
  margin-bottom: 4px;
}

.pricing-addon-title {
  font-family: var(--font-serif);
  font-size: 18px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 6px;
}

.pricing-addon-tagline {
  font-size: 13px;
  color: var(--text-3);
  line-height: 1.55;
  margin-bottom: 12px;
}

.pricing-addon-features {
  display: flex;
  flex-wrap: wrap;
  gap: 6px 16px;
}

.pricing-addon-feature {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 12px;
  color: var(--text-2);
}

.pricing-addon-price {
  flex-shrink: 0;
  text-align: right;
  min-width: 160px;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 4px;
}

.pricing-addon-amount {
  font-family: var(--font-serif);
  font-size: 28px;
  font-weight: 700;
  color: var(--text);
  line-height: 1;
}

.pricing-addon-billed {
  font-size: 11px;
  color: var(--text-4);
  margin-bottom: 8px;
}

.pricing-addon-req {
  font-size: 11px;
  color: var(--text-4);
  margin-top: 4px;
}

/* ── Founding banner ───────────────────────────────────────── */
.pricing-founding {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-top: 24px;
  background: var(--icon-bg-gold);
  border: 1px solid var(--badge-border-gold);
  border-radius: var(--radius-lg);
  padding: 16px 22px;
  font-size: 13px;
  color: var(--text-2);
  line-height: 1.5;
}

.pricing-founding strong {
  color: var(--text);
  font-weight: 700;
}

/* ── Steward grid ──────────────────────────────────────────── */
.pricing-steward-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}

/* ── Enterprise note ───────────────────────────────────────── */
.pricing-enterprise-note {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  margin-top: 20px;
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-lg);
  padding: 16px 20px;
  font-size: 13px;
  color: var(--text-2);
}

.pricing-enterprise-inner {
  display: flex;
  align-items: center;
  gap: 10px;
}

.pricing-enterprise-inner strong {
  color: var(--text);
  font-weight: 700;
}

/* ── BP card ───────────────────────────────────────────────── */
.pricing-bp-wrap {
  max-width: 820px;
  margin: 0 auto;
}

.pricing-bp-inner {
  display: grid;
  grid-template-columns: 1fr 220px;
  gap: 32px;
  align-items: start;
}

.pricing-card-tagline {
  font-size: 13px;
  color: var(--text-3);
  line-height: 1.55;
}

.pricing-bp-side {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 20px;
  text-align: center;
}

.pricing-bp-note {
  font-size: 11px;
  color: var(--text-4);
  margin-top: 6px;
}

/* ── FAQ ───────────────────────────────────────────────────── */
.pricing-faq-email {
  color: var(--gold-dark);
  font-weight: 600;
  text-decoration: none;
}
.pricing-faq-email:hover { text-decoration: underline; }

.pricing-faq-q {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

/* ── Responsive ────────────────────────────────────────────── */
@media (max-width: 1024px) {
  .pricing-how-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 820px) {
  .pricing-steward-grid { grid-template-columns: 1fr; }
  .pricing-how-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 680px) {
  .pricing-how-grid { grid-template-columns: 1fr; }
  .pricing-cards-grid { grid-template-columns: 1fr; }
  .pricing-addon { flex-direction: column; gap: 16px; }
  .pricing-addon-price { text-align: left; min-width: 0; align-items: flex-start; }
  .pricing-bp-inner { grid-template-columns: 1fr; }
  .pricing-enterprise-note { flex-direction: column; align-items: flex-start; }
}
</style>
