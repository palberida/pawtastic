<!-- resources/views/records/edit.blade.php -->

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Nueva Transferencia') }}
        </h2>
    </x-slot>
    <div class="py-12">

        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="p-6 bg-white border-b border-gray-200">

                    <form method="POST" action="{{ route('transfers.store') }}">
                        @csrf
                        <input type="hidden" name="rango_fin" value="{{ $rangoFin }}">
                        <input type="hidden" name="rango_inicio" value="{{ $rangoInicio }}">
                        <div>
                            <label for="total" class="block text-sm font-medium text-gray-700">Total</label>
                            <input readonly type="text" id="total" name="total" value="{{ old('total', $total) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="codigo" class="block text-sm font-medium text-gray-700">Codigo</label>
                            <input type="text" id="codigo" name="codigo"  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                        </div>
                        <div>
                            <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripcion</label>
                            <input type="text" id="descripcion" name="descripcion" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                        </div>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-700  text-white rounded">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>