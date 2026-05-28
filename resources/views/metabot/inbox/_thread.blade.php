<div class="space-y-2">
    @forelse ($messages as $m)
        @php
            $out  = $m->direction === 'out';
            $text = $m->body ?: ($m->button_title ?: '[' . $m->kind . ']');
            $who  = '';
            if ($out && $m->kind === 'human_reply') {
                $who = ' · tú';
            } elseif ($out) {
                $who = ' · bot';
            }
        @endphp
        <div class="flex {{ $out ? 'justify-end' : 'justify-start' }}">
            <div class="max-w-md px-3 py-2 rounded-lg {{ $out ? 'bg-green-100' : 'bg-white border border-gray-200' }}">
                <div class="text-sm whitespace-pre-wrap break-words">{{ $text }}</div>
                <div class="text-xs text-gray-400 mt-1 text-right">{{ $m->created_at }}{{ $who }}</div>
            </div>
        </div>
    @empty
        <p class="text-gray-400">Sin mensajes.</p>
    @endforelse
</div>
