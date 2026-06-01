<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Metabot · Etiquetas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-2 lg:px-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <p class="text-sm text-gray-500 mb-4">Elige un producto para editar sus tags y los de sus variantes.</p>

                    <input type="text" id="filter" placeholder="Buscar producto..." class="border-gray-300 rounded-md shadow-sm sm:text-sm w-full mb-4">

                    <div id="list">
                        @forelse($products as $p)
                            <a href="{{ route('metabot.tags.product', ['id' => $p->id]) }}"
                               class="prod-row flex justify-between items-center py-3 px-2 border-b border-gray-100 hover:bg-gray-50"
                               data-name="{{ \Illuminate\Support\Str::lower($p->descripcion) }}">
                                <span class="font-medium text-gray-700">{{ $p->descripcion }}</span>
                                <span class="text-xs text-gray-400">{{ $counts[$p->id] ?? 0 }} tags de producto</span>
                            </a>
                        @empty
                            <p class="text-gray-400 py-6">No hay productos.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var input = document.getElementById('filter');
        var rows = document.querySelectorAll('.prod-row');
        if (input) input.addEventListener('input', function () {
            var q = input.value.toLowerCase();
            rows.forEach(function (r) {
                r.style.display = r.getAttribute('data-name').indexOf(q) !== -1 ? '' : 'none';
            });
        });
    })();
    </script>
</x-app-layout>
