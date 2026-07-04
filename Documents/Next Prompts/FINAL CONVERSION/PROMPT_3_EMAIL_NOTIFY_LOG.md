# AEGIS — PROMPT 3: Email Triggers + Third-Party Notifications + Self Logging

**Self-contained. Everything you need to wire the write-path notifications for one page/domain. Do not look for other MD files.**

For every write action a page performs: (1) trigger the correct email to every affected party, (2) write a `notification` activity entry for every OTHER affected party, (3) write a `log` activity entry for the actor's own history. Design (Prompt 1) and data wiring (Prompt 2) are already done.

---

## Repo facts (verified — do not re-derive)

**Write path:** Controller → **Service** (does DB write, calls `ActivityService::log()`, dispatches `event(new …)`) → Listener sends email. Events fire from Services, never Controllers.

**`ActivityService::log()` — REAL signature is POSITIONAL, 12 params (not named args):**
```php
$this->activity->log(
    string $userId,              // 1  whose feed this row belongs to (actor for 'log', recipient for 'notification')
    string $portal,              // 2  'provider'|'cs'|'ss'|'bp'|'admin'  ($user->role->portal())
    string $module,              // 3  plan|incident|vault|steward|network|bp|documents|messages|support|finances|settings|referral
    ActivitySeverity $severity,  // 4  ActivitySeverity::Info|Warning|Error|Critical
    string $action,              // 5  snake_case, e.g. 'connection_request_sent'
    string $title,               // 6  short headline
    string $description,         // 7  sentence
    ?string $linkableType = null,// 8  Model::class
    ?string $linkableId = null,  // 9  $model->id
    ?string $relatedUserId = null,//10 the OTHER party's id
    string $entryType = 'log',   //11  'log' | 'notification'   ← position 11
    ?string $actorId = null      //12  who triggered it         ← position 12
);
```
⚠️ Positions 11 & 12 are frequently swapped — that throws an ENUM error. `entryType` is 11, `actorId` is 12.

- **`entry_type = 'log'`** → actor's own history ("My Activity" tab). `userId` = actor.
- **`entry_type = 'notification'`** → other party's feed ("Notifications" tab). `userId` = recipient, `relatedUserId`/`actorId` = the actor.
- **> 3 recipients:** use `ActivityFanoutJob` (via `ActivityService::fanout(...)`), don't loop.

**Email path:**
- Events registered in **`app/Providers/AppServiceProvider.php`** via `Event::listen(Event::class, Listener::class)` (63 already wired). NOT EventServiceProvider.
- `SendEmailNotificationListener::resolve()` is a `match(true) { $event instanceof X => $this->x($event) }` → one private method per event returning an array of recipient specs:
  ```php
  [ ['user_id'=>…, 'gate_key'=>'notify_…', 'template'=>'emails.domain.NN-name', 'data'=>[…]], … ]
  ```
  It then checks `NotificationService::shouldSend($user_id, $gate_key)` and dispatches `SendEmailJob`.
- **`SendEmailJob(string $template, array $data = [], ?string $recipientUserId = null, ?string $recipientEmail = null)`** — positional; queued on `email`.
- **`ActivityFanoutListener`** is a safety-net for edge-case events; most Services log inline (preferred, more contextual).
- **Incident emails** go through `SendIncidentAlertsListener` (UNGATED) — do not route incident alerts through the gated listener.
- Email templates live in `resources/views/emails/<domain>/NN-name.blade.php`. Data merged by `EmailDataResolver`.

---

## Step 0 — Setup + full read

```bash
cd /home/claude && rm -rf aegis
git clone --depth=1 https://github.com/rehanurrashid/aegis-laravel.git aegis
cd aegis && git log -1 --oneline

# ⚠️ Read the REAL ActivityService signature FIRST
sed -n '/public function log/,/): ActivityEvent/p' app/Services/ActivityService.php

# The service(s) backing this page's write actions
cat app/Services/[Domain]Service.php

# Event wiring + listener
grep -n "Event::listen" app/Providers/AppServiceProvider.php | grep -i "[domain]"
sed -n '/private function resolve/,/^    }/p' app/Listeners/SendEmailNotificationListener.php
cat app/Listeners/ActivityFanoutListener.php | head -80

# Events for this domain
ls app/Events/[Domain]/ 2>/dev/null
cat app/Events/[Domain]/*.php

# Email templates for this domain
ls resources/views/emails/[domain]/ 2>/dev/null

# SendEmailJob + gate keys
sed -n '/public function __construct/,/}/p' app/Jobs/SendEmailJob.php
grep -n "notify_" app/Services/NotificationService.php | head -30

# EmailDataResolver — how merge fields are built
grep -n "function " app/Services/EmailDataResolver.php | head -30
```

---

## Step 1 — Write-action inventory

For every state-mutating method in the domain service(s):

```markdown
## Write Actions — [Domain]

| Service method | Actor | Other party(ies) | Email template | Actor log (entry_type=log) | Notification(s) (entry_type=notification) |
|---|---|---|---|---|---|
| request()  | Provider | target provider | emails.network.42-connection-request | network / connection_request_sent | recipient: connection_request_received |
| accept()   | Provider | requester | emails.network.43-connection-accepted | network / connection_accepted | requester: your_request_accepted |
| decline()  | Provider | — (silent, business rule) | none | network / connection_declined | — |
| …          | | | | | |

### Silent-by-design actions (log actor only, no email, no notification)
| Method | Why silent |

### Fan-out actions (>3 recipients → ActivityFanoutJob)
| Method | Recipient set |
```

---

## Step 2 — Gap report

```markdown
## Gap Report
### Missing email dispatch (service does DB write but no event(new …))
| Method | Event to dispatch | Template available |

### Missing actor log (no entry_type='log' call)
| Method | module | action string |

### Missing notification (other party not written entry_type='notification')
| Method | Recipient | relatedUserId source |

### Event dispatched but not registered in AppServiceProvider
| Event | Registered? | resolve() case in listener? |

### Listener resolve() case missing / wrong gate_key / wrong template path
| Event | Issue |

### Email template exists but nothing dispatches it
| Template | Should fire on |

### WRONG named-arg ActivityService calls (must be positional)
| File:line | Fix |

### Positions 11/12 swapped (entryType/actorId)
| File:line | Fix |
```

---

## Step 3 — Fixes (surgical, in the Service)

For each write method, the completed pattern:

```php
public function request(User $provider, User $recipient, ?string $message = null): NetworkRequest
{
    $req = NetworkRequest::create([ /* … */ ]);   // (data write done in Prompt 2)

    // 1) Actor log  (entry_type = 'log', position 11)
    $this->activity->log(
        $provider->id,                    // userId = actor
        $provider->role->portal(),        // portal
        'network',                        // module
        ActivitySeverity::Info,           // severity
        'connection_request_sent',        // action
        'Connection request sent',        // title
        "You sent a connection request to {$recipient->display_name}.", // description
        NetworkRequest::class,            // linkableType
        $req->id,                         // linkableId
        $recipient->id,                   // relatedUserId (other party)
        'log',                            // entryType  (position 11)
        $provider->id,                    // actorId    (position 12)
    );

    // 2) Notification to the other party (entry_type = 'notification')
    $this->activity->log(
        $recipient->id,                   // userId = recipient
        $recipient->role->portal(),
        'network',
        ActivitySeverity::Info,
        'connection_request_received',
        'New connection request',
        "{$provider->display_name} wants to connect with you.",
        NetworkRequest::class,
        $req->id,
        $provider->id,                    // relatedUserId (the actor)
        'notification',                   // entryType  (position 11)
        $provider->id,                    // actorId    (position 12)
    );

    // 3) Email
    event(new ConnectionRequestSent($req));
    return $req;
}
```

**Fan-out (>3 recipients):**
```php
$this->activity->fanout(
    $recipientIds,           // array
    'incident', ActivitySeverity::Critical,
    'incident_activated', 'Incident activated', $desc,
    Incident::class, $incident->id, $actor->id, 'notification'
);   // dispatches ActivityFanoutJob internally
```

**Register a new event** in `app/Providers/AppServiceProvider.php`:
```php
Event::listen(Events\Network\ConnectionRequestSent::class, Listeners\SendEmailNotificationListener::class);
// add ActivityFanoutListener ONLY if the service doesn't log inline
```

**Add the resolve() case** in `SendEmailNotificationListener`:
```php
$event instanceof ConnectionRequestSent => $this->connectionRequestSent($event),
// …
private function connectionRequestSent(ConnectionRequestSent $e): array {
    return [[
        'user_id'  => $e->request->recipient_id,
        'gate_key' => 'notify_network_requests',
        'template' => 'emails.network.42-connection-request',
        'data'     => ['requester_name' => $e->request->requester->display_name],
    ]];
}
```

**Rules:**
- Email to EVERY affected party (actor confirmation + each recipient) unless a rule says silent.
- Incident alerts → `SendIncidentAlertsListener` (ungated), never the gated one.
- Never move DB writes here; those belong to Prompt 2. This pass adds logs + events only.
- Every `event(new …)` must have (a) a registration in AppServiceProvider and (b) a resolve() case, or it sends nothing.

---

## Step 4 — Verification gates

```bash
SVC=app/Services/[Domain]Service.php

# Every mutation has BOTH a log and (unless silent) an event dispatch
grep -n "->save()\|->update(\|->create(" $SVC
grep -n "activity->log\|event(new"        $SVC

# No named-arg ActivityService calls (must be positional)
grep -rn "entryType:\|actorId:\|actor:\|subject:\|module:\s*'" app/Services/ | grep -v vendor   # none

# entryType present as a positional 'log'/'notification' literal
grep -c "'log'\|'notification'" $SVC   # > 0

# Every domain email template has a dispatch somewhere
for t in $(find resources/views/emails/[domain] -name "*.blade.php" | sed "s|resources/views/||;s|.blade.php||;s|/|.|g"); do
  c=$(grep -rn "'$t'" app/ 2>/dev/null | grep -c "template\|SendEmailJob\|=>")
  echo "$c  $t"
done   # every count > 0

# Every event(new X) is registered + resolved
for e in $(grep -oE "new [A-Za-z]+\(" $SVC | grep -oE "[A-Za-z]+" | grep -v new | sort -u); do
  reg=$(grep -c "$e::class" app/Providers/AppServiceProvider.php)
  res=$(grep -c "$e" app/Listeners/SendEmailNotificationListener.php)
  echo "$e  registered=$reg resolved=$res"
done   # both ≥ 1 (unless handled by SendIncidentAlertsListener)

php -l $SVC
php -l app/Listeners/SendEmailNotificationListener.php
php -l app/Providers/AppServiceProvider.php
```

---

## Step 5 — Deliver

```bash
cd /home/claude/aegis
CHANGED=$(git status --porcelain | awk '{print $2}')
zip -r /mnt/user-data/outputs/aegis_[domain]_notify.zip $CHANGED 2>/dev/null
unzip -l /mnt/user-data/outputs/aegis_[domain]_notify.zip
```

Summary:
```markdown
## Email / Notification / Log Summary
| Action | Email | Actor log | Notification |
|--------|-------|-----------|-------------|
| … | ✅ | ✅ | ✅ / — (silent) |

### event(new) added: N   ### resolve() cases added: N
### AppServiceProvider registrations added: N
### Actor logs added: N   ### Notifications added: N
### Fan-outs: N   ### Named-arg calls fixed: N   ### 11/12 swaps fixed: N
### Gates: ✅
```

## Start
Read the REAL `ActivityService::log()` signature first (positions 11=entryType, 12=actorId). Read the service, listener resolve(), events, templates, AppServiceProvider. Output Step 1 write-action inventory. Output Step 2 gap report. Apply Step 3 in the Service + Listener + AppServiceProvider surgically. Run Step 4 gates. Deliver Step 5. No design, no data-write changes.
