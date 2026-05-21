<!-- resources/views/records/index.blade.php -->

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Gastos') }}
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
                   
                    
                    <form method="POST" action="{{ route('expenses.store') }}" class="">
                        @csrf  
                        <div>
                            <label for="dia" class="block text-sm font-medium text-gray-700">Dia</label>
                            <input type="date" name="dia" id="dia" value="{{ old('dia', date('Y-m-d')) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"><br>
                        </div>
                        <div>
                        <label for="proveedor" class="block text-sm font-medium text-gray-700">Proveedor</label>
                        <input type="text" name="proveedor" id="proveedor" maxlength="250" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"><br>
                        </div>
                        <div>
                        <label for="descripcion" class="block text-sm font-medium text-gray-700">Descripcion</label>
                        <input type="text" name="descripcion" id="descripcion" maxlength="250" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"><br>
                        </div>
                        <div>
                        <label for="valor" class="block text-sm font-medium text-gray-700">Valor</label>
                        <input type="number" step="0.01" name="valor" id="valor" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"><br>
                        </div>
                        <div>
                        <label for="moneda" class="block text-sm font-medium text-gray-700">Moneda</label>
                        <select id="moneda" name="moneda" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="GTQ">GTQ</option>
                            <option value="USD">USD</option>
                        </select>  
                        </div>
                        
                        <div>
                        <label for="tipo_pago" class="block text-sm font-medium text-gray-700">Tipo Pago</label>
                        <select id="tipo_pago" name="tipo_pago" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="unico">unico</option>
                            <option value="recurrente">recurrente</option>
                        </select> 
                        </div>
                        <div id="tipo_container" style="display:none">
                        <label for="tipo" class="block text-sm font-medium text-gray-700">Tipo</label>
                        <select id="tipo" name="tipo" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="mensual">mensual</option>
                            <option value="anual">anual</option>
                            <option value="diario">diario</option>
                        </select>  
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
<script>
document.getElementById('tipo_pago').addEventListener('change', function() {
    var tipoContainer = document.getElementById('tipo_container');
    if (this.value === 'unico') {
        tipoContainer.style.display = 'none';
    } else {
        tipoContainer.style.display = 'block';
    }
});
</script>