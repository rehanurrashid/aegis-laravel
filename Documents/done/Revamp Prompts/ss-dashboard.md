# SS Portal — dashboard.php — Two Prompts

---

## PROMPT 1 — Claude Design
## SS Dashboard — HTML Design Reference

```
You are designing a dashboard for a Support Steward (SS) —
a trusted non-clinical contact designated by a healthcare
practitioner to watch over their wellbeing and report a
critical incident if something goes wrong.

Deeply understand who this person is:
The SS is NOT a doctor. NOT a licensed professional. They are
a close family member, a trusted office manager, a personal
assistant, or a longtime friend. They agreed to be listed on
a practitioner's Continuity Plan because they care about that
person and want to help if something happens.

On a normal day the SS has almost nothing to do. Their job is
to stay ready — know who to call, have the app installed, and
check in on their assigned practitioners occasionally.

On the day something goes wrong, the SS becomes the first
responder. They see something worrying — the doctor hasn't
shown up, a family member called, there's been an accident —
and they come to this dashboard to report it. That single
action (Report Critical Incident) sets the entire continuity
chain in motion.

The SS's emotional state matters deeply for this design:
- On a normal day: calm, "nothing to do, everything is fine"
- On a crisis day: scared, confused, possibly grieving —
  they need the dashboard to be impossibly clear and simple
  The big red button must be impossible to miss
  The instructions must be written for a non-technical person
  under extreme stress

What the SS cares about:
- Is everything OK with my assigned practitioners right now?
- Do I have any preparation tasks I haven't done yet?
- If something is wrong — what do I do RIGHT NOW?
- Who is the Continuity Steward and how do I reach them?

What the SS does NOT care about:
- Certifications, credentials, or licensed professional status
- Vault access (SS has no vault access by design)
- Financial flows or billing
- Network discovery or referrals
- CEU credits or professional development

The SS portal is intentionally simpler than Provider or CS.
It should feel like a calm, trustworthy safety net — not a
complex professional tool.

─────────────────────────────────────────────────────────────
DESIGN REFERENCE — provider_dashboard.php is attached.
─────────────────────────────────────────────────────────────
Study it carefully. Reuse these design patterns exactly:

- The "Good morning, [Name]" hero greeting structure
- The stat-chips-row below the hero (KPI strip)
- The section header pattern (eyebrow + title + right-side link)
- The card system (border, radius, shadow, surface color)
- The color-rail card pattern (left border accent per status)
- The alert/banner patterns (info, warning, critical)
- The empty state pattern
- The modal trigger button style
- Gold accent color for primary actions and highlights
- Serif font for section titles and hero greeting
- The overall two-column body layout (main content + sidebar)

DO NOT reuse Provider-specific widgets:
- No Continuity Plan builder (SS doesn't author plans)
- No vault access widget (SS has no vault access)
- No certification tracker (not a licensed role)
- No license/CEU tracker (not applicable)
- No Integrative Network carousel (Provider-only)
- No referral or job posting widgets
- No financial widgets

─────────────────────────────────────────────────────────────
LEGACY REFERENCE — ss_dashboard_legacy.php is also attached.
─────────────────────────────────────────────────────────────
Study it for context only — NOT as a design reference.
Extract from it:
- What widgets and sections currently exist on the SS dashboard
- What actions/modals the SS can trigger from here
- What data the SS currently sees (provider names, task counts,
  incident status indicators)
- Any SS-specific functionality not mentioned in the design
  task below that should be preserved in the redesign

Do NOT carry forward:
- Any visual styles, layout patterns, or CSS from this file
- Any hardcoded colors, inline styles, or legacy markup
- Any design decisions

The legacy file answers "what does SS need" —
the Provider dashboard answers "how should it look".

─────────────────────────────────────────────────────────────
DESIGN TASK
─────────────────────────────────────────────────────────────
Design a complete, beautiful, fully responsive HTML dashboard
page for the Support Steward. Single HTML file with embedded
CSS. Use the exact same CSS variables, typography, color
tokens, spacing, and component patterns from the Provider
dashboard.

The page must include ALL of these sections:

1. TOPBAR (placeholder — same style as Provider)
   Logo left, user avatar + name right. No functionality needed.

2. SIDEBAR (placeholder — same style as Provider)
   Nav items for the SS portal sidebar structure:
     Main: Overview, Dashboard, My Profile
     Critical Moment Plans: My Practitioners
     Activation: My Tasks, View Important Documents,
       Continuity Stewards (read-only), Critical Incident Log
     Communication: Messages, Activity Log
     Account: Settings
   Active state on Dashboard.
   "Active Critical Incident" badge under brand name
   (shown because $has_emergency is true in this design).

3. HERO GREETING
   Same "Good morning, [Name]" pattern as Provider.
   Name: Linda Johnson
   Role chip: Support Steward (green)
   Subtitle: "You are watching over 2 practitioners.
              Everything is normal · 1 preparation task pending."
   This subtitle should feel reassuring on a normal day.
   Right side actions:
     "Activity Log" link with activity icon
     "Report Critical Incident" button — RED, prominent,
     this is the most important action on the entire portal.
     It should stand out from all other buttons visually.

4. STAT CHIPS ROW
   4 chips in the same style as Provider:
     Practitioners: 2
     Preparation Tasks: 1 pending
     Active Incidents: 1
     Messages: 3 unread

5. ACTIVE INCIDENT BANNER (critical state — show it)
   Full-width critical alert banner — this is the most
   important element on the page when an incident is active.
   "⚠ You reported a Critical Incident — Dr. Sarah Johnson"
   "Reported today at 9:14 AM · Short-Term Incapacitation"
   "The Continuity Steward has been notified and is now
    managing Dr. Johnson's practice continuity."
   Status indicator: "CS Marcus Webb — Verified ✓ · Active"
   One button: "View Incident Status" (primary)
   The tone here is different from CS's banner — the SS
   reported this and is now waiting and supporting, not
   executing. The banner should feel: "you did the right
   thing, it's being handled."

6. MAIN CONTENT AREA (left, wider column)

   6a. MY PRACTITIONERS — provider status cards
       Section header: eyebrow "Your Watch" + title
       "Practitioners You Support" + "View All" link
       2 provider cards using the color-rail pattern:

         Card 1 — Dr. Sarah Johnson
           Status rail: RED (active incident reported by SS)
           Avatar initials: SJ (gold avatar)
           Title: Licensed Psychologist · Atlanta, GA
           Chips: "Incident Reported" (red) · "Primary SS"
           Meta row: Incident reported today ·
                     CS Marcus Webb notified
           Actions: "View Incident Status" (primary only —
                     SS has no task list, just status visibility)
           Note below card: "The Continuity Steward is now
           managing Dr. Johnson's practice. Stay available
           for coordination."

         Card 2 — Dr. James Okafor
           Status rail: GOLD (preparation task pending)
           Avatar initials: JO
           Title: Licensed Counselor · Chicago, IL
           Chips: "1 Task Pending" (gold) · "Primary SS"
           Meta row: Plan reviewed · Last check-in: 3 days ago
           Actions: "View Tasks" + "Message Provider"

   6b. MY PREPARATION TASKS
       Section header: eyebrow "Before It Happens" + title
       "Your Preparation Checklist"
       These are standby tasks — things to do BEFORE any
       incident so you're fully prepared:
         ✓ Review Dr. Sarah Johnson's Continuity Plan — Done
         ✓ Confirm CS contact details are saved — Done
         ☐ Complete emergency contact info for Dr. Okafor
           — "Complete Now" action link, gold accent
       Each task: checkbox state + title + provider tag +
                  status chip (Done green / Pending gold)
       "View All Tasks" link at bottom
       Tone: calm, preparation-focused, not alarming

   6c. CONTINUITY STEWARD CONTACTS card
       "Who to Call When It Happens"
       This card is critical — the SS needs to know who
       their CS contacts are at a glance:
         Dr. Sarah Johnson's CS:
           Avatar: MW · Marcus Webb, LCSW
           Role: Primary Continuity Steward
           Status: Active ✓
           Action: "Message" button
         Dr. James Okafor's CS:
           Avatar: PR · Dr. Priya Raman
           Role: Primary Continuity Steward
           Status: Active ✓
           Action: "Message" button
       Card subtitle: "These are the licensed professionals
       who will manage the practice if you report an incident."
       Simple, clean — names, faces (initials), one button each.

7. SIDEBAR WIDGETS (right, narrower column)

   7a. REPORT INCIDENT widget (always visible, prominent)
       Even in the sidebar this widget stays visible.
       A card with a clear, calm explanation:
       "If something has happened to one of your practitioners"
       List the 5 most common incident types simply:
         · They have passed away
         · They are hospitalized or incapacitated
         · They are missing or unreachable
         · A natural disaster has affected their area
         · Another serious situation
       "Report Critical Incident" button — RED, full width
       Below: small reassuring text:
       "Only submit if the situation is real. This notifies
        the Continuity Steward immediately."
       This widget should feel like a calm instruction card,
       not an alarm button — but the button is unmistakably red.

   7b. INCIDENT STATUS widget (shown because incident is active)
       "Active Incident — Dr. Sarah Johnson"
       Your report: Short-Term Incapacitation
       Reported: Today at 9:14 AM
       CS assigned: Marcus Webb, LCSW
       Status: "CS Verified · Tasks in progress"
       Progress indicator: simple text "3 of 12 tasks done"
       (No detailed task list — SS sees status, not tasks)
       "View Full Status" link

   7c. RECENT ACTIVITY feed
       Last 4 activity events for Linda
       Same style as Provider's activity feed widget
       Events:
         🔴 Critical — You reported: Dr. Sarah Johnson incident
         🟢 Success  — CS Marcus Webb verified the incident
         🔵 Info     — Message from Dr. James Okafor
         🟡 Warning  — Preparation task pending: Dr. Okafor
       "View Activity Log" link

   7d. MY PROFILE COMPLETENESS
       Same profile strip pattern as Provider
       Linda Johnson: 75% complete
       "Complete your profile so practitioners can find you"
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

CRITICAL DESIGN PRINCIPLES FOR THE SS:
- Simplicity over completeness. Every widget should have
  fewer elements than the CS equivalent. This person is not
  a professional user of this software — they are here
  because something may have happened to someone they care
  about. Reduce cognitive load at every turn.
- The "Report Critical Incident" button must be the most
  visually dominant interactive element on the page.
  Red. Clear label. Never hidden in a menu or modal trigger.
  Present in both the hero and the sidebar widget.
- The incident banner tone is different from CS.
  CS's banner = "HERE IS WHAT YOU MUST DO RIGHT NOW"
  SS's banner = "You did the right thing. It's being handled.
                 Here is the current status."
  SS is supporting, not executing. Calm reassurance.
- Preparation tasks should feel like a friendly checklist,
  not a compliance requirement. Warm copy, not clinical.
- Green = safe / all good / confirmed
- Red = incident reported / needs attention
- Gold = pending / needs your action soon
- The overall feeling: a trusted friend who keeps things
  organized for you — clear, warm, and impossibly simple
  to use under pressure.

─────────────────────────────────────────────────────────────
DELIVER
─────────────────────────────────────────────────────────────
One complete HTML file.
Every section listed above must be present and visually
polished. This is a design reference — it will be handed
to a developer as the visual spec for the PHP implementation.
Make it pixel-perfect, realistic, and true to the SS role.
The SS dashboard should feel noticeably simpler and warmer
than the CS dashboard — same design system, different
emotional register.
```

---

## PROMPT 2 — Claude (Wave 1: Centralize + Design)
## SS Portal — dashboard.php — Wave 1

```
Task: SS Portal — dashboard.php — Wave 1 (Centralize + Design)

You are building the Support Steward portal dashboard.
The SS is a trusted non-clinical contact — a family member,
office manager, or close friend of the practitioner. They are
NOT a doctor. Their primary job is to watch over their assigned
practitioners and report a critical incident if something goes
wrong. On a normal day they have almost nothing to do.
On a crisis day they are the first responder.

Design for this emotional reality:
- Normal day: calm, reassuring, "everything is fine"
- Crisis day: scared, possibly grieving, needs impossible
  clarity — the big red Report Incident button must dominate,
  instructions must be written for a non-technical person
  under extreme stress

The dashboard must be simpler and warmer than CS or Provider.
Fewer widgets. Larger text on critical actions. Calm copy.

Read and apply these rules before touching anything:

1. Data is user-scoped, not portal-scoped. seed.json keyed by
   user_id. ss_linda is the demo SS user.

2. Centralize first, design second — in one pass. No wiring in
   this chat. No write-path, no fetch() calls, no save_*.php.
   All dynamic data is hardcoded or stubbed with realistic
   placeholder values for now. Wiring is Wave 2.

3. Auth gate is role-specific for portal pages (unlike shared
   templates). SS dashboard must gate on support_steward role:
     $current_user = aegis_current_user('ss_linda');
     if (!$current_user || $current_user['role'] !== 'support_steward')
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
   "Report Critical Incident" button must also be in
   .page-hero-actions — red/danger treatment, always visible.

9. Modals: always .modal-overlay + .open driven by global JS.
   openModal() / closeModal() from _shared.js — never local
   redefinitions.

10. No hardcoded copy that belongs in seed.json or a models.php
    helper. SS name, avatar initials, role label — all from
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
    reference for the SS dashboard created in Claude Design.
    Mirror it exactly — every section, widget, card, layout,
    and spacing decision in that file is intentional.
    provider_dashboard.php is the secondary reference only
    for shared chrome patterns (hero structure, stat-chips-row,
    section headers, modal shell) where the HTML design file
    doesn't cover them. SS-specific widgets (Report Incident
    button, Practitioner status cards, CS contact card,
    Incident status widget) come from dashboard.html exclusively.

13. php -l every file before delivery. No exceptions.

14. Before executing, list every file you need that is not
    already in project knowledge or uploaded — by exact
    filename — and wait for upload before proceeding.

─────────────────────────────────────────────────────────────
STEP 1 — Read everything, output plan, wait for confirmation
─────────────────────────────────────────────────────────────
Read in this order:

FROM PROJECT KNOWLEDGE:
1. AEGIS-PROJECT-CONTEXT.md       — §4.3 SS portal pages + key
                                    pages spec, §17.1 symmetric
                                    naming, SS sidebar structure
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
8. seed.json                      — find ss_linda user record,
                                    confirm fields present:
                                    display_name, avatar_initials,
                                    role, any providers she is
                                    assigned to as SS,
                                    plan_stewards rows for linda,
                                    any critical_incidents linked
                                    to her providers,
                                    activity_events for ss_linda
9. _shared/models.php             — scan for any existing SS
                                    dashboard helpers, confirm
                                    aegis_current_user() signature
10. dashboard.html                — SS dashboard design reference,
                                    full read — this is the visual
                                    spec, extract every section,
                                    class name, CSS variable, and
                                    component pattern before writing
                                    any markup
11. ss_dashboard_legacy.php       — legacy file, full read —
                                    extract every section, widget,
                                    metric, modal that exists
                                    (even if poorly implemented)
                                    — nothing should be lost in
                                    the redesign

After reading ALL files output ONE plan block:

  ── SS dashboard sections inventory ──
  From provider_dashboard.php:
    Sections present: [list with brief description]
  From ss_dashboard_legacy.php:
    Sections present: [list]
    Sections unique to SS (not in Provider): [list]
    Sections in Provider not applicable to SS: [list]

  ── SS dashboard final section plan ──
  List every section the new SS dashboard will have, in order,
  with a one-line description of its SS-specific content:
    e.g. "Hero greeting — ss_linda name + role + date +
          Report Incident red button in actions"
    e.g. "KPI chips — Practitioners / Preparation Tasks /
          Active Incidents / Unread Messages"
    e.g. "Active Incident banner — conditional on
          $has_emergency — reassuring tone, status-focused"
    e.g. "My Practitioners — color-rail cards per assigned
          provider with incident status or task count"
    e.g. "Preparation Tasks — standby checklist, warm copy"
    e.g. "CS Contact card — who to call when it happens"
    e.g. "Report Incident sidebar widget — always visible"
    e.g. "Recent Activity feed — last 4 events"

  ── Stub variables needed (Wave 2 wires these) ──
  List every PHP variable that will be stubbed in Wave 1:
    e.g. $practitioners_assigned = 2;  // STUB
    e.g. $pending_tasks_count    = 1;  // STUB
    e.g. $active_incidents       = 1;  // STUB

  ── Modals needed ──
  List every modal on SS dashboard (from legacy file + any
  new ones matching the SS spec in AEGIS-PROJECT-CONTEXT.md)
  The Report Critical Incident modal is the most important —
  confirm it includes incident type dropdown (from the 7
  approved types), narrative field, contact attempts log,
  and a false-reporting warning before submit.

  ── Design violations in ss_dashboard_legacy.php ──
  List every violation of Aegis_Desing_Prompt_Short.md found:
    e.g. "inline SVG icons — replace with aegis_icon()"
    e.g. "hardcoded hex colors — replace with CSS vars"
    e.g. "local openModal() redefinition — remove"

  ── Seed status for ss_linda ──
  Fields present: [list]
  Fields missing: [list — flag for Wave 2 seed gate]

Auto-proceed to Step 2 immediately after plan block.

─────────────────────────────────────────────────────────────
STEP 2 — Centralize
─────────────────────────────────────────────────────────────
Apply centralization to the legacy SS dashboard.php:

2a. Replace file header with canonical pattern:
      <?php
      declare(strict_types=1);
      define('AEGIS_ENTRY', true);
      require_once __DIR__ . '/../_shared/models.php';
      require_once __DIR__ . '/../_shared/icons.php';

      $current_user = aegis_current_user('ss_linda');
      if (!$current_user ||
          $current_user['role'] !== 'support_steward') {
          header('Location: /reset.php?token=aegis-demo-reset');
          exit;
      }

      $role  = $current_user['role'];
      $uid   = $current_user['id'];

      // Page identity
      $active_page       = 'dashboard';
      $page_title        = 'Dashboard';
      $page_portal_label = 'Aegis Support Steward Portal';
      $has_emergency     = !empty($_GET['emergency']) &&
                           $_GET['emergency'] === 'true';

      // ── STUBS — wire in Wave 2 ──────────────────────────
      $practitioners_assigned = 2;   // STUB
      $pending_tasks_count    = 1;   // STUB
      $active_incidents       = 1;   // STUB
      $unread_messages        = 3;   // STUB
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
- Role chip: "Support Steward" green chip from $current_user
- Subtitle: calm reassuring summary on normal day —
  "You are watching over N practitioners. Everything is normal."
  Or if $has_emergency: "Active incident reported for [name]."
- .page-hero-actions must include:
    Activity link: aegis_icon('activity', 14) → activity.php
    "Report Critical Incident" button — .btn.btn-danger or
    red treatment using var(--red-dark). This is the most
    important button in the entire portal. Never style it
    the same as a standard btn-primary. Always red.

ACTIVE INCIDENT BANNER:
- Conditional: <?php if ($has_emergency): ?>
- .alert.alert-critical — but tone is reassuring not urgent
  "You reported an incident for [provider name]"
  "The Continuity Steward has been notified and is acting."
  Status line showing CS name + verified status
- One button: "View Incident Status"
- <?php endif; ?>
- This banner is fundamentally different from CS's banner.
  CS sees: "ACT NOW". SS sees: "It's being handled. Here's
  the current status." Same class, different copy.

MY PRACTITIONERS:
- Section header eyebrow "Your Watch" + title
  "Practitioners You Support" + "View All" link
- Color-rail cards per assigned provider
- SS cards are simpler than CS cards — fewer action buttons,
  status-focused not task-focused
- Incident status chip when $has_emergency
- "View Incident Status" (not "Open Task List" — SS has no
  task execution role, only status visibility)
- Below incident card: a reassuring note:
  "The CS is managing this. Stay available for coordination."

PREPARATION TASKS:
- Section header eyebrow "Before It Happens" + title
  "Your Preparation Checklist"
- Warm, friendly copy — not clinical compliance language
- Standby tasks from stub $preparation_tasks array
- Done tasks: green check. Pending: gold with action link.
- "View All Tasks" → my-tasks.php

CS CONTACT CARD:
- "Who to Call When It Happens"
- One row per assigned provider showing their CS's name,
  initials avatar, and a Message button
- Short subtitle explaining the CS's role in plain language
  for a non-clinical reader

REPORT INCIDENT MODAL:
- Triggered by the red hero button + sidebar widget button
- Modal ID: reportIncidentModal
- Content: select practitioner → incident type dropdown
  (the 7 approved types in plain language, not clinical) →
  narrative textarea (what did you observe / what happened) →
  contact attempts log (did you try calling? who answered?) →
  FALSE REPORTING WARNING before submit button:
  "⚠ Only submit if this situation is real. This immediately
   notifies the Continuity Steward and begins the incident
   response process."
- Submit button: red, "Report Incident"
- Cancel: standard btn-outline

SIDEBAR WIDGETS:

  REPORT INCIDENT widget (always visible):
  - Card titled "If Something Has Happened"
  - 5 bullet points describing incident types in plain language
    (not clinical — "They have passed away" not "Death")
  - Red full-width "Report Critical Incident" button
  - Small reassuring subtext below button

  INCIDENT STATUS widget (conditional on $has_emergency):
  - Provider name + incident type
  - CS name + verified status
  - Simple progress text "X of Y tasks done"
  - "View Full Status" link

  RECENT ACTIVITY:
  - Last 4 events for ss_linda from stub array
  - Same .activity-feed style as Provider
  - "View Activity Log" link

  PROFILE COMPLETENESS:
  - Same profile strip pattern
  - Reads from $current_user completeness
  - "Edit Profile" button

CSS:
- Page-specific <style> block at top of file
- Only styles not covered by _shared.css
- All tokens use CSS variables — no hardcoded values
- SS dashboard intentionally has fewer page-specific styles
  than Provider or CS — simpler page, simpler CSS

JS:
- Page-specific <script> block via $page_extra_foot
- No global helper redefinitions
- Report incident modal submit: stub with comment
  // Wire in Wave 2: fetch('/_shared/save_incident.php',
  //   { action: 'trigger', practitioner_id, incident_type,
  //     narrative, contact_attempts })
- Stub fetch() calls commented out for all other actions

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
- Confirm "Report Critical Incident" red button in hero actions
- Confirm $has_emergency guard on incident banner
- Confirm incident banner uses reassuring SS tone (not CS tone)
- Confirm all stub variables clearly marked // STUB
- Confirm reportIncidentModal has false-reporting warning
- Confirm Report Incident sidebar widget always renders
  (not conditional — it's always visible for SS)
- Confirm every modal uses .modal-overlay structure
- Confirm page_head / header / sidebar / page_foot all included
- Confirm auth gate checks support_steward role

─────────────────────────────────────────────────────────────
DELIVER
─────────────────────────────────────────────────────────────
Files changed:
  support-steward-portal/dashboard.php    (centralized + designed)

Single file delivery — not zipped unless you prefer zip.
CHANGES note: list every centralization fix applied and every
design violation corrected, section by section.
```
