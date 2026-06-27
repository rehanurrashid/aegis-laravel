# Aegis Email Templates

**69 transactional templates + 3 shared partials**
Generated against `AEGIS_USE_CASES_OUTPUT.md` (commit `bbac3bc`, 419 UCs).

---

## Shared partials

| File | Purpose |
|---|---|
| `_email_head.php` | Shared `<head>` block — include at top of every template |
| `_email_foot.php` | Shared footer row — include inside outer 600px table |
| `_email_wrapper.php` | Structural reference only — not included directly |

---

## Gate key legend

- `[UNGATED]` — always sends; ignores `notify_email` master switch
- All others — send only if `notify_email = true` AND the per-key pref is `true` or unset (default-on)

---

## Trigger map

| # | File | UC trigger | Recipient | Gate |
|---|---|---|---|---|
| 1 | `auth/01-welcome.php` | UC-PRV-001 / UC-CS-001,002 / UC-SS-001 / BP signup | New user | `[UNGATED]` ⚠️ UNWIRED |
| 2 | `auth/02-email-verification.php` | UC-PRV-213 | New user | `[UNGATED]` ⚠️ UNWIRED |
| 3 | `auth/03-password-reset.php` | UC-PRV-005 / UC-ADM-025 | User | `[UNGATED]` |
| 4 | `auth/04-password-changed.php` | UC-PRV-005 (post) | User | `[UNGATED]` |
| 5 | `auth/05-mfa-enabled.php` | UC-PRV-214 | User | `[UNGATED]` |
| 6 | `auth/06-mfa-disabled.php` | Settings MFA off | User | `[UNGATED]` |
| 7 | `auth/07-new-device-login.php` | UC-PRV-002 (A-path) | User | `[UNGATED]` |
| 8 | `auth/08-account-locked.php` | UC-PRV-002 lockout / UC-ADM-023 | User | `[UNGATED]` |
| 9 | `auth/09-account-closure.php` | UC-ADM-027 / self-close | User | `[UNGATED]` |
| 10 | `plan/10-plan-signed.php` | UC-PRV-036 | Practitioner | `notify_plan_change` |
| 11 | `plan/11-plan-ready-cs.php` | UC-PRV-036 / UC-XP-008 | CS | `notify_plan_change` |
| 11 | `plan/11-plan-ready-ss.php` | UC-PRV-036 / UC-XP-008 | SS | `notify_plan_change` |
| 12 | `plan/12-cs-countersigned.php` | UC-CS (countersign) / UC-XP-002 | Practitioner | `notify_plan_change` |
| 13 | `plan/13-vault-attested-cs.php` | UC-PRV-040 / UC-XP-003 | CS | `notify_attestation` |
| 13 | `plan/13-vault-attested-ss.php` | UC-PRV-040 / UC-XP-003 | SS | `notify_attestation` |
| 14 | `plan/14-reattestation-due-30d.php` | UC-PRV-038 / UC-XP-014 | Practitioner | `notify_plan_review` |
| 14 | `plan/14-reattestation-due-7d.php` | UC-PRV-038 / UC-XP-014 | Practitioner | `notify_plan_review` |
| 14 | `plan/14-reattestation-due-0d.php` | UC-PRV-038 / UC-XP-014 | Practitioner | `[UNGATED]` |
| 15 | `plan/15-reattestation-completed.php` | UC-PRV-039 | CS, SS | `notify_attestation` |
| 16 | `plan/16-plan-version-updated.php` | UC-PRV-035 / UC-PRV-192 | CS, SS | `notify_plan_change` |
| 17 | `steward/17-cs-invite-external.php` | UC-PRV-051 | Invitee (new) | `[UNGATED]` |
| 18 | `steward/18-cs-invite-internal.php` | UC-PRV-050 | Existing user | `notify_assignment` |
| 19 | `steward/19-ss-invite-external.php` | UC-PRV (SS ext) | Invitee (new) | `[UNGATED]` |
| 20 | `steward/20-ss-invite-internal.php` | UC-PRV (SS int) | Existing user | `notify_assignment` |
| 21 | `steward/21-cs-accepted.php` | UC-CS-001/025 / UC-XP-017 | Practitioner | `notify_assignment` |
| 22 | `steward/22-cs-declined.php` | UC-CS decline | Practitioner | `notify_assignment` |
| 23 | `steward/23-cs-role-change.php` | UC-CS-023 | Practitioner | `notify_role_change` |
| 24 | `steward/24-cs-removed.php` | UC-PRV-056 | CS | `notify_assignment` |
| 25 | `steward/25-alternate-cs-activated.php` | UC-XP-019 | Practitioner + Alt CS | `notify_activation` |
| 26 | `incident/26-incident-reported.php` | UC-PRV-090 / UC-SS-030 / UC-XP-004 | CS | `[UNGATED]` |
| 27 | `incident/27-incident-verified.php` | UC-CS-041 / UC-XP-005 | SS + Practitioner | `[UNGATED]` |
| 28 | `incident/28-vault-unlocked.php` | UC-XP-006 | CS | `[UNGATED]` |
| 29 | `incident/29-incident-task-assigned.php` | UC-CS-031 | CS | `notify_task` |
| 30 | `incident/30-incident-escalated.php` | Incident escalation | Aegis team | `[UNGATED]` |
| 31 | `incident/31-incident-closed.php` | UC-XP-007 | All parties | `[UNGATED]` |
| 32 | `bp/32-support-request-received.php` | UC-BP (submit) | BP | `notify_proposal` |
| 33 | `bp/33-proposal-accepted.php` | UC-PRV-132 / UC-XP-010 | BP | `notify_proposal` |
| 34 | `bp/34-proposal-declined.php` | UC-PRV-133 | BP | `notify_proposal` |
| 35 | `bp/35-contract-created.php` | UC-XP-010 | BP + Practitioner | `notify_agreement` |
| 36 | `bp/36-milestone-submitted.php` | UC-BP milestone | Practitioner | `notify_assignment` |
| 37 | `bp/37-milestone-approved.php` | UC-PRV-135 | BP | `notify_payment` |
| 38 | `bp/38-invoice-received.php` | UC-XP-011 | Practitioner | `notify_invoice` |
| 39 | `bp/39-invoice-paid.php` | UC-PRV-136 / UC-XP-012 | BP | `notify_payment` |
| 40 | `bp/40-payout-released.php` | UC-ADM-045 / UC-XP-012 | BP | `notify_payment` |
| 41 | `bp/41-team-invite.php` | UC-XP-018 | Invitee | `[UNGATED]` |
| 42 | `network/42-connection-request.php` | UC-PRV-100 | Practitioner | `notify_message` |
| 43 | `network/43-connection-accepted.php` | UC-PRV-101 | Requester | `notify_message` |
| 44 | `network/44-referral-received.php` | UC-PRV-108 | Practitioner | `notify_agreement` |
| 45 | `network/45-referral-responded.php` | UC-PRV-111 | Sender | `notify_agreement` |
| 46 | `support/46-ticket-received.php` | UC-ADM-050 | Submitter | `[UNGATED]` |
| 47 | `support/47-ticket-reply.php` | UC-ADM-053 / UC-XP-015 | Submitter | `[UNGATED]` |
| 48 | `support/48-ticket-resolved.php` | UC-ADM-055 | Submitter | `[UNGATED]` |
| 49 | `support/49-feedback-received.php` | UC-ADM-050 (feedback) | Submitter | `[UNGATED]` |
| 50 | `admin/50-account-action.php` | UC-ADM-023/024/026 | Affected user | `[UNGATED]` |
| 51 | `admin/51-plan-upgraded.php` | UC-PRV-003 | Practitioner | `notify_payment` |
| 52 | `admin/52-plan-downgraded.php` | UC-PRV-004 / UC-XP-020 | Practitioner | `notify_payment` |
| 53 | `admin/53-payment-failed.php` | UC-ADM-041 / billing | Subscriber + admin | `[UNGATED]` |
| 54 | `admin/54-payment-receipt.php` | Billing success | Subscriber | `notify_payment` |
| 55 | `admin/55-renewal-upcoming.php` | Billing scheduler (7d) | Subscriber | `notify_payment` |
| 56 | `digest/56-weekly-digest.php` | Scheduled opt-in | User | `notify_summary` |
| 57 | `digest/57-monthly-summary.php` | Scheduled opt-in | Practitioner | `notify_summary` |
| 58 | `gaps/58-service-inquiry-received.php` | UC-PRV-124 | Practitioner | `notify_message` |
| 59 | `gaps/59-service-inquiry-responded.php` | UC-PRV-124 set_status | Inquirer | `notify_message` |
| 60 | `gaps/60-document-requested.php` | UC-PRV-082 | CS or SS | `notify_assignment` |
| 61 | `gaps/61-document-release-requested.php` | UC-PRV-196 | Holding steward | `notify_assignment` |
| 62 | `gaps/62-document-signature-reminder.php` | UC-PRV-197 | Pending signer | `notify_plan_change` |
| 63 | `gaps/63-vault-item-shared.php` | UC-PRV-200 | Recipient steward | `notify_docs_accessed` |
| 64 | `gaps/64-document-updated.php` | UC-PRV-192/193/194 | CS, SS | `notify_plan_change` |
| 65 | `gaps/65-cs-flagged-unresponsive.php` | UC-XP-016 | Practitioner | `notify_practitioner_cs_unresponsive` |
| 66 | `gaps/66-contract-signed.php` | UC-PRV-137 | BP + Practitioner | `notify_agreement` |
| 67 | `gaps/67-contract-cancelled.php` | UC-PRV-138 | Other party | `notify_agreement` |
| 68 | `gaps/68-subscription-cancelled.php` | UC-PRV-145 | Practitioner | `[UNGATED]` |
| 69 | `gaps/69-maat-addon-change.php` | UC-PRV-210/211 | Practitioner | `notify_payment` ⚠️ STUB |

---

## Implementation notes

- **All templates**: table-based layout, inline CSS only, no `<style>` blocks
- **All `$data[]` output**: wrapped in `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')`
- **`[UNGATED]` templates**: `$data['ungated'] = true` set before `_email_foot.php` include — suppresses unsubscribe link
- **Gated templates**: footer renders unsubscribe link from `$data['unsubscribe_token']`
- **`aegis_icon()`**: available via `require_once __DIR__ . '/../../icons.php'` — use for any icon needed in body
- **`aegis_pricing()`**: available via `require_once __DIR__ . '/../../pricing_data.php'` — use for live tier names/prices (T51, T52)
- **`AEGIS_SITE_URL`**: not globally defined in current build — each template defines it as a fallback constant
- **Role-split variants** (T11, T13): two files per template number — CS and SS receive different copy
- **Cadence variants** (T14): three files — 30d and 7d are gated; 0d is `[UNGATED]`
- **Single-template status variants** (T45, T59, T64, T69): PHP variable drives box color and copy — no duplicate files
- **T1, T2**: marked `[UNWIRED — SIMULATED]` — onboarding.php performs no backend write in current build
- **T69**: marked `[STUB]` — billing event contingent on Stripe Connect setup

---

## File count

- 3 shared partials
- 69 templates (72 files due to role/cadence splits on T11, T13, T14)
- 1 README
- **Total: 76 files**
