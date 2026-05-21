<!-- resources/views/records/index.blade.php -->

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Historial de Inventario') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-2 lg:px-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if (session('success'))
                        <div class="mb-4 text-green-600">{{ session('success') }}</div>
                    @endif
                
                    <div class="mb-4 w-full flex items-center justify-between">
                        <form method="GET" action="{{ route('report-inventory.history') }}" class="flex items-center gap-4">
                            <div class="flex flex-col">
                                <label for="search_fecha_inicio" class="block text-sm font-medium text-gray-700">Fecha Inicio</label>
                                <input type="month" id="search_fecha_inicio" name="search_fecha_inicio"  
                                    value="{{ request('search_fecha_inicio') }}"  
                                    onchange="this.form.submit()"  
                                    class="border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </form>

                        <a href="{{ route('report-inventory-history.export', ['search_fecha_inicio' => request('search_fecha_inicio')]) }}"
                        class="custom-button bg-blue-700 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Exportar
                        </a>
                    </div>


                    
                    <table class="w-full min-w-full divide-y divide-gray-200 mt-4 text-sm">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Variante</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                           
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($results as $result)
                                <tr >
                                    <td class="px-4 py-4 whitespace-nowrap text-xs">{{ $result->fecha->format('d/m/y H:i') }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap">{{ $result->product_name }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap">{{ $result->variant_name }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap">{{ $result->stock }}</td>                                  
                                </tr>
                               
                                
                            @endforeach
                        </tbody>
                        
                    </table>
                    <div class="mt-4">
                    {{ $results->appends([
                        'search_fecha_inicio' => request('search_fecha_inicio'),
                    ])->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

