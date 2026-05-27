-- Metabot Phase 2a — schema + seed
-- Apply BY HAND against the production MySQL (no Laravel migration, per CLAUDE.md).
-- ENGINE/CHARSET/COLLATE omitted so tables inherit the DB's utf8mb4 defaults
-- (required for WhatsApp's 4-byte characters and emoji).

-- 1) Existing table: allow product-level tags (id_variante NULL).
--    product-level tag => id_variante IS NULL; variant-level tag => id_variante set.
ALTER TABLE product_tags MODIFY id_variante INT(11) NULL;

-- 2) Ads the bot engages — replaces the single METABOT_TARGET_AD_ID env var.
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

-- 3) Which products belong to a product_set ad.
CREATE TABLE metabot_ad_products (
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

-- 4) Canned FAQ answers — sent verbatim by the bot's send_faq tool.
CREATE TABLE metabot_faq (
    id                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    topic               VARCHAR(64) NOT NULL,                 -- 'shipping','payment','delivery_time','returns'
    trigger_description VARCHAR(500) NULL,                    -- what customer questions this covers (helps Claude match)
    answer_text         VARCHAR(1024) NOT NULL,               -- sent verbatim
    status              ENUM('active','inactive') NOT NULL DEFAULT 'active',
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,
    PRIMARY KEY (id),
    KEY metabot_faq_topic_index (topic)
);

-- 5) Per-customer conversation state (newest referral wins; handed_off => bot stays quiet).
CREATE TABLE metabot_conversations (
    id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    phone             VARCHAR(32) NOT NULL,
    current_ad_id     BIGINT UNSIGNED NULL,                   -- active ad scope
    current_source_id VARCHAR(128) NULL,
    status            ENUM('active','handed_off') NOT NULL DEFAULT 'active',
    last_message_at   TIMESTAMP NULL,
    created_at        TIMESTAMP NULL,
    updated_at        TIMESTAMP NULL,
    PRIMARY KEY (id),
    UNIQUE KEY metabot_conversations_phone_unique (phone),
    KEY metabot_conversations_ad_index (current_ad_id)
);

-- 6) Seed the four FAQ topics. Inactive + placeholder answer; fill them in the admin UI
--    (a FAQ only fires once it's active and has a real answer_text).
INSERT INTO metabot_faq (topic, trigger_description, answer_text, status, created_at, updated_at) VALUES
  ('shipping',      'Envíos, cobertura, a qué zonas llega y costo de envío.',        'PENDIENTE', 'inactive', NOW(), NOW()),
  ('payment',       'Métodos de pago: contra entrega, tarjeta, transferencia.',      'PENDIENTE', 'inactive', NOW(), NOW()),
  ('delivery_time', 'Tiempo de entrega / cuánto tarda en llegar.',                   'PENDIENTE', 'inactive', NOW(), NOW()),
  ('returns',       'Política de devoluciones, cambios y garantía.',                 'PENDIENTE', 'inactive', NOW(), NOW());
