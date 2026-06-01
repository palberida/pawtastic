<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Metabot · Bandeja') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-2 lg:px-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if (session('success'))
                        <div class="mb-4 text-green-600">{{ session('success') }}</div>
                    @endif

                    <div class="flex items-center justify-between mb-4">
                        <p class="text-sm text-gray-500">Conversaciones de WhatsApp con clientes. Toca una para responder.</p>
                        @if ($pendingCount > 0)
                            <span style="display:inline-flex;align-items:center;gap:6px;font-size:13px;font-weight:600;color:#1d4ed8;background:#eff6ff;padding:4px 12px;border-radius:9999px;white-space:nowrap;">
                                <span style="display:inline-block;width:8px;height:8px;border-radius:9999px;background:#3b82f6;"></span>
                                {{ $pendingCount }} {{ $pendingCount === 1 ? 'pendiente' : 'pendientes' }}
                            </span>
                        @endif
                    </div>

                    @forelse ($conversations as $c)
                        <a href="{{ route('metabot.inbox.show', ['phone' => $c->phone]) }}"
                           class="flex justify-between items-center py-3 px-3 border-b border-gray-100 hover:bg-gray-50"
                           @if($c->pending) style="background:#eff6ff;border-left:4px solid #3b82f6;" @endif>
                            <div class="min-w-0">
                                <div class="flex items-center" style="gap:8px;">
                                    @if($c->pending)
                                        <span style="display:inline-block;width:8px;height:8px;border-radius:9999px;background:#3b82f6;flex:none;"></span>
                                    @endif
                                    <span style="{{ $c->pending ? 'font-weight:700;color:#111827;' : 'font-weight:600;color:#374151;' }}">+{{ $c->phone }}</span>
                                    @if($c->pending)
                                        <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:9999px;background:#dbeafe;color:#1d4ed8;white-space:nowrap;">Pendiente</span>
                                    @endif
                                    @if($c->status === 'handed_off')
                                        <span style="font-size:11px;font-weight:600;padding:2px 8px;border-radius:9999px;background:#fef3c7;color:#b45309;white-space:nowrap;">Escalado</span>
                                    @endif
                                </div>
                                <div class="text-sm truncate" style="max-width: 32rem;{{ $c->pending ? 'color:#374151;' : 'color:#6b7280;' }}">
                                    @if($c->last_direction === 'out')<span class="text-gray-400">↩ </span>@endif{{ $c->last_body }}
                                </div>
                            </div>
                            <div class="text-xs text-gray-400 whitespace-nowrap ml-4">{{ $c->last_at }}</div>
                        </a>
                    @empty
                        <p class="text-gray-400 py-6">Aún no hay conversaciones.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
