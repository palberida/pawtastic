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

                    <p class="text-sm text-gray-500 mb-4">Conversaciones de WhatsApp con clientes. Toca una para responder.</p>

                    @forelse ($conversations as $c)
                        <a href="{{ route('metabot.inbox.show', ['phone' => $c->phone]) }}" class="flex justify-between items-center py-3 px-2 border-b border-gray-100 hover:bg-gray-50">
                            <div class="min-w-0">
                                <div class="font-semibold">+{{ $c->phone }}</div>
                                <div class="text-sm text-gray-500 truncate" style="max-width: 32rem">
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
