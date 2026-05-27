<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Metabot · Preguntas frecuentes') }}
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
                            Respuestas que el bot envía <strong>tal cual</strong> (sin reformular) cuando un cliente
                            pregunta por temas fuera del producto. Si no hay coincidencia clara, escala a un humano.
                        </p>
                        <a href="{{ route('metabot.faqs.new') }}" class="custom-button bg-blue-700 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Nueva pregunta
                        </a>
                    </div>

                    <table class="w-full min-w-full divide-y divide-gray-200 mt-4 text-sm">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tema</th>
                            <th class="px-4 py-3 w-1/3 text-left text-xs font-medium text-gray-500 uppercase">Cubre</th>
                            <th class="px-4 py-3 w-1/3 text-left text-xs font-medium text-gray-500 uppercase">Respuesta</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($faqs as $faq)
                                <tr>
                                    <td class="px-4 py-4 font-bold">{{ $faq->topic }}</td>
                                    <td class="px-4 py-4 text-gray-500">{{ $faq->trigger_description }}</td>
                                    <td class="px-4 py-4">{{ \Illuminate\Support\Str::limit($faq->answer_text, 80) }}</td>
                                    <td class="px-4 py-4">
                                        <span class="{{ $faq->status === 'active' ? 'text-green-600' : 'text-gray-400' }}">
                                            {{ $faq->status === 'active' ? 'Activa' : 'Inactiva' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4">
                                        <a href="{{ route('metabot.faqs.edit', ['id' => $faq->id]) }}" class="text-green-500">Editar</a>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-6 text-gray-400">Aún no hay preguntas configuradas.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
