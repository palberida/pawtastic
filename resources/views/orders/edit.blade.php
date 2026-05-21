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
                @if ($errors->any())
                    <div class="mb-4 text-red-600">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="py-6">
                        ORDEN #{{ $order->id_shopify }}
                    </div>
                    <form method="POST" action="{{ route('orders.update', $order->id) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="search" value="{{ $search }}">
                        <input type="hidden" name="estado" value="{{ $order->estado }}">
                        <div>
                            <label for="nombre_cliente" class="block text-sm font-medium text-gray-700">Nombre</label>
                            <input type="text" id="nombre_cliente" name="nombre_cliente" value="{{ old('name', $order->nombre_cliente) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                        </div>
                        <div>
                            <label for="direccion_cliente" class="block text-sm font-medium text-gray-700">Direccion</label>
                            <input type="text" id="direccion_cliente" name="direccion_cliente" value="{{ old('name', $order->direccion_cliente) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                        </div>
                        <div>
                            <label for="departamento_cliente" class="block text-sm font-medium text-gray-700">Departamento</label>
                            <input type="text" id="departamento_cliente" name="departamento_cliente" value="{{ old('name', $order->departamento_cliente) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="municipio_cliente" class="block text-sm font-medium text-gray-700">Municipio</label>
                            <input type="text" id="municipio_cliente" name="municipio_cliente" value="{{ old('name', $order->municipio_cliente) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="telefono1_cliente" class="block text-sm font-medium text-gray-700">Telefono 1</label>
                            <input type="text" id="telefono1_cliente" name="telefono1_cliente" value="{{ old('name', $order->telefono1_cliente) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                        </div>
                        <div>
                            <label for="telefono2_cliente" class="block text-sm font-medium text-gray-700">Telefono 2</label>
                            <input type="text" id="telefono2_cliente" name="telefono2_cliente" value="{{ old('name', $order->telefono2_cliente) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="email_cliente" class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="text" id="email_cliente" name="email_cliente" value="{{ old('name', $order->email_cliente) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="nit_cliente" class="block text-sm font-medium text-gray-700">NIT</label>
                            <input type="text" id="nit_cliente" name="nit_cliente" value="{{ old('name', $order->nit_cliente) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="vendedor" class="block text-sm font-medium text-gray-700">Vendedor                         
                            </label>
                            <select id="vendedor" name="vendedor" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                            @foreach(getUsersWithRole(3) as $seller)
                                <option value="{{ $seller->seller_code }}" {{ old('vendedor', $order->vendedor) == $seller->seller_code ? 'selected' : '' }}>{{ $seller->seller_code }}</option>

                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="forma_pago" class="block text-sm font-medium text-gray-700">Forma de Pago</label>
                            <select id="forma_pago" name="forma_pago" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>   
                                <option value="Cash on Delivery (COD)" {{ old('forma_pago', $order->forma_pago) == 'Cash on Delivery (COD)' ? 'selected' : '' }}>Pago contra entrega</option>
                                <option value="cyber_source" {{ old('forma_pago', $order->forma_pago) == 'cyber_source' ? 'selected' : '' }}>Pago con tarjeta de credito directo en la página</option>
                                <option value="Bank Deposit" {{ old('forma_pago', $order->forma_pago) == 'Bank Deposit' ? 'selected' : '' }}>Transferencia bancaria</option>
                                <option value="Link de Pago con VisaNet" {{ old('forma_pago', $order->forma_pago) == 'Link de Pago con VisaNet' ? 'selected' : '' }}>Link de pago con VisaNet</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="guia" class="block text-sm font-medium text-gray-700">Guia</label>
                            <input type="text" id="guia" name="guia" value="{{ old('name', $order->guia) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="costo_envio_aproximado" class="block text-sm font-medium text-gray-700">Costo Envio Aproximado</label>
                            <input type="text" id="costo_envio_aproximado" name="costo_envio_aproximado" value="{{ old('name', $order->costo_envio_aproximado) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div class="mt-4">
                            <label for="notas" class="block text-sm font-medium text-gray-700">Notas</label>
                            <textarea id="notas" name="notas" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('description', $order->notas) }}</textarea>
                        </div>
                        <div>
                            <label for="bank_statement_id" class="block text-sm font-medium text-gray-700">Id Estado Cuenta</label>
                            <input type="text" id="bank_statement_id" name="bank_statement_id" value="{{ old('name', $order->bank_statement_id) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        
                        <div class="mt-4 ">
                            <label for="productos" class="block text-sm font-medium text-gray-700">Productos</label>
                            <div class="flex items-center ">
                                <div >
                                    @foreach ($order->items as $item)
                                        {{ $item->cantidad }} x {{ $item->descripcion }}<br>
                                    @endforeach
                                    TOTAL Q.{{ number_format($order->total, 2) }}
                                </div>
                                @if ($order->pagado)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="ml-4" width="100" height="100" viewBox="0 0 100 100">
                                        <!-- Outer circles -->
                                        <circle cx="50" cy="50" r="35" stroke="red" stroke-width="3" fill="none" />
                                        <circle cx="50" cy="50" r="31" stroke="red" stroke-width="2" fill="none" />

                                        <!-- "PAGADO" text with an incline -->
                                        <text x="70%" y="37%" text-anchor="middle" fill="red" font-size="12" font-family="Arial" dy=".3em" font-weight="bold" transform="rotate(-20 100 100)">PAGADO</text>
                                    </svg>
                                @endif
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-700  text-white rounded">Guardar Cambios</button>
                            <a href="{{ route('orders.index', ['search_nombre' => request('search'), 'search_estado' => request('search_estado') , 'search_fecha' => request('search_fecha') , 'search_type' => request('search_type')]) }}" class="inline-flex items-center justify-center px-6 py-2 bg-gray-500 text-white font-semibold rounded-lg shadow-md hover:bg-gray-400 transition duration-200 ease-in-out">
                                 Regresar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>