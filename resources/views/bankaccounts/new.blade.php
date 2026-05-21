<!-- resources/views/records/index.blade.php -->

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Cuentas de Banco') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-2 lg:px-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if (session('success'))
                        <div class="mb-4 text-green-600">{{ session('success') }}</div>
                    @endif
                    @if($errors->any())
                        <div style="color: red;">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                   
                    
                    <form method="POST" action="{{ route('bank-accounts.store') }}" class="">
                        @csrf  
                        <div>
                            <label for="mes" class="block text-sm font-medium text-gray-700">Mes</label>
                            <input type="date" name="mes" id="mes" value="{{ old('mes', date('Y-m-d')) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"><br>
                        </div>
                        <div>
                        <div>
                        <label for="ingresos" class="block text-sm font-medium text-gray-700">Ingresos</label>
                        <input type="number" step="0.01" name="ingresos" id="ingresos" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"><br>
                        </div>
                        <div>
                        <label for="egresos" class="block text-sm font-medium text-gray-700">Egresos</label>
                        <input type="number" step="0.01" name="egresos" id="egresos" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"><br>
                        </div>
                        
                        <div class="mt-4">
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">Guardar</button>
                        </div>
                        
                    </form>  
                </div>
            </div>
        </div>
    </div>
</x-app-layout>