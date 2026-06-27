# Aegis — Consolidated Pending Items

**As of:** June 6, 2026
**Sources reconciled:**
- Original `aegis-provider-portal-communication-audit.md` — write-path gaps
- Previous `PENDING-ITEMS.md` — internal tracker
- Dr. Chapman's email thread (May 31 → Jun 2) — explicit client commitments

**Provider Portal write-path:** ✅ Complete (Waves 1-7 + AES-256 + schema v13 + post-wave polish)
**Original audit findings:** ✅ Fully resolved (re-validated June 6)

---

## 🔴 A. Items requiring Dr. Chapman's action

These need MAAT-side setup or a written client decision before Devlet can proceed.

| Item | What's needed | Unblocks |
|---|---|---|
| **AWS account creation** | Sign up + provide root credentials to Devlet | Hosting, file binary storage, BAA execution |
| **Stripe account setup** | Sign up + Stripe Connect onboarding | Practitioner subscriptions + CS payouts + invoice flows |
| **Email Service Provider account** (Amazon SES or SendGrid — recommend SES since AWS is already in scope) | Sign up + verify sending domain | Transactional emails: password resets, invitations, incident alerts, digest emails |
| **Execute BAA with AWS** | AWS provides standard BAA — needs to be signed | HIPAA-compliant data hosting |
| **Execute BAA with ESP** | Both SES and SendGrid offer BAAs | HIPAA-compliant incident-alert emails |
| **File storage approach decision** | Confirm "AWS S3 + signed URLs" is the path | ~28 file-binary stubs across vault downloads, document PDFs, invoice PDFs, CEU certificates, settings export |
| **Google Analytics — final go-ahead** | Confirm GA + GTM is OK with the PHI-exclusion config described in the email | Analytics, Google Ad traffic tracking |
| **Vault Privacy Policy + BAA copy review** | Review the access-model language from the email (practitioner + CS-after-incident only) | Public-facing legal copy |
| **Help desk tool decision** | Pick Freshdesk vs Zendesk vs custom inbox at `support@aegis` | Help ticketing implementation path |

---

## 🟡 B. Items Devlet will build (committed in the email response)

These are explicit deliverables I committed to in the June 1 email.

### B1. Authentication & onboarding

| Item | Detail |
|---|---|
| **Password reset flow** | Secure email-link reset, part of standard auth flow before launch |
| **Multi-factor authentication (MFA)** | Promised for Phase 2 — not blocking launch |

### B2. Support & feedback infrastructure

| Item | Detail |
|---|---|
| **Help ticket submission** | Embedded support form routing to chosen helpdesk tool (pending A: client decision on Freshdesk/Zendesk/custom) |
| **Feedback Button** (channel 1 of 3) | Always-visible button in every portal |
| **In-app contextual questionnaires** (channel 2 of 3) | Trigger after key workflows complete for the first time |
| **Open free-form feedback submission** (channel 3 of 3) | Unprompted user-initiated submission |
| **Internal feedback dashboard** | MAAT team admin view aggregating all 3 channels |

### B3. White-glove service workflow (MAAT editing practitioner profiles)

| Item | Detail |
|---|---|
| **MAAT staff role** | New internal role permitting profile edits on behalf of a practitioner — explicitly EXCLUDES the Vault |
| **Tracked-changes audit log** | Every edit logged with timestamp, MAAT staff identity, field changed, before/after values |
| **Practitioner authorization workflow** | Practitioner notified of pending changes → reviews → approves/rejects/requests revision — only authorized changes finalize |
| **Digital authorization record** | Practitioner's approval stored as a structured audit row |

### B4. Visibility permissions (controllable from Settings)

| Item | Detail |
|---|---|
| **Public profile visibility** | On/off for anyone outside Aegis |
| **Network visibility** | Restrict to network members or open |
| **Per-steward visibility** | What CS / SS can see during standby vs. active incident |

Most underlying toggle wiring exists in `settings.php`; needs the actual visibility-respecting reads on public profile pages.

### B5. Integration with Email Service Provider

| Item | Detail |
|---|---|
| **Transactional email pipeline** | Once ESP is set up, wire account/email flows: invitations, password resets, incident alerts, digest emails |
| **Template system** | HTML email templates matching Aegis design system |

### B6. Integration checklist document

| Item | Detail |
|---|---|
| **Detailed integration checklist** | Promised separate deliverable to Dr. Chapman covering each system (AWS, Stripe, ESP, GA), specific setup steps, BAA status flags, and timelines |

---

## 🟠 C. Workflows that need their own wave

These are own-feature efforts, not Provider Portal write-path gaps.

| Item | Notes |
|---|---|
| **OAuth integrations** (settings.php Connect buttons) | Per-integration OAuth setup — Calendar, EHR, etc. |
| **Sessions table + revoke flow** | settings.php "Active Sessions" — needs new `user_sessions` schema |
| **API keys table + revoke flow** | settings.php "API Keys" — needs new `user_api_keys` schema |
| **Webhooks delivery + test event** | Needs `user_webhooks` schema + delivery worker |
| **Practice transfer workflow** | settings.php "Initiate Transfer" — multi-step own workflow |
| **Plan change request** | settings.php "Change Plan" — billing-side flow tied to Stripe |

---

## 🔵 D. Other portals (separate multi-wave efforts)

| Portal | Status | Scope |
|---|---|---|
| **CS Portal write-path** | Not started | Fresh project `/continuity-steward-portal/` — its own 4-6 wave effort |
| **SS Portal write-path** | Not started | Fresh project `/support-steward-portal/` — its own 3-5 wave effort |
| **BP Portal write-path** | Not started | Fresh project `/biz-portal/` — its own 5-7 wave effort. Closes the ~15 cross-portal stubs below. |

---

## 🟣 E. Cross-portal stubs (waiting on other portals)

These ~15 stubs sit in Provider pages but the OTHER portal needs to originate the action.

| Stub | Blocked on |
|---|---|
| `bpHireModal` Confirm Engagement | BP Portal write-path |
| `bpProposeContractModal` Send Proposal | BP Portal write-path |
| `bpRequestQuoteModal` Send Request | BP Portal write-path |
| `bpScheduleModal` Send Request | BP Portal write-path |
| `sbpHireModal` Send Hire Request | BP Portal write-path |
| `sbpPostJobModal` Post Job to marketplace | BP Portal marketplace receive |
| `inviteProviderModal` cross-network invite | Recipient practitioner portal handshake |
| `scheduleCallModal` Schedule Call | Cross-portal calendar integration |
| `importContactsModal` Import | CSV processor — own feature |

---

## ⚪ F. Nice-to-haves (deferred for cause)

| Item | Why deferred |
|---|---|
| Pagination loaders ("Loading page 2...") | Server-side paging not implemented for these tables |
| NPI registry live search | External API integration |
| Edit Draft inline editor (important-documents.php) | Drafts created only via wizard for now |
| NDA attach Send (important-documents.php) | Cross-feature with vault sharing — consolidated NDA flow needed |
| Dispute Send (important-documents.php) | Dispute is its own workflow with status transitions |
| Access Revocation modal (important-documents.php) | Per-agreement access model needed (separate from vault access) |
| Generic Add Document modal | Overlaps with vault.php add_item — decide ownership |

---

## ✅ Resolved (recorded for completeness)

| Item | Resolution |
|---|---|
| **Vault encryption at rest** | ✅ AES-256-GCM implemented June 6 — full envelope with IV, auth tag, version byte |
| **Annual review schema columns** | ✅ `last_review_at` + `annual_review_notes` promoted to first-class in `continuity_plans` (schema v13) |
| **Provider Portal write-path** (Wave 1-7) | ✅ 89 stubs wired across 15 pages + 13 endpoints + 71 helpers |
| **Cross-portal communication backbone** | ✅ 21 helpers fan out to CS/SS/BP feeds; same shared SQLite tables read by all portals |
| **`critical_incidents` seed gap** (original audit §4) | ✅ 2 incidents + 9 incident_tasks now seeded for `p_sarah` |
| **Encryption in transit** | ✅ TLS 1.3 — infra-level, confirmed in email |
| **Role-based access** | ✅ All 5 portals enforce role-scoped routes |
| **Aegis Verified badge / Integrative Network discovery** | ✅ Already implemented — search/filter on `network.php` |

---

## 📊 Summary

| Category | Count |
|---|---|
| 🔴 A. Client (MAAT) action items | 9 |
| 🟡 B. Devlet build commitments from email | ~16 across 6 sub-areas |
| 🟠 C. Own-workflow features | 6 |
| 🔵 D. Other portals to build | 3 |
| 🟣 E. Cross-portal stubs (waiting on D) | ~9 workflows / ~15 stubs |
| ⚪ F. Nice-to-haves | 7 |

---

## 🎯 Recommended action order

1. **Dr. Chapman creates AWS, Stripe, and ESP accounts** (Section A) — these are gate items
2. **Devlet delivers integration checklist document** (Section B6) — consolidates all timelines
3. **BAA execution in parallel** with #1 — non-blocking
4. **Build password reset + help ticket + feedback channels** (Section B1, B2) — pre-launch essentials
5. **Build white-glove tracked-changes workflow** (Section B3) — MAAT business model enabler
6. **ESP integration + transactional email pipeline** (Section B5) — depends on #1
7. **Stripe Connect integration** (depends on #1) — payments live
8. **Begin CS Portal write-path** (Section D) — second-biggest backbone after Provider
9. **MFA + visibility-respecting profile reads** — Phase 2 / launch-week polish

**Provider Portal itself is launch-ready.** Everything above is either client-side setup, new feature work, or other portals.