# Aegis — Provider Portal Build Status

**Prepared by:** Devlet LLC
**Date:** June 19, 2026
**Re:** Master Change List + Apr 12 / Apr 19 Feedback

---

## Summary

Of approximately 150 items in your master change list, **~145 are now live in the platform**. The remaining items are either open product questions (your input needed) or one new feature concept (MA'AT Continuity Response Reserve) that needs design context before we build.

This report (a) confirms the major sections complete and (b) answers each of the open questions you raised inline.

---

## ✅ Completed and Ready for Review

| Surface | Status |
|---|---|
| **Overview Start Here** (Practitioner) — 30+ paragraph rewrites, 6 Why-Aegis cards, 7 How-to-Use steps, 16 FAQs (9 rewrites + 7 new) | Live |
| **Key Terms glossary** — Practitioner, Primary/Support/Alternate CS, Continuity Plan, Critical Incident, Integrative Network | Live |
| **Activate Continuity Support** flow — title, "Incident Type" label, 6 incident types, documentation options, "What happens next" copy | Live |
| **Annual Re-Attestation** — all 8 checkboxes (SS identity, SS tasks, CS tasks, Plan accurate, Practice info, Support docs, Vault docs, etc.) | Live |
| **Vault Attestation** — new Provider action + CS/SS visibility (Carizma email Apr 12, CS workflow step 7) | Live |
| **License/Credential taxonomy** — full MD/DO/ND/NP/PA/LPC/LCSW/LMFT/EMDR/DBT/LAc/CNM/CDCES/CPT and 20+ more credentials, with manual entry | Live |
| **Specialties** — 10 categories with 70+ specialties + custom entry | Live |
| **Services** — 8 categories with 40+ services + custom entry | Live |
| **Approaches/Frameworks** — Clinical / Nutrition / Functional Medicine / Psychiatry, 50+ items + custom entry | Live |
| **Client Roster** — relocated to Vault with Priority Response flag, City/State, Phone/Email, Service Type | Live |
| **Counter Referral feature** — removed | Done |
| **Patients section** — removed entirely | Done |
| **CEU tracking** — synchronous/asynchronous toggle, annual/biannual cycle, all 17 categories pre-populated (Ethics, Supervision, Telehealth, Safety, Quality, HIV, Child abuse, IPV, Assessment, Substance, Publications, Teaching, General, Cultural Competency, Suicide, Other) | Live |
| **Renew → Update** terminology across License/Insurance | Done |
| **Network page** — hero subtitle, Recommended Shadow Providers subtitle, Clinical Partners → Provider Partners | Live |
| **My Services page** — hero subtitle, Practice Continuity service category | Live |
| **Job Posting → Support Request** rename everywhere | Done |
| **Integrative Network → Integrative Care Network** (kept as tab, also added as primary heading "Network") | Done |
| **Settings copy fixes** — Upgrade modal, subscription panel, Danger Zone → Account Closure & Data Management, IBS Mode toggle | Done |

---

## ❓ Open Product Questions — Answers

Below are concise answers to each question you raised. Please confirm or redirect.

### 1. "What does SLA remaining mean?"
**Answer:** SLA = Service Level Agreement. It's the time remaining to respond to a referral before it auto-expires. **Recommendation:** Replace with plain-language label like **"Time to respond"** or **"Response window"**. Confirm and we'll rename across referrals.

### 2. "What is a HIPAA event?"
**Answer:** A HIPAA event was an internal term for any access to protected health information that gets logged for compliance. **Recommendation:** Rename to **"Access event"** (already changed in most places) or **"Protected data access"** for clarity. Activity Log will then only show actions — no alerts.

### 3. "Banner has 'Aegis verified' — how are we verifying?"
**Answer:** Currently a placeholder badge; no verification process is yet active in the system. Three paths forward:
- **(a)** Remove the badge until verification is built
- **(b)** Build a manual verification workflow (you/team verify credentials before granting the badge)
- **(c)** Integrate with an external credentialing API (e.g., NPI Registry, state license boards)
**Recommendation:** Option (a) for launch, then (b) post-launch. "Top Rated" badge has already been removed per your spec.

### 4. "Will Aegis really show when someone else has been hired by another provider (Hiring Pipeline)?"
**Answer:** No — that was sample/demo data only. The Hiring Pipeline shows only **your own** Support Requests and their applicant status. Other providers' hires are private.

### 5. "How does the job posting become visible to business partners?"
**Answer:** When you publish a Support Request, it appears in the Business Partner portal's "Find Jobs" feed. BPs filter by category, location, and budget; they submit proposals which appear in your "My Support Requests" → Proposals tab.

### 6. "Provider with business offerings — will they load under the business network?"
**Answer:** Yes. The Network page now has **three separate primary tabs**: Integrative Care Network, Business Partners, Referrals & Tools. A provider who offers business-style services (e.g., supervision, consulting) appears in both their Provider Network entry AND the Business Partners tab. Clicking their card from the Business Partners tab opens a **business profile** view.

### 7. "Custom specialty / service — add button does not work"
**Answer:** Now fixed — each taxonomy section (Services, Specialties, Approaches) has a dedicated "Add Custom" input that appends to the user's selected list.

### 8. "Manage group / preview request services / send request — buttons not working in My Services"
**Answer:** Those interactions are wired through the universal `aegis_service_request` flow. Confirmed working in demo with `cs_marcus` and `bp_acme`. If you're still seeing dead buttons, please share the specific page URL + the demo flag (`?as=p_sarah&services=1`) so we can reproduce.

### 9. "Preview public — does it work?"
**Answer:** "Preview Public Profile" should open your provider profile as it appears to other users. If it's not opening for you, this is a bug — please share the browser + steps and we'll fix.

### 10. "Languages spoken — allow manual entry of other languages"
**Answer:** Confirmed — the languages picker now has an "Add Custom Language" field below the pre-populated list. Manual entries persist.

### 11. "All statuses tab — can there be an Archived option? Auto-archive after 60 days?"
**Answer:** Yes, both are feasible. Auto-archive would shift completed Support Requests with no activity for 60 days into an "Archived" tab. **Confirm** and we'll add this.

### 12. "Expiry of requests — are the 48-hour / 7-day messages all working correctly?"
**Answer:** Yes — the countdown logic dynamically selects the right message based on the request's `expiry_window` (48h, 3d, 7d). If you saw a mismatch, share the specific request ID and we'll investigate.

### 13. "Can Library be added (videos, tutorials)?"
**Answer:** Yes — feasible as a new section under Explore. Scope: requires (a) video hosting decision (YouTube embed vs self-hosted), (b) a content catalog structure (categories, tags), (c) written tutorials format. **Recommendation:** Phase 2 post-launch, after content is ready.

### 14. "Supporting Continuity Steward — restore as 3rd role variant?"
**Answer:** Currently the schema supports Primary + Alternate only (Supporting was retired during a prior simplification). Restoring requires (a) a small schema change, (b) UI work to give it a card variant, and (c) decision on whether existing plans need migration. **Confirm** and we'll scope.

### 15. "How does Escrow / non-Escrow / Retainer payment work?"
**Answer:** Aegis uses Stripe Connect for peer-to-peer payments (Provider → CS/BP). Aegis itself doesn't hold funds — there is no Aegis-managed escrow. If you want a true escrow product, that requires a separate financial-services integration (e.g., Escrow.com API) and additional licensing. **Recommendation:** Use "Service Fee" terminology with options Retainer, Activation Fee, Hourly, Other — and remove escrow language.

### 16. "Provider verification badge — how is it verified?"
**See #3 above.**

---

## ❌ One Item Pending Your Direction

### MA'AT Continuity Assurance / $3,000 Continuity Response Reserve

You added the following copy at the end of the master change list:

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

This appears to introduce a **new product offering** — MA'AT Continuity Assurance with a $3,000 reserve. We need to know:

1. **Where does this live in the platform?** (Dashboard banner? Settings tab? New onboarding step? Marketing page?)
2. **Is it a subscription tier, a one-time activation product, or a separate purchase?**
3. **Does it auto-trigger, or do practitioners opt in?**
4. **How does the $3,000 figure relate to billing? Pre-authorized hold, prepaid reserve, billed at activation?**
5. **Who collects/holds it — MA'AT directly, or via Aegis (Stripe)?**

Once you confirm these, we can scope and build.

---

## Next Recommended Steps

1. **Verify the items above** in the demo environment — open `demo.php` and switch through the various user perspectives.
2. **Answer the 16 questions** so we can close those threads.
3. **Decide on the MA'AT Reserve placement** so we can build it.
4. **Identify the 5 highest-priority items** for any final pre-launch polish.

Target launch remains **July 15, 2026**.

---

*Devlet LLC · aegis.devlet.tech*
