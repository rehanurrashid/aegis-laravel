# Pending Items for Dr. Chapman — Confirmation Required

**As of:** 2026-07-09
**Repo state:** `main @ 9351e14` — P0 + P1 + Batch3 + Patch all landed
**Related docs:** `AEGIS_BILLING_LIFECYCLE.md` · `AEGIS_PAYMENTS_FINANCE.md` · `CONTINUITY_GROUP_CONVERSION_PLAN.md`

This is the shortlist of decisions blocked on Dr. Chapman's confirmation before the corresponding code path can be built or shipped. Everything else in the platform can proceed without her input.

---

## Section 1 — Blocking product decisions

### 1.1 Access Tier — Support Steward cap (1 or 2?)

**What's coded today:** `TIER_ACCESS_MAX_SS` env var, default `1`.
**What's ambiguous:** In earlier calls she verbally referenced "2 SS on Access," but no formal sign-off. Config default and UI copy both currently say 1.
**Impact if wrong:** Access-tier users can only invite N Support Stewards; hitting the cap triggers the upgrade modal. Wrong number = confusing UX.
**What we need:** One-line email answer — "Access users get 1 SS" or "Access users get 2 SS."
**Effort once confirmed:** Set `.env` to `TIER_ACCESS_MAX_SS=2` (or leave at default). 5 minutes.

---

### 1.2 Founding Member Perks — 4 open questions

**What's coded today:** `config/aegis.php` `founding_member` section defines the perks per role. No `users` column, no DB assignment logic, no UI. Only `is_founding_member` boolean computed on-the-fly in `Provider/SettingsController::index` (checks if user is among first 100 practitioners by registration date).

**Current perk copy in config:**
- Access founder (first 100): *"2 additional CS free for life + 1 marketing ad/yr"*
- Practice founder (first 100): *"2 additional CS free for life + 2 marketing ads/yr"*
- CS founder (first 25 Business CS): *"50% off Continuity Steward Training"*

**Four questions before we can build this:**

**Q1 — Time-limited or truly for life?**
- Option A: For life — perk lasts as long as subscription active. Simplest, best marketing.
- Option B: Time-limited (e.g., 5 years) — needs `founding_perks_expire_at` column.
- **Devlet recommendation:** for life.

**Q2 — If a founder cancels and re-subscribes, do they retain founder status?**
- Sub-Q2a: Founder pauses for 3 months, resumes on same account. Perks retained?
- Sub-Q2b: Founder fully cancels, re-registers 2 years later on a new subscription. Perks retained?
- **Devlet recommendation:** Pause = retain. Full cancel = forfeit.

**Q3 — "2 additional CS free" — on top of the cap or lifts the cap entirely?**
- Option A: Practice founder = 2 baseline + 2 perk = 4 CS total, hard cap enforced at 4.
- Option B: Practice founder = unlimited CS.
- **Devlet recommendation:** Option A (bounded, prevents spam/ghost designations).

**Q4 — Marketing ads — how do they work?**
- Placement: Public profile? Homepage carousel? Network search results? Newsletter?
- Format: Banner image + link? Featured provider card? Text spotlight?
- Duration per ad: 7 days? 30 days? Permanent?
- Redemption UX: Self-serve in Settings? Submit for admin approval? Email you directly?
- Reset cadence: Calendar year (Jan 1 refresh) or subscription anniversary?

**Effort once confirmed:** ~1–2 days to build:
- Add `users.founding_tier` column + assignment logic on registration
- Steward-cap check reads `founding_tier` and applies the +2 modifier
- Founder-status card in Provider Settings
- Marketing-ad redemption UX per Q4 spec
- Admin dashboard tile showing founding-slot fill (`67/100 Access, 42/100 Practice, 8/25 CS`)

---

### 1.3 W-9 Gating — soft-warn or hard-block?

**What's coded today:** Soft-warn. Both `payCSInvoice` and `payBPInvoice` log an ActivityService warning if the recipient doesn't have a verified W-9 on file, but the payment goes through.

**Why we chose soft-warn (Devlet recommendation):**
- Aegis uses Stripe Connect **destination charges** — money moves directly from Provider's card to CS/BP's Connect account. Aegis never touches the funds.
- Under IRS rules, Stripe (as the Payment Settlement Entity) — not Aegis — issues the 1099-K to the recipient when thresholds are met.
- Hard-block would break the Invited-CS-as-family use case (sister volunteering as CS shouldn't need a W-9).
- Upwork requires W-9 because they route payments through their own accounts. We don't.

**When you might want hard-block:**
- If Aegis wants to file its own 1099-NEC for independent contractor payments (not required, but some platforms do it as a courtesy/audit trail).
- If your attorney advises stricter tax compliance posture.

**What we need:** Confirmation to keep soft-warn (Devlet recommendation) OR direction to add hard-block.

---

### 1.4 Signature Mechanics on Continuity Plan

**What's ambiguous:** From April 2026 decision log, this was flagged as pending: "per-line typed name + title + date, or click-to-apply-on-file?"

**What we need:** Direction on how the signature ceremony renders on the Continuity Plan finalize modal.
- Option A: Per-line — user types name + title + date for each attestation clause.
- Option B: Click-to-apply — user's stored identity signature is applied to all clauses with one confirmation.

---

### 1.5 "Provider" Terminology Replacement

**Status:** You previously indicated the term "Provider" should be replaced but were going to send the replacement. That's still outstanding.

**What we need:** Replacement term. Anywhere the UI currently says "Provider" we'd swap. Portal name stays `/provider` but user-facing copy changes.

---

## Section 2 — Ready to build once above decisions land

Once §1.1–1.5 are confirmed, the following work unblocks:

| Blocked item | Blocked by | Effort |
|---|---|---|
| Founding member DB column + assignment logic | Q1–Q4 answers | 1–2 days |
| Access tier `max_support_stewards` correct default | Q1.1 | 5 min |
| Continuity Plan sign-ceremony final UI | Q1.4 | 4 hr |
| Global "Provider" → new term rename | Q1.5 | 1 hr |

## Section 3 — What's NOT blocked (proceeding independently)

- CS engagement contract auto-invoice flow — built and working
- Dispute system — built and working
- Native "Add Card" (Stripe Elements) — built for Provider; CS + BP need one more small handler
- Continuity Group Provider Vue rebuild — plan doc ready; execution can start
- All 4 peer-payment flows — working end-to-end
- Real Stripe Connect Express onboarding — working for all 3 paid portals

---

---

# 📧 Email Draft to Dr. Chapman

Copy-paste into your email client. Adjust the greeting per your usual tone with her.

---

**To:** Dr. Carizma Chapman <support@maatpracticefirm.com>
**From:** Arslan · Devlet LLC
**Subject:** Aegis — quick decisions needed to unblock final build items

---

Hi Carizma,

Wrapping up the last major build items on Aegis. Five things need your call before I can lock them down — most are one-line answers, except the founding member perks which need a bit more thought.

**1. Access tier — how many Support Stewards?** Currently coded as 1. You'd mentioned 2 on an earlier call. Which is right?

**2. Founding Member perks.** I have the perk copy in config, but four details are missing:
- Are they for life, or time-limited?
- If a founder cancels and re-subscribes later, do they keep founder status?
- The "+2 CS free" — is that on top of the cap (Practice founder = 4 CS total) or does it lift the cap entirely?
- Marketing ads — where do they run (public profile, homepage, etc.), what format, how long each, and how does the founder redeem them?

**3. W-9 requirement on peer payments.** Because we use Stripe Connect (money moves direct, Aegis never holds it), Stripe handles the 1099-K reporting for CS and BP recipients. So technically we don't need to require a W-9 to let a payment through. I've currently coded it as a soft warning — payment proceeds, but we log if there's no W-9. Fine to keep it that way, or do you want a hard block?

**4. Signature mechanics on the Continuity Plan.** When a practitioner signs the plan, do you want per-line typed name + title + date for each clause, or a single click-to-apply-on-file at the end?

**5. "Provider" terminology.** You mentioned you'd be sending a replacement term — is there one you'd like to use now, or should we keep "Provider" for launch?

Once these are settled, we can close out the remaining build work. Nothing else is blocked on your side — the CS engagement contract flow, the dispute system, native card capture, and all the peer-payment flows are already built and working.


Thanks,
Arslan
Devlet LLC · arslan@devlet.tech

---

*Send date TBD. Update this file as answers come in.*
