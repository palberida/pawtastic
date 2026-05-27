<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Metabot · Nuevo anuncio') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-2 lg:px-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if($errors->any())
                        <div style="color: red;">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('metabot.ads.store') }}">
                        @csrf

                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700">Nombre (referencia interna)</label>
                            <input type="text" name="name" id="name" maxlength="150" value="{{ old('name') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        </div>

                        <div class="mt-3">
                            <label for="source_id" class="block text-sm font-medium text-gray-700">source_id del anuncio</label>
                            <input type="text" name="source_id" id="source_id" maxlength="128" value="{{ old('source_id') }}" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <p class="text-xs text-gray-400 mt-1">El <code>referral.source_id</code> que llega cuando el cliente da clic. Aparece en <code>metabot_events</code>.</p>
                        </div>

                        <div class="mt-3">
                            <label for="scope" class="block text-sm font-medium text-gray-700">Alcance</label>
                            <select id="scope" name="scope" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="product_set" {{ old('scope', 'product_set') === 'product_set' ? 'selected' : '' }}>Set de productos</option>
                                <option value="site_wide" {{ old('scope') === 'site_wide' ? 'selected' : '' }}>Todo el catálogo</option>
                            </select>
                        </div>

                        <div class="mt-3">
                            <label for="status" class="block text-sm font-medium text-gray-700">Estado</label>
                            <select id="status" name="status" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Activo</option>
                                <option value="paused" {{ old('status') === 'paused' ? 'selected' : '' }}>Pausado</option>
                            </select>
                        </div>

                        <div class="mt-3">
                            <label for="welcome_text" class="block text-sm font-medium text-gray-700">Saludo personalizado (opcional)</label>
                            <textarea name="welcome_text" id="welcome_text" rows="2" maxlength="1024" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('welcome_text') }}</textarea>
                        </div>

                        <div class="mt-3" id="product_picker">
                            <label class="block text-sm font-medium text-gray-700">Productos del set</label>
                            <p class="text-xs text-gray-400 mb-2">Solo aplica para "Set de productos".</p>
                            @php $checked = old('product_ids', $selected ?? []); @endphp
                            <div class="border border-gray-200 rounded-md max-h-80 overflow-y-auto p-3 space-y-1">
                                @foreach ($products as $product)
                                    <label class="flex items-center text-sm">
                                        <input type="checkbox" name="product_ids[]" value="{{ $product->id }}" {{ in_array($product->id, $checked) ? 'checked' : '' }} class="mr-2 rounded border-gray-300">
                                        <span>{{ $product->descripcion }} @if($product->codigo)<span class="text-gray-400">({{ $product->codigo }})</span>@endif</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var scope  = document.getElementById('scope');
        var picker = document.getElementById('product_picker');
        function toggle() { picker.style.display = scope.value === 'product_set' ? 'block' : 'none'; }
        scope.addEventListener('change', toggle);
        toggle();
    })();
    </script>
</x-app-layout>
