<!-- resources/views/records/index.blade.php -->

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Inventario') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto sm:px-2 lg:px-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if (session('success'))
                        <div class="mb-4 text-green-600">{{ session('success') }}</div>
                    @endif

                    <a href="{{ route('report-inventory.export') }}" class="custom-button bg-blue-700 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Exportar
                    </a>
                    
                    <table class="w-full min-w-full divide-y divide-gray-200 mt-4 text-sm">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Producto</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Variante</th>

                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>

                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($results as $result)
                                <tr >
                                    <td class="px-4 py-4 whitespace-nowrap">{{ $result->product_description }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap">{{ $result->variant_description }}</td>

                                    <td class="px-4 py-4 whitespace-nowrap">{{ $result->stock }}</td>
                                    
                                </tr>
                               
                                
                            @endforeach
                        </tbody>
                        
                    </table>
                    
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

