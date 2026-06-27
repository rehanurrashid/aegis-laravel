# Aegis — Third-Party Systems Setup Guide

**Prepared for:** Dr. Carizma Chapman, MAAT Practice Firm
**Prepared by:** Devlet LLC
**Date:** June 6, 2026
**Document type:** Pre-launch integration checklist

---

## Why this document exists

Aegis is built on a small number of well-established external services. Before launch, MAAT needs to create accounts with these services, execute the required HIPAA agreements, and securely share credentials with Devlet so we can integrate them into Aegis.

This document walks you through each one — what it does, why we need it, how to set it up, and what to send to me when it's ready.

Everything below was previewed in my June 1 email response. This is the formal walkthrough.

---

## At-a-glance overview

| # | System | What it does | BAA required? | Estimated time | Estimated cost |
|---|---|---|---|---|---|
| 1 | **Amazon Web Services (AWS)** | Hosting + file storage | ✅ Yes | 1–2 hours setup | ~$50–150/month at launch |
| 2 | **Stripe** | Subscription payments + CS payouts | ❌ Not required* | 30 min + 2-3 days verification | 2.9% + 30¢ per transaction |
| 3 | **Amazon SES** (Email Service) | Transactional emails | ✅ Yes | 1–2 hours + DNS wait | ~$1 per 10,000 emails |
| 4 | **Google Analytics + Tag Manager** | Usage analytics + ad tracking | ❌ No (with PHI exclusion) | 30 min | Free |
| 5 | **Helpdesk tool** (Freshdesk, Zendesk, or custom) | Support ticket management | Depends on choice | 1 hour | $0–25/month |

*Stripe doesn't process clinical data, so no BAA is needed for payments. Confirmed in our email exchange.

**Total estimated setup time on your side: 4-6 hours active work + 2-3 days waiting on verifications.**

---

## 1. Amazon Web Services (AWS)

### What it does

AWS is where Aegis lives — the application servers, the database, and (Phase 2) the encrypted file storage for vault uploads, document PDFs, invoices, and certificates.

### Why AWS specifically

- HIPAA-eligible with a standard, free BAA
- Native encryption at rest (matches the AES-256 we're already using in the application layer)
- Same vendor for hosting AND email (Item 3) — simpler relationship, one bill, one BAA
- Industry standard — most healthcare platforms use AWS

### Step-by-step setup

**Step 1.** Go to **https://aws.amazon.com/** and click **"Create an AWS Account"**.

**Step 2.** Use your MAAT business email (e.g. dr.chapman@maatpracticefirm.com). This becomes the **root account** — protect the password carefully and enable MFA on it immediately.

**Step 3.** Choose the **"Business" account type** during signup.

**Step 4.** Add a payment method. AWS bills monthly based on usage. At launch volumes you should expect **$50–150/month**; we'll provide a more precise estimate after the first month of live traffic.

**Step 5.** Once the account is created, **enable MFA on the root user** immediately. AWS prompts you to do this. Use Google Authenticator or Authy on your phone.

**Step 6.** Create an **IAM (Identity and Access Management) user** for Devlet rather than sharing your root credentials:
- Go to IAM in the AWS Console
- Create user: name it `devlet-deploy`
- Attach policy: **`AdministratorAccess`** (this can be tightened later once everything is wired)
- Generate **access keys** (you'll get a Key ID and Secret Access Key)
- Send these two keys to me via the secure channel described at the bottom of this document

**Step 7.** Execute the AWS BAA:
- Sign in to **AWS Artifact** (https://aws.amazon.com/artifact/)
- Go to **"Agreements" → "AWS agreements"**
- Find the **"AWS Business Associate Addendum"**
- Click **"Download Agreement"**, fill in MAAT's details, and **"Accept Agreement"**
- This is free and self-service — no negotiation needed

### What to send me

1. The IAM user's **Access Key ID** + **Secret Access Key**
2. Confirmation that the BAA has been executed (a screenshot or download of the signed agreement is ideal)
3. The AWS region you'd like to use — I recommend **`us-east-1`** (Virginia) for lowest cost and best HIPAA service coverage

### Estimated time on your side: 1-2 hours

---

## 2. Stripe

### What it does

Stripe handles two flows in Aegis:
- **Practitioner subscriptions** — recurring monthly/annual billing for the Aegis subscription tiers
- **Continuity Steward payouts** — paying CSes directly from practitioners via Stripe Connect (Aegis does not hold escrow)

### Step-by-step setup

**Step 1.** Go to **https://stripe.com/** and click **"Start now"**.

**Step 2.** Sign up with your MAAT business email and create the account.

**Step 3.** Complete business verification:
- Business type: **LLC** (or whatever MAAT's actual entity is)
- Tax ID / EIN
- Bank account for receiving funds (deposits go here)
- Identity verification (Carizma's ID + a verification selfie)

Stripe verification typically takes **2-3 business days** — start this early.

**Step 4.** Once verified, enable **Stripe Connect** in your dashboard:
- Go to **Settings → Connect settings**
- Choose **"Standard accounts"** for Continuity Stewards (each CS will onboard their own Stripe account once they're ready to receive payouts)

**Step 5.** Generate API keys for Devlet:
- Go to **Developers → API keys**
- You'll see a **Publishable key** (`pk_live_...`) and a **Secret key** (`sk_live_...`)
- **Also enable "Test mode"** in the dashboard and grab the test keys (`pk_test_...` and `sk_test_...`) — we use those during development

**Step 6.** Configure **webhook endpoint** (I'll provide the exact URL once hosting is up):
- Go to **Developers → Webhooks**
- I'll send you the endpoint URL to add when we're ready

### What to send me

1. **Test mode**: Publishable key + Secret key (start with these so we can build safely)
2. **Live mode**: Publishable key + Secret key (sent only when ready to go live)
3. Confirmation that **Stripe Connect is enabled**
4. The webhook signing secret (once we add the endpoint together)

### BAA status

**Not required.** Stripe processes payment info only, never clinical data. Aegis sends Stripe a customer ID + a charge amount — never patient names, conditions, or clinical records.

### Estimated time on your side: 30 min active + 2-3 days verification

---

## 3. Amazon SES (Email Service)

### What it does

Sends all transactional emails from Aegis:
- Account invitations (when you invite a practitioner or steward to join)
- Password reset links
- Critical Incident alerts to stewards
- Daily/weekly digest emails
- Help ticket confirmations
- Feedback acknowledgments

### Why Amazon SES specifically

- We already have AWS for hosting — same vendor, one bill
- Covered under the same AWS BAA you executed in Item 1
- ~$1 per 10,000 emails — by far the cheapest option
- High deliverability (Gmail, Outlook, Apple Mail all trust Amazon's IP ranges)

### Step-by-step setup

**Step 1.** In your AWS Console (from Item 1), search **"SES"** in the top bar and open Amazon SES.

**Step 2.** **Verify your sending domain.** This is the key step:
- Click **"Verified identities" → "Create identity"**
- Choose **"Domain"**
- Enter your domain (e.g. `maatpracticefirm.com` — or `aegis.maatpracticefirm.com` if you want a subdomain for transactional emails)
- AWS will give you a set of **DNS records** (3-4 TXT/CNAME records) to add
- I can help you add these to your DNS provider (GoDaddy, Cloudflare, wherever your domain is registered) — send me the records and I'll walk you through it
- Wait 1-24 hours for DNS to propagate; AWS auto-verifies

**Step 3.** **Request production access:**
- New SES accounts start in **"sandbox mode"** which only allows sending to verified email addresses
- Click **"Account dashboard" → "Request production access"**
- Fill out the form: use case = "Transactional emails for a HIPAA-compliant healthcare platform"
- Estimated daily volume: start with 50,000/day (over-estimate is fine; AWS approves quickly)
- This usually approves within 24 hours

**Step 4.** **Confirm BAA covers SES.** The AWS BAA you executed in Item 1 automatically covers SES — no separate action needed. Confirmation page: https://aws.amazon.com/compliance/hipaa-eligible-services-reference/

**Step 5.** Send Devlet the credentials:
- Create an IAM user named `devlet-ses` (similar to Item 1, Step 6)
- Attach policy: **`AmazonSESFullAccess`**
- Send the Access Key ID + Secret Access Key

### What to send me

1. The sending domain you've verified (e.g. `noreply@aegis.maatpracticefirm.com`)
2. The DNS records AWS gives you — I'll help you add them
3. The IAM user keys for SES
4. Confirmation that production access has been granted

### Estimated time on your side: 1-2 hours + DNS propagation wait

---

## 4. Google Analytics + Google Tag Manager

### What it does

- Tracks page views, session duration, button clicks, drop-off points
- Measures onboarding completion rates
- Monitors Google Ad campaign traffic
- Helps us iterate on the platform based on real usage patterns

### Critical: PHI exclusion

Google Analytics is configured to **never receive PHI**. We track:
- ✅ Page views, button clicks, session duration, referral sources, device types
- ❌ Patient names, conditions, practitioner-clinical-identifiers, vault contents

This is standard practice for healthcare SaaS and the configuration I'll set up handles this automatically.

### Step-by-step setup

**Step 1.** Go to **https://analytics.google.com/** and sign in with a Google account.

**Step 2.** Create a new **GA4 property**:
- Account name: `MAAT Practice Firm`
- Property name: `Aegis Production`
- Reporting time zone: US/Eastern
- Currency: USD

**Step 3.** Create a **Web data stream**:
- URL: your Aegis production domain (we'll send you this when hosting is up)
- Stream name: `Aegis Web`
- Copy the **Measurement ID** (looks like `G-XXXXXXXXXX`)

**Step 4.** Create a **Google Tag Manager container**:
- Go to **https://tagmanager.google.com/**
- Create container: target = Web
- Container name: `Aegis`
- Copy the **GTM ID** (looks like `GTM-XXXXXXX`)

**Step 5.** **Add me as a user** on both accounts:
- Analytics: Admin → Property Access Management → Add user (Editor role)
- Tag Manager: Admin → User Management → Add user (Edit + Publish)
- Email me at: `rehan@devlet.tech`

### What to send me

1. The **GA4 Measurement ID** (`G-XXXXXXXXXX`)
2. The **GTM Container ID** (`GTM-XXXXXXX`)
3. Confirmation that I've been added as an Editor on both

### Cost

**Free.** GA and GTM are free for standard healthcare-scale usage.

### Estimated time on your side: 30 minutes

---

## 5. Helpdesk Tool (Pick One)

You committed to supporting practitioners via a help ticket system. There are three viable approaches — pick whichever feels right:

### Option A — Freshdesk (recommended for ease)

- Clean UI, easy to manage
- **Free** Sprout plan supports up to 10 agents
- HIPAA Compliance available on paid plans ($35/agent/month) — sign BAA with Freshworks
- Best if MAAT plans to grow the support team
- Sign up: **https://www.freshworks.com/freshdesk/**

### Option B — Zendesk

- More mature feature set, more enterprise
- Starting at **$55/agent/month**
- Strong HIPAA + BAA support
- Sign up: **https://www.zendesk.com/**

### Option C — Custom: Just `support@aegis.maatpracticefirm.com`

- Lowest cost (free — just an email forwarder)
- Tickets land in a shared Gmail/Outlook inbox
- Simpler at first; can migrate to Freshdesk/Zendesk later if volume grows
- No BAA needed (Google Workspace or Microsoft 365 BAA covers it if you're already on either)

### My recommendation

Start with **Option C (custom `support@` inbox)** for launch — lowest cost, fastest to set up. Once monthly ticket volume exceeds ~30, migrate to Option A (Freshdesk Sprout free tier).

### What to send me

Once you've decided:
- **Option A or B:** Account credentials (API key + base URL) + BAA confirmation
- **Option C:** The chosen email address (e.g. `support@maatpracticefirm.com`) — I'll wire the in-app submission form to route there

### Estimated time on your side: 1 hour

---

## 6. Secure credential handover

API keys, IAM credentials, and Stripe secret keys are sensitive. **Do not send any of these via plain email.**

### Recommended channels (pick one)

**Option 1 — 1Password Shared Vault** (preferred)
- I can set up a shared vault for "Aegis Production" credentials
- One-time invitation, encrypted end-to-end
- Persists for the lifetime of the project — useful when you onboard new MAAT team members later

**Option 2 — Encrypted message via Signal**
- Both Signal apps installed, end-to-end encrypted
- One-time use per credential

**Option 3 — Phone call**
- I read them back to you for verification
- Slow but works for one or two keys

**Option 4 — Encrypted PDF**
- You send a password-protected PDF with the credentials
- We exchange the PDF password via phone

Whichever you pick — let me know and I'll set up my side accordingly.

### What NOT to do

- ❌ Plain email
- ❌ Slack DM
- ❌ SMS
- ❌ Saved in a Google Doc (even private ones)

---

## 7. Timeline & checklist

Here's the recommended order, designed to maximize parallelization. AWS and Stripe verification take the longest, so start those first.

### Day 1 (today)

- [ ] Start AWS account creation (Item 1, Steps 1-5)
- [ ] Start Stripe account creation (Item 2, Steps 1-3) — this kicks off the 2-3 day verification clock
- [ ] Decide on helpdesk option (Item 5) — can be revisited later if you change your mind

### Day 2-3

- [ ] AWS account active → execute AWS BAA (Item 1, Step 7)
- [ ] AWS account active → create IAM user for Devlet (Item 1, Step 6)
- [ ] Create Google Analytics + GTM accounts (Item 4)
- [ ] Pick helpdesk option and create account if needed (Item 5)

### Day 3-5 (while Stripe is verifying)

- [ ] Set up Amazon SES domain verification (Item 3, Steps 1-2) — start the DNS clock
- [ ] Request SES production access (Item 3, Step 3)
- [ ] Send Devlet all credentials gathered so far via the secure channel

### Day 5-7

- [ ] Stripe verification completes → grab API keys + send to Devlet (Item 2, Step 5)
- [ ] SES production access granted → confirm
- [ ] Devlet begins integration work
- [ ] Final sync call to confirm everything is in place

**Target: All third-party access fully set up within 1 week of this document.**

---

## 8. Cost summary (monthly estimates at launch)

| System | Estimated cost |
|---|---|
| AWS (hosting + database + S3 storage) | $50–150/month |
| Stripe | 2.9% + 30¢ per transaction (passed through in pricing) |
| Amazon SES | ~$1 per 10,000 emails (~$5–20/month at launch volumes) |
| Google Analytics + Tag Manager | Free |
| Helpdesk (if Freshdesk Sprout) | Free (up to 10 agents) |
| **Total fixed infrastructure cost** | **~$55–170/month** |

These are launch-time estimates. They scale with usage but stay modest until you're well past initial growth.

---

## 9. What happens after this document is complete

Once all credentials are in my hands and BAAs are executed:

1. **Week 1:** Devlet wires up AWS deployment, Stripe Connect, and SES transactional emails
2. **Week 2-3:** Devlet builds password reset, help ticket form, feedback channels, white-glove workflow, visibility controls
3. **Week 4-6:** Stripe payment flows go live; AWS S3 file uploads replace the metadata-only placeholders
4. **Week 6-7:** Provider Portal soft-launch to pilot practitioners

This is exactly the **Phase 0 → Phase 1 → Phase 2** schedule from the Plan of Action document.

---

## Questions or stuck on a step?

Call or text any time:
- **Phone:** (945) 387-4196
- **Email:** rehan@devlet.tech
- **Calendly:** https://calendly.com/devlet/30min

For anything credential-related, please use phone or the secure channel set up in Section 6 — not email.

---

**Document version:** 1.0
**Next revision:** After AWS + Stripe accounts are active (we may refine specifics)
