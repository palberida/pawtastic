<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class WhatsAppClient
{
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
