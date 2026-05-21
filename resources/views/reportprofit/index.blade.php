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
                        <form method="GET" action="{{ route('report-profit.index') }}" class="flex flex-wrap items-center gap-4 w-full">
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
                        <div style="font-size:20px; font-weight:bold;">Rango seleccionado</div>
                        <div class="font-bold">Total Ordenes:  {{ $total_revenue->conteo }}</div>
                        <div class="font-bold">Total Productos:  {{ $totals->total }}</div>
                        <div class="font-bold" >Total Ventas:  <span style="color:green;">Q.{{ number_format($total_revenue->total,2) }}</span></div>
                        <div class="font-bold" >Total Costo:  <span style="color:red;">Q.{{ number_format($totals->total_costo,2) }}</span></div>
                        <div class="font-bold" >Total Descuento:  <span style="color:red;">Q.{{ number_format($totals->total_descuento,2) }}</span></div>
                        <div class="font-bold" >Total Ads:  <span style="color:red;">Q.{{ number_format($totalads->total,2) }}</span></div>
                        <div class="font-bold" >Total Planilla:  <span style="color:red;">Q.{{ number_format($totalpayroll->total,2) }}</span></div>
                        <div class="font-bold" >Total Recurrentes (funcionamiento):  <span style="color:red;">Q.{{ number_format($totalexpenses->total,2) }}</span></div>
                        <div class="font-bold" >Total Bonos por Venta:  <span style="color:red;">Q.{{ number_format($totalbonus->total,2) }}</span></div>
                        <div class="font-bold" >Total Envios por Venta:  <span style="color:red;">Q.{{ number_format($totalenvios->total,2) }}</span></div>

                        <div class="font-bold">Total Ganancia (falta pagar impuestos): <span
                        @if ( $total_revenue->total - $totals->total_costo - $totalads->total - $totalbonus->total - $totalpayroll->total - $totalexpenses->total - $totalenvios->total > 0  )    
                            style="color:green;"
                        @else
                            style="color:red;"
                        @endif
                        >Q.{{ number_format($total_revenue->total - $totals->total_costo - $totalads->total - $totalbonus->total - $totalpayroll->total - $totalexpenses->total - $totalenvios->total,2) }}</span></div>
                        <br/>
                        <div style="font-size:20px; font-weight:bold;">Histórico</div>
                        
                        <div class="font-bold" >Total Ingresos:  <span style="color:green;">Q.{{ number_format($total_historico_revenue1->total,2) }}</span></div>
                        <div class="font-bold" >Total Egresos Recurrentes:  <span style="color:red;">Q.{{ number_format($total_historico_expenses->total,2) }}</span></div>
                        <div class="font-bold" >Total Egresos Unicos:  <span style="color:red;">Q.{{ number_format($total_historico_expenses2->total,2) }}</span></div>
                        <div class="font-bold" >Total Egresos Ads:  <span style="color:red;">Q.{{ number_format($total_historico_expenses3->total,2) }}</span></div>
                        <div class="font-bold" >Total Egresos Impuestos:  <span style="color:red;">Q.{{ number_format($total_historico_taxes->total,2) }}</span></div>
                        <div class="font-bold" >Total:  <span style="color:red;">Q.{{ number_format($total_historico_revenue1->total - $total_historico_expenses->total - $total_historico_expenses2->total - $total_historico_taxes->total - $total_historico_expenses3->total,2) }}</span></div>

                    </div>                    
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
