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
                            @php
                                $sellers = getUsersWithRole(3)->map(function ($seller) {
                                    return ['value' => $seller->seller_code, 'label' => $seller->seller_code];
                                })->values()->all();
                            @endphp
                            <x-searchable-select
                                id="vendedor"
                                name="vendedor"
                                :options="$sellers"
                                :selected="$sellers[0]['value'] ?? null"
                                placeholder="Busca un vendedor..."
                                empty-text="Sin vendedores"
                                required />
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



                        





@php
    $productOptions = $products->map(function ($product) {
        return [
            'value' => $product->id,
            'label' => $product->descripcion,
            'variants' => $product->variants->map(function ($variant) {
                return [
                    'value' => $variant->id,
                    'label' => $variant->descripcion,
                    'precio' => (float) $variant->precio,
                    'stock' => (int) $variant->stock,
                ];
            })->values()->all(),
        ];
    })->values()->all();
@endphp

<h3 class="text-lg font-semibold mt-6">Productos</h3>
{{-- Los menús usan las clases .ss-* y el helper ssMenuStyle() que define el componente
     x-searchable-select (presente en esta página, en el campo Vendedor). --}}
<style>
    .pp-row { display: flex; flex-wrap: wrap; align-items: flex-end; gap: .75rem; margin-top: .5rem; }
    .pp-col-producto { flex: 3 1 16rem; }
    .pp-col-variante { flex: 2 1 14rem; }
    .pp-col-cantidad { flex: 0 0 6rem; }
    .pp-col-boton { flex: 0 0 8rem; }
    .pp-add { width: 100%; padding: .5rem 1rem; border-radius: .375rem; background: #1d4ed8; color: #fff; }
    .pp-add:disabled { background: #9ca3af; cursor: not-allowed; }
    .pp-meta { color: #6b7280; }
    .pp-item { display: flex; justify-content: space-between; align-items: center; gap: .75rem; border: 1px solid #e5e7eb; border-radius: .375rem; padding: .5rem .75rem; margin-bottom: .5rem; }
    .pp-remove { color: #ef4444; }
    .pp-remove:hover { text-decoration: underline; }
    .pp-empty { padding: .25rem 0; font-size: .875rem; color: #6b7280; }
    .pp-totals { text-align: right; }
</style>

<div
    x-data="orderProductPicker({
        products: {{ json_encode($productOptions, JSON_UNESCAPED_UNICODE) }},
        envio: 30
    })"
    @scroll.window="reposition()"
    @resize.window="reposition()"
    class="mb-4"
>
    <div class="pp-row">
        {{-- Producto --}}
        <div class="pp-col-producto relative" @click.outside="product.open = false; syncProductLabel()">
            <label class="block text-sm font-medium text-gray-700">Producto</label>
            <input
                type="text"
                x-ref="productSearch"
                x-model="product.query"
                @focus="openProducts()"
                @click="openProducts()"
                @keydown.arrow-down.prevent="moveProduct(1)"
                @keydown.arrow-up.prevent="moveProduct(-1)"
                @keydown.enter.prevent="chooseProduct(filteredProducts[product.highlight])"
                @keydown.escape.prevent="product.open = false; syncProductLabel()"
                placeholder="Escribe para buscar un producto..."
                autocomplete="off"
                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
            <ul x-ref="productMenu" x-show="product.open" :style="product.style" style="display: none" class="ss-menu">
                <template x-if="!filteredProducts.length">
                    <li class="ss-empty">Sin resultados</li>
                </template>
                <template x-for="(option, i) in filteredProducts" :key="option.value">
                    <li
                        @click="chooseProduct(option)"
                        @mouseenter="product.highlight = i"
                        :class="{ 'is-active': product.highlight === i, 'is-selected': option.value == product.value }"
                        class="ss-option"
                    >
                        <span x-text="option.label"></span>
                        <span class="pp-meta shrink-0" x-text="option.variants.length + ' variantes'"></span>
                    </li>
                </template>
            </ul>
        </div>

        {{-- Variante (se llena sola al elegir el producto) --}}
        <div class="pp-col-variante relative" @click.outside="variant.open = false; syncVariantLabel()">
            <label class="block text-sm font-medium text-gray-700">Variante</label>
            <input
                type="text"
                x-ref="variantSearch"
                x-model="variant.query"
                :disabled="!product.value"
                @focus="openVariants()"
                @click="openVariants()"
                @keydown.arrow-down.prevent="moveVariant(1)"
                @keydown.arrow-up.prevent="moveVariant(-1)"
                @keydown.enter.prevent="chooseVariant(filteredVariants[variant.highlight])"
                @keydown.escape.prevent="variant.open = false; syncVariantLabel()"
                :placeholder="product.value ? 'Escribe para filtrar variantes...' : 'Selecciona un producto primero'"
                autocomplete="off"
                class="ss-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            >
            <ul x-ref="variantMenu" x-show="variant.open" :style="variant.style" style="display: none" class="ss-menu">
                <template x-if="!filteredVariants.length">
                    <li class="ss-empty">Sin resultados</li>
                </template>
                <template x-for="(option, i) in filteredVariants" :key="option.value">
                    <li
                        @click="chooseVariant(option)"
                        @mouseenter="variant.highlight = i"
                        :class="{ 'is-active': variant.highlight === i, 'is-selected': option.value == variant.value }"
                        class="ss-option"
                    >
                        <span x-text="option.label"></span>
                        <span class="shrink-0" :class="option.stock > 0 ? 'pp-meta' : 'text-red-500'">
                            Q<span x-text="option.precio.toFixed(2)"></span> ·
                            <span x-text="option.stock > 0 ? 'stock ' + option.stock : 'sin stock'"></span>
                        </span>
                    </li>
                </template>
            </ul>
        </div>

        <div class="pp-col-cantidad">
            <label class="block text-sm font-medium text-gray-700">Cant.</label>
            <input type="number" x-model.number="qty" min="1" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm text-center">
        </div>

        <div class="pp-col-boton">
            <button type="button" @click="addItem()" :disabled="!variant.value" class="pp-add">
                Agregar
            </button>
        </div>
    </div>

    <p x-show="selectedVariant && qty > selectedVariant.stock" style="display: none" class="mt-2 text-sm text-red-600">
        Solo hay <span x-text="selectedVariant ? selectedVariant.stock : 0"></span> en inventario de esta variante.
    </p>

    <ul class="mt-4">
        <template x-for="item in items" :key="item.id">
            <li class="pp-item">
                <span>
                    <span x-text="item.label"></span>
                    — Cant: <span x-text="item.qty"></span>
                    — Subtotal: Q<span x-text="(item.precio * item.qty).toFixed(2)"></span>
                </span>
                <button type="button" class="pp-remove" @click="removeItem(item.id)">Eliminar</button>
            </li>
        </template>
        <template x-if="!items.length">
            <li class="pp-empty">Aún no has agregado productos.</li>
        </template>
    </ul>

    <div class="pp-totals mt-4 text-sm">
        Productos: Q<span x-text="itemsTotal.toFixed(2)"></span> · Envío: Q<span x-text="envio.toFixed(2)"></span>
    </div>
    <div class="pp-totals text-lg font-semibold">
        Total: Q<span x-text="(itemsTotal + envio).toFixed(2)"></span>
    </div>

    <template x-for="item in items" :key="'input-' + item.id">
        <div>
            <input type="hidden" :name="'variants[' + item.id + '][selected]'" value="1">
            <input type="hidden" :name="'variants[' + item.id + '][quantity]'" :value="item.qty">
        </div>
    </template>
</div>

<script>
window.orderProductPicker = function ({ products = [], envio = 30 }) {
    const matches = (label, query) => {
        const q = query.trim().toLowerCase();
        if (!q) return true;
        const haystack = label.toLowerCase();
        return q.split(/\s+/).every(term => haystack.includes(term));
    };

    return {
        products,
        envio,
        qty: 1,
        items: [],
        product: { value: '', query: '', open: false, highlight: 0, style: {} },
        variant: { value: '', query: '', open: false, highlight: 0, style: {} },

        get selectedProduct() {
            return this.products.find(p => String(p.value) === String(this.product.value)) || null;
        },
        get selectedVariant() {
            if (!this.selectedProduct) return null;
            return this.selectedProduct.variants.find(v => String(v.value) === String(this.variant.value)) || null;
        },
        get filteredProducts() {
            if (this.selectedProduct && this.product.query === this.selectedProduct.label) return this.products;
            return this.products.filter(p => matches(p.label, this.product.query));
        },
        get filteredVariants() {
            const variants = this.selectedProduct ? this.selectedProduct.variants : [];
            if (this.selectedVariant && this.variant.query === this.selectedVariant.label) return variants;
            return variants.filter(v => matches(v.label, this.variant.query));
        },
        get itemsTotal() {
            return this.items.reduce((sum, item) => sum + item.precio * item.qty, 0);
        },

        syncProductLabel() {
            this.product.query = this.selectedProduct ? this.selectedProduct.label : '';
        },
        syncVariantLabel() {
            this.variant.query = this.selectedVariant ? this.selectedVariant.label : '';
        },
        openProducts() {
            this.product.open = true;
            this.product.highlight = Math.max(0, this.filteredProducts.findIndex(p => String(p.value) === String(this.product.value)));
            this.product.style = window.ssMenuStyle(this.$refs.productSearch);
            this.$nextTick(() => this.$refs.productSearch.select());
        },
        openVariants() {
            if (!this.product.value) return;
            this.variant.open = true;
            this.variant.highlight = Math.max(0, this.filteredVariants.findIndex(v => String(v.value) === String(this.variant.value)));
            this.variant.style = window.ssMenuStyle(this.$refs.variantSearch);
            this.$nextTick(() => this.$refs.variantSearch.select());
        },
        // Los menús son position:fixed, así que hay que recolocarlos al hacer scroll.
        reposition() {
            if (this.product.open) this.product.style = window.ssMenuStyle(this.$refs.productSearch);
            if (this.variant.open) this.variant.style = window.ssMenuStyle(this.$refs.variantSearch);
        },
        moveProduct(step) {
            if (!this.product.open) this.openProducts();
            const max = this.filteredProducts.length - 1;
            if (max < 0) return;
            this.product.highlight = Math.min(max, Math.max(0, this.product.highlight + step));
            this.scrollIntoView('productMenu');
        },
        moveVariant(step) {
            if (!this.variant.open) this.openVariants();
            const max = this.filteredVariants.length - 1;
            if (max < 0) return;
            this.variant.highlight = Math.min(max, Math.max(0, this.variant.highlight + step));
            this.scrollIntoView('variantMenu');
        },
        scrollIntoView(menuRef) {
            this.$nextTick(() => {
                const active = this.$refs[menuRef].querySelector('.ss-option.is-active');
                if (active) active.scrollIntoView({ block: 'nearest' });
            });
        },
        chooseProduct(option) {
            if (!option) return;
            this.product.value = String(option.value);
            this.product.query = option.label;
            this.product.open = false;
            // Las variantes se llenan solas: si hay una sola, queda seleccionada.
            this.variant.value = '';
            this.variant.query = '';
            this.variant.highlight = 0;
            // Si el producto tiene una sola variante, queda elegida de una vez.
            if (option.variants.length === 1) {
                this.chooseVariant(option.variants[0]);
            }
            // El foco abre la lista de variantes (ver openVariants).
            this.$nextTick(() => this.$refs.variantSearch.focus());
        },
        chooseVariant(option) {
            if (!option) return;
            this.variant.value = String(option.value);
            this.variant.query = option.label;
            this.variant.open = false;
        },
        addItem() {
            const variant = this.selectedVariant;
            const qty = parseInt(this.qty);

            if (!variant || !qty || qty < 1) {
                return alert('Selecciona una variante y una cantidad válida.');
            }
            if (this.items.some(item => String(item.id) === String(variant.value))) {
                return alert('Esta variante ya fue agregada.');
            }
            // El servidor rechaza la orden completa si no hay inventario, así que lo cortamos aquí.
            if (qty > variant.stock) {
                return alert('Solo hay ' + variant.stock + ' en inventario de esta variante.');
            }

            this.items.push({
                id: variant.value,
                label: this.selectedProduct.label + ' - ' + variant.label,
                precio: variant.precio,
                qty: qty,
            });

            this.product.value = '';
            this.product.query = '';
            this.variant.value = '';
            this.variant.query = '';
            this.qty = 1;
            this.$nextTick(() => this.$refs.productSearch.focus());
        },
        removeItem(id) {
            this.items = this.items.filter(item => String(item.id) !== String(id));
        },
    };
};
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