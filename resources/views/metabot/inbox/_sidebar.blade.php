{{-- params: $conversations, $active (the phone of the open chat) --}}
<div class="px-3 py-2 border-b border-gray-200 flex items-center justify-between" style="flex:none;">
    <span class="text-sm font-semibold text-gray-700">Bandeja</span>
    <a href="{{ route('metabot.inbox.index') }}" class="text-xs text-blue-600 hover:underline">Ver todo</a>
</div>
<div id="chat-sidebar-list">
    @forelse ($conversations as $c)
        @php($isActive = $c->phone === $active)
        <a href="{{ route('metabot.inbox.show', ['phone' => $c->phone]) }}"
           class="flex items-center px-3 py-2 border-b border-gray-100 hover:bg-gray-50"
           style="gap:10px;min-width:0;{{ $isActive ? 'background:#eef2ff;border-left:4px solid #3730a3;' : ($c->pending ? 'background:#eff6ff;border-left:4px solid #3b82f6;' : 'border-left:4px solid transparent;') }}">
            @include('metabot.inbox._avatar', ['name' => $c->name, 'phone' => $c->phone, 'size' => 36])
            <div class="min-w-0" style="flex:1;">
                <div class="flex items-center" style="gap:6px;min-width:0;">
                    @if($c->pending && !$isActive)
                        <span style="display:inline-block;width:8px;height:8px;border-radius:9999px;background:#3b82f6;flex:none;"></span>
                    @endif
                    <span class="truncate" style="{{ $c->pending ? 'font-weight:700;color:#111827;' : 'font-weight:600;color:#374151;' }}">
                        {{ $c->name ?: ('+' . $c->phone) }}
                    </span>
                </div>
                <div class="text-xs truncate" style="{{ $c->pending ? 'color:#374151;' : 'color:#9ca3af;' }}">
                    @if($c->last_direction === 'out')<span class="text-gray-400">↩ </span>@endif{{ $c->last_body }}
                </div>
            </div>
            @if($c->status === 'handed_off')
                <span style="font-size:10px;font-weight:600;padding:1px 6px;border-radius:9999px;background:#fef3c7;color:#b45309;white-space:nowrap;flex:none;">Esc</span>
            @endif
        </a>
    @empty
        <p class="text-gray-400 text-sm px-3 py-6">Sin conversaciones.</p>
    @endforelse
</div>
