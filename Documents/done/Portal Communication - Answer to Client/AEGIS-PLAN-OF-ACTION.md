# Aegis — Plan of Action

**Date:** June 6, 2026
**Baseline:** Provider Portal write-path complete (Waves 1-7 shipped, AES-256 live, schema v13).
**Scope of this POA:** Everything tracked in `AEGIS-PENDING-CONSOLIDATED.md` — sequenced into phases with dependencies, parallel tracks, decision gates, and rough effort.

---

## How to read this document

- **Critical path** = items the *next* phase cannot start without
- **Parallel track** = work that runs alongside the critical path on a separate workstream
- **Decision gate** = a yes/no the client owns; everything downstream of it stalls until decided
- **Effort** is calendar weeks at current team velocity (1 senior dev + design support)

---

## 🟢 Phase 0 — Setup & decisions (Week 0–1)

**Goal:** Get every external dependency unblocked so build phases can actually start.

### Critical path — Dr. Chapman owns these

| # | Item | Action | Time |
|---|---|---|---|
| 0.1 | **AWS account** | MAAT signs up, provides root credentials to Devlet via secure channel | 1 day |
| 0.2 | **AWS BAA** | Execute with AWS (free, downloadable from AWS Artifact) | 1 day |
| 0.3 | **Stripe account** | MAAT signs up, completes business verification | 2-3 days |
| 0.4 | **ESP account** (recommend Amazon SES — already in AWS) | Sign up, verify sending domain (DNS records) | 1-2 days |
| 0.5 | **ESP BAA** | Execute with chosen ESP | 1 day |
| 0.6 | **Helpdesk tool pick** | Choose Freshdesk vs Zendesk vs custom `support@aegis` inbox | 1 day |
| 0.7 | **GA + GTM confirmation** | Approve PHI-exclusion config approach | 1 day |
| 0.8 | **File storage decision** | Confirm "AWS S3 + signed URLs" approach | 1 day |
| 0.9 | **Privacy Policy + BAA copy review** | Review the access-model language already drafted | 1-2 days |

### Parallel track — Devlet delivers in same window

| # | Item | Deliverable |
|---|---|---|
| 0.A | **Integration checklist document** | Full system list with setup steps + BAA status + timelines (promised in June 1 email) |
| 0.B | **Email template designs** | Approve HTML email layouts matching Aegis design system |
| 0.C | **Helpdesk integration spec** | Once 0.6 is decided, draft technical spec |

**Phase 0 exit criterion:** All 9 client items complete + integration checklist delivered. Without these, Phase 1 cannot start cleanly.

---

## 🟡 Phase 1 — Pre-launch essentials (Week 1–4)

**Goal:** Everything an end-user needs on day-one of launch beyond the write-path that's already done.

### Phase 1A — Authentication & support infra (Week 1–2)

| # | Item | Depends on | Effort |
|---|---|---|---|
| 1.1 | **Password reset flow** | 0.4 (ESP) | 3-4 days |
| 1.2 | **ESP integration + transactional email pipeline** | 0.4, 0.5 | 4-5 days |
| 1.3 | **Email templates** (invitations, password reset, incident alerts, digests) | 0.B | 3-4 days |
| 1.4 | **Help ticket submission form** | 0.6 (helpdesk pick) | 2-3 days |

### Phase 1B — Feedback infrastructure (Week 2–3)

| # | Item | Effort |
|---|---|---|
| 1.5 | **Feedback Button** (always-visible, every portal) | 2 days |
| 1.6 | **In-app contextual questionnaires** (post-workflow triggers) | 3-4 days |
| 1.7 | **Open free-form feedback submission** | 1-2 days |
| 1.8 | **Internal feedback dashboard** for MAAT team | 3-4 days |

### Phase 1C — White-glove service workflow (Week 3–4)

This is a MAAT business-model enabler — high priority.

| # | Item | Effort |
|---|---|---|
| 1.9 | **MAAT internal staff role** (excludes Vault) | 2 days |
| 1.10 | **Tracked-changes audit log** (timestamp + identity + field + before/after) | 3-4 days |
| 1.11 | **Practitioner authorization workflow** (notify → review → approve/reject/revise) | 4-5 days |
| 1.12 | **Digital authorization record storage** | 1-2 days |

### Phase 1D — Visibility permissions (Week 3–4, parallel)

| # | Item | Effort |
|---|---|---|
| 1.13 | **Public profile visibility toggle** (respected by public profile reads) | 2 days |
| 1.14 | **Network visibility scope** | 2 days |
| 1.15 | **Per-steward visibility** (standby vs incident-active) | 2-3 days |

**Phase 1 exit criterion:** Launch-blocking essentials complete. System is technically launchable from a Provider-portal perspective.

**Total Phase 1 effort:** ~4 weeks single-developer, less with parallelization.

---

## 🟠 Phase 2 — Payments + file storage integration (Week 4–6)

**Goal:** Real money flow + real file binaries.

| # | Item | Depends on | Effort |
|---|---|---|---|
| 2.1 | **Stripe Connect integration** | 0.3 (Stripe account) | 5-7 days |
| 2.2 | **Practitioner subscription billing** | 2.1 | 3-4 days |
| 2.3 | **CS payout flows** | 2.1 | 3-4 days |
| 2.4 | **Invoice approval → BP visibility** (replaces stubbed flip) | 2.1 | 2 days |
| 2.5 | **AWS S3 wiring** for file binaries | 0.1, 0.2, 0.8 | 4-5 days |
| 2.6 | **Vault file uploads** (real binaries instead of metadata-only) | 2.5 | 3-4 days |
| 2.7 | **Document PDF generation + signed-URL downloads** | 2.5 | 3-4 days |
| 2.8 | **Invoice/CEU certificate PDFs** | 2.5 | 3 days |
| 2.9 | **Settings export to JSON** | 2.5 | 1 day |

**Phase 2 exit criterion:** ~28 file-binary stubs in Provider portal are now real. Stripe is live. The "D-2 file storage" deferral is fully cleared.

**Total Phase 2 effort:** ~2-3 weeks.

---

## 🔵 Phase 3 — Analytics & polish (Week 5–6, parallel to Phase 2)

| # | Item | Depends on | Effort |
|---|---|---|---|
| 3.1 | **Google Analytics + GTM integration** with PHI-exclusion config | 0.7 | 2 days |
| 3.2 | **Provider Portal launch QA pass** | All Phase 1+2 | 3-5 days |
| 3.3 | **Aegis Verified badge / Integrative Network polish** | — | 2 days |

---

## 🟢 LAUNCH READINESS GATE (~Week 6-7)

**At this point Provider Portal is fully production-ready.**
- Write-path complete ✅
- All client decisions actioned ✅
- Auth, support, feedback, white-glove all live ✅
- Real payments + real files ✅
- Analytics live ✅
- BAAs executed ✅

**Decision:** Soft-launch Provider Portal to MAAT pilot practitioners, OR continue holding for other portals?

Recommend **soft-launch** at this gate. Provider portal can run standalone with seeded steward demos; CS/SS portal arrivals enhance but don't gate Provider value.

---

## 🟣 Phase 4 — Continuity Steward Portal (Week 7–13)

**Goal:** CS Portal write-path so CS can actually do their job — verify incidents, execute tasks, certify their plan, manage vault during incidents.

This mirrors the Provider Portal 7-wave structure but scoped to CS.

| Wave | Scope | Effort |
|---|---|---|
| CS-1 | Settings + edit-profile + activity (quick wins) | 1 week |
| CS-2 | `my-tasks.php` — task certification, exception flags | 1 week |
| CS-3 | `continuity-management.php` — the verify cockpit + documentation upload + task generation | 1.5 weeks |
| CS-4 | `vault.php` — gated read-only access during verified incidents | 1 week |
| CS-5 | `important-documents.php` — countersignature flow + plan management | 1 week |
| CS-6 | `providers.php` — caseload, invite-provider, refer-from-roster | 0.5 week |
| CS-7 | Final polish + finances.php + messages.php + close-out | 1 week |

**Phase 4 exit criterion:** CS Portal write-path complete. Carizma's demo flow #1–#4 is now CS-driven too.

**Total Phase 4 effort:** ~6-7 weeks.

---

## 🔷 Phase 5 — Support Steward Portal (Week 13–17)

**Goal:** SS Portal write-path. SS triggers incidents, manages day-to-day tasks, contact attempts.

| Wave | Scope | Effort |
|---|---|---|
| SS-1 | Settings + edit-profile + activity | 0.5 week |
| SS-2 | `my-tasks.php` per-provider | 0.5 week |
| SS-3 | `critical-incident-log.php` — the trigger form with auth gating | 1.5 weeks |
| SS-4 | `providers.php` — caseload | 0.5 week |
| SS-5 | `continuity-stewards.php` (read-only) + "Notify Practitioner — CS Unresponsive" | 0.5 week |
| SS-6 | Messages + important-documents (read-only) + final polish | 0.5 week |

**Total Phase 5 effort:** ~4 weeks.

---

## 🔶 Phase 6 — Business Partner Portal (Week 17–24)

**Goal:** BP Portal — the biggest remaining build. Closes the ~15 cross-portal stubs in Provider.

| Wave | Scope | Effort |
|---|---|---|
| BP-1 | Settings + profile + onboarding (Stripe Connect onboarding for BPs) | 1 week |
| BP-2 | Marketplace — browse jobs, search practitioners, hire requests | 1.5 weeks |
| BP-3 | Proposals — receive job requests, send proposals, contract negotiation | 1.5 weeks |
| BP-4 | Contracts + milestones | 1 week |
| BP-5 | Invoices — submit work, receive payment, dispute | 1 week |
| BP-6 | Messages + activity + final polish | 1 week |

**Phase 6 exit criterion:** All ~15 Provider-side cross-portal stubs are now live two-way flows. Network/marketplace fully functional.

**Total Phase 6 effort:** ~7 weeks.

---

## 🟤 Phase 7 — Post-launch enhancement (parallel from Week 14+ onward)

These are own-workflow features that don't block any portal but add real value.

| # | Item | Effort | Priority |
|---|---|---|---|
| 7.1 | **MFA (Phase 2 promised in email)** | 4-5 days | High — security |
| 7.2 | **OAuth integrations** (Calendar, EHR) per integration | 3-5 days each | Medium |
| 7.3 | **Sessions table + revoke flow** | 3-4 days | Medium |
| 7.4 | **API keys table + revoke flow** | 3-4 days | Medium |
| 7.5 | **Webhooks delivery + test event** | 5-7 days | Medium |
| 7.6 | **Practice transfer workflow** | 1-2 weeks | Low (until first request) |
| 7.7 | **Plan change request flow** | 3-4 days | Tied to Stripe billing |
| 7.8 | **NPI registry live search** | 2-3 days | Low |
| 7.9 | **Pagination loaders** | 2 days per table | Low |
| 7.10 | **NDA attach flow** | 1 week | Low |
| 7.11 | **Dispute Send workflow** | 1.5 weeks | Tied to BP Portal completion |
| 7.12 | **Access Revocation modal** | 1 week | Low |

---

## 📅 Compressed timeline view

```
Week 0–1   │ Phase 0  │ Client setup + integration checklist
Week 1–4   │ Phase 1  │ Auth + support + feedback + white-glove + visibility
Week 4–6   │ Phase 2  │ Stripe Connect + AWS S3 file binaries
Week 5–6   │ Phase 3  │ Analytics + QA  (parallel with Phase 2)
─────────────────────────────────────────────────────────────────
Week 6–7   │ ★ LAUNCH GATE — Provider Portal production-ready
─────────────────────────────────────────────────────────────────
Week 7–13  │ Phase 4  │ CS Portal write-path
Week 13–17 │ Phase 5  │ SS Portal write-path
Week 17–24 │ Phase 6  │ BP Portal write-path  → closes cross-portal stubs
Week 14+   │ Phase 7  │ Post-launch enhancements (rolling)
```

**Total to full multi-portal completion:** ~24 weeks (6 months) at current velocity.
**To Provider Portal launch:** ~6-7 weeks from Day 0.

---

## ⚠️ Risk register

| Risk | Mitigation |
|---|---|
| Client setup (Phase 0) drags — AWS/Stripe verification can take longer than expected | Start verification immediately; use sandbox modes for development in parallel |
| Stripe Connect onboarding for CS payouts adds compliance complexity | Stripe handles KYC — but expect 2–4 days of CS-side onboarding friction per CS |
| AWS BAA limits regions/services | Confirm BAA scope covers RDS/S3/SES before architecting |
| White-glove tracked-changes workflow has UX subtleties (approve-each-field vs batch) | Spec sign-off with Dr. Chapman before build |
| CS Portal verify-flow has documentation-required enforcement that must match Provider portal's `plan_incident_configs.documents_required` | Already wired Provider-side; build CS verify modal against same schema |
| Other portals could surface bugs in Provider write-path that didn't show under demo data | Comprehensive integration test suite mid-Phase 4 |

---

## 🎯 Immediate next steps (this week)

1. **Devlet**: Send integration checklist document to Dr. Chapman
2. **Dr. Chapman**: Start AWS + Stripe account creation
3. **Devlet**: Set up dev AWS account for parallel development
4. **Both**: 30-min sync to confirm helpdesk tool choice + GA + file storage decisions
5. **Devlet**: Begin password reset flow + ESP integration scaffolding (can use AWS sandbox creds)

**Phase 0 should close within 5-7 business days.** Everything downstream depends on it.
