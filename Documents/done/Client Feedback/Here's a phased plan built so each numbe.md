Here's a phased plan built so each numbered item is a **single safe chat** (right-sized to read → surgical edit → lint → verify without running out of room), grouped into **3 waves** by risk. Run them top-to-bottom; never start a structural phase in the same chat as another.

**Standard protocol for every phase (don't repeat it each time):**
1. Search project KB + read the target file's relevant section *before* any edit.
2. Wiring pass first, then design pass — never mixed.
3. `str_replace` only, scoped to the exact lines. No rewrites.
4. Run `php -l` + the pre-flight checklist before delivering.
5. Deliver only the file(s) for that phase + a 1-screen CHANGES note. Stop.

---

## Wave 1 — Low-risk copy & renames (safe to move fast)

| Phase | Files | Scope | Don't touch |
|---|---|---|---|
| **1. Explore & system copy** | `settings.php`, `events.php`, `activity.php`, `news.php` | settings: 2× Professional Will→Continuity Plan · events: sub-copy→"health and well-being professionals" (+Oxford comma) · activity: Emergency→Critical Incident labels/tab, confirm feed is actions-only, add a HIPAA-event tooltip · news: remove Library tab/mode, remove compliance+licensure alert items | Dashboard. (See note: news alerts *move* to dashboard — for now just remove from news, don't duplicate what dashboard already shows.) |
| **2. Marketplace & referrals** | `referrals.php`, `job-postings.php` | referrals: header→Referral Coordination, hero title/sub rewrite, patient→client, remove PHQ-9, remove counter-refer, add self-pay · job-postings: "Post a Job…"→"Request support and connect with business partners", tab→Support & Services, all buttons→Request Support, My Job Postings→My Support Requests | Proposal/contract/BP-side logic |
| **3. Document surfaces** | `important-documents.php`, `vault.php` | imp-docs: header/sub copy, All Agreements→All Documents + agreements/documents split, add "Add Document", sample-forms copy (Continuity Plans) · vault: Add to Emergency Vault→Add to Vault, People With Access→"…granted access during a critical moment", Emergency Document→Sensitive Information, remove pending-signature + expiring-license items, add intro text, Patient List→client | Vault unlock/seal logic, upload wiring |

---

## Wave 2 — Content-heavy & gated (slower, confirm first where noted)

| Phase | Files | Scope | Gate / don't touch |
|---|---|---|---|
| **4. Finances (GATED)** | `finances.php` | Strip escrow (balance/total/pre-funded/funds-held), consolidate payment model to **Retainer / Annual Fee / Retainer + Annual**, "Update Payment Model", remove Agreement Expires + Last Top-up | ⚠️ **Confirm with Carizma** the 3 payment options + that escrow removal is approved (financial copy is on attorney hold). Do **not** touch pricing $ figures. Don't touch Stripe Connect status UI. |
| **5. Overview** | `overview.php` | Add refined key terms (verbatim defs), banner body, Why-Aegis bodies (exact copy), How-to bodies (exact copy), reword existing FAQs, delete pricing FAQ, add **8 new FAQs**, fix CS-invite-email FAQ | ⚠️ Pricing figures ($39/$79 vs $29/$49) are on hold — omit pricing terms or confirm numbers first. Don't touch setup-path logic. |
| **6. Public profile & network labels** | `provider.php`, `network.php` | provider: referral-tip rewrite, **remove Outcome & Performance Metrics + PHQ-9**, genericize EHR, "Clinical Services Offered"→"Services", confirm online/in-person, decide on map · network: Clinical Services→Services labels, pending-requests review copy | Viewer-tier gating, slug resolution |
| **7. Profile editor & dashboard adds** | `edit-profile.php`, `dashboard.php` | edit-profile: "Medical License"→"License", make license # optional, fix custom specialty/service Add (🔎 bug), session-length custom entry, fix Insurance Add (🔎 bug), add credential **archive** · dashboard: add "Vault documentation is complete and accurate" attest line, fix literal ✓ → `aegis_icon('check')` | ⚠️ The two 🔎 items are **runtime bugs**, not copy — fix + actually test the add flow, don't just swap strings. Don't touch dashboard CEU *logging* (it works). |

---

## Wave 3 — Structural reworks (one file per chat, two passes each)

These are the two biggest files. Give each its **own chat**, and split into Pass A (structure) and Pass B (content) so a half-finished structure change never ships.

**Phase 8 — `continuity-stewards.php`**
- *Pass A (structure):* role taxonomy → Primary / Support / **Alternate** (remove Tertiary), rename Set Role→**Role Step-up**, add the **Approved Critical Incidents** step with a per-incident "verification required?" toggle, separate Search-Aegis-Users vs External-Email inputs, add Notify-me section, fix Request-to-be-Released options (remove Conflict of Interest), readiness labels (Patient List→"Sensitive Information & Credentials (Vault)", "Agreement(s) Signed", drop Tertiary).
- *Pass B (content):* replace the **full responsibilities taxonomy** (remove all timeframes), patient→client sweep, invite-external copy, "I'm a steward for" copy, Activate Succession→Activate Continuity Support, add compensation field.
- ⚠️ Removing Tertiary touches schema/seed (`db.php`, `seed.json`, `models.php` ORDER BY) — that's out-of-scope-without-approval. **Confirm before changing the data layer**; UI can hide Tertiary without dropping the column.

**Phase 9 — `support-stewards.php`**
- *Pass A (structure):* banner rewrite, collapse role model to **2 roles** (Support / Alternative Support), rebuild the permission matrix with the 5-section taxonomy + add-task, remove the entire quarterly-review apparatus, "Access Review Due"→"Annual Attestation Due", remove Fill a Gap / Delegate First Task, add Notify-me.
- *Pass B (content):* add the new **"Planning and Guidance for Support Steward Readiness"** tab (3 checklists), swap onboarding-checklist content, simplify Delegate Task modal, Edit SS (Job Title→Relationship, remove Org + role type), Emergency Activation→Activate Continuity Support, patient→client.

---

**Two things to lock before we start:** (1) the payment-model options for Phase 4, and (2) whether you want me to touch the data layer for the Tertiary removal in Phase 8 or just hide it in the UI. Everything in Wave 1 is safe to begin immediately — say the word and I'll open Phase 1.