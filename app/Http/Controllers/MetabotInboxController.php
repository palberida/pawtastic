<?php

namespace App\Http\Controllers;

use App\Models\MetabotConversation;
use App\Services\WhatsAppClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MetabotInboxController extends Controller
{
    public function index()
    {
        // A conversation is keyed by the customer phone, which appears as
        // from_phone on inbound rows. Outbound rows carry it as to_phone.
        $phones = DB::table('metabot_events')
            ->where('direction', 'in')
            ->whereNotNull('from_phone')
            ->distinct()
            ->pluck('from_phone');

        $conversations = $phones->map(function ($phone) {
            $last = DB::table('metabot_events')
                ->where(function ($q) use ($phone) {
                    $q->where('from_phone', $phone)->orWhere('to_phone', $phone);
                })
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->first();

            return (object) [
                'phone'          => $phone,
                'last_at'        => $last->created_at ?? null,
                'last_body'      => $this->previewText($last),
                'last_direction' => $last->direction ?? null,
            ];
        })->sortByDesc('last_at')->values();

        return view('metabot.inbox.index', compact('conversations'));
    }

    public function show($phone)
    {
        $messages = $this->threadFor($phone);

        return view('metabot.inbox.show', compact('phone', 'messages'));
    }

    // Polled by the thread view to pick up new customer messages without a reload.
    public function messages($phone)
    {
        $messages = $this->threadFor($phone);

        return view('metabot.inbox._thread', compact('phone', 'messages'));
    }

    public function reply(Request $request, WhatsAppClient $whatsapp, $phone)
    {
        $request->validate(['body' => ['required', 'string', 'max:4096']]);
        $text = $request->input('body');

        $resp      = $whatsapp->sendText($phone, $text);
        $messageId = data_get($resp, 'body.messages.0.id');
        $ok        = $resp['status'] === 200 && $messageId;

        if (!$ok) {
            $err = data_get($resp, 'body.error.message', 'No se pudo enviar el mensaje (posible ventana de 24h vencida).');

            return redirect()->route('metabot.inbox.show', ['phone' => $phone])
                ->with('error', $err)
                ->withInput();
        }

        DB::table('metabot_events')->insert([
            'wa_message_id' => $messageId,
            'direction'     => 'out',
            'from_phone'    => null,
            'to_phone'      => $phone,
            'kind'          => 'human_reply',
            'body'          => $text,
            'payload'       => json_encode($resp, JSON_UNESCAPED_UNICODE),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        // A human is now handling this chat — keep the (future) bot silent on it.
        $conv = MetabotConversation::firstOrNew(['phone' => $phone]);
        $conv->status = 'handed_off';
        $conv->last_message_at = now();
        $conv->save();

        return redirect()->route('metabot.inbox.show', ['phone' => $phone]);
    }

    private function threadFor($phone)
    {
        return DB::table('metabot_events')
            ->where(function ($q) use ($phone) {
                $q->where('from_phone', $phone)->orWhere('to_phone', $phone);
            })
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();
    }

    private function previewText($row): string
    {
        if (!$row) {
            return '';
        }
        if (!empty($row->body)) {
            return $row->body;
        }
        if (!empty($row->button_title)) {
            return $row->button_title;
        }

        return '[' . ($row->kind ?? 'evento') . ']';
    }
}
