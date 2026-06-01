-- Metabot: remember each WhatsApp customer's profile name (from the webhook's
-- value.contacts[].profile.name), keyed by phone (wa_id). Lets the inbox show a
-- name instead of just a number. English-named per the metabot_ convention.
CREATE TABLE metabot_contacts (
    phone      VARCHAR(32) NOT NULL,
    name       VARCHAR(255) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    PRIMARY KEY (phone)
);
