{{--
    Dropdown con búsqueda (Alpine).
    Uso:
        <x-searchable-select name="vendedor" :options="[['value' => 'A', 'label' => 'A']]" :selected="'A'" />
    Envía el valor en un input hidden con el nombre indicado.
--}}
@props([
    'name',
    'options' => [],
    'selected' => null,
    'placeholder' => 'Buscar...',
    'emptyText' => 'Sin resultados',
    'disabled' => false,
    'required' => false,
])

<div
    x-data="searchableSelect({
        options: {{ json_encode(array_values($options), JSON_UNESCAPED_UNICODE) }},
        selected: {{ json_encode($selected, JSON_UNESCAPED_UNICODE) }}
    })"
    @click.outside="close()"
    @scroll.window="reposition()"
    @resize.window="reposition()"
    class="relative"
>
    <input type="hidden" name="{{ $name }}" x-model="value">

    <input
        type="text"
        x-ref="search"
        x-model="query"
        @focus="open()"
        @click="open()"
        @keydown.arrow-down.prevent="move(1)"
        @keydown.arrow-up.prevent="move(-1)"
        @keydown.enter.prevent="choose(filtered[highlight])"
        @keydown.escape.prevent="close()"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        @if($disabled) disabled @endif
        @if($required) required @endif
        {{ $attributes->merge(['class' => 'ss-input mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm']) }}
    >

    @unless($required)
    <button
        type="button"
        x-show="value"
        style="display: none"
        @click="clear()"
        class="ss-clear"
        title="Limpiar"
    >&times;</button>
    @endunless

    <ul x-show="isOpen" :style="menuStyle" style="display: none" class="ss-menu">
        <template x-if="!filtered.length">
            <li class="ss-empty">{{ $emptyText }}</li>
        </template>
        <template x-for="(option, i) in filtered" :key="option.value">
            <li
                @click="choose(option)"
                @mouseenter="highlight = i"
                :class="{ 'is-active': highlight === i, 'is-selected': option.value == value }"
                class="ss-option"
                x-text="option.label"
            ></li>
        </template>
    </ul>
</div>

@once
{{--
    El CSS compilado (public/build) no incluye varias utilidades de Tailwind que usa este
    combobox, y no se reconstruye en cada deploy, así que los estilos estructurales van aquí.
    El picker de productos de orders/add reutiliza estas mismas clases .ss-*.
--}}
<style>
    /* Posición fija: el menú tiene que poder salirse del .overflow-hidden de la tarjeta.
       top/left/width/max-height los calcula ssMenuStyle() sobre el input. */
    .ss-menu { position: fixed; z-index: 50; overflow-y: auto; background: #fff; border: 1px solid #d1d5db; border-radius: .375rem; box-shadow: 0 10px 15px -3px rgba(0,0,0,.1); font-size: .875rem; }
    .ss-option { display: flex; justify-content: space-between; gap: .5rem; padding: .5rem .75rem; cursor: pointer; }
    .ss-option.is-active { background: #eef2ff; }
    .ss-option.is-selected { font-weight: 600; }
    .ss-empty { padding: .5rem .75rem; color: #6b7280; }
    .ss-clear { position: absolute; right: .5rem; top: .25rem; bottom: 0; display: flex; align-items: center; color: #9ca3af; font-size: 1.125rem; line-height: 1; }
    .ss-clear:hover { color: #4b5563; }
    .ss-input:disabled { background: #f3f4f6; }
</style>
<script>
// Calcula la posición del menú sobre el input, abriéndolo hacia arriba si no cabe abajo.
window.ssMenuStyle = function (input) {
    const rect = input.getBoundingClientRect();
    const margin = 8;
    const below = window.innerHeight - rect.bottom - margin;
    const above = rect.top - margin;
    const openUp = below < 200 && above > below;
    const space = openUp ? above : below;

    return {
        left: rect.left + 'px',
        width: rect.width + 'px',
        maxHeight: Math.max(120, Math.min(240, space)) + 'px',
        top: openUp ? 'auto' : (rect.bottom + 4) + 'px',
        bottom: openUp ? (window.innerHeight - rect.top + 4) + 'px' : 'auto',
    };
};

window.searchableSelect = function ({ options = [], selected = null }) {
    return {
        options,
        value: selected ?? '',
        query: '',
        isOpen: false,
        highlight: 0,
        menuStyle: {},
        init() {
            this.syncLabel();
        },
        get selectedOption() {
            return this.options.find(o => String(o.value) === String(this.value)) || null;
        },
        get filtered() {
            const q = this.query.trim().toLowerCase();
            if (!q || (this.selectedOption && this.selectedOption.label === this.query)) {
                return this.options;
            }
            const terms = q.split(/\s+/);
            return this.options.filter(o => {
                const label = o.label.toLowerCase();
                return terms.every(t => label.includes(t));
            });
        },
        syncLabel() {
            this.query = this.selectedOption ? this.selectedOption.label : '';
        },
        open() {
            this.isOpen = true;
            this.highlight = Math.max(0, this.filtered.findIndex(o => String(o.value) === String(this.value)));
            this.menuStyle = window.ssMenuStyle(this.$refs.search);
            this.$nextTick(() => this.$refs.search.select());
        },
        reposition() {
            if (this.isOpen) this.menuStyle = window.ssMenuStyle(this.$refs.search);
        },
        close() {
            this.isOpen = false;
            this.syncLabel();
        },
        move(step) {
            if (!this.isOpen) this.open();
            const max = this.filtered.length - 1;
            if (max < 0) return;
            this.highlight = Math.min(max, Math.max(0, this.highlight + step));
            this.$nextTick(() => {
                const active = this.$el.querySelector('.ss-option.is-active');
                if (active) active.scrollIntoView({ block: 'nearest' });
            });
        },
        choose(option) {
            if (!option) return;
            this.value = String(option.value);
            this.query = option.label;
            this.isOpen = false;
            this.$el.dispatchEvent(new CustomEvent('selected', { detail: option, bubbles: true }));
        },
        clear() {
            this.value = '';
            this.query = '';
            this.highlight = 0;
            this.$el.dispatchEvent(new CustomEvent('selected', { detail: null, bubbles: true }));
            this.$refs.search.focus();
        },
    };
};
</script>
@endonce
