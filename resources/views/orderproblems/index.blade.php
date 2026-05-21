<!-- resources/views/records/index.blade.php -->

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Imprevistos') }}
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
                        <a href="{{ route('order-problems.new') }}" class="custom-button bg-blue-700 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            Nueva Devolucion/Garantia
                        </a>
                    </div>
                    
                    <table class="w-full min-w-full divide-y divide-gray-200 mt-4 text-sm">
                        <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Orden</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tipo</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notas</th>

                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach ($problems as $problem)
                                <tr>
                                    <td class="px-4 py-4 whitespace-nowrap">{{  $problem->dia}}</td>
                                    <td class="px-4 py-4 whitespace-nowrap">{{ $problem->id_orden }}</td>
                                    <td class="py-1 px-2 border-b">{{ $problem->tipo }}</td>
                                    <td class="py-1 px-2 border-b">{{ $problem->notas }}</td>
                                    
                                    <td class="px-4 py-4 whitespace-nowrap flex space-x-4 items-center">
                                        <a href="{{ route('order-problems.edit', ['id' => $problem->id]) }}" class="text-green-500">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                            </svg>
                                        </a>
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

