# Aegis — Stripe Products Setup Guide (Test/Sandbox Mode)

> **You are in Sandbox/Test mode.** No real transactions will be processed. All cards and payments are fake.

---

## How to create each product

For every product below, the steps are identical:

1. Go to **dashboard.stripe.com → Product catalog → Add a product**
2. Fill in **Name** and **Description** exactly as shown
3. Under **Pricing** — leave it on **Recurring**
4. Set the **Amount** and **Billing period**
5. Click **Add product**
6. On the product detail page — find the price under the **Pricing** section — copy the ID that looks like `price_1ABC123def456ghi` — paste it into your `.env`

---

## The 10 products to create

### 1. Continuity Access — Monthly

| Field | Value |
|---|---|
| **Name** | `Continuity Access — Monthly` |
| **Description** | `Aegis Practitioner subscription. Essential continuity for solo practitioners. 1 Continuity Steward, 1 Support Steward, full Vault, all 7 incident types.` |
| **Amount** | `29.00` |
| **Billing period** | `Monthly` |
| **`.env` key** | `STRIPE_PRICE_ACCESS_MONTHLY` |

---

### 2. Continuity Access — Annual

| Field | Value |
|---|---|
| **Name** | `Continuity Access — Annual` |
| **Description** | `Aegis Practitioner subscription, billed annually. $276/year (equivalent to $23/mo). Save 20% vs monthly.` |
| **Amount** | `276.00` |
| **Billing period** | `Yearly` |
| **`.env` key** | `STRIPE_PRICE_ACCESS_ANNUAL` |

---

### 3. Continuity Practice — Monthly

| Field | Value |
|---|---|
| **Name** | `Continuity Practice — Monthly` |
| **Description** | `Aegis Practitioner subscription. Full toolkit — 2 CS, 4 SS, Referrals, Integrative Network, Job Postings, Services Mode, priority support.` |
| **Amount** | `49.00` |
| **Billing period** | `Monthly` |
| **`.env` key** | `STRIPE_PRICE_PRACTICE_MONTHLY` |

---

### 4. Continuity Practice — Annual

| Field | Value |
|---|---|
| **Name** | `Continuity Practice — Annual` |
| **Description** | `Aegis Practitioner subscription, billed annually. $468/year (equivalent to $39/mo). Save 20% vs monthly.` |
| **Amount** | `468.00` |
| **Billing period** | `Yearly` |
| **`.env` key** | `STRIPE_PRICE_PRACTICE_ANNUAL` |

---

### 5. Business Partner — Monthly

| Field | Value |
|---|---|
| **Name** | `Business Partner — Monthly` |
| **Description** | `Aegis Business Partner subscription. Public directory listing, browse job postings, send proposals, milestone contracts, Stripe Connect payouts. Covers both Agency and Freelancer account types.` |
| **Amount** | `69.00` |
| **Billing period** | `Monthly` |
| **`.env` key** | `STRIPE_PRICE_BP_MONTHLY` |

---

### 6. Business Partner — Annual

| Field | Value |
|---|---|
| **Name** | `Business Partner — Annual` |
| **Description** | `Aegis Business Partner subscription, billed annually. $690/year — save 2 months vs monthly billing.` |
| **Amount** | `690.00` |
| **Billing period** | `Yearly` |
| **`.env` key** | `STRIPE_PRICE_BP_ANNUAL` |

---

### 7. Business CS — Monthly

| Field | Value |
|---|---|
| **Name** | `Business CS — Monthly` |
| **Description** | `Aegis Business Continuity Steward subscription. Independent paid CS account. Serve 2–40 practitioners, public profile at /steward/<slug>, Stripe Connect payouts.` |
| **Amount** | `49.00` |
| **Billing period** | `Monthly` |
| **`.env` key** | `STRIPE_PRICE_CS_BUSINESS_MONTHLY` |

---

### 8. Business CS — Annual

| Field | Value |
|---|---|
| **Name** | `Business CS — Annual` |
| **Description** | `Aegis Business CS subscription, billed annually. $429/year — save ~27% vs monthly.` |
| **Amount** | `429.00` |
| **Billing period** | `Yearly` |
| **`.env` key** | `STRIPE_PRICE_CS_BUSINESS_ANNUAL` |

---

### 9. MAAT Professional CS Service — Monthly

| Field | Value |
|---|---|
| **Name** | `MAAT Professional CS Service — Monthly` |
| **Description** | `Add-on for Continuity Practice subscribers only. Designates a MAAT-certified, licensed, insured Continuity Steward to your practice. Emergency response within 4 hours. Requires active Continuity Practice base subscription.` |
| **Amount** | `29.00` |
| **Billing period** | `Monthly` |
| **`.env` key** | `STRIPE_PRICE_MAAT_MONTHLY` |

---

### 10. MAAT Professional CS Service — Annual

| Field | Value |
|---|---|
| **Name** | `MAAT Professional CS Service — Annual` |
| **Description** | `Add-on for Continuity Practice subscribers only, billed annually. $276/year (equivalent to $23/mo). Save 20% vs monthly.` |
| **Amount** | `276.00` |
| **Billing period** | `Yearly` |
| **`.env` key** | `STRIPE_PRICE_MAAT_ANNUAL` |

---

## `.env` after all 10 are created

Replace each `price_xxxxxxxxxxxxxxxx` with the real ID copied from Stripe Dashboard.

```dotenv
# ── Stripe platform keys (from Stripe Dashboard → Developers → API keys) ─────
STRIPE_KEY=pk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxx
STRIPE_SECRET=sk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxxxxxxxxxx

# ── Practitioner plan prices ──────────────────────────────────────────────────
STRIPE_PRICE_ACCESS_MONTHLY=price_xxxxxxxxxxxxxxxx
STRIPE_PRICE_ACCESS_ANNUAL=price_xxxxxxxxxxxxxxxx
STRIPE_PRICE_PRACTICE_MONTHLY=price_xxxxxxxxxxxxxxxx
STRIPE_PRICE_PRACTICE_ANNUAL=price_xxxxxxxxxxxxxxxx

# ── MAAT add-on prices (requires Continuity Practice) ────────────────────────
STRIPE_PRICE_MAAT_MONTHLY=price_xxxxxxxxxxxxxxxx
STRIPE_PRICE_MAAT_ANNUAL=price_xxxxxxxxxxxxxxxx

# ── Business Partner prices ───────────────────────────────────────────────────
STRIPE_PRICE_BP_MONTHLY=price_xxxxxxxxxxxxxxxx
STRIPE_PRICE_BP_ANNUAL=price_xxxxxxxxxxxxxxxx

# ── Business CS prices ────────────────────────────────────────────────────────
STRIPE_PRICE_CS_BUSINESS_MONTHLY=price_xxxxxxxxxxxxxxxx
STRIPE_PRICE_CS_BUSINESS_ANNUAL=price_xxxxxxxxxxxxxxxx
```

After saving `.env` run:

```bash
php artisan config:clear
php artisan queue:restart
```

---

## Test cards (Sandbox only)

All test cards use any future expiry (e.g. `12/29`), any CVV (e.g. `123`), any ZIP (e.g. `12345`).

| Card number | What it simulates |
|---|---|
| `4242 4242 4242 4242` | Payment succeeds ✅ |
| `4000 0000 0000 0002` | Card declined ❌ |
| `4000 0025 0000 3155` | 3D Secure authentication required |
| `4000 0000 0000 9995` | Insufficient funds |
| `4000 0000 0000 0069` | Expired card |
| `4000 0000 0000 0127` | Incorrect CVC |

---

## Why `price_xxx` appears in the code

Stripe generates a unique price ID (`price_1ABC123...`) for each price when you create it in the dashboard. These IDs are specific to your Stripe account — there is no way to know them before you create them. So `price_xxx` in the code is a placeholder meaning *"replace this with the real ID from your dashboard."*

Once the real IDs are in `.env`, the `config/aegis.php` `stripe_price_to_tier` map uses them to translate Stripe's price ID back to the internal tier name (`access`, `practice`, etc.) every time a webhook fires. Without the real IDs in `.env`, the tier sync from webhooks will silently do nothing.

---

## Webhook setup (required for subscription sync)

After creating products, register the webhook endpoint in Stripe so subscription events reach Aegis:

1. Go to **Stripe Dashboard → Developers → Webhooks → Add endpoint**
2. **Endpoint URL:** `https://aegis.devlet.tech/stripe/webhook`
3. **Events to listen for** — add all of these:

```
invoice.payment_succeeded
invoice.payment_failed
customer.subscription.created
customer.subscription.updated
customer.subscription.deleted
payment_intent.succeeded
payment_intent.payment_failed
account.updated
charge.refunded
transfer.paid
transfer.failed
```

4. Click **Add endpoint** — copy the **Signing secret** (`whsec_...`) → add to `.env` as `STRIPE_WEBHOOK_SECRET`

For **local development**, use the Stripe CLI to forward webhooks:

```bash
./stripe listen --forward-to localhost:8000/stripe/webhook
```

The CLI prints a temporary `whsec_...` key — set that as `STRIPE_WEBHOOK_SECRET` in your local `.env` while the CLI is running.
