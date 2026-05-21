<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Ordenes Atascadas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class=" mx-auto sm:px-2 lg:px-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if (session('success'))
                        <div class="mb-4 text-green-600">{{ session('success') }}</div>
                    @endif
                   
                    <div class="mb-4 w-full">
                        <form method="GET" action="{{ route('shipments') }}" class="flex flex-wrap items-center gap-4 w-full">
                            


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

                    
                    <table class="w-full min-w-full divide-y divide-gray-200 mt-4 text-sm">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orden</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre</th>
                            <th class="w-1/4 px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Direccion</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Guia</th>
                            
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($orders as $order)
                            <tr style="{{ $order->created_at->lt(now()->subWeek()) ? 'background-color:#d1ffbd;' : '' }}">
                                <td class="px-4 py-4 whitespace-normal">{{ $order->id_shopify }}</td>
                                <td class="px-4 py-4 whitespace-nowrap text-xs">{{ $order->created_at->format('d/m/y') }}</td>
                                <td class="px-4 py-4 whitespace-normal text-sm break-words">{{ $order->nombre_cliente }}</td>
                                <td class="px-4 py-4 whitespace-normal text-xs break-words">{{ $order->direccion_factura }}</td>
                                <td class="px-4 py-4 whitespace-normal text-xs break-words">{{ $order->guia }}</td>
                                
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