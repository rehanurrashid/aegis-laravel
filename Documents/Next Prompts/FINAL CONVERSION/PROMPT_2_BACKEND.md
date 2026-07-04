# AEGIS — PROMPT 2: Wire Vue Frontend to Laravel Backend

**Self-contained. Everything you need to connect one Vue page to the real backend. Do not look for other MD files.**

Take a design-complete Vue page and make every piece of data dynamic, every form submit real, every list/detail bound to controller props. Analyze frontend against controller + service + FormRequest + migration, fill the ~1–2% backend gaps, seed data to cover all UI states.

Design is already done (Prompt 1). Email/notification wiring is separate (Prompt 3).

---

## Repo facts (verified — do not re-derive)

- **Laravel root = repo root.** Clone `github.com/rehanurrashid/aegis-laravel`.
- **Write path:** Vue `useForm()` → Inertia POST → FormRequest (`authorize()` returns `true`; real auth at Policy) → Controller → Service → DB. Events fire from **Services**, never Controllers.
- **UUID PKs** are `CHAR(36)` / prefixed random strings (e.g. `'ae_'.Str::lower(Str::random(12))`). Models with string PKs need manual ID generation — `updateOrCreate` may fail; create explicitly.
- **Money is integer cents.** Dollar inputs in Vue convert via `watch()`; store cents.
- **Inertia shared props** (from `HandleInertiaRequests`): `auth.user` (with `avatar_url`, `avatar_initials`, `slug`), `auth.portal`, `flash`, `unreadCount`, `unreadMessages`. Don't re-fetch these.
- **Services** live in `app/Services/` (34 of them). **Controllers** in `app/Http/Controllers/<Portal>/`. **FormRequests** in `app/Http/Requests/<Portal>/`.
- **Reference portals:** when unsure how a page should wire, read the equivalent Provider page + controller + service — Provider is canonical; CS/SS/BP/Admin mirror it.

---

## Step 0 — Setup + full read

```bash
cd /home/claude && rm -rf aegis
git clone --depth=1 https://github.com/rehanurrashid/aegis-laravel.git aegis
cd aegis && git log -1 --oneline

VUE_PAGE="resources/js/pages/[portal]/[Page].vue"
CTRL="app/Http/Controllers/[Portal]/[Page]Controller.php"

# 1. The design-complete Vue file — every prop it reads, every form it submits
cat $VUE_PAGE

# 2. The controller — what it renders + what props it passes
cat $CTRL

# 3. FormRequests for this page
ls app/Http/Requests/[Portal]/ | grep -i "[domain]"
cat app/Http/Requests/[Portal]/*[Domain]*.php

# 4. The service(s) it calls
cat app/Services/[Domain]Service.php

# 5. Migrations — REAL column names + types (never trust the prototype's names)
ls database/migrations/ | grep -iE "[relevant tables]"
cat database/migrations/*[table]*

# 6. Models — casts, relations, fillable, PK type
cat app/Models/[Model].php

# 7. Enums used
ls app/Enums/ | grep -iE "[domain]"
cat app/Enums/[Enum].php

# 8. Routes — verb + name for every action the page submits
grep -E "[domain-route-fragment]" routes/web.php

# 9. Reference: the Provider equivalent (if this isn't Provider)
cat resources/js/pages/provider/[SimilarPage].vue 2>/dev/null | head -120
cat app/Http/Controllers/Provider/[Similar]Controller.php 2>/dev/null

# 10. Seeders — what data already exists
ls database/seeders/ | grep -iE "[domain]"
cat database/seeders/[Domain]Seeder.php 2>/dev/null

# 11. Inertia shared props (don't duplicate)
grep -n "'auth'\|'flash'\|portal\|unread" app/Http/Middleware/HandleInertiaRequests.php
```

---

## Step 1 — Frontend ↔ Backend contract inventory

```markdown
## Contract Inventory — [Page]

### Props the Vue page declares (defineProps)
| Prop | Type in Vue | Controller returns it? | Real source (service method) |

### Props the controller passes (Inertia::render)
| Prop | Value expression | Vue declares it? |
→ Every controller prop must be declared in Vue; every Vue prop must be returned by controller.

### Forms (every useForm in the page)
| Form | Fields (v-model keys) | Route + verb | FormRequest | Field names match FormRequest? |

### Static/hardcoded data still in the page (must become dynamic)
| Location | Hardcoded content | Replacement prop | Source |
(names, IDs, amounts, dates, "Robert Miller", "$12,450", demo rows — all banned)

### Detail modals — do they read from an active* ref (not hardcoded)?
| Modal | activeX ref set on click? | Reads from ref? |

### Column-name reality check (Vue field → migration column)
| Vue/form field | Assumed column | Actual migration column | Type | Match? |
(Common traps: completed_at vs completed_on; credits vs credit_hours; VARCHAR vs JSON)

### Enum fields
| Field | Enum | Arrives as {value:x} or string? (unwrap with val()) |

### Route verbs
| Action | Vue verb (.post/.put/.delete) | routes/web.php verb | Match? |
```

---

## Step 2 — Gap report

```markdown
## Backend Gap Report

### Missing controller props (Vue declares, controller doesn't pass)
### Missing service methods (controller needs, service lacks)
### FormRequest field mismatches (form sends X, request validates Y)
### Route verb mismatches
### Migration column mismatches (service/form uses wrong name)
### Hardcoded data not yet dynamic
### Missing routes entirely
### Missing seed data for UI states (empty / partial / full / each status)
```

---

## Step 3 — Fixes (surgical, backend first)

### 3A — Fix the contract, backend-first (frontend depends on it)

**Field names must match FormRequest exactly:**
```php
// Read the FormRequest rules() keys. Vue useForm keys must equal them.
// If FormRequest validates 'cred_type' and Vue sends 'type' → server rejects silently.
```

**HTTP verb must match route:**
```bash
grep "[domain]" routes/web.php
# Route::post → .post() ; Route::put/patch → .put()/.patch() ; Route::delete → .delete()
```

**Column names must match migration** — read `cat database/migrations/*[table]*` before writing any service/seed code. Fix the SERVICE if it uses a wrong column; never bend Vue to a backend bug. Check `->string()` vs `->json()` before treating a column as an array.

**Controller must return every declared prop:**
```php
return Inertia::render('[Portal]/[Page]', [
    'items'   => $service->listForUser(auth()->user()),   // real data, paginated where large
    'stats'   => $service->statsFor(auth()->user()),
    'filters' => $request->only('status','q'),
    // …every prop the Vue defineProps declares
]);
```
Large lists → `->paginate(N)` (primary tables 5–8/page). Grouped props → `->groupBy(k)->map(fn($g)=>$g->values())` (force plain arrays) and `default: () => ({})` in Vue with `prop?.[key] ?? []` access.

### 3B — Make every value dynamic

Replace all hardcoded data with prop bindings. Every list is a `v-for` over a prop. Every detail modal reads from an `activeX` ref set on row click (store only the ID; derive the live object via `computed(() => list.find(x => x.id === activeId))` so it survives reloads). Zero fake names/IDs/amounts anywhere.

### 3C — Wire every form

```js
const form = useForm({ /* keys EXACTLY match FormRequest */ })

function submit() {
  // Vuelidate first if present, then:
  form.post(route('[portal].[domain].store'), {   // verb matches route
    // forceFormData: true  ← only if a File field is present
    preserveScroll: true,
    onSuccess: () => { modals.x = false; toast.success('Saved.') },
    onError:   () => toast.error('Please check the form.'),
  })
}
// enum compares: const val = v => (v && typeof v==='object' && 'value' in v) ? v.value : (v ?? '')
// money: dollar input ref → watch → form.amount_cents = Math.round(dollars*100)
```

### 3D — Backend gaps you may create (allowed)
- New service method → write it (follow the existing service's constructor-injected `ActivityService` pattern; but the actual log/email calls are Prompt 3's job — here just make data flow).
- New route → add it with correct verb + name.
- New FormRequest → create it; `authorize()` returns `true`.
- New migration/seeder for missing columns or data.
- New controller method → thin: validate → call service → `back()->with('success',…)` or `Inertia::render`.

### 3E — Seed all UI states
Extend/create the domain seeder so the page renders every state:
- Empty (new user, no rows)
- Partial (a few rows)
- Full (10+ rows → exercises pagination)
- Every status/enum value the UI branches on
- Related records for detail modals
Use deterministic prefixed IDs so re-runs update in place.

```bash
php artisan migrate:fresh --seed
# login as the relevant demo user, walk the page, confirm no blank sections
```

---

## Step 4 — Verification gates

```bash
VUE=$VUE_PAGE
# No hardcoded fake data
grep -E "Robert Miller|John D\.|Sarah M\.|NPI-2024|CA-MD-67890|Lorem ipsum|\\\$12,450|\\\$8,900" $VUE  # none
# No axios/fetch — Inertia only
grep -cE "axios|fetch\(" $VUE   # 0
# No window.location navigation
grep -c "window.location" $VUE  # 0
# Every form field key check (manual): compare useForm keys ↔ FormRequest rules() keys
# Every controller prop is declared in Vue and vice-versa (manual diff)
# Route names resolve
for r in $(grep -oE "route\('[a-z._]+'" $VUE | grep -oE "'[^']+'" | tr -d "'" | sort -u); do
  grep -q "->name('$r')" routes/web.php || echo "MISSING ROUTE: $r"
done
# Money never rendered as raw cents
grep -nE "/ 100|\.toFixed" $VUE   # review each — should use a formatMoney helper
# Migrations back the columns used
# (manual) confirm each service column against cat database/migrations/*table*
php -l $CTRL
php -l app/Services/[Domain]Service.php
```

---

## Step 5 — Deliver

```bash
cd /home/claude/aegis
CHANGED=$(git status --porcelain | awk '{print $2}')
zip -r /mnt/user-data/outputs/aegis_[page]_backend.zip $CHANGED 2>/dev/null
unzip -l /mnt/user-data/outputs/aegis_[page]_backend.zip
```

Summary:
```markdown
## Backend Wiring Summary
### Props wired: N (all controller↔Vue matched)
### Forms wired: N (all field names + verbs verified)
### Hardcoded → dynamic: N replacements
### Backend gaps filled: [service methods / routes / FormRequests / migrations]
### Seed states covered: empty / partial / full / [statuses]
### Gates: ✅
```

## Start
Read the Vue page, controller, FormRequests, services, migrations, models, enums, routes — and the Provider reference equivalent. Output Step 1 contract inventory. Output Step 2 gap report. Apply Step 3 fixes backend-first. Seed all states. Run Step 4 gates. Deliver Step 5. No design changes, no email wiring.
