# Pending Items for Dr. Chapman — Decision Log

**Last updated:** 2026-07-09 (email reply received 7:45 PM)
**Repo state:** `main @ eb48d79`
**Related docs:** `AEGIS_BILLING_LIFECYCLE.md` · `AEGIS_PAYMENTS_FINANCE.md` · `CONTINUITY_GROUP_CONVERSION_PLAN.md`

---

## ✅ Section 1 — Confirmed (act on these)

### ✅ 1.1 Signature Mechanics on Continuity Plan
**Confirmed:** Option B — single click-to-apply at the end. The practitioner reviews all clauses, then one confirmation applies their stored identity to the full document. No per-clause typing.

**Build impact:** Continuity Plan sign-ceremony modal uses one `confirmAction()` callback. No per-row name/title/date inputs. See `CONTINUITY_GROUP_CONVERSION_PLAN.md` §0.6.

---

### ✅ 1.2 W-9 Gating
**Confirmed:** Soft warning only. No hard block.

**Attorney's exact response:** *"If the payments are not coming to Aegis, then Aegis doesn't need a W-9. The question is whether Aegis should require the payees to have a W9 on file, but that's only necessary if Aegis receives the money. Because it doesn't, I'd leave the reporting requirements to Stripe."*

**What's in code:** `payCSInvoice` and `payBPInvoice` log an `ActivityService` warning if W-9 not verified but allow the payment through. This is correct and final. No further action needed.

---

### ✅ 1.3 Founding Member Perks — fully confirmed

**Duration:** For life of active Aegis membership. No expiry date needed. `founding_perks_expire_at` column not required.

**Slot counts (revised from original assumptions):**

| Role | Founding slots | Was |
|---|---|---|
| Practitioner (Access + Practice) | First **5,000** members | Was 100 per tier |
| Business CS | First **100** members | Was 25 |
| Business Partner | First **100** members | Was not defined |

**Perks per role:**

| Role | Perks |
|---|---|
| Practitioner (Access) | +2 CS slots for life · Founding badge · Early feature access · Introductory pricing lock · 1 spotlight inclusion |
| Practitioner (Practice) | +2 CS slots for life · Founding badge · Early feature access · Introductory pricing lock · 1 spotlight inclusion |
| Business CS | 50% off CS Training (up to 25 discounted placements) · Founding badge · Early feature access · 1 spotlight inclusion |
| Business Partner | Founding badge · Early feature access · Introductory pricing lock · 1 spotlight inclusion |

**"+2 CS free" resolution:** 2 additional CS slots on top of tier cap, for life of active membership.
- Access founder: 1 (baseline) + 2 (perk) = **3 CS total**
- Practice founder: 2 (baseline) + 2 (perk) = **4 CS total**

**Pricing lock:** Founding members retain their introductory pricing through Aegis' first platform-wide pricing adjustment.

**Spotlight / marketing placement:** Lives on the **News page only**. MAAT selects the featured business internally and posts it — no self-serve redemption UI needed for members. Scheduling is on a rotating/availability basis. One spotlight inclusion per founding member. Small, central, non-distracting section.

**`config/aegis.php` already updated** to reflect confirmed slots and perks (commit pending).

---

### ✅ 1.4 Access Tier — Support Steward cap
**Confirmed:** 2 SS on Access. The document she sent lists "2 Support Stewards" under Access tier. `.env` is already set to `TIER_ACCESS_MAX_SS=2`. No action needed.

---

### ✅ 1.5 W-9 — No additional Stripe or IRS integration
Attorney confirmed no action needed. Stripe handles 1099-K reporting as Payment Settlement Entity.

---

## ⏳ Section 2 — Still pending

### ⏳ 2.1 Founding member — cancel + re-subscribe status
**Question:** If a founding member fully cancels and re-subscribes later, do they retain founding status?

**Devlet recommendation:** Pause = retain status. Full cancel + re-subscribe = forfeit status.

**Why this matters for code:** Determines whether `users.founding_tier` is a permanent flag set at registration or needs a `forfeited_at` timestamp. Gates the entire DB schema decision for the founding member build.

**Action needed:** One-line answer from Dr. Chapman.

---

### ⏳ 2.2 Onboarding flow + pricing presentation
**Status:** Dr. Chapman is reviewing and will send feedback. Her message: *"The packages won't change, but their presentation will."*

**Do NOT touch:** Onboarding flow, pricing copy, plan feature lists, or any onboarding Vue pages until her feedback arrives.

**Note:** The document she attached uses old prices ($39/$79). Those are NOT the confirmed prices. Stripe prices ($29/$49) remain correct. Wait for her presentation feedback before updating any copy.

---

## Section 3 — What's blocked by pending items

| Blocked item | Blocked by | Effort once unblocked |
|---|---|---|
| `users.founding_tier` column + assignment migration | §2.1 cancel/re-subscribe answer | 30 min |
| Steward-cap check reads `founding_tier` + applies +2 modifier | §2.1 | 30 min |
| Founder badge + status card in Provider Settings | §2.1 | 2 hr |
| Admin founding-slot fill dashboard tile | §2.1 | 1 hr |
| Onboarding/pricing page copy updates | §2.2 | TBD |

## Section 4 — What's NOT blocked (proceeding independently)

- CS engagement contract auto-invoice — built and working
- Dispute system — built and working
- Native Add Card (Stripe Elements) — Provider done; CS + BP need `storePaymentMethod` handler (30 min)
- Continuity Group Provider Vue rebuild — in progress per `CONTINUITY_GROUP_CONVERSION_PLAN.md` Rev 4
- All 4 peer-payment flows — working end-to-end
- Real Stripe Connect Express onboarding — working all 3 paid portals
- 7 email blade templates — pending build (2 hr, unblocked)
- Dispute wiring in Finances.vue — pending build (1.75 hr, unblocked)

---

*Updated 2026-07-09 based on Dr. Chapman email reply. Previous version had 5 open questions — 4 closed, 2 remain (§2.1 and §2.2).*
