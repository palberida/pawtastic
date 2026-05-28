<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsAppClient
{
    /**
     * Send an approved template message. The only message type allowed once the
     * 24h customer-service window has closed; billed by Meta per send. Fixed-text
     * templates need no components.
     */
    public function sendTemplate(string $toPhone, string $name, string $language): array
    {
        $apiVersion    = config('metabot.graph_api_version');
        $phoneNumberId = config('metabot.phone_number_id');
        $token         = config('metabot.access_token');

        $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $toPhone,
            'type'              => 'template',
            'template'          => [
                'name'     => $name,
                'language' => ['code' => $language],
            ],
        ];

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($url, $payload);

        return [
            'status' => $response->status(),
            'body'   => $response->json() ?? $response->body(),
        ];
    }

    /**
     * Upload a media file to Meta and return its media id (reusable for 30 days).
     * Multipart upload to the phone number's /media edge.
     */
    public function uploadMedia(string $path, string $mime, string $filename): array
    {
        $apiVersion    = config('metabot.graph_api_version');
        $phoneNumberId = config('metabot.phone_number_id');
        $token         = config('metabot.access_token');

        $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/media";

        $response = Http::withToken($token)
            ->attach('file', file_get_contents($path), $filename, ['Content-Type' => $mime])
            ->post($url, [
                'messaging_product' => 'whatsapp',
                'type'              => $mime,
            ]);

        return [
            'status' => $response->status(),
            'body'   => $response->json() ?? $response->body(),
        ];
    }

    public function sendImageById(string $toPhone, string $mediaId, ?string $caption = null): array
    {
        $apiVersion    = config('metabot.graph_api_version');
        $phoneNumberId = config('metabot.phone_number_id');
        $token         = config('metabot.access_token');

        $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages";

        $image = ['id' => $mediaId];
        if ($caption !== null && $caption !== '') {
            $image['caption'] = $caption;
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $toPhone,
            'type'              => 'image',
            'image'             => $image,
        ];

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($url, $payload);

        return [
            'status' => $response->status(),
            'body'   => $response->json() ?? $response->body(),
        ];
    }

    public function sendText(string $toPhone, string $text): array
    {
        $apiVersion    = config('metabot.graph_api_version');
        $phoneNumberId = config('metabot.phone_number_id');
        $token         = config('metabot.access_token');

        $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $toPhone,
            'type'              => 'text',
            'text'              => ['preview_url' => false, 'body' => $text],
        ];

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($url, $payload);

        return [
            'status' => $response->status(),
            'body'   => $response->json() ?? $response->body(),
        ];
    }

    public function sendButtons(string $toPhone, string $body, array $buttons): array
    {
        $apiVersion    = config('metabot.graph_api_version');
        $phoneNumberId = config('metabot.phone_number_id');
        $token         = config('metabot.access_token');

        $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $toPhone,
            'type'              => 'interactive',
            'interactive'       => [
                'type'   => 'button',
                'body'   => ['text' => $body],
                'action' => [
                    'buttons' => array_map(function ($b) {
                        return [
                            'type'  => 'reply',
                            'reply' => ['id' => $b['id'], 'title' => $b['title']],
                        ];
                    }, $buttons),
                ],
            ],
        ];

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($url, $payload);

        return [
            'status' => $response->status(),
            'body'   => $response->json() ?? $response->body(),
        ];
    }
}
