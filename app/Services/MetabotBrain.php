<?php

namespace App\Services;

use App\Mail\MetabotEscalation;
use App\Models\MetabotAd;
use App\Models\MetabotConversation;
use App\Models\MetabotFaq;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * The conversational core. For an active, ad-originated conversation it assembles
 * the catalog context + full transcript, asks Claude for a tool call, and executes
 * whatever tool(s) Claude chose. No tool call = silence (the safety default).
 *
 * Honors config('metabot.shadow_mode'): when true, Claude still runs but its chosen
 * actions are only logged — nothing is sent and no side effects are applied.
 */
class MetabotBrain
{
    /** @var ClaudeClient */
    private $claude;

    /** @var MetabotCatalog */
    private $catalog;

    public function __construct(ClaudeClient $claude, MetabotCatalog $catalog)
    {
        $this->claude  = $claude;
        $this->catalog = $catalog;
    }

    public function handle(MetabotConversation $conv, WhatsAppClient $whatsapp): void
    {
        $ad = $conv->current_ad_id ? MetabotAd::find($conv->current_ad_id) : null;
        if (!$ad) {
            Log::warning('metabot: brain invoked without an active ad', ['phone' => $conv->phone]);
            return;
        }

        $messages = $this->buildMessages($conv->phone);
        $faqs     = MetabotFaq::where('status', 'active')->orderBy('topic')->get();
        $system   = $this->systemPrompt($ad, $this->catalog->forAd($ad), $faqs);

        $resp = $this->claude->respond($system, $messages, $this->tools());

        if (($resp['status'] ?? 0) !== 200) {
            // Claude API error → fall back to the Phase 1 buttons so the customer isn't left hanging.
            Log::error('metabot: Claude API error, sending buttons fallback', [
                'phone'  => $conv->phone,
                'status' => $resp['status'] ?? null,
                'body'   => $resp['body'] ?? null,
            ]);
            if (!config('metabot.shadow_mode')) {
                $whatsapp->sendButtons($conv->phone, config('metabot.buttons_body'), config('metabot.buttons'));
            }
            return;
        }

        $blocks = data_get($resp, 'body.content', []);
        $toolUses = array_values(array_filter($blocks, fn ($b) => ($b['type'] ?? null) === 'tool_use'));

        if (empty($toolUses)) {
            // No tool call = silence. This is intentional, not a failure.
            Log::info('metabot: Claude chose silence', ['phone' => $conv->phone]);
            return;
        }

        foreach ($toolUses as $tool) {
            $this->executeTool($tool['name'] ?? '', $tool['input'] ?? [], $conv, $ad, $whatsapp, $messages);
        }
    }

    /**
     * Run one tool Claude asked for. In shadow mode the intended action is logged
     * and nothing happens.
     */
    private function executeTool(string $name, array $input, MetabotConversation $conv, MetabotAd $ad, WhatsAppClient $whatsapp, array $messages): void
    {
        if (config('metabot.shadow_mode')) {
            Log::info('metabot[shadow]: would run tool', [
                'phone' => $conv->phone,
                'tool'  => $name,
                'input' => $input,
            ]);
            return;
        }

        try {
            switch ($name) {
                case 'send_text':
                    $text = trim((string) ($input['text'] ?? ''));
                    if ($text === '') {
                        return;
                    }
                    $resp = $whatsapp->sendText($conv->phone, $text);
                    $this->logOut($conv, 'bot_text', $text, $resp);
                    break;

                case 'send_list':
                    $body   = (string) ($input['body'] ?? '');
                    $button = (string) ($input['button'] ?? 'Ver opciones');
                    $rows   = $input['rows'] ?? [];
                    if (!is_array($rows) || empty($rows)) {
                        return;
                    }
                    $resp = $whatsapp->sendList($conv->phone, $body, $button, $rows);
                    $this->logOut($conv, 'bot_list', $body !== '' ? $body : '[lista]', $resp);
                    break;

                case 'send_images':
                    $images = $input['images'] ?? [];
                    if (!is_array($images)) {
                        return;
                    }
                    foreach (array_slice($images, 0, 10) as $img) {
                        $url = (string) ($img['url'] ?? '');
                        if ($url === '') {
                            continue;
                        }
                        $caption = isset($img['caption']) ? (string) $img['caption'] : null;
                        $resp = $whatsapp->sendImageByUrl($conv->phone, $url, $caption);
                        $this->logOut($conv, 'bot_image', '[imagen]' . ($caption ? ' ' . $caption : ''), $resp);
                    }
                    break;

                case 'send_faq':
                    $faq = MetabotFaq::where('status', 'active')->find($input['faq_id'] ?? null);
                    if (!$faq) {
                        Log::warning('metabot: send_faq for unknown/inactive faq', ['faq_id' => $input['faq_id'] ?? null]);
                        return;
                    }
                    $resp = $whatsapp->sendText($conv->phone, $faq->answer_text);
                    $this->logOut($conv, 'bot_faq', $faq->answer_text, $resp);
                    break;

                case 'escalate_to_human':
                    $reason = trim((string) ($input['reason'] ?? 'Sin motivo especificado.'));
                    $this->escalate($conv, $ad, $reason, $messages);
                    break;

                default:
                    Log::warning('metabot: unknown tool requested', ['tool' => $name]);
            }
        } catch (Throwable $e) {
            Log::error('metabot: tool execution failed', [
                'tool'  => $name,
                'phone' => $conv->phone,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Email staff with full context, then mark the conversation handed_off so the
     * bot goes quiet on it permanently (a new ad click can re-wake it).
     */
    private function escalate(MetabotConversation $conv, MetabotAd $ad, string $reason, array $messages): void
    {
        $recipients = config('metabot.escalation_emails', []);
        if (!empty($recipients)) {
            try {
                Mail::to($recipients)->send(new MetabotEscalation(
                    $conv->phone,
                    $ad->name,
                    $ad->source_id,
                    $reason,
                    $messages
                ));
            } catch (Throwable $e) {
                Log::error('metabot: escalation email failed', ['error' => $e->getMessage()]);
            }
        }

        $conv->status = 'handed_off';
        $conv->save();

        $this->logOut($conv, 'bot_escalate', '↗ Escalado a un humano: ' . $reason);
    }

    /**
     * Build the Anthropic message list from the stored event transcript.
     * Inbound → user, outbound → assistant. Consecutive same-role turns are merged,
     * leading assistant turns dropped, and a synthetic opener appended if the last
     * turn isn't the customer's (e.g. the <from_ad> simulator cue, which is skipped).
     *
     * @return array<array{role:string,text:string,content:string}>
     */
    private function buildMessages(string $phone): array
    {
        $events = DB::table('metabot_events')
            ->where(function ($q) use ($phone) {
                $q->where('from_phone', $phone)->orWhere('to_phone', $phone);
            })
            ->whereIn('direction', ['in', 'out'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get(['direction', 'body']);

        $turns = [];
        foreach ($events as $e) {
            $text = trim((string) ($e->body ?? ''));
            if ($text === '' || $text === '<from_ad>') {
                continue;
            }
            $role = $e->direction === 'in' ? 'user' : 'assistant';

            // Merge consecutive same-role turns.
            if (!empty($turns) && $turns[count($turns) - 1]['role'] === $role) {
                $turns[count($turns) - 1]['text'] .= "\n" . $text;
            } else {
                $turns[] = ['role' => $role, 'text' => $text];
            }
        }

        // The first turn must be the user's.
        while (!empty($turns) && $turns[0]['role'] !== 'user') {
            array_shift($turns);
        }

        // Ensure Claude has a customer turn to respond to.
        if (empty($turns) || end($turns)['role'] !== 'user') {
            $turns[] = ['role' => 'user', 'text' => '[el cliente abrió el chat desde el anuncio]'];
        }

        return array_map(fn ($t) => [
            'role'    => $t['role'],
            'text'    => $t['text'],
            'content' => $t['text'],
        ], $turns);
    }

    private function logOut(MetabotConversation $conv, string $kind, string $body, ?array $resp = null): void
    {
        DB::table('metabot_events')->insert([
            'wa_message_id' => $resp ? data_get($resp, 'body.messages.0.id') : null,
            'direction'     => 'out',
            'from_phone'    => null,
            'to_phone'      => $conv->phone,
            'kind'          => $kind,
            'body'          => $body,
            'payload'       => $resp ? json_encode($resp, JSON_UNESCAPED_UNICODE) : null,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    /**
     * @return array Anthropic tool definitions
     */
    private function tools(): array
    {
        return [
            [
                'name'        => 'send_text',
                'description' => 'Enviar un mensaje de texto libre al cliente (saludo, precio, medidas, una respuesta corta). Úsalo para responder con claridad y brevedad en español.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'text' => ['type' => 'string', 'description' => 'El mensaje en español.'],
                    ],
                    'required' => ['text'],
                ],
            ],
            [
                'name'        => 'send_list',
                'description' => 'Enviar una lista interactiva (selector) cuando el cliente debe elegir entre opciones: categorías, productos o tallas. Máximo 10 filas.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'body'   => ['type' => 'string', 'description' => 'Texto que acompaña la lista.'],
                        'button' => ['type' => 'string', 'description' => 'Etiqueta del botón que abre la lista (máx. 20 caracteres), ej. "Ver tallas".'],
                        'rows'   => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'properties' => [
                                    'id'          => ['type' => 'string', 'description' => 'Identificador estable de la fila (ej. el código o id del producto/talla).'],
                                    'title'       => ['type' => 'string', 'description' => 'Texto visible (máx. 24 caracteres).'],
                                    'description' => ['type' => 'string', 'description' => 'Detalle opcional (máx. 72 caracteres), ej. precio o medidas.'],
                                ],
                                'required' => ['id', 'title'],
                            ],
                        ],
                    ],
                    'required' => ['body', 'button', 'rows'],
                ],
            ],
            [
                'name'        => 'send_images',
                'description' => 'Enviar una o varias fotos del catálogo por URL. Usa solo URLs que aparezcan en el contexto del catálogo. Las fotos se envían solo cuando el cliente las pide o necesita más detalle.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'images' => [
                            'type'  => 'array',
                            'items' => [
                                'type'       => 'object',
                                'properties' => [
                                    'url'     => ['type' => 'string', 'description' => 'URL HTTPS de la imagen (del catálogo).'],
                                    'caption' => ['type' => 'string', 'description' => 'Pie de foto opcional (ej. el nombre del producto o color).'],
                                ],
                                'required' => ['url'],
                            ],
                        ],
                    ],
                    'required' => ['images'],
                ],
            ],
            [
                'name'        => 'send_faq',
                'description' => 'Enviar la respuesta GUARDADA de una pregunta frecuente, palabra por palabra. Úsalo SOLO cuando la pregunta del cliente coincide con claridad con una FAQ del contexto. Nunca reformules el texto de la política.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'faq_id' => ['type' => 'integer', 'description' => 'El id de la FAQ que coincide (de la lista de FAQs del contexto).'],
                    ],
                    'required' => ['faq_id'],
                ],
            ],
            [
                'name'        => 'escalate_to_human',
                'description' => 'Pasar la conversación a un humano (envía un correo al equipo y el bot guarda silencio). Úsalo ante intención de compra, preguntas fuera del catálogo sin FAQ, mensajes no textuales, o cualquier duda. Ante la duda, escala.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'reason' => ['type' => 'string', 'description' => 'Motivo breve para el equipo (en español).'],
                    ],
                    'required' => ['reason'],
                ],
            ],
        ];
    }

    private function systemPrompt(MetabotAd $ad, array $catalog, $faqs): string
    {
        $catalogJson = json_encode($catalog, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $faqList = $faqs->map(fn ($f) => [
            'id'     => $f->id,
            'topic'  => $f->topic,
            'cubre'  => $f->trigger_description,
        ])->values();
        $faqJson = json_encode($faqList, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        $welcome = $ad->welcome_text ? "\nSaludo sugerido para este anuncio: \"{$ad->welcome_text}\"" : '';
        $scope   = $catalog['scope'] ?? 'product_set';

        return <<<PROMPT
Eres el asistente de ventas por WhatsApp de "ossu", una tienda guatemalteca de productos para mascotas. El cliente llegó a este chat desde un anuncio de Facebook/Instagram.

PRINCIPIO CENTRAL (lo más importante):
- Solo actúas llamando a una herramienta. Si NO llamas a ninguna herramienta, el cliente no recibe nada (silencio). Eso está bien cuando corresponde.
- Si no estás 100% seguro de la respuesta correcta, NO adivines: llama a escalate_to_human (o guarda silencio). Una respuesta equivocada es peor que no responder.
- Eres de SOLO LECTURA. No tomas pedidos, no confirmas precios fuera del catálogo, no inventas datos.
- Responde SIEMPRE en español (guatemalteco, cálido y breve), sin importar el idioma del cliente.

FLUJO POR MENSAJE:
1. Primer contacto: abre con un saludo breve y luego continúa.{$welcome}
2. Enruta según el alcance del anuncio (scope = "{$scope}"):
   - product_set con 1 producto: ve directo al "remate de producto" (paso 3).
   - product_set con 2–10 productos: envía send_images con UNA foto representativa por producto (pie = nombre del producto), luego send_list con filas nombre-solo. Al elegir, ve al remate.
   - site_wide: arma una send_list de categorías (valores distintos de "categoria", ≤10). Al elegir categoría, muestra sus productos como product_set (fotos + lista). Al elegir producto, ve al remate.
3. Remate de producto (según "pivot"):
   - pivot presente (ej. pivot="talla"): muestra una send_list de tallas con medidas en la descripción, para que el cliente elija por el tamaño de su mascota. Agrupa las variantes por el valor del pivot. Si el precio varía, dilo como rango ("desde Q__ hasta Q__").
   - pivot ausente: responde el precio directamente.
   - Fotos: solo cuando el cliente las pide o necesita más detalle → una foto por color (agrupa variantes por color), con el color como pie.
   - Agotado: solo menciónalo cuando sea relevante. Una talla está agotada solo si TODAS sus variantes (colores) están en 0; un color está agotado solo si TODAS sus tallas están en 0; una variante específica usa su propio stock.
4. Respuesta final: da SOLO lo que se pidió (precio O medidas O fotos; tú lees la intención). Luego espera en silencio: sin frases de cierre, sin "¿algo más?".
5. Preguntas fuera del catálogo (envíos/cobertura, métodos de pago, tiempo de entrega, devoluciones/garantía): compáralas con las FAQs. Si coincide con claridad, llama a send_faq(faq_id) (texto literal). Si no, escala.
6. Intención de compra ("lo quiero", "¿cómo compro?", "quiero 2"): escala y guarda silencio. No se toman pedidos.
7. Mensaje no textual (el transcript mostrará [nota de voz], [imagen], [video], [sticker], [ubicación], [documento]): escala indicando el tipo. Solo lees texto.
8. Mensaje en otro idioma: responde igualmente en español.
9. Cualquier otra duda: escala (o silencio). Ante la duda, escala.

CATÁLOGO ACTIVO (JSON; precios en Quetzales, "agotado" = stock 0; "images" son URLs para send_images):
{$catalogJson}

FAQS DISPONIBLES (usa send_faq con el id correcto solo si coincide con claridad):
{$faqJson}
PROMPT;
    }
}
