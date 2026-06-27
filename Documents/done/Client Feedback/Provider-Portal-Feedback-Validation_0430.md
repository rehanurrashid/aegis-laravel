# Provider Portal — Feedback Validation (w_0430)

**Purpose:** Validate every change requested in `Provider_Dashboard_Feedback_w_0430.docx` against the *current* centralized Provider Portal in the Aegis KB, and identify what still needs work.

**How this was checked:** Each feedback bullet was traced to the live file(s) in the KB (`overview.php`, `dashboard.php`, `continuity-stewards.php`, `support-stewards.php`, `edit-profile.php`, `network.php`, `provider.php`, `referrals.php`, `job-postings.php`, `important-documents.php`, `vault.php`, `finances.php`, `activity.php`, `news.php`, `events.php`, `settings.php`).

**Status legend**
- ✅ **DONE** — implemented as requested
- 🟡 **PARTIAL** — partly done; specific gap noted
- ❌ **NOT DONE** — still old copy/structure
- 🔎 **VERIFY** — needs a live click-through (bug report / runtime behavior, can't confirm statically)
- ❓ **QUESTION** — client raised a question, not a change; addressed at the end

> **Important context:** The 0430 doc was written against the *old, pre-centralization* portal. A large amount of it is already absorbed — "Professional Will" is essentially gone, escrow language is mostly stripped from terminology, the network was restructured, the public profile dropped its duplicated activity feed, and the CEU logging modal was built. The work that remains is concentrated in **five files**: `continuity-stewards.php`, `support-stewards.php`, `overview.php`, `provider.php` (public profile), and `finances.php` — plus a long tail of smaller copy swaps.

---

## 0. Summary — where each file stands

| File | Overall status | Headline remaining work |
|---|---|---|
| `overview.php` | 🟡 Partial | Banner body, "Why Aegis" bodies, How-to bodies, FAQ rewording + **8 new FAQs**, key-terms expansion, delete pricing FAQ |
| `dashboard.php` | ✅ Mostly done | Add "Vault documentation…" attest line; CEU **requirements** (state/due/type) section; small emoji/copy nits |
| `continuity-stewards.php` | ❌ Major work | Role terms, "Role Step-up", **Approved Critical Incidents** tab, full responsibilities taxonomy (remove timeframes), relationship options, notify-me, compensation entry, readiness labels, "I'm a steward for" copy |
| `support-stewards.php` | ❌ Major work | Banner, drop admin/billing/scheduling role model → 2 roles, remove quarterly review, new 5-section permission matrix + add-task, simplify delegate, notify-me, new "Planning & Guidance" tab, onboarding checklist |
| `edit-profile.php` | 🟡 Partial | "Medical License"→"License", license # not required, custom add buttons (bug), session-length custom entry; CEU requirements section |
| `network.php` | ✅ Mostly done | "Clinical Services"→"Services" labels; verify filters |
| `provider.php` (public profile) | 🟡 Partial | Referral-tip rewrite, remove Outcome/Performance Metrics + PHQ-9, genericize EHR, "Clinical Services"→"Services", map |
| `referrals.php` | ❌ Not done | Header→Referral Coordination, hero copy, patient→client, remove PHQ-9, remove counter-refer, add self-pay |
| `job-postings.php` | ❌ Not done | "Post a Job…"→"Request Support", "My Job Postings"→"My Support Requests", all button labels, tab/center copy |
| `important-documents.php` | 🟡 Partial | Header copy, "All Agreements"→"All Documents" + agreements/documents split, "Add Document", sample-forms copy |
| `vault.php` | 🟡 Partial | "Add to Emergency Vault"→"Add to Vault", "People With Access"→"…granted access during a critical moment", "Sensitive Information" rename, remove pending-sig + expiring-license, intro text |
| `finances.php` | ❌ Not done | **Escrow still present (93 refs)** — strip escrow/balance, consolidate to retainer / annual fee / retainer+annual, remove agreement-expires / last-top-up / funds-held-by-Aegis |
| `activity.php` | 🟡 Partial | "Emergency"→"Critical Incident" in tab/labels; confirm no alert items; answer "HIPAA event" |
| `news.php` | ❌ Not done | Remove Library from tab, move compliance/licensure alerts to dashboard, keep events/resources only |
| `events.php` | 🟡 Partial | Sub-copy → "health and well-being professionals"; "Training"→"Trainings" (minor) |
| `settings.php` | 🟡 Partial | 2× "Professional Will" → "Continuity Plan" |

---

## 1. `overview.php` — Overview / Start Here 🟡

**Key Terms list** — ❌ The list (12 terms) does **not** match the client's "Refined Key Terms & Responsibilities." Missing terms to add: *Practitioner, Critical Moment / Critical Incident, Alternate Support Steward, Alternate Continuity Steward, Support Continuity Steward, Shadow Practitioner, Important Documentation, Aegis, MA'AT*. Several existing definitions are paraphrases, not the client's exact text. The instruction was "add only the terms provided and not change any of the text" → the provided definitions should be used verbatim.

**Overview banner** — ❌ Hero sub (line ~617) still reads "You are a Practitioner on the Aegis platform… This guide explains key terms, why Aegis matters…". Replace with the "Welcome to Aegis — a place to put thoughtful structures in place…" copy. (Title still says "let's protect your practice" — client's new language avoids "protect.")

**Start-here banner ("Plan for the unexpected")** — 🟡 Partly aligned (line ~663 mentions Continuity Plan), but should match the client's exact "Plan for the unexpected. Complete your Continuity Plan, establish and prepare your Continuity Steward and Support Steward…" text.

**Why Aegis cards** — 🟡 **Headings updated** (Supporting the Continuity…, Care Remains Connected, Keeping What Matters Together, Built for Health and Wellbeing Practitioners, Sustaining and Growing Your Practice, Clarity and Steadiness) but the **body text under each does not match** the client's provided copy — several still carry the old "patient/protected" framing with only the title swapped. Bodies need to be replaced with the exact client text for each card.

**How to Use Aegis steps** — 🟡 Titles updated and "Professional Will"→"Continuity Plan" done, but step **bodies** still carry old copy (e.g. "Assign a Continuity Steward" still says "Designate a licensed professional…"; "Assign a Support Steward" still ends "…and the Aegis team"). Replace each step body with the client's softened text.

**FAQ** — 🟡 / ❌
- Existing questions present but several **bodies need rewording** to the client's exact new text (Continuity Plan, Vault documents, Integrative Network, "difference between CS and SS", "first thing I should do", "How often…").
- "What types of emergencies can be reported?" → reframe to the "You decide what to include…" answer; consider retitling.
- "What plan do I need — Continuity Access or Continuity Practice?" → **delete** from overview (client wants it on the MA'AT website FAQ).
- **8 new FAQs to ADD** (none present): *What happens when a critical incident is activated? · What control do I have over access and permissions? · Who can report a critical incident? · Why is continuity planning important for my practice? · Does Aegis replace my existing systems or workflows? · What if I never need to use my Continuity Plan? · What does my Continuity Steward and Support Steward experience?* (client gave two variants of the last — use the fuller one).
- "Can I invite someone who is not already on Aegis…" → update to specify the email is entered **in the Continuity Steward tab** (addresses Q7).

---

## 2. `dashboard.php` — Continuity Dashboard ✅ (mostly done)

- ✅ "Professional Will & Continuity Steward" center heading → now "Continuity Plan · Active since…". Professional Will fully gone.
- ✅ "Activate Succession" banner/modal → **"Activate Continuity Support"** (visible text); "Reason for Activation" → **"Incident Type"**.
- ✅ "What happens next" text shortened to the new softer phrasing (Vault access per Continuity Plan permissions).
- ✅ **CEU tracker built** — modal has full category list (Ethics, Supervision, Telehealth, Safety, Quality, HIV, Child Abuse Assessment & Reporting, IPV/DV, Assessment & Diagnosis, Referral & Interventions, Alcohol/Substance, Publications, Teaching/Education/Training, General CEUs, Cultural Competency, Suicide, Other), synchronous/asynchronous, annual/biannual cycle, hours, date, certificate upload.
- 🟡 **Annual Review attestation list** — has 7 items but is **missing the explicit "Vault documentation is complete and accurate"** line the client asked to add (current item is "Support documentation is complete and accurate").
- 🟡 **CEU *requirements* tracking missing** (Q10) — the modal logs *completed* CEUs against a hardcoded 30-hr target, but there is no section to **define requirements**: due date, state(s), CEU type(s) required, and annual-vs-biannual per requirement. This is the substantive gap behind the "CEU Hours required" action-required alert.
- 🟡 Minor: "✓ Verified" badges use the literal `✓` character (design system says use `aegis_icon('check')` — no emoji/✓ in rendered HTML). One leftover toast string says "Please select a reason for activation".
- 🔎 "Manage Continuity Steward → task lists not loading" — links to `continuity-stewards.php`; needs a live click-through to confirm the task lists render (the CS responsibilities section there is still old, see §3).

---

## 3. `continuity-stewards.php` — Continuity Steward tab ❌ (largest remaining effort)

- ❌ **Role terms** — still "Primary / Secondary / **Tertiary** Continuity Steward" (incl. an "Add Tertiary Continuity Steward" CTA). Client wants: **Primary Continuity Steward**, **Support Continuity Steward**, **Alternate Continuity Steward** — and **Tertiary removed** entirely. (Note: schema/seed still carry a `tertiary` role per project memory; UI taxonomy must be reconciled.)
- ❌ **"Set Role"** step label → rename to **"Role Step-up"**.
- ❌ **"Approved Critical Incidents"** — no such tab/section exists. Add a step listing the 7 approved incidents (Death, Short-Term Incap., Long-Term Incap., Geopolitical/Conflict, Natural Disaster, Missing Person, Detainment) with a **per-incident "verification required?" toggle** and the intro line "Providers may indicate whether verification is required before a Continuity Response is initiated for each selected critical incident."
- ❌ **Responsibilities taxonomy** — still the **old timeframe-based list** ("within 24–72 hours", "HIPAA-compliant within 72 hours", "Notify Aegis emergency line within 24 hours") with "patient" language. Replace wholesale with the client's new 7-section + post-practice taxonomy (Immediate Practice Stabilization → Professional & Regulatory Alignment → Controlled Substances → Records & Information Stewardship → Financial & Administrative Stewardship → Practice Presence & Relationships → Practice Closure → Post-Practice Stewardship), **remove all timeframes**, and update the intro text to "Select all responsibilities this Continuity Steward is authorized and expected to carry out…".
- ❌ **Relationship to You** options — still includes "Hospital / Institution" (remove); missing "**Designated Continuity Steward**"; "Other" needs a free-text entry.
- ❌ **Invite-external copy** — still "Aegis users can view and sign… free if serving only you — they must upgrade…". Replace with "If you invite an external Continuity Steward, they will receive an invitation to join Aegis. Their account is provided at no cost as they support your practice."
- ❌ **"Search Aegis Users or Enter External Email"** is one combined label — client wants the search on its own line and the external-email entry as a separate, clearly-labeled field above its input.
- ❌ **"Activate Succession"** button + "ACTIVATE SUCCESSION" confirm + "Activate Succession Now" still present (lines ~419, ~1312, ~1320) → "Activate Continuity Support".
- ❌ **Readiness score labels** — "Patient List (Active) · 68 patients" → "**Sensitive Information & Credentials (Vault)**"; "patient" language throughout; confirm "Both Agreements Signed" reads "**Agreement(s) Signed**"; ensure **Tertiary removed** from the readiness breakdown.
- ❌ **"I'm a Continuity Steward for" view** — "…these are your legally agreed obligations in the event of his incapacitation." → "…these are the responsibilities you have agreed to carry out in the event of a critical moment." Also soften the "legal obligations / manage in an emergency" banner copy per the client text.
- ❌ **Request to be Released** — option "Workload — unable to take on…" → "**Unable to fulfill Continuity Steward responsibilities**"; **remove "Conflict of interest"** option.
- ❌ **Notify-me section** — not present. Add the 8 toggles: Annual Re-Attestation complete · changes requested · information updated · roles/permissions change · Important Documents accessed · CS added/removed/updated · critical incident reported · Continuity Response activated.
- ❓/🟡 **Compensation entry** — the send-agreement section references "no compensation" but there is no field to enter compensation terms. Add a compensation field (or remove the assertion).
- 🔎 **Patient/"patient"** appears 13× across this file — sweep to "client".

---

## 4. `support-stewards.php` — Support Steward tab ❌ (major effort)

- ❌ **Banner** — still "Authorize trusted staff to handle specific administrative, billing, and scheduling tasks… tracked and logged for compliance." → "Designate trusted individuals to support communication, coordination, and key tasks during a critical moment, guided by your Continuity Plan."
- ❌ **Role model** — still the old "Support Steward Role Type" with administrative/billing/scheduling/patient-intake descriptions. Client wants **only two roles: Support Steward and Alternative Support Steward**, and all the scheduling/billing/patient-intake/record-management text removed.
- ❌ **Permission matrix** — still the old admin matrix. Replace with the client's 5-section responsibility taxonomy (**Activation & Verification · Access & Resource Coordination · Oversight & Coordination · Financial Responsibilities · Completion & Transition**), the exact sub-items provided, **the ability to add custom tasks under each header**, and assignment to Support Steward / Alternative Support Steward.
- ❌ **Program settings** — "Log all Support Steward actions (recommended for HIPAA)" → "…(recommended)"; **delete** auto-suspend-on-overdue rule (appears already removed — verify); **remove the entire Quarterly Access Review** apparatus (alert, stat chip "Access Review Due", review modal, "required for HIPAA" notes) — quarterly belongs to the *practitioner's own* updates, not SS.
- ❌ **Stat chip / due label** — "Access Review Due" → "**Annual Attestation Due ___"**; **remove "Fill a Gap"** (verify) and **"Delegate First Task"** (still present, line ~1759).
- ❌ **Delegate Task modal** — simplify: remove priority level, due date, and the bottom confirmation-of-completion section (client considers delegation duplicative of permissions/responsibilities; keep it optional).
- ❌ **Notify-me** — add the 7 toggles (same as CS minus "CS added/removed/updated").
- ❌ **Onboarding checklist** — still old "Administrative Support Steward / before handling any tasks" content. Replace with the client's "Onboarding and Planning for Critical Moments (Informational)" checklist; make items provider-editable.
- ❌ **New tab: "Planning and Guidance for Support Steward Readiness"** — add, with the three informational checklists the client supplied (Onboarding & Planning; Active Critical Moment Guidance; Continuity Steward Unavailable / Non-Responsive). Mirror the educational-tab pattern from Overview.
- ❌ **"Emergency Activation" / "ACTIVATE EMERGENCY"** confirm (line ~2077) → "Activate Continuity Support".
- ❌ **"Edit Support Steward"** — "Job Title" → "**Relationship**" (manual entry), remove Organization, remove SS role type; reword "Changes to contact details will trigger an amendment notice…" to the client's softer version.
- 🔎 **Patient** language throughout (intake, records) → "client".

> **Note for CS & SS "Planning & Guidance" content:** the client also asks for parallel readiness/guidance content on the *CS portal* and *SS portal* dashboards. Those portals are **not built yet**, so capture the copy now and slot it when those portals are scaffolded.

---

## 5. `edit-profile.php` — Edit Profile 🟡

- ❌ **"Medical Licenses"** card title → "**License**" (and the credential card label). 
- ❌ **License Number required** — still marked required (`ep-label-req`) at multiple spots. Make optional (do not require).
- ✅ **Availability online/in-person** — service-delivery select exists ("In-Person Only / Telehealth Only / Both"). Confirm it is surfaced where the client expects.
- 🟡 **Custom Specialty or Service** — a custom field exists, but the client reports (a) confusion that custom items append at the end rather than entering under Service *or* Specialty, and (b) **the Add button doesn't work**. Treat as a behavior fix (🔎) plus an UX placement change.
- 🟡 **Session / Meeting Length** — field exists; client needs the ability to **enter a custom length** when not listed (intensives). Confirm it isn't a fixed dropdown without an "Other" entry.
- 🔎 **Insurance Add button** — client reports it doesn't work. Behavior fix.
- ❌ **CEU requirements section** — not present here. Decide whether CEU *requirements* (state, due date, type, cycle) live under profile edit or dashboard; today only the dashboard logs *completed* CEUs (see §2).

---

## 6. `network.php` — Network ✅ (mostly done)

- ✅ Main title → "**My Network**" (was Integrative Network).
- ✅ **Business Partners subsection** added, with its own sub-tabs, and **Integrative Care Network** + **Shadow Network** separated (matches the "separate like shadow network" request).
- ❌ **"Clinical Services"** still used in tooltips/labels ("Clinical Services Available", "Request Service → Clinical Services") → "Services".
- 🟡 **Pending requests review copy** — "Accepting adds them to your network; declining removes the request" → "Accepting adds the connection to your network…". Verify in the Pending Connection Requests modal.
- 🔎 **Filters work for integrative & shadow network?** (Q12) — behavior check.

---

## 7. `provider.php` — Public Provider Profile (viewed from Network → Review) 🟡

- ✅ **Recent Activity feed removed** (explicit comment: "Activity Feed REMOVED — duplicated content lived here").
- ❌ **Referral tip** — still "Include a brief **clinical** summary with referrals. Prefers warm handoffs — call ahead when possible. Direct Messaging supported (**CCD**)." → "Include a brief summary when making referrals. Prefers warm handoffs when possible. Can receive shared records or documentation through secure methods."
- ❌ **Outcome & Performance Metrics section** (incl. **PHQ-9 tracking** row) — still present. **Remove** entirely (logged-in viewers shouldn't see another provider's outcome metrics).
- ❌ **EHR line** — "Epic · Direct Messaging Enabled" → genericize ("secure methods").
- ❌ **"Clinical Services Offered"** heading → "**Services**".
- ✅/🟡 **Online/in-person indicators** present in hero + practice/contact; confirm the practice & contact block explicitly states online / in-person / both.
- 🔎 **Map (Q13)** — there is **no interactive map** component, only a `map-pin` location label. If a "view on a map" affordance is expected, it needs building; otherwise remove the implication.

---

## 8. `referrals.php` — Referrals ❌

- ❌ **Header** "Referral Management" (title, page_title, breadcrumb) → "**Referral Coordination**".
- ❌ **Hero title** "Track, Send & Manage Referrals" → "**Send, receive, and manage referrals**".
- ❌ **Hero sub** still "…patient referrals across your clinical network — accept, **counter-refer**, or follow up…" → "client", **remove counter-refer** (counter-referral feature to be removed), generic network language.
- ❌ **"Patient Information"** (full-details + pending) → "**Client Information**".
- ❌ **PHQ-9** row → remove.
- ❌ **Self-pay option** → add.

---

## 9. `job-postings.php` — Jobs ❌

- ❌ **Hero title** "Post a Job & Hire Business Partners" → "**Request support and connect with business partners**".
- ❌ **Tab name** (post a job) → "**Support & Services**".
- ❌ **Top-right + all "Post a Job" buttons** → "**Request Support**".
- ❌ **"My Job Postings"** → "**My Support Requests**".
- ❌ **"Jobs and Hiring Center"** text → "**Support & Services**".

> "Request Support" appears nowhere in the file yet — this rename is entirely outstanding.

---

## 10. `important-documents.php` — Important Documents 🟡

- ✅ "Professional Will" → "Continuity Plan" (Continuity Plan section + party meta present).
- 🟡 **Header/sub copy** — current sub ("Every agreement, attachment, and amendment in one place…") doesn't match the client's "Manage agreements and supporting documents shared between Practitioners, Continuity Stewards, and Support Stewards."
- ❌ **"Add Document"** — no explicit Add Document affordance found; client wants one added.
- ❌ **"All Agreements" → "All Documents"** and a clear **split between Agreements and Documents** (the tab still reads "All Agreements (14)"; this section should "house agreements, amendments and other support documents").
- 🟡 **Sample-forms copy** — MSA/NDA/SOW present in a dropdown; the description should be updated to the client's "Access sample templates — including MSAs, NDAs, SOWs, **Continuity Plans**, and MOUs…" text (and "Professional Wills" → "Continuity Plans" in any forms list).

---

## 11. `vault.php` — Document Vault 🟡

- ✅ "Emergency Vault Status" → "**Vault Status: Secure**".
- ❌ **"Add to Emergency Vault"** (toolbar + modal title) → "**Add to Vault**".
- ❌ **"People With Access"** → "**People granted access during a critical moment**".
- ❌ **"Emergency Document" / "Other Emergency Document"** → "**Sensitive Information**".
- ❌ **Remove Pending Signature** items (belong in Important Documents / agreements).
- ❌ **Remove Expiring License** info (belongs under Profile / Personal Information).
- ❌ **Intro text** — add "Securely store sensitive documents and access information for use during a verified critical moment."
- 🟡 **Headers** — confirm zones are "System Access Credentials" and "Client Roster" and that a separate "Licenses & Credentials" zone is removed; "Client Roster / Patient List" option still says "Patient List" → client language.

---

## 12. `finances.php` — Finances ❌

- ❌ **Escrow is still the dominant model** (~93 references: `.escrow-card`, "Current/New escrow balance", "Pre-Funded", escrow documentation). Client wants: **delete Total Escrow Balance, delete escrow (leave Balance), remove pre-funded escrow / "funds held by Aegis", delete Agreement Expires, delete Last Top-up.**
- 🟡 **Payment model** — there *is* a change-payment-model modal and some retainer / annual-fee scaffolding, but the model needs to be consolidated to exactly: **Retainer · Annual Fee · Retainer + Annual Fee**, and the control labeled "**Update Payment Model**".
- ❓ **Q3** — "Does Finances include money generated from service requests for practitioner services through Aegis?" — addressed below.

---

## 13. `activity.php` — Activity Log 🟡

- 🟡 **"Emergency" → "Critical Incident"** — the category label is "Critical Incident" in places, but the quick-filter tab and several labels still say **"Emergency"** → standardize on "Critical Incident."
- 🟡 **Alerts removal** — client wants the activity log to track **actions only**, not alerts (no credential-expiring notes, no compliance alerts). The page reads as an event feed; confirm no alert-type rows leak in, and that the "compliance tab" alerts she referenced are gone.
- ❓ **"What is a HIPAA event?"** (Q2) — the page tags events that touch PHI (HIPAA-logged). Answer below; consider a tooltip/legend so the term is self-explanatory.

---

## 14. `news.php` — News & Resources ❌

- ❌ **Remove Library from inside the tab** — `?view=library` mode + Library videos/guides are still wired in.
- ❌ **Move practice alerts (compliance, licensure) to the primary dashboard** — `compliance` filter + `aegis_count_news_compliance_alerts()` still live here.
- ❌ **Delete all non-event content and update alerts** — the page should end up events/resources-focused, with compliance/licensure alerts relocated to the dashboard.

---

## 15. `events.php` — Events & Training 🟡

- ✅ "Healthcare" removed from the title (reads "Events & Training").
- 🟡 Minor: client wrote "Events & **Trainings**" (plural) — confirm desired spelling.
- ❌ **Sub-copy** still "CEU courses, conferences, workshops and networking for **mental health professionals**." → "CEU courses, conferences, workshops, **and** networking for **health and well-being professionals**." (note added Oxford comma).

---

## 16. `settings.php` — Settings 🟡

- ❌ Two list items still read "**Professional Will**" (lines ~1717, ~1739) → "**Continuity Plan**" (the only remaining raw "Professional Will" strings in the portal outside the intentional "commonly known as…" note in overview).

---

## 17. Cross-cutting items

- **"Activate Succession" / "Emergency Activation"** still appears as visible text in `continuity-stewards.php` and `support-stewards.php` → "Activate Continuity Support" (dashboard already done).
- **"patient" → "client"** sweep still needed in `continuity-stewards.php`, `support-stewards.php`, `referrals.php`, `vault.php`, and `provider.php`.
- **Emoji / `✓` in rendered HTML** — a few literal `✓` characters remain (e.g. dashboard CEU "✓ Verified") → `aegis_icon('check')` per the design pre-flight.
- **Pricing inconsistency to resolve (gated by "hold financial copy"):** `overview.php` shows **Continuity Access $39 / Continuity Practice $79**; the master instructions §10 list **$29 / $49**. Confirm the correct figures before touching this (and note the FAQ pricing block is slated for deletion anyway).

---

## 18. Client's 14 questions — recommended answers / dispositions

1. **Upload own plan vs. Aegis-generated?** Not yet built. Add an "Upload your own Continuity Plan" path alongside the builder on `continuity-plan.php` once the client sends the template. (Out of current scope until template arrives.)
2. **What is a HIPAA event? (activity)** An action that touches Protected Health Information (e.g., a CS viewing the client roster or revealing a credential during a verified incident). It's flagged so the access is auditable. Recommend a small legend/tooltip on `activity.php`. The activity log should record **actions only** — alerts must be removed.
3. **Does Finances include income from service requests via Aegis?** Not currently. `finances.php` is continuity-payment (practitioner ↔ CS) only. Service-request earnings would be a separate stream — confirm whether to surface it here or in a future earnings view.
4. **If a provider is also a CS, how is that managed?** Per the architecture, one identity can hold multiple roles; the CS work lives in the (not-yet-built) **CS portal**, reached via the portal switcher — not inside the Provider Portal CS tab. The Provider CS tab is identity/routing only.
5. **Is the CS tab where invitation + triggering events are configured (vs. at plan execution)?** Yes — designation, per-incident authorization, and the **"verification required?"** choice should live on `continuity-stewards.php` (the new **Approved Critical Incidents** step, §3). Support-vs-Alternate division of duties should be selectable there. "Add for all persons without manual entry" is a reasonable convenience toggle to add.
6. **Improve the Overview → "assign CS/SS" workflow (links vs. assign-in-place).** Today the steps link out. Consider an inline assign action, or at minimum land the user directly on the relevant wizard step. Capture how Alternate/Support CS and Alternate SS get added in the same flow.
7. **Where is the CS/SS invite email entered?** On `continuity-stewards.php` (external-email field). Update the FAQ answer to say so explicitly (§1), and separate the search vs. external-email inputs (§3).
8. **Manage expiring/expired credentials (archive)?** Renewals currently route to "add a credential." Add an **archive** action for superseded credentials in `edit-profile.php`.
9. **How is "Average Response Time" calculated?** Needs a defined source (e.g., median time from referral received → first response). Define and document, or remove the metric until it's real.
10. **Where do providers enter CEU *requirements*?** This is the real gap (§2/§5): build a requirements section capturing state(s), due date, required CEU types, and annual-vs-biannual cycle — distinct from the completed-CEU log already on the dashboard.
11. **Peer reviews — how are they given?** No "leave a review" flow exists. Either build a review-submission path or hide the peer-reviews surface until it's real.
12. **Do filters work for integrative & shadow network?** Behavior check on `network.php`.
13. **Does the "view on a map" work?** No interactive map exists today — only a location label on `provider.php`. Build or remove.
14. **How will Aegis verify members for the Verified badge?** Per context doc, the Aegis Verified module (gov ID + credentials + Code of Conduct e-sign + Checkr background check) is Phase 2 / not yet wired. Confirm the verification policy before exposing the badge as earnable.

---

## 19. Suggested sequencing (when you action this)

1. **High-impact copy swaps (fast):** `settings.php`, `events.php`, `referrals.php`, `job-postings.php`, `vault.php` labels, `important-documents.php` headers, `activity.php`/`news.php` emergency→critical-incident + alert/library removal.
2. **`finances.php`:** strip escrow → retainer/annual-fee model.
3. **`overview.php`:** key terms + all bodies + FAQ rewrite + 8 new FAQs.
4. **`provider.php`:** referral tip, remove metrics/PHQ-9, EHR, Services rename.
5. **`continuity-stewards.php`** and **`support-stewards.php`:** the two big structural reworks (role taxonomy, Approved Critical Incidents, responsibilities taxonomy, permission matrix, notify-me, Planning & Guidance tabs).
6. **Capture CS/SS portal guidance copy** for when those portals are scaffolded.

Each of the above should follow the standard **wiring pass → design pass** discipline, surgical `str_replace` edits, and the pre-flight checklist before delivery.
