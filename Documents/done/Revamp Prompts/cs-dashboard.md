Task: CS Portal — dashboard.php — Wave 1 (Centralize + Design)

You are building the Continuity Steward portal dashboard.
Provider's dashboard.php is the reference implementation for
design and structure. CS dashboard has the same shell but
different data domain — caseload, task readiness, incident
state, CS-specific KPIs.

Read and apply these rules before touching anything:

1. Data is user-scoped, not portal-scoped. seed.json keyed by
   user_id. cs_marcus is the demo CS user.

2. Centralize first, design second — in one pass. No wiring in
   this chat. No write-path, no fetch() calls, no save_*.php.
   All dynamic data is hardcoded or stubbed with realistic
   placeholder values for now. Wiring is Wave 2.

3. Auth gate is role-specific for portal pages (unlike shared
   templates). CS dashboard must gate on continuity_steward role:
     $current_user = aegis_current_user('cs_marcus');
     if (!$current_user || $current_user['role'] !== 'continuity_steward')
         header('Location: /reset.php?token=aegis-demo-reset'); exit;

4. Shared chrome — all from _shared/:
     declare(strict_types=1);
     define('AEGIS_ENTRY', true);
     require_once __DIR__ . '/../_shared/models.php';
     require_once __DIR__ . '/../_shared/icons.php';
     include _shared/page_head.php
     include _shared/header.php
     include _shared/sidebar.php
     include _shared/page_foot.php
   No local copies of any shared partial.

5. Design system rules — apply Aegis_Desing_Prompt_Short.md
   checklist verbatim. Every rule, no skipping.
   Short prompt wins on any conflict with long prompt.
   network.php is the canonical design reference page.

6. Canonical classes only. No local CSS that duplicates
   _shared.css. Page-specific <style> block for page-local
   component styles only (custom card layouts, dashboard-specific
   grid compositions not covered by _shared.css).

7. aegis_icon() for all icons — no inline SVGs, no ✓ literals.

8. Hero banner: always .hero-banner.is-quiet.
   Activity link with aegis_icon('activity', 14) mandatory
   in hero .page-hero-actions.

9. Modals: always .modal-overlay + .open driven by global JS.
   openModal() / closeModal() from _shared.js — never local
   redefinitions.

10. No hardcoded copy that belongs in seed.json or a models.php
    helper. CS name, avatar initials, role label — all from
    $current_user. Provider names, plan details — stubbed as
    PHP variables at the top of the file, clearly marked
    // STUB — wire in Wave 2.

11. seed.json and models.php are uploaded by the user — read
    from uploaded files only. All other files (_shared.css,
    icons.php, Aegis_Desing_Prompt_Short.md, etc.) must be
    read from Aegis project knowledge. If a needed file is not
    found in project knowledge, flag it by exact filename and
    wait for upload.

12. dashboard.html is the visual spec — a purpose-built design
    reference for the CS dashboard created in Claude Design.
    Mirror it exactly — every section, widget, card, layout,
    and spacing decision in that file is intentional.
    provider_dashboard.php is the secondary reference only
    for shared chrome patterns (hero structure, stat-chips-row,
    section headers, modal shell) where the HTML design file
    doesn't cover them. CS-specific widgets (Incident Cockpit,
    Provider caseload cards, Certification Status, Task list)
    come from dashboard.html exclusively.

13. php -l every file before delivery. No exceptions.

14. Before executing, list every file you need that is not
    already in project knowledge or uploaded — by exact
    filename — and wait for upload before proceeding.

─────────────────────────────────────────────────────────────
STEP 1 — Read everything, output plan, wait for confirmation
─────────────────────────────────────────────────────────────
Read in this order:

FROM PROJECT KNOWLEDGE:
1. AEGIS-PROJECT-CONTEXT.md       — §4.2 CS portal pages + key
                                    pages spec, §17.1 symmetric
                                    naming, CS sidebar structure
2. CENTRALIZED-SYSTEM.md          — shared file inventory,
                                    portal page pattern
3. Aegis_Desing_Prompt_Short.md   — full read, every rule
4. Aegis_Desing_Prompt.md         — reference for any edge case
                                    not covered by short prompt
5. _shared/_shared.css            — full read, every canonical
                                    class before writing markup
6. _shared/icons.php              — all icon names
7. provider_dashboard.php         — reference implementation,
                                    full read — note every
                                    section, card, modal, JS
                                    pattern used

FROM UPLOADED FILES:
8. seed.json                      — find cs_marcus user record,
                                    confirm fields present:
                                    display_name, avatar_initials,
                                    role, cs_account_type,
                                    plan_stewards rows for marcus,
                                    any providers he is assigned to,
                                    activity_events for cs_marcus
9. _shared/models.php             — scan for any existing CS
                                    dashboard helpers, confirm
                                    aegis_current_user() signature
10. dashboard.html                — CS dashboard design reference,
                                    full read — this is the visual
                                    spec, extract every section,
                                    class name, CSS variable, and
                                    component pattern before writing
                                    any markup
11. cs_dashboard_legacy.php       — legacy file, full read —
                                    extract every section, widget,
                                    metric, modal that exists
                                    (even if poorly implemented)
                                    — nothing should be lost in
                                    the redesign

After reading ALL files output ONE plan block:

  ── CS dashboard sections inventory ──
  From provider_dashboard.php:
    Sections present: [list with brief description]
  From cs_dashboard_legacy.php:
    Sections present: [list]
    Sections unique to CS (not in Provider): [list]
    Sections in Provider not applicable to CS: [list]

  ── CS dashboard final section plan ──
  List every section the new CS dashboard will have, in order,
  with a one-line description of its CS-specific content:
    e.g. "Hero greeting — cs_marcus name + role + date"
    e.g. "KPI chips — Providers Assigned / Active Tasks /
          Incidents Monitored / Certifications Due"
    e.g. "Providers caseload strip — cards per assigned provider
          with plan status + last activity"
    e.g. "My Tasks summary — upcoming tasks, count by provider"
    e.g. "Active Incident banner — conditional on $has_emergency"
    e.g. "Recent Activity feed — last 5 events from activity_events"

  ── Stub variables needed (Wave 2 wires these) ──
  List every PHP variable that will be stubbed in Wave 1:
    e.g. $providers_assigned = 3;  // STUB
    e.g. $active_tasks_count = 7;  // STUB

  ── Modals needed ──
  List every modal on CS dashboard (from legacy file + any
  new ones matching the CS spec in AEGIS-PROJECT-CONTEXT.md)

  ── Design violations in cs_dashboard_legacy.php ──
  List every violation of Aegis_Desing_Prompt_Short.md found:
    e.g. "inline SVG icons — replace with aegis_icon()"
    e.g. "hardcoded hex colors — replace with CSS vars"
    e.g. "local openModal() redefinition — remove"

  ── Seed status for cs_marcus ──
  Fields present: [list]
  Fields missing: [list — flag for Wave 2 seed gate]

Auto-proceed to Step 2 immediately after plan block.

─────────────────────────────────────────────────────────────
STEP 2 — Centralize
─────────────────────────────────────────────────────────────
Apply centralization to the legacy CS dashboard.php:

2a. Replace file header with canonical pattern:
      <?php
      declare(strict_types=1);
      define('AEGIS_ENTRY', true);
      require_once __DIR__ . '/../_shared/models.php';
      require_once __DIR__ . '/../_shared/icons.php';

      $current_user = aegis_current_user('cs_marcus');
      if (!$current_user ||
          $current_user['role'] !== 'continuity_steward') {
          header('Location: /reset.php?token=aegis-demo-reset');
          exit;
      }

      $role  = $current_user['role'];
      $uid   = $current_user['id'];

      // Page identity
      $active_page       = 'dashboard';
      $page_title        = 'Dashboard';
      $page_portal_label = 'Aegis Continuity Steward Portal';
      $has_emergency     = !empty($_GET['emergency']) &&
                           $_GET['emergency'] === 'true';

      // ── STUBS — wire in Wave 2 ──────────────────────────
      $providers_assigned  = 3;   // STUB
      $active_tasks_count  = 7;   // STUB
      $certifications_due  = 1;   // STUB
      $incidents_monitored = 0;   // STUB
      // ... all other dynamic values stubbed here ...

2b. Replace all chrome with shared includes:
      include __DIR__ . '/../_shared/page_head.php';
      include __DIR__ . '/../_shared/header.php';
      include __DIR__ . '/../_shared/sidebar.php';
    At end of file:
      include __DIR__ . '/../_shared/page_foot.php';

2c. Remove every local redefinition of global helpers:
    openModal, closeModal, showToast, toggleSwitch,
    navigateTo, confirmAction — delete any local copies.

2d. Remove all hardcoded asset paths, inline <link> tags,
    inline <script src="..."> tags — _shared.js and
    _shared.css are loaded by page_head.php / page_foot.php.

2e. Replace all inline SVGs with aegis_icon() calls.
    Replace all ✓ literals with aegis_icon('check', N).
    Replace all hardcoded hex colors with CSS variable equivalents.

─────────────────────────────────────────────────────────────
STEP 3 — Design
─────────────────────────────────────────────────────────────
Apply Aegis_Desing_Prompt_Short.md checklist in full.
Use dashboard.html as the primary visual reference.
Use provider_dashboard.php for shared chrome patterns only.
Rebuild each section using canonical _shared.css classes.

Section by section rules:

HERO BANNER:
- .hero-banner.is-quiet always
- .page-hero-inner with left col (eyebrow + title + subtitle)
  and right col (.page-hero-actions)
- Greeting: "Good [morning/afternoon], [first name]"
  first name derived from $current_user['display_name']
  same pattern as provider_dashboard.php
- Role chip: CS account type label from $current_user
- .page-hero-actions must include:
    Activity link: aegis_icon('activity', 14) → activity.php
    Primary CTA relevant to CS role (e.g. View My Providers)
- Hero stat-chips-row below inner:
    chips for Providers Assigned / Active Tasks /
    Certifications Due / Incidents Monitored
    all values from stub variables

ACTIVE INCIDENT BANNER:
- Conditional: <?php if ($has_emergency): ?>
- .alert.alert-critical with shield icon
- "Active Critical Incident" — links to continuity-management.php
- <?php endif; ?>

PROVIDERS CASELOAD:
- Section header (.dh-sh pattern from provider_dashboard.php)
- Provider cards — one per assigned provider
  showing plan status chip, last activity, CS role on that plan
  (Primary CS / Alternate CS)
- "View All Providers" link → providers.php
- Use stub $providers array for now

MY TASKS SUMMARY:
- Card with upcoming tasks grouped by provider
- Count chip per provider
- "View All Tasks" → my-tasks.php
- Stub data

CERTIFICATIONS DUE:
- Alert or card — conditional on $certifications_due > 0
- Links to relevant provider's plan certification

RECENT ACTIVITY:
- Last 5 activity_events for cs_marcus
- Stub as empty array for now — Wire in Wave 2
- Show empty state if no events

MODALS:
- Every modal identified in Step 1 plan block
- .modal-overlay + .modal structure
- openModal() / closeModal() from _shared.js
- No local modal JS

CSS:
- Page-specific <style> block at top of file
- Only styles not covered by _shared.css
- All tokens use CSS variables — no hardcoded values
- Follow provider_dashboard.php local CSS as the pattern
  for what is page-specific vs what comes from _shared.css

JS:
- Page-specific <script> block via $page_extra_foot
- No global helper redefinitions
- Stub fetch() calls commented out:
  // Wire in Wave 2: fetch('/_shared/save_*.php', ...)

─────────────────────────────────────────────────────────────
STEP 4 — Verify
─────────────────────────────────────────────────────────────
- php -l on delivered file
- Grep: no inline SVGs in output
- Grep: no hardcoded hex colors — only var(--*)
- Grep: no local redefinitions of openModal / closeModal /
  showToast / navigateTo / toggleSwitch
- Grep: no hardcoded asset paths or local <link>/<script> tags
- Confirm .hero-banner.is-quiet present
- Confirm aegis_icon('activity', 14) in hero actions
- Confirm $has_emergency guard on incident banner
- Confirm all stub variables clearly marked // STUB
- Confirm every modal uses .modal-overlay structure
- Confirm page_head / header / sidebar / page_foot all included
- Confirm auth gate checks continuity_steward role

─────────────────────────────────────────────────────────────
DELIVER
─────────────────────────────────────────────────────────────
Files changed:
  continuity-steward-portal/dashboard.php    (centralized + designed)

Single file delivery — not zipped unless you prefer zip.
CHANGES note: list every centralization fix applied and every
design violation corrected, section by section.
















=======================================================================================






You are designing a dashboard for a Continuity Steward (CS) — 
a licensed healthcare professional who has agreed to step in 
and manage another practitioner's practice if that practitioner 
becomes unavailable due to death, incapacitation, natural 
disaster, or other critical events.

The CS dashboard is their command center. On a normal day it 
shows readiness status — are their assigned practitioners' 
plans signed, certified, and up to date? On an active incident 
day it becomes an emergency cockpit — what needs to happen 
RIGHT NOW to protect those clients and that practice.

Understanding the CS role deeply:
- A CS may be responsible for 1–5 practitioners simultaneously
- Each practitioner has a Continuity Plan the CS has certified
- The CS must monitor each practitioner's plan status, task 
  readiness, and certification currency
- When an incident is triggered by the Support Steward, the CS 
  is notified immediately and must execute a structured task list
- The CS has access to the practitioner's Document Vault only 
  after a verified incident
- The CS's own profile and credentials must stay current for 
  their role to remain valid
- Key metrics a CS cares about daily:
    How many practitioners am I responsible for?
    Are all their plans signed and certified by me?
    Do I have any tasks overdue or coming due?
    Are any of my certifications expiring?
    Is there an active incident I need to act on RIGHT NOW?
    What happened recently across my caseload?

─────────────────────────────────────────────────────────────
DESIGN REFERENCE — provider provider_dashboard.php is attached.
─────────────────────────────────────────────────────────────
Study it carefully. Reuse these design patterns exactly:

- The "Good morning, [Name]" hero greeting structure
- The stat-chips-row below the hero (KPI strip)
- The section header pattern (eyebrow + title + right-side link)
- The card system (border, radius, shadow, surface color)
- The steward identity row pattern (avatar + name + role + status)
- The color-rail card pattern (left border accent per status)
- The alert/banner patterns (info, warning, critical)
- The empty state pattern
- The modal trigger button style
- Gold accent color for primary actions and highlights
- Serif font for section titles and hero greeting
- The overall two-column body layout (main content + sidebar)

DO NOT reuse Provider-specific widgets:
- No Continuity Plan readiness ring (that's the Provider's plan)
- No "Your practice continues" copy (CS doesn't own a practice)
- No license/CEU tracker (Provider-specific compliance)
- No Integrative Network carousel (Provider-only feature)
- No referral or job posting widgets

─────────────────────────────────────────────────────────────
LEGACY REFERENCE — continuity-steward-portal/cs_dashboard_legacy.php 
is also attached.
─────────────────────────────────────────────────────────────
Study it for context only — NOT as a design reference.
Extract from it:
- What widgets and sections currently exist on the CS dashboard
- What actions/modals the CS can trigger from here
- What data the CS currently sees (provider names, task counts,
  plan status indicators)
- Any CS-specific functionality not mentioned in the design task
  above that should be preserved in the redesign

Do NOT carry forward:
- Any visual styles, layout patterns, or CSS from this file
- Any hardcoded colors, inline styles, or legacy markup patterns
- Any design decisions — the Provider provider_dashboard.php and the
  design task above are the only visual authorities

The legacy file answers "what does CS need" —
the Provider dashboard answers "how should it look".

─────────────────────────────────────────────────────────────
DESIGN TASK
─────────────────────────────────────────────────────────────
Design a complete, beautiful, fully responsive HTML dashboard 
page for the Continuity Steward. Single HTML file with embedded 
CSS. Use the exact same CSS variables, typography, color tokens, 
spacing, and component patterns from the Provider dashboard.

The page must include ALL of these sections:

1. TOPBAR (placeholder — same style as Provider)
   Logo left, user avatar + name right. No functionality needed.

2. SIDEBAR (placeholder — same style as Provider)
   Nav items for the CS portal sidebar structure:
     Main: Overview, Dashboard, My Profile
     Critical Moment Plans: My Providers, My Tasks,
       Important Documents, Vault (locked until incident)
     Activation: Continuity Management, Certifications
     Communication: Messages, Activity Log
     Account: Settings
   Active state on Dashboard.

3. HERO GREETING
   Same "Good morning, [Name]" pattern as Provider.
   Name: Marcus Webb, LCSW
   Role chip: Continuity Steward (gold)
   Subtitle: "You are responsible for 3 practitioners.
              2 plans certified · 1 certification due soon."
   Right side actions:
     "Activity Log" link with activity icon
     "View My Providers" primary button

4. STAT CHIPS ROW
   4 chips in the same style as Provider:
     Providers Assigned: 3
     Plans Certified: 2
     Tasks Due: 4
     Certifications: 1 expiring

5. ACTIVE INCIDENT BANNER (critical state — show it)
   Full-width critical alert banner below the stat chips.
   "⚠ Active Critical Incident — Dr. Sarah Johnson"
   "Triggered 2 hours ago by Support Steward Linda Johnson.
    Your task list has been activated. Vault access granted."
   Two buttons: "Open Task List" (primary) and "View Vault"
   This banner should feel urgent — red/critical treatment
   matching the alert-critical pattern from Provider.

6. MAIN CONTENT AREA (left, wider column)

   6a. MY PROVIDERS — caseload cards
       Section header: eyebrow "Your Caseload" + title 
       "Practitioners You Serve" + "View All" link
       3 provider cards using the color-rail pattern:
         Card 1 — Dr. Sarah Johnson
           Status rail: RED (active incident)
           Avatar initials: SJ (gold avatar)
           Title: Licensed Psychologist · Atlanta, GA
           Chips: "Active Incident" (red) · "Primary CS"
           Meta row: Plan signed · Certified by you · 
                     Last activity: 2 hours ago
           Actions: "Open Task List" + "View Vault"

         Card 2 — Dr. Priya Raman
           Status rail: GOLD (certification due)
           Avatar initials: PR
           Title: Marriage & Family Therapist · Austin, TX
           Chips: "Cert Due in 14 days" (gold) · "Primary CS"
           Meta row: Plan signed · Awaiting re-certification
           Actions: "Certify Plan" (primary) + "View Plan"

         Card 3 — Dr. James Okafor
           Status rail: GREEN (all good)
           Avatar initials: JO
           Title: Licensed Counselor · Chicago, IL
           Chips: "All Good" (green) · "Alternate CS"
           Meta row: Plan signed · Certified · 
                     Annual review due in 3 months
           Actions: "View Plan" + "Message"

   6b. MY ACTIVE TASKS (incident tasks for Sarah's plan)
       Section header: "Immediate Actions Required"
       Shown because of active incident — these are the 
       first 4 tasks from the activated task list:
         ☐ Contact all scheduled clients for the next 7 days
         ☐ Access Document Vault and retrieve client roster
         ☐ Notify referring providers of temporary closure
         ☐ Contact DEA if controlled substances are involved
       Each task: checkbox + title + priority chip + 
                  provider name tag
       "View Full Task List" link at bottom
       Use the same checklist card pattern as Provider

   6c. CERTIFICATION STATUS card
       A card showing CS's own certification currency:
         Dr. Sarah Johnson's plan — Certified ✓ 
           Last certified: Jan 15, 2025
         Dr. Priya Raman's plan — Due for renewal
           Due date: Jun 24, 2025 — "Certify Now" button
         Dr. James Okafor's plan — Certified ✓
           Last certified: Mar 3, 2025
       Gold accent on due items. Green check on certified.

7. SIDEBAR WIDGETS (right, narrower column)

   7a. INCIDENT COCKPIT (prominent, top of sidebar)
       A card specifically for the active incident.
       "Active Incident — Dr. Sarah Johnson"
       Incident type: Short-Term Incapacitation
       Triggered: Today at 9:14 AM
       Verified by: SS Linda Johnson
       Progress ring or progress bar:
         3 of 12 tasks completed (25%)
       Quick links: Task List · Vault · Important Documents
       This should feel like a mission control widget —
       the most important thing on the page right now.

   7b. UPCOMING TASKS widget
       Next 3 tasks due across all providers
       Each: task title + provider name + due date chip
       "View All Tasks" link

   7c. RECENT ACTIVITY feed
       Last 4 activity events for Marcus
       Same style as Provider's activity feed widget
       Events:
         🔴 Critical — Incident triggered for Dr. Sarah Johnson
         🟡 Warning  — Certification due: Dr. Priya Raman
         🔵 Info     — New message from Dr. James Okafor
         ✅ Success  — Plan certified: Dr. James Okafor
       "View Activity Log" link

   7d. MY PROFILE COMPLETENESS
       Same profile strip pattern as Provider
       Marcus Webb: 85% complete
       "2 fields missing — complete your profile"
       "Edit Profile" button

─────────────────────────────────────────────────────────────
DESIGN REQUIREMENTS
─────────────────────────────────────────────────────────────
- Single HTML file, all CSS embedded in <style> tag
- Use IDENTICAL CSS variables as Provider dashboard:
    --gold-dark, --gold-light, --icon-bg-gold
    --surface, --portal-bg, --border, --border-dark
    --text, --text-3, --text-4
    --red, --red-dark, --red-light
    --green, --green-dark, --green-light
    --font-serif, --font-sans
    --radius, --radius-lg, --radius-sm
    --shadow, --shadow-sm
    --transition
- Fully responsive — sidebar collapses on mobile
- Every component must be visually complete —
  no placeholder boxes, no "lorem ipsum"
- The incident state must feel urgent but not chaotic —
  organized urgency, not alarm fatigue
- Gold = primary brand / active / certified
- Red = incident / critical / overdue
- Green = all good / certified / complete
- The overall feeling: calm competence under pressure —
  Marcus knows exactly what to do because the dashboard
  tells him exactly what matters right now

─────────────────────────────────────────────────────────────
DELIVER
─────────────────────────────────────────────────────────────
One complete HTML file.
Every section listed above must be present and visually 
polished. This is a design reference — it will be handed 
to a developer as the visual spec for the PHP implementation.
Make it pixel-perfect, realistic, and true to the CS role.







