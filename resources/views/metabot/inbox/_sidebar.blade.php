{{-- params: $conversations, $active (the phone of the open chat) --}}
@php($pendingCount = $conversations->where('pending', true)->count())
<div class="px-3 py-2 border-b border-gray-200 flex items-center justify-between" style="flex:none;gap:8px;">
    <span class="text-sm font-semibold text-gray-700">Bandeja</span>
    @if ($pendingCount > 0)
        <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#1d4ed8;background:#eff6ff;padding:3px 10px;border-radius:9999px;white-space:nowrap;">
            <span style="display:inline-block;width:8px;height:8px;border-radius:9999px;background:#3b82f6;"></span>
            {{ $pendingCount }} {{ $pendingCount === 1 ? 'pendiente' : 'pendientes' }}
        </span>
    @endif
</div>
<div id="chat-sidebar-list">
    @forelse ($conversations as $c)
        @php($isActive = $c->phone === $active)
        <a href="{{ route('metabot.inbox.show', ['phone' => $c->phone]) }}"
           class="flex items-center px-3 py-2 border-b border-gray-100 {{ $isActive ? '' : 'hover:bg-gray-50' }}"
           style="gap:10px;min-width:0;{{ $isActive ? 'background:#c7d2fe;border-left:6px solid #312e81;box-shadow:inset 0 0 0 1px #818cf8;' : ($c->pending ? 'background:#eff6ff;border-left:4px solid #3b82f6;' : 'border-left:4px solid transparent;') }}">
            @include('metabot.inbox._avatar', ['name' => $c->name, 'phone' => $c->phone, 'size' => 36])
            @php($localPhone = \Illuminate\Support\Str::startsWith($c->phone, '502') ? substr($c->phone, 3) : $c->phone)
            @php($lastAt = $c->last_at ? \Illuminate\Support\Carbon::parse($c->last_at) : null)
            <div style="flex:1;min-width:0;">
                <div class="flex items-center" style="gap:6px;min-width:0;">
                    @if($c->pending && !$isActive)
                        <span style="display:inline-block;width:8px;height:8px;border-radius:9999px;background:#3b82f6;flex:none;"></span>
                    @endif
                    <span style="flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;{{ $isActive ? 'font-weight:700;color:#1e1b4b;' : ($c->pending ? 'font-weight:700;color:#111827;' : 'font-weight:600;color:#374151;') }}">
                        {{ $c->name ?: $localPhone }}
                    </span>
                    @if($lastAt)
                        <span class="text-xs" style="flex:none;white-space:nowrap;color:#9ca3af;">{{ $lastAt->isToday() ? $lastAt->format('H:i') : $lastAt->format('d/m/y') }}</span>
                    @endif
                </div>
                @if($c->name)
                    <div class="text-xs" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:#9ca3af;">{{ $localPhone }}</div>
                @endif
                <div class="text-xs" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;{{ $c->pending ? 'color:#374151;' : 'color:#9ca3af;' }}">
                    @if($c->last_direction === 'out')<span style="color:#6b7280;font-weight:600;">Tú: </span>@endif{{ $c->last_body }}
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
