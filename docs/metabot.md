# Metabot — Project plan

A WhatsApp chatbot for the shop, built inside this Laravel app. The long-term goal is for Claude to read incoming customer messages and either auto-reply (when confident) or escalate to a human, with read-only access to the product catalog. We are starting with a much narrower **Phase 1** to prove the pipeline end-to-end before adding any of that.

> **Read this section first.** Phase 1 scope is intentionally minimal — no Claude, no DB lookups, no admin UI. If you're tempted to build any of those, stop and confirm.

---

## Stack (decided)

- **Language / framework:** PHP, inside this existing Laravel 8 app — one route group, one controller, one service, one migration. No Composer additions for Phase 1; standard Laravel `Http` facade handles outbound.
- **Hosting:** existing app's existing domain (already HTTPS, already public).
- **Database:** existing MySQL instance. Phase 1 adds **new** tables prefixed `metabot_` and does **not** touch any product/order tables.
- **No Claude API in Phase 1.** Routing only.

---

## Phase 1 — scope (this is all we're building right now)

**Goal:** when a customer's first message to our WhatsApp number was triggered by clicking a specific Facebook/Instagram ad (matched by a configured `source_id`), reply once with an interactive button message. Log whatever the customer does next and stay silent — we take over in Meta Business Suite.

### Trigger logic

For every incoming POST to `/webhooks/whatsapp`:

1. **Verify** Meta's `X-Hub-Signature-256` header by HMAC-SHA256 over the **raw** request body using `METABOT_APP_SECRET`. Reject mismatches with 401.
2. **Dedupe** on `messages[].id` — if we've already logged that id, skip silently (Meta retries webhooks).
3. **Ad match:** if the message has a `referral` object and `referral.source_id === config('metabot.target_ad_id')`, send the buttons reply (one outbound call), log both the inbound event and the outbound send.
4. **Button reply:** if the message is `type === 'interactive'` with `interactive.type === 'button_reply'`, log the button id and title — **no further action**.
5. **Anything else** (free text not from the target ad, statuses, reactions, etc.) → log as `ignored`, do nothing.

That's the whole behavior in Phase 1.

### Buttons (hard-coded placeholders)

Three buttons (WhatsApp's max), text fixed in `config/metabot.php`. Exact labels TBD; placeholders to start:

| id                    | title (≤20 chars)  |
|-----------------------|--------------------|
| `metabot:see_price`   | See price          |
| `metabot:see_photos`  | See more photos    |
| `metabot:talk_human`  | Talk to a human    |

A `body` text (≤1024 chars) sits above the buttons. Also configurable.

### What happens after a button tap

Just log the choice. Bot stays silent. We respond manually in Meta Business Suite. No "waiting for human" dashboard in Phase 1.

---

## Out of scope for Phase 1

Reintroduced in later phases (see bottom). If a request lands that needs any of these, confirm a phase bump before building:

- Claude integration of any kind
- Product DB lookups, photos, search
- Multiple ads / per-ad customization (single configured ad id for now)
- Per-button canned follow-up replies
- "Waiting for human" dashboard or notifications
- Admin UI for browsing logs
- Facebook Messenger, Instagram DMs
- Order placement / payments
- Multi-language, multi-agent / team inbox

---

## Architecture (Phase 1)

```
[ Customer on WhatsApp ]
          │
          ▼
[ Meta WhatsApp Cloud API ]
          │  HTTPS POST  (existing ossu domain)
          ▼
[ Laravel route: POST /webhooks/whatsapp ]
          │
          ├─ verify X-Hub-Signature-256
          ├─ dedupe on message id
          ├─ if referral.source_id == METABOT_TARGET_AD_ID  → send buttons
          ├─ if interactive button reply                    → log
          └─ else                                           → log
                  │
                  ▼
       [ Meta Graph API ]  (outbound: interactive button message)
```

---

## Implementation in this repo

### Routes

In `routes/web.php`, **outside** every role group (the webhook is unauthenticated from Laravel's POV — Meta authenticates via signature):

- `GET  /webhooks/whatsapp` → `WhatsAppWebhookController@verify` — Meta's verification handshake. Compare `hub.verify_token` query param to `config('metabot.verify_token')`, echo `hub.challenge` on match.
- `POST /webhooks/whatsapp` → `WhatsAppWebhookController@handle` — incoming events dispatcher.

### CSRF exemption

Add `'webhooks/whatsapp'` to `App\Http\Middleware\VerifyCsrfToken::$except`. Without this, every POST from Meta returns 419.

### Controller

`App\Http\Controllers\WhatsAppWebhookController`:

- `verify(Request $r)` — handshake.
- `handle(Request $r)` — signature check → dedupe → branch on the 3 cases above. Inline validation, no FormRequest (matches the rest of this codebase).

Per ossu conventions, validation is inline; do not introduce a `WhatsAppWebhookRequest`.

### Service

`App\Services\WhatsAppClient` — thin wrapper around Laravel's `Http` facade. Phase 1 needs only:

- `sendButtons(string $toPhone, string $body, array $buttons): array` — POSTs an interactive button message to `https://graph.facebook.com/v{API_VERSION}/{phone_number_id}/messages` with the bearer token. Returns Meta's response (we log it).

API version: pin to a recent stable (`v20.0` or whichever is current at build time) in config.

### Schema

One new table: `metabot_events`. The real schema in this repo is managed directly in MySQL (see `CLAUDE.md`), and **we follow that convention here too — no Laravel migration, even though this table is new and isolated**. Apply the DDL below by hand against the MySQL server.

`ENGINE`, `CHARSET`, and `COLLATE` are intentionally omitted so the table inherits the database defaults (confirmed `utf8mb4` / `utf8mb4_general_ci` on the current DB, which is required for WhatsApp's 4-byte characters and emoji).

```sql
CREATE TABLE metabot_events (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    wa_message_id   VARCHAR(128)     NULL,
    direction       ENUM('in','out') NOT NULL,
    from_phone      VARCHAR(32)      NULL,
    to_phone        VARCHAR(32)      NULL,
    kind            VARCHAR(64)      NOT NULL,    -- 'ad_match', 'sent_buttons', 'button_reply', 'ignored', 'verify_fail'
    source_id       VARCHAR(128)     NULL,        -- referral.source_id when present
    button_id       VARCHAR(128)     NULL,        -- on button reply (kept for Phase 2 routing + analytics)
    button_title    VARCHAR(128)     NULL,        -- denormalized for log readability + historical record
    payload         JSON             NULL,        -- raw inbound event or outbound response
    created_at      TIMESTAMP        NULL,
    updated_at      TIMESTAMP        NULL,
    PRIMARY KEY (id),
    UNIQUE KEY metabot_events_wa_message_id_unique (wa_message_id),
    KEY metabot_events_from_phone_index  (from_phone),
    KEY metabot_events_created_at_index  (created_at),
    KEY metabot_events_kind_index        (kind)
);
```

The unique constraint on `wa_message_id` is the dedup mechanism — the controller uses `insertOrIgnore` and bails when zero rows are affected (Meta retried a message we already handled).

`kind` is a `VARCHAR(64)` rather than an `ENUM` so later phases can add new kinds without an `ALTER TABLE`.

**Retention:** Phase 1 has none — rows accrue indefinitely. At expected volume this is fine for years, but `from_phone` and `payload` contain customer PII, so a retention cron (e.g. `DELETE … WHERE created_at < NOW() - INTERVAL 90 DAY`) should land before this subsystem grows beyond a test ad.

**Naming note:** English column names here are a deliberate exception to the project's Spanish convention, called out in `CLAUDE.md`. The bot mirrors WhatsApp's English API fields and is self-contained — do not propagate this exception elsewhere.

### Config

New `config/metabot.php` reading from env:

```php
return [
    'verify_token'      => env('METABOT_VERIFY_TOKEN'),
    'app_secret'        => env('METABOT_APP_SECRET'),
    'access_token'      => env('METABOT_ACCESS_TOKEN', env('META_TOKEN')),
    'phone_number_id'   => env('METABOT_PHONE_NUMBER_ID'),
    'target_ad_id'      => env('METABOT_TARGET_AD_ID'),
    'graph_api_version' => env('METABOT_GRAPH_API_VERSION', 'v22.0'),
    'buttons_body'      => env('METABOT_BUTTONS_BODY', '¡Hola! Gracias por tu interés. ¿En qué te puedo ayudar?'),
    'buttons'           => [
        ['id' => 'metabot:see_price',  'title' => 'See price'],
        ['id' => 'metabot:see_photos', 'title' => 'See more photos'],
        ['id' => 'metabot:talk_human', 'title' => 'Talk to a human'],
    ],
];
```

`.env` additions:

- `METABOT_VERIFY_TOKEN` — random string we choose and paste into Meta's webhook config.
- `METABOT_APP_SECRET` — from Meta App Settings → Basic, used to verify `X-Hub-Signature-256`.
- `METABOT_ACCESS_TOKEN` — *optional*; falls back to the existing `META_TOKEN` already used by `scripts/fetch_ads.php`, since the same System User token has the WhatsApp scopes (`whatsapp_business_messaging`, `whatsapp_business_management`). Set this only if you want a token dedicated to the bot.
- `METABOT_PHONE_NUMBER_ID` — from Meta's WhatsApp dashboard.
- `METABOT_TARGET_AD_ID` — set blank initially; copy in the real `source_id` after the first ad-click row lands in `metabot_events`.
- `METABOT_GRAPH_API_VERSION` — defaults to `v22.0` to match the ads pipeline; no need to set unless you want to pin a different version.
- `METABOT_BUTTONS_BODY` — optional welcome-text override; falls back to the Spanish default above.

### Signature verification

```php
$raw  = $request->getContent();           // raw body, BEFORE any decoding
$sig  = $request->header('X-Hub-Signature-256');   // 'sha256=...'
$calc = 'sha256=' . hash_hmac('sha256', $raw, config('metabot.app_secret'));
if (!$sig || !hash_equals($calc, $sig)) {
    // log 'verify_fail', abort(401)
}
```

`hash_equals` (not `===`) for constant-time comparison.

### Operational scripts

If we later need cron-style jobs for metabot, they go in `scripts/` alongside `monthly_inventory.php` etc., following the existing pattern (load `.env` via `vlucas/phpdotenv`). Phase 1 has no cron needs.

---

## Setup checklist (Meta side, Phase 1 only)

- [ ] Meta Developer account at https://developers.facebook.com
- [ ] Meta App with the **WhatsApp** product added
- [ ] WhatsApp test number OR verified business number, with Phone Number ID + permanent access token
- [ ] App Secret (App Settings → Basic) — needed for signature verification
- [ ] Pick a random string → `METABOT_VERIFY_TOKEN`, paste it into Meta's webhook config
- [ ] Webhook URL pointed at `https://<ossu-domain>/webhooks/whatsapp`
- [ ] Subscribe to the `messages` field on the WhatsApp webhook
- [ ] One **live Click-to-WhatsApp ad** running, so a real `referral.source_id` exists to put in `METABOT_TARGET_AD_ID`

Not needed for Phase 1: Anthropic API key, photo URLs, read-only DB user, any product table changes.

---

## Open questions still remaining for Phase 1

1. **Exact button labels** for the test ad — 3 buttons, ≤20 chars each (WhatsApp limit).
2. **Welcome message body** — text shown above the buttons (≤1024 chars).
3. **Which ad** to target first — we need its `source_id`. Easiest way to grab it: deploy the webhook in log-only mode briefly, click my own ad once, copy `referral.source_id` from `metabot_events`.
4. **Language of the welcome message** — should match the ad creative's language.

---

## Later phases (deferred until Phase 1 ships)

Brief — to be re-detailed when we get there:

- **Phase 2 — per-button follow-up.** Each button id → a fixed canned reply. Still no Claude.
- **Phase 3 — multiple ads.** Replace single `METABOT_TARGET_AD_ID` with a config (table or file) mapping `source_id → { welcome_body, buttons }`.
- **Phase 4 — read-only product DB access.** Implement `search_products`, `get_product_details`, `get_product_photos` as plain PHP / Eloquent against the existing tables. Create a read-only MySQL user for the bot at this point.
- **Phase 5 — Claude routing.** Free-text handling via Anthropic API with tool-use: `search_products`, `get_product_details`, `get_product_photos`, `reply_to_customer`, `escalate_to_human`. Bot only replies when Claude calls `reply_to_customer`. Anthropic API key arrives in this phase.
- **Phase 6 — admin UI.** Laravel page listing conversations + escalations. Add a role gate (likely `ceo,administrador`) per the existing convention.
- **Phase 7 — polish.** Real phone number (if not yet), business hours behavior, escalation notifications, conversion tracking back to Meta via `ctwa_clid`.

---

## Cross-cutting things to remember (all phases)

- **`referral` only on the first message.** Phase 1 doesn't persist it (just matches and replies). Phase 3+ must persist `source_id` / `headline` / `ctwa_clid` per customer or ad attribution is lost permanently for that conversation.
- **WhatsApp 24-hour window.** After 24h of customer silence, only template messages can be sent. Not an issue for the reactive Phase 1 flow.
- **Human-in-the-loop is the design principle.** The bot must err on the side of silence. Even when Claude lands in Phase 5, "silent guessing" (replying outside `reply_to_customer`) is a bug, not a feature.
- **Read-only on shop data.** The bot never writes to products, variants, orders, etc. Catalog editing stays in the existing ossu admin UI.
