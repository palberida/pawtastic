<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Tags · ') }}{{ $product->descripcion }}
            </h2>
            <a href="{{ route('metabot.tags.index') }}" class="text-sm text-blue-600 hover:underline">← Productos</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-2 lg:px-4">

            @if (session('success'))
                <div class="mb-4 text-green-600">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-4 text-red-600 text-sm">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('metabot.tags.product.save', ['id' => $product->id]) }}">
                @csrf

                <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="font-semibold text-gray-800 mb-1">Tags del producto</h3>
                    <p class="text-xs text-gray-400 mb-4">Nivel producto (id_variante NULL): <code>categoria</code>, <code>pivot</code>, fotos de respaldo <code>image_1</code>… La sincronización de Shopify no toca estos tags.</p>
                    @include('metabot.tags._inline', ['tagName' => 'tags[]', 'valName' => 'values[]', 'rows' => $productTags])
                </div>

                <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="font-semibold text-gray-800 mb-1">Variantes</h3>
                    <p class="text-xs text-amber-600 mb-4">⚠ La sincronización de Shopify puede sobrescribir los tags de variante. Para que perduren, ponlos en el metafield <code>ossu_tags</code> en Shopify.</p>

                    @forelse($variants as $v)
                        <div class="border border-gray-100 rounded-md p-4 mb-4">
                            <div class="flex justify-between items-baseline mb-2">
                                <span class="font-medium text-gray-700">{{ $v->descripcion ?: ('Variante ' . $v->id) }}</span>
                                <span class="text-xs text-gray-400">{{ $v->codigo ? $v->codigo . ' · ' : '' }}Q{{ $v->precio }} · stock {{ $v->stock }}</span>
                            </div>
                            @include('metabot.tags._inline', [
                                'tagName'   => 'variant_tags[' . $v->id . '][]',
                                'valName'   => 'variant_values[' . $v->id . '][]',
                                'rows'      => $variantTags[$v->id] ?? collect(),
                                'isVariant' => true,
                            ])
                        </div>
                    @empty
                        <p class="text-gray-400">Este producto no tiene variantes.</p>
                    @endforelse
                </div>

                <div class="sticky bottom-0 bg-gray-100 py-3 -mx-2 px-2 sm:rounded-b-lg">
                    <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600 shadow">Guardar todo</button>
                </div>
            </form>

        </div>
    </div>

    <script>
    (function () {
        function makeRow(tagName, valName, tag, val) {
            var div = document.createElement('div');
            div.className = 'tag-row';
            div.style.cssText = 'display:flex;gap:8px;margin-bottom:6px;align-items:center;';
            var t = document.createElement('input');
            t.name = tagName; t.placeholder = 'tag'; t.maxLength = 50;
            t.className = 'border-gray-300 rounded-md shadow-sm sm:text-sm'; t.style.flex = '0 0 35%'; t.value = tag || '';
            var v = document.createElement('input');
            v.name = valName; v.placeholder = 'valor'; v.maxLength = 500;
            v.className = 'border-gray-300 rounded-md shadow-sm sm:text-sm'; v.style.flex = '1'; v.value = val || '';
            var x = document.createElement('button');
            x.type = 'button'; x.className = 'tag-remove'; x.textContent = '✕';
            x.style.cssText = 'color:#dc2626;background:none;border:none;cursor:pointer;font-size:18px;line-height:1;';
            div.appendChild(t); div.appendChild(v); div.appendChild(x);
            return div;
        }
        function pairs(editor) {
            return Array.prototype.map.call(editor.querySelectorAll('.tag-row'), function (r) {
                var ins = r.querySelectorAll('input');
                return { tag: ins[0].value, val: ins[1].value };
            }).filter(function (p) { return p.tag.trim() !== ''; });
        }
        document.addEventListener('click', function (e) {
            var t = e.target;
            if (t.classList.contains('tag-remove')) {
                t.closest('.tag-row').remove();
            } else if (t.classList.contains('tag-add')) {
                var ed = t.closest('.tag-editor');
                ed.querySelector('.tag-rows').appendChild(makeRow(ed.dataset.tagName, ed.dataset.valName, '', ''));
            } else if (t.classList.contains('copy-to-all')) {
                var src = t.closest('.tag-editor');
                var data = pairs(src);
                document.querySelectorAll('.tag-editor[data-variant]').forEach(function (ed) {
                    if (ed === src) return;
                    var rows = ed.querySelector('.tag-rows');
                    rows.innerHTML = '';
                    if (data.length === 0) {
                        rows.appendChild(makeRow(ed.dataset.tagName, ed.dataset.valName, '', ''));
                    } else {
                        data.forEach(function (p) { rows.appendChild(makeRow(ed.dataset.tagName, ed.dataset.valName, p.tag, p.val)); });
                    }
                });
                t.textContent = '✓ Copiado';
                setTimeout(function () { t.textContent = 'Copiar a todas las variantes'; }, 1500);
            }
        });
    })();
    </script>
</x-app-layout>
