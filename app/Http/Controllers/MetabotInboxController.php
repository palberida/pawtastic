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
    // "Bandeja" entry point. The standalone list page is gone — the chat page now
    // carries the conversation list in its sidebar — so this just opens the chat on
    // the top conversation (pending/newest first). Empty inbox renders the shell.
    public function index()
    {
        $conversations = $this->conversationList();
        $first = $conversations->first();

        if ($first) {
            return redirect()->route('metabot.inbox.show', ['phone' => $first->phone]);
        }

        return view('metabot.inbox.show', [
            'phone'         => null,
            'messages'      => collect(),
            'templates'     => collect(),
            'name'          => null,
            'quickMenu'     => [],
            'conversations' => $conversations,
        ]);
    }

    /**
     * The inbox conversation list (newest/pending first). Shared by the bandeja
     * index and the in-chat sidebar.
     */
    private function conversationList()
    {
        // A conversation is keyed by the customer phone, which appears as
        // from_phone on inbound rows. Outbound rows carry it as to_phone.
        $phones = DB::table('metabot_events')
            ->where('direction', 'in')
            ->whereNotNull('from_phone')
            ->distinct()
            ->pluck('from_phone');

        $statuses = MetabotConversation::pluck('status', 'phone');
        // Raw strings (not the model, whose datetime cast would yield Carbon) so we
        // can string-compare against metabot_events.created_at directly.
        $reads    = DB::table('metabot_conversations')->pluck('last_read_at', 'phone');
        $names    = DB::table('metabot_contacts')->pluck('name', 'phone');

        return $phones->map(function ($phone) use ($statuses, $reads, $names) {
            $last = DB::table('metabot_events')
                ->where(function ($q) use ($phone) {
                    $q->where('from_phone', $phone)->orWhere('to_phone', $phone);
                })
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->first();

            // Pending (unread) = the customer's last message is newer than the last
            // time staff opened the chat (last_read_at). Opening sets last_read_at so
            // it clears even without a reply; a newer inbound or "mark as unread"
            // (which nulls last_read_at) re-flags it.
            $lastInbound = DB::table('metabot_events')
                ->where('from_phone', $phone)->where('direction', 'in')
                ->max('created_at');
            $readAt  = $reads[$phone] ?? null;
            $pending = $lastInbound && (!$readAt || $lastInbound > $readAt);

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
            // Strictly newest activity first — no pending-priority reordering.
            return strcmp((string) $b->last_at, (string) $a->last_at);
        })->values();
    }

    public function show(Request $request, $phone)
    {
        // Opening a chat marks it read — unless we just came back from "mark as
        // unread" (?unread=1), which would otherwise immediately re-read it.
        if (!$request->boolean('unread')) {
            $this->touchRead($phone);
        }

        $messages      = $this->threadFor($phone);
        $templates     = MetabotTemplate::where('status', 'active')->orderBy('label')->orderBy('name')->get();
        $name          = DB::table('metabot_contacts')->where('phone', $phone)->value('name');
        $quickMenu     = $this->buildQuickMenu();
        $conversations = $this->conversationList(); // left sidebar to hop between chats

        return view('metabot.inbox.show', compact('phone', 'messages', 'templates', 'name', 'quickMenu', 'conversations'));
    }

    // Mark a chat read (it's been opened). Preserves any existing conversation state.
    private function touchRead($phone): void
    {
        $conv = MetabotConversation::firstOrNew(['phone' => $phone]);
        $conv->last_read_at = now();
        $conv->save();
    }

    // Re-flag a chat as unread (opened by accident). Nulling last_read_at makes it
    // pending again; the ?unread=1 redirect keeps show() from re-reading it on land.
    public function markUnread($phone)
    {
        $conv = MetabotConversation::firstOrNew(['phone' => $phone]);
        $conv->last_read_at = null;
        $conv->save();

        return redirect()->route('metabot.inbox.show', ['phone' => $phone, 'unread' => 1])
            ->with('success', 'Conversación marcada como no leída.');
    }

    /**
     * Data for the quick-reply console: categories → products → tag-narrowing drill.
     *
     * The drill itself (scan variant tags → if one value, prefill; if many, show
     * the values and narrow, then re-scan) runs client-side over the variant data
     * shipped here — the catalog is small and the narrowing is a tree-walk. So per
     * product we ship the product-level terminal tags plus every variant's tags,
     * price, stock flag and photo URLs.
     *
     * Built read-only; a failure (e.g. RO user not provisioned) degrades to an
     * empty menu instead of breaking the chat page.
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
                $products[] = $this->productForMenu($p);
            }
            $menu[] = ['categoria' => $group['categoria'], 'products' => $products];
        }

        return $menu;
    }

    /**
     * Shape one catalog product for the drill: product-level terminal tags (row 1)
     * plus the raw variant data the client narrows over.
     */
    private function productForMenu(array $p): array
    {
        // Row 1: product-level tags. These apply to the whole product, so they are
        // terminal (click → value). Structured tags are NOT terminal: a JSON array
        // value is photos (→ Fotos) and a JSON object value is measurements
        // (→ Medidas); medidas_* and image_N / pivot / categoria are likewise not
        // plain row-1 tags.
        $productTags = [];
        foreach ($p['tags'] as $tag => $val) {
            if ($val === null || $val === '' || strpos($tag, 'medidas_') === 0 || $this->isStructured($val)) {
                continue;
            }
            $productTags[] = [
                'label' => $this->prettyLabel($tag),
                'text'  => $this->prettyLabel($tag) . ' de ' . $p['nombre'] . ': ' . $val,
            ];
        }

        $variants = [];
        foreach ($p['variants'] as $v) {
            $variants[] = [
                'tags'        => (object) $v['tags'], // force a JSON object even when empty
                'pivot_valor' => $v['pivot_valor'],
                'precio'      => $v['precio'],
                'agotado'     => $v['agotado'],
                'images'      => $v['images'],
            ];
        }

        return [
            'id'             => $p['id'],
            'nombre'         => $p['nombre'],
            'pivot'          => $p['pivot'],
            'pivot_label'    => $p['pivot'] ? $this->prettyLabel($p['pivot']) : null,
            'product_tags'   => $productTags,
            'product_images' => array_values(array_unique(array_merge($p['images'], $this->urlsFromTags($p['tags'])))),
            'variants'       => $variants,
        ];
    }

    // "color" → "Color", "tipo_correa" → "Tipo correa".
    private function prettyLabel(string $tag): string
    {
        return ucfirst(str_replace('_', ' ', $tag));
    }

    // A tag value that is a JSON array or object (e.g. images list / measures map)
    // rather than a plain scalar.
    private function isStructured($val): bool
    {
        if (!is_string($val)) {
            return false;
        }
        $s = ltrim($val);
        if ($s === '' || ($s[0] !== '[' && $s[0] !== '{')) {
            return false;
        }
        $decoded = json_decode($s, true);

        return is_array($decoded);
    }

    /**
     * Photo URLs carried inside tags whose value is a JSON array of URLs
     * (the `images` tag convention), in addition to image_N tags handled upstream.
     *
     * @return array<string>
     */
    private function urlsFromTags(array $tags): array
    {
        $urls = [];
        foreach ($tags as $val) {
            if (!is_string($val)) {
                continue;
            }
            $s = ltrim($val);
            if ($s === '' || $s[0] !== '[') {
                continue;
            }
            $decoded = json_decode($s, true);
            if (!is_array($decoded)) {
                continue;
            }
            foreach ($decoded as $u) {
                if (is_string($u) && preg_match('#^https?://#', $u)) {
                    $urls[] = $u;
                }
            }
        }

        return $urls;
    }

    /**
     * Every photo URL that legitimately belongs to a product (product-level plus
     * all variants), deduped. Used as the allow-list when the staff sends photos —
     * it is NOT capped, because the staff may have narrowed to a variant whose
     * photos sort past any cap; the actual send is bounded at the call site.
     * Covers both image_N tags and the `images` JSON array convention.
     *
     * @return array<string>
     */
    private function imagesFor(array $p): array
    {
        $images = array_merge($p['images'], $this->urlsFromTags($p['tags']));
        foreach ($p['variants'] as $v) {
            foreach ($v['images'] as $u) {
                $images[] = $u;
            }
            $images = array_merge($images, $this->urlsFromTags($v['tags']));
        }

        return array_values(array_unique(array_filter($images)));
    }

    /**
     * Send a product's catalog photos by URL (the staff tapped "Fotos" and
     * confirmed). Stores a local thumbnail per image so the thread shows it.
     */
    public function quickPhotos(Request $request, WhatsAppClient $whatsapp, $phone)
    {
        $request->validate([
            'product_id' => ['required', 'integer'],
            'images'     => ['array'],
            'images.*'   => ['string'],
        ]);

        $p = app(MetabotCatalog::class)->products([$request->input('product_id')])[0] ?? null;
        if (!$p) {
            return redirect()->route('metabot.inbox.show', ['phone' => $phone])->with('error', 'Producto no encontrado.');
        }

        // Only ever send URLs that belong to this product's catalog photos. Keep the
        // staff's selection and order (the client posts the scoped/edited subset);
        // fall back to all of the product's photos if nothing was posted. The send
        // is capped here, not in the allow-list, so a scoped photo can't be dropped.
        $allowed   = array_flip($this->imagesFor($p));
        $requested = $request->input('images', []);
        if (!empty($requested)) {
            $images = array_values(array_filter($requested, function ($u) use ($allowed) {
                return is_string($u) && isset($allowed[$u]);
            }));
        } else {
            $images = array_keys($allowed);
        }
        $images = array_slice($images, 0, 10);
        if (empty($images)) {
            return redirect()->route('metabot.inbox.show', ['phone' => $phone])->with('error', 'Este producto no tiene fotos.');
        }

        // Single product: no caption — the customer already knows which product.
        // (The category sender captions each image with its product name.)
        return $this->dispatchPhotos($whatsapp, $phone, $images, []);
    }

    /**
     * Send one representative photo per product in a category (the staff tapped
     * "Fotos de la categoría" and confirmed). The client posts the product ids it
     * scoped and the cover image it chose per product; we validate every URL
     * against those products' catalog photos before sending.
     */
    public function quickCategoryPhotos(Request $request, WhatsAppClient $whatsapp, $phone)
    {
        $request->validate([
            'product_ids'   => ['required', 'array'],
            'product_ids.*' => ['integer'],
            'images'        => ['required', 'array'],
            'images.*'      => ['string'],
        ]);

        $products = app(MetabotCatalog::class)->products($request->input('product_ids', []));
        if (empty($products)) {
            return redirect()->route('metabot.inbox.show', ['phone' => $phone])->with('error', 'Categoría no encontrada.');
        }

        // Allow-list = every catalog photo across the posted products, mapped back to
        // the product name so each image keeps a sensible caption.
        $captions = [];
        foreach ($products as $p) {
            foreach ($this->imagesFor($p) as $url) {
                if (!isset($captions[$url])) {
                    $captions[$url] = $p['nombre'];
                }
            }
        }

        $images = array_values(array_filter($request->input('images', []), function ($u) use ($captions) {
            return is_string($u) && isset($captions[$u]);
        }));
        $images = array_slice($images, 0, 30);
        if (empty($images)) {
            return redirect()->route('metabot.inbox.show', ['phone' => $phone])->with('error', 'No hay fotos para enviar.');
        }

        return $this->dispatchPhotos($whatsapp, $phone, $images, $captions);
    }

    /**
     * Send a list of (already allow-listed) image URLs to the customer, logging a
     * human_image row with a best-effort local thumbnail per image and handing the
     * conversation off. Shared by the single-product and category photo senders.
     */
    private function dispatchPhotos(WhatsAppClient $whatsapp, string $phone, array $images, array $captions)
    {
        $sent = 0;
        foreach ($images as $url) {
            $caption = $captions[$url] ?? null;
            $resp = $whatsapp->sendImageByUrl($phone, $url, $caption);
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
                Log::warning('metabot: dispatchPhotos thumbnail fetch failed', ['url' => $url, 'error' => $e->getMessage()]);
            }

            DB::table('metabot_events')->insert([
                'wa_message_id' => data_get($resp, 'body.messages.0.id'),
                'direction'     => 'out',
                'from_phone'    => null,
                'to_phone'      => $phone,
                'kind'          => 'human_image',
                'body'          => '[imagen]' . ($caption ? ' ' . $caption : ''),
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

    // Upload one or more images from the device to Meta and send them to the
    // customer (free-form: 24h window only). The caption (if any) rides the first.
    public function sendImage(Request $request, WhatsAppClient $whatsapp, $phone)
    {
        $request->validate([
            'images'   => ['required', 'array', 'min:1', 'max:10'],
            'images.*' => ['file', 'mimes:jpg,jpeg,png', 'max:5120'],
            'caption'  => ['nullable', 'string', 'max:1024'],
        ]);

        $files   = $request->file('images');
        $caption = $request->input('caption');
        $sent    = 0;
        $lastErr = null;

        foreach (array_values($files) as $i => $file) {
            $upload  = $whatsapp->uploadMedia($file->getRealPath(), $file->getMimeType(), $file->getClientOriginalName());
            $mediaId = data_get($upload, 'body.id');
            if (($upload['status'] ?? 0) !== 200 || !$mediaId) {
                $lastErr = data_get($upload, 'body.error.message', 'No se pudo subir la imagen.');
                continue;
            }

            // Keep a local copy so the thread can show the thumbnail we sent.
            $localPath = $whatsapp->putMedia(file_get_contents($file->getRealPath()), $file->getMimeType());

            $thisCaption = $i === 0 ? $caption : null; // caption only on the first
            $resp        = $whatsapp->sendImageById($phone, $mediaId, $thisCaption);
            $messageId   = data_get($resp, 'body.messages.0.id');
            if (($resp['status'] ?? 0) !== 200 || !$messageId) {
                $lastErr = data_get($resp, 'body.error.message', 'No se pudo enviar la imagen (posible ventana de 24h vencida).');
                continue;
            }

            DB::table('metabot_events')->insert([
                'wa_message_id' => $messageId,
                'direction'     => 'out',
                'from_phone'    => null,
                'to_phone'      => $phone,
                'kind'          => 'human_image',
                'body'          => '[imagen]' . ($thisCaption ? ' ' . $thisCaption : ''),
                'media_path'    => $localPath,
                'payload'       => json_encode($resp, JSON_UNESCAPED_UNICODE),
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
            $sent++;
        }

        if ($sent === 0) {
            return redirect()->route('metabot.inbox.show', ['phone' => $phone])
                ->with('error', $lastErr ?: 'No se pudieron enviar las imágenes.');
        }

        $conv = MetabotConversation::firstOrNew(['phone' => $phone]);
        $conv->status = 'handed_off';
        $conv->last_message_at = now();
        $conv->save();

        return redirect()->route('metabot.inbox.show', ['phone' => $phone])
            ->with('success', $sent . ' imagen(es) enviada(s).');
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
            return $this->cropPreview($row->body);
        }
        if (!empty($row->button_title)) {
            return $this->cropPreview($row->button_title);
        }

        return '[' . ($row->kind ?? 'evento') . ']';
    }

    // Sidebar previews show only a teaser: collapse newlines/runs of whitespace to
    // single spaces and cap the length so a long message (e.g. medidas) can't blow
    // out the conversation list.
    private function cropPreview(string $text): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', $text));

        return \Illuminate\Support\Str::limit($text, 60);
    }
}
