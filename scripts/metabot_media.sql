-- Metabot media — schema
-- Apply BY HAND against MySQL (no Laravel migration, per CLAUDE.md).
-- REQUIRED before deploying the thumbnail code.
--
-- Relative path (under storage/app) to a downloaded copy of an image message,
-- both directions. Inbound images are fetched from Meta when the webhook fires;
-- outbound images are copied from the staff upload. Served through an
-- authenticated route, never published directly.
ALTER TABLE metabot_events ADD COLUMN media_path VARCHAR(255) NULL AFTER body;
