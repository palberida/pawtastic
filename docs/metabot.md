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

## Phase 2 — LLM-in-the-loop conversational bot

> This section supersedes the old one-line "Phase 2–6" placeholders. It was defined collaboratively, case by case, before being written here — treat it as the agreed spec for the conversational bot. Build it in the sub-phases at the end of this section; do not jump straight to wiring outbound sends.

Phase 1 sends one static buttons reply on an ad match and stays silent. Phase 2 replaces that with **Claude reading every inbound message** and either answering from the catalog or handing off to a human.

### Core principle

Claude reads every inbound message and replies **only** by calling a tool. **No tool call = silence.** If Claude is not 100% confident, it escalates to a human (or stays silent). The bot is **read-only** on shop data — it never writes to products / variants / orders / tags. This is the same human-in-the-loop principle as Phase 1, now enforced through the tool boundary: a reply the bot wasn't sure about is a bug, not a feature.

### Engagement scope

The bot acts **only on ad-originated conversations** — a message carrying an ad `referral`, plus the follow-up messages of that conversation. Any inbound with **no ad origin and no active bot conversation → ignored, no email** (humans handle normal inbox traffic exactly as today). This keeps the bot off the shop's existing organic WhatsApp traffic.

### Tools available to Claude

| tool | purpose |
|------|---------|
| `send_text(text)` | free-form reply (greeting, price, measurements) |
| `send_list(body, button, rows)` | pickers — category / product / size lists (≤10 rows) |
| `send_images(images[])` | product photos (URL + caption per image) |
| `send_faq(faq_id)` | sends a stored FAQ answer **verbatim** — Claude never rewords policy text |
| `escalate_to_human(reason)` | emails staff with full context, then the bot goes quiet |

`send_buttons` (the Phase 1 mechanism) is retained as a fallback for an unconfigured ad or a Claude API error. Emitting **no tool call** is the explicit "stay silent" path.

### Conversation lifecycle

- The first message's `referral.source_id` is matched to a `metabot_ads` row → a `metabot_conversations` row is created/activated for that phone.
- **Newest referral wins.** A later ad click switches the conversation's active product scope to the new ad; earlier messages stay as history but no longer drive the answers.
- **Full history is retained and fed to Claude** on every reply — no truncation for now (catalog and conversation volumes are small; revisit if token cost grows).
- **Escalation → conversation marked `handed_off`, bot goes quiet.** It does not reply or re-email on subsequent messages — the human owns it.
- **A new ad click re-wakes** a handed-off conversation (clear new intent): status returns to `active` with the new ad's scope.
- **No idle timeouts.** The bot **never** chases a quiet customer — no proactive "¿sigues ahí?".
- Because the bot is purely reactive (only ever replies to an inbound message), every reply is automatically inside WhatsApp's 24-hour service window — **no paid template messages are needed** in Phase 2.

### The flow, per message

**1. First contact.** Always open the first reply with a greeting, then proceed. Ad prefills ("precio?", "¿cómo puedo hacer una compra?") are treated as ordinary free text — no special-casing.

**2. Route by the active ad's scope:**

- **Single-product ad** → straight to the product tail (step 3).
- **Product-set ad** (`scope = product_set`, 2–10 products) → send **one representative photo per product** (product-level `image_1`, else the first variant's first photo; **name-only caption; no cap**), then a **text list** picker with **name-only rows** → on selection, go to the product tail.
- **Site-wide ad** (`scope = site_wide`, could be any product) → send a **category list** built from the distinct product-level `categoria` tag values (≤10) → on category selection, show that category's products exactly like a product-set ad (photos + text list) → on product selection, go to the product tail. *(Catalog is small, so a category never overflows the 10-row list; no secondary narrowing step is built.)*

**3. Product tail.** Driven by the product's **pivot** — a product-level tag naming the single dimension that affects price/measurements:

- **Pivot present** (e.g. `pivot = talla`): show a **size list with measurements** so the customer picks by their pet's size. Collapse the variant grid by the pivot value (e.g. 5 colors × 5 sizes = 25 variants → 5 size options). Show **price as a range** ("desde Q120 hasta Q180") when it varies across the pivot.
- **Pivot absent**: answer the price directly.
- **Photos: on-demand only** (when the customer asks, or asks for more detail) → **one photo per color** (group variants by color, one representative each), captioned with the color name.
- **Stock → shown as `agotado`** when 0, using a **group-level** rule: a size is agotado only if *every* color of that size is 0; a color is agotado only if *all* its sizes are 0; a specific chosen variant uses its own exact stock.

**4. Final answer.** Give **only what was asked** — price *or* measurements *or* photos (the LLM reads intent). Mention `agotado` only when relevant. Then **wait silently** (react-style; no closer, no prompt).

**5. Off-topic questions** (shipping/coverage, payment methods, delivery time, returns/warranty) → Claude matches against `metabot_faq`; on a confident match it calls `send_faq` (verbatim answer); otherwise it **escalates**.

**6. Buying intent** ("lo quiero", "¿cómo compro?", "quiero 2") → **escalate** + go quiet. **No order-taking in Phase 2.**

**7. Non-text message** (voice note, photo, sticker, location, document) → **escalate** (noting the media type in the reason) + go quiet. Phase 2 reads text only.

**8. Non-Spanish message** → **always reply in Spanish** regardless of the customer's language.

**9. Any other uncertainty** → escalate (full-context email) or stay silent. When in doubt, hand off.

### Escalation email

Sent to `METABOT_ESCALATION_EMAILS` (comma-separated). Contains **full context** so a human can take over fast: customer phone, the matched ad / `source_id`, the full message transcript so far, the current product/category in play, the bot's stated reason for escalating, and a **click-to-WhatsApp link** (`https://wa.me/<phone>`). The customer receives **no acknowledgment** — escalation is silent to them.

> SMTP: the current `.env` points at mailhog (dev only). A real SMTP provider must be configured before this ships (sub-phase 2d).

### Data model changes

All new tables keep the `metabot_` prefix and the English-column exception (per `CLAUDE.md`), and — like `metabot_events` — are applied as **hand-written DDL against MySQL, no Laravel migration**. `ENGINE`/`CHARSET`/`COLLATE` omitted to inherit the DB's `utf8mb4` defaults.

**Existing table — one ALTER (the only change to a non-`metabot_` table, and it's additive/nullable):**

```sql
ALTER TABLE product_tags MODIFY id_variante INT(11) NULL;
```

A product-level tag now has `id_variante IS NULL`; a variant-level tag keeps `id_variante` set.

**Tag conventions** (all freeform `(tag, valor)` rows in `product_tags`, read-only to the bot):

| tag | level | valor | role |
|-----|-------|-------|------|
| `categoria` | product | e.g. `Collares` | site-wide category step |
| `pivot` | product | e.g. `talla` | names the one dimension driving price/measurements; absent = no pivot |
| `talla` (the pivot's name) | variant | e.g. `M` | the pivot value per variant |
| `image_N` | variant first, product fallback | full HTTPS URL | photos (`image_1`, `image_2`, …) |
| *(measurement tags)* | variant or product | freeform | shown alongside the size list |

Authoritative price = `variants.precio` (`products.precio` is ignored). Stock = `variants.stock`.

Photo lookup is variant-first with product fallback, ordered numerically:
```sql
WHERE id_producto = ? AND tag LIKE 'image\_%' ORDER BY CAST(SUBSTRING(tag, 7) AS UNSIGNED)
```

**New tables:**

```sql
CREATE TABLE metabot_ads (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_id    VARCHAR(128) NOT NULL,                       -- referral.source_id of the ad
    name         VARCHAR(150) NULL,                           -- human label for the admin UI
    scope        ENUM('site_wide','product_set') NOT NULL,
    welcome_text VARCHAR(1024) NULL,                          -- optional per-ad greeting override
    status       ENUM('active','paused') NOT NULL DEFAULT 'active',
    created_at   TIMESTAMP NULL,
    updated_at   TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY metabot_ads_source_id_unique (source_id)
);

CREATE TABLE metabot_ad_products (                            -- product-set ads only
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    metabot_ad_id BIGINT UNSIGNED NOT NULL,
    id_producto   INT NOT NULL,                               -- references products.id (no hard FK: keeps metabot decoupled + read-only)
    created_at    TIMESTAMP NULL,
    updated_at    TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY metabot_ad_products_unique (metabot_ad_id, id_producto),
    KEY metabot_ad_products_producto_index (id_producto),
    CONSTRAINT metabot_ad_products_ad_fk FOREIGN KEY (metabot_ad_id) REFERENCES metabot_ads (id) ON DELETE CASCADE
);

CREATE TABLE metabot_faq (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    topic       VARCHAR(64) NOT NULL,                         -- 'shipping','payment','delivery_time','returns'
    trigger_description VARCHAR(500) NULL,                     -- what customer questions this covers (helps Claude match)
    answer_text VARCHAR(1024) NOT NULL,                        -- sent verbatim by send_faq
    status      ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY metabot_faq_topic_index (topic)
);

CREATE TABLE metabot_conversations (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    phone             VARCHAR(32) NOT NULL,
    current_ad_id     BIGINT UNSIGNED NULL,                   -- active ad scope (newest referral wins)
    current_source_id VARCHAR(128) NULL,
    status            ENUM('active','handed_off') NOT NULL DEFAULT 'active',
    last_message_at   TIMESTAMP NULL,
    created_at        TIMESTAMP NULL,
    updated_at        TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY metabot_conversations_phone_unique (phone),
    KEY metabot_conversations_ad_index (current_ad_id)
);
```

`metabot_events` (Phase 1) remains the per-message log and is the source of the "full history" fed to Claude, joined on `from_phone`. `metabot_conversations` holds only the current state.

### Config / `.env` additions

```php
// config/metabot.php — new keys
'anthropic_api_key'  => env('METABOT_ANTHROPIC_API_KEY'),
'claude_model'       => env('METABOT_CLAUDE_MODEL', 'claude-sonnet-4-6'),
'escalation_emails'  => array_filter(explode(',', env('METABOT_ESCALATION_EMAILS', ''))),
'shadow_mode'        => env('METABOT_SHADOW_MODE', true),   // sub-phase 2c: log intended action, do not send
```

- `METABOT_ANTHROPIC_API_KEY` — Claude API key (arrives in Phase 2).
- `METABOT_CLAUDE_MODEL` — model id; defaults to the current Sonnet.
- `METABOT_ESCALATION_EMAILS` — comma-separated staff recipients.
- `METABOT_SHADOW_MODE` — when true, Claude runs and its intended tool call is logged but **nothing is sent** (the 2c safety gate). Flip to false in 2d.
- Real `MAIL_*` SMTP settings (current `.env` is mailhog).
- The bot reads the catalog through the **`mysql_metabot_ro`** read-only connection (already in `config/database.php`).

### Build order (sub-phases)

- **2a — schema + admin.** Apply the `ALTER` and `CREATE TABLE`s above; add Eloquent models (`MetabotAd`, `MetabotAdProduct`, `MetabotFaq`, `MetabotConversation`) and a `tags`/`pivot` accessor path on `Product`/`Variant`; build the admin UI (role `administrador`) to map ads↔products, set ad scope/welcome text, and edit FAQs. Seed the four FAQ topics.
- **2b — read-only catalog service.** A `ProductCatalog` service over `mysql_metabot_ro` exposing the lookups the tools need (products in an ad, collapse-by-pivot, measurements, photos variant-first, group-level stock). Unit-tested against fixtures.
- **2c — shadow mode.** Add `ClaudeClient` (Anthropic tool-use) + a conversation service that assembles history + the active ad's catalog context and asks Claude for a tool call, but **only logs the intended action** (`METABOT_SHADOW_MODE=true`). Validate behavior against real inbound traffic before sending anything.
- **2d — go live.** Extend `WhatsAppClient` with `sendText` / `sendList` / `sendImages`; wire the escalation `Mailable` + real SMTP; flip `METABOT_SHADOW_MODE=false`.

---

## Later phases (deferred until Phase 2 ships)

- **Order placement / payments** — taking orders in-chat (Phase 2 escalates buying intent instead).
- **Canned-FAQ growth** — richer FAQ matching, more topics, per-ad FAQ overrides if needed.
- **Admin polish** — a conversations + escalations dashboard (beyond the 2a ad/FAQ admin), retention/PII tooling.
- **Media understanding** — transcribing voice notes / reading product photos instead of escalating them.
- **Multi-channel** — Facebook Messenger, Instagram DMs, team inbox.
- **Conversion tracking** — report conversions back to Meta via `ctwa_clid`.
- **Business-hours behavior** — e.g. different escalation routing after hours.

---

## Cross-cutting things to remember (all phases)

- **`referral` only on the first message.** Phase 1 doesn't persist it (just matches and replies). **Phase 2 persists it** in `metabot_conversations` (newest referral wins) — follow-up messages carry no `referral`, so without this the active ad scope is lost. `headline` / `ctwa_clid` are still only available on that first message if later phases need them for attribution.
- **WhatsApp 24-hour window.** After 24h of customer silence, only template messages can be sent. Not an issue for the reactive Phase 1 flow.
- **Human-in-the-loop is the design principle.** The bot must err on the side of silence. With Claude landing in Phase 2, this is enforced at the tool boundary: **no tool call = silence**, and any uncertainty escalates. "Silent guessing" (replying when not confident) is a bug, not a feature.
- **Read-only on shop data.** The bot never writes to products, variants, orders, etc. Catalog editing stays in the existing ossu admin UI.
