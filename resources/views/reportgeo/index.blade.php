<!-- resources/views/records/index.blade.php -->

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Geo') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-2 lg:px-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if (session('success'))
                        <div class="mb-4 text-green-600">{{ session('success') }}</div>
                    @endif
                   
                    
                    <table class="w-full min-w-full divide-y divide-gray-200 mt-4 text-sm">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Porcentaje</th>
                            
                            
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($orders as $order)
                                <tr onclick="fetchDetails({{ $order->departamento_cliente }})">
                                    <td class="px-4 py-4 whitespace-nowrap">{{ $order->departamento_cliente }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap">{{ $order->total }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap">{{ $order->percentage }}</td>

                                    
                                </tr>
                               
                                
                            @endforeach
                        </tbody>
                        
                    </table>
                    <div class="mt-4">
                    {{ $orders->appends([
                        'search_nombre' => request('search_nombre'),
                        'search_fecha' => request('search_fecha'),
                        'search_fecha_incio' => request('search_fecha_incio'),
                        'search_fecha_fin' => request('search_fecha_fin'),
                    ])->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    function fetchDetails(productId) {
        var detailsRow = document.getElementById('details-' + productId);
        var detailsContent = document.getElementById('details-content-' + productId);
        var fechaInicio = '{{ request('search_fecha_inicio') }}';
        var fechaFin = '{{ request('search_fecha_fin') }}';
        var fecha = '{{ request('search_fecha') }}';
        var nombre = '{{ request('search_nombre') }}';
        if (detailsRow.classList.contains('hidden')) {
            const url = `/reports/${productId}/details?search_nombre=${nombre}&search_fecha=${fecha}&search_fecha_incio=${fechaInicio}&search_fecha_fin=${fechaFin}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.variants ) {
                        let variantsTable = '<table class=" bg-gray-50"><tbody>';

                        data.variants.forEach(variant => {
                            variantsTable += `<tr>

                                <td class="py-1 px-2 border-b">${variant.descripcion}</td>
                                <td class="py-1 px-2 border-b">${variant.total}</td>
                            </tr>`;
                        });

                        variantsTable += '</tbody></table>';

                        detailsContent.innerHTML = variantsTable;
                        detailsRow.classList.remove('hidden');
                    }
                })
                .catch(error => console.error('Error fetching product details:', error));
        } else {
            // Toggle visibility if already loaded
            detailsRow.classList.add('hidden');
        }
    }
</script>