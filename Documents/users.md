All seed users share the same password. Here's the full list:

**Password (all users): `Demo1234!`**

| ID | Email | Role | Notes |
|---|---|---|---|
| `p_sarah` | `sarah@demo.aegis` | Practitioner | Practice tier, 2FA enabled , services mode |
| `p_david` | `david@demo.aegis` | Practitioner | Access tier |
| `p_maria` | `maria@demo.aegis` | Practitioner | Practice tier, services mode |
| `p_access_only` | `jordan@demo.aegis` | Practitioner | Access tier, unverified |
| `p_locked` | `alex@demo.aegis` | Practitioner | **Account locked** — won't login |
| `p_deactivated` | `sam@demo.aegis` | Practitioner | **Deactivated** — won't login |
| `cs_marcus` | `marcus@demo.aegis` | Continuity Steward | Business CS, 2FA enabled |
| `cs_priya` | `priya@demo.aegis` | Continuity Steward | Business CS |
| `cs_alternate` | `jameswilson@demo.aegis` | Continuity Steward | Invited CS |
| `cs_resigned` | `rachelkim@demo.aegis` | Continuity Steward | Business CS |
| `ss_linda` | `linda@demo.aegis` | Support Steward | |
| `ss_james` | `jamescarter@demo.aegis` | Support Steward | |
| `bp_acme` | `contact@acmehealth.demo.aegis` | Business Partner | Agency |
| `bp_jamal` | `jamal@demo.aegis` | Business Partner | Freelancer |
| `bp_team_owner` | `contact@nexus.demo.aegis` | Business Partner | Agency |
| `bp_team_member` | `tanya@demo.aegis` | Business Partner | Freelancer |
| `admin_root` | `admin@aegis.internal` | Admin | 2FA enabled |

> Note: `p_sarah` and `cs_marcus` and `admin_root` have `two_factor_enabled = 1` — logging in will trigger the MFA challenge screen. You'll need to disable 2FA in the DB or set `two_factor_enabled = 0` for those users if you haven't set up TOTP yet.