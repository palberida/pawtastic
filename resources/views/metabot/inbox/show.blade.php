<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Metabot · ') }}+{{ $phone }}
            </h2>
            <a href="{{ route('metabot.inbox.index') }}" class="text-sm text-blue-600 hover:underline">← Bandeja</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-2 lg:px-4">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    @if (session('error'))
                        <div class="mb-4 text-red-600">{{ session('error') }}</div>
                    @endif

                    <div id="thread" class="max-h-96 overflow-y-auto border border-gray-100 rounded-md p-3 bg-gray-50">
                        @include('metabot.inbox._thread')
                    </div>

                    <form method="POST" action="{{ route('metabot.inbox.reply', ['phone' => $phone]) }}" class="mt-4">
                        @csrf
                        <textarea name="body" rows="2" maxlength="4096" required placeholder="Escribe una respuesta..." class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('body') }}</textarea>
                        <div class="mt-2 flex justify-between items-center">
                            <span class="text-xs text-gray-400">Solo se puede responder dentro de las 24h del último mensaje del cliente.</span>
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">Enviar</button>
                        </div>
                    </form>

                    @if($templates->isNotEmpty())
                        <div class="mt-6 pt-4 border-t border-gray-200">
                            <label for="template_id" class="block text-sm font-medium text-gray-700">Reabrir conversación con una plantilla</label>
                            <p class="text-xs text-gray-400 mb-2">Úsala cuando ya pasaron 24h del último mensaje del cliente. Cada envío tiene costo (mensaje de plantilla de Meta).</p>
                            <form method="POST" action="{{ route('metabot.inbox.template', ['phone' => $phone]) }}" onsubmit="return confirm('Se enviará una plantilla pagada para reabrir la conversación. ¿Continuar?');">
                                @csrf
                                <select name="template_id" id="template_id" required class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @foreach($templates as $t)
                                        <option value="{{ $t->id }}" data-body="{{ $t->body_preview }}">{{ $t->label ?: $t->name }}</option>
                                    @endforeach
                                </select>
                                <p id="template_preview" class="text-sm text-gray-600 mt-2 italic"></p>
                                <div class="mt-2 text-right">
                                    <button type="submit" class="bg-amber-500 text-white px-4 py-2 rounded hover:bg-amber-600 transition">Enviar plantilla</button>
                                </div>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var url    = "{{ route('metabot.inbox.messages', ['phone' => $phone]) }}";
        var thread = document.getElementById('thread');
        function scrollBottom() { thread.scrollTop = thread.scrollHeight; }
        function poll() {
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.text(); })
                .then(function (html) { thread.innerHTML = html; scrollBottom(); })
                .catch(function () {});
        }
        scrollBottom();
        setInterval(poll, 7000);

        var sel = document.getElementById('template_id');
        var prev = document.getElementById('template_preview');
        if (sel && prev) {
            function showPreview() {
                var opt = sel.options[sel.selectedIndex];
                prev.textContent = opt ? (opt.getAttribute('data-body') || '') : '';
            }
            sel.addEventListener('change', showPreview);
            showPreview();
        }
    })();
    </script>
</x-app-layout>
