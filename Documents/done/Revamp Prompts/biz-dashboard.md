# BP Portal — biz_dashboard.php — Two Prompts

---

## PROMPT 1 — Claude Design
## BP Dashboard — HTML Design Reference

```
You are designing a dashboard for a Business Partner (BP) —
an independent freelancer or agency that provides professional
services to healthcare practitioners through the Aegis
marketplace.

Deeply understand who this person is:
A BP is a professional service provider — think medical
billing specialists, practice management consultants, web
designers, IT support, legal/compliance advisors, marketing
agencies, bookkeepers, HR consultants, and more. They are
not healthcare practitioners and have nothing to do with
clinical continuity. They are here to run their service
business and get paid.

The BP portal has TWO distinct modes driven by bp_type:
  AGENCY  — A company with a team. Multiple staff members
            working on multiple practitioner clients
            simultaneously. KPIs are business-level:
            revenue, team utilization, active partners,
            pipeline value. The owner manages team members
            and assigns contracts to specialists.

  FREELANCER — A solo professional. Personal brand, personal
               hourly rate, personal availability calendar.
               KPIs are individual: active clients, earnings,
               open proposals, profile views.

Design BOTH modes. The agency mode is shown by default.
A ?type=freelancer toggle switches the experience.
Every section that differs between modes must be designed
in both variants.

What a BP cares about on their dashboard:
  AGENCY VIEW:
  - How much revenue did we generate this month?
  - How many active contracts are running right now?
  - Is my team at capacity or do we have open slots?
  - Which proposals are still pending a decision?
  - Are any milestones overdue right now?
  - What new jobs just got posted that match our services?
  - Any invoices overdue or awaiting payment?

  FREELANCER VIEW:
  - What are my active clients and their status?
  - What am I earning this month?
  - Am I available for new work — what's my next open slot?
  - Which proposals are still out there?
  - What milestones do I need to submit this week?
  - What new jobs match my skills?

The BP dashboard is a professional business tool — similar
in complexity to Upwork's freelancer dashboard but simpler
and more focused. Clean, data-forward, efficient.

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
- No Continuity Plan widgets (BP has no continuity role)
- No CS/SS designation panels
- No vault access
- No CEU or license tracker
- No critical incident anything

─────────────────────────────────────────────────────────────
LEGACY REFERENCE — biz_dashboard_legacy.php is also attached.
─────────────────────────────────────────────────────────────
Study it for context only — NOT as a design reference.
Extract from it:
- What widgets and sections currently exist
- What actions/modals the BP can trigger
- What data the BP currently sees
- Any BP-specific functionality not in the design task below
  that should be preserved

Do NOT carry forward any visual styles, layout, or CSS.

The legacy file answers "what does BP need" —
the Provider dashboard answers "how should it look".

─────────────────────────────────────────────────────────────
DESIGN TASK — AGENCY MODE (default, ?type=agency)
─────────────────────────────────────────────────────────────
Design the Agency dashboard. Single HTML file with embedded
CSS. Use the exact same CSS variables from Provider dashboard.

1. TOPBAR (placeholder — same style as Provider)
   Logo left. Avatar + "Acme Practice Services" + "Agency" chip right.

2. SIDEBAR (placeholder — same style as Provider)
   Nav items for BP portal sidebar (Agency mode):
     Main: Overview, Dashboard, My Profile
     Work: Find Jobs (badge: 5 new), Contracts (badge: 3),
           Proposals (badge: 2), Milestones (badge: 1 overdue)
     Financial: Finances, Invoices (badge: 2 pending),
                Payment Setup
     Communication: Messages (badge: 4), Notifications
     Account: Settings
     Team: Team Management
   Active state on Dashboard.

3. HERO GREETING
   "Good morning, Acme Practice Services"
   Role chip: Agency Partner (gold)
   Subtitle: "3 active contracts · 2 proposals pending ·
              Team at 75% capacity"
   Right side actions:
     "Notifications" link with activity icon
     "Find New Jobs" primary button — gold, main CTA

4. STAT CHIPS ROW — Agency KPIs (6 chips):
   Active Partners: 3
   Monthly Revenue: $12,400  ← blurred by default (privacy toggle)
   Open Proposals: 2
   Active Projects: 4
   Team Utilisation: 75%
   Partner Rating: 4.8 ★

   REVENUE PRIVACY TOGGLE: A small eye icon button next to
   the revenue chip. Default state = blurred. Click to reveal.
   This toggle must be designed — show both blurred and
   revealed states with a note that blurred is default.
   Blurred = "••••••" with eye-off icon.
   Revealed = "$12,400" with eye icon.

5. MAIN CONTENT AREA (left, wider column)

   5a. ACTIVE CONTRACTS
       Section header: eyebrow "Your Work" + title
       "Active Contracts" + "View All" → contracts.php
       3 contract cards using color-rail pattern:

         Card 1 — Dr. Sarah Johnson
           Status rail: GREEN (on track)
           Avatar: SJ (gold)
           Service: Medical Billing & Claims Management
           Engagement type chip: Monthly Retainer
           Value: $2,800/mo  ← blurred if privacy on
           Next milestone: "June Billing Cycle" · Due Jun 30
           Team member assigned: "Riley Chen" (Agency only)
           Actions: "View Contract" + "Message Provider"

         Card 2 — Dr. James Okafor
           Status rail: GOLD (milestone due soon)
           Avatar: JO
           Service: Practice Website Redesign
           Engagement type chip: Fixed-Price Project
           Value: $4,500  ← blurred if privacy on
           Next milestone: "Homepage Mockup" · Due in 3 days
           Team member: "Marcus T." (Agency only)
           Actions: "Submit Milestone" (primary) + "View Contract"

         Card 3 — Dr. Priya Raman
           Status rail: RED (milestone overdue)
           Avatar: PR
           Service: HIPAA Compliance Audit
           Engagement type chip: Per-Deliverable
           Value: $1,200  ← blurred if privacy on
           Overdue: "Audit Report" · Was due Jun 5
           Team member: "Agency Owner" (Agency only)
           Actions: "Submit Now" (primary red) + "View Contract"

   5b. OPEN PROPOSALS
       Section header: eyebrow "Pipeline" + title
       "Proposals Awaiting Decision" + "View All" → proposals.php
       2 proposal cards:

         Proposal 1 — Dr. Elena Vasquez
           Status chip: Under Review (gold)
           Service: Credentialing Support
           Submitted: 3 days ago
           Proposed value: $950  ← blurred if privacy on
           Actions: "View Proposal" + "Withdraw"

         Proposal 2 — Dr. Marcus Webb
           Status chip: Viewed (blue — provider opened it)
           Service: Medical Billing Setup
           Submitted: 1 week ago
           Proposed value: $1,800  ← blurred if privacy on
           Actions: "View Proposal" + "Edit"

   5c. NEW JOB MATCHES
       Section header: eyebrow "Marketplace" + title
       "New Jobs Matching Your Services" + "Browse All" → find-jobs.php
       3 compact job listing cards (lighter than contract cards):

         Job 1 — Medical Billing Specialist Needed
           Posted by: Dr. [Anonymous] — Atlanta, GA
           Budget: $800–$1,200/mo · Monthly Retainer
           Urgency chip: Urgent (red)
           Tags: medical billing, claims, insurance
           Action: "Send Proposal" button

         Job 2 — Practice Website & SEO
           Posted by: Dr. [Anonymous] — Austin, TX
           Budget: $3,000–$5,000 · Fixed Price
           Tags: web design, SEO, healthcare
           Action: "Send Proposal" button

         Job 3 — HIPAA Compliance Review
           Posted by: Dr. [Anonymous] — Chicago, IL
           Budget: $500–$1,500 · Per Deliverable
           Tags: HIPAA, compliance, audit
           Action: "Send Proposal" button

6. SIDEBAR WIDGETS (right, narrower column)

   6a. TEAM CAPACITY PANEL (Agency only)
       "Team Capacity" card — this is a key Agency widget.
       Shows X of Y client slots used with a visual bar:
         Current: 6 of 8 slots · 75% capacity
         Capacity bar: gold fill to 75%, neutral remainder
       Team member status list (compact):
         Riley Chen — Active · 2 projects
         Marcus T.  — Active · 2 projects
         Sam Lee    — Idle · 0 projects
         Jordan K.  — Active · 1 project
       "Manage Team" → team.php
       Note: "2 open slots — ready for new partners"

   6b. MILESTONES DUE
       "Upcoming Milestones" card
       3 items sorted by urgency:
         🔴 Audit Report — Dr. Priya Raman · OVERDUE
             "Submit Now" link (red)
         🟡 Homepage Mockup — Dr. Okafor · Due in 3 days
             "Submit" link
         🟢 June Billing — Dr. Johnson · Due Jun 30
             "View" link
       "View All Milestones" → milestones.php

   6c. REVENUE SNAPSHOT
       "This Month" card with revenue figures
       All figures blurred by default (privacy toggle)
       Invoiced: $14,200
       Received: $12,400
       Outstanding: $1,800
       Eye toggle button to reveal/blur all figures
       "View Finances" → finances.php

   6d. RECENT NOTIFICATIONS
       Last 4 notifications
       Same style as Provider activity feed widget:
         🟢 Success  — Proposal accepted: Dr. Johnson
         🔵 Info     — New job match: Medical Billing
         🟡 Warning  — Milestone overdue: Dr. Raman
         🔵 Info     — New message from Dr. Okafor
       "View All Notifications" → notifications.php

─────────────────────────────────────────────────────────────
DESIGN TASK — FREELANCER MODE (?type=freelancer)
─────────────────────────────────────────────────────────────
Show this as a second page / section in the same HTML file,
clearly labeled "Freelancer Mode" with a mode toggle at top.
All sections that differ from Agency mode:

HERO: "Good morning, Jamal Washington"
  Role chip: Freelancer Partner (gold)
  Subtitle: "2 active clients · Next availability: June 15"

STAT CHIPS ROW — Freelancer KPIs (5 chips, no Team Utilisation):
  Active Clients: 2
  Monthly Earnings: $4,200  ← blurred by default
  Open Proposals: 1
  Profile Views: 34 this week
  Avg Rating: 4.9 ★

ACTIVE CONTRACTS — same structure, NO "Team member assigned" row

CAPACITY PANEL changes to AVAILABILITY PANEL:
  "My Availability" card
  Current status: Available for new work
  Next open slot: June 15, 2025
  Availability toggle: Full-time / Part-time / Not Available
  "Update Availability" button
  Hours per week: 20 hrs/wk remaining

Everything else (proposals, job matches, milestones,
revenue snapshot, notifications) same as Agency but
with freelancer-appropriate copy (no "team" references).

─────────────────────────────────────────────────────────────
DESIGN REQUIREMENTS
─────────────────────────────────────────────────────────────
- Single HTML file, all CSS embedded in <style> tag
- Include a mode toggle at the top: "Agency" | "Freelancer"
  clicking it scrolls to / shows the relevant section
- Use IDENTICAL CSS variables as Provider dashboard:
    --gold-dark, --gold-light, --icon-bg-gold
    --surface, --portal-bg, --border, --border-dark
    --text, --text-3, --text-4
    --red, --red-dark, --red-light
    --green, --green-dark, --green-light
    --blue-dark (for info states)
    --font-serif, --font-sans
    --radius, --radius-lg, --radius-sm
    --shadow, --shadow-sm
    --transition
- Fully responsive — sidebar collapses on mobile
- Every component must be visually complete —
  no placeholder boxes, no "lorem ipsum"

CRITICAL DESIGN PRINCIPLES FOR THE BP:
- This is a professional business tool. More data-dense
  than SS, slightly simpler than a full CRM. Clean,
  efficient, task-oriented.
- Revenue privacy toggle must be visually prominent and
  consistent. Every financial figure on the page blurs/
  reveals together when the toggle fires. The blurred
  state (default) should look intentional, not broken —
  use "••••" or a blur CSS effect, not empty space.
- Agency mode feels like running a small business.
  Freelancer mode feels like managing your personal
  consulting practice. Same components, different scale.
- Contract cards are the most important widgets.
  They tell the BP exactly what's happening with each
  client and what action is needed. Color rails:
    GREEN  = on track, no action needed
    GOLD   = milestone due soon, attention needed
    RED    = overdue, act now
- "Find New Jobs" is the growth CTA — always visible
  in the hero. The BP needs to keep their pipeline full.
- Gold = primary brand / active / accepted
- Blue = info / pipeline / under review
- Green = completed / on track / paid
- Red = overdue / rejected / action required
- The overall feeling: a professional freelance/agency
  platform built specifically for the healthcare services
  market — familiar to anyone who's used Upwork or
  Fiverr, but focused and purpose-built.

─────────────────────────────────────────────────────────────
DELIVER
─────────────────────────────────────────────────────────────
One complete HTML file showing both Agency and Freelancer
modes. Every section listed above must be present and
visually polished. This is a design reference — it will be
handed to a developer as the visual spec for the PHP
implementation. Make it pixel-perfect, realistic, and true
to the BP role.
```

---

## PROMPT 2 — Claude (Wave 1: Centralize + Design)
## BP Portal — biz_dashboard.php — Wave 1

```
Task: BP Portal — biz_dashboard.php — Wave 1 (Centralize + Design)

You are building the Business Partner portal dashboard.
The BP is an independent freelancer or agency providing
professional services to healthcare practitioners through
the Aegis marketplace — medical billing, web design,
compliance, IT, HR, marketing, and more.

The BP portal has TWO modes driven by bp_type:
  AGENCY     (?type=agency)     — company with a team,
             business-level KPIs, team capacity panel,
             team member assignment on contracts
  FREELANCER (?type=freelancer) — solo professional,
             personal KPIs, availability panel,
             no team management

Both modes are served by the same dashboard.php file.
$bp_type = $current_user['bp_type'] ?? 'agency' drives
every conditional section. Never hardcode mode — always
branch on $bp_type.

Read and apply these rules before touching anything:

1. Data is user-scoped, not portal-scoped. seed.json keyed by
   user_id. bp_acme is the demo Agency BP user.
   bp_jamal (if seeded) is the demo Freelancer BP user.

2. Centralize first, design second — in one pass. No wiring in
   this chat. No write-path, no fetch() calls, no save_*.php.
   All dynamic data is hardcoded or stubbed with realistic
   placeholder values for now. Wiring is Wave 2.

3. Auth gate is role-specific for portal pages.
   BP dashboard must gate on business_partner role:
     $current_user = aegis_current_user('bp_acme');
     if (!$current_user || $current_user['role'] !== 'business_partner')
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
   component styles only.

7. aegis_icon() for all icons — no inline SVGs, no ✓ literals.

8. Hero banner: always .hero-banner.is-quiet.
   Activity link with aegis_icon('activity', 14) mandatory
   in hero .page-hero-actions.
   "Find New Jobs" primary button also in .page-hero-actions.

9. Modals: always .modal-overlay + .open driven by global JS.
   openModal() / closeModal() from _shared.js — never local
   redefinitions.

10. No hardcoded copy that belongs in seed.json or a models.php
    helper. BP name, avatar initials, bp_type, role label —
    all from $current_user. Contract details, proposal data,
    team members — stubbed as PHP variables at the top of the
    file, clearly marked // STUB — wire in Wave 2.

11. seed.json and models.php are uploaded by the user — read
    from uploaded files only. All other files (_shared.css,
    icons.php, Aegis_Desing_Prompt_Short.md, etc.) must be
    read from Aegis project knowledge. If a needed file is not
    found in project knowledge, flag it by exact filename and
    wait for upload.

12. dashboard.html is the visual spec — a purpose-built design
    reference for the BP dashboard created in Claude Design.
    Mirror it exactly — every section, widget, card, layout,
    and spacing decision in that file is intentional.
    provider_dashboard.php is the secondary reference only
    for shared chrome patterns (hero structure, stat-chips-row,
    section headers, modal shell) where the HTML design file
    doesn't cover them. BP-specific widgets (contract cards,
    proposal pipeline, job matches, team capacity / availability
    panel, revenue snapshot with privacy toggle) come from
    dashboard.html exclusively.

13. Revenue privacy toggle is a first-class feature.
    Per AEGIS-PROJECT-CONTEXT.md §14.4: default ON, persists
    per session. "ON" = figures VISIBLE (BP sees their
    numbers); "OFF" = privacy mode, figures BLURRED.

    All financial figures on the page must be wrapped in a
    .bp-revenue element. JavaScript toggles a
    .bp-revenue-hidden class on <body>. CSS blurs figures
    when the class is present:
      body.bp-revenue-hidden .bp-revenue {
        filter: blur(5px);
        user-select: none;
        pointer-events: none;
      }

    Default state = visible (toggle ON, no class on body).
    Toggle persists per session via sessionStorage key
    'aegis_bp_revenue_visible' (boolean). On DOMContentLoaded:
    if sessionStorage explicitly stores false, add
    .bp-revenue-hidden to body; otherwise leave clean.

    The eye-icon button lives in the hero stat chips row
    next to the revenue chip:
      - Visible state: aegis_icon('eye', 14)
        + data-tip="Hide financial figures"
      - Hidden state:  aegis_icon('eye-off', 14)
        + data-tip="Show financial figures"
    JS swaps the icon and data-tip on toggle.

    The Revenue Snapshot sidebar card has its OWN eye-icon
    button that controls the SAME body class — they sync
    automatically because both read/write the same
    sessionStorage key and body class.

14. ?type= query param drives bp_type for demo switching.
    $bp_type = $current_user['bp_type'] ?? $_GET['type'] ?? 'agency';
    All Agency-only sections (Team Capacity, team member rows
    in contract cards) are guarded by:
      <?php if ($bp_type === 'agency'): ?>
    All Freelancer-only sections (Availability Panel) are
    guarded by:
      <?php if ($bp_type === 'freelancer'): ?>

15. php -l every file before delivery. No exceptions.

16. Before executing, list every file you need that is not
    already in project knowledge or uploaded — by exact
    filename — and wait for upload before proceeding.
17. Modal & Button Rules (M1–M4 from updated
    Aegis_Desing_Prompt_Short.md):
    M1. Modal titles plain text only — never aegis_icon() in
        .modal-title. Only modal-close button carries an
        icon (aegis_icon('x', 13)).
    M2. Multi-step modals use the global .modal-steps row
        with .modal-step / .modal-step-num / .modal-step-divider
        markup. JS toggles .active / .done classes only —
        never inline styles, never bespoke step indicators.
    M3. btn-ghost is invisible on light backgrounds. Use
        btn-outline for any visible secondary action
        ("Update Availability", "View Contract", "Withdraw",
        external links). Reserve btn-ghost for truly
        tertiary (modal Cancel, edit-jump links).
    M4. All btn-icon in tables / card rows use size 14
        (aegis_icon('name', 14)). Size 13 only for compact
        contexts (modal-close, is-compact rows). Match
        network.php as canonical sizing reference.

18. Own-DOCTYPE pattern (P1 from page-update rules):
    The page writes its own <!DOCTYPE html> + <head> +
    <link rel="stylesheet" href="/_shared.css"> +
    theme_loader.php require + page-local <style> block
    with `body { display: flex; }` as the FIRST rule.
    Do NOT use page_head.php — the BP dashboard owns its
    head. Rule 4 above implies page_head.php; this rule
    OVERRIDES that — drop page_head.php from the
    include list, keep header/sidebar/page_foot.

19. Page-local JS lives inside $page_extra_foot as a
    nowdoc, included via page_foot.php. No <?php or <?=
    inside the nowdoc — extract PHP values to
    <script>const NAME = …;</script> set BEFORE the nowdoc
    opens. The revenue privacy toggle JS goes here.

20. dashboard.html → biz_dashboard.php fidelity contract:
    Since dashboard.html is the visual spec, treat it as
    authoritative for:
      - Section order and grouping
      - Class names (where they match _shared.css canonical
        names — use those; where dashboard.html invents a
        name, check if a canonical equivalent exists before
        accepting the new name)
      - Spacing tokens, grid columns, card layouts
      - Empty state copy and illustrations
    Where dashboard.html disagrees with _shared.css canonical
    classes, FLAG IT in the plan block — do NOT silently
    accept either side. Most common conflict areas:
      - dashboard.html may use bespoke .kpi-card; canonical
        is .stat-chips-row > .stat-chip
      - dashboard.html may use bespoke .alert-banner;
        canonical is .alert.alert-info / .alert.alert-critical
      - dashboard.html may use bespoke .btn-primary-gold;
        canonical is .btn.btn-primary
    Resolution: canonical wins unless the dashboard.html
    pattern fills a genuine gap (then promote to _shared.css
    candidate, flag in CHANGES — do not redefine locally).

21. Person-name click routing (P6 from page-update rules):
    Every person name (provider in contract card, team
    member in capacity panel) routes through
    viewPartyProfile(name, kind, slug) from _shared.js.
    Slug comes from users.slug, never derived from name.
    "View Profile" / "Message" actions on provider cards
    use this — never hardcoded /public/<role>.php?slug=…
    URLs in markup.

22. Data attribute encoding (P7):
    All json_encode() output inside HTML attributes wrapped
    in htmlspecialchars(..., ENT_QUOTES). Applies to any
    onclick or data-* attribute carrying structured data
    (e.g. <button data-contract='<?= htmlspecialchars(
    json_encode($c), ENT_QUOTES) ?>'>).

23. Revenue privacy toggle is sessionStorage, NOT
    localStorage. Per-session is correct (matches §14.4 in
    AEGIS-PROJECT-CONTEXT.md — default ON, persists per
    session). Confirm key name 'aegis_bp_revenue_visible'
    is consistent if it already exists elsewhere in the
    portal; otherwise this becomes the canonical name.

24. Agency/Freelancer guards run at MARKUP level, not CSS.
    Use <?php if ($is_agency): ?> … <?php endif; ?> to
    omit sections entirely from output. Do NOT render both
    and CSS-hide via display:none — that ships markup
    Freelancer users shouldn't see and bloats response
    size. Same applies to team-member rows inside contract
    cards: PHP-guard the row, not CSS.

25. Lint gate additions (M1–M4 + privacy + guards):
    - grep -c 'modal-title' biz_dashboard.php — for each
      match, confirm no aegis_icon( within 2 lines after
    - grep -nE 'btn-ghost' biz_dashboard.php — each match
      reviewed against M3 (must be Cancel or truly tertiary)
    - All row-level btn-icon at size 14
    - grep -c 'localStorage' biz_dashboard.php = 0
      (sessionStorage is correct for revenue toggle)
    - Every .bp-revenue wrapper present on every dollar
      figure in markup (grep $ amounts vs .bp-revenue
      occurrences)
    - Every $is_agency / $is_freelancer guard balanced
      (count opens vs endifs)
    - page_head.php NOT included anywhere
    - exactly one theme_loader require + one
      `body { display: flex; }` declaration
─────────────────────────────────────────────────────────────
STEP 1 — Read everything, output plan, wait for confirmation
─────────────────────────────────────────────────────────────
Read in this order:

FROM PROJECT KNOWLEDGE:
1. AEGIS-PROJECT-CONTEXT.md       — §4.4 BP portal pages,
                                    §14 Business Partner full
                                    spec (14.1–14.5), §17.1
                                    symmetric naming,
                                    BP sidebar structure
2. CENTRALIZED-SYSTEM.md          — shared file inventory,
                                    portal page pattern
3. Aegis_Desing_Prompt_Short.md   — full read, every rule
4. Aegis_Desing_Prompt.md         — reference for edge cases
5. _shared/_shared.css            — full read, every canonical
                                    class before writing markup
6. _shared/icons.php              — all icon names
7. provider_dashboard.php         — reference implementation,
                                    full read — note every
                                    section, card, modal, JS
                                    pattern used

FROM UPLOADED FILES:
8. seed.json                      — find bp_acme user record,
                                    confirm fields present:
                                    display_name, avatar_initials,
                                    role, bp_type, bp_business_name,
                                    bp_team_size, bp_hourly_rate,
                                    stripe_connected,
                                    any job_proposals rows,
                                    any contracts rows,
                                    activity_events for bp_acme.
                                    Also check bp_jamal if present.
                                    Flag any gaps.
9. _shared/models.php             — scan for any existing BP
                                    dashboard helpers:
                                    aegis_count_bp_badges(),
                                    aegis_get_bp_contracts(),
                                    aegis_get_bp_proposals(),
                                    aegis_get_bp_jobs() —
                                    confirm which exist
10. dashboard.html                — BP dashboard design reference,
                                    full read — this is the visual
                                    spec, extract every section,
                                    class name, CSS variable, and
                                    component pattern before writing
                                    any markup. Note which sections
                                    differ between Agency and
                                    Freelancer modes.
11. biz_dashboard_legacy.php      — legacy file, full read —
                                    extract every section, widget,
                                    metric, modal that exists
                                    (even if poorly implemented)
                                    — nothing should be lost

After reading ALL files output ONE plan block:

  ── BP dashboard sections inventory ──
  From provider_dashboard.php:
    Sections present: [list with brief description]
  From biz_dashboard_legacy.php:
    Sections present: [list]
    Sections unique to BP (not in Provider): [list]
    Sections in Provider not applicable to BP: [list]

  ── BP dashboard final section plan ──
  List every section in order, noting Agency/Freelancer/Both:
    e.g. "Hero greeting — bp_acme name + bp_type chip +
          Find New Jobs CTA  [Both modes]"
    e.g. "KPI stat chips — Agency: 6 chips with revenue
          privacy toggle  [Agency]"
    e.g. "KPI stat chips — Freelancer: 5 chips  [Freelancer]"
    e.g. "Active Contracts — color-rail cards with milestone
          status + team member row (Agency only)  [Both]"
    e.g. "Open Proposals — pipeline cards  [Both]"
    e.g. "New Job Matches — 3 compact job cards  [Both]"
    e.g. "Team Capacity Panel — sidebar  [Agency only]"
    e.g. "Availability Panel — sidebar  [Freelancer only]"
    e.g. "Milestones Due — sidebar  [Both]"
    e.g. "Revenue Snapshot — sidebar, blurred default  [Both]"
    e.g. "Recent Notifications — sidebar  [Both]"

  ── Stub variables needed (Wave 2 wires these) ──
  List every PHP variable that will be stubbed in Wave 1:
    e.g. $active_contracts   = [];  // STUB array of contracts
    e.g. $open_proposals     = [];  // STUB
    e.g. $new_job_matches    = [];  // STUB
    e.g. $monthly_revenue    = 12400; // STUB — blurred default
    e.g. $team_members       = [];  // STUB Agency only
    e.g. $milestones_due     = [];  // STUB

  ── Revenue privacy toggle implementation plan ──
  Describe exactly how .is-private / sessionStorage will work
  in this file's page-specific JS

  ── Modals needed ──
  List every modal (from legacy file + BP spec)
  e.g. Quick Proposal modal, Job Preview modal, etc.

  ── Agency vs Freelancer conditional sections ──
  List every section that differs between modes and which
  PHP guard ($bp_type === 'agency') wraps it

  ── Design violations in biz_dashboard_legacy.php ──
  List every violation of Aegis_Desing_Prompt_Short.md found

  ── Seed status for bp_acme ──
  Fields present: [list]
  Fields missing: [list — flag for Wave 2 seed gate]

Auto-proceed to Step 2 immediately after plan block.

─────────────────────────────────────────────────────────────
STEP 2 — Centralize
─────────────────────────────────────────────────────────────
Apply centralization to the legacy BP dashboard.php:

2a. Replace file header with canonical pattern:
      <?php
      declare(strict_types=1);
      define('AEGIS_ENTRY', true);
      require_once __DIR__ . '/../_shared/models.php';
      require_once __DIR__ . '/../_shared/icons.php';

      $current_user = aegis_current_user('bp_acme');
      if (!$current_user ||
          $current_user['role'] !== 'business_partner') {
          header('Location: /reset.php?token=aegis-demo-reset');
          exit;
      }

      $role    = $current_user['role'];
      $uid     = $current_user['id'];
      $bp_type = $current_user['bp_type']
                 ?? ($_GET['type'] ?? 'agency');
      if (!in_array($bp_type, ['agency','freelancer'], true)) {
          $bp_type = 'agency';
      }
      $is_agency     = $bp_type === 'agency';
      $is_freelancer = $bp_type === 'freelancer';

      // Page identity
      $active_page       = 'dashboard';
      $page_title        = 'Dashboard';
      $page_portal_label = 'Aegis Business Partner Portal';

      // ── STUBS — wire in Wave 2 ──────────────────────────
      $active_contracts  = [];   // STUB
      $open_proposals    = [];   // STUB
      $new_job_matches   = [];   // STUB
      $monthly_revenue   = 12400; // STUB
      $team_members      = [];   // STUB — Agency only
      $milestones_due    = [];   // STUB
      $pending_invoices  = 2;    // STUB
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
    inline <script src="..."> tags.

2e. Replace all inline SVGs with aegis_icon() calls.
    Replace all ✓ literals with aegis_icon('check', N).
    Replace all hardcoded hex colors with CSS vars.

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
- Greeting: "Good [morning/afternoon], [business name]"
  Business name from $current_user['bp_business_name']
  or fallback to display_name
- Role chip: Agency Partner / Freelancer Partner
  driven by $bp_type
- Subtitle: Agency → "N active contracts · N proposals pending
            · Team at X% capacity"
            Freelancer → "N active clients · Next availability: [date]"
- .page-hero-actions must include:
    Notifications link: aegis_icon('activity', 14)
    "Find New Jobs" button → find-jobs.php — gold/primary

STAT CHIPS ROW:
  Agency (6 chips):
    Active Partners / Monthly Revenue (privacy toggle) /
    Open Proposals / Active Projects /
    Team Utilisation % / Partner Rating ★
  Freelancer (5 chips, no Team Utilisation):
    Active Clients / Monthly Earnings (privacy toggle) /
    Open Proposals / Profile Views / Avg Rating ★

REVENUE PRIVACY TOGGLE:
- Eye icon button adjacent to revenue chip in stat row
- On click: toggles .bp-revenue-hidden class on <body>
- CSS: body.bp-revenue-hidden .bp-revenue { filter: blur(6px); }
- All financial figures wrapped in <span class="bp-revenue">
- sessionStorage key: 'aegis_bp_revenue_visible'
  Default: hidden (privacy on)
- Init on DOMContentLoaded: read sessionStorage, apply class

ACTIVE CONTRACTS:
- Section header eyebrow "Your Work" + title
  "Active Contracts" + "View All" → contracts.php
- Iterate $active_contracts stub array
- Color-rail cards per contract:
    GREEN  = on track
    GOLD   = milestone due soon
    RED    = milestone overdue
- Each card: provider avatar + name + service type chip +
  engagement type chip + value (wrapped in .bp-revenue) +
  next milestone info + actions
- Agency only: team member assigned row below milestone
  <?php if ($is_agency): ?> team member <?php endif; ?>
- Actions: "View Contract" always + context action:
    On track → "Message"
    Due soon  → "Submit Milestone" (primary)
    Overdue   → "Submit Now" (primary, red treatment)
- Show realistic stub cards (not empty state) in Wave 1

OPEN PROPOSALS:
- Section header eyebrow "Pipeline" + title
  "Proposals Awaiting Decision" + "View All" → proposals.php
- Iterate $open_proposals stub array
- Proposal cards: provider avatar + service + status chip +
  submitted date + proposed value (.bp-revenue) + actions
- Status chips: Under Review (gold) / Viewed (blue) /
  Pending Response (neutral)
- Actions: "View" + "Withdraw" or "Edit"

NEW JOB MATCHES:
- Section header eyebrow "Marketplace" + title
  "Jobs Matching Your Services" + "Browse All" → find-jobs.php
- 3 compact job cards (lighter than contract cards)
- Each: job title + location + budget (.bp-revenue) +
  engagement type + urgency chip + service tags +
  "Send Proposal" button
- Urgency chip: Urgent (red) only when is_urgent flag set

AGENCY SIDEBAR WIDGETS:
  TEAM CAPACITY (Agency only):
  <?php if ($is_agency): ?>
  - Card: "Team Capacity"
  - Capacity bar: visual fill based on $team_utilization stub
  - Team member list: name + status chip + project count
  - "Manage Team" → team.php
  - Open slots note
  <?php endif; ?>

  AVAILABILITY PANEL (Freelancer only):
  <?php if ($is_freelancer): ?>
  - Card: "My Availability"
  - Current status chip: Available / Busy / Unavailable
  - Next open slot date
  - Hours per week remaining
  - "Update Availability" button → opens modal stub
  <?php endif; ?>

MILESTONES DUE (Both):
- "Upcoming Milestones" card
- 3 items sorted by urgency with colored dot indicators
- Overdue: red dot + "Submit Now" link
- Due soon: gold dot + "Submit" link
- On track: green dot + "View" link
- "View All Milestones" → milestones.php

REVENUE SNAPSHOT (Both):
- "This Month" card
- Invoiced / Received / Outstanding figures
- ALL wrapped in .bp-revenue (blurs with toggle)
- Eye toggle button to reveal/blur this card's figures
  (same toggle as hero — they sync)
- "View Finances" → finances.php

RECENT NOTIFICATIONS (Both):
- Last 4 notifications from $notifications stub
- Same .activity-feed style as Provider
- Colored severity dots: green/blue/gold/red
- "View All Notifications" → notifications.php

MODALS:
- Every modal identified in Step 1 plan block
- .modal-overlay + .modal structure
- openModal() / closeModal() from _shared.js
- No local modal JS

CSS:
- Page-specific <style> block at top of file
- .bp-revenue blur/hide CSS here
- body.bp-revenue-hidden .bp-revenue { filter: blur(5px);
  user-select: none; pointer-events: none; }
- All other tokens use CSS variables — no hardcoded values

JS:
- Page-specific <script> block via $page_extra_foot
- Revenue privacy toggle init + handler
- No global helper redefinitions
- Stub fetch() calls commented out for all other actions:
  // Wire in Wave 2: fetch('/_shared/save_job.php', ...)

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
- Confirm "Find New Jobs" gold button in hero actions
- Confirm all stub variables clearly marked // STUB
- Confirm .bp-revenue CSS blur is defined in page styles
- Confirm revenue privacy toggle reads/writes sessionStorage
- Confirm Agency-only sections wrapped in $is_agency guard
- Confirm Freelancer-only sections wrapped in $is_freelancer guard
- Confirm ALL financial figures wrapped in .bp-revenue
- Confirm every modal uses .modal-overlay structure
- Confirm page_head / header / sidebar / page_foot all included
- Confirm auth gate checks business_partner role
- Confirm $bp_type derived from $current_user['bp_type']
  with ?type= fallback for demo switching

─────────────────────────────────────────────────────────────
DELIVER
─────────────────────────────────────────────────────────────
Files changed:
  biz-portal/biz_dashboard.php    (centralized + designed)

Single file delivery — not zipped unless you prefer zip.
CHANGES note: list every centralization fix applied and every
design violation corrected, section by section.
Note which sections are Agency-only vs Freelancer-only vs Both.
```
