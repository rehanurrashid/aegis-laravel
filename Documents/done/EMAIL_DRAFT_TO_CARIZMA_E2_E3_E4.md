# Email Draft — Three Open Product Decisions

**To:** Carizma Chapman
**From:** Devlet LLC
**Subject:** Aegis — Three product decisions needed before final build

---

Hi Carizma,

The Provider portal and the three steward portals (CS, SS, BP) are now essentially complete — your master change list is ~145 of ~150 items implemented, and your Apr 12 and Apr 19 emails have been fully addressed.

There are three remaining product decisions where I need your input before we can finalize the build. None of these are blocking immediate progress (we can continue with the Admin portal in parallel), but each requires your direction so we don't make assumptions.

---

## 1. MA'AT Continuity Response Reserve — placement and product model

At the end of your master change list, you included a new copy block introducing MA'AT Continuity Assurance and a **$3,000 Continuity Response Reserve**:

> "When something changes unexpectedly, the need is not only for a plan, but for steadiness."
>
> "In moments where decision making and coordination would otherwise fall entirely on you or your loved ones, Ma'at provides structure and support to help carry the continuity plan forward."
>
> "Ma'at provides coordinated continuity support designed to help practices navigate interruption, transition, closure, or continuity response during critical moments…"
>
> **$3,000 Continuity Response Reserve**
>
> "Reserve amounts may be adjusted when continuity response needs involve substantial operational, administrative, or practice transition responsibilities outside standard response support."
>
> "Support to carry your continuity plan forward when it matters most."

This appears to introduce a new product offering. To build it correctly, I need five answers:

1. **Where does this live in the platform?** Dashboard banner, Settings tab, new onboarding step, or a marketing/info page?
2. **What is the product structure** — is the Reserve a subscription tier add-on, a one-time activation product, or a separate purchase outside of plan tiers?
3. **Opt-in or default?** Does the practitioner enroll explicitly, or does it apply automatically to one of the existing tiers (Continuity Access or Continuity Practice)?
4. **Billing model for the $3,000** — pre-authorized hold, monthly accrual, prepaid reserve, or billed at activation?
5. **Who holds the funds?** — MA'AT directly, or via Aegis (Stripe Connect)? If MA'AT directly, we don't need to build it into the platform's payment flow; we just need the copy and the marketing surface.

Once you confirm these, we can scope and build it cleanly.

---

## 2. Supporting Continuity Steward — restore as a third role variant?

Your email Key Terms section defined three CS variants:

- **Primary Continuity Steward** — carries out the plan
- **Support Continuity Steward** — supports the Primary when responsibilities are divided
- **Alternate Continuity Steward** — steps in if Primary or Support is unavailable

The current Aegis schema supports **Primary + Alternate only**. Support CS was retired in an earlier simplification.

Restoring Support CS requires:

- A small schema migration (extending the role enum on `plan_stewards`)
- UI work in the Continuity Stewards designation page (a third card variant + invite flow)
- Decision on whether existing plans need migration (default: leave them as-is, opt-in for new Support CS designation)

**Please confirm:** do you want Support CS restored as a third role variant, or would you prefer to keep Primary + Alternate only and document Support CS as a conceptual role within the Primary CS's responsibility?

---

## 3. "Offers Services" filter on the Network page

In April you raised: *"How will providers discover other providers offering services?"*

Currently, the Network page has tabs for Integrative Care Network, Business Partners, and Referrals. Providers offering services on the platform (e.g., supervision, consultation, practice continuity services) are visible only if a practitioner already knows them and connects directly.

**Recommended approach:** add an **"Offers Services" filter** as a second-tier filter on the Network page — a toggle that surfaces all practitioners on the platform who have enabled Integrative Business Services (IBS) Mode and have at least one active service offering.

This becomes a discovery surface for finding providers to hire for services, complementing the existing Find Provider flow in the Services page.

**Please confirm:** do you want this added as recommended, or would you prefer a different discovery approach?

---

## Next Steps

I'll continue building the Admin portal in parallel while you consider these. The platform is otherwise in great shape — once you confirm these three decisions and complete the Stripe setup (highest priority per my last email), we'll be in the final stretch to launch.

Looking forward to your thoughts. Happy to schedule a 30-minute call to walk through any of these if that's easier.

Best regards,
Arslan
Devlet LLC
