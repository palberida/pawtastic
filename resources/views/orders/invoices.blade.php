<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Facturación') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto sm:px-2 lg:px-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if (session('success'))
                        <div class="mb-4 text-green-600">{{ session('success') }}</div>
                    @endif
                   
                    <div class="mb-4 w-full">
                        <form method="GET" action="{{ route('invoices', ['state' => $state]) }}" class="flex flex-wrap items-center gap-4 w-full">
                            <div class="flex flex-col ">
                                <label for="search_fecha" class="block text-sm font-medium text-gray-700">Fecha</label>
                                <select 
                                    id="search_fecha" 
                                    name="search_fecha" 
                                    onchange="this.form.submit()" 
                                    class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                >
                                    <option value="today" {{ request('search_fecha') == 'today' ? 'selected' : '' }}>Hoy</option>
                                    <option value="yesterday" {{ request('search_fecha') == 'yesterday' ? 'selected' : '' }}>Ayer</option>
                                    <option value="this_week" {{ request('search_fecha') == 'this_week' ? 'selected' : '' }}>Esta semana</option>
                                    <option value="last_week" {{ request('search_fecha') == 'last_week' ? 'selected' : '' }}>Semana pasada</option>
                                    <option value="this_month" {{ request('search_fecha') == 'this_month' ? 'selected' : '' }}>Este mes</option>
                                    <option value="last_month" {{ request('search_fecha') == 'last_month' ? 'selected' : '' }}>Mes pasado</option>
                                    <option value="this_year" {{ request('search_fecha') == 'this_year' ? 'selected' : '' }}>Este año</option>
                                    <option value="last_year" {{ request('search_fecha') == 'last_year' ? 'selected' : '' }}>Año pasado</option>
                                    <option value="lifetime" {{ request('search_fecha') == 'lifetime' ? 'selected' : '' }}>Todos</option>
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
                            <div class="flex flex-col ">
                                <label for="search_output" class="block text-sm font-medium text-gray-700">Vista</label>
                                <select 
                                    id="search_output" 
                                    name="search_output" 
                                    onchange="this.form.submit()" 
                                    class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                >
                                    <option value="pantalla" {{ request('search_output') == 'pantalla' ? 'selected' : '' }}>Pantalla</option>
                                    <option value="archivo" {{ request('search_output') == 'archivo' ? 'selected' : '' }}>Archivo</option>
                                </select>  
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

                            <!--<button type="submit" name="action" value="export" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition">
                                Submit Form
                            </button>-->
                        </form>
                    </div>



                        <div class="mb-4">
                            <button
                                id="facturar-batch"
                                class="custom-button bg-blue-700 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded"
                            >
                                Facturar Batch
                            </button>
                        </div>
                    
                    <div id="total-container">
                        Total Seleccionado: <strong id="total_selected">0</strong>
                    </div>
                    
                    <table id="ordenes" class="w-full min-w-full divide-y divide-gray-200 mt-4 text-sm min-w-[1000px]">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3">
                                
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                            <th class="w-1/4 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Direccion</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">NIT</th>
                            <th class="w-1/4 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descuento</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Guía</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Transf.</th>

                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($orders as $order)
                                <tr>
                                    <td class="px-4 py-4">
                                        <input
                                            type="checkbox"
                                            name="order_ids[]"
                                            value="{{ $order->id }}"
                                            class="order-checkbox"
                                            total="{{ $order->total }}"
                                        >
                                    </td>
                                    <td class="px-4 py-4 whitespace-normal text-xs">{{ $order->id_shopify }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-xs">{{ $order->created_at->format('d/m/y') }}</td>
                                    <td class="px-4 py-4 whitespace-normal text-sm break-words">{{ $order->nombre_cliente }}</td>
                                    <td class="px-4 py-4 whitespace-normal text-xs break-words">{{ $order->direccion_factura }}</td>
                                    <td class="px-4 py-4 break-words text-xs">{{ $order->nit_cliente ? $order->nit_cliente : 'CF' }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap"></td>
                                    <td class="px-4 py-4 whitespace-nowrap"></td>
                                    <td class="px-4 py-4 whitespace-nowrap"></td>
                                    <td class="px-4 py-4 whitespace-nowrap text-xs">{{ number_format($order->descuento, 2) }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap text-xs">{{ number_format($order->total, 2) }}</td>
                                    <td class="px-4 py-4 whitespace-normal  text-xs">{{ $order->guia }}</td>
                                    <td class="px-4 py-4  whitespace-normal  text-xs">
                                        @if($order->transfers()->first())
                                            {{ $order->transfers()->first()->transfer->codigo ?? 'N/A' }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    @if($state == "pending")
                                    <td class="px-4 py-4 whitespace-nowrap flex space-x-4 items-center">
                                        <form action="{{ route('invoices.done', ['id' => $order->id, 'search_nombre' => request('search_nombre'), 'search_fecha' => request('search_fecha'), 'search_fecha_inicio' => request('search_fecha_inicio'), 'search_fecha_fin' => request('search_fecha_fin'), 'search_outuput' => request('search_outuput') ]) }}" method="POST" class="flex items-center space-x-2">
                                            @csrf 
                                            <input type="text" name="autorizacion" placeholder="Autorización" class="border rounded px-1 py-1 w-20">
                                            <button type="submit" class="text-green-500 flex items-center">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                </svg>
                                            </button>
                                        </form>
                                        <form action="{{ route('invoices.generate', ['id' => $order->id, 'search_nombre' => request('search_nombre'), 'search_fecha' => request('search_fecha'), 'search_fecha_inicio' => request('search_fecha_inicio'), 'search_fecha_fin' => request('search_fecha_fin'), 'search_outuput' => request('search_outuput') ]) }}" method="POST" class="flex items-center space-x-2">
                                            @csrf
                                            <button type="submit" class="text-green-500 flex items-center">
                                                Generar
                                            </button>
                                        </form>
                                    </td>
                                    @else
                                    <td class="px-4 py-4 whitespace-nowrap flex space-x-4 items-center">
                                        {{ $order->autorizacion }}
                                    </td>
                                    @endif
                                </tr>
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td class="px-4 py-4 whitespace-normal text-xs max-w-xs break-words">{{ $item->descripcion }}</td>
                                        <td class="px-4 py-4 whitespace-normal text-xs max-w-xs break-words">{{ $item->cantidad }}</td>
                                        <td class="px-4 py-4 whitespace-normal text-xs max-w-xs break-words">{{ $item->precio / $item->cantidad }}</td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                        
                    </table>

                    
                    <div class="mt-4">
                    {{ $orders->appends([
                        'search_nombre' => request('search_nombre'),
                        'search_fecha' => request('search_fecha'),
                        'search_fecha_incio' => request('search_fecha_incio'),
                        'search_fecha_fin' => request('search_fecha_fin'),
                        'search_output' => request('search_output'),
                        'state' => request('state')
                    ])->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    
  document.getElementById('ordenes').addEventListener('change', function (e) {
    if (!e.target.classList.contains('order-checkbox')) return;

    let sum = 0;
    document.querySelectorAll('.order-checkbox:checked').forEach(checkbox => {
        const amount = parseFloat(checkbox.getAttribute('total')) || 0;
        sum += amount;
    });

    

    document.getElementById('total_selected').textContent = sum.toFixed(2);
  });

    document.getElementById('facturar-batch').addEventListener('click', function () {
        const ids = Array.from(document.querySelectorAll('.order-checkbox:checked'))
            .map(cb => cb.value);

        if (ids.length === 0) {
            alert('Selecciona al menos una orden');
            return;
        }

        fetch('{{ route('invoices.generate_batch') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ order_ids: ids })
        })
        .then(res => res)
        .then(data => {
            alert('Proceso completado, por favor ingrese a SAT para verificar la factura');
            location.reload();
            }
        )
        .catch(() => alert('Error al facturar batch'));
        //.then(res => res.json())
        //.then(data => {
        //    alert(data.message ?? 'Proceso completado');
        //    location.reload();
        //})
        //.catch(() => alert('Error al facturar batch'));
    });
</script>
