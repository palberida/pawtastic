@php
    // Epoch (absolute) of the customer's last inbound message — drives the live
    // 24h-window indicator. Re-emitted on every thread poll so the window resets
    // the moment a new customer message arrives.
    $lastInbound   = $messages->where('direction', 'in')->last();
    $lastInboundTs = $lastInbound ? \Illuminate\Support\Carbon::parse($lastInbound->created_at)->getTimestamp() : '';
@endphp
<div id="wa-window-data" data-at="{{ $lastInboundTs }}" hidden></div>
<div class="space-y-2">
    @forelse ($messages as $m)
        @php
            $out      = $m->direction === 'out';
            $hasMedia = !empty($m->media_path);
            $text     = $m->body ?: ($m->button_title ?: '[' . $m->kind . ']');
            $caption  = $hasMedia ? trim(preg_replace('/^\[imagen\]/u', '', (string) $m->body)) : '';
            $who      = '';
            if ($out && $m->kind === 'human_reply') {
                $who = ' · tú';
            } elseif ($out && $m->kind === 'human_image') {
                $who = ' · tú';
            } elseif ($out) {
                $who = ' · bot';
            }
        @endphp
        <div class="flex {{ $out ? 'justify-end' : 'justify-start' }}">
            <div class="max-w-md px-3 py-2 rounded-lg {{ $out ? 'bg-green-100' : 'bg-white border border-gray-200' }}">
                @if ($hasMedia)
                    <a href="{{ route('metabot.media', ['id' => $m->id]) }}" target="_blank">
                        <img src="{{ route('metabot.media', ['id' => $m->id]) }}" alt="imagen" class="rounded max-w-full" style="max-height: 240px">
                    </a>
                    @if ($caption !== '')
                        <div class="text-sm whitespace-pre-wrap break-words mt-1">{{ $caption }}</div>
                    @endif
                @else
                    <div class="text-sm whitespace-pre-wrap break-words">{{ $text }}</div>
                @endif
                <div class="text-xs text-gray-400 mt-1 text-right">{{ $m->created_at }}{{ $who }}</div>
            </div>
        </div>
    @empty
        <p class="text-gray-400">Sin mensajes.</p>
    @endforelse
</div>
