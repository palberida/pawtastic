<!-- resources/views/records/index.blade.php -->

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Estado de cuenta') }}
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
                        <form method="GET" action="{{ route('accounting.index') }}" class="flex flex-wrap items-center gap-4 w-full">
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
                        </form>
                    </div>
                    
                    <table class="w-full min-w-full divide-y divide-gray-200 mt-4 text-sm">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Id</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Documento</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Autorizaciones</th>                      
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($orders as $order)
                                <tr style="{{ $order->tipo_transaccion == 'NC' ? 'background-color:#dafbd9;' : 'background-color:#ffe7e2;' }}">
                                    <td class="px-4 py-4 whitespace-nowrap">{{  $order->id}}</td>
                                    <td class="px-4 py-4 whitespace-nowrap">{{  $order->fecha_transaccion}}</td>
                                    <td class="px-4 py-4 whitespace-nowrap">{{ $order->tipo_transaccion }}</td>
                                    <td class="py-1 px-2 border-b">{{ $order->descripcion }}</td>
                                    <td class="py-1 px-2 border-b">{{ $order->numero_documento }}</td>
                                    <td class="py-1 px-2 border-b" style="font-weight:bold">{{ number_format($order->total,2) }}</td>
                                    <td class="py-1 px-2 border-b">{{ $order->autorizaciones }}</td>
                                </tr>
                                
                            @endforeach
                        </tbody>
                        
                    </table>
                    
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

