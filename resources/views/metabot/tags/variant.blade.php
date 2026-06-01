<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Tags variante · ') }}{{ $variant->descripcion ?: ('Variante ' . $variant->id) }}
            </h2>
            @if($product)
                <a href="{{ route('metabot.tags.product', ['id' => $product->id]) }}" class="text-sm text-blue-600 hover:underline">← {{ $product->descripcion }}</a>
            @endif
        </div>
    </x-slot>

    @php
        $copyOptions = $siblings->map(fn ($s) => [
            'id'    => $s->id,
            'label' => ($s->descripcion ?: ('Variante ' . $s->id)) . ($s->codigo ? ' (' . $s->codigo . ')' : ''),
        ])->all();
    @endphp

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-2 lg:px-4 space-y-6">

            @if (session('success'))
                <div class="text-green-600">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="text-red-600 text-sm">{{ $errors->first() }}</div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-semibold text-gray-800 mb-1">Tags de la variante</h3>
                <p class="text-xs text-gray-400 mb-1">
                    {{ $variant->codigo ? $variant->codigo . ' · ' : '' }}Q{{ $variant->precio }} · stock {{ $variant->stock }}
                </p>
                <p class="text-xs text-amber-600 mb-4">⚠ Nivel variante: la sincronización de Shopify puede sobrescribir estos tags. Para que perduren, ponlos en el metafield <code>ossu_tags</code> del producto en Shopify.</p>

                @include('metabot.tags._editor', [
                    'action'      => route('metabot.tags.variant.save', ['id' => $variant->id]),
                    'tags'        => $tags,
                    'copyOptions' => $copyOptions,
                    'siblingTags' => $siblingTags,
                ])
            </div>

        </div>
    </div>
</x-app-layout>
