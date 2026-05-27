# Metabot handoff — pawtastic

Written 2026-05-21 from the ossu project. This repo will be seeded from ossu, so all metabot code and docs travel with it. **Read `docs/metabot.md` first** — it is the plan-of-record. This file only covers what changes for the pawtastic deployment.

---

## State at handoff

Phase 1 webhook pipeline was built and proven end-to-end in ossu against a Meta **test number** in Live mode (commit `b6894ce`, plus `412b5af` for the public privacy-policy page Meta requires for Live mode). What is known good and does **not** need re-debugging:

- Route + CSRF exemption (`GET`/`POST /webhooks/whatsapp`, exempted in `VerifyCsrfToken::$except`)
- `X-Hub-Signature-256` HMAC verification over the raw body
- `insertOrIgnore`-based dedup on `wa_message_id`
- Inbound event → DB insert
- WABA→app subscription mechanism (`POST /{waba}/subscribed_apps`)
- Outbound interactive-buttons send via `App\Services\WhatsAppClient`

Things that are **new in the repo but not yet exercised in production**:

- `mysql_metabot_ro` DB connection (`05bbbff`, May 18). It expects `METABOT_DB_USERNAME` / `METABOT_DB_PASSWORD` in `.env`. The MySQL user itself was never created on the ossu DB and **must be created fresh on pawtastic's DB** — see DB setup below. This connection is Phase 4 scaffolding; Phase 1 does not use it.
- Dockerfile for local php/artisan dev container (`13e8525`). Unrelated to metabot but available.

Phase 1 scope is **intentionally minimal** — no Claude, no DB lookups, no admin UI, no per-button replies. If a new request looks like it crosses into Phase 2+, stop and confirm a phase bump before building (see `docs/metabot.md` § "Out of scope for Phase 1" and § "Later phases").

---

## What changes for pawtastic vs. ossu

Everything Meta-side and DB-side is being re-provisioned from scratch. Treat the env values from ossu as **non-portable** — do not copy them across.

### Meta side (fresh account)

The phone number is being disconnected from the current ossu Meta integration and re-added under a **new Meta account**. That means every Meta-issued value gets re-minted:

| Env var | Source after re-provisioning |
|---|---|
| `METABOT_PHONE_NUMBER_ID` | New WhatsApp dashboard, after the number is re-added |
| `METABOT_APP_SECRET` | New Meta app → App Settings → Basic |
| `METABOT_VERIFY_TOKEN` | Random string you choose, then paste into Meta's webhook config |
| `METABOT_ACCESS_TOKEN` (or `META_TOKEN` fallback) | New System User token, scopes `whatsapp_business_messaging` + `whatsapp_business_management` |
| `METABOT_TARGET_AD_ID` | Leave blank initially; copy in the real `referral.source_id` after the first ad-click row lands in `metabot_events` |
| `METABOT_GRAPH_API_VERSION` | Optional. Defaults to `v22.0`. |
| `METABOT_BUTTONS_BODY` | Optional welcome-text override |

Full Meta-side setup checklist is in `docs/metabot.md` § "Setup checklist (Meta side, Phase 1 only)" — follow it verbatim. Two things from the ossu run that bit us and are worth remembering:

- **Dev mode delivers no real webhooks**, not even for app admins/devs/testers. Only the dashboard's "Probar" synthetic test fires in Dev mode. Real traffic needs Live mode, which needs a public privacy-policy URL at App Settings → Basic. The Blade view + `Route::view` for ossu's privacy page is at `412b5af` — adapt the URL/branding for pawtastic and keep the page reachable; if it 404s Meta can flip the app back to Dev.
- **WABA→app subscription is separate from field subscription.** The `messages` field showing "Suscritos" in the WhatsApp config UI is **not** sufficient. You also need `POST /{waba_id}/subscribed_apps` with the bot's access token. Both must be in place for production traffic to reach the webhook.

### Database side (fresh user)

Per project convention (no Laravel migrations, schema managed by hand in MySQL — see `CLAUDE.md` § "Schema changes via raw SQL"):

1. **Create the `metabot_events` table** by running the DDL in `docs/metabot.md` § "Schema" directly against pawtastic's MySQL.
2. **Create the read-only MySQL user** for `mysql_metabot_ro`. The user was never created on ossu's DB, so there is no precedent — pick a username/password, grant `SELECT` only on the pawtastic DB, and put the credentials in `.env` as `METABOT_DB_USERNAME` / `METABOT_DB_PASSWORD`. The connection block in `config/database.php` already exists from `05bbbff` and does not need changes.

Phase 1 does not actually use `mysql_metabot_ro` — it only writes to `metabot_events` via the default connection. Creating the RO user now is optional; you can defer it until Phase 4 if you prefer.

### Things that stay the same

- All code under `App\Http\Controllers\WhatsAppWebhookController`, `App\Services\WhatsAppClient`, `config/metabot.php`, and the `webhooks/whatsapp` routes.
- The `VerifyCsrfToken::$except` entry for `webhooks/whatsapp`.
- The privacy-policy Blade view pattern (rebrand the copy; keep the route).
- All Phase 1 behavior contracts in `docs/metabot.md` (dedup, signature verification, ad-match → buttons, button-reply → log, everything-else → log silently).

---

## Where to pick up in the next session

In rough order:

1. **Disconnect the phone** from the current ossu Meta integration and re-add it under the new Meta account. (User-action, not code.)
2. **Provision the new Meta app** end-to-end per `docs/metabot.md` § "Setup checklist". Flip to Live mode once the pawtastic privacy-policy page is live.
3. **Create `metabot_events`** on the pawtastic MySQL DB (DDL in `docs/metabot.md`).
4. **Optional (or deferred to Phase 4):** create the `metabot_ro` MySQL user and set `METABOT_DB_USERNAME` / `METABOT_DB_PASSWORD`.
5. **Smoke test** — send a message from a phone to the new number, confirm a row lands in `metabot_events`. Useful debugging notes from ossu's smoke test:
   - Meta's dashboard "Probar" sends a synthetic payload with a **fixed sample `wa_message_id`** — repeat clicks dedup against the first row via the UNIQUE constraint. Symptom is "IDs increment but no row lands". Not a bug. To re-test, `DELETE FROM metabot_events WHERE id = N` first.
   - "Carga completa" in Meta's dashboard shows the payload Meta *would* send for a recent event — it is **not** proof of delivery. Easy to misread.
6. **Discover `METABOT_TARGET_AD_ID`** — leave the env value blank, run a tiny CTWA ad on the new account targeting the new number, click it from your own phone, and copy `referral.source_id` from the resulting `metabot_events` row into `.env`.
7. **Verify the round-trip** — clicking that specific ad should land an `ad_match` row, send the buttons reply (`sent_buttons` row), and clicking a button should log a `button_reply` row. Then freeze Phase 1.

Anything beyond that is Phase 2+. Confirm before building.

---

## Conventions to preserve (carried over from ossu)

These are in `CLAUDE.md` already but worth restating because they are easy to forget for an isolated subsystem like metabot:

- **No Laravel migrations.** Apply DDL directly to MySQL. Real schema is not in `database/migrations/`.
- **Spanish naming for everything except metabot.** Metabot is a deliberate, self-contained English-naming exception because it mirrors WhatsApp's English API fields. Do not propagate the English-naming exception to other subsystems.
- **Read-only on shop data.** The bot never writes to products/variants/orders/etc. The `mysql_metabot_ro` connection enforces this at the DB level for later phases; in Phase 1 the bot does not touch those tables at all.
- **Human-in-the-loop is the design principle.** Silence > wrong auto-reply. The bot's only outbound message in Phase 1 is the buttons reply on an ad match. Everything else is silent. This holds across all later phases too — when Claude lands in Phase 5, "silent guessing" is a bug, not a feature.

---

## Open / known-stale items to clean up while here

- `CLAUDE.md`'s Metabot subsystem note still references `v20.0` in the Graph API URL; `config/metabot.php` defaults to `v22.0`. Low priority — fix when convenient.
- No retention policy on `metabot_events`. At expected volume rows accrue indefinitely, which is fine for years, but `from_phone` and `payload` contain customer PII. A retention cron (`DELETE … WHERE created_at < NOW() - INTERVAL 90 DAY`) should land before this subsystem grows beyond a test ad. Not a Phase 1 blocker.
