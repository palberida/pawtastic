<!-- resources/views/records/index.blade.php -->

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Editar Imprevistos') }}
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
                   
                    
                    <form method="POST" action="{{ route('order-problem.update', ['id' => $problem->id]) }}" class="">
                        @csrf  
                        <div>
                            <label for="dia" class="block text-sm font-medium text-gray-700">Dia</label>
                            <input type="date" name="dia" id="dia" value="{{ old('dia', $problem->dia) }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"><br>
                        </div>
                        <div>
                        <label for="orden" class="block text-sm font-medium text-gray-700"># Orden</label>
                        <input type="text" name="orden" id="orden" value="{{ old('orden', $problem->orden) }}" maxlength="250" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"><br>
                        </div>
                        <label for="notas" class="block text-sm font-medium text-gray-700">Notas</label>
                        <input type="text" name="notas" id="notas" value="{{ old('notas', $problem->notas) }}" maxlength="250" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"><br>
                        </div>
                        <div>
                        <label for="tipo" class="block text-sm font-medium text-gray-700">Tipo</label>
                        <select id="tipo" name="tipo" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="devolucion" {{ old('tipo', $problem->tipo) == 'devolucion' ? 'selected' : '' }}>Devolución</option>
                            <option value="garantia" {{ old('tipo', $problem->tipo) == 'garantia' ? 'selected' : '' }}>Garantía</option>
                        </select>  
                        </div>
                        
                        
                    </form>  
                </div>
            </div>
        </div>
    </div>
</x-app-layout>