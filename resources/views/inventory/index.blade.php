<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Inventario') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-2 lg:px-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if (session('success'))
                        <div class="mb-4 text-green-600">{{ session('success') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="mb-4 text-red-600">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="mb-4 w-full flex items-center justify-between">
                        <form method="GET" action="{{ route('inventory.index') }}" class="flex items-end gap-2">
                            <div class="flex flex-col">
                                <label for="search" class="block text-sm font-medium text-gray-700">Buscar</label>
                                <input type="text" id="search" name="search" value="{{ $search }}"
                                    placeholder="Producto, variante o código"
                                    class="border border-gray-300 rounded-lg p-2 w-72 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <button type="submit" class="custom-button bg-blue-700 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Buscar
                            </button>
                            @if ($search !== '')
                                <a href="{{ route('inventory.index') }}" class="py-2 px-4 text-sm text-gray-500 hover:text-gray-700">
                                    Limpiar
                                </a>
                            @endif
                        </form>

                        <div class="text-sm text-gray-500">
                            {{ $variants->total() }} {{ $variants->total() === 1 ? 'variante' : 'variantes' }}
                        </div>
                    </div>

                    <form method="POST" action="{{ route('inventory.stock.update') }}">
                        @csrf
                        <input type="hidden" name="search" value="{{ $search }}">
                        <input type="hidden" name="page" value="{{ $variants->currentPage() }}">

                        <table class="w-full min-w-full divide-y divide-gray-200 mt-4 text-sm">
                            <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Variante</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Código</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse ($variants as $variant)
                                    <tr>
                                        <td class="px-4 py-3">{{ $variant->product_description }}</td>
                                        <td class="px-4 py-3">{{ $variant->variant_description }}</td>
                                        <td class="px-4 py-3 text-gray-500">{{ $variant->codigo }}</td>
                                        <td class="px-4 py-3">
                                            <input type="number" min="0" step="1"
                                                name="stock[{{ $variant->id }}]"
                                                value="{{ $variant->stock }}"
                                                class="w-24 border rounded-md p-1 text-right sm:text-sm focus:ring-blue-500 focus:border-blue-500 {{ $variant->stock <= 0 ? 'border-red-400 text-red-600' : 'border-gray-300' }}">
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-6 text-gray-400">No hay variantes que coincidan con la búsqueda.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        @if ($variants->count())
                            <div class="mt-4">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-700 text-white rounded">
                                    Guardar Cambios
                                </button>
                                <span class="ml-2 text-sm text-gray-500">Guarda solo las variantes de esta página.</span>
                            </div>
                        @endif
                    </form>

                    <div class="mt-4">
                        {{ $variants->appends(['search' => $search])->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
