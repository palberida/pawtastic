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
        <div class="max-w-3xl mx-auto sm:px-2 lg:px-4 space-y-6">

            @if (session('success'))
                <div class="text-green-600">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="text-red-600 text-sm">{{ $errors->first() }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-1">Tags del producto</h3>
                <p class="text-xs text-gray-400 mb-4">Nivel producto (id_variante NULL). Aquí van <code>categoria</code>, <code>pivot</code> y fotos de respaldo <code>image_1</code>… La sincronización de Shopify no toca estos tags.</p>
                @include('metabot.tags._editor', ['action' => route('metabot.tags.product.save', ['id' => $product->id]), 'tags' => $productTags])
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-4">Variantes</h3>
                @forelse($variants as $v)
                    <a href="{{ route('metabot.tags.variant', ['id' => $v->id]) }}"
                       class="flex justify-between items-center py-3 px-2 border-b border-gray-100 hover:bg-gray-50">
                        <div class="min-w-0">
                            <div class="font-medium text-gray-700">{{ $v->descripcion ?: ('Variante ' . $v->id) }}</div>
                            <div class="text-xs text-gray-400">
                                {{ $v->codigo ? $v->codigo . ' · ' : '' }}Q{{ $v->precio }} · stock {{ $v->stock }}
                            </div>
                        </div>
                        <span class="text-xs text-gray-400 whitespace-nowrap ml-4">{{ $variantTagCounts[$v->id] ?? 0 }} tags</span>
                    </a>
                @empty
                    <p class="text-gray-400">Este producto no tiene variantes.</p>
                @endforelse
            </div>

        </div>
    </div>
</x-app-layout>
