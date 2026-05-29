<?php

namespace App\Http\Controllers;

use App\Models\MetabotAd;
use App\Models\MetabotConversation;
use App\Services\MetabotBrain;
use App\Services\WhatsAppClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $mode      = $request->query('hub_mode');
        $token     = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        if ($mode === 'subscribe' && $token === config('metabot.verify_token')) {
            return response($challenge, 200);
        }

        abort(403);
    }

    public function handle(Request $request, WhatsAppClient $whatsapp)
    {
        $raw    = $request->getContent();
        $sig    = $request->header('X-Hub-Signature-256');
        $secret = config('metabot.app_secret');
        $calc   = 'sha256=' . hash_hmac('sha256', $raw, (string) $secret);

        if (!$sig || !hash_equals($calc, $sig)) {
            DB::table('metabot_events')->insert([
                'direction'  => 'in',
                'kind'       => 'verify_fail',
                'payload'    => $raw !== '' ? $raw : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            abort(401);
        }

        $data = json_decode($raw, true) ?: [];

        foreach (data_get($data, 'entry', []) as $entry) {
            foreach (data_get($entry, 'changes', []) as $change) {
                foreach (data_get($change, 'value.messages', []) as $message) {
                    $this->processMessage($message, $whatsapp);
                }
            }
        }

        return response('', 200);
    }

    private function processMessage(array $message, WhatsAppClient $whatsapp): void
    {
        $waMessageId      = $message['id'] ?? null;
        $from             = $message['from'] ?? null;
        $type             = $message['type'] ?? null;
        $referralSourceId = data_get($message, 'referral.source_id');
        $targetAdId       = config('metabot.target_ad_id');

        $isButtonReply = $type === 'interactive'
            && data_get($message, 'interactive.type') === 'button_reply';
        $isAdMatch = $referralSourceId !== null
            && $targetAdId !== null
            && $referralSourceId === $targetAdId;

        $buttonId    = null;
        $buttonTitle = null;

        if ($isButtonReply) {
            $kind        = 'button_reply';
            $buttonId    = data_get($message, 'interactive.button_reply.id');
            $buttonTitle = data_get($message, 'interactive.button_reply.title');
        } elseif ($isAdMatch) {
            $kind = 'ad_match';
        } else {
            $kind = 'ignored';
        }

        $inserted = DB::table('metabot_events')->insertOrIgnore([
            'wa_message_id' => $waMessageId,
            'direction'     => 'in',
            'from_phone'    => $from,
            'to_phone'      => null,
            'kind'          => $kind,
            'source_id'     => $referralSourceId,
            'button_id'     => $buttonId,
            'button_title'  => $buttonTitle,
            'body'          => $this->extractBody($message),
            'payload'       => json_encode($message, JSON_UNESCAPED_UNICODE),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // Meta retried a message we already handled — skip the outbound send.
        if ($inserted === 0) {
            return;
        }

        // Download inbound images so the inbox can show a thumbnail.
        if ($type === 'image' && $waMessageId) {
            $mediaId = data_get($message, 'image.id');
            if ($mediaId && ($path = $whatsapp->storeMedia($mediaId))) {
                DB::table('metabot_events')
                    ->where('wa_message_id', $waMessageId)
                    ->update(['media_path' => $path, 'updated_at' => now()]);
            }
        }

        // Phase 2: hand the message to the conversational bot (ad-originated only).
        $this->routeBot($message, $type, $from, $referralSourceId, $whatsapp);
    }

    /**
     * Decide whether the conversational bot owns this message and, if so, run it.
     *
     * The bot engages only on ad-originated conversations: an inbound carrying a
     * referral whose source_id matches an active metabot_ads row, plus the
     * follow-ups of an already-active conversation. Everything else is left to
     * humans (no reply, no email).
     *
     * Newest referral wins — a later ad click re-points (and re-wakes) the convo.
     */
    private function routeBot(array $message, ?string $type, ?string $from, ?string $referralSourceId, WhatsAppClient $whatsapp): void
    {
        if ($from === null) {
            return;
        }

        // Gated <from_ad> simulator: lets the owner test the bot without a real ad click.
        // Fires ONLY when enabled AND the sender is the configured simulator phone.
        $body = $type === 'text' ? trim((string) data_get($message, 'text.body')) : null;
        if ($body === '<from_ad>'
            && config('metabot.simulator_enabled')
            && config('metabot.simulator_phone')
            && $from === config('metabot.simulator_phone')) {
            $referralSourceId = config('metabot.simulator_source_id');
        }

        $ad = $referralSourceId
            ? MetabotAd::where('source_id', $referralSourceId)->where('status', 'active')->first()
            : null;

        $conv = MetabotConversation::where('phone', $from)->first();

        if ($ad) {
            // Engage or re-wake. A handed_off conversation returns to active here.
            $conv = MetabotConversation::firstOrNew(['phone' => $from]);
            $conv->current_ad_id     = $ad->id;
            $conv->current_source_id = $ad->source_id;
            $conv->status            = 'active';
            $conv->last_message_at   = now();
            $conv->save();
        } else {
            // No ad on this message — only continue if the bot already owns it.
            if (!$conv || $conv->status !== 'active' || !$conv->current_ad_id) {
                return;
            }
            $conv->last_message_at = now();
            $conv->save();
        }

        app(MetabotBrain::class)->handle($conv, $whatsapp);
    }

    // Human-readable text for the inbox thread, by WhatsApp message type.
    private function extractBody(array $message): ?string
    {
        $type = $message['type'] ?? null;

        switch ($type) {
            case 'text':
                return data_get($message, 'text.body');
            case 'interactive':
                return data_get($message, 'interactive.button_reply.title')
                    ?? data_get($message, 'interactive.list_reply.title');
            case 'button':
                return data_get($message, 'button.text');
            case 'image':
                $caption = data_get($message, 'image.caption');
                return '[imagen]' . ($caption ? ' ' . $caption : '');
            case 'audio':
            case 'voice':
                return '[nota de voz]';
            case 'video':
                return '[video]';
            case 'document':
                return '[documento]';
            case 'sticker':
                return '[sticker]';
            case 'location':
                return '[ubicación]';
            default:
                return $type ? '[' . $type . ']' : null;
        }
    }
}
