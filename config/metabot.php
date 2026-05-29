<?php

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

    // --- Phase 2: LLM-in-the-loop conversational bot ---

    // Anthropic (Claude) API. Key lives ONLY in .env, handled like the WhatsApp tokens.
    'anthropic_api_key' => env('METABOT_ANTHROPIC_API_KEY'),
    'anthropic_version' => env('METABOT_ANTHROPIC_VERSION', '2023-06-01'),
    'claude_model'      => env('METABOT_CLAUDE_MODEL', 'claude-sonnet-4-6'),
    'max_tokens'        => (int) env('METABOT_MAX_TOKENS', 1024),

    // Read-only connection the bot uses for the catalog. Defaults to the RO user
    // (config/database.php → mysql_metabot_ro). Set METABOT_DB_CONNECTION=mysql to
    // fall back to the main connection while the RO user isn't provisioned yet.
    'catalog_connection' => env('METABOT_DB_CONNECTION', 'mysql_metabot_ro'),

    // Staff recipients for escalation emails (comma-separated). Defaults to ventas@ossu.gt.
    'escalation_emails' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('METABOT_ESCALATION_EMAILS', 'ventas@ossu.gt'))
    ))),

    // Shadow mode (sub-phase 2c safety gate): when true, Claude runs and its intended
    // tool call is logged, but NOTHING is sent to the customer. Flip to false to go live.
    'shadow_mode' => filter_var(env('METABOT_SHADOW_MODE', true), FILTER_VALIDATE_BOOLEAN),

    // Gated simulator: lets the owner pretend a message arrived from the ad, for
    // testing the bot without an actual ad click. Prefix a normal text with the
    // trigger word (e.g. "adtest hola, ¿precio?") and the bot treats it as coming
    // from simulator_source_id, answering the message minus the prefix. NOT a public
    // backdoor — it only fires when enabled AND the sender matches the configured phone.
    'simulator_enabled'   => filter_var(env('METABOT_SIMULATOR_ENABLED', false), FILTER_VALIDATE_BOOLEAN),
    'simulator_phone'     => env('METABOT_SIMULATOR_PHONE'),
    'simulator_source_id' => env('METABOT_SIMULATOR_SOURCE_ID', env('METABOT_TARGET_AD_ID')),
    'simulator_prefix'    => env('METABOT_SIMULATOR_PREFIX', 'adtest'),
];
