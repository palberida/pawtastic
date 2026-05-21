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
];
