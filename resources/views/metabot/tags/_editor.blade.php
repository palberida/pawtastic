@php($rows = $tags ?? collect())
<form method="POST" action="{{ $action }}">
    @csrf
    <div id="tag-rows">
        @forelse($rows as $t)
            <div class="tag-row" style="display:flex;gap:8px;margin-bottom:8px;align-items:center;">
                <input name="tags[]" value="{{ $t->tag }}" placeholder="tag (ej. talla)" maxlength="50" class="border-gray-300 rounded-md shadow-sm sm:text-sm" style="flex:0 0 35%;">
                <input name="values[]" value="{{ $t->valor }}" placeholder="valor (ej. M)" maxlength="500" class="border-gray-300 rounded-md shadow-sm sm:text-sm" style="flex:1;">
                <button type="button" class="tag-remove" title="Quitar" style="color:#dc2626;background:none;border:none;cursor:pointer;font-size:18px;line-height:1;">✕</button>
            </div>
        @empty
            <div class="tag-row" style="display:flex;gap:8px;margin-bottom:8px;align-items:center;">
                <input name="tags[]" value="" placeholder="tag (ej. talla)" maxlength="50" class="border-gray-300 rounded-md shadow-sm sm:text-sm" style="flex:0 0 35%;">
                <input name="values[]" value="" placeholder="valor (ej. M)" maxlength="500" class="border-gray-300 rounded-md shadow-sm sm:text-sm" style="flex:1;">
                <button type="button" class="tag-remove" title="Quitar" style="color:#dc2626;background:none;border:none;cursor:pointer;font-size:18px;line-height:1;">✕</button>
            </div>
        @endforelse
    </div>

    <div style="margin-top:8px;display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        <button type="button" id="tag-add" class="bg-gray-100 text-gray-700 px-3 py-1 rounded hover:bg-gray-200" style="font-size:13px;">+ Agregar tag</button>

        @if(!empty($copyOptions) && count($copyOptions))
            <span style="margin-left:auto;font-size:13px;color:#6b7280;">Copiar tags desde:</span>
            <select id="copy-from" class="border-gray-300 rounded-md shadow-sm sm:text-sm" style="font-size:13px;">
                <option value="">— variante —</option>
                @foreach($copyOptions as $opt)
                    <option value="{{ $opt['id'] }}">{{ $opt['label'] }}</option>
                @endforeach
            </select>
            <button type="button" id="copy-btn" class="bg-blue-100 text-blue-700 px-3 py-1 rounded hover:bg-blue-200" style="font-size:13px;">Copiar</button>
        @endif
    </div>

    <div style="margin-top:16px;">
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Guardar</button>
    </div>
</form>

<script>
(function () {
    var rows = document.getElementById('tag-rows');
    function addRow(tag, val) {
        var div = document.createElement('div');
        div.className = 'tag-row';
        div.style.cssText = 'display:flex;gap:8px;margin-bottom:8px;align-items:center;';
        var t = document.createElement('input');
        t.name = 'tags[]'; t.placeholder = 'tag (ej. talla)'; t.maxLength = 50;
        t.className = 'border-gray-300 rounded-md shadow-sm sm:text-sm'; t.style.flex = '0 0 35%';
        t.value = tag || '';
        var v = document.createElement('input');
        v.name = 'values[]'; v.placeholder = 'valor (ej. M)'; v.maxLength = 500;
        v.className = 'border-gray-300 rounded-md shadow-sm sm:text-sm'; v.style.flex = '1';
        v.value = val || '';
        var x = document.createElement('button');
        x.type = 'button'; x.textContent = '✕';
        x.style.cssText = 'color:#dc2626;background:none;border:none;cursor:pointer;font-size:18px;line-height:1;';
        x.addEventListener('click', function () { div.remove(); });
        div.appendChild(t); div.appendChild(v); div.appendChild(x);
        rows.appendChild(div);
    }
    document.querySelectorAll('.tag-remove').forEach(function (b) {
        b.addEventListener('click', function () { b.closest('.tag-row').remove(); });
    });
    var addBtn = document.getElementById('tag-add');
    if (addBtn) addBtn.addEventListener('click', function () { addRow('', ''); });

    var SIBLING_TAGS = @json($siblingTags ?? null);
    var copyBtn = document.getElementById('copy-btn');
    var copySel = document.getElementById('copy-from');
    if (copyBtn && copySel && SIBLING_TAGS) {
        copyBtn.addEventListener('click', function () {
            var id = copySel.value;
            if (!id || !SIBLING_TAGS[id]) return;
            rows.innerHTML = '';
            SIBLING_TAGS[id].forEach(function (t) { addRow(t.tag, t.valor); });
        });
    }
})();
</script>
