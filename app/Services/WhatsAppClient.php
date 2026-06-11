<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WhatsAppClient
{
    /**
     * Resolve a Meta media id to a temporary download URL, fetch the bytes
     * (the download also requires the bearer token), and store a copy under
     * storage/app/metabot_media. Returns the relative storage path or null.
     */
    public function storeMedia(string $mediaId): ?string
    {
        $apiVersion = config('metabot.graph_api_version');
        $token      = config('metabot.access_token');

        $meta = Http::withToken($token)->acceptJson()
            ->get("https://graph.facebook.com/{$apiVersion}/{$mediaId}");
        $url  = data_get($meta->json(), 'url');
        $mime = data_get($meta->json(), 'mime_type');
        if (!$url) {
            return null;
        }

        $bin = Http::withToken($token)->get($url);
        if (!$bin->successful()) {
            return null;
        }

        return $this->putMedia($bin->body(), $mime);
    }

    /**
     * Persist raw image bytes to storage and return the relative path.
     * Used for both inbound (downloaded) and outbound (staff upload) images.
     */
    public function putMedia(string $contents, ?string $mime): string
    {
        $ext  = $mime === 'image/png' ? 'png' : 'jpg';
        $path = 'metabot_media/' . Str::random(40) . '.' . $ext;
        Storage::disk('local')->put($path, $contents);

        return $path;
    }

    /**
     * Persist a downscaled copy of an image for the inbox preview only. The
     * customer always receives the full-resolution file (uploaded to Meta
     * separately), so this local copy is shrunk to cap disk usage. Resizes via
     * GD to at most $maxWidth px wide; if GD is unavailable, the bytes don't
     * decode, or the image is already small enough, the original bytes are
     * stored unchanged so sending never breaks on a resize failure.
     */
    public function putMediaThumbnail(string $contents, ?string $mime, int $maxWidth = 300): string
    {
        return $this->putMedia($this->resizeImage($contents, $mime, $maxWidth) ?? $contents, $mime);
    }

    /**
     * Resize image bytes to at most $maxWidth px wide, preserving aspect ratio
     * and PNG transparency. Returns null (caller keeps the original) when GD is
     * missing, the bytes can't be decoded, or the image is already narrow enough.
     */
    private function resizeImage(string $contents, ?string $mime, int $maxWidth): ?string
    {
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }

        $src = @imagecreatefromstring($contents);
        if ($src === false) {
            return null;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        if ($w <= $maxWidth) {
            imagedestroy($src);
            return null;
        }

        $newW = $maxWidth;
        $newH = max(1, (int) round($h * $maxWidth / $w));
        $dst  = imagecreatetruecolor($newW, $newH);

        $isPng = $mime === 'image/png';
        if ($isPng) {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $w, $h);

        ob_start();
        if ($isPng) {
            imagepng($dst, null, 6);
        } else {
            imagejpeg($dst, null, 82);
        }
        $out = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        return $out !== '' ? $out : null;
    }

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

    /**
     * Send an interactive list message (picker). Up to 10 rows in a single section.
     * Titles are capped at 24 chars and descriptions at 72 by the API, so we truncate.
     *
     * @param  array<array{id:string,title:string,description?:string}>  $rows
     */
    public function sendList(string $toPhone, string $body, string $button, array $rows): array
    {
        $apiVersion    = config('metabot.graph_api_version');
        $phoneNumberId = config('metabot.phone_number_id');
        $token         = config('metabot.access_token');

        $url = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages";

        $listRows = array_map(function ($r) {
            $row = [
                'id'    => mb_substr((string) ($r['id'] ?? ''), 0, 200),
                'title' => mb_substr((string) ($r['title'] ?? ''), 0, 24),
            ];
            if (!empty($r['description'])) {
                $row['description'] = mb_substr((string) $r['description'], 0, 72);
            }

            return $row;
        }, array_slice($rows, 0, 10));

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type'    => 'individual',
            'to'                => $toPhone,
            'type'              => 'interactive',
            'interactive'       => [
                'type'   => 'list',
                'body'   => ['text' => mb_substr($body, 0, 1024)],
                'action' => [
                    'button'   => mb_substr($button ?: 'Ver opciones', 0, 20),
                    'sections' => [
                        ['rows' => $listRows],
                    ],
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

    /**
     * Send an image by public URL (used for catalog photos, which already live on
     * an HTTPS CDN — no upload step needed, unlike staff-uploaded images).
     */
    public function sendImageByUrl(string $toPhone, string $url, ?string $caption = null): array
    {
        $apiVersion    = config('metabot.graph_api_version');
        $phoneNumberId = config('metabot.phone_number_id');
        $token         = config('metabot.access_token');

        $endpoint = "https://graph.facebook.com/{$apiVersion}/{$phoneNumberId}/messages";

        $image = ['link' => $url];
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
            ->post($endpoint, $payload);

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
