<!-- resources/views/records/index.blade.php -->

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Ordenes') }}
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
                        <form method="GET" action="{{ route('orders.index') }}" class="flex flex-wrap items-center gap-4 w-full">
                            <div class="flex flex-col ">
                                <label for="search_estado" class="block text-sm font-medium text-gray-700">Estado</label>
                                <select 
                                    id="search_estado" 
                                    name="search_estado" 
                                    onchange="this.form.submit()" 
                                    class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                >
                                    <option value="">Todos</option>
                                    <option value="creado" {{ request('search_estado') == 'creado' ? 'selected' : '' }}>Iniciado</option>
                                    <option value="enviado" {{ request('search_estado') == 'enviado' ? 'selected' : '' }}>Enviado</option>
                                    <option value="completado" {{ request('search_estado') == 'completado' ? 'selected' : '' }}>Completado</option>
                                    <option value="cancelado" {{ request('search_estado') == 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                                </select>  
                            </div>

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
                                <label for="search_mensajero" class="block text-sm font-medium text-gray-700">Mensajero</label>
                                <select 
                                    id="search_mensajero" 
                                    name="search_mensajero" 
                                    onchange="this.form.submit()" 
                                    class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                >
                                    <option value="">Todos</option>
                                    <option value="CAEX" {{ request('search_mensajero') == 'CAEX' ? 'selected' : '' }}>CAEX</option>
                                    <option value="FORZA" {{ request('search_mensajero') == 'FORZA' ? 'selected' : '' }}>FORZA</option>
                                    <option value="RAINER" {{ request('search_mensajero') == 'RAINER' ? 'selected' : '' }}>RAINER</option>
                                    <option value="SAMUEL" {{ request('search_mensajero') == 'SAMUEL' ? 'selected' : '' }}>SAMUEL</option>
                                    <option value="KEVIN_MENSAJERO" {{ request('search_mensajero') == 'KEVIN_MENSAJERO' ? 'selected' : '' }}>KEVIN</option>
                                    <option value="OSCAR" {{ request('search_mensajero') == 'OSCAR' ? 'selected' : '' }}>OSCAR</option>
                                </select>
                            </div>

                            <div class="flex flex-col ">
                                <label for="search_vendedor" class="block text-sm font-medium text-gray-700">Vendedor</label>
                                <select 
                                    id="search_vendedor" 
                                    name="search_vendedor" 
                                    onchange="this.form.submit()" 
                                    class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                >
                                    <option value="">Todos</option>
                                    @foreach(getUsersWithRole(3) as $seller)
                                        <option value="{{ $seller->seller_code }}" {{ request('search_vendedor') == $seller->seller_code ? 'selected' : '' }}>{{ $seller->seller_code }}</option>    
                                    @endforeach
                                    
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
                                <label for="search_pago" class="block text-sm font-medium text-gray-700">Forma Pago</label>
                                <select 
                                    id="search_pago" 
                                    name="search_pago" 
                                    onchange="this.form.submit()" 
                                    class="mt-1 w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                >
                                    <option value="">Todos</option>
                                    <option value="Cash on Delivery (COD)" {{ request('search_pago') == 'Cash on Delivery (COD)' ? 'selected' : '' }}>COD</option>
                                    <option value="Link de Pago con VisaNet" {{ request('search_pago') == 'Link de Pago con VisaNet' ? 'selected' : '' }}>NeoNet</option>
                                    <option value="Bank Deposit" {{ request('search_pago') == 'Bank Deposit' ? 'selected' : '' }}>Transferencia</option>
                                </select>  
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
                                <a href="{{ route('orders.add') }}">
                                    Agregar Orden
                                </a>
                            </div>
                            
                            <div class="flex flex-col grow">
                                <label for="search_nombre" class="block text-sm font-medium text-gray-700 mb-1">Buscar nombre, producto, guia, numero</label>    
                                <div class="relative w-full">
                                    <input 
                                        type="text" 
                                        id="search_nombre" 
                                        name="search_nombre" 
                                        value="{{ request('search_nombre') }}" 
                                        placeholder="nombre, producto, guia, numero" 
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
                        
                        <div class="font-bold">Ordenes Validas:  Q.{{ number_format($totalNonCancelledSum, 2) }} - {{ $totalNonCancelledRows }} Ordenes</div>
                        <div>Ordenes No Pagadas: Q.{{ number_format($totalNonCancelledNotPaidSum, 2) }} - {{ $totalNonCancelledNotPaidRows }} Ordenes</div>
                        <div>Ordenes: Q.{{ number_format($totalSum, 2) }} - {{ $totalRows }} Ordenes</div>

                    </div>
                    <div class="mt-4">
                    {{ $orders->appends([
                        'search_nombre' => request('search_nombre'),
                        'search_estado' => request('search_estado'),
                        'search_fecha' => request('search_fecha'),
                        'search_mensajero' => request('search_mensajero'),
                        'search_pago' => request('search_pago'),
                        'search_vendedor' => request('search_vendedor'),
                        'search_fecha_inicio' => request('search_fecha_inicio'),
                        'search_fecha_fin' => request('search_fecha_fin'),
                        'search_output' => request('search_output'),
                    ])->links() }}
                    </div>
                    <table class="w-full min-w-full divide-y divide-gray-200 mt-4 text-sm">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"></th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orden</th>
                            
                            <th class="w-1/4 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th> <!-- Wider column -->
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ciudad</th>
                            <th class="w-1/4 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Productos</th> <!-- Wider column -->
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="w-1/4 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notas</th> <!-- Wider column -->
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($orders as $order)
                                <tr>                                    
                                    <td class="px-4 py-4 whitespace-nowrap inline-flex items-center space-x-2">
                                    @php
                                        $user_batch = getUserBatch($order->vendedor) ? getUserBatch($order->vendedor)->batch : '';
                                        
                                    @endphp
                                    {!!  $user_batch !!}

                                    @switch($order->mensajero)
                                        @case('RAINER')
                                            <svg width="25" height="25" viewBox="0 0 25 25" xmlns="http://www.w3.org/2000/svg">
                                                <rect x="0" y="0" width="25" height="25" fill="#fc00ff" />
                                                <text x="50%" y="40%" font-size="14" font-weight="bold" fill="white" text-anchor="middle" alignment-baseline="middle" dy=".3em">RA</text>
                                            </svg>
                                            @break
                                        @case('SAMUEL')
                                            <svg width="25" height="25" viewBox="0 0 25 25" xmlns="http://www.w3.org/2000/svg">
                                                <rect x="0" y="0" width="25" height="25" fill="#e85e00" />
                                                <text x="50%" y="40%" font-size="14" font-weight="bold" fill="white" text-anchor="middle" alignment-baseline="middle" dy=".3em">SA</text>
                                            </svg>
                                            @break
                                        @case('CAEX')
                                            <svg width="25" height="25" viewBox="0 0 25 25" xmlns="http://www.w3.org/2000/svg">
                                                <rect x="0" y="0" width="25" height="25" fill="#7f8796" />
                                                <text x="50%" y="40%" font-size="14" font-weight="bold" fill="white" text-anchor="middle" alignment-baseline="middle" dy=".3em">CA</text>
                                            </svg>
                                            @break
                                        @case('FORZA')
                                            <svg width="25" height="25" viewBox="0 0 25 25" xmlns="http://www.w3.org/2000/svg">
                                                <rect x="0" y="0" width="25" height="25" fill="#7f1796" />
                                                <text x="50%" y="40%" font-size="14" font-weight="bold" fill="white" text-anchor="middle" alignment-baseline="middle" dy=".3em">FZ</text>
                                            </svg>
                                            @break
                                        @case('KEVIN_MENSAJERO')
                                            <svg width="25" height="25" viewBox="0 0 25 25" xmlns="http://www.w3.org/2000/svg">                                                 
                                                <rect x="0" y="0" width="25" height="25" fill="#ff69b4" />                                                 
                                                <text x="50%" y="40%" font-size="14" font-weight="bold" fill="white" text-anchor="middle" alignment-baseline="middle" dy=".3em">KM</text>                                             
                                            </svg>
                                            @break
                                        @case('OSCAR')
                                            <svg width="25" height="25" viewBox="0 0 25 25" xmlns="http://www.w3.org/2000/svg">                                                 
                                                <rect x="0" y="0" width="25" height="25" fill="#0f69b4" />                                                 
                                                <text x="50%" y="40%" font-size="14" font-weight="bold" fill="white" text-anchor="middle" alignment-baseline="middle" dy=".3em">OS</text>                                             
                                            </svg>
                                            @break
                                        @default
                                            
                                    @endswitch  
                                    @if ($order->pagado)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="ml-4" width="30" height="30" viewBox="0 0 30 30">

                                        <circle cx="15" cy="15" r="12" stroke="red" stroke-width="2" fill="none" />
                                        <circle cx="15" cy="15" r="9" stroke="red" stroke-width="1" fill="none" />
                                        <text x="51%" y="52%" text-anchor="middle" fill="red" font-size="16" font-family="Arial" dy=".3em" font-weight="bold" >P</text>
                                    </svg>
                                    @endif
                                    @if ($order->facturado)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="ml-4" width="30" height="30" viewBox="0 0 30 30" style="margin-left:1px;">

                                        <circle cx="15" cy="15" r="12" stroke="black" stroke-width="2" fill="none" />
                                        
                                        <text x="51%" y="52%" text-anchor="middle" fill="black" font-size="16" font-family="Arial" dy=".3em" font-weight="bold" >F</text>
                                    </svg>
                                    @endif
                                    
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-xs">{{ $order->created_at->format('d/m/y H:i') }}</td>
                                    <td class="
                                        {{ $order->estado === 'enviado' ? 'bg-green-500 text-white' : '' }}
                                        {{ $order->estado === 'cancelado' ? 'bg-red-500 text-white' : '' }}
                                        {{ $order->estado === 'creado' ? 'bg-slate-400 text-white' : '' }}
                                         {{ $order->estado === 'completado' ? 'bg-blue-300 text-white' : '' }}
                                        px-4 py-4 whitespace-nowrap
                                    ">
                                        {{ $order->id_shopify }}
                                    </td>
                                    <td class="px-4 py-4 whitespace-normal text-sm break-words">{{ $order->nombre_cliente }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap">{{ $order->municipio_cliente }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap">{{ $order->telefono_cliente }}</td>
                                    <td class="px-4 py-4 whitespace-nowrap">{{ number_format($order->total, 2) }}</td>

                                    <td class="px-4 py-4 whitespace-normal text-xs break-words text-red-500 font-bold ">{!! nl2br(e($order->notas)) !!}</td>
                                    <td class="px-4 py-4 whitespace-nowrap flex space-x-4 items-center">
                                        <a href="{{ route('orders.partialEdit', ['id' => $order->id, 'search_nombre' => request('search_nombre'), 'search_estado' => request('search_estado') , 'search_fecha' => request('search_fecha') , 'search_mensajero' => request('search_mensajero'), 'search_pago' => request('search_pago'),'search_vendedor' => request('search_vendedor'),'search_fecha_inicio' => request('search_fecha_inicio'),'search_fecha_fin' => request('search_fecha_fin'),'search_output' => request('search_output')]) }}" class="text-green-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 0 0-3.213-9.193 2.056 2.056 0 0 0-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 0 0-10.026 0 1.106 1.106 0 0 0-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />
                                            </svg>
                                        </a>    
                                        <a href="{{ route('orders.edit', ['id' => $order->id, 'search_nombre' => request('search_nombre'), 'search_estado' => request('search_estado') , 'search_fecha' => request('search_fecha') , 'search_mensajero' => request('search_mensajero'), 'search_pago' => request('search_pago') ,'search_vendedor' => request('search_vendedor'),'search_fecha_inicio' => request('search_fecha_inicio'),'search_fecha_fin' => request('search_fecha_fin'),'search_output' => request('search_output')]) }}" class="text-yellow-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                            </svg>
                                        </a>
                                        <!-- 
                                        <a href="{{ route('orders.cancel', ['id' => $order->id, 'search' => request('search')]) }}" class="text-red-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                            </svg>
                                        </a>
                                        
                                        <form action="{{ route('orders.destroy', $order->id) }}" method="POST" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 ml-2">Delete</button>
                                        </form>
                                        -->
                                    </td>
                                </tr>
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td></td>
                                        <td class="px-4 py-4 whitespace-normal text-xs max-w-xs break-words">{{ $item->cantidad }} x {{ $item->descripcion }}</td>
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
                        'search_estado' => request('search_estado'),
                        'search_fecha' => request('search_fecha'),
                        'search_mensajero' => request('search_mensajero'),
                        'search_pago' => request('search_pago'),
                        'search_vendedor' => request('search_vendedor'),
                        'search_fecha_inicio' => request('search_fecha_inicio'),
                        'search_fecha_fin' => request('search_fecha_fin'),
                        'search_output' => request('search_output'),
                    ])->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>