<!-- resources/views/records/edit.blade.php -->

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar Orden') }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            
                <div class="p-6 bg-white border-b border-gray-200">
                    <a target="_blank" href="{{ route('orders.pdf', ['id' => $id, 'search_nombre' => request('search'), 'search_estado' => request('search_estado') , 'search_fecha' => request('search_fecha') , 'search_type' => request('search_type')]) }}" class="inline-flex items-center justify-center px-6 py-2 bg-blue-500 text-white font-semibold rounded-lg shadow-md hover:bg-blue-400 transition duration-200 ease-in-out">
                        Imprimir
                    </a>
                    
                    <a href="{{ route('orders.index', ['search_nombre' => request('search'), 'search_estado' => request('search_estado') , 'search_fecha' => request('search_fecha') , 'search_type' => request('search_type')]) }}" class="inline-flex items-center justify-center px-6 py-2 bg-gray-500 text-white font-semibold rounded-lg shadow-md hover:bg-gray-400 transition duration-200 ease-in-out">
                        Regresar
                    </a>      
                            
            </div>
        </div>
    </div>
</x-app-layout>