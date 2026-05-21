<!-- resources/views/records/edit.blade.php -->

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Agregar Orden') }}
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
                    <form method="POST" action="{{ route('orders.save') }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="search" value="{{ $search }}">
                        <div>
                            <label for="nombre_cliente" class="block text-sm font-medium text-gray-700">Nombre</label>
                            <input type="text" id="nombre_cliente" name="nombre_cliente" value="" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                        </div>
                        <div>
                            <label for="direccion_cliente" class="block text-sm font-medium text-gray-700">Direccion</label>
                            <input type="text" id="direccion_cliente" name="direccion_cliente" value="" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                        </div>
                        <div>
                            <label for="departamento_cliente" class="block text-sm font-medium text-gray-700">Departamento</label>
                            <input type="text" id="departamento_cliente" name="departamento_cliente" value="" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="municipio_cliente" class="block text-sm font-medium text-gray-700">Municipio</label>
                            <input type="text" id="municipio_cliente" name="municipio_cliente" value="" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="telefono1_cliente" class="block text-sm font-medium text-gray-700">Telefono 1</label>
                            <input type="text" id="telefono1_cliente" name="telefono1_cliente" value="" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                        </div>
                        <div>
                            <label for="telefono2_cliente" class="block text-sm font-medium text-gray-700">Telefono 2</label>
                            <input type="text" id="telefono2_cliente" name="telefono2_cliente" value="" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="email_cliente" class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="text" id="email_cliente" name="email_cliente" value="" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="nit_cliente" class="block text-sm font-medium text-gray-700">NIT</label>
                            <input type="text" id="nit_cliente" name="nit_cliente" value="" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        <div>
                            <label for="vendedor" class="block text-sm font-medium text-gray-700">Vendedor                         
                            </label>
                            <select id="vendedor" name="vendedor" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>
                            @foreach(getUsersWithRole(3) as $seller)
                                <option value="{{ $seller->seller_code }}">{{ $seller->seller_code }}</option>

                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="forma_pago" class="block text-sm font-medium text-gray-700">Forma de Pago</label>
                            <select id="forma_pago" name="forma_pago" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required>   
                                <option value="Cash on Delivery (COD)">Pago contra entrega</option>
                                <option value="cyber_source">Pago con tarjeta de credito directo en la página</option>
                                <option value="Bank Deposit" >Transferencia bancaria</option>
                                <option value="Link de Pago con VisaNet">Link de pago con VisaNet</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="guia" class="block text-sm font-medium text-gray-700">Guia</label>
                            <input type="text" id="guia" name="guia" value="" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>
                        
                        <div class="mt-4">
                            <label for="notas" class="block text-sm font-medium text-gray-700">Notas</label>
                            <textarea id="notas" name="notas" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        </div>



                        





<h3 class="text-lg font-semibold mb-2">Productos</h3>

<div class="flex items-center gap-3 mb-4">
    <select id="variant-select" class="border rounded px-3 py-2 flex-1">
        <option value="">Selecciona una variante...</option>
        @foreach ($variants as $variant)
            <option value="{{ $variant->id }}" data-price="{{ $variant->precio }}">
                {{ $variant->product->descripcion }} - {{ $variant->descripcion }} (Q{{ number_format($variant->precio, 2) }})
            </option>
        @endforeach
    </select>

    <input type="number" id="variant-qty" min="1" value="1" class="border rounded px-2 py-2 w-24 text-center" placeholder="Cant.">

    <button type="button" id="add-variant" class="px-4 py-2 bg-blue-700  text-white rounded">
        Agregar
    </button>
</div>

<ul id="variant-list" class="space-y-2"></ul>
<div class="mt-4 text-right text-lg font-semibold">
    Total: Q<span id="order-total">0.00</span>
</div>


<div id="variant-hidden-inputs"></div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const select = document.getElementById('variant-select');
    const qtyInput = document.getElementById('variant-qty');
    const addBtn = document.getElementById('add-variant');
    const list = document.getElementById('variant-list');
    const hiddenInputs = document.getElementById('variant-hidden-inputs');
    const totalEl = document.getElementById('order-total');

    let total = 25;

    function updateTotalDisplay() {
        totalEl.textContent = total.toFixed(2);
    }

    addBtn.addEventListener('click', () => {
        const variantId = select.value;
        const option = select.options[select.selectedIndex];
        const variantText = option?.text;
        const price = parseFloat(option?.dataset.price || 0);
        const qty = parseInt(qtyInput.value);

        if (!variantId || !qty || qty < 1) {
            return alert('Selecciona una variante y cantidad válida.');
        }

        // Prevent duplicates
        if (document.getElementById('variant-item-' + variantId)) {
            return alert('Esta variante ya fue agregada.');
        }

        const subtotal = price * qty;
        total += subtotal;
        updateTotalDisplay();

        // Add to visible list
        const li = document.createElement('li');
        li.id = 'variant-item-' + variantId;
        li.className = 'flex justify-between items-center border rounded px-3 py-2';
        li.innerHTML = `
            <span>${variantText} — Cant: ${qty} — Subtotal: Q${subtotal.toFixed(2)}</span>
            <button type="button" class="text-red-500 hover:underline" onclick="removeVariant(${variantId}, ${subtotal})">Eliminar</button>
        `;
        list.appendChild(li);

        // Add hidden inputs for form submission
        const hidden = document.createElement('div');
        hidden.id = 'hidden-variant-' + variantId;
        hidden.innerHTML = `
            <input type="hidden" name="variants[${variantId}][selected]" value="1">
            <input type="hidden" name="variants[${variantId}][quantity]" value="${qty}">
        `;
        hiddenInputs.appendChild(hidden);

        // Reset selection
        select.value = '';
        qtyInput.value = 1;
    });

    window.removeVariant = (id, subtotal) => {
        document.getElementById('variant-item-' + id)?.remove();
        document.getElementById('hidden-variant-' + id)?.remove();
        total -= subtotal;
        updateTotalDisplay();
    };
});
</script>




                        
                        
                        
                        
                        <div class="mt-4">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-700  text-white rounded">Guardar</button>
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