<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Thin client for the Anthropic Messages API (tool use).
 *
 * The API key lives only in config('metabot.anthropic_api_key') (from .env).
 * respond() returns the raw decoded response; the caller reads stop_reason and
 * the content blocks (text / tool_use) itself.
 */
class ClaudeClient
{
    /**
     * @param  string  $system   system prompt
     * @param  array   $messages Anthropic-format messages [['role'=>..,'content'=>..], ...]
     * @param  array   $tools    Anthropic tool definitions
     * @return array{status:int, body:mixed}
     */
    public function respond(string $system, array $messages, array $tools): array
    {
        $payload = [
            'model'      => config('metabot.claude_model'),
            'max_tokens' => config('metabot.max_tokens', 1024),
            'system'     => $system,
            'messages'   => $messages,
            'tools'      => $tools,
        ];

        $response = Http::withHeaders([
            'x-api-key'         => config('metabot.anthropic_api_key'),
            'anthropic-version' => config('metabot.anthropic_version'),
            'content-type'      => 'application/json',
        ])
            ->timeout(60)
            ->acceptJson()
            ->post('https://api.anthropic.com/v1/messages', $payload);

        return [
            'status' => $response->status(),
            'body'   => $response->json() ?? $response->body(),
        ];
    }
}
