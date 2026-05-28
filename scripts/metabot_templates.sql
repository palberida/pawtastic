-- Metabot templates — schema
-- Apply BY HAND against MySQL (no Laravel migration, per CLAUDE.md).
--
-- Registry of WhatsApp message templates that have been APPROVED in Meta's
-- WhatsApp Manager. These are the only messages that can reopen a conversation
-- once the 24h customer-service window has closed (each send is billed by Meta).
-- `name` + `language` must match the approved template exactly; `body_preview`
-- is shown in the UI only (Meta delivers the approved body).
CREATE TABLE metabot_templates (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name         VARCHAR(128) NOT NULL,                       -- exact approved template name (e.g. reengage_general)
    language     VARCHAR(16)  NOT NULL DEFAULT 'es',          -- approved language code
    label        VARCHAR(150) NULL,                           -- friendly label for the chat picker
    body_preview VARCHAR(1024) NULL,                          -- the body text, for display only
    status       ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at   TIMESTAMP NULL,
    updated_at   TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY metabot_templates_name_lang_unique (name, language)
);
