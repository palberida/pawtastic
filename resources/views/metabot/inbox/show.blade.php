<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center" style="gap:10px;">
                @include('metabot.inbox._avatar', ['name' => $name ?? null, 'phone' => $phone, 'size' => 32])
                <span>@if(!empty($name)){{ $name }} <span class="text-sm text-gray-400 font-normal">+{{ $phone }}</span>@else+{{ $phone }}@endif</span>
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
                    @if (session('success'))
                        <div class="mb-4 text-green-600">{{ session('success') }}</div>
                    @endif

                    <div id="thread" class="max-h-96 overflow-y-auto border border-gray-100 rounded-md p-3 bg-gray-50">
                        @include('metabot.inbox._thread')
                    </div>

                    @if(!empty($quickMenu))
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-medium text-gray-700">Respuestas rápidas</label>
                                <button type="button" id="qr-back" style="display:none;font-size:13px;color:#2563eb;background:none;border:none;cursor:pointer;">← Atrás</button>
                            </div>
                            <p id="qr-crumb" class="text-xs text-gray-400 mb-2">Elige una categoría.</p>
                            <div id="qr-buttons" class="flex flex-wrap" style="gap:8px;"></div>
                            <div id="qr-photos" style="display:none;" class="mt-3"></div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('metabot.inbox.reply', ['phone' => $phone]) }}" class="mt-4">
                        @csrf
                        <textarea name="body" id="reply-body" rows="2" maxlength="4096" required placeholder="Escribe una respuesta..." class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">{{ old('body') }}</textarea>
                        <div class="mt-2 flex justify-between items-center">
                            <span class="text-xs text-gray-400">Solo se puede responder dentro de las 24h del último mensaje del cliente.</span>
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">Enviar</button>
                        </div>
                    </form>

                    <form method="POST" action="{{ route('metabot.inbox.image', ['phone' => $phone]) }}" enctype="multipart/form-data" class="mt-4 pt-4 border-t border-gray-200">
                        @csrf
                        <label for="image" class="block text-sm font-medium text-gray-700">Enviar una imagen</label>
                        <input type="file" name="image" id="image" accept="image/jpeg,image/png" required class="mt-1 block w-full text-sm text-gray-600">
                        <input type="text" name="caption" maxlength="1024" placeholder="Descripción (opcional)" class="mt-2 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <div class="mt-2 flex justify-between items-center">
                            <span class="text-xs text-gray-400">JPG o PNG, máx 5MB. Solo dentro de la ventana de 24h.</span>
                            <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 transition">Enviar imagen</button>
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

        // --- Quick replies: Categorías → Productos → grupos de tags (Precio / color / Medidas / talla / Fotos…) ---
        var MENU       = @json($quickMenu ?? []);
        var CSRF       = "{{ csrf_token() }}";
        var PHOTOS_URL = "{{ route('metabot.inbox.quickphotos', ['phone' => $phone]) }}";
        var qrButtons  = document.getElementById('qr-buttons');
        var qrBack     = document.getElementById('qr-back');
        var qrCrumb    = document.getElementById('qr-crumb');
        var qrPhotos   = document.getElementById('qr-photos');
        var replyBox   = document.getElementById('reply-body');

        if (qrButtons) {
            var state = { level: 0, cat: null, prod: null };

            function pill(label, opts) {
                opts = opts || {};
                var b = document.createElement('button');
                b.type = 'button';
                b.textContent = label;
                b.disabled = !!opts.disabled;
                b.style.cssText = 'font-size:13px;padding:6px 12px;border-radius:9999px;border:1px solid #d1d5db;background:' +
                    (opts.disabled ? '#f3f4f6' : (opts.accent || '#fff')) + ';color:' +
                    (opts.disabled ? '#9ca3af' : (opts.color || '#374151')) + ';cursor:' +
                    (opts.disabled ? 'not-allowed' : 'pointer') + ';';
                if (!opts.disabled && opts.onClick) b.addEventListener('click', opts.onClick);
                return b;
            }

            function clearPhotos() { qrPhotos.style.display = 'none'; qrPhotos.innerHTML = ''; }

            function fillReply(text) {
                if (!replyBox || !text) return;
                replyBox.value = text;
                replyBox.focus();
                replyBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            function showPhotos(p) {
                qrPhotos.innerHTML = '';
                var note = document.createElement('p');
                note.style.cssText = 'font-size:12px;color:#6b7280;margin-bottom:6px;';
                note.textContent = 'Se enviarán estas fotos al cliente:';
                qrPhotos.appendChild(note);

                var gallery = document.createElement('div');
                gallery.style.cssText = 'display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px;';
                p.images.forEach(function (url) {
                    var img = document.createElement('img');
                    img.src = url;
                    img.style.cssText = 'width:64px;height:64px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb;';
                    gallery.appendChild(img);
                });
                qrPhotos.appendChild(gallery);

                var form = document.createElement('form');
                form.method = 'POST';
                form.action = PHOTOS_URL;
                form.innerHTML = '<input type="hidden" name="_token" value="' + CSRF + '">' +
                    '<input type="hidden" name="product_id" value="' + p.id + '">';
                var send = document.createElement('button');
                send.type = 'submit';
                send.textContent = 'Enviar ' + p.images.length + ' foto(s)';
                send.style.cssText = 'font-size:13px;padding:6px 14px;border-radius:6px;border:none;background:#f59e0b;color:#fff;cursor:pointer;';
                form.appendChild(send);
                qrPhotos.appendChild(form);

                qrPhotos.style.display = '';
                qrPhotos.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            function render() {
                qrButtons.innerHTML = '';
                clearPhotos();
                if (state.level === 0) {
                    qrBack.style.display = 'none';
                    qrCrumb.textContent = 'Elige una categoría.';
                    MENU.forEach(function (g, i) {
                        qrButtons.appendChild(pill(g.categoria + ' (' + g.products.length + ')', {
                            accent: '#eef2ff', color: '#3730a3',
                            onClick: function () { state.level = 1; state.cat = i; render(); }
                        }));
                    });
                } else if (state.level === 1) {
                    qrBack.style.display = '';
                    var g = MENU[state.cat];
                    qrCrumb.textContent = g.categoria + ' · elige un producto.';
                    g.products.forEach(function (p, j) {
                        qrButtons.appendChild(pill(p.nombre, {
                            onClick: function () { state.level = 2; state.prod = j; render(); }
                        }));
                    });
                } else {
                    qrBack.style.display = '';
                    var p = MENU[state.cat].products[state.prod];
                    qrCrumb.textContent = p.nombre + ' · elige qué enviar.';
                    var groups = p.groups || [];
                    if (!groups.length) {
                        var none = document.createElement('span');
                        none.style.cssText = 'font-size:13px;color:#9ca3af;';
                        none.textContent = 'Este producto no tiene tags todavía.';
                        qrButtons.appendChild(none);
                    }
                    groups.forEach(function (grp) {
                        var accent, color;
                        if (grp.type === 'photos')      { accent = '#fef3c7'; color = '#92400e'; }
                        else if (grp.key === 'precio')  { accent = '#ecfdf5'; color = '#065f46'; }
                        else if (grp.key === 'medidas') { accent = '#eff6ff'; color = '#1e40af'; }
                        else                            { accent = '#f5f3ff'; color = '#5b21b6'; }
                        qrButtons.appendChild(pill(grp.label, {
                            accent: accent, color: color,
                            onClick: function () {
                                if (grp.type === 'photos') { showPhotos(p); }
                                else { fillReply(grp.text); }
                            }
                        }));
                    });
                }
            }

            qrBack.addEventListener('click', function () {
                if (state.level === 2) { state.level = 1; }
                else if (state.level === 1) { state.level = 0; }
                render();
            });

            render();
        }
    })();
    </script>
</x-app-layout>
