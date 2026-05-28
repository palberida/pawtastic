<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Metabot · Plantillas') }}
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
                            Plantillas <strong>aprobadas en Meta</strong> que puedes enviar desde el chat para reabrir una
                            conversación fuera de la ventana de 24h. El <code>nombre</code> y el <code>idioma</code> deben
                            coincidir exactamente con los aprobados en WhatsApp Manager. Cada envío tiene costo.
                        </p>
                        <a href="{{ route('metabot.templates.new') }}" class="custom-button bg-blue-700 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Registrar plantilla
                        </a>
                    </div>

                    <table class="w-full min-w-full divide-y divide-gray-200 mt-4 text-sm">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Etiqueta</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre (Meta)</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Idioma</th>
                            <th class="px-4 py-3 w-1/2 text-left text-xs font-medium text-gray-500 uppercase">Mensaje</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($templates as $t)
                                <tr>
                                    <td class="px-4 py-4 font-bold">{{ $t->label ?: '—' }}</td>
                                    <td class="px-4 py-4 font-mono text-xs">{{ $t->name }}</td>
                                    <td class="px-4 py-4">{{ $t->language }}</td>
                                    <td class="px-4 py-4 text-gray-500">{{ $t->body_preview }}</td>
                                    <td class="px-4 py-4">
                                        <span class="{{ $t->status === 'active' ? 'text-green-600' : 'text-gray-400' }}">
                                            {{ $t->status === 'active' ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <a href="{{ route('metabot.templates.edit', ['id' => $t->id]) }}" class="text-green-500">Editar</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-6 text-gray-400">Aún no hay plantillas registradas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
