<!-- resources/views/records/index.blade.php -->

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Transferencias') }}
        </h2>
    </x-slot>

    <div class="py-12">
    
        <div class="max-w-7xl mx-auto sm:px-2 lg:px-4">
            
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">

            
                <div class="p-6 bg-white border-b border-gray-200">
                    @if (session('success'))
                        <div class="mb-4 text-green-600">{{ session('success') }}</div>
                    @endif
                    <div class="flex justify-end mb-4">
                        <a href="{{ route('transfers.new') }}" class="custom-button bg-blue-700 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Nueva transferencia
                        </a>
                    </div>
                    
                    <table class="min-w-full divide-y divide-gray-200 mt-4">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Codigo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripcion</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rango inicio</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Rango fin</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ordenes</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($transfers as $transfer)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $transfer->codigo }}</td>
                                    <td class="px-6 py-4 whitespace-normal text-xs max-w-xs break-words">{{ $transfer->descripcion }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $transfer->total }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $transfer->updated_at->format('d/m/y H:i:s') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">@if ($transfer->rango_inicio)
                                        {{ $transfer->rango_inicio->format('d/m/y H:i:s') }}
                                    @else
                                        N/A
                                    @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">{{ $transfer->rango_fin->format('d/m/y H:i:s') }}</td>
                                    <td class="px-6 py-4">{{ $transfer->orders->pluck('id_shopify')->implode(', ') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>