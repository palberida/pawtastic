<?php

namespace App\Http\Controllers;

use App\Models\MetabotConversation;
use App\Models\MetabotTemplate;
use App\Services\MetabotCatalog;
use App\Services\WhatsAppClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetabotInboxController extends Controller
{
    // Outbound kinds the customer actually receives. An internal row like
    // bot_escalate is NOT here: when the bot hands off, the customer got nothing,
    // so the chat must still read as pending a human reply.
    private const CUSTOMER_FACING = [
        'bot_text', 'bot_list', 'bot_image', 'bot_faq',
        'human_reply', 'human_image', 'template', 'sent_buttons',
    ];

    public function index()
    {
        // A conversation is keyed by the customer phone, which appears as
        // from_phone on inbound rows. Outbound rows carry it as to_phone.
        $phones = DB::table('metabot_events')
            ->where('direction', 'in')
            ->whereNotNull('from_phone')
            ->distinct()
            ->pluck('from_phone');

        $statuses = MetabotConversation::pluck('status', 'phone');
        $names    = DB::table('metabot_contacts')->pluck('name', 'phone');

        $conversations = $phones->map(function ($phone) use ($statuses, $names) {
            $last = DB::table('metabot_events')
                ->where(function ($q) use ($phone) {
                    $q->where('from_phone', $phone)->orWhere('to_phone', $phone);
                })
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->first();

            // Pending = the customer's last message has no customer-facing reply after it.
            $lastInboundId = DB::table('metabot_events')
                ->where('from_phone', $phone)->where('direction', 'in')
                ->max('id');
            $lastReplyId = DB::table('metabot_events')
                ->where('to_phone', $phone)->where('direction', 'out')
                ->whereIn('kind', self::CUSTOMER_FACING)
                ->max('id');
            $pending = $lastInboundId && (!$lastReplyId || $lastInboundId > $lastReplyId);

            return (object) [
                'phone'          => $phone,
                'name'           => $names[$phone] ?? null,
                'last_at'        => $last->created_at ?? null,
                'last_body'      => $this->previewText($last),
                'last_direction' => $last->direction ?? null,
                'status'         => $statuses[$phone] ?? null,
                'pending'        => (bool) $pending,
            ];
        })->sort(function ($a, $b) {
            // Pending first, then newest activity. Explicit comparator so ordering is
            // stable on PHP 7.4 (where sort() isn't guaranteed stable).
            if ($a->pending !== $b->pending) {
                return $a->pending ? -1 : 1;
            }
            return strcmp((string) $b->last_at, (string) $a->last_at);
        })->values();

        $pendingCount = $conversations->where('pending', true)->count();

        return view('metabot.inbox.index', compact('conversations', 'pendingCount'));
    }

    public function show($phone)
    {
        $messages  = $this->threadFor($phone);
        $templates = MetabotTemplate::where('status', 'active')->orderBy('label')->orderBy('name')->get();
        $name      = DB::table('metabot_contacts')->where('phone', $phone)->value('name');
        $quickMenu = $this->buildQuickMenu();

        return view('metabot.inbox.show', compact('phone', 'messages', 'templates', 'name', 'quickMenu'));
    }

    /**
     * Catalog drill-down for the quick-reply console: categories → products →
     * precomposed Price / Measurements text + photo URLs. Built read-only from
     * the catalog; a failure (e.g. RO user not provisioned) degrades to an empty
     * menu instead of breaking the chat page.
     *
     * @return array
     */
    private function buildQuickMenu(): array
    {
        try {
            $catalog = app(MetabotCatalog::class)->everything();
        } catch (\Throwable $e) {
            Log::warning('metabot: buildQuickMenu failed', ['error' => $e->getMessage()]);
            return [];
        }

        $menu = [];
        foreach ($catalog as $group) {
            $products = [];
            foreach ($group['products'] as $p) {
                $products[] = [
                    'id'     => $p['id'],
                    'nombre' => $p['nombre'],
                    'groups' => $this->composeGroups($p),
                    'images' => $this->imagesFor($p),
                ];
            }
            $menu[] = ['categoria' => $group['categoria'], 'products' => $products];
        }

        return $menu;
    }

    /**
     * Per-product quick-reply buttons, one per variant tag-group.
     *
     * The button set is derived from the union of the product's descriptive tags
     * (image_N / pivot / categoria are already stripped by the catalog). Two
     * families collapse to a single button: every `medidas_*` tag becomes one
     * "Medidas", and the `image_*` photos become one "Fotos". Every other tag
     * (color, talla, material, …) gets its own button. Price is prepended as the
     * most-used quick reply even though it isn't a tag.
     *
     * Each group is {key, label, type: 'text'|'photos', text?}. 'text' groups
     * prefill the reply box; the 'photos' group opens the send-photos preview.
     *
     * @return array<array{key:string,label:string,type:string,text?:string}>
     */
    private function composeGroups(array $p): array
    {
        $groups = [];

        $price = $this->composePrice($p);
        if ($price !== null) {
            $groups[] = ['key' => 'precio', 'label' => '💰 Precio', 'type' => 'text', 'text' => $price];
        }

        // Union of descriptive tag names across the product and its variants.
        $tagKeys = [];
        foreach (array_keys($p['tags']) as $t) {
            $tagKeys[$t] = true;
        }
        foreach ($p['variants'] as $v) {
            foreach (array_keys($v['tags']) as $t) {
                $tagKeys[$t] = true;
            }
        }

        // medidas_* collapse into one "Medidas"; everything else is its own group.
        $hasMedidas = false;
        $attrs      = [];
        foreach (array_keys($tagKeys) as $tag) {
            if (strpos($tag, 'medidas_') === 0) {
                $hasMedidas = true;
                continue;
            }
            $attrs[$tag] = true;
        }

        $dynamic = [];
        if ($hasMedidas) {
            $text = $this->composeMedidas($p);
            if ($text !== null) {
                $dynamic['medidas'] = ['key' => 'medidas', 'label' => '📏 Medidas', 'type' => 'text', 'text' => $text];
            }
        }
        foreach (array_keys($attrs) as $tag) {
            $text = $this->composeAttribute($p, $tag);
            if ($text !== null) {
                $dynamic[$tag] = ['key' => $tag, 'label' => $this->prettyLabel($tag), 'type' => 'text', 'text' => $text];
            }
        }
        ksort($dynamic);
        foreach ($dynamic as $g) {
            $groups[] = $g;
        }

        // image_* collapse into one "Fotos" (sent, not prefilled).
        if (!empty($this->imagesFor($p))) {
            $groups[] = ['key' => 'fotos', 'label' => '📷 Fotos', 'type' => 'photos'];
        }

        return $groups;
    }

    private function composePrice(array $p): ?string
    {
        if ($p['precio_min'] === null) {
            return null;
        }
        $line = $p['precio_min'] == $p['precio_max']
            ? "💰 {$p['nombre']}: Q" . $this->money($p['precio_min'])
            : "💰 {$p['nombre']}: desde Q" . $this->money($p['precio_min']) . ' hasta Q' . $this->money($p['precio_max']);

        if (!empty($p['agotado_todo'])) {
            $line .= ' (agotado)';
        }

        return $line;
    }

    /**
     * The "Medidas" reply: only the medidas_* tags, grouped by the pivot value
     * (e.g. one line per talla) when the product has a pivot, otherwise a flat list.
     */
    private function composeMedidas(array $p): ?string
    {
        $lines = [];

        if (!empty($p['pivot'])) {
            $seen = [];
            foreach ($p['variants'] as $v) {
                $pv = $v['pivot_valor'];
                if ($pv === null || isset($seen[$pv])) {
                    continue;
                }
                $seen[$pv] = true;

                $measures = [];
                foreach ($v['tags'] as $tag => $val) {
                    if (strpos($tag, 'medidas_') === 0 && $val !== null && $val !== '') {
                        $measures[] = $this->prettyMeasureLabel($tag) . ": {$val}";
                    }
                }
                if ($measures) {
                    $label   = ucfirst($p['pivot']) . " {$pv}";
                    $lines[] = "• {$label} — " . implode(', ', $measures);
                }
            }
        } else {
            $tags = !empty($p['tags']) ? $p['tags'] : (!empty($p['variants']) ? $p['variants'][0]['tags'] : []);
            foreach ($tags as $tag => $val) {
                if (strpos($tag, 'medidas_') === 0 && $val !== null && $val !== '') {
                    $lines[] = '• ' . $this->prettyMeasureLabel($tag) . ": {$val}";
                }
            }
        }

        if (empty($lines)) {
            return null;
        }

        return "📏 Medidas de {$p['nombre']}:\n" . implode("\n", $lines);
    }

    /**
     * A single descriptive tag (color, talla, material, …) as a quick reply:
     * the distinct values that tag takes across the product and its variants.
     */
    private function composeAttribute(array $p, string $tag): ?string
    {
        $values = [];
        if (isset($p['tags'][$tag]) && $p['tags'][$tag] !== '') {
            $values[] = $p['tags'][$tag];
        }
        foreach ($p['variants'] as $v) {
            if (isset($v['tags'][$tag]) && $v['tags'][$tag] !== null && $v['tags'][$tag] !== '') {
                $values[] = $v['tags'][$tag];
            }
        }
        $values = array_values(array_unique($values));
        if (empty($values)) {
            return null;
        }

        return $this->prettyLabel($tag) . " de {$p['nombre']}: " . implode(', ', $values);
    }

    // "color" → "Color", "tipo_correa" → "Tipo correa".
    private function prettyLabel(string $tag): string
    {
        return ucfirst(str_replace('_', ' ', $tag));
    }

    // "medidas_cuello_cm" → "cuello (cm)".
    private function prettyMeasureLabel(string $tag): string
    {
        $s = preg_replace('/^medidas_/', '', $tag);
        $s = preg_replace('/_(cm|mm|m|in|kg|g|lb|ml|l)$/', ' ($1)', $s);

        return str_replace('_', ' ', $s);
    }

    /**
     * Representative photo URLs: product-level first, then one per variant,
     * deduped and capped at 10.
     *
     * @return array<string>
     */
    private function imagesFor(array $p): array
    {
        $images = $p['images'];
        foreach ($p['variants'] as $v) {
            foreach ($v['images'] as $u) {
                $images[] = $u;
            }
        }

        return array_slice(array_values(array_unique(array_filter($images))), 0, 10);
    }

    private function money($value): string
    {
        $value = (float) $value;

        return $value == (int) $value
            ? number_format($value, 0, '.', ',')
            : number_format($value, 2, '.', ',');
    }

    /**
     * Send a product's catalog photos by URL (the staff tapped "Fotos" and
     * confirmed). Stores a local thumbnail per image so the thread shows it.
     */
    public function quickPhotos(Request $request, WhatsAppClient $whatsapp, $phone)
    {
        $request->validate(['product_id' => ['required', 'integer']]);

        $p = app(MetabotCatalog::class)->products([$request->input('product_id')])[0] ?? null;
        if (!$p) {
            return redirect()->route('metabot.inbox.show', ['phone' => $phone])->with('error', 'Producto no encontrado.');
        }

        $images = $this->imagesFor($p);
        if (empty($images)) {
            return redirect()->route('metabot.inbox.show', ['phone' => $phone])->with('error', 'Este producto no tiene fotos.');
        }

        $sent = 0;
        foreach ($images as $url) {
            $resp = $whatsapp->sendImageByUrl($phone, $url, $p['nombre']);
            if (($resp['status'] ?? 0) !== 200 || !data_get($resp, 'body.messages.0.id')) {
                continue;
            }

            // Keep a local copy so the thread renders a thumbnail (best-effort).
            $mediaPath = null;
            try {
                $bin = Http::timeout(15)->get($url);
                if ($bin->successful()) {
                    $mediaPath = $whatsapp->putMedia($bin->body(), $bin->header('Content-Type'));
                }
            } catch (\Throwable $e) {
                Log::warning('metabot: quickPhotos thumbnail fetch failed', ['url' => $url, 'error' => $e->getMessage()]);
            }

            DB::table('metabot_events')->insert([
                'wa_message_id' => data_get($resp, 'body.messages.0.id'),
                'direction'     => 'out',
                'from_phone'    => null,
                'to_phone'      => $phone,
                'kind'          => 'human_image',
                'body'          => '[imagen] ' . $p['nombre'],
                'media_path'    => $mediaPath,
                'payload'       => json_encode($resp, JSON_UNESCAPED_UNICODE),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
            $sent++;
        }

        if ($sent === 0) {
            return redirect()->route('metabot.inbox.show', ['phone' => $phone])
                ->with('error', 'No se pudieron enviar las fotos (posible ventana de 24h vencida).');
        }

        $conv = MetabotConversation::firstOrNew(['phone' => $phone]);
        $conv->status = 'handed_off';
        $conv->last_message_at = now();
        $conv->save();

        return redirect()->route('metabot.inbox.show', ['phone' => $phone])
            ->with('success', $sent . ' foto(s) enviada(s).');
    }

    // Stream a stored media file inline (behind the inbox's role gate).
    public function media($id)
    {
        $event = DB::table('metabot_events')->where('id', $id)->whereNotNull('media_path')->first();
        abort_unless($event, 404);

        $path = storage_path('app/' . $event->media_path);
        abort_unless(is_file($path), 404);

        return response()->file($path);
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

    // Send an approved template — the only way to reopen a chat past the 24h window.
    public function sendTemplate(Request $request, WhatsAppClient $whatsapp, $phone)
    {
        $request->validate(['template_id' => ['required', 'integer']]);
        $template = MetabotTemplate::where('status', 'active')->findOrFail($request->input('template_id'));

        $resp      = $whatsapp->sendTemplate($phone, $template->name, $template->language);
        $messageId = data_get($resp, 'body.messages.0.id');
        $ok        = $resp['status'] === 200 && $messageId;

        if (!$ok) {
            $err = data_get($resp, 'body.error.message', 'No se pudo enviar la plantilla.');

            return redirect()->route('metabot.inbox.show', ['phone' => $phone])->with('error', $err);
        }

        DB::table('metabot_events')->insert([
            'wa_message_id' => $messageId,
            'direction'     => 'out',
            'from_phone'    => null,
            'to_phone'      => $phone,
            'kind'          => 'template',
            'body'          => $template->body_preview ?: ('[plantilla: ' . $template->name . ']'),
            'payload'       => json_encode($resp, JSON_UNESCAPED_UNICODE),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        $conv = MetabotConversation::firstOrNew(['phone' => $phone]);
        $conv->status = 'handed_off';
        $conv->last_message_at = now();
        $conv->save();

        return redirect()->route('metabot.inbox.show', ['phone' => $phone])
            ->with('success', 'Plantilla enviada. Espera la respuesta del cliente para reabrir la ventana de 24h.');
    }

    // Upload an image to Meta and send it to the customer (free-form: 24h window only).
    public function sendImage(Request $request, WhatsAppClient $whatsapp, $phone)
    {
        $request->validate([
            'image'   => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'caption' => ['nullable', 'string', 'max:1024'],
        ]);

        $file   = $request->file('image');
        $upload = $whatsapp->uploadMedia($file->getRealPath(), $file->getMimeType(), $file->getClientOriginalName());
        $mediaId = data_get($upload, 'body.id');

        if ($upload['status'] !== 200 || !$mediaId) {
            $err = data_get($upload, 'body.error.message', 'No se pudo subir la imagen.');

            return redirect()->route('metabot.inbox.show', ['phone' => $phone])->with('error', $err);
        }

        // Keep a local copy so the thread can show the thumbnail we sent.
        $localPath = $whatsapp->putMedia(file_get_contents($file->getRealPath()), $file->getMimeType());

        $caption = $request->input('caption');
        $resp      = $whatsapp->sendImageById($phone, $mediaId, $caption);
        $messageId = data_get($resp, 'body.messages.0.id');
        $ok        = $resp['status'] === 200 && $messageId;

        if (!$ok) {
            $err = data_get($resp, 'body.error.message', 'No se pudo enviar la imagen (posible ventana de 24h vencida).');

            return redirect()->route('metabot.inbox.show', ['phone' => $phone])->with('error', $err);
        }

        DB::table('metabot_events')->insert([
            'wa_message_id' => $messageId,
            'direction'     => 'out',
            'from_phone'    => null,
            'to_phone'      => $phone,
            'kind'          => 'human_image',
            'body'          => '[imagen]' . ($caption ? ' ' . $caption : ''),
            'media_path'    => $localPath,
            'payload'       => json_encode($resp, JSON_UNESCAPED_UNICODE),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

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
