<!-- resources/views/records/index.blade.php -->

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Reportes') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-2 lg:px-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if (session('success'))
                        <div class="mb-4 text-green-600">{{ session('success') }}</div>
                    @endif
                   
                    <div class="mb-4 w-full">
                        <form method="GET" action="{{ route('report-product.index') }}" class="flex flex-wrap items-center gap-4 w-full">
                            <div class="flex flex-col ">
                                <label for="search_fecha" class="block text-sm font-medium text-gray-700">Fecha</label>
                                <select 
                                    id="search_fecha" 
                                    name="search_fecha" 
                                    onchange="this.form.submit()" 
                                    class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                >
                                    <option value="lifetime" {{ request('search_fecha') == 'lifetime' ? 'selected' : '' }}>Todos</option>
                                    <option value="today" {{ request('search_fecha') == 'today' ? 'selected' : '' }}>Hoy</option>
                                    <option value="yesterday" {{ request('search_fecha') == 'yesterday' ? 'selected' : '' }}>Ayer</option>
                                    <option value="this_week" {{ request('search_fecha') == 'this_week' ? 'selected' : '' }}>Esta semana</option>
                                    <option value="last_week" {{ request('search_fecha') == 'last_week' ? 'selected' : '' }}>Semana pasada</option>
                                    <option value="this_month" {{ request('search_fecha') == 'this_month' ? 'selected' : '' }}>Este mes</option>
                                    <option value="last_month" {{ request('search_fecha') == 'last_month' ? 'selected' : '' }}>Mes pasado</option>
                                    <option value="this_year" {{ request('search_fecha') == 'this_year' ? 'selected' : '' }}>Este año</option>
                                    <option value="last_year" {{ request('search_fecha') == 'last_year' ? 'selected' : '' }}>Año pasado</option>
                                    
                                </select>
                            </div>
                            <div class="flex flex-col ">
                                <label for="search_fecha_inicio" class="block text-sm font-medium text-gray-700">Fecha Inicio</label>
                                <input type="date" id="search_fecha_inicio" name="search_fecha_inicio"  value="{{ request('search_fecha_inicio') }}"  onchange="this.form.submit()"  class="border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="flex flex-col ">
                                <label for="search_fecha_fin" class="block text-sm font-medium text-gray-700">Fecha Fin</label>
                                <input type="date" id="search_fecha_fin" name="search_fecha_fin"  value="{{ request('search_fecha_fin') }}"  onchange="this.form.submit()"  class="border border-gray-300 rounded-lg p-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="flex flex-col grow">
                                <label for="search_nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre o producto</label>    
                                <div class="relative w-full">
                                    <input 
                                        type="text" 
                                        id="search_nombre" 
                                        name="search_nombre" 
                                        value="{{ request('search_nombre') }}" 
                                        placeholder="Buscar nombre o producto" 
                                        class="w-full px-4 py-2 pr-12 border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    >
                                    <button 
                                        type="submit" 
                                        class="absolute inset-y-0 right-0 px-3 py-1.5 bg-gray-300 text-white font-medium rounded-r-md hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 flex items-center"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0a7.5 7.5 0 1 0-10.607-10.607 7.5 7.5 0 0 0 10.607 10.607z" />
                                        </svg>
                                    </button>
                                </div>                       
                            </div>
                        </form>
                    </div>
                    <div>
                        
                        <div class="font-bold">Total Productos:  {{ $totals->total }}</div>
                        <div class="font-bold">Total Precio:  Q.{{ number_format($totals->total_precio,2) }}</div>
                        <div class="font-bold">Total Costo:  Q.{{ number_format($totals->total_costo,2) }}</div>
                        <div class="font-bold">Total Descuento:  Q.{{ number_format($totals->total_descuento,2) }}</div>
                        <div class="font-bold">Total Ads:  Q.{{ number_format($totals->total_ads,2) }}</div>
                        <div class="font-bold">Total Ganancia*:  Q.{{ number_format($totals->total_precio - $totals->total_costo - $totals->total_descuento - $totals->total_ads,2) }}</div>

                    </div>
                    <table class="w-full min-w-full divide-y divide-gray-200 mt-4 text-sm">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Recaudado</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Costo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Ads</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total ganancia*</th>
                            
                            
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($products as $product)
                                <tr onclick="fetchDetails({{ $product->id }})">
                                    <td class="px-4 py-4 whitespace-nowrap">{{ $product->descripcion }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap">{{ $product->total }}</td>
                                    <td class="py-1 px-2 border-b">{{ number_format($product->total_dinero,2) }}</td>
                                    <td class="py-1 px-2 border-b">{{ number_format($product->total_costo,2) }}</td>
                                    <td class="py-1 px-2 border-b">{{ number_format($product->ads,2) }}</td>
                                    <td class="py-1 px-2 border-b">{{ number_format($product->total_dinero - $product->total_costo - $product->ads,2) }}</td>

                                    
                                </tr>
                                <tr id="details-{{ $product->id }}" class="hidden">
                                    <td colspan="1" class="py-2 px-4">
                                        
                                    </td>
                                    <td colspan="1" class="py-2 px-4">
                                        <div id="details2-content-{{ $product->id }}"></div>
                                    </td>
                                    <td colspan="1" class="py-2 px-4">
                                        <div id="details-content-{{ $product->id }}"></div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        
                    </table>
                    
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    function fetchDetails(productId) {
        var detailsRow = document.getElementById('details-' + productId);
        var detailsContent = document.getElementById('details-content-' + productId);
        var details2Content = document.getElementById('details2-content-' + productId);
        var fechaInicio = '{{ request('search_fecha_inicio') }}';
        var fechaFin = '{{ request('search_fecha_fin') }}';
        var fecha = '{{ request('search_fecha') }}';
        var nombre = '{{ request('search_nombre') }}';
        if (detailsRow.classList.contains('hidden')) {
            let baseUrl = "{{ route('report-product.details', ['id' => 'placeholder']) }}";
            let s_url = baseUrl.replace('placeholder', productId);
            let url = `${s_url}?search_nombre=${nombre}&search_fecha=${fecha}&search_fecha_inicio=${fechaInicio}&search_fecha_fin=${fechaFin}`;

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

            baseUrl = "{{ route('report-product.details2', ['id' => 'placeholder']) }}";
            s_url = baseUrl.replace('placeholder', productId);
            url = `${s_url}?search_nombre=${nombre}&search_fecha=${fecha}&search_fecha_inicio=${fechaInicio}&search_fecha_fin=${fechaFin}`;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.variants ) {
                        let variantsTable = '<table class=" bg-gray-50"><tbody>';

                        data.variants.forEach(variant => {
                            variantsTable += `<tr>

                                <td class="py-1 px-2 border-b">${variant.variant_name}</td>
                                <td class="py-1 px-2 border-b">${variant.total}</td>
                            </tr>`;
                        });

                        variantsTable += '</tbody></table>';

                        details2Content.innerHTML = variantsTable;

                    }
                })
                .catch(error => console.error('Error fetching product details:', error));
        } else {
            // Toggle visibility if already loaded
            detailsRow.classList.add('hidden');
        }
    }
</script>