<!-- resources/views/records/edit.blade.php -->

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Calculadora') }}
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
                   
                    <h3 class="text-lg font-semibold mb-2">Productos</h3>

                    <div class="flex items-center gap-3 mb-4">

                        <select id="variant-select" class="border rounded px-3 py-2 flex-1 text-sm">
                            <option value="">Selecciona una variante...</option>
                            @foreach ($combos as $combo)
                                <option value="{{ $combo->id }}" data-price="{{ $combo->precio }}">
                                    {{ $combo->descripcion }} (Q{{ number_format($combo->precio, 2) }})
                                </option>
                            @endforeach
                            @foreach ($variants as $variant)
                                <option value="{{ $variant->id }}" data-price="{{ $variant->precio }}">
                                    {{ $variant->descripcion }} (Q{{ number_format($variant->precio, 2) }})
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

        li.className = 'flex justify-between items-center border rounded px-3 py-2 text-sm';

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




                        
                        
                        

                </div>
            </div>
        </div>
    </div>
</x-app-layout>