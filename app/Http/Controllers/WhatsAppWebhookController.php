<?php

namespace App\Http\Controllers;

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
            'payload'       => json_encode($message, JSON_UNESCAPED_UNICODE),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // Meta retried a message we already handled — skip the outbound send.
        if ($inserted === 0) {
            return;
        }

        if ($kind === 'ad_match' && $from !== null) {
            $response = $whatsapp->sendButtons(
                $from,
                config('metabot.buttons_body'),
                config('metabot.buttons')
            );

            DB::table('metabot_events')->insert([
                'wa_message_id' => null,
                'direction'     => 'out',
                'from_phone'    => null,
                'to_phone'      => $from,
                'kind'          => 'sent_buttons',
                'source_id'     => $referralSourceId,
                'payload'       => json_encode($response, JSON_UNESCAPED_UNICODE),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }
}
