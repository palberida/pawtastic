{{-- params: $tagName, $valName, $rows (collection|null), $isVariant (bool) --}}
@php($rows = $rows ?? collect())
<div class="tag-editor" data-tag-name="{{ $tagName }}" data-val-name="{{ $valName }}" @if(!empty($isVariant)) data-variant="1" @endif>
    <div class="tag-rows">
        @forelse($rows as $t)
            <div class="tag-row" style="display:flex;gap:8px;margin-bottom:6px;align-items:center;">
                <input name="{{ $tagName }}" value="{{ $t->tag }}" placeholder="tag" maxlength="50" class="border-gray-300 rounded-md shadow-sm sm:text-sm" style="flex:0 0 35%;">
                <input name="{{ $valName }}" value="{{ $t->valor }}" placeholder="valor" maxlength="500" class="border-gray-300 rounded-md shadow-sm sm:text-sm" style="flex:1;">
                <button type="button" class="tag-remove" title="Quitar" style="color:#dc2626;background:none;border:none;cursor:pointer;font-size:18px;line-height:1;">✕</button>
            </div>
        @empty
            <div class="tag-row" style="display:flex;gap:8px;margin-bottom:6px;align-items:center;">
                <input name="{{ $tagName }}" value="" placeholder="tag" maxlength="50" class="border-gray-300 rounded-md shadow-sm sm:text-sm" style="flex:0 0 35%;">
                <input name="{{ $valName }}" value="" placeholder="valor" maxlength="500" class="border-gray-300 rounded-md shadow-sm sm:text-sm" style="flex:1;">
                <button type="button" class="tag-remove" title="Quitar" style="color:#dc2626;background:none;border:none;cursor:pointer;font-size:18px;line-height:1;">✕</button>
            </div>
        @endforelse
    </div>
    <div style="margin-top:6px;display:flex;gap:8px;flex-wrap:wrap;">
        <button type="button" class="tag-add bg-gray-100 text-gray-700 px-3 py-1 rounded hover:bg-gray-200" style="font-size:13px;">+ Agregar tag</button>
        @if(!empty($isVariant))
            <button type="button" class="copy-to-all bg-blue-100 text-blue-700 px-3 py-1 rounded hover:bg-blue-200" style="font-size:13px;">Copiar a todas las variantes</button>
        @endif
    </div>
</div>
