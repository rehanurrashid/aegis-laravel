# Aegis — Master Change List (May 5, 2026)

This document consolidates **every change Carizma has requested** across her recent emails:

- **Apr 30 / May 1** — Continuity Steward Provider profile workflow email
- **May 4 (4:45 PM)** — "Pause until we connect" email
- **May 4 (10:10 PM)** — "Provider (Continuity Plan) Dashboard Feedback" + attached Word doc
- **May 5** — Brand-voice and key-terms update email

It is the source-of-truth for the post-pause build. **Nothing on this list ships until Carizma signs off after the post-pause review call.**

---

## Part 1 — Terminology Sweep (Global Across All Portals)

These changes apply to **every portal** (Practitioner, CS, SS, BP) and every public-facing surface (profile pages, FAQ, onboarding, emails, banners, tooltips, error messages, button labels, modal titles).

### 1.1 Primary Term Changes

| Old Term | New Term | Scope |
|---|---|---|
| **Provider** | **Practitioner** | All UI strings everywhere. File/directory/CSS-class names retain `provider*` for backward compatibility. |
| **Healthcare Provider / Healthcare Professional** | **Health and Wellbeing Practitioner** | Title text, banner copy, marketing copy on FAQ/Why-Aegis/etc. |
| **Professional Will** | **Continuity Plan** | All references everywhere. FAQ may note "commonly known as a Professional Will in many professional associations" — but the canonical term in Aegis is Continuity Plan. |
| **Executor Agreement** | **Continuity Plan** | Rolled into the same change. |
| **Emergency** | **Critical Incident** *or* **Critical Moment** | "Critical Incident" for system labels (Critical Incident Log, Critical Incident Type). "Critical Moment" for narrative copy ("when a critical moment occurs"). |
| **Activate Emergency Protocol** | **Activate Continuity Support** | Button label change. |
| **Activate Succession** | **Activate Continuity Support** | Banner + modal headers. |
| **Reason for Activation** | **Incident Type** | Form field label. |
| **Patient** | **Client** | E.g. "patient information" → "client information". |
| **Patient List** | **Sensitive Information & Credentials (Vault)** | Readiness score line item rename. |
| **Tertiary Continuity Steward** | (Removed) | Only Primary CS, Alternate CS, and Support CS exist. Tertiary tier deleted. |
| **Primary / Secondary / Tertiary CS** | **Continuity Steward / Alternate Continuity Steward / Support Continuity Steward** | Per Carizma's May 5 key terms list. |
| **Set Role** (CS designation step) | **Role Step-up** | Step header in CS designation wizard. |
| **Job Postings** (nav + page) | **Support & Services** | Practitioner Portal sidebar item. |
| **Post a Job** (button + page) | **Request Support** | All CTA buttons + page header. |
| **My Job Postings** | **My Support Requests** | Tab label. |
| **Jobs and Hiring Center** | **Support & Services** | Section header text. |
| **Post a Job & Hire Business Partners** | **Request support and connect with business partners** | Banner copy. |
| **Track, Send & Manage Referrals** | **Send, receive, and manage referrals** | Section header. |
| **Referral Management** | **Referral Coordination** | Section header. |
| **Manage the trusted individuals…** (CS banner) | **Identify and support the trusted individuals…** | CS tab top banner. |
| **Authorize trusted staff to handle specific administrative, billing, and scheduling tasks…** (SS banner) | **Designate trusted individuals to support communication, coordination, and key tasks during a critical moment, guided by your Continuity Plan.** | SS tab top banner. Applies in two locations. |
| **Medical License** | **License** | Profile field. License number is **not required** to be entered. |
| **Workload Responsibilities** (release reason) | **Unable to fulfill Continuity Steward responsibilities** | Drop-down option in CS release request. |
| **Conflict of Interest** (release reason) | (Removed) | Drop-down option deleted. |
| **HIPAA event** (Activity Log) | **Access event** | Activity log row label. |
| **Log all Support Steward actions (recommended for HIPAA)** | **Log all Support Steward actions (recommended)** | SS settings text. |
| **Hospitality/Institution** (CS relationship type) | (Removed) + add **Designated Continuity Steward** | Relationship-to-you dropdown options. |
| **Job Title** (SS edit field) | **Relationship** (with manual text entry) | SS profile field. |
| **Healthcare Professionals** (Events tab subtitle) | **Health and Wellbeing Professionals** | Tab text. |
| **Both Agreements Signed** | **Agreement(s) Signed** | Readiness score line item. |
| **Aegis Professional Will** (CS send-agreement page) | **Continuity Plan** | Page reference. |
| **My Profile Section: Provider Profile** | **Practitioner Profile** | Section heading. |
| **The Integrative Network is the professional connection layer in Aegis…** | **The Integrative Network is a network you cultivate within Aegis to support access to holistic care and resources for your clients…** | FAQ definition (full rewrite — see Carizma's May 5 doc). |

### 1.2 Term Removals (Delete Entirely)

These terms are gone — they should not appear anywhere in the system after the sweep:

- **Escrow** / **Funds in Escrow** / **Escrow Account** / **Escrow Status** — Already underway; finish the sweep. Replace where needed with "Active Invoices," "Finance," or remove entirely.
- **Tertiary Continuity Steward** — Only three CS roles exist now: CS, Alt CS, Support CS.
- **HIPAA-compliance footers/badges in user-facing copy** — Per Carizma's MA'AT voice (don't sound directive or compliance-heavy).
- **PHQ9** — Removed from referral form.
- **Counter-referral feature** — Removed entirely from referrals.
- **Referral outcome and performance metrics section** — Removed from Referral tip / view-provider-profile.
- **Recent Activity** (on view-provider-profile) — Removed (Practitioners shouldn't see what other Practitioners are doing).
- **Conflict of Interest** option in CS release reason dropdown.
- **Workload Responsibilities** as a release reason (renamed, see above).
- **Auto-suspend Support Steward if quarterly review is 14+ days overdue** — Removed from SS settings.
- **Quarterly access review** — Removed from SS section (quarterly pertains to Practitioner self-updates, not SS access reviews).
- **Delegate first task** demo — Removed from SS onboarding checklist.
- **Priority Level / Due Date / Confirmation of Completion** in SS Delegate Tasks — All removed.
- **All timeframes** in CS responsibilities ("within 24 hours", "within 72 hours", "within 1 week" etc.) — Removed throughout.
- **Specific tasks lists** in CS designation that include phrases like "HIPAA-compliant manner," "tail coverage," "estate legal documentation" etc. — Removed; replaced with cleaner placeholder copy (see Carizma's full text in the Word doc).
- **References to scheduling, billing, patient intake, record management, referral coordination, vault records** in SS section — All removed (SS is relational, not administrative).
- **All alerts in Activity Log** — Activity Log tracks actions only.
- **All alerts in Compliance tab** — Move alerts to Dashboard.
- **Library inside News & Resources tab** — Removed (library is its own section).
- **All non-event content in News & Resources** — Removed.
- **Practice alerts (compliance, licensure)** in News & Resources — Moved to primary Dashboard.

### 1.3 Tone Sweep — MA'AT Brand Voice

Apply across **every UI string** in the system. Sources: Carizma's May 5 brand-voice email + Word doc rewrites.

**Replace these patterns:**

| Current pattern | MA'AT pattern |
|---|---|
| "ensures…" | "helps you…" |
| "guarantees…" | "supports…" |
| "fully covered" | "in place when needed" |
| "if something happens" | "when circumstances change" |
| "protected" | "remains supported" |
| "Your patients are cared for" | "Care remains connected" |
| "Your practice is protected" | "Supporting the continuity of your practice" |
| "Built for healthcare professionals" | "Built for health and wellbeing practitioners" |
| "Peace of mind" | "Clarity and steadiness" |
| "Don't worry — our system handles…" | "You're not alone when things shift. Aegis provides support and guidance…" |
| "Cutting-edge platform revolutionizes…" | (Remove entirely. No marketing hype.) |
| "Critical step" | "Foundational step" |
| "Most important document" | (Soften — emphasize completeness, not hierarchy.) |

**Aegis voice principles:**

- **Aegis provides structure and resources.** It does not direct, oversee, or act on the practitioner's behalf.
- **Practitioners decide.** Aegis surfaces options, supports decisions, organizes information — never instructs.
- **Relational, not transactional.** Onboarding banners, FAQ answers, and notifications should feel like guidance from a trusted partner, not a system enforcing compliance.
- **Calm, steady rhythm.** No urgency, no alarm-based phrasing, no exclamation points.
- **Clear, not simplified.** Don't dumb down — write with precision, but in complete intentional sentences.

---

## Part 2 — Practitioner Profile / CS Architecture (May 4 Decisions)

These are the architectural decisions that came out of the Apr 30 / May 4 emails. They have been formally added to the project context document.

### 2.1 Practitioner-as-CS Gateway Pattern

When a Practitioner also holds a CS role, the Practitioner profile shows a **small CS summary panel**:

- Count of practitioners stewarded
- Next attestation due date
- Active critical incidents (if any)
- **"Switch to CS Dashboard"** button

The full caseload, agreements, and CS-specific data live in the CS Portal — NOT on the Practitioner profile.

The **CS menu item / dashboard navigation** is gated: it only appears for Practitioners who actually hold an active CS role (`cs_addon=1` OR linked Business CS account).

### 2.2 Practitioner-as-SS Gateway Pattern (Same UX)

Same gateway pattern as CS, applied to SS:

- Small SS summary panel on the Practitioner profile
- **"Switch to SS Dashboard"** button
- SS menu item gated: visible only to Practitioners holding an active SS role

**No fee implication** — SS is a free role (typically a spouse/family member).

### 2.3 New CS Pricing Tier — Practitioner + CS Add-on

| Tier | Price | Practitioners | Public CS profile | Proactive invites |
|---|---|---|---|---|
| Invited CS | Free | 1 (the inviter only) | No | No |
| **Practitioner + CS Add-on (NEW)** | **+$19/mo** | **Up to 3** | **No** | **No** |
| Business CS | $49/mo or $429/yr | Up to 40 | Yes | Yes |

- Add-on stored as `cs_addon=1` on the Practitioner's `users` row.
- Hard cap at 3 — at practitioner #4, system blocks and surfaces upgrade prompt to Business CS.
- Downgrade allowed: a Business CS with ≤3 active practitioners may downgrade to the Add-on, forfeiting public profile + outbound invites.

---

## Part 3 — Functional / Page-Level Changes (From Word Doc)

These are the specific page-level changes from Carizma's Word document. Organized by portal area.

### 3.1 Activity Log

- Activity Log tracks **actions only** (logins, profile edits, document access, role designations, attestations, plan activations).
- Remove all **alerts** from Activity Log.
- Rename "HIPAA event" → "Access event."
- Remove the **credentials-expiring note** from this section (alerts belong on Dashboard).
- Remove alert content from the **Compliance tab** as well.

### 3.2 News & Resources

- Delete all non-event content.
- Update alerts in this section.
- Move practice alerts (compliance, licensure) to primary Dashboard.
- Remove Library from inside this tab.

### 3.3 Events & Training

- Remove "Healthcare" from tab title → just **"Events & Training."**
- Subtitle text: "CEU courses, conferences, workshops, and networking for **health and well-being professionals**" (was "mental health professionals").

### 3.4 Overview / Start Here

- Update key terms list to use **only** the terms Carizma provided (in §16.2 of the project context). Don't change Carizma's text on existing items.
- Full banner and section copy rewrites — see Word doc lines 125–172. Apply in entirety.
- Replace "Why Aegis" copy block with the six new MA'AT-voice sections:
  1. **Supporting the Continuity of Your Practice**
  2. **Care Remains Connected**
  3. **Keeping What Matters Together**
  4. **Built for Health and Wellbeing Practitioners**
  5. **Sustaining and Growing Your Practice**
  6. **Clarity and Steadiness**

### 3.5 Continuity Plan / Continuity Steward Section

- Center section on Practitioner Dashboard: "Professional Will & Continuity Steward" → **"Continuity Plan and Continuity Steward."**
- All "Professional Will" references throughout → **"Continuity Plan"** (every dashboard, every portal).
- "Activate Succession" → **"Activate Continuity Support."**
- "Reason for Activation" → **"Incident Type."**
- "View Agreement" page: Professional Will → Continuity Plan; allow editing of responsibilities and timelines.
- Annual Professional Will Review → **Annual Continuity Plan Review.** Add to authorization list: **"Vault documentation is complete and accurate."**

### 3.6 Continuity Steward Designation Wizard

- Add new tab/section: **"Approved Critical Incidents."** Practitioners select from the seven approved types: Death, Short-Term Incapacitation, Long-Term Incapacitation, Geopolitical or Conflict-Related Events, Natural Disaster, Missing Person, Detainment.
- Per-incident verification toggle: Practitioners may indicate whether verification is required before a Continuity Response is initiated, **per critical-incident type, per CS.**
- **Bulk-add option:** "Apply these settings to all my CSes" (and same for SSes) for triggering events, verification preferences, and permissions, with per-role customize toggle.
- Remove **all timeframes** from responsibilities (no "within 24 hours," etc.).
- Role labels (Step 2 of CS designation):
  - **Primary Continuity Steward** — first in line
  - **Alternate Continuity Steward** — backup if primary unavailable
  - **Support Continuity Steward** — co-CS when responsibilities are shared
  - **Tertiary CS — REMOVED** entirely.
- "Set Role" → **"Role Step-up."**
- Send Agreement page: "Aegis Professional Will" → **"Continuity Plan."** Compensation field clarification needed (where is it entered?).
- Designate-CS form layout: separate **"Search Aegis Users"** and **"Enter External Email"** onto distinct lines, with proper labeling.
- "Aegis users can view and sign their agreement directly in their portal. External invitees will receive a secure link…" → **"If you invite an external Continuity Steward, they will receive an invitation to join Aegis. Their account is provided at no cost as they support your practice."**
- Relationship-to-you dropdown: remove "Hospitality/Institution"; add "Designated Continuity Steward"; the "Other" option allows manual text entry.
- Section header: "Select all responsibilities this Continuity Steward is authorized…" → **"Select all responsibilities this Continuity Steward is authorized and expected to carry out. Taking time to document these within your Continuity Plan and supporting materials helps ensure your practice can be supported with clarity and alignment."**

### 3.7 Continuity Steward Tab — "I'm a Continuity Steward For…"

- "Practices you have agreed to manage in an emergency. These are your legal obligations." → **"Practices you have agreed to support in the event of a critical moment."**
- "Important: As a named Continuity Steward, you have legal responsibilities to act…" → **"Important: As a named Continuity Steward, you play a key role in supporting these practices during a critical moment. Review your responsibilities and confirm that the information is accurate, up to date, and accessible, including access to the Document Vault."**
- "View My Responsibilities" intro text rewritten — see Word doc line 410.

### 3.8 Support Steward Section (CRITICAL — Carizma's pause was triggered by this section)

**Roll back all unrequested professional/clinical/administrative additions.** Per Carizma's definition, the SS is a **relational** family-member role. NOT administrative. NOT clinical.

- Banner text rewrite: "Authorize trusted staff…" → **"Designate trusted individuals to support communication, coordination, and key tasks during a critical moment, guided by your Continuity Plan."** (Apply in two places — the SS tab AND the SS task settings.)
- **Remove ALL references to**: scheduling, billing, patient intake, record management, referral coordination, vault records.
- Replace with the SS responsibilities matrix Carizma provided (Word doc lines 490–1053). **Do not add additional information beyond what's in the Word doc.**
- "Activate emergency activation" → **"Activate Continuity Support."**
- SS Settings:
  - "Log all Support Steward actions (recommended for HIPAA)" → **"Log all Support Steward actions (recommended)."**
  - **Delete:** "Auto-suspend Support Steward if quarterly review is 14+ days overdue."
  - Add the option to **remove** an SS or CS (in addition to add) — system should prompt signing of new agreement and assignment confirmation.
  - **Delete:** Quarterly review (it pertains to Practitioner quarterly self-updates, not SS access).
  - **Delete:** Quarterly access review.
- Edit Support Steward form:
  - "Job Title" → **"Relationship"** (with manual entry).
  - **Delete:** Reference to "organization."
  - **Delete:** "Support Steward role type."
  - "Changes to contact details will trigger an amendment notice. Changes to role type will require re-signing the agreement." → **"If contact details are updated, an amendment notice will be issued. Changes to role type will require the agreement to be re-signed."**
- **Quarterly review for Practitioners** (separate from SS): focused on Practitioner client roster updates, system access (EHR, billing, email, calendar), case load, new licenses, supervisee roster, contract roster, business locations, new services. **Limit quarterly to Practitioner info; updates to Continuity Plan are opt-in at any time.**
- SS Notifications — replace existing with:
  - Notify me when Annual Re-Attestation is complete
  - Notify me if changes are requested
  - Notify me when information is updated
  - Notify me if roles or permissions change
  - Notify me if a Continuity Steward is added, removed, or updated
- SS Delegate Tasks:
  - Limit to "Activation & Verification" + "Access & Resource Coordination" headers + custom "Add Other."
  - **Delete:** Priority Level, Due Date, Confirmation of Completion section.
  - The Delegations section is duplicative of permissions/responsibilities — keep only permissions and responsibilities; Delegation is opt-in.
- SS Onboarding Checklist:
  - "Access Review Due" → **"Annual Attestation Due ___(date)."**
  - **Delete:** "Fill a gap."
  - **Delete:** "Delegate first task" demo.
  - Replace content with the **Onboarding and Planning for Critical Moments (Informational)** matrix from the Word doc.
- SS Responsibilities matrix: see Word doc lines 490–1053 — apply in entirety, structured by header.

### 3.9 Integrative Network

- Main title: "Integrative Network" → **"Network."**
- Add subsection **Business Partners.**
- Section ordering: Integrative Care Network listed first.
- Sections separated visually within the page (similar to how Shadow Network is currently separated).
- FAQ definition rewrite — see §3.4 above, item 6.

### 3.10 Edit Profile

- Professional tab: "Medical License" → **"License."** **License number not required.**
- Custom specialty/service: allow entry under either Service or Specialty (don't add at end). Fix the broken **Add tab** button.

### 3.11 Referrals

- "Track, Send & Manage Referrals" → **"Send, receive, and manage referrals."**
- "Referral Management" header → **"Referral Coordination."**
- **Remove** counter-referral feature.
- Pending Referrals → Full Details:
  - "Patient Information" → **"Client Information."**
  - Add **self-pay option.**
  - **Remove:** PHQ9.

### 3.12 Integrative Network — Pending Requests / View Practitioner Profile

- Referral tip rewrite: "Include a brief clinical summary…" → **"Include a brief summary when making referrals. Prefers warm handoffs when possible. Can receive shared records or documentation through secure methods."**
- **Remove:** Outcome and performance metrics section.
- **Delete:** Recent Activity section (Practitioners shouldn't see other Practitioners' activity).
- Practice and contact: include whether services are **online, in person, or both.**
- "Clinical Services" → **"Services."**
- Connection request copy: "Review and respond to connection requests. Accepting adds **the connection** to your network…" (was "adds them").

### 3.13 Job Postings → Support & Services

Full rename:

- Tab: "Job Postings" → **"Support & Services."**
- Page header banner: "Post a Job & Hire Business Partners" → **"Request support and connect with business partners."**
- All "Post a Job" buttons → **"Request Support."**
- "My Job Postings" → **"My Support Requests."**
- "Jobs and Hiring Center" → **"Support & Services."**

### 3.14 Continuity Readiness Score

- "Both Agreements Signed" → **"Agreement(s) Signed"** (one agreement covers all CS + SS).
- "Patient List" → **"Sensitive Information & Credentials (Vault)."**
- Remove **Tertiary Continuity Steward** option (only Primary, Alternate, Support remain).
- Note from Carizma: ensure ONE document includes all signatures (CS + SS together). This is a key plan-architecture point — verify the implementation.

### 3.15 Continuity Steward Release Request

- Workload Responsibilities reason → **"Unable to fulfill Continuity Steward responsibilities."**
- **Remove:** Conflict of Interest reason.

### 3.16 Activate Continuity Support (formerly Activate Succession)

- Header: "Activate Succession" → **"Activate Continuity Support."**
- "What happens next" copy rewrite: "Your Continuity Steward (James Wilson) and Support Steward (Maria Santos) will be notified immediately. Your Professional Will protocol will be initiated…" → **"Your Continuity Steward (James Wilson) and Support Steward (Maria Santos) will be notified. Your Continuity Plan will be activated, and access to your Vault will be granted according to the permissions you have defined, making designated information available at this time."**

### 3.17 FAQ Page (Multiple Rewrites)

All FAQ entries rewritten by Carizma — see Word doc lines 155–188. Apply in entirety, including:

- "What is the first thing I should do when I join Aegis?" — full rewrite.
- "What is the difference between a Continuity Steward and a Support Steward?" — full rewrite.
- "What is a Professional Will?" → "What is a Continuity Plan?" — full rewrite.
- "What types of emergencies can be reported?" — full rewrite (Practitioner now decides what's included).
- "Can I invite someone who is not already on Aegis as my Continuity Steward?" — copy update.
- "What plan do I need — Continuity Access or Continuity Practice?" — **DELETE this question from FAQ.** Move to MAAT website FAQ. Replace with the new descriptive paragraphs Carizma provided (Continuity Access + Continuity Practice descriptions).
- "How often do I need to update my information?" — full rewrite, includes mention of MA'AT Continuity Assurance.
- "What happens to my Vault documents in a critical moment?" — full rewrite.
- "What is the Integrative Network?" — full rewrite.
- **ADD:** "Does Aegis replace my existing systems or workflows?"
- **ADD:** "What does my Continuity Steward and Support Steward experience?"

---

## Part 4 — Open Questions Carizma Asked (15 from Word Doc)

These are answered in the response email drafted earlier. For build purposes, they map to:

1. **HIPAA event** → renamed Access event (action-only log).
2. **Upload own Continuity Plan** → both upload and Aegis-builder paths supported.
3. **Finance tab includes service-request income** → confirmed yes; Stripe Connect direct.
4. **Practitioner-as-CS info management** → gateway pattern (see §2.1).
5. **CS tab is for setup, not activation** → confirmed; activation is a separate flow.
6. **Bulk-add settings across designees** → yes, with per-role customize toggle.
7. **"How to use Aegis" workflow** → CTAs route directly to designation wizard, not to navigation pages.
8. **Where email is entered for CS/SS** → inside the designation wizard itself.
9. **Renewals / archive expired credentials** → Add Credential pairs with Archive option.
10. **Average response time** → ⚠️ **Carizma to direct** (metric definition + visibility).
11. **CEU section** → ⚠️ **Wireframe to be reviewed** (build per her category list).
12. **Peer reviews entry point** → ⚠️ **Carizma to direct** (relationship-gated recommended).
13. **Filters for Integrative/Shadow Network** → wire backend filter logic.
14. **Map on view-provider-profile** → wire to actual practitioner addresses with privacy controls.
15. **Aegis Verified banner** → ⚠️ **Walkthrough to be done** (gov ID + credentials + Code of Conduct + Checkr).

---

## Part 5 — Other Items From Recent Emails

### 5.1 From May 5 Brand Email

- **Welcome banner** on Practitioner login (personalized, MA'AT voice).
- Apply MA'AT brand voice to **all surfaces** (see §1.3 above).
- **Master Admin dashboard scope** noted: enrollments, engagement across populations, system requests, revenue, feature requests, money earned. Carizma uses this to oversee the system. **Scoping pass needed** to define v1.

### 5.2 From May 5 Pause Email

- **Forms and templates** — placeholders only until Carizma's editor + lawyer review is complete.
- **Excel feedback form is broken** — Vignesh is helping resolve. Word doc is the interim feedback method.
- **Edit Payment section** disappeared during her review — Carizma will provide feedback once visible again.
- **Trademark in progress** for "Aegis" and "MA'AT" with Carizma's attorney.
- **Website draft content** expected soon from her brand team.
- **Launch pushed to June 22, 2026** (was June 1).
- **Pause is in effect** — no Aegis work ships until Carizma signs off after the post-pause review call.

### 5.3 Change-Control Rule (May 5)

**Going forward:** if the team identifies a workflow, content area, or feature that isn't in Carizma's specs, **pause and ask before building.** Do not fill gaps with intuition. Surface open questions by text, call, or email — connect first.

This is a non-negotiable protocol moving forward. The unrequested SS additions that triggered her pause email are exactly what this rule prevents.

---

## Part 6 — What's NOT Changing

To prevent future scope drift, here's what should remain untouched unless Carizma explicitly requests:

- File and directory naming (`provider-portal/`, `cs-portal/`, etc.) — Backward compatibility.
- CSS class names with `provider-` or `dsr-` prefixes — Display text only changes.
- Database column names that reference old terms — App-layer rename only.
- The 7 approved critical-incident types — locked list, no additions.
- Vault locked-by-default architecture.
- Stripe Connect direct (no Aegis-held escrow).
- Two-tier Practitioner pricing ($39 / $79).
- Marcus Chen multi-role pattern (one user, multiple `user_roles` rows).

---

**End of master change list.** This list reflects every requested change as of May 5, 2026. The post-pause build executes against this list in full.
