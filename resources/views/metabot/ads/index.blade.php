<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Metabot · Anuncios') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-2 lg:px-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if (session('success'))
                        <div class="mb-4 text-green-600">{{ session('success') }}</div>
                    @endif

                    <div class="flex justify-between items-center mb-4">
                        <p class="text-sm text-gray-500">
                            Anuncios que el bot atiende. El <code>source_id</code> es el ID del anuncio
                            de Facebook/Instagram (<code>referral.source_id</code>) que llega al dar clic.
                        </p>
                        <a href="{{ route('metabot.ads.new') }}" class="custom-button bg-blue-700 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Nuevo anuncio
                        </a>
                    </div>

                    <table class="w-full min-w-full divide-y divide-gray-200 mt-4 text-sm">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">source_id</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Alcance</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Productos</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($ads as $ad)
                                <tr>
                                    <td class="px-4 py-4 font-bold">{{ $ad->name ?: '—' }}</td>
                                    <td class="px-4 py-4 font-mono text-xs">{{ $ad->source_id }}</td>
                                    <td class="px-4 py-4">{{ $ad->scope === 'site_wide' ? 'Todo el catálogo' : 'Set de productos' }}</td>
                                    <td class="px-4 py-4">{{ $ad->scope === 'product_set' ? $ad->products_count : '—' }}</td>
                                    <td class="px-4 py-4">
                                        <span class="{{ $ad->status === 'active' ? 'text-green-600' : 'text-gray-400' }}">
                                            {{ $ad->status === 'active' ? 'Activo' : 'Pausado' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <a href="{{ route('metabot.ads.edit', ['id' => $ad->id]) }}" class="text-green-500">Editar</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-6 text-gray-400">Aún no hay anuncios configurados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
