# Aegis — Email Templates Design Prompt  *(UC-grounded, enhanced)*

**Purpose:** Generate the complete set of transactional HTML email templates for Aegis, fully aligned with the platform's design system (`Aegis_Desing_Prompt.md` + `Aegis_Desing_Prompt_Short.md`). Run this prompt AFTER use cases are finalized so we know every email trigger is covered.

> **Enhancement note (this revision):** Every template below is now cross-referenced against the validated `AEGIS_USE_CASES_OUTPUT.md` (commit `bbac3bc`, 419 UCs). A **UC-Grounded Trigger Map** (new §A) anchors each template to a real use case; a **Notification Preference Gate** rule (new §B) ties each template to a real `user_meta` `notify_*` key extracted from the codebase; **12 missing templates (58–69)** discovered in the gap analysis have been added; and partial templates have correction notes. No trigger is invented and no fan-out UC is left without a template.

---

## §A — UC-Grounded Trigger Map  *(new — source of truth for every email)*

**Counts (this analysis):**
- Email-worthy UC events (cross-portal fan-out to a human + security + money + lifecycle + invitations): **69**
- Existing templates in the prior prompt: **57**
- Missing templates added: **12** (Templates 58–69)
- Partial templates corrected: **5** (see §A.3)
- Templates with no UC basis (flagged `[REMOVE]`): **0** — all 57 map to a real UC or a legitimate system event.

Status legend: ✅ Covered · ⚠️ Partial (recipient/merge-tags/variant fix) · ❌ Missing (new template added below).

### A.1 — Existing templates 1–57 mapped to UCs

| # | Template | UC trigger | Recipient(s) | Gate key | Status |
|---|---|---|---|---|---|
| 1 | Welcome (per role) | UC-PRV-001 / UC-CS-001,002 / UC-SS-001 / BP signup | New user | `[UNGATED]` | ⚠️ see A.4 (onboarding simulated) |
| 2 | Email verification | UC-PRV-213 | New user | `[UNGATED]` | ⚠️ see A.4 |
| 3 | Password reset | UC-PRV-005 / UC-ADM-025 | User | `[UNGATED]` | ✅ |
| 4 | Password changed | UC-PRV-005 (post) | User | `[UNGATED]` | ✅ |
| 5 | MFA enabled | UC-PRV-214 / settings MFA | User | `[UNGATED]` | ✅ |
| 6 | MFA disabled alert | settings MFA off | User | `[UNGATED]` | ✅ |
| 7 | New device login | UC-PRV-002 (A-path) | User | `[UNGATED]` | ✅ |
| 8 | Account locked | UC-PRV-002 lockout / UC-ADM-023 | User | `[UNGATED]` | ✅ |
| 9 | Account closure | UC-ADM-027 / self-close | User | `[UNGATED]` | ✅ |
| 10 | Plan signed (to Practitioner) | UC-PRV-036 | Practitioner | `notify_plan_change` | ✅ |
| 11 | Plan ready (to CS + SS) | UC-PRV-036 / UC-XP-008 | CS, SS | `notify_plan_change` | ⚠️ per-role variant |
| 12 | CS countersigned | UC-CS (countersign) / UC-XP-002 | Practitioner | `notify_plan_change` | ✅ |
| 13 | Vault attested | UC-PRV-040 / UC-XP-003 | CS, SS | `notify_attestation` | ✅ |
| 14 | Annual Re-Attestation due | UC-PRV-038 / UC-XP-014 | Practitioner | `notify_plan_review` | ✅ (3 sends) |
| 15 | Annual Re-Attestation completed | UC-PRV-039 | CS, SS | `notify_attestation` | ✅ |
| 16 | Plan version updated | UC-PRV-035/192 (material change) | CS, SS | `notify_plan_change` | ✅ |
| 17 | CS invitation — external | UC-PRV-051 | Invitee | `[UNGATED]` (invite) | ✅ |
| 18 | CS invitation — internal | UC-PRV-050 | Existing user | `notify_assignment` | ✅ |
| 19 | SS invitation — external | UC-PRV (SS invite ext) | Invitee | `[UNGATED]` | ✅ |
| 20 | SS invitation — internal | UC-PRV (SS invite int) | Existing user | `notify_assignment` | ✅ |
| 21 | CS accepted designation | UC-CS-001/025 / UC-XP-017 | Practitioner | `notify_assignment` | ✅ |
| 22 | CS declined designation | UC-CS decline | Practitioner | `notify_assignment` | ✅ |
| 23 | CS role change requested | UC-CS-023 | Practitioner | `notify_role_change` | ✅ |
| 24 | CS removed from plan | UC-PRV-056 | CS | `notify_assignment` | ✅ |
| 25 | Alternate CS activated | UC-XP-019 | Practitioner, Alternate CS | `notify_activation` | ✅ |
| 26 | Critical incident reported | UC-PRV-090 / UC-SS-030 / UC-XP-004 | CS | `[UNGATED]` | ✅ |
| 27 | Incident verified by CS | UC-CS-041 / UC-XP-005 | SS, Practitioner | `[UNGATED]` | ✅ |
| 28 | Vault unlocked notice | UC-XP-006 | CS | `notify_vault_unlock` `[UNGATED]` | ✅ |
| 29 | Incident task assigned | UC-CS-031 (task) | CS | `notify_task` | ✅ |
| 30 | Incident escalation requested | incident oversight → admin | Aegis team | `[UNGATED]` | ✅ |
| 31 | Incident closed | UC-XP-007 | All parties | `[UNGATED]` | ✅ |
| 32 | Support Request received (proposal ack) | UC-BP (submit proposal) | BP | `notify_proposal` | ✅ |
| 33 | Proposal accepted | UC-PRV-132 / UC-XP-010 | BP | `notify_proposal` | ✅ |
| 34 | Proposal declined | UC-PRV-133 | BP | `notify_proposal` | ✅ |
| 35 | Contract created | UC-XP-010 | BP, Practitioner | `notify_agreement` | ✅ |
| 36 | Milestone submitted | UC-BP milestone submit | Practitioner | `notify_assignment` | ✅ |
| 37 | Milestone approved | UC-PRV-135 | BP | `notify_payment` | ✅ |
| 38 | Invoice received | UC-XP-011 | Practitioner | `notify_invoice` | ✅ |
| 39 | Invoice paid | UC-PRV-136 / UC-XP-012 | BP | `notify_payment` | ✅ |
| 40 | Payout released | UC-ADM-045 / UC-XP-012 | BP | `notify_payment` | ✅ |
| 41 | Team member invitation | UC-XP-018 / BP team invite | Invitee | `[UNGATED]` | ✅ |
| 42 | Connection request received | UC-PRV-100 | Practitioner | `notify_message` | ✅ |
| 43 | Connection accepted | UC-PRV-101 | Requester | `notify_message` | ✅ |
| 44 | Referral received | UC-PRV-108 | Practitioner | `notify_agreement` | ✅ |
| 45 | Referral accepted/declined | UC-PRV-111 | Sender | `notify_agreement` | ✅ |
| 46 | Support ticket received | UC-ADM-050 (submit) | Submitter | `[UNGATED]` | ✅ |
| 47 | Support ticket reply | UC-ADM-053 / UC-XP-015 | Submitter | `[UNGATED]` | ✅ |
| 48 | Support ticket resolved | UC-ADM-055 | Submitter | `[UNGATED]` | ✅ |
| 49 | Feedback received | UC-ADM-050 (feedback channel) | Submitter | `[UNGATED]` | ✅ |
| 50 | Account action by admin | UC-ADM-023/024/026 | Affected user | `[UNGATED]` | ✅ |
| 51 | Plan upgraded | UC-PRV-003 | Practitioner | `notify_payment` | ✅ |
| 52 | Plan downgraded | UC-PRV-004 / UC-XP-020 | Practitioner | `notify_payment` | ✅ |
| 53 | Payment failed | UC-ADM-041 / billing | Subscriber + admin | `[UNGATED]` | ✅ |
| 54 | Payment succeeded (receipt) | UC-ADM-054-adjacent / billing | Subscriber | `notify_payment` | ✅ |
| 55 | Subscription renewal upcoming | billing (7d) | Subscriber | `notify_payment` | ✅ |
| 56 | Weekly activity digest | opt-in | User | `notify_summary` | ✅ |
| 57 | Monthly summary | opt-in (Practitioner) | Practitioner | `notify_summary` | ✅ |

### A.2 — Missing templates added (58–69) — from gap analysis

| # | Template | UC trigger | Recipient(s) | Gate key | Why it was missing |
|---|---|---|---|---|---|
| 58 | Service inquiry received | UC-PRV-124 (inbound service request) | Practitioner | `notify_message` | Services-mode inquiries had no email (only BP proposals did) |
| 59 | Service inquiry responded | UC-PRV-124 `set_status` | Inquirer | `notify_message` | accept/decline of a service request unnotified |
| 60 | Document requested from CS/SS | UC-PRV-082 (`document_requested`) | CS or SS | `notify_assignment` | provider→steward document request unnotified |
| 61 | Document release requested | UC-PRV-196 (`document_release_requested`) | Holding steward | `notify_assignment` | release-of-custody request unnotified |
| 62 | Document signature reminder | UC-PRV-197 (`document_reminder`) | Pending signer/countersigner | `notify_plan_change` | doc-signing nudge (distinct from annual re-attest) |
| 63 | Vault item shared | UC-PRV-200 (`vault_item_shared`) | Recipient steward | `notify_docs_accessed` | shared-credential grant unnotified |
| 64 | Document updated (amend/renew/archive) | UC-PRV-192/193/194 | CS, SS | `notify_plan_change` | document lifecycle changes unnotified — `{{change_type}}` variant |
| 65 | CS flagged unresponsive | UC-XP-016 | Practitioner | `notify_practitioner_cs_unresponsive` | distinct from "Alternate activated" (T25) — the warning *before* activation |
| 66 | Contract signed | UC-PRV-137 (`sign_contract`) | BP, Practitioner | `notify_agreement` | signing event distinct from contract-created (T35) |
| 67 | Contract cancelled | UC-PRV-138 (`cancel_contract`) | Other party | `notify_agreement` | cancellation unnotified |
| 68 | Subscription cancelled — confirmation | UC-PRV-145 (`cancel_subscription`) | Practitioner | `[UNGATED]` (billing confirmation) | distinct from downgrade (T52) |
| 69 | MAAT add-on activated / deactivated | UC-PRV-210 / 211 | Practitioner | `notify_payment` | add-on billing change had no email; `{{addon_state}}` variant |

### A.3 — Partial corrections to existing templates

1. **Template 11 (Plan ready → CS + SS):** UC-PRV-036 fans out to **both** CS and SS. Produce **two role-targeted variants** (CS copy references countersign duty; SS copy references awareness/standby). One template that BCCs both is non-compliant — recipients see different next-actions.
2. **Template 13 (Vault attested → CS + SS):** same — split into CS/SS variants; the chip language differs per portal (`vault_attested_at` green chip on both, but CS gets the attestation-review CTA).
3. **Template 14 (Annual Re-Attestation due):** UC-PRV-038 + UC-XP-014 specify **three sends** (30d / 7d / day-of) — make the cadence + `{{days_until_due}}` merge tag explicit; only the day-of send is `[UNGATED]`.
4. **Template 25 (Alternate CS activated):** maps to UC-XP-019 (CS resigns → alternate). The *CS-unresponsive warning to the practitioner* (UC-XP-016) is a **separate** event — see new Template 65. Don't conflate.
5. **Template 35 (Contract created):** keep for UC-XP-010 auto-creation, but contract **signed** (T66) and **cancelled** (T67) are now separate templates — Template 35's merge tags should not imply a signature occurred.

### A.4 — Onboarding caveat (use-case-driven)

Per validation, **UC-PRV-001 and the onboarding wizard are `[UNWIRED — SIMULATED]`** in the current build — `onboarding.php` performs no backend write (no `users` insert, no Stripe, no email/2FA persistence). Templates **1 (Welcome)** and **2 (Email verification)** are therefore *specs that will not fire until onboarding is wired*. Mark both with a build-dependency note; do not assume a real registration event exists yet.

---

## §B — Notification Preference Gate  *(new rule — applies to every template)*

> **Every template must declare which `user_meta` `notify_*` key gates it.** A gated template sends only if its key is `true` **or unset** (default-on). The master switch `notify_email` gates *all* email regardless of per-event keys: if `notify_email = false`, only `[UNGATED]` templates send. `[UNGATED]` templates **always** send and ignore both `notify_email` and any per-event key.

**`[UNGATED]` set (security / safety / legal / money-critical — never suppressed):**
critical incident flow (T26–31), account locked (T8), password reset/changed (T3/4), MFA changes (T5/6), new-device login (T7), account action by admin (T50), payment failed (T53), all invitations (T17/19/41), support-ticket lifecycle (T46–49), account closure (T9), subscription cancelled confirmation (T68), vault unlocked (T28).

**Real gate keys (verified in code — use these exact strings, no invented variables):**
`notify_email` (master), `notify_critical` (always-on, disabled in UI), `notify_incident`, `notify_message`, `notify_task`, `notify_assignment`, `notify_attestation`, `notify_attestation_request`, `notify_plan_change`, `notify_plan_review`, `notify_role_change`, `notify_change_request`, `notify_vault_unlock`, `notify_docs_accessed`, `notify_invoice`, `notify_payment`, `notify_proposal`, `notify_new_job`, `notify_agreement`, `notify_checkin`, `notify_activation`, `notify_practitioner_cs_unresponsive`, `notify_summary`, `notify_platform`, `notify_views`, `notify_info_update`.

**Implementation note for the sender:** the gate is evaluated server-side at send time against `aegis_get_all_prefs($recipient_id)`; absence of a key = default-on. SMS variants (where applicable) gate on `notify_sms` independently.

---

## Step 0 — Setup

```bash
cd /home/claude
rm -rf aegis
git clone https://github.com/rehanurrashid/aegis.git
cd aegis
```

Then read via `project_knowledge_search`:
- `Aegis_Desing_Prompt.md` — design system full reference (CSS variables, type scale, color tokens, brand voice)
- `Aegis_Desing_Prompt_Short.md` — strict execution checklist
- `Aegis_Tone_Voice_Prompt.md` — copy tone (MA'AT voice)
- `CENTRALIZED-SYSTEM.md` — system overview
- `AEGIS-PROJECT-CONTEXT.md` — roles, lifecycle, terminology
- `AEGIS_USE_CASES_OUTPUT.md` — every email-firing event (the §A trigger map is derived from it)

Also read in the repo:
- `_shared/_shared.css` — extract all design tokens (colors, fonts, radii, spacing)
- `_shared/icons.php` — icon registry to inline as SVGs
- `_shared/page_head.php` — see how the brand is rendered in chrome

---

## Email Template Requirements

### Brand foundation (must inherit from Aegis design system)

- **Colors:** gold-dark `#a0813e`, surface `#fffdf7`, text `#2d2a26`, text-2 `#4a4741`, surface-2 `#f5f0e6`, red `#c0392b`, green-dark `#2f7d54`, border `#e4dfd7`
- **Fonts:** serif (Georgia/system serif) for headings + brand wordmark; sans-serif for body
- **Tone:** calm, grounded, professional — MA'AT voice (steadiness, support, clarity)
- **Brand wordmark:** "Aegis" in serif, weighted 700
- **Footer:** muted, contains "© 2026 Aegis · A MA'AT product" + unsubscribe link where relevant
- **No emoji** in subject lines or body
- **No marketing fluff** — every email serves a specific transactional purpose

### Technical constraints (HTML email reality)

- **Table-based layout** — no flexbox/grid (email clients break those)
- **Inline CSS only** — no `<style>` block, no external stylesheets
- **Max width:** 600px
- **Fallback fonts everywhere:** `font-family: 'Georgia', 'Times New Roman', serif`
- **Logo as SVG inline** (with PNG fallback URL) — no remote font loading
- **Dark mode safe** — set explicit `background-color` on every cell
- **Apple Mail + Gmail + Outlook 365 + iOS Mail** all tested mentally
- **Plain-text alternative** generated alongside every HTML version

### Layout pattern (consistent across all emails)

```
┌─────────────────────────────────┐
│  [Brand wordmark — Aegis]       │  ← header strip, 24px top padding
├─────────────────────────────────┤
│  [Email title — serif, 22px]    │
│  [Body paragraph — 14px,        │
│   line-height 1.6, color #4a..] │
│  [Optional alert/info box       │  ← surface-2 background, gold-dark border-left
│   with icon + message]          │
│  [Primary CTA button —          │  ← gold-dark background, white text, 12px radius
│   centered, 14px font]          │
│  [Secondary text / fine print]  │  ← text-3, 12px
├─────────────────────────────────┤
│  Aegis · A MA'AT product        │  ← footer, muted, centered
│  Privacy · Unsubscribe          │
└─────────────────────────────────┘
```

---

## Email Templates To Generate

For each email below, produce: (1) Subject ≤60 chars no emoji, (2) Preheader ≤90 chars, (3) HTML (inline CSS, table-based), (4) Plain-text, (5) Merge tags, (6) Trigger (UC ID), **(7) Gate key** per §B.

### Authentication & Onboarding
1. **Welcome email** (per role) — *build-dependency: onboarding `[UNWIRED — SIMULATED]`, see §A.4*
2. **Email verification** — *same build-dependency*
3. **Password reset**
4. **Password changed confirmation**
5. **MFA enabled confirmation**
6. **MFA disabled alert**
7. **New device login alert**
8. **Account locked notice**
9. **Account closure confirmation**

### Continuity Plan
10. **Plan signed confirmation** (Practitioner)
11. **Plan ready notification** (CS + SS) — *two role variants, §A.3*
12. **CS countersigned plan** (Practitioner)
13. **Vault attested by Practitioner** (CS + SS) — *two role variants, §A.3*
14. **Annual Re-Attestation due** (30d / 7d / day-of) — *cadence explicit, §A.3*
15. **Annual Re-Attestation completed** (CS + SS)
16. **Plan version updated** (CS + SS)

### Steward Designation
17. **CS invitation — external**
18. **CS invitation — internal**
19. **SS invitation — external**
20. **SS invitation — internal**
21. **CS accepted designation** (Practitioner)
22. **CS declined designation** (Practitioner)
23. **CS role change requested** (Practitioner)
24. **CS removed from plan** (CS)
25. **Alternate CS activated** (Primary unresponsive) — *activation only; warning is T65*

### Critical Incident Flow
26. **Critical incident reported** (CS — urgent)
27. **Critical incident verified by CS** (SS + Practitioner)
28. **Vault unlocked notice** (CS)
29. **Incident task assigned** (CS)
30. **Incident escalation requested** (Aegis team)
31. **Incident closed** (all parties)

### Business Partner
32. **Support Request received** (proposal ack → BP)
33. **Proposal accepted** (BP)
34. **Proposal declined** (BP)
35. **Contract created** (BP + Practitioner) — *creation only; signing is T66*
36. **Milestone submitted** (Practitioner)
37. **Milestone approved** (BP)
38. **Invoice received** (Practitioner)
39. **Invoice paid** (BP)
40. **Payout released** (BP)
41. **Team member invitation** (Agency BP)

### Network & Referrals
42. **Connection request received** (Practitioner)
43. **Connection accepted**
44. **Referral received** (Practitioner)
45. **Referral accepted/declined** (sender)

### Support & Feedback
46. **Support ticket received** (auto-ack)
47. **Support ticket reply** (admin → user)
48. **Support ticket resolved**
49. **Feedback received** (auto-ack)

### Admin / System
50. **Account action by admin** (lock/unlock/role change — with reason)
51. **Plan upgraded** (Access → Practice)
52. **Plan downgraded** (next cycle)
53. **Payment failed** (subscriber + admin queue)
54. **Payment succeeded** (receipt)
55. **Subscription renewal upcoming** (7 days before)

### Digest emails
56. **Weekly activity digest** (opt-in, `notify_summary`)
57. **Monthly summary** (Practitioner — CEUs due, plan health, network)

### NEW — Services & Documents (gap-fill, §A.2)
58. **Service inquiry received** (Practitioner)
59. **Service inquiry responded** (Inquirer)
60. **Document requested from CS/SS** (steward)
61. **Document release requested** (holding steward)
62. **Document signature reminder** (pending signer/countersigner)
63. **Vault item shared** (recipient steward)
64. **Document updated** (amend/renew/archive → CS + SS)

### NEW — Steward / Contract / Billing (gap-fill, §A.2)
65. **CS flagged unresponsive** (Practitioner — warning)
66. **Contract signed** (BP + Practitioner)
67. **Contract cancelled** (other party)
68. **Subscription cancelled — confirmation** (Practitioner)
69. **MAAT add-on activated / deactivated** (Practitioner)

---

## New Template Specifications (58–69)

> Subject ≤60 chars, preheader ≤90, MA'AT tone (no exclamation unless emergency; "Action required" prefix reserved for incident flow). Merge tags use real column/field names from the UC Data Touched sections.

### Template 58 — Service Inquiry Received
**Trigger:** UC-PRV-124 (inbound service request on a published service) · **Recipient:** Practitioner · **Priority:** Normal · **Gate:** `notify_message`
**Subject:** New inquiry for your service: {{service_title}}
**Preheader:** {{inquirer_name}} is interested in {{service_title}}. Review the request.
**Merge tags:** `{{practitioner_name}}`, `{{inquirer_name}}`, `{{service_title}}`, `{{inquiry_message}}`, `{{service_request_url}}`

### Template 59 — Service Inquiry Responded
**Trigger:** UC-PRV-124 `set_status` (accept/decline) · **Recipient:** Inquirer · **Priority:** Normal · **Gate:** `notify_message`
**Subject:** Your inquiry for {{service_title}} was {{status_label}}
**Preheader:** {{practitioner_name}} has {{status_label}} your request for {{service_title}}.
**Merge tags:** `{{inquirer_name}}`, `{{practitioner_name}}`, `{{service_title}}`, `{{status_label}}`, `{{response_note}}`, `{{service_url}}`

### Template 60 — Document Requested from CS/SS
**Trigger:** UC-PRV-082 (`document_requested`) · **Recipient:** CS or SS · **Priority:** Normal · **Gate:** `notify_assignment`
**Subject:** Document request from {{practitioner_name}}
**Preheader:** {{practitioner_name}} has requested {{document_title}}. Please review.
**Merge tags:** `{{steward_name}}`, `{{practitioner_name}}`, `{{document_title}}`, `{{request_note}}`, `{{document_url}}`

### Template 61 — Document Release Requested
**Trigger:** UC-PRV-196 (`document_release_requested`, status → `release_pending`) · **Recipient:** Holding steward · **Priority:** Normal · **Gate:** `notify_assignment`
**Subject:** Release requested for {{document_title}}
**Preheader:** {{practitioner_name}} has requested release of {{document_title}} held in your care.
**Merge tags:** `{{steward_name}}`, `{{practitioner_name}}`, `{{document_title}}`, `{{document_url}}`

### Template 62 — Document Signature Reminder
**Trigger:** UC-PRV-197 (`document_reminder`, no status change) · **Recipient:** Pending signer/countersigner · **Priority:** Normal · **Gate:** `notify_plan_change`
**Subject:** Reminder: {{document_title}} awaits your signature
**Preheader:** A gentle reminder that {{document_title}} is waiting for your signature.
**Merge tags:** `{{recipient_name}}`, `{{practitioner_name}}`, `{{document_title}}`, `{{sign_url}}`

### Template 63 — Vault Item Shared
**Trigger:** UC-PRV-200 (`vault_item_shared`, fan-out ×2) · **Recipient:** Recipient steward · **Priority:** Normal · **Gate:** `notify_docs_accessed`
**Subject:** {{practitioner_name}} shared a vault item with you
**Preheader:** You now have scoped access to {{item_title}} in {{practitioner_name}}'s vault.
**Merge tags:** `{{steward_name}}`, `{{practitioner_name}}`, `{{item_title}}`, `{{access_level}}`, `{{vault_url}}`

### Template 64 — Document Updated
**Trigger:** UC-PRV-192/193/194 (amend / renew / archive) · **Recipient:** CS + SS · **Priority:** Normal · **Gate:** `notify_plan_change`
**Subject:** {{document_title}} was {{change_type}}
**Preheader:** {{practitioner_name}} has {{change_type}} {{document_title}}. Review if needed.
**Merge tags:** `{{steward_name}}`, `{{practitioner_name}}`, `{{document_title}}`, `{{change_type}}` (amended/renewed/archived), `{{document_url}}`
**Note:** single template with `{{change_type}}` variant; renew sets a new expiry surfaced as `{{new_expiry_date}}`.

### Template 65 — CS Flagged Unresponsive
**Trigger:** UC-XP-016 (SS flags CS unresponsive) · **Recipient:** Practitioner · **Priority:** Normal (warning) · **Gate:** `notify_practitioner_cs_unresponsive`
**Subject:** Your Continuity Steward may be unresponsive
**Preheader:** {{ss_name}} has flagged {{cs_name}} as unresponsive on your plan.
**Merge tags:** `{{practitioner_name}}`, `{{cs_name}}`, `{{ss_name}}`, `{{flagged_at}}`, `{{stewards_url}}`
**Note:** distinct from Template 25 (alternate *activated*) — this is the early warning before any activation.

### Template 66 — Contract Signed
**Trigger:** UC-PRV-137 (`sign_contract`) · **Recipient:** BP + Practitioner · **Priority:** Normal · **Gate:** `notify_agreement`
**Subject:** Contract signed: {{contract_title}}
**Preheader:** The agreement for {{contract_title}} is now fully signed and active.
**Merge tags:** `{{recipient_name}}`, `{{counterparty_name}}`, `{{contract_title}}`, `{{contract_url}}`, `{{signed_at}}`

### Template 67 — Contract Cancelled
**Trigger:** UC-PRV-138 (`cancel_contract`) · **Recipient:** Other party · **Priority:** Normal · **Gate:** `notify_agreement`
**Subject:** Contract cancelled: {{contract_title}}
**Preheader:** The agreement for {{contract_title}} has been cancelled.
**Merge tags:** `{{recipient_name}}`, `{{counterparty_name}}`, `{{contract_title}}`, `{{cancel_reason}}`, `{{contract_url}}`

### Template 68 — Subscription Cancelled (Confirmation)
**Trigger:** UC-PRV-145 (`cancel_subscription`) · **Recipient:** Practitioner · **Priority:** Normal · **Gate:** `[UNGATED]` (billing confirmation)
**Subject:** Your Aegis subscription has been cancelled
**Preheader:** Your {{tier_label}} plan ends {{access_until_date}}. Your data remains safe.
**Merge tags:** `{{practitioner_name}}`, `{{tier_label}}`, `{{access_until_date}}`, `{{reactivate_url}}`
**Note:** clarifies access continues through the paid period (per pricing FAQ) — distinct from downgrade (T52).

### Template 69 — MAAT Add-on Activated / Deactivated
**Trigger:** UC-PRV-210 / 211 (`maat_addon` toggle) · **Recipient:** Practitioner · **Priority:** Normal · **Gate:** `notify_payment`
**Subject:** MAAT Continuity Steward Service {{addon_state}}
**Preheader:** Your MAAT add-on is now {{addon_state}} ({{addon_price}}/mo).
**Merge tags:** `{{practitioner_name}}`, `{{addon_state}}` (active/inactive), `{{addon_price}}`, `{{billing_url}}`
**Note:** add-on requires Practice tier; on activation reference the +$29/mo charge. Billing side is a `[STUB]` pending Stripe — copy is correct but send is contingent on the billing event existing.

---

## §A.5 — UC Closure Pass Templates (70–81) — from final gap-closure work

These twelve templates correspond to events introduced during the closure pass that fills the last 22 `[NO UC BASIS]` items. Each maps to a Laravel event in `AEGIS_LARAVEL_STRUCTURE.md §4` that registers `SendEmailNotificationListener`.

### Template 70 — Continuity Steward Resigned
**Trigger:** UC-CS-026 (`StewardResigned` event) · **Recipient:** Practitioner whose plan lost a CS · **Priority:** High · **Gate:** `notify_steward_changes`
**Subject:** Your Continuity Steward {{cs_name}} has resigned
**Preheader:** Choose a replacement from your suggested alternates. Your plan stays active.
**Merge tags:** `{{practitioner_name}}`, `{{cs_name}}`, `{{resignation_reason}}`, `{{suggested_alternates}}` (array: name + role), `{{stewards_url}}`
**Note:** include the alternates list so the practitioner can act in one click. MA'AT tone: never sound like a system error — frame as ordinary stewardship change.

### Template 71 — Critical Incident Reopened
**Trigger:** UC-CS-049 (`IncidentReopened` event) · **Recipient:** All plan parties (practitioner + CS + SS roster) · **Priority:** Urgent · **Gate:** `[UNGATED]`
**Subject:** Incident reopened — {{incident_type}} for {{practitioner_name}}
**Preheader:** {{reopened_by_name}} reopened the incident. Vault access has been restored.
**Merge tags:** `{{recipient_name}}`, `{{practitioner_name}}`, `{{incident_type}}`, `{{reopened_by_name}}`, `{{reason}}`, `{{incident_id}}`, `{{incident_url}}`
**Note:** mirror Template 26 (incident reported) styling; "ungated" because incident events bypass normal preferences.

### Template 72 — Incident Withdrawn by SS
**Trigger:** UC-SS-044 (`IncidentWithdrawn` event) · **Recipient:** Practitioner + assigned CS · **Priority:** High · **Gate:** `notify_incident`
**Subject:** Incident withdrawn — {{incident_type}} for {{practitioner_name}}
**Preheader:** {{ss_name}} withdrew the incident report. No further action needed.
**Merge tags:** `{{recipient_name}}`, `{{practitioner_name}}`, `{{ss_name}}`, `{{incident_type}}`, `{{withdrawal_reason}}`, `{{incident_id}}`
**Note:** only sent when status was `reported` (pre-verification); not used after activation. Calm, neutral tone — withdrawal is not an error.

### Template 73 — Help Article Published (system announcement)
**Trigger:** UC-ADM-059 (`HelpArticlePublished` event) · **Recipient:** All users in the article's audience segment · **Priority:** Low · **Gate:** `notify_announcement`
**Subject:** New Aegis Help Center article: {{article_title}}
**Preheader:** {{article_excerpt}}
**Merge tags:** `{{recipient_name}}`, `{{article_title}}`, `{{article_excerpt}}` (200 chars), `{{article_url}}`, `{{category}}`
**Note:** digest-eligible — if recipient's weekly digest is on, batch instead of sending standalone. Skip for category=internal.

### Template 74 — Payout Released Manually (admin override)
**Trigger:** UC-ADM-045 (`PayoutReleasedManually` event) · **Recipient:** BP or CS who owns the payout · **Priority:** Normal · **Gate:** `notify_payment`
**Subject:** Your Aegis payout of {{amount_formatted}} has been released
**Preheader:** Released by Aegis admin on {{released_at_date}}. Funds arrive within 2–5 business days.
**Merge tags:** `{{recipient_name}}`, `{{amount_formatted}}`, `{{payout_period}}`, `{{released_at_date}}`, `{{reason}}` (admin's note), `{{payouts_url}}`
**Note:** distinct from automatic payout release (T46) — this one names the admin action explicitly, "released by Aegis admin," so the BP/CS understands the manual nature.

### Template 75 — Email Address Verified (welcome)
**Trigger:** UC-PRV-213 (`EmailVerified` event) · **Recipient:** The user · **Priority:** Normal · **Gate:** `[UNGATED]`
**Subject:** Welcome to Aegis — your email is verified
**Preheader:** Continuing your continuity plan from where you left off.
**Merge tags:** `{{user_name}}`, `{{role_label}}` (Practitioner/CS/SS/BP), `{{next_step_url}}` (deep-link to first incomplete onboarding step), `{{role_specific_guidance}}` (1 sentence per role)
**Note:** sent ONCE upon successful verification; replaces the generic registration confirmation for verified-email users. Distinct from T1 (registration confirm) which fires before verification.

### Template 76 — Invoice Voided
**Trigger:** UC-BP-055 / UC-CS-084 (`InvoiceVoided` event) · **Recipient:** Invoice recipient (practitioner) · **Priority:** Normal · **Gate:** `notify_invoice`
**Subject:** Invoice {{invoice_number}} from {{sender_name}} has been voided
**Preheader:** No payment is due. {{sender_name}} voided this invoice on {{voided_at_date}}.
**Merge tags:** `{{practitioner_name}}`, `{{invoice_number}}`, `{{sender_name}}`, `{{voided_at_date}}`, `{{amount_formatted}}`, `{{void_reason}}`, `{{invoice_url}}`
**Note:** clarifies that no further action is needed. If the invoice was already paid, this template is not sent — use T55 (refund) instead.

### Template 77 — Proposal Submitted (notify Practitioner)
**Trigger:** UC-BP-030 (`ProposalSubmitted` event) · **Recipient:** Practitioner who posted the job · **Priority:** Normal · **Gate:** `notify_proposal`
**Subject:** New proposal on your Support Request: {{job_title}}
**Preheader:** {{bp_name}} submitted a proposal — bid {{bid_formatted}}, start {{proposed_start_date}}.
**Merge tags:** `{{practitioner_name}}`, `{{bp_name}}`, `{{bp_organization}}`, `{{job_title}}`, `{{bid_formatted}}`, `{{proposed_start_date}}`, `{{proposal_url}}`, `{{job_url}}`
**Note:** practitioner needs enough at-a-glance info to triage without clicking. Show bid + start date in preview.

### Template 78 — Proposal Withdrawn (notify Practitioner)
**Trigger:** UC-BP-032 (`ProposalWithdrawn` event) · **Recipient:** Practitioner who posted the job · **Priority:** Low · **Gate:** `notify_proposal`
**Subject:** {{bp_name}} withdrew their proposal on {{job_title}}
**Preheader:** Other proposals are still open for review.
**Merge tags:** `{{practitioner_name}}`, `{{bp_name}}`, `{{job_title}}`, `{{withdrawal_reason}}`, `{{job_url}}`
**Note:** only sent if proposal was in `pending` or `under_review` (not `accepted`/`declined`). Keep tone neutral; withdrawal is common.

### Template 79 — News Post Published (digest-only)
**Trigger:** UC-PRV-241 (`NewsPostPublished` event) · **Recipient:** Author's network followers · **Priority:** Low · **Gate:** `notify_news_posts` + digest preference
**Subject:** {{author_name}} posted: {{post_title_or_excerpt}}
**Preheader:** {{post_excerpt}}
**Merge tags:** `{{recipient_name}}`, `{{author_name}}`, `{{post_title_or_excerpt}}`, `{{post_excerpt}}` (240 chars), `{{post_url}}`, `{{category}}`
**Note:** **never sent in real time** — always batched into the weekly digest (`DispatchDigestsCommand`). Standalone send is `[BLOCKED]`. Template exists for digest builder to render.

### Template 80 — News Comment Received
**Trigger:** UC-PRV-242 (`NewsCommented` event) · **Recipient:** Post author · **Priority:** Low · **Gate:** `notify_news_comments`
**Subject:** {{commenter_name}} commented on your Aegis post
**Preheader:** "{{comment_excerpt}}"
**Merge tags:** `{{author_name}}`, `{{commenter_name}}`, `{{comment_excerpt}}` (160 chars), `{{post_title}}`, `{{post_url}}`
**Note:** never sent for the author commenting on their own post. Single primary CTA: "View comment."

### Template 81 — Event RSVP Received (notify Organizer)
**Trigger:** UC-PRV-246 (`EventRsvpReceived` event) · **Recipient:** Event organizer · **Priority:** Low · **Gate:** `notify_event_rsvp`
**Subject:** {{respondent_name}} RSVP'd {{response}} to {{event_title}}
**Preheader:** {{rsvp_summary}} — {{attending_count}} attending, {{maybe_count}} maybe.
**Merge tags:** `{{organizer_name}}`, `{{respondent_name}}`, `{{response}}` (Yes/No/Maybe), `{{event_title}}`, `{{event_date}}`, `{{rsvp_summary}}`, `{{attending_count}}`, `{{maybe_count}}`, `{{event_url}}`
**Note:** rate-limited at organizer level: 1 digest email per event per 6 hours rather than per-RSVP, to avoid spam during high-volume RSVPs.

---

## §A.6 — Closure Pass Trigger Map Summary

For quick reference when wiring `SendEmailNotificationListener`:

| Event class | Template | UC | Gate |
|---|---|---|---|
| `Steward\StewardResigned` | T70 | UC-CS-026 | `notify_steward_changes` |
| `Incident\IncidentReopened` | T71 | UC-CS-049 | `[UNGATED]` |
| `Incident\IncidentWithdrawn` | T72 | UC-SS-044 | `notify_incident` |
| `Admin\HelpArticlePublished` | T73 | UC-ADM-059 | `notify_announcement` |
| `Admin\PayoutReleasedManually` | T74 | UC-ADM-045 | `notify_payment` |
| `Auth\EmailVerified` | T75 | UC-PRV-213 | `[UNGATED]` |
| `Business\InvoiceVoided` | T76 | UC-BP-055/CS-084 | `notify_invoice` |
| `Business\ProposalSubmitted` | T77 | UC-BP-030 | `notify_proposal` |
| `Business\ProposalWithdrawn` | T78 | UC-BP-032 | `notify_proposal` |
| `News\NewsPostPublished` | T79 | UC-PRV-241 | `notify_news_posts` (digest only) |
| `News\NewsCommented` | T80 | UC-PRV-242 | `notify_news_comments` |
| `News\EventRsvpReceived` | T81 | UC-PRV-246 | `notify_event_rsvp` |

---

## §A.7 — New Notification Gate Keys (extends §B)

Three new gate keys introduced by the closure pass. Add to `user_preferences.notify_*` columns:

| Gate key | Default | Used by | UC |
|---|---|---|---|
| `notify_news_posts` | true (digest only) | T79 | UC-PRV-241 |
| `notify_news_comments` | true | T80 | UC-PRV-242 |
| `notify_event_rsvp` | true | T81 | UC-PRV-246 |

The existing `notify_announcement`, `notify_steward_changes`, `notify_incident`, `notify_payment`, `notify_invoice`, `notify_proposal` gates are reused — no new columns required for templates 70, 71, 72, 73, 74, 75, 76, 77, 78.

---

## Output Format

For each template generate a section like:

```markdown
## Template 26 — Critical Incident Reported
**Trigger:** UC-SS-030 (SS reports critical incident)
**Recipient:** Assigned Continuity Steward(s)
**Priority:** Urgent · **Gate:** [UNGATED]
**Subject:** Action required — Critical incident reported for {{practitioner_name}}
**Preheader:** {{ss_name}} has reported a {{incident_type}}. Please review and verify.
**Merge tags:** {{cs_name}}, {{practitioner_name}}, {{ss_name}}, {{incident_type}}, {{incident_time}}, {{incident_id}}, {{verify_url}}
### HTML version
[Full HTML with inline CSS]
### Plain text version
[Plain text with same merge tags]
```

---

## Working Rules

- **Use the design system tokens directly** — no improvised hex values
- **MA'AT tone** — no urgency-bait, no exclamation marks unless an emergency, no marketing phrases
- **Subject lines descriptive** — "Action required" prefix only for incident-flow emails
- **CTAs purposeful** — every email has exactly ONE primary action button
- **Notification gate (NEW, §B)** — every template declares its `notify_*` gate key; `[UNGATED]` templates always send; `notify_email=false` suppresses all gated email
- **Merge tags = real fields** — match UC Data Touched / real column names; no invented variables
- **Test mental render** — verify clean in Gmail's preview pane
- **Accessibility** — alt text on all images, 4.5:1 contrast minimum, semantic tables with role="presentation"
- **No links to internal `_shared/` paths** — every link → `https://aegis.devlet.tech/{route}` with merge-tag tokens

---

## Start Here

After Step 0 setup, generate templates in order — start with **Authentication & Onboarding (1–9)**, complete that block + show preview, get approval, then move to **Continuity Plan (10–16)**, and so on through **Services & Documents (58–64)** and **Steward/Contract/Billing (65–69)**. Do NOT generate all 69 in one pass — too much volume to review.

**Scope:** 81 templates total (57 original + 12 gap-fill §A.2 + 12 closure-pass §A.5), each UC-grounded per §A, each with a §B gate key. Honor the §A.3 partial corrections and the §A.4 onboarding build-dependency. Generate templates 70–81 in a separate batch *after* the original 1–69 are reviewed and approved.
